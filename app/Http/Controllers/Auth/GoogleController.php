<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AccessInvite;
use App\Models\User;
use App\Services\AccessInviteService;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect()
    {
        if (request()->boolean('popup')) {
            session(['google_oauth_popup' => true]);
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->user();

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
            ]);
        } else {
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'is_admin' => str_ends_with($googleUser->getEmail(), '@roberttodds.com'),
            ]);
        }

        Auth::login($user, true);
        session()->regenerate();

        if (session()->pull('google_oauth_popup')) {
            return response()->view('auth.popup-complete');
        }

        if ($token = session()->pull('pending_access_invite_token')) {
            $invite = AccessInvite::query()
                ->where('token', $token)
                ->with(['publication', 'season'])
                ->first();

            if ($invite && $invite->isValid() && strtolower($user->email) === strtolower($invite->email)) {
                try {
                    (new AccessInviteService)->redeem($invite, $user);

                    return redirect($invite->redirectAfterClaim());
                } catch (\InvalidArgumentException) {
                    // Fall through to dashboard
                }
            }
        }

        return redirect()->intended(route('dashboard'));
    }
}
