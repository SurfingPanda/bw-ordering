<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            // Who did it — resolved from the Supabase session at write time.
            // Nullable so a row survives even if the actor can't be resolved.
            $table->string('actor_email')->nullable()->index();
            $table->string('actor_name')->nullable();
            $table->string('actor_role', 16)->nullable();
            // What happened — a dotted verb like "content.updated",
            // "product.saved", "order.status_updated" (see AuditLog::ACTIONS).
            $table->string('action', 64)->index();
            // Human label for the thing acted on ("Order #A1B2C3D4",
            // "user@example.com", "Voucher grid"), and a one-line summary.
            $table->string('target')->nullable();
            $table->string('summary')->nullable();
            // Structured extras (counts, before/after) — display only.
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            // Audit rows are immutable — only ever created, never touched again.
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
