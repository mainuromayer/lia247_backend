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
        Schema::table('system_settings', function (Blueprint $table) {
            $table->decimal('ride_base_fare', 10, 2)->default(9.00)->after('ride_commission');
            $table->decimal('ride_price_per_mile', 10, 2)->default(1.75)->after('ride_base_fare');
            $table->decimal('ride_cancellation_fee', 10, 2)->default(0.00)->after('ride_price_per_mile');
            $table->decimal('ride_no_show_fee', 10, 2)->default(7.00)->after('ride_cancellation_fee');
            $table->decimal('ride_no_show_driver_pay', 10, 2)->default(5.00)->after('ride_no_show_fee');
            $table->decimal('ride_waiting_charge_per_minute', 10, 2)->default(0.50)->after('ride_no_show_driver_pay');
            $table->decimal('ride_driver_payout_percentage', 5, 2)->default(75.00)->after('ride_waiting_charge_per_minute');
            $table->decimal('ride_refund_percentage', 5, 2)->default(100.00)->after('ride_driver_payout_percentage');
            $table->decimal('restaurant_commission_percentage', 5, 2)->default(15.00)->after('ride_refund_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn([
                'ride_base_fare',
                'ride_price_per_mile',
                'ride_cancellation_fee',
                'ride_no_show_fee',
                'ride_no_show_driver_pay',
                'ride_waiting_charge_per_minute',
                'ride_driver_payout_percentage',
                'ride_refund_percentage',
                'restaurant_commission_percentage'
            ]);
        });
    }
};
