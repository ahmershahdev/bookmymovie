<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * The public demo admin, so anyone reviewing the project can open the back
 * office. Its password is published in the README, so it is flagged
 * `is_demo`, and on any non-local environment it is read-only
 * (BlockDemoAdminWrites). It never receives email: nothing in the admin
 * sign-in sends a code, and recovery is disabled for it.
 *
 * Safe to re-run; set BOOKMYMOVIE_DEMO_ADMIN=false to remove the account.
 */
class DemoAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('bookmymovie.admin.demo_enabled')) {
            Admin::query()->where('email', config('bookmymovie.admin.demo_email'))->delete();

            return;
        }

        Admin::query()->updateOrCreate(['email' => config('bookmymovie.admin.demo_email')], [
            'name' => 'Site Admin',
            'password' => Hash::make(config('bookmymovie.admin.demo_password')),
            'role' => 'superadmin',
            'is_active' => true,
            'is_demo' => true,
        ]);
    }
}
