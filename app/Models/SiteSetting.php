<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'is_public'];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    /**
     * Brand, contact and social settings the admin can edit under
     * Admin → Site & brand. Defaults apply until a value is saved.
     */
    public const BRAND_DEFAULTS = [
        'site_name' => 'BookMyMovie',
        'site_tagline' => 'Cinema tickets for Pakistan',
        'logo_path' => null,
        'support_email' => 'support@ahmershah.dev',
        'support_phone' => '+92 370 4831994',
        'contact_address' => 'Lahore, Pakistan',
        'social_website' => 'https://ahmershah.dev/',
        'social_github' => 'https://github.com/ahmershahdev',
        'social_linkedin' => 'https://linkedin.com/in/syedahmershah',
        'social_instagram' => null,
        'social_facebook' => null,
        'social_x' => null,
        'social_youtube' => null,
    ];

    public static function put(string $key, ?string $value): void
    {
        self::query()->updateOrCreate(['key' => $key], ['value' => $value, 'type' => 'string', 'is_public' => true]);
    }

    /**
     * @return array<string, string>
     */
    public static function publicMap(): array
    {
        return self::query()
            ->where('is_public', true)
            ->pluck('value', 'key')
            ->map(fn ($value) => (string) $value)
            ->all();
    }
}
