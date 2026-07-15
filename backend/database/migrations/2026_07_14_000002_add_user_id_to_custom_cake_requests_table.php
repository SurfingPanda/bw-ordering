<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_cake_requests', function (Blueprint $table) {
            // Supabase auth user id (JWT `sub`) when the requester was signed
            // in — lets My Orders list their cake requests. Guests stay null.
            $table->uuid('user_id')->nullable()->index()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('custom_cake_requests', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};
