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
        Schema::table('delivery_options', function (Blueprint $table) {
            $table->string('sub_text')->nullable()->after('name');
            $table->string('image')->nullable()->after('sub_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_options', function (Blueprint $table) {
            $table->dropColumn(['sub_text', 'image']);
        });
    }
};
