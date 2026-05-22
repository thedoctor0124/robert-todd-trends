<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('publication_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_free')->default(false);
            $table->string('granted_by')->nullable();
            $table->string('square_payment_id')->nullable();
            $table->string('discount_code_used')->nullable();
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'publication_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
