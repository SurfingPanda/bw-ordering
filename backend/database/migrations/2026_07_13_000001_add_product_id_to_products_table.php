<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Editor-assigned product code (SKU-style), distinct from the UUID
            // primary key. Nullable so pre-existing rows don't collide on the
            // unique index (MySQL allows multiple NULLs in a unique column).
            $table->string('product_id', 20)->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['product_id']);
            $table->dropColumn('product_id');
        });
    }
};
