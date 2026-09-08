<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessInvite extends Model
{
    protected $fillable = [
        'token',
        'email',
        'invited_name',
        'invited_company',
        'user_id',
        'access_type',
        'publication_id',
        'season_id',
        'granted_by',
        'expires_at',
        'redeemed_at',
        'redeemed_by_user_id',
        'sent_at',
        'send_failed_at',
        'send_error',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'redeemed_at' => 'datetime',
            'sent_at' => 'datetime',
            'send_failed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function redeemedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by_user_id');
    }

    public function isRedeemed(): bool
    {
        return $this->redeemed_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return ! $this->isRedeemed() && ! $this->isExpired();
    }

    public function deliveryFailed(): bool
    {
        return $this->send_failed_at !== null;
    }

    public function wasDelivered(): bool
    {
        return $this->sent_at !== null && $this->send_failed_at === null;
    }

    /**
     * Invites created before delivery tracking existed have no record either
     * way, so they are reported as unknown rather than assumed successful.
     */
    public function deliveryIsUnknown(): bool
    {
        return $this->sent_at === null && $this->send_failed_at === null;
    }

    /**
     * True when someone should act: the invite is still usable but the email
     * did not reach the recipient.
     */
    public function needsResend(): bool
    {
        return $this->deliveryFailed() && $this->isValid();
    }

    public function scopeUndelivered($query)
    {
        return $query->whereNotNull('send_failed_at')
            ->whereNull('redeemed_at')
            ->where('expires_at', '>', now());
    }

    public function claimUrl(): string
    {
        return route('access-invite.claim', $this->token);
    }

    public function itemTitle(): string
    {
        if ($this->access_type === 'publication' && $this->publication) {
            return $this->publication->title;
        }

        if ($this->access_type === 'subscription' && $this->season) {
            return $this->season->name.' ('.$this->season->year.')';
        }

        return 'your content';
    }

    public function redirectAfterClaim(): string
    {
        if ($this->access_type === 'publication' && $this->publication) {
            return route('publications.viewer', $this->publication->slug);
        }

        if ($this->access_type === 'subscription' && $this->season) {
            return route('seasons.show', $this->season->slug);
        }

        return route('dashboard');
    }
}
