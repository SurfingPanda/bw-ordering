<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// DB-backed role assignments, editable from the admin Users page. The env
// allowlists (ADMIN_EMAILS/EDITOR_EMAILS/HR_EMAILS) remain as an immutable
// bootstrap (the founding admin always works); this table is the editable
// layer on top. One role per email; no row means a plain customer.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();   // stored lowercased
            $table->string('role');              // admin | editor | cashier | hr
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
