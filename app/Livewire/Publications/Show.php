<?php

namespace App\Livewire\Publications;

use App\Models\AppSetting;
use App\Models\Publication;
use Livewire\Component;

class Show extends Component
{
    public Publication $publication;

    public function mount(string $slug)
    {
        $this->publication = Publication::where('slug', $slug)->published()->with('season')->firstOrFail();
    }

    public function render()
    {
        $user = auth()->user();
        $hasAccess = $user->hasAccessToPublication($this->publication);

        return view('livewire.publications.show', [
            'hasAccess' => $hasAccess,
            'season' => $this->publication->season,
            'subscriptionAccessEnabled' => AppSetting::subscriptionAccessEnabled(),
        ])->layout('layouts.app', ['title' => $this->publication->title]);
    }
}
