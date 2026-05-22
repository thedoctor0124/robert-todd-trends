<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    protected $fillable = [
        'user_id',
        'publication_id',
        'is_free',
        'granted_by',
        'square_payment_id',
        'discount_code_used',
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
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'amount_paid' => 'decimal:2',
        'delivery_required' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }
}
