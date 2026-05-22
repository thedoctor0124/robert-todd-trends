<?php

namespace App\Livewire\Account;

use Livewire\Component;

class Orders extends Component
{
    public function render()
    {
        $user = auth()->user();

        return view('livewire.account.orders', [
            'purchases' => $user->purchases()->with('publication.season')->latest()->get(),
            'subscriptions' => $user->subscriptions()->with('season')->latest()->get(),
        ])->layout('layouts.app', ['title' => 'Orders']);
    }
}
