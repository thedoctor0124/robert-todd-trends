<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A new customer has no user row until they claim their invite, so the
     * company captured when sending has to be parked on the invite and copied
     * onto the account at registration.
     */
    public function up(): void
    {
        Schema::table('access_invites', function (Blueprint $table) {
            $table->string('invited_company')->nullable()->after('invited_name');
        });
    }

    public function down(): void
    {
        Schema::table('access_invites', function (Blueprint $table) {
            $table->dropColumn('invited_company');
        });
    }
};
