<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\ContentPage;
use App\Models\Coupon;
use App\Models\Faq;
use App\Models\Movie;
use App\Models\Person;
use App\Support\FormSecurity;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Response;

class PublicController extends Controller
{
    public const CONTACT_TOPICS = [
        'booking' => 'A booking I made',
        'account' => 'My account or sign-in',
        'cinema' => 'Cinema facilities or accessibility',
        'partnership' => 'Partnering with BookMyMovie',
        'bug' => 'Report a bug or security issue',
        'other' => 'Something else',
    ];

    private const PAGE_ROUTES = [
        'about' => 'about', 'terms' => 'terms', 'privacy' => 'privacy', 'refund' => 'refund',
        'eticket-info' => 'eticket.info', 'cookies' => 'cookies', 'accessibility' => 'accessibility',
    ];

    public function search(Request $request, ?string $query = null): Response|RedirectResponse
    {
        // Old /search?q=… links move to the clean /search/… address.
        if ($request->filled('q')) {
            return redirect()->route('search', ['query' => mb_substr(trim((string) $request->query('q')), 0, 120)], 301);
        }

        $query = mb_substr(trim(str_replace('/', ' ', rawurldecode((string) $query))), 0, 120);

        $movies = $query === '' ? collect() : Movie::query()
            ->withCardMetrics()
            ->with('genres')
            ->search($query)
            ->orderByRaw("FIELD(status, 'now_showing', 'coming_soon', 'ended')")
            ->limit(24)
            ->get()
            ->map(fn (Movie $movie) => $movie->toCardArray());

        $like = '%'.addcslashes($query, '\\%_').'%';

        $cinemas = $query === '' ? collect() : DB::table('v_theater_catalog')
            ->where('is_active', true)
            ->where(fn ($builder) => $builder->where('name', 'like', $like)->orWhere('city', 'like', $like)->orWhere('address', 'like', $like))
            ->limit(6)
            ->get()
            ->map(fn ($cinema) => ['slug' => $cinema->slug, 'name' => $cinema->name, 'city' => $cinema->city, 'screens' => (int) $cinema->screen_count]);

        $people = $query === '' ? collect() : Person::query()
            ->where('name', 'like', $like)
            ->with(['credits' => fn ($credits) => $credits->with('movie:id,title,slug')->limit(4)])
            ->limit(6)
            ->get()
            ->map(fn (Person $person) => [
                'name' => $person->name,
                'initials' => $person->initials(),
                'known_for' => $person->known_for,
                'movies' => $person->credits->unique('movie_id')->filter(fn ($credit) => $credit->movie)
                    ->map(fn ($credit) => ['title' => $credit->movie->title, 'slug' => $credit->movie->slug])->values(),
            ]);

        return $this->page('Public/Search', [
            'query' => $query,
            'results' => $movies->values(),
            'cinemas' => $cinemas->values(),
            'people' => $people->values(),
            'suggestions' => Movie::query()->where('status', 'now_showing')->orderByDesc('average_rating')->limit(6)->get(['title', 'slug']),
        ], [
            'title' => $query !== '' ? 'Search: '.$query.' | BookMyMovie' : 'Search films, cinemas & people | BookMyMovie',
            'description' => 'Search every film, cinema, actor and director on BookMyMovie.',
        ]);
    }

    public function compare(): Response
    {
        return $this->page('Public/Compare', [
            'catalogue' => Movie::query()
                ->withCardMetrics()
                ->with('genres')
                ->publiclyListed()
                ->orderBy('title')
                ->get()
                ->map(fn (Movie $movie) => $movie->toCardArray())
                ->values(),
        ], [
            'title' => 'Compare Films Side by Side | BookMyMovie',
            'description' => 'Compare up to four films by rating, runtime, certificate, language and ticket price before you choose a showtime.',
            // The bare page is indexable; ?ids= variants are kept out by the canonical and robots.txt.
        ]);
    }

    public function faq(): Response
    {
        $faqs = Faq::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return $this->page('Public/Faq', [
            'faqs' => $faqs->map(fn (Faq $faq) => ['id' => $faq->id, 'category' => $faq->category, 'question' => $faq->question, 'answer' => $faq->answer])->values(),
        ], [
            'title' => 'Help Centre & FAQ | BookMyMovie',
            'description' => 'Answers about seat holds, prices, paying at the counter, cancellations, e-tickets, accounts and cinema facilities.',
            'schema' => [Seo::faqPage($faqs)],
        ]);
    }

