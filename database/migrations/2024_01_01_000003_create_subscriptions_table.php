<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_free')->default(false);
            $table->string('granted_by')->nullable();
            $table->string('square_payment_id')->nullable();
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'season_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
