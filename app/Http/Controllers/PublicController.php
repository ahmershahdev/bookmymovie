<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\Movie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PublicController extends Controller
{
    public function search(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));

        $movies = Movie::query()
            ->with('genres')
            ->when($query, function ($builder) use ($query) {
                $builder->where(function ($builder) use ($query) {
                    $builder->where('title', 'like', "%{$query}%")
                        ->orWhere('language', 'like', "%{$query}%")
                        ->orWhereHas('genres', fn ($genres) => $genres->where('name', 'like', "%{$query}%"));
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
        $movies = Movie::query()
            ->with('genres')
            ->whereIn('status', ['now_showing', 'coming_soon'])
            ->orderByDesc('average_rating')
            ->limit(6)
            ->get();

        return view('public.compare', compact('movies'));
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

    public function submitContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'message' => ['required', 'string', 'max:5000'],
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
}
