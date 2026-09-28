<?php

namespace Tests;

use App\Models\Show;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected const CAPTCHA = 'ABCDE';

    /**
     * Plants a solved image-captcha challenge for a form action, exactly as
     * FormSecurity::customCaptchaChallenge() would store it.
     */
    protected function withCaptcha(string $action): static
    {
        return $this->withSession([
            'custom_captcha.'.$action => [
                'answer' => hash_hmac('sha256', self::CAPTCHA, (string) config('app.key')),
                'created_at' => now()->subMinute()->timestamp,
            ],
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function captchaFields(): array
    {
        return ['custom_captcha_answer' => self::CAPTCHA];
    }

    /**
     * A scheduled show comfortably in the future with at least $seats free
     * seats; returns the show and the ids of free seats.
     *
     * @return array{0: Show, 1: list<int>}
     */
    protected function bookableShow(int $seats = 4): array
    {
        $showId = DB::table('shows')
            ->where('status', 'scheduled')
            ->whereRaw('TIMESTAMP(show_date, show_time) > ?', [now()->addHours(6)->toDateTimeString()])
            ->whereRaw('total_seats - booked_seats >= ?', [$seats + 4])
            ->orderBy('show_date')
            ->value('id');

        $seatIds = DB::table('v_seat_availability')
            ->where('show_id', $showId)
            ->where('seat_status', 'available')
            ->where('category_name', '<>', 'Box')
            ->orderBy('row_label')
            ->orderBy('seat_number')
            ->limit($seats)
            ->pluck('seat_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return [Show::query()->findOrFail($showId), $seatIds];
    }
}
