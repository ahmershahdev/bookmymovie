<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    /**
     * Roles, most to least powerful:
     *  - superadmin (Owner): everything, including staff and site settings;
     *  - admin (Manager): catalogue, prices, coupons, stock, members, bans, reviews, refunds;
     *  - box_office: sees the dashboard, bookings and members, checks tickets in.
     */
    public const ROLES = [
        'superadmin' => 'Owner',
        'admin' => 'Manager',
        'box_office' => 'Box office',
    ];

    private const ABILITIES = [
        'view' => ['superadmin', 'admin', 'box_office'],
        'admit' => ['superadmin', 'admin', 'box_office'],
        'manage' => ['superadmin', 'admin'],
        'own' => ['superadmin'],
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'is_demo',
        'created_by',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The public demo account. On production it can look at everything but
     * change nothing, because its password is published in the README.
     */
    public function isReadOnly(): bool
    {
        return $this->is_demo && (bool) config('bookmymovie.admin.demo_read_only');
    }

    public function allows(string $ability): bool
    {
        return in_array($this->role, self::ABILITIES[$ability] ?? [], true);
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? ucfirst((string) $this->role);
    }

    public function hasTwoFactor(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_demo' => 'boolean',
            'last_login_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