    public function contact(): Response
    {
        return $this->page('Public/Contact', [
            'topics' => self::CONTACT_TOPICS,
            'captcha' => FormSecurity::forPage('contact'),
        ], [
            'title' => 'Contact BookMyMovie Support',
            'description' => 'Questions about a booking, your account or partnering with us? Message BookMyMovie support and we reply within one working day.',
            'schema' => [[
                '@type' => 'ContactPage',
                'name' => 'Contact BookMyMovie',
                'url' => Seo::url('contact'),
                'mainEntity' => ['@id' => Seo::url('#organization')],
            ]],
        ]);
    }

    public function offers(): Response
    {
        $offers = Coupon::query()
            ->where('is_active', true)
            ->where('valid_until', '>=', now())
            ->orderBy('valid_until')
            ->get();

        return $this->page('Public/Offers', [
            'offers' => $offers->map(fn (Coupon $offer) => [
                'code' => $offer->code,
                'description' => $offer->description,
                'value' => $offer->discount_type === 'percentage'
                    ? rtrim(rtrim(number_format((float) $offer->discount_value, 2), '0'), '.').'%'
                    : 'PKR '.number_format((float) $offer->discount_value),
                'live' => now()->between($offer->valid_from, $offer->valid_until),
                'starts' => $offer->valid_from?->format('j M'),
                'ends' => $offer->valid_until?->format('j M Y'),
                'min_spend' => 'PKR '.number_format((float) $offer->min_order_amount),
                'max_off' => $offer->max_discount_amount ? 'PKR '.number_format((float) $offer->max_discount_amount) : null,
                'per_user' => (int) $offer->max_uses_per_user,
                'remaining' => $offer->max_uses ? max(0, $offer->max_uses - $offer->used_count) : null,
            ])->values(),
        ], [
            'title' => 'Cinema Ticket Offers & Coupon Codes | BookMyMovie',
            'description' => 'Current BookMyMovie coupon codes and matinée deals. Discounts apply before you confirm your booking, with no hidden fees.',
            'schema' => [[
                '@type' => 'OfferCatalog',
                'name' => 'BookMyMovie offers',
                'itemListElement' => $offers->map(fn ($offer) => [
                    '@type' => 'Offer',
                    'name' => $offer->code,
                    'description' => $offer->description,
                    'validFrom' => $offer->valid_from?->toIso8601String(),
                    'priceValidUntil' => $offer->valid_until?->toDateString(),
                ])->values()->all(),
            ]],
        ]);
    }

    public function about(): Response
    {
        return $this->contentPage('about', [
            'stats' => [
                'films' => Movie::query()->publiclyListed()->count(),
                'cinemas' => DB::table('theaters')->where('is_active', true)->count(),
                'screens' => DB::table('screens')->where('is_active', true)->count(),
                'seats' => (int) DB::table('screens')->where('is_active', true)->sum('total_seats'),
            ],
        ]);
    }

    public function terms(): Response
    {
        return $this->contentPage('terms');
    }

    public function privacy(): Response
    {
        return $this->contentPage('privacy');
    }

    public function refund(): Response
    {
        return $this->contentPage('refund');
    }

    public function eticket(): Response
    {
        return $this->contentPage('eticket-info');
    }

    public function cookies(): Response
    {
        return $this->contentPage('cookies');
    }

    public function accessibility(): Response
    {
        return $this->contentPage('accessibility');
    }

    public function giftCards(): Response
    {
        return $this->page('Public/GiftCards', [
            'pointValue' => \App\Support\BookingLifecycle::POINT_VALUE,
            'pointsPer100' => (int) round(100 * \App\Support\BookingLifecycle::POINTS_PER_RUPEE),
        ], [
            'title' => 'Gift cards and loyalty points | BookMyMovie',
            'description' => 'Check a BookMyMovie gift card balance and see how loyalty points work. Both come off your ticket total at checkout.',
        ]);
    }

    public function giftCardBalance(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:24', 'regex:/^[A-Za-z0-9-]+$/']]);
        $card = \App\Models\GiftCard::query()->where('code', strtoupper(trim($data['code'])))->first();

        if (! $card) {
            return back()->withErrors(['code' => 'We could not find a gift card with that code.']);
        }

        return back()->with('giftCard', [
            'code' => $card->code,
            'balance' => (float) $card->balance,
            'expires' => $card->expires_at?->format('j F Y'),
            'usable' => $card->usable(),
        ]);
    }

