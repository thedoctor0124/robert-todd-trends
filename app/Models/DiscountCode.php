<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountCode extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'season_id',
        'publication_id',
        'usage_limit',
        'times_used',
        'expires_at',
        'active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'usage_limit' => 'integer',
        'times_used' => 'integer',
        'expires_at' => 'date',
        'active' => 'boolean',
    ];

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }

    public function isValid(): bool
    {
        if (! $this->active) {
            return false;
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }
        if ($this->usage_limit && $this->times_used >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $originalPrice): float
    {
        if ($this->type === 'percentage') {
            return round($originalPrice * ($this->value / 100), 2);
        }

        return min($this->value, $originalPrice);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
