<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Public usernames and profiles, user and IP bans, richer reviews, a demo
 * admin flag, and a schema tidy-up found in the September 2026 audit:
 *
 *  - gift_cards.issued_by had no foreign key (orphans possible)
 *  - admin_activity_logs duplicated audit_logs and was never written to
 *  - movies.status and shows.status indexes were left-prefixes of composite
 *    indexes, so MySQL maintained them on every write for no read benefit
 *  - theaters.slug was nullable although every route needs it
 *  - reviews could not be listed by newest per user without a filesort
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->nullable()->after('name');
            $table->string('bio', 160)->nullable()->after('address');
            $table->string('city', 60)->nullable()->after('bio');
            $table->string('last_login_ip', 45)->nullable()->after('remember_token');
            $table->timestamp('last_login_at')->nullable()->after('last_login_ip');
        });

        // Backfill unique usernames from names, then make the column required.
        $taken = [];
        foreach (DB::table('users')->orderBy('id')->get(['id', 'name', 'email']) as $user) {
            $base = Str::of($user->name ?: Str::before($user->email, '@'))->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->limit(22, '')->value() ?: 'member';
            $candidate = $base;
            $suffix = 1;
            while (isset($taken[$candidate]) || DB::table('users')->where('username', $candidate)->exists()) {
                $candidate = $base.'_'.(++$suffix);
            }
            $taken[$candidate] = true;
            DB::table('users')->where('id', $user->id)->update(['username' => $candidate]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->nullable(false)->change();
            $table->unique('username');
            $table->index('last_login_ip');
        });

        Schema::create('banned_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->unique();
            $table->string('reason', 200);
            $table->foreignId('banned_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->string('title', 90)->nullable()->after('rating');
            $table->boolean('contains_spoilers')->default(false)->after('review_text');
            $table->unsignedInteger('helpful_count')->default(0)->after('contains_spoilers');
            $table->index(['user_id', 'created_at']);
        });

        Schema::table('admins', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('is_active');
        });

        Schema::table('gift_cards', function (Blueprint $table) {
            $table->foreign('issued_by')->references('id')->on('admins')->nullOnDelete();
        });

        Schema::dropIfExists('admin_activity_logs');

        Schema::table('movies', fn (Blueprint $table) => $table->dropIndex('movies_status_index'));
        Schema::table('shows', fn (Blueprint $table) => $table->dropIndex('shows_status_index'));

        foreach (DB::table('theaters')->whereNull('slug')->get(['id', 'name']) as $theater) {
            DB::table('theaters')->where('id', $theater->id)->update(['slug' => Str::slug($theater->name).'-'.$theater->id]);
        }
        Schema::table('theaters', fn (Blueprint $table) => $table->string('slug', 170)->nullable(false)->change());
    }

    public function down(): void
    {
        Schema::table('theaters', fn (Blueprint $table) => $table->string('slug', 170)->nullable()->change());
        Schema::table('shows', fn (Blueprint $table) => $table->index('status'));
        Schema::table('movies', fn (Blueprint $table) => $table->index('status'));

        Schema::create('admin_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->string('action', 100)->index();
            $table->string('model_type', 100)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['model_type', 'model_id']);
        });

        Schema::table('gift_cards', fn (Blueprint $table) => $table->dropForeign(['issued_by']));
        Schema::table('admins', fn (Blueprint $table) => $table->dropColumn('is_demo'));
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropColumn(['title', 'contains_spoilers', 'helpful_count']);
        });
        Schema::dropIfExists('banned_ips');
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropIndex(['last_login_ip']);
            $table->dropColumn(['username', 'bio', 'city', 'last_login_ip', 'last_login_at']);
        });
    }
};
