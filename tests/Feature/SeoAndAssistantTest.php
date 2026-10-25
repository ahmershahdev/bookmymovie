<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\Theater;
use App\Models\User;
use App\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoAndAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_long_titles_drop_their_suffix_before_being_clipped(): void
    {
        $this->assertSame('The Last Cinema on Empress Road', Seo::title('The Last Cinema on Empress Road | Showtimes & Tickets'));
        $this->assertSame('Metropole Picture House, Karachi', Seo::title('Metropole Picture House, Karachi: Showtimes & Tickets'));
        $this->assertSame('Short | BookMyMovie', Seo::title('Short | BookMyMovie'));
        $this->assertLessThanOrEqual(Seo::TITLE_MAX, mb_strlen(Seo::title(str_repeat('word ', 30))));
    }

    public function test_public_pages_keep_titles_and_descriptions_within_limits(): void
    {
        $paths = ['/', '/movies', '/cinemas', '/offers', '/faq', '/about', '/gift-cards'];
        $paths[] = '/movies/'.Movie::query()->where('status', 'now_showing')->value('slug');
        $paths[] = '/cinemas/'.Theater::query()->value('slug');

        foreach ($paths as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            preg_match('/<title[^>]*>([^<]*)<\/title>/', $html, $title);
            preg_match('/<meta name="description" content="([^"]*)"/', $html, $description);

            $this->assertLessThanOrEqual(50, mb_strlen(html_entity_decode($title[1], ENT_QUOTES)), "title on {$path}");
            $this->assertLessThanOrEqual(150, mb_strlen(html_entity_decode($description[1], ENT_QUOTES)), "description on {$path}");
            $this->assertStringContainsString('application/ld+json', $html, "JSON-LD on {$path}");
            $this->assertStringContainsString('property="og:image"', $html, "og:image on {$path}");
        }
    }

    public function test_error_pages_are_not_indexed(): void
    {
        $html = $this->get('/this-page-does-not-exist', ['Accept' => 'text/html'])->assertNotFound()->getContent();

        $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $html);
        $this->assertStringContainsString('Page not found', $html);
    }

    public function test_assistant_answers_guests_and_members_without_mixing_caches(): void
    {
        $guest = $this->getJson('/assistant/context')->assertOk();
        $guest->assertJsonPath('user', null);
        $this->assertStringContainsString('no-cache', (string) $guest->headers->get('Cache-Control'));

        $user = User::query()->firstOrFail();
        $member = $this->actingAs($user)->getJson('/assistant/context')->assertOk();
        $member->assertJsonStructure(['user' => ['first_name', 'points', 'bookings', 'watchlist', 'pick', 'next']]);
        $this->assertStringContainsString('no-store', (string) $member->headers->get('Cache-Control'));
    }
}
