<?php

namespace App\Services;

use App\Mail\FreeAccessGranted;
use App\Models\Publication;
use App\Models\Purchase;
use App\Models\Season;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class AccessService
{
    public function grantPublicationAccess(
        User $user,
        Publication $publication,
        string $grantedBy,
        bool $isFree = true,
        ?string $squarePaymentId = null,
        float $amountPaid = 0,
        ?string $discountCode = null,
        ?array $deliveryAddress = null
    ): Purchase {
        $purchase = Purchase::updateOrCreate(
            ['user_id' => $user->id, 'publication_id' => $publication->id],
            array_merge([
                'is_free' => $isFree,
                'granted_by' => $isFree ? $grantedBy : null,
                'square_payment_id' => $squarePaymentId,
                'discount_code_used' => $discountCode,
                'amount_paid' => $amountPaid,
            ], $this->deliveryAddressAttributes($deliveryAddress))
        );

        if ($isFree) {
            Mail::to($user)->send(new FreeAccessGranted($user, 'publication', $publication));
        }

        return $purchase;
    }

    public function grantSeasonSubscription(
        User $user,
        Season $season,
        string $grantedBy,
        bool $isFree = true,
        ?string $squarePaymentId = null,
        float $amountPaid = 0,
        ?array $deliveryAddress = null
    ): Subscription {
        $subscription = Subscription::updateOrCreate(
            ['user_id' => $user->id, 'season_id' => $season->id],
            array_merge([
                'is_free' => $isFree,
                'granted_by' => $isFree ? $grantedBy : null,
                'square_payment_id' => $squarePaymentId,
                'amount_paid' => $amountPaid,
                'starts_at' => now(),
            ], $this->deliveryAddressAttributes($deliveryAddress))
        );

        if ($isFree) {
            Mail::to($user)->send(new FreeAccessGranted($user, 'subscription', $season));
        }

        return $subscription;
    }

    public function revokePublicationAccess(User $user, Publication $publication): void
    {
        Purchase::where('user_id', $user->id)
            ->where('publication_id', $publication->id)
            ->delete();
    }

    public function revokeSeasonSubscription(User $user, Season $season): void
    {
        Subscription::where('user_id', $user->id)
            ->where('season_id', $season->id)
            ->delete();
    }

    private function deliveryAddressAttributes(?array $deliveryAddress): array
    {
        if (! $deliveryAddress) {
            return ['delivery_required' => false];
        }

        return [
            'delivery_required' => true,
            'delivery_name' => $deliveryAddress['name'],
            'delivery_address_line_1' => $deliveryAddress['address_line_1'],
            'delivery_address_line_2' => $deliveryAddress['address_line_2'] ?? null,
            'delivery_city' => $deliveryAddress['city'],
            'delivery_county' => $deliveryAddress['county'] ?? null,
            'delivery_postcode' => $deliveryAddress['postcode'],
            'delivery_country' => $deliveryAddress['country'],
            'delivery_phone' => $deliveryAddress['phone'] ?? null,
        ];
    }
}
