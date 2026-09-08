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
    /** Company dropdown sentinel: reach the users who have no company yet. */
    public const NO_COMPANY = '__none';

    /** Company dropdown sentinel: reveal a text box for a company not yet known. */
    public const NEW_COMPANY = '__new';

    public string $recipientMode = 'existing';

    public ?int $userId = null;

    public string $email = '';

    public string $invitedName = '';

    /**
     * Either a known company name, one of the sentinels above, or '' for none.
     * It does double duty: it narrows the customer list, and it is the company
     * assigned to whoever is picked.
     */
    public string $company = '';

    public string $newCompany = '';

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

    public function updatedCompany(): void
    {
        if ($this->company !== self::NEW_COMPANY) {
            $this->newCompany = '';
        }

        // The selected customer is deliberately left alone. Narrowing to
        // "No company set", picking someone, then switching to a real company
        // is how an unassigned user gets one, so the choice has to survive.
        $this->resetValidation();
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

                // Do not clobber a company the admin has already chosen: that
                // choice is what assigns a company to an unassigned user.
                if ($this->company === '') {
                    $this->company = (string) $user->company;
                }
            }
        }
    }

    /**
     * The company to assign, or null to leave the account as it is. '' and the
     * "No company set" sentinel both mean "no company was chosen".
     */
    public function resolveCompany(): ?string
    {
        if ($this->company === self::NEW_COMPANY) {
            return trim($this->newCompany) ?: null;
        }

        if ($this->company === '' || $this->company === self::NO_COMPANY) {
            return null;
        }

        return trim($this->company) ?: null;
    }

    public function sendInvite(AccessInviteService $service): void
    {
        $this->feedbackStatus = null;
        $this->feedbackMessage = '';

        $this->validate($this->rules());

        $company = $this->resolveCompany();

        $existingUser = null;
        if ($this->recipientMode === 'existing') {
            $existingUser = User::findOrFail($this->userId);
            $this->email = $existingUser->email;

            // Assigning a company is the point of choosing one here, so write
            // it through. A blank choice leaves whatever they already have.
            if ($company !== null && $company !== $existingUser->company) {
                $existingUser->update(['company' => $company]);
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
            invitedCompany: $company,
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
            'users' => $this->customerOptions(),
            'companies' => User::query()
                ->whereNotNull('company')
                ->where('company', '!=', '')
                ->distinct()
                ->orderBy('company')
                ->pluck('company'),
            'missingCompanyCount' => User::query()
                ->where(fn ($q) => $q->whereNull('company')->orWhere('company', ''))
                ->count(),
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

    /**
     * Customers matching the company choice, in company-then-user order.
     *
     * The currently selected customer is always included even when they fall
     * outside the filter: switching the company after picking someone is how an
     * unassigned user is given one, and losing the selection would break that.
     */
    private function customerOptions()
    {
        $narrowing = match (true) {
            $this->company === self::NO_COMPANY => fn ($q) => $q->whereNull('company')->orWhere('company', ''),
            $this->company !== '' && $this->company !== self::NEW_COMPANY => fn ($q) => $q->where('company', $this->company),
            default => null,
        };

        // No company chosen means every customer is a candidate; only add the
        // "keep the selected one" clause when something is actually narrowing.
        if ($narrowing === null) {
            return User::query()->orderBy('name')->get(['id', 'name', 'email', 'company']);
        }

        return User::query()
            ->where(function ($q) use ($narrowing) {
                $q->where($narrowing);

                if ($this->userId) {
                    $q->orWhere('id', $this->userId);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'company']);
    }

    private function rules(): array
    {
        $rules = [
            'company' => 'nullable|string|max:255',
            'newCompany' => $this->company === self::NEW_COMPANY
                ? 'required|string|max:255'
                : 'nullable|string|max:255',
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
