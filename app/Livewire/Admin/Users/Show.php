<?php

namespace App\Livewire\Admin\Users;

use App\Models\Publication;
use App\Models\Season;
use App\Models\User;
use App\Services\AccessService;
use Livewire\Component;

class Show extends Component
{
    public User $user;

    public string $grantType = 'publication';

    public int $grantItemId = 0;

    public bool $granted = false;

    public function mount(User $user)
    {
        $this->user = $user;
    }

    public function toggleAdmin()
    {
        $this->user->update(['is_admin' => ! $this->user->is_admin]);
        $this->user->refresh();
    }

    public function grantAccess()
    {
        $this->validate([
            'grantItemId' => 'required|integer|min:1',
        ]);

        $service = new AccessService;

        if ($this->grantType === 'publication') {
            $publication = Publication::findOrFail($this->grantItemId);
            $service->grantPublicationAccess($this->user, $publication, auth()->user()->email);
        } else {
            $season = Season::findOrFail($this->grantItemId);
            $service->grantSeasonSubscription($this->user, $season, auth()->user()->email);
        }

        $this->granted = true;
        $this->grantItemId = 0;
        $this->user->refresh();
    }

    public function revokePublication(int $purchaseId)
    {
        $this->user->purchases()->where('id', $purchaseId)->delete();
        $this->user->refresh();
    }

    public function revokeSubscription(int $subscriptionId)
    {
        $this->user->subscriptions()->where('id', $subscriptionId)->delete();
        $this->user->refresh();
    }

    public function render()
    {
        return view('livewire.admin.users.show', [
            'purchases' => $this->user->purchases()->with('publication.season')->get(),
            'subscriptions' => $this->user->subscriptions()->with('season')->get(),
            'allSeasons' => Season::orderByDesc('year')->get(),
            'allPublications' => Publication::with('season')->orderBy('title')->get(),
        ])->layout('layouts.admin', ['title' => $this->user->name]);
    }
}
