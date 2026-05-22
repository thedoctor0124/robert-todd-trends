<?php

namespace App\Livewire;

use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();

        return view('livewire.dashboard', [
            'subscriptions' => $user->subscriptions()->with('season')->latest()->get(),
            'purchases' => $user->purchases()->with('publication.season')->latest()->get(),
        ])->layout('layouts.app', ['title' => 'My Library']);
    }
}
