<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Online payments (JazzCash, Easypaisa, cards) next to cash at the counter,
 * plus an append-only audit log of who changed what.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE bookings MODIFY payment_method ENUM('cod','jazzcash','easypaisa','card') NOT NULL DEFAULT 'cod'");
        DB::statement("ALTER TABLE payments MODIFY payment_method ENUM('cod','jazzcash','easypaisa','card') NOT NULL DEFAULT 'cod'");

        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway_reference', 120)->nullable()->after('transaction_reference')->index();
            $table->json('gateway_payload')->nullable()->after('notes');
            $table->timestamp('gateway_started_at')->nullable()->after('gateway_payload');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('actor_type', 20);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action', 80)->index();
            $table->string('subject_type', 80)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['actor_type', 'actor_id']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['gateway_reference', 'gateway_payload', 'gateway_started_at']);
        });

        DB::statement("UPDATE payments SET payment_method = 'cod'");
        DB::statement("UPDATE bookings SET payment_method = 'cod'");
        DB::statement("ALTER TABLE payments MODIFY payment_method ENUM('cod') NOT NULL DEFAULT 'cod'");
        DB::statement("ALTER TABLE bookings MODIFY payment_method ENUM('cod') NOT NULL DEFAULT 'cod'");
    }
};
