<?php

namespace App\Livewire\Publications;

use App\Models\Publication;
use Livewire\Component;

class Viewer extends Component
{
    public Publication $publication;

    public function mount(string $slug)
    {
        $this->publication = Publication::where('slug', $slug)
            ->published()
            ->with('season.publications')
            ->firstOrFail();

        if (! auth()->user()->hasAccessToPublication($this->publication)) {
            return redirect()->route('publications.show', $this->publication->slug);
        }
    }

    public function render()
    {
        $seasonPublications = $this->publication->season
            ->publications()
            ->published()
            ->orderBy('sort_order')
            ->get();

        return view('livewire.publications.viewer', [
            'seasonPublications' => $seasonPublications,
            'previous' => $this->publication->previous,
            'next' => $this->publication->next,
        ])->layout('layouts.app', ['title' => $this->publication->title]);
    }
}
