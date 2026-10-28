<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_cake_requests', function (Blueprint $table) {
            $table->string('theme', 30)->default('None');
            $table->string('character_name', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('custom_cake_requests', function (Blueprint $table) {
            $table->dropColumn(['theme', 'character_name']);
        });
    }
};
