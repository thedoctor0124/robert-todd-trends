<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('app_settings')->updateOrInsert(
            ['key' => 'order_notification_email'],
            [
                'value' => 'neil@roberttodds.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table('app_settings')->where('key', 'order_notification_email')->delete();
    }
};
