<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

/**
 * SMTP credentials are held in app_settings (managed in the admin portal) rather
 * than the environment, so mail can be reconfigured without a redeploy.
 *
 * apply() rewrites the runtime mail config just before the mailer is built. When
 * the settings are incomplete nothing is touched and the .env values stand, so a
 * half-filled form cannot break a working environment-configured mailer.
 */
class MailSettings
{
    public const DEFAULT_HOST = 'smtp.gmail.com';

    public const DEFAULT_PORT = 587;

    public const DEFAULT_ENCRYPTION = 'tls';

    /**
     * Seconds to wait on the SMTP conversation. Order confirmations are sent
     * inline during checkout, so an unresponsive server must fail rather than
     * hold the request open indefinitely.
     */
    private const TIMEOUT = 15;

    public static function host(): string
    {
        return trim(AppSetting::string(AppSetting::MAIL_HOST, self::DEFAULT_HOST));
    }

    public static function port(): int
    {
        $port = (int) AppSetting::string(AppSetting::MAIL_PORT, (string) self::DEFAULT_PORT);

        return $port > 0 ? $port : self::DEFAULT_PORT;
    }

    public static function encryption(): string
    {
        $encryption = strtolower(trim(AppSetting::string(AppSetting::MAIL_ENCRYPTION, self::DEFAULT_ENCRYPTION)));

        return in_array($encryption, ['tls', 'ssl'], true) ? $encryption : self::DEFAULT_ENCRYPTION;
    }

    public static function username(): string
    {
        return trim(AppSetting::string(AppSetting::MAIL_USERNAME));
    }

    public static function password(): string
    {
        return AppSetting::encrypted(AppSetting::MAIL_PASSWORD);
    }

    /**
     * Gmail rejects a From address that is neither the authenticated account nor
     * one of its verified "Send mail as" aliases, so the username is the safest
     * fallback when no address has been set explicitly.
     */
    public static function fromAddress(): string
    {
        $address = trim(AppSetting::string(AppSetting::MAIL_FROM_ADDRESS));

        if ($address !== '') {
            return $address;
        }

        $username = self::username();

        return $username !== '' ? $username : (string) config('mail.from.address');
    }

    public static function fromName(): string
    {
        $name = trim(AppSetting::string(AppSetting::MAIL_FROM_NAME));

        return $name !== '' ? $name : (string) config('mail.from.name');
    }

    /**
     * True once there is enough stored detail to authenticate against the server.
     */
    public static function configured(): bool
    {
        return self::host() !== ''
            && self::username() !== ''
            && self::password() !== '';
    }

    public static function passwordIsSet(): bool
    {
        return self::password() !== '';
    }

    /**
     * Point the runtime mail config at the stored settings. Safe to call more
     * than once; a no-op when the stored settings are incomplete.
     */
    public static function apply(): void
    {
        if (! self::configured()) {
            return;
        }

        $port = self::port();

        Config::set([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => self::host(),
            'mail.mailers.smtp.port' => $port,
            // Laravel 12 derives STARTTLS vs implicit TLS from the scheme, and
            // falls back to the port when it is empty. Set it explicitly so an
            // unusual port with SSL selected still negotiates correctly.
            'mail.mailers.smtp.scheme' => (self::encryption() === 'ssl' || $port === 465) ? 'smtps' : 'smtp',
            'mail.mailers.smtp.username' => self::username(),
            'mail.mailers.smtp.password' => self::password(),
            'mail.mailers.smtp.timeout' => self::TIMEOUT,
            'mail.from.address' => self::fromAddress(),
            'mail.from.name' => self::fromName(),
        ]);
    }

    /**
     * Apply the stored settings and discard any mailer already built from the
     * previous config. Needed after saving, so a test send in the same process
     * uses the new credentials instead of a cached transport.
     */
    public static function refresh(): void
    {
        AppSetting::flushCache();

        self::apply();

        Mail::forgetMailers();
    }
}
