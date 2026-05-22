<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_invites', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('email');
            $table->string('invited_name')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('access_type');
            $table->foreignId('publication_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('granted_by');
            $table->timestamp('expires_at');
            $table->timestamp('redeemed_at')->nullable();
            $table->foreignId('redeemed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['email', 'redeemed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_invites');
    }
};
