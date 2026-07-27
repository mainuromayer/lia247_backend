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
            $table->boolean('free_delivery')->default(false)->after('delivery_fee');
            $table->enum('free_delivery_covered_by', ['admin', 'vendor', 'hybrid'])->nullable()->after('free_delivery');
            $table->decimal('admin_delivery_contribution', 10, 2)->default(0)->after('free_delivery_covered_by');
            $table->decimal('vendor_delivery_contribution', 10, 2)->default(0)->after('admin_delivery_contribution');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['free_delivery', 'free_delivery_covered_by', 'admin_delivery_contribution', 'vendor_delivery_contribution']);
        });
    }
};
