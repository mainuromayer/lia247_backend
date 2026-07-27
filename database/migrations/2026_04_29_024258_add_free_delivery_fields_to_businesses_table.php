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
        Schema::table('businesses', function (Blueprint $table) {
            $table->boolean('free_delivery')->default(false)->after('business_address');
            $table->enum('free_delivery_type', ['admin', 'vendor', 'hybrid'])->nullable()->after('free_delivery');
            $table->decimal('admin_contribution', 10, 2)->default(0)->after('free_delivery_type');
            $table->decimal('vendor_contribution', 10, 2)->default(0)->after('admin_contribution');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['free_delivery', 'free_delivery_type', 'admin_contribution', 'vendor_contribution']);
        });
    }
};
