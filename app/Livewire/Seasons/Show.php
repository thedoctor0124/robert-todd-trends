<?php

namespace App\Livewire\Seasons;

use App\Models\AppSetting;
use App\Models\Season;
use Illuminate\Support\Collection;
use Livewire\Component;

class Show extends Component
{
    public Season $season;

    public function mount(string $slug)
    {
        $this->season = Season::where('slug', $slug)->published()->firstOrFail();
    }

    public function render()
    {
        $user = auth()->user();
        $publications = $this->season->publications()->published()->orderBy('sort_order')->get();
        $subscriptionAccessEnabled = AppSetting::subscriptionAccessEnabled();
        $hasSubscription = $user && $user->hasSubscription($this->season);
        $purchasedPublicationIds = $this->purchasedPublicationIds($publications, $hasSubscription);
        // One list: featured first (each in sort_order), then the rest in sort_order.
        $publicationsForGrid = $publications
            ->sortBy(fn ($p) => [$p->is_featured ? 0 : 1, $p->sort_order])
            ->values();

        return view('livewire.seasons.show', [
            'publications' => $publications,
            'publicationsForGrid' => $publicationsForGrid,
            'hasSubscription' => $hasSubscription,
            'purchasedPublicationIds' => $purchasedPublicationIds,
            'subscriptionAccessEnabled' => $subscriptionAccessEnabled,
        ])->layout('layouts.app', ['title' => $this->season->name]);
    }

    private function purchasedPublicationIds(Collection $publications, bool $hasSubscription): Collection
    {
        $user = auth()->user();

        if (! $user || $hasSubscription || $user->isRobertToddsEmail()) {
            return collect();
        }

        return $user->purchases()
            ->whereIn('publication_id', $publications->pluck('id'))
            ->pluck('publication_id');
    }
}
