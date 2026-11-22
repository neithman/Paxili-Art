<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ORDERS:      one checkout by a buyer.        status: pending | paid | cancelled
 * ORDER_ITEMS: each artwork inside the order.  (title + price are copied so history never changes)
 * PAYMENTS:    each GCash payment attempt.     status: pending | success | failed
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // the buyer
            $table->string('order_number')->unique();
            $table->decimal('total', 10, 2);
            $table->string('status')->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('artwork_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete(); // the seller's store
            $table->string('title');            // snapshot of the artwork title
            $table->decimal('price', 10, 2);    // snapshot of the price paid
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('method')->default('gcash');  // payment method (GCash)
            $table->string('reference')->unique();       // transaction reference number
            $table->decimal('amount', 10, 2);
            $table->string('status');                    // pending | success | failed
            $table->string('mode')->default('test');     // test = GCash simulator, live = real GCash via PayMongo
            $table->string('provider_reference')->nullable(); // PayMongo checkout session id (live mode)
            $table->string('account_number')->nullable(); // masked GCash number, e.g. 0917****567
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
