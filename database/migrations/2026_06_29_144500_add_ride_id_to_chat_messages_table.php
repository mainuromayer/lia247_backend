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
        Schema::table('chat_messages', function (Blueprint $table) {
            // Make order_id nullable to support ride chat messages
            $table->foreignId('order_id')->nullable()->change();
            
            // Add ride_id as nullable foreign key pointing to rides table
            $table->foreignId('ride_id')->nullable()->after('order_id')->constrained('rides')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropForeign(['ride_id']);
            $table->dropColumn('ride_id');
            $table->foreignId('order_id')->nullable(false)->change();
        });
    }
};
