<?php

namespace App\Services;

use App\Mail\FreeAccessInvite;
use App\Models\AccessInvite;
use App\Models\Publication;
use App\Models\Season;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AccessInviteService
{
    public function createAndSend(
        string $email,
        string $accessType,
        int $itemId,
        string $grantedBy,
        ?User $existingUser = null,
        ?string $invitedName = null,
        int $expiresInDays = 30,
    ): AccessInvite {
        $email = strtolower(trim($email));

        $invite = match ($accessType) {
            'publication' => $this->makePublicationInvite($email, $itemId, $grantedBy, $existingUser, $invitedName, $expiresInDays),
            'subscription' => $this->makeSubscriptionInvite($email, $itemId, $grantedBy, $existingUser, $invitedName, $expiresInDays),
            default => throw new InvalidArgumentException('Invalid access type.'),
        };

        try {
            Mail::to($email)->send(new FreeAccessInvite($invite));
        } catch (\Throwable $e) {
            report($e);
        }

        return $invite;
    }

    public function redeem(AccessInvite $invite, User $user): void
    {
        if (! $invite->isValid()) {
            throw new InvalidArgumentException('This invite link is no longer valid.');
        }

        if (strtolower($user->email) !== strtolower($invite->email)) {
            throw new InvalidArgumentException('Please sign in with '.$invite->email.' to claim this access.');
        }

        $accessService = new AccessService;

        if ($invite->access_type === 'publication') {
            $publication = $invite->publication ?? Publication::findOrFail($invite->publication_id);
            $accessService->grantPublicationAccess(
                $user,
                $publication,
                $invite->granted_by,
                isFree: true,
                sendEmail: false,
            );
        } else {
            $season = $invite->season ?? Season::findOrFail($invite->season_id);
            $accessService->grantSeasonSubscription(
                $user,
                $season,
                $invite->granted_by,
                isFree: true,
                sendEmail: false,
            );
        }

        $invite->update([
            'redeemed_at' => now(),
            'redeemed_by_user_id' => $user->id,
        ]);
    }

    private function makePublicationInvite(
        string $email,
        int $publicationId,
        string $grantedBy,
        ?User $existingUser,
        ?string $invitedName,
        int $expiresInDays,
    ): AccessInvite {
        $publication = Publication::with('season')->findOrFail($publicationId);

        return AccessInvite::create([
            'token' => Str::random(64),
            'email' => $email,
            'invited_name' => $invitedName ?? $existingUser?->name,
            'user_id' => $existingUser?->id,
            'access_type' => 'publication',
            'publication_id' => $publication->id,
            'season_id' => null,
            'granted_by' => $grantedBy,
            'expires_at' => now()->addDays($expiresInDays),
        ]);
    }

    private function makeSubscriptionInvite(
        string $email,
        int $seasonId,
        string $grantedBy,
        ?User $existingUser,
        ?string $invitedName,
        int $expiresInDays,
    ): AccessInvite {
        $season = Season::findOrFail($seasonId);

        return AccessInvite::create([
            'token' => Str::random(64),
            'email' => $email,
            'invited_name' => $invitedName ?? $existingUser?->name,
            'user_id' => $existingUser?->id,
            'access_type' => 'subscription',
            'publication_id' => null,
            'season_id' => $season->id,
            'granted_by' => $grantedBy,
            'expires_at' => now()->addDays($expiresInDays),
        ]);
    }
}
