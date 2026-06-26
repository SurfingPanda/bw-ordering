<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-branch fulfillment capability: which order types this store serves.
        // 'both' | 'pickup' | 'delivery'. Drives which branches appear in the
        // checkout branch picker for each mode.
        Schema::table('stores', function (Blueprint $table) {
            $table->string('fulfillment')->default('both')->after('region');
        });

        // The branch fulfilling an order (the pickup branch, or the branch that
        // delivers). `fulfillment_store_id` is the stores.id reference;
        // `fulfillment_branch` denormalizes the branch name + address so it stays
        // displayable even if the store later changes.
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('fulfillment_store_id')->nullable()->after('delivery_speed');
            $table->string('fulfillment_branch')->nullable()->after('fulfillment_store_id');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('fulfillment');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['fulfillment_store_id', 'fulfillment_branch']);
        });
    }
};
