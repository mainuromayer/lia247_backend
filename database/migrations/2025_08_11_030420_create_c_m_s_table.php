<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('c_m_s', function (Blueprint $table) {
            $table->id();
            $table->string('page')->nullable();
            $table->string('section')->nullable();
            $table->string('type')->nullable();
            $table->longText('title')->nullable();
            $table->longText('sub_title')->nullable();
            $table->string('logo')->nullable();
            $table->string('image')->nullable();
            $table->string('icon')->nullable();
            $table->string('video')->nullable();
            $table->string('duration')->nullable();
            $table->longText('description')->nullable();
            $table->longText('sub_description')->nullable();
            $table->longText('main_text')->nullable();
            $table->longText('sub_text')->nullable();
            $table->string('button_text')->nullable();
            $table->string('sub_button_text')->nullable();
            $table->string('link_url')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('lat')->nullable();
            $table->string('long')->nullable();
            $table->longText('location')->nullable();
            $table->json('office_hours')->nullable();
            $table->longText('v1')->nullable();
            $table->longText('v2')->nullable();
            $table->longText('v3')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_m_s');
    }
};
