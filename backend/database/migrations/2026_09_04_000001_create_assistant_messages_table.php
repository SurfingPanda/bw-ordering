<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_messages', function (Blueprint $table) {
            $table->id();
            // Groups the turns of one browser conversation together. Not the
            // Laravel session id (that rotates on login) — a random token the
            // widget mints once and keeps in sessionStorage.
            $table->string('conversation_id', 40)->index();
            // Supabase auth `sub` + email when the visitor is signed in;
            // null for guests (the widget is public, like the contact form).
            $table->uuid('user_id')->nullable()->index();
            $table->string('user_email')->nullable();
            $table->string('role', 16);              // user | assistant
            $table->text('content');
            // How the assistant turn was produced: 'rules' (answered locally, no
            // API call), 'groq' (primary model), 'groq-mini' (smaller Groq
            // model), 'gemini' (cross-provider fallback), or 'fallback' (every
            // provider failed and the canned reply was used).
            $table->string('source', 16)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_messages');
    }
};
