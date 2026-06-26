<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // The branch a pickup order is collected from. `pickup_store_id` is the
            // stores.id reference; `pickup_branch` denormalizes the branch name +
            // address so it stays displayable even if the store later changes.
            $table->unsignedBigInteger('pickup_store_id')->nullable()->after('delivery_speed');
            $table->string('pickup_branch')->nullable()->after('pickup_store_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['pickup_store_id', 'pickup_branch']);
        });
    }
};
