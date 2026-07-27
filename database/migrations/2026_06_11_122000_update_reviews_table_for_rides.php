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
        // 1. Drop foreign key constraint first
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });

        // 2. Drop the unique constraint
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique(['order_id', 'user_id', 'target_id', 'role']);
        });

        // 3. Make order_id nullable, restore foreign key, and add nullable ride_id column.
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->change();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            
            $table->foreignId('ride_id')->nullable()->after('order_id')->constrained('rides')->cascadeOnDelete();
            
            // Add a unique constraint that allows nullable order_id and nullable ride_id
            $table->unique(['order_id', 'ride_id', 'user_id', 'target_id', 'role'], 'reviews_unique_constraint');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique('reviews_unique_constraint');
            $table->dropForeign(['ride_id']);
            $table->dropColumn('ride_id');
            $table->dropForeign(['order_id']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable(false)->change();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->unique(['order_id', 'user_id', 'target_id', 'role']);
        });
    }
};
