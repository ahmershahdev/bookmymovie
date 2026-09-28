<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'is_demo',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The public demo account. On production it can look at everything but
     * change nothing, because its password is published in the README.
     */
    public function isReadOnly(): bool
    {
        return $this->is_demo && (bool) config('bookmymovie.admin.demo_read_only');
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_demo' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }
}
