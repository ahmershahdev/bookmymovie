<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /** Usernames that would clash with routes or impersonate staff. */
    public const RESERVED_USERNAMES = ['admin', 'administrator', 'root', 'support', 'staff', 'help', 'bookmymovie', 'moderator', 'system', 'api', 'account', 'login', 'register', 'me', 'null'];

    protected static function booted(): void
    {
        // Every account gets a public username, including social sign-ups.
        static::creating(function (User $user) {
            if (blank($user->username)) {
                $user->username = self::uniqueUsername((string) ($user->name ?: strstr((string) $user->email, '@', true)));
            }
        });
    }

    public static function uniqueUsername(string $from): string
    {
        // The shape the sign-up form enforces: starts with a letter, single
        // underscores, at most 20 characters including any numeric suffix.
        $base = Str::of($from)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '_')->replaceMatches('/^[^a-z]+/', '')->limit(15, '')->trim('_')->value();
        if (\App\Support\CleanText::usernameProblem($base) !== null) {
            $base = 'member';
        }
        $candidate = $base;
        for ($suffix = 2; self::withTrashed()->where('username', $candidate)->exists(); $suffix++) {
            $candidate = $base.$suffix;
        }

        return $candidate;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'address',
        'date_of_birth',
        'gender',
        'profile_picture',
        'bio',
        'city',
        'password',
        'email_verification_code',
        'email_verification_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_code',
        'last_login_ip',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_expires_at' => 'datetime',
            'date_of_birth' => 'date',
            'is_blocked' => 'boolean',
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
            'loyalty_points' => 'integer',
            'last_login_at' => 'datetime',
            'blocked_at' => 'datetime',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
