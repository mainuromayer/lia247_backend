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
        Schema::create('tips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->foreignId('ride_id')->nullable()->constrained('rides')->onDelete('set null');
            $table->decimal('amount', 15, 2);
            $table->string('payment_type'); // wallet, stripe
            $table->string('payment_status')->default('pending'); // pending, captured, failed
            $table->string('transaction_id')->nullable(); // wallet transaction ID or charge ID
            $table->string('payment_intent_id')->nullable(); // stripe payment intent ID
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tips');
    }
};
