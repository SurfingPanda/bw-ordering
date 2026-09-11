<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_ratings', function (Blueprint $table) {
            $table->id();
            // Signed-in visitors can be associated with their Supabase ID;
            // ratings remain available to guests too.
            $table->uuid('user_id')->nullable()->index();
            $table->unsignedTinyInteger('rating');
            $table->string('page', 120)->default('/');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_ratings');
    }
};
