<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Invite emails were sent without recording the outcome, so a failed send
     * left an invite that looked identical to a delivered one. Track delivery so
     * the admin UI can show what actually happened.
     *
     * Rows created before this migration keep NULL in all three columns, which
     * reads as "unknown" rather than falsely claiming success or failure.
     */
    public function up(): void
    {
        Schema::table('access_invites', function (Blueprint $table) {
            $table->timestamp('sent_at')->nullable()->after('granted_by');
            $table->timestamp('send_failed_at')->nullable()->after('sent_at');
            $table->text('send_error')->nullable()->after('send_failed_at');
        });
    }

    public function down(): void
    {
        Schema::table('access_invites', function (Blueprint $table) {
            $table->dropColumn(['sent_at', 'send_failed_at', 'send_error']);
        });
    }
};
