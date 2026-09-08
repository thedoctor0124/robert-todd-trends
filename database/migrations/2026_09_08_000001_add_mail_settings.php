<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seed the SMTP settings with Gmail defaults. Credentials are left blank —
     * they are entered in the admin portal (Settings → Email).
     */
    private const DEFAULTS = [
        'mail_host' => 'smtp.gmail.com',
        'mail_port' => '587',
        'mail_encryption' => 'tls',
        'mail_username' => '',
        'mail_password' => '',
        'mail_from_address' => '',
        'mail_from_name' => 'Robert Todd Trends',
    ];

    public function up(): void
    {
        foreach (self::DEFAULTS as $key => $value) {
            DB::table('app_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('app_settings')->whereIn('key', array_keys(self::DEFAULTS))->delete();
    }
};
