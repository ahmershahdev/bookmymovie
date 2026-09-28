<?php

use App\Support\MovieMedia;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Device-level bans and the September 29 media drop.
 *
 *  - user_devices: every browser a member has used (a random id kept in an
 *    encrypted cookie, stored here only as a SHA-256 hash) with its IPs.
 *  - banned_devices: a ban on that browser, so a banned member cannot come
 *    back with a new account from the same device on a new network.
 *  - Films pick up the new artwork, WebM trailers and small image copies.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('device_hash', 64);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('first_seen_at')->useCurrent();
            $table->timestamp('last_seen_at')->useCurrent()->index();
            $table->unique(['user_id', 'device_hash']);
            $table->index('device_hash');
            $table->index('ip_address');
        });

        Schema::create('banned_devices', function (Blueprint $table) {
            $table->id();
            $table->char('device_hash', 64)->unique();
            $table->string('reason', 200);
            $table->foreignId('banned_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('label', 120)->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        MovieMedia::attach();
        Cache::flush();
    }

    public function down(): void
    {
        Schema::dropIfExists('banned_devices');
        Schema::dropIfExists('user_devices');
    }
};
