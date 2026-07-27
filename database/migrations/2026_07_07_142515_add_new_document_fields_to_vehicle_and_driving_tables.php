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
        Schema::table('vehicle_informations', function (Blueprint $table) {
            $table->json('vehicle_photos')->nullable()->after('vehicle_information_documents');
            $table->string('license_front')->nullable()->after('vehicle_photos');
            $table->string('license_back')->nullable()->after('license_front');
        });

        Schema::table('driving_informations', function (Blueprint $table) {
            $table->json('police_reports')->nullable()->after('police_report_documents');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_informations', function (Blueprint $table) {
            $table->dropColumn(['vehicle_photos', 'license_front', 'license_back']);
        });

        Schema::table('driving_informations', function (Blueprint $table) {
            $table->dropColumn(['police_reports']);
        });
    }
};
