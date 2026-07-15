<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_cake_requests', function (Blueprint $table) {
            // How the customer wants the cake — same naming as the orders
            // table (delivery_type / address / fulfillment_branch).
            $table->string('delivery_type', 10)->nullable()->after('frosting_color'); // delivery | pickup
            $table->string('address', 500)->nullable()->after('delivery_type');       // delivery only
            $table->string('fulfillment_branch')->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('custom_cake_requests', function (Blueprint $table) {
            $table->dropColumn(['delivery_type', 'address', 'fulfillment_branch']);
        });
    }
};
