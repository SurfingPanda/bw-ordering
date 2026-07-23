<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            // Supabase auth `sub` — guests may send a message too, so this is
            // nullable with no FK (same pattern as custom_cake_requests.user_id).
            $table->uuid('user_id')->nullable()->index();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->string('subject', 150)->nullable();
            $table->text('message');
            $table->string('status', 20)->default('new'); // new | read | replied
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
