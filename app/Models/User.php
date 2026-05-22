<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function hasAccessToPublication(Publication $publication): bool
    {
        if ($this->isRobertToddsEmail()) {
            return true;
        }

        return $this->purchases()->where('publication_id', $publication->id)->exists()
            || (AppSetting::subscriptionAccessEnabled()
                && $this->subscriptions()->where('season_id', $publication->season_id)->exists());
    }

    public function hasSubscription(Season $season): bool
    {
        if ($this->isRobertToddsEmail()) {
            return true;
        }

        if (! AppSetting::subscriptionAccessEnabled()) {
            return false;
        }

        return $this->subscriptions()->where('season_id', $season->id)->exists();
    }

    public function hasPurchased(Publication $publication): bool
    {
        if ($this->isRobertToddsEmail()) {
            return true;
        }

        return $this->purchases()->where('publication_id', $publication->id)->exists();
    }

    public function isRobertToddsEmail(): bool
    {
        return str_ends_with($this->email, '@roberttodds.com');
    }
}