    public function submitContact(Request $request): RedirectResponse
    {
        FormSecurity::validateRecaptcha($request, 'contact');

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[\pL\pM .\'-]+$/u'],
            'email' => ['required', 'email:rfc', 'max:150', FormSecurity::disposableEmailRule()],
            'topic' => ['required', Rule::in(array_keys(self::CONTACT_TOPICS))],
            'booking_number' => ['nullable', 'string', 'max:24', 'regex:/^BM-\d{4}-[A-Z0-9]{8}$/i'],
            'message' => ['required', 'string', 'min:20', 'max:2000'],
        ], [
            'booking_number.regex' => 'Booking numbers look like BM-2026-8KQ2Z7TX.',
        ]);

        // The same message sent twice (double submit, refresh, several tabs)
        // is stored once. Cache::add is atomic, so racing copies cannot both pass.
        $fingerprint = 'contact.dup.'.hash('sha256', strtolower($data['email']).'|'.preg_replace('/\s+/', ' ', trim($data['message'])));
        if (! Cache::add($fingerprint, 1, now()->addMinutes(30))) {
            return back()->with('status', 'Thanks, we already have that message and will reply by email.');
        }

        ContactMessage::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'subject' => self::CONTACT_TOPICS[$data['topic']].(! empty($data['booking_number']) ? ' · '.strtoupper($data['booking_number']) : ''),
            'message' => $data['message'],
            'user_id' => Auth::id(),
            'is_read' => false,
            'is_replied' => false,
        ]);

        return back()->with('status', 'Thanks, '.strtok($data['name'], ' ').'. Your message is with our team and we will reply by email.');
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function contentPage(string $slug, array $extra = []): Response
    {
        $page = ContentPage::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $related = ContentPage::query()
            ->where('is_active', true)
            ->where('slug', '<>', $slug)
            ->orderBy('title')
            ->get(['slug', 'title', 'hero_label'])
            ->filter(fn (ContentPage $item) => isset(self::PAGE_ROUTES[$item->slug]))
            ->map(fn (ContentPage $item) => ['title' => $item->title, 'label' => $item->hero_label, 'url' => route(self::PAGE_ROUTES[$item->slug])])
            ->values();

        $sections = collect($page->sections ?? [])->values()->map(fn ($section, $index) => [
            'title' => preg_replace('/^\d+\.\s*/', '', $section['title'] ?? 'Section '.($index + 1)),
            'body' => $section['body'] ?? null,
            'items' => is_array($section['items'] ?? null) ? array_values($section['items']) : [],
        ]);

        $props = [
            'page' => [
                'slug' => $page->slug,
                'title' => $page->title,
                'label' => $page->hero_label,
                'excerpt' => $page->excerpt,
                'paragraphs' => array_values(array_filter(preg_split('/\R{2,}/', trim((string) $page->body)) ?: [])),
                'sections' => $sections,
                'updated' => $page->updated_at?->format('j F Y'),
                'minutes' => max(1, (int) ceil(str_word_count(strip_tags($page->body.' '.json_encode($page->sections))) / 220)),
            ],
            'related' => $related,
            ...$extra,
        ];

        $seo = $slug === 'about' ? [
            'title' => $page->meta_title ?: 'About BookMyMovie',
            'description' => $page->meta_description ?: $page->excerpt,
            'schema' => [[
                '@type' => 'AboutPage',
                'name' => 'About BookMyMovie',
                'url' => Seo::url('about'),
                'about' => ['@id' => Seo::url('#organization')],
                'author' => ['@type' => 'Person', 'name' => 'Syed Ahmer Shah', 'url' => 'https://ahmershah.dev'],
            ]],
        ] : array_filter([
            'title' => $page->meta_title ?: $page->title,
            'description' => $page->meta_description ?: $page->excerpt,
            'canonical' => $page->canonical_path ? Seo::url($page->canonical_path) : null,
            'schema' => [[
                '@type' => 'WebPage',
                'name' => $page->title,
                'description' => Seo::description($page->meta_description ?: $page->excerpt),
                'url' => Seo::url(request()->getPathInfo()),
                'dateModified' => $page->updated_at?->toIso8601String(),
                'isPartOf' => ['@id' => Seo::url('#website')],
            ]],
        ]);

        return $this->page($slug === 'about' ? 'Public/About' : 'Public/Page', $props, $seo);
    }
}
