<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();      // Supabase auth user id (JWT `sub`)
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->json('items');                    // [{ product_id, name, qty, price }]
            $table->string('voucher')->nullable();
            // All money columns are recomputed server-side on insert from the
            // trusted products + vouchers tables — never trusted from the client.
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('delivery', 10, 2)->default(0);
            $table->decimal('vat', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('status')->default('pending'); // pending|preparing|completed|cancelled
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
