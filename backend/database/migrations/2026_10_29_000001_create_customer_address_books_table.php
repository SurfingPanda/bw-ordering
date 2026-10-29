<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_address_books', function (Blueprint $table) {
            // Supabase account IDs are strings, independent of Laravel's local users table.
            $table->string('user_id', 64)->primary();
            $table->json('addresses');
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_address_books');
    }
};
