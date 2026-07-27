<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('delivery_man_address')->nullable();
            $table->string('delivery_man_lat')->nullable();
            $table->string('delivery_man_lng')->nullable();
        });

        Schema::table('rides', function (Blueprint $table) {
            $table->string('customer_address')->nullable();
            $table->string('customer_lat')->nullable();
            $table->string('customer_lng')->nullable();
            $table->string('rider_address')->nullable();
            $table->string('rider_lat')->nullable();
            $table->string('rider_lng')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_man_address', 'delivery_man_lat', 'delivery_man_lng']);
        });

        Schema::table('rides', function (Blueprint $table) {
            $table->dropColumn([
                'customer_address', 'customer_lat', 'customer_lng',
                'rider_address', 'rider_lat', 'rider_lng'
            ]);
        });
    }
};
