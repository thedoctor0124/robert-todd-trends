<?php

namespace App\Livewire\Admin;

use App\Models\AccessInvite;
use App\Models\Publication;
use App\Models\Season;
use App\Models\User;
use App\Services\AccessInviteService;
use InvalidArgumentException;
use Livewire\Component;

class SendAccess extends Component
{
    public string $recipientMode = 'existing';

    public ?int $userId = null;

    public string $email = '';

    public string $invitedName = '';

    public string $company = '';

    public string $accessType = 'publication';

    public int $grantItemId = 0;

    public ?string $sentClaimUrl = null;

    /**
     * Outcome of the last send or resend, rendered inline on the page. Kept as
     * component state rather than a session flash so the message sits next to
     * the form and the delivery error is readable in full.
     */
    public ?string $feedbackStatus = null;

    public string $feedbackMessage = '';

    public function mount(): void
    {
        if ($userId = request()->integer('user')) {
            $user = User::find($userId);
            if ($user) {
                $this->recipientMode = 'existing';
                $this->userId = $user->id;
                $this->email = $user->email;
                $this->invitedName = $user->name;
                $this->company = (string) $user->company;
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
                $this->company = (string) $user->company;
            }
        }
    }

    public function sendInvite(AccessInviteService $service): void
    {
        $this->feedbackStatus = null;
        $this->feedbackMessage = '';

        $this->validate($this->rules());

        $company = trim($this->company);

        $existingUser = null;
        if ($this->recipientMode === 'existing') {
            $existingUser = User::findOrFail($this->userId);
            $this->email = $existingUser->email;

            // The field is prefilled from the account, so treat an edit here as
            // an update to it rather than silently discarding what was typed.
            if ($company !== (string) $existingUser->company) {
                $existingUser->update(['company' => $company === '' ? null : $company]);
                $existingUser->refresh();
            }
        }

        $invite = $service->createAndSend(
            email: $this->email,
            accessType: $this->accessType,
            itemId: $this->grantItemId,
            grantedBy: auth()->user()->email,
            existingUser: $existingUser,
            invitedName: $this->recipientMode === 'new' ? $this->invitedName : null,
            invitedCompany: $company !== '' ? $company : null,
        );

        $this->sentClaimUrl = $invite->claimUrl();
        $this->reset(['grantItemId']);
        $this->grantItemId = 0;

        if ($invite->deliveryFailed()) {
            // The invite exists and its link works, but nobody has been told.
            $this->feedbackStatus = 'error';
            $this->feedbackMessage = 'Access link created for '.$invite->email.
                ', but the email could NOT be sent: '.$invite->send_error;

            return;
        }

        $this->feedbackStatus = 'success';
        $this->feedbackMessage = 'Access link created and emailed to '.$invite->email.'.';
    }

    public function resendInvite(int $inviteId, AccessInviteService $service): void
    {
        $this->feedbackStatus = null;
        $this->feedbackMessage = '';

        $invite = AccessInvite::findOrFail($inviteId);

        try {
            $service->resend($invite);
        } catch (InvalidArgumentException $e) {
            $this->feedbackStatus = 'error';
            $this->feedbackMessage = $e->getMessage();

            return;
        }

        if ($invite->deliveryFailed()) {
            $this->feedbackStatus = 'error';
            $this->feedbackMessage = 'Still could not email '.$invite->email.': '.$invite->send_error;

            return;
        }

        $this->sentClaimUrl = $invite->claimUrl();
        $this->feedbackStatus = 'success';
        $this->feedbackMessage = 'Invite re-sent to '.$invite->email.'.';
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
            'undeliveredInvites' => AccessInvite::with(['publication', 'season'])
                ->undelivered()
                ->orderByDesc('created_at')
                ->get(),
        ])->layout('layouts.admin', ['title' => 'Send Access Link']);
    }

    private function rules(): array
    {
        $rules = [
            'company' => 'nullable|string|max:255',
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
