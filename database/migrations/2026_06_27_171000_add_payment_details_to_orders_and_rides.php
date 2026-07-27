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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'payment_type')) {
                $table->string('payment_type')->nullable()->after('payment_gateway');
            }
            if (!Schema::hasColumn('orders', 'payment_status')) {
                $table->string('payment_status')->default('pending')->after('payment_type');
            }
            if (!Schema::hasColumn('orders', 'amount')) {
                $table->decimal('amount', 10, 2)->default(0.00)->after('payment_status');
            }
            if (!Schema::hasColumn('orders', 'transaction_id')) {
                $table->string('transaction_id')->nullable()->after('amount');
            }
            if (!Schema::hasColumn('orders', 'refund_transaction_id')) {
                $table->string('refund_transaction_id')->nullable()->after('transaction_id');
            }
            if (!Schema::hasColumn('orders', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('refund_transaction_id');
            }
            if (!Schema::hasColumn('orders', 'refunded_amount')) {
                $table->decimal('refunded_amount', 10, 2)->nullable()->after('refunded_at');
            }
        });

        Schema::table('rides', function (Blueprint $table) {
            if (!Schema::hasColumn('rides', 'payment_type')) {
                $table->string('payment_type')->nullable()->after('payment_gateway');
            }
            if (!Schema::hasColumn('rides', 'amount')) {
                $table->decimal('amount', 10, 2)->default(0.00)->after('payment_status');
            }
            if (!Schema::hasColumn('rides', 'transaction_id')) {
                $table->string('transaction_id')->nullable()->after('amount');
            }
            if (!Schema::hasColumn('rides', 'refund_transaction_id')) {
                $table->string('refund_transaction_id')->nullable()->after('transaction_id');
            }
            if (!Schema::hasColumn('rides', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('refund_transaction_id');
            }
            if (!Schema::hasColumn('rides', 'refunded_amount')) {
                $table->decimal('refunded_amount', 10, 2)->nullable()->after('refunded_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_type',
                'payment_status',
                'amount',
                'transaction_id',
                'refund_transaction_id',
                'refunded_at',
                'refunded_amount',
            ]);
        });

        Schema::table('rides', function (Blueprint $table) {
            $table->dropColumn([
                'payment_type',
                'amount',
                'transaction_id',
                'refund_transaction_id',
                'refunded_at',
                'refunded_amount',
            ]);
        });
    }
};
