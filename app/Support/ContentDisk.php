<?php

namespace App\Support;

/**
 * Disk used for publication PDFs, covers, and season covers.
 * Google Cloud Storage when configured; otherwise local storage.
 *
 * When STORAGE_USE_PUBLIC_PATH=true (typical Krystal / no-SSH uploads), files live under
 * public/uploads/ so no `php artisan storage:link` is required.
 */
final class ContentDisk
{
    public static function name(): string
    {
        if (filled(config('filesystems.disks.gcs.bucket'))) {
            return 'gcs';
        }

        return filter_var(config('filesystems.use_public_path', false), FILTER_VALIDATE_BOOL)
            ? 'host_public'
            : 'public';
    }

    public static function isGoogle(): bool
    {
        return self::name() === 'gcs';
    }
}
