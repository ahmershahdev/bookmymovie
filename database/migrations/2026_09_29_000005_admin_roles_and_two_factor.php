<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Staff roles and authenticator-app sign-in for the back office.
 *
 *  - role: superadmin (Owner), admin (Manager), box_office (Box office).
 *  - two_factor_secret / recovery codes are stored encrypted (model casts);
 *    confirmed_at is set only after the admin proves the app works.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE admins MODIFY role ENUM('superadmin','admin','box_office') NOT NULL DEFAULT 'box_office'");

        Schema::table('admins', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            $table->foreignId('created_by')->nullable()->after('is_demo')->constrained('admins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);
        });
        DB::table('admins')->where('role', 'box_office')->update(['role' => 'admin']);
        DB::statement("ALTER TABLE admins MODIFY role ENUM('superadmin','admin') NOT NULL DEFAULT 'admin'");
    }
};
