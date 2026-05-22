<?php

namespace App\Livewire\AccessInvite;

use App\Models\AccessInvite;
use App\Models\User;
use App\Services\AccessInviteService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Claim extends Component
{
    public AccessInvite $invite;

    public string $authMode = 'login';

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $errorMessage = null;

    public function mount(string $token): void
    {
        $this->invite = AccessInvite::query()
            ->where('token', $token)
            ->with(['publication.season', 'season'])
            ->firstOrFail();

        $this->name = $this->invite->invited_name ?? '';
        $this->email = $this->invite->email;
        session(['pending_access_invite_token' => $token]);

        if (! $this->invite->isValid()) {
            $this->errorMessage = $this->invite->isRedeemed()
                ? 'This access link has already been used.'
                : 'This access link has expired. Please contact us for a new link.';

            return;
        }

        if (Auth::check() && $this->emailsMatch(Auth::user())) {
            $this->completeClaim();
        }
    }

    public function login(): void
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $this->ensureEmailMatchesInvite($this->email);

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], remember: true)) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        session()->regenerate();
        $this->completeClaim();
    }

    public function register(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        $this->ensureEmailMatchesInvite($this->email);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'is_admin' => str_ends_with($this->email, '@roberttodds.com'),
        ]);

        Auth::login($user);
        session()->regenerate();

        $this->completeClaim();
    }

    public function claimAsCurrentUser(): void
    {
        if (! Auth::check()) {
            return;
        }

        $this->completeClaim();
    }

    public function render()
    {
        return view('livewire.access-invite.claim', [
            'existingAccount' => User::where('email', $this->invite->email)->first(),
        ])->layout('layouts.guest', ['title' => 'Claim access']);
    }

    private function completeClaim(): void
    {
        if ($this->errorMessage) {
            return;
        }

        $user = Auth::user();

        if (! $this->emailsMatch($user)) {
            $this->errorMessage = 'You are signed in as '.$user->email.'. Please sign out and use '.$this->invite->email.' to claim this access.';

            return;
        }

        try {
            (new AccessInviteService)->redeem($this->invite->fresh(['publication.season', 'season']), $user);
        } catch (\InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();

            return;
        }

        $this->redirect($this->invite->redirectAfterClaim(), navigate: true);
    }

    private function ensureEmailMatchesInvite(string $email): void
    {
        if (strtolower(trim($email)) !== strtolower($this->invite->email)) {
            throw ValidationException::withMessages([
                'email' => 'This link was sent to '.$this->invite->email.'. Please use that email address.',
            ]);
        }
    }

    private function emailsMatch(User $user): bool
    {
        return strtolower($user->email) === strtolower($this->invite->email);
    }
}
