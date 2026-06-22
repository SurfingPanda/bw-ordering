<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            // UUID primary key so ids stay stable across the migrate-from-Supabase
            // import and so the frontend keeps treating product ids as strings.
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('original_price', 10, 2)->nullable();
            $table->string('image_path', 1024)->nullable();
            $table->json('features')->nullable();
            $table->integer('calories')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('status')->nullable();        // new | best_seller | sold_out | null
            $table->string('category')->nullable();
            $table->timestamp('archived_at')->nullable(); // soft-delete (never hard-delete)
            $table->timestamps();

            $table->index('category');
            $table->index('archived_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
