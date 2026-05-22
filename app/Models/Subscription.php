<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'season_id',
        'is_free',
        'granted_by',
        'square_payment_id',
        'amount_paid',
        'delivery_required',
        'delivery_name',
        'delivery_address_line_1',
        'delivery_address_line_2',
        'delivery_city',
        'delivery_county',
        'delivery_postcode',
        'delivery_country',
        'delivery_phone',
        'starts_at',
        'expires_at',
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'amount_paid' => 'decimal:2',
        'delivery_required' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function isActive(): bool
    {
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
