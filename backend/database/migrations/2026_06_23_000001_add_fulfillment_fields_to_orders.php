<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('customer_phone')->nullable()->after('customer_email');
            $table->string('payment_method')->nullable()->after('voucher');   // cod | gcash | qrph
            $table->string('delivery_type')->nullable()->after('payment_method'); // delivery | pickup
            $table->string('delivery_speed')->nullable()->after('delivery_type'); // standard | express
            $table->text('address')->nullable()->after('delivery_speed');
            $table->text('notes')->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'customer_phone',
                'payment_method',
                'delivery_type',
                'delivery_speed',
                'address',
                'notes',
            ]);
        });
    }
};
