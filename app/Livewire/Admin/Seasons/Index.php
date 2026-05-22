<?php

namespace App\Livewire\Admin\Seasons;

use App\Models\Season;
use Livewire\Component;

class Index extends Component
{
    public function delete(int $id)
    {
        Season::findOrFail($id)->delete();
        session()->flash('success', 'Season deleted.');
    }

    public function render()
    {
        return view('livewire.admin.seasons.index', [
            'seasons' => Season::withCount('publications', 'subscriptions')->orderByDesc('year')->get(),
        ])->layout('layouts.admin', ['title' => 'Seasons']);
    }
}
