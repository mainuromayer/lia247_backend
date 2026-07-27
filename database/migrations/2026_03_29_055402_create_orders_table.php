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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('stripe_session_id')->nullable();
            $table->string('payment_intent_id')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->string('status')->default('pending'); // pending, completed, canceled
            $table->enum('order_type', ['delivery', 'pickup'])->default('delivery');
            $table->string('receiver_name')->nullable();
            $table->string('receiver_phone')->nullable();
            $table->unsignedBigInteger('delivery_option_id')->nullable();
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->unsignedBigInteger('voucher_id')->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('payment_gateway')->nullable();
            $table->text('notes')->nullable();
            $table->text('checkout_url')->nullable();
            $table->timestamps();
            // Note: intentionally not adding foreign keys to allow soft deletes / simple schema, or will add if preferred

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
