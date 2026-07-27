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
        Schema::table('vouchers', function (Blueprint $table) {
            $table->enum('app_type', ['food', 'ride', 'both'])->default('food')->after('type');
            $table->foreignId('created_by_id')->nullable()->after('app_type')->constrained('users')->onDelete('cascade');
            $table->enum('owner_role', ['admin', 'vendor'])->default('admin')->after('created_by_id');
            $table->foreignId('vendor_id')->nullable()->after('owner_role')->constrained('users')->onDelete('cascade'); // If owner is vendor, which shop
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn(['app_type', 'created_by_id', 'owner_role', 'vendor_id']);
        });
    }
};
