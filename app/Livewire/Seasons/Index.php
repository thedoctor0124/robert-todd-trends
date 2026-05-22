<?php

namespace App\Livewire\Seasons;

use App\Models\Season;
use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        return view('livewire.seasons.index', [
            'seasons' => Season::published()->orderByDesc('year')->orderBy('name')->get(),
        ])->layout('layouts.app', ['title' => 'Seasons']);
    }
}
