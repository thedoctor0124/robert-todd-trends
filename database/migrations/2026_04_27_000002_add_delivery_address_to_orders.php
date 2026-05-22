<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $this->addDeliveryAddressColumns($table);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $this->addDeliveryAddressColumns($table);
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $this->dropDeliveryAddressColumns($table);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $this->dropDeliveryAddressColumns($table);
        });
    }

    private function addDeliveryAddressColumns(Blueprint $table): void
    {
        $table->boolean('delivery_required')->default(false)->after('amount_paid');
        $table->string('delivery_name')->nullable()->after('delivery_required');
        $table->string('delivery_address_line_1')->nullable()->after('delivery_name');
        $table->string('delivery_address_line_2')->nullable()->after('delivery_address_line_1');
        $table->string('delivery_city')->nullable()->after('delivery_address_line_2');
        $table->string('delivery_county')->nullable()->after('delivery_city');
        $table->string('delivery_postcode')->nullable()->after('delivery_county');
        $table->string('delivery_country')->nullable()->after('delivery_postcode');
        $table->string('delivery_phone')->nullable()->after('delivery_country');
    }

    private function dropDeliveryAddressColumns(Blueprint $table): void
    {
        $table->dropColumn([
            'delivery_required',
            'delivery_name',
            'delivery_address_line_1',
            'delivery_address_line_2',
            'delivery_city',
            'delivery_county',
            'delivery_postcode',
            'delivery_country',
            'delivery_phone',
        ]);
    }
};
