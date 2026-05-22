<?php

namespace App\Models;

use App\Support\ContentDisk;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Season extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'year',
        'description',
        'cover_image',
        'subscription_price',
        'status',
    ];

    protected $casts = [
        'subscription_price' => 'decimal:2',
        'year' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Season $season) {
            if (empty($season->slug)) {
                $season->slug = Str::slug($season->name.'-'.$season->year);
            }
        });
    }

    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class)->orderBy('sort_order');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function discountCodes(): HasMany
    {
        return $this->hasMany(DiscountCode::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        if (! $this->cover_image) {
            return null;
        }

        if (! ContentDisk::isGoogle()) {
            return Storage::disk(ContentDisk::name())->url($this->cover_image);
        }

        $disk = Storage::disk('gcs');

        if ($disk->providesTemporaryUrls()) {
            try {
                return $disk->temporaryUrl($this->cover_image, now()->addDays(7));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $disk->url($this->cover_image);
    }

    public function getPublishedPublicationsCountAttribute(): int
    {
        return $this->publications()->where('status', 'published')->count();
    }

    /**
     * True if this season has any published publication that still ships as print + digital.
     */
    public function hasPublishedPrintTitles(): bool
    {
        return $this->publications()
            ->where('status', 'published')
            ->where('is_digital_only', false)
            ->exists();
    }

    /**
     * Short label for placeholders, e.g. SS26 (Spring/Summer 2026) or AW25 (Autumn/Winter 2025).
     */
    public function getSeasonCodeAttribute(): string
    {
        $yy = str_pad((string) ($this->year % 100), 2, '0', STR_PAD_LEFT);
        $n = strtolower($this->name);

        $hasSpring = str_contains($n, 'spring');
        $hasSummer = str_contains($n, 'summer');
        $hasAutumn = str_contains($n, 'autumn');
        $hasFall = str_contains($n, 'fall');
        $hasWinter = str_contains($n, 'winter');

        if (($hasSpring && $hasSummer) || str_contains($n, 'spring/summer') || str_contains($n, 'spring summer')) {
            return 'SS'.$yy;
        }

        if (($hasAutumn && $hasWinter) || ($hasFall && $hasWinter)
            || str_contains($n, 'autumn/winter') || str_contains($n, 'autumn winter')
            || str_contains($n, 'fall/winter') || str_contains($n, 'a/w')) {
            return 'AW'.$yy;
        }

        if (($hasSpring || $hasSummer) && ! $hasWinter && ! $hasAutumn && ! $hasFall) {
            return 'SS'.$yy;
        }

        if (($hasWinter || $hasAutumn || $hasFall) && ! $hasSpring && ! $hasSummer) {
            return 'AW'.$yy;
        }

        return strtoupper(mb_substr($this->name, 0, 2)).$yy;
    }
}
