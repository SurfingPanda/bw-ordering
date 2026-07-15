<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_cake_requests', function (Blueprint $table) {
            $table->id();
            // Requester (guests may inquire — no FK to users/Supabase).
            $table->string('name');
            $table->string('email');
            $table->string('phone', 40)->nullable();
            // Cake details (all free-form/optional except the description).
            $table->string('occasion', 60)->nullable();
            $table->date('needed_by')->nullable();
            $table->string('flavor', 120)->nullable();
            $table->string('size', 60)->nullable();
            $table->string('frosting_color', 30)->nullable();
            $table->text('description');
            $table->string('reference_path', 1024)->nullable(); // uploaded design peg
            $table->string('reference_link', 1024)->nullable(); // pasted inspiration URL
            $table->string('status', 30)->default('new');       // new | quoted | closed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_cake_requests');
    }
};
