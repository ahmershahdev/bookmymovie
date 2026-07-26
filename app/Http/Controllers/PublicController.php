<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\ContentPage;
use App\Models\Faq;
use App\Models\Movie;
use App\Support\FormSecurity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PublicController extends Controller
{
    public function search(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $likeQuery = addcslashes($query, '\\%_');

        $movies = Movie::query()
            ->with('genres')
            ->when($query !== '', function ($builder) use ($likeQuery) {
                $builder->where(function ($builder) use ($likeQuery) {
                    $builder->where('title', 'like', "%{$likeQuery}%")
                        ->orWhere('language', 'like', "%{$likeQuery}%")
                        ->orWhereHas('genres', fn ($genres) => $genres->where('name', 'like', "%{$likeQuery}%"));
                });
            })
            ->latest('release_date')
            ->limit(12)
            ->get();

        return view('public.search', [
            'query' => $query,
            'results' => $movies->map(fn (Movie $movie) => $movie->toCardArray()),
        ]);
    }

    public function compare(): View
    {
        return view('public.compare');
    }

    public function faq(): View
    {
        $faqs = Faq::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('public.faq', compact('faqs'));
    }

    public function contact(): View
    {
        return view('public.contact');
    }

    public function about(): View
    {
        return $this->contentPage('about');
    }

    public function terms(): View
    {
        return $this->contentPage('terms');
    }

    public function privacy(): View
    {
        return $this->contentPage('privacy');
    }

    public function refund(): View
    {
        return $this->contentPage('refund');
    }

    public function eticket(): View
    {
        return $this->contentPage('eticket-info');
    }

    public function submitContact(Request $request): RedirectResponse
    {
        FormSecurity::validateRecaptcha($request, 'contact');

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email:rfc', 'min:6', 'max:150', FormSecurity::disposableEmailRule()],
            'message' => ['required', 'string', 'min:20', 'max:1200'],
        ]);

        ContactMessage::create([
            ...$data,
            'subject' => 'Website contact form',
            'user_id' => Auth::id(),
            'is_read' => false,
            'is_replied' => false,
        ]);

        return back()->with('status', 'Your message has been sent.');
    }

    private function contentPage(string $slug): View
    {
        $page = ContentPage::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return view('public.page', compact('page'));
    }
}
