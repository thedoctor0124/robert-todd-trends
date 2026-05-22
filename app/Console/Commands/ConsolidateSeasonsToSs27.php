<?php

namespace App\Console\Commands;

use App\Models\DiscountCode;
use App\Models\Publication;
use App\Models\Season;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConsolidateSeasonsToSs27 extends Command
{
    protected $signature = 'seasons:consolidate-ss27
        {--dry-run : Show the plan without changing the database}
        {--force : Required to actually delete seasons (safety guard)}';

    protected $description = 'Move every publication into SS27 (Spring/Summer 2027), merge subscriptions, remove other seasons';

    public function handle(): int
    {
        if (! $this->option('dry-run') && ! $this->option('force')) {
            $this->error('Refusing to change data without --force (use --dry-run to preview).');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $target = Season::query()->get()->first(fn (Season $s) => $s->season_code === 'SS27');

        if (! $target) {
            $basis = Season::query()->orderByDesc('updated_at')->first();
            $this->warn('No SS27 season found (Spring/Summer, year 2027).');

            if ($dryRun) {
                $this->line('Would create: Spring/Summer, year 2027, slug spring-summer-2027');

                return self::SUCCESS;
            }

            $target = Season::create([
                'name' => 'Spring/Summer',
                'slug' => 'spring-summer-2027',
                'year' => 2027,
                'description' => $basis?->description ?? '',
                'cover_image' => $basis?->cover_image,
                'subscription_price' => $basis?->subscription_price ?? 149,
                'status' => 'published',
            ]);
            $this->info("Created SS27 season id {$target->id}.");
        } else {
            $this->info("Using SS27: id {$target->id} — {$target->name} ({$target->year}).");
        }

        $oldIds = Season::query()->where('id', '!=', $target->id)->pluck('id');

        if ($oldIds->isEmpty()) {
            $this->info('No other seasons to remove.');

            return self::SUCCESS;
        }

        $pubCount = Publication::query()->whereIn('season_id', $oldIds)->count();
        $subCount = Subscription::query()->whereIn('season_id', $oldIds)->count();
        $discCount = DiscountCode::query()->whereIn('season_id', $oldIds)->count();

        $this->table(
            ['Item', 'Count'],
            [
                ['Publications on other seasons', $pubCount],
                ['Subscriptions on other seasons', $subCount],
                ['Discount codes tied to other seasons', $discCount],
                ['Seasons to delete', $oldIds->count()],
            ]
        );

        if ($dryRun) {
            $this->comment('Dry run only — no changes.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($target, $oldIds): void {
            Publication::query()->whereIn('season_id', $oldIds)->update(['season_id' => $target->id]);

            $order = 1;
            foreach (
                Publication::query()
                    ->where('season_id', $target->id)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->cursor() as $publication
            ) {
                $publication->update(['sort_order' => $order]);
                $order++;
            }

            $userIds = Subscription::query()
                ->whereIn('season_id', $oldIds)
                ->pluck('user_id')
                ->merge(
                    Subscription::query()
                        ->where('season_id', $target->id)
                        ->pluck('user_id')
                )
                ->unique()
                ->values();

            foreach ($userIds as $userId) {
                $subs = Subscription::query()
                    ->where('user_id', $userId)
                    ->where(function ($q) use ($oldIds, $target) {
                        $q->whereIn('season_id', $oldIds)
                            ->orWhere('season_id', $target->id);
                    })
                    ->get();

                if ($subs->count() <= 1) {
                    $one = $subs->first();
                    if ($one && (int) $one->season_id !== (int) $target->id) {
                        $one->update(['season_id' => $target->id]);
                    }

                    continue;
                }

                $keeper = $subs->sortByDesc(function (Subscription $s) {
                    return $s->expires_at?->getTimestamp() ?? $s->created_at?->getTimestamp() ?? 0;
                })->first();

                $summedPaid = $subs->sum(fn (Subscription $s) => (float) $s->amount_paid);

                foreach ($subs as $sub) {
                    if ($sub->id === $keeper->id) {
                        $sub->update([
                            'season_id' => $target->id,
                            'amount_paid' => $summedPaid,
                        ]);
                    } else {
                        $sub->delete();
                    }
                }
            }

            DiscountCode::query()->whereIn('season_id', $oldIds)->update(['season_id' => $target->id]);

            Season::query()->whereIn('id', $oldIds)->delete();
        });

        $this->info('Done. All publications are on SS27; other seasons removed.');

        return self::SUCCESS;
    }
}
