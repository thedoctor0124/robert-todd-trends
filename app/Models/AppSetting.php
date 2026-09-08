<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class AppSetting extends Model
{
    public const SUBSCRIPTION_ACCESS_ENABLED = 'subscription_access_enabled';

    public const ORDER_NOTIFICATION_EMAIL = 'order_notification_email';

    public const MAIL_HOST = 'mail_host';

    public const MAIL_PORT = 'mail_port';

    public const MAIL_ENCRYPTION = 'mail_encryption';

    public const MAIL_USERNAME = 'mail_username';

    public const MAIL_PASSWORD = 'mail_password';

    public const MAIL_FROM_ADDRESS = 'mail_from_address';

    public const MAIL_FROM_NAME = 'mail_from_name';

    protected $fillable = [
        'key',
        'value',
    ];

    private static array $runtimeCache = [];

    public static function subscriptionAccessEnabled(): bool
    {
        return static::bool(self::SUBSCRIPTION_ACCESS_ENABLED, false);
    }

    public static function setSubscriptionAccessEnabled(bool $enabled): void
    {
        static::setBool(self::SUBSCRIPTION_ACCESS_ENABLED, $enabled);
    }

    public static function orderNotificationEmail(): string
    {
        return static::string(self::ORDER_NOTIFICATION_EMAIL, 'neil@roberttodds.com');
    }

    public static function setOrderNotificationEmail(string $email): void
    {
        static::setString(self::ORDER_NOTIFICATION_EMAIL, $email);
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = static::getValue($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function setBool(string $key, bool $value): void
    {
        if (! static::settingsTableExists()) {
            return;
        }

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value ? '1' : '0'],
        );

        static::$runtimeCache[$key] = $value ? '1' : '0';
    }

    public static function string(string $key, string $default = ''): string
    {
        $value = static::getValue($key);

        return $value === null ? $default : (string) $value;
    }

    public static function setString(string $key, string $value): void
    {
        if (! static::settingsTableExists()) {
            return;
        }

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );

        static::$runtimeCache[$key] = $value;
    }

    /**
     * Read a value that was stored with setEncrypted(). Returns the default when
     * the value is missing or cannot be decrypted (e.g. after an APP_KEY change),
     * so a rotated key surfaces as "not configured" rather than a hard failure.
     */
    public static function encrypted(string $key, string $default = ''): string
    {
        $value = static::getValue($key);

        if ($value === null || $value === '') {
            return $default;
        }

        try {
            return (string) Crypt::decryptString($value);
        } catch (\Throwable) {
            return $default;
        }
    }

    public static function setEncrypted(string $key, string $value): void
    {
        if ($value === '') {
            static::setString($key, '');

            return;
        }

        static::setString($key, Crypt::encryptString($value));
    }

    /**
     * Drop the per-request cache so the next read hits the database. Used after
     * writes that other services immediately read back.
     */
    public static function flushCache(): void
    {
        static::$runtimeCache = [];
    }

    private static function getValue(string $key): ?string
    {
        if (array_key_exists($key, static::$runtimeCache)) {
            return static::$runtimeCache[$key];
        }

        if (! static::settingsTableExists()) {
            return null;
        }

        return static::$runtimeCache[$key] = static::query()
            ->where('key', $key)
            ->value('value');
    }

    private static function settingsTableExists(): bool
    {
        try {
            return Schema::hasTable('app_settings');
        } catch (\Throwable) {
            return false;
        }
    }
}
