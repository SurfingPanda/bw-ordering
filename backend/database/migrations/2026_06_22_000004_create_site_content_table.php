<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Single-row landing/franchise CMS blob (always id = 1), mirroring the
        // old Supabase site_content row.
        Schema::create('site_content', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_content');
    }
};
