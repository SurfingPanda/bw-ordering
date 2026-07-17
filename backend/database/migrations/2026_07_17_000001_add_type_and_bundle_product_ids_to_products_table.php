<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // 'single' (default) | 'bundle'. A bundle product still has its
            // own price/image like any product — bundle_product_ids just
            // names the other products auto-added alongside it in the cart
            // (see menu.blade.php's add()) and priced at ₱0 at checkout up
            // to the matching bundle quantity (see OrderCreationService).
            $table->string('type')->default('single')->after('name');
            $table->json('bundle_product_ids')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['type', 'bundle_product_ids']);
        });
    }
};
