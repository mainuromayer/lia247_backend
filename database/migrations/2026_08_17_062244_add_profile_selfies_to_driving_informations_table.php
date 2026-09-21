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
        Schema::table('driving_informations', function (Blueprint $table) {
            $table->string('front_prfile_selfie')->nullable()->after('police_reports');
            $table->string('side_profile_selfie')->nullable()->after('front_prfile_selfie');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('driving_informations', function (Blueprint $table) {
            $table->dropColumn(['front_prfile_selfie', 'side_profile_selfie']);
        });
    }
};
