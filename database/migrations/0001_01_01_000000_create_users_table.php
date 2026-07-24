<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 150)->unique();
            $table->string('password');
            $table->string('profile_picture')->nullable();
            $table->enum('role', ['superadmin', 'admin'])->default('admin');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index('role');
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 150)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone', 20)->nullable()->unique();
            $table->date('date_of_birth')->nullable()->index();
            $table->string('profile_picture')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            $table->boolean('is_blocked')->default(false)->index();
            $table->string('blocked_reason')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes()->index();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 150)->primary();
            $table->string('token');
            $table->timestamp('created_at')->useCurrent();
            $table->index('token');
        });

        Schema::create('admin_password_resets', function (Blueprint $table) {
            $table->string('email', 150)->primary();
            $table->string('token');
            $table->timestamp('created_at')->useCurrent();
            $table->index('token');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('admin_password_resets');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('admins');
    }
};
