<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('calorie_info')->nullable()->after('calorie_unit');
        });

        // Fold each product's single calories/calorie_unit pair into the new
        // multi-entry shape (e.g. "180 kcal per piece" + "1200 kcal per
        // whole" can now both live on the same product).
        DB::table('products')->whereNotNull('calories')->get(['id', 'calories', 'calorie_unit'])->each(function ($product) {
            DB::table('products')->where('id', $product->id)->update([
                'calorie_info' => json_encode([[
                    'amount' => (int) $product->calories,
                    'unit' => $product->calorie_unit ?: 'piece',
                ]]),
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['calories', 'calorie_unit']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('calories')->nullable();
            $table->string('calorie_unit')->nullable();
        });

        DB::table('products')->whereNotNull('calorie_info')->get(['id', 'calorie_info'])->each(function ($product) {
            $first = (json_decode($product->calorie_info, true) ?: [])[0] ?? null;
            if ($first) {
                DB::table('products')->where('id', $product->id)->update([
                    'calories' => $first['amount'] ?? null,
                    'calorie_unit' => $first['unit'] ?? null,
                ]);
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('calorie_info');
        });
    }
};
