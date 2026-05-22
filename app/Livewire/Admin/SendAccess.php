<?php

namespace App\Livewire\Admin;

use App\Models\AccessInvite;
use App\Models\Publication;
use App\Models\Season;
use App\Models\User;
use App\Services\AccessInviteService;
use Livewire\Component;

class SendAccess extends Component
{
    public string $recipientMode = 'existing';

    public ?int $userId = null;

    public string $email = '';

    public string $invitedName = '';

    public string $accessType = 'publication';

    public int $grantItemId = 0;

    public ?string $sentClaimUrl = null;

    public function mount(): void
    {
        if ($userId = request()->integer('user')) {
            $user = User::find($userId);
            if ($user) {
                $this->recipientMode = 'existing';
                $this->userId = $user->id;
                $this->email = $user->email;
                $this->invitedName = $user->name;
            }
        }

        if ($publicationId = request()->integer('publication')) {
            $this->accessType = 'publication';
            $this->grantItemId = $publicationId;
        }
    }

    public function updatedRecipientMode(): void
    {
        if ($this->recipientMode === 'new') {
            $this->userId = null;
        }
    }

    public function updatedUserId(): void
    {
        if ($this->userId) {
            $user = User::find($this->userId);
            if ($user) {
                $this->email = $user->email;
                $this->invitedName = $user->name;
            }
        }
    }

    public function sendInvite(AccessInviteService $service): void
    {
        $this->validate($this->rules());

        $existingUser = null;
        if ($this->recipientMode === 'existing') {
            $existingUser = User::findOrFail($this->userId);
            $this->email = $existingUser->email;
        }

        $invite = $service->createAndSend(
            email: $this->email,
            accessType: $this->accessType,
            itemId: $this->grantItemId,
            grantedBy: auth()->user()->email,
            existingUser: $existingUser,
            invitedName: $this->recipientMode === 'new' ? $this->invitedName : null,
        );

        $this->sentClaimUrl = $invite->claimUrl();
        $this->reset(['grantItemId']);
        $this->grantItemId = 0;

        session()->flash('success', 'Access link created for '.$invite->email.'. An email was sent when mail is configured.');
    }

    public function render()
    {
        return view('livewire.admin.send-access', [
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'allSeasons' => Season::orderByDesc('year')->get(),
            'allPublications' => Publication::with('season')->orderBy('title')->get(),
            'recentInvites' => AccessInvite::with(['publication', 'season', 'user'])
                ->orderByDesc('created_at')
                ->limit(15)
                ->get(),
        ])->layout('layouts.admin', ['title' => 'Send Access Link']);
    }

    private function rules(): array
    {
        $rules = [
            'recipientMode' => 'required|in:existing,new',
            'accessType' => 'required|in:publication,subscription',
            'grantItemId' => 'required|integer|min:1',
            'email' => 'required|email',
        ];

        if ($this->recipientMode === 'existing') {
            $rules['userId'] = 'required|exists:users,id';
        } else {
            $rules['invitedName'] = 'required|string|max:255';
            $rules['email'] = 'required|email|unique:users,email';
        }

        return $rules;
    }
}
