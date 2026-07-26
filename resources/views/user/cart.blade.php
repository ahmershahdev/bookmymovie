@extends('layouts.app')

@section('title', 'Cart | BookMyMovie')
@section('meta_description', 'Review selected BookMyMovie seats, remove held seats, add more seats, and continue to secure checkout.')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-6xl gap-8 lg:grid-cols-[1fr_340px]">
            <div>
                <x-section-heading eyebrow="Cart" title="Seat cart" description="Held seats expire after 10 minutes. Each booking is capped at 4 seats." />
                @error('cart')<p class="mt-4 text-sm text-red-300">{{ $message }}</p>@enderror
                @error('seats')<p class="mt-4 text-sm text-red-300">{{ $message }}</p>@enderror
                <div class="mt-8 space-y-4">
                    @forelse($cartItems as $item)
                        <article class="grid gap-4 rounded-lg border border-white/10 bg-gray-900 p-5 md:grid-cols-[1fr_auto] md:items-center">
                            <div>
                                <h2 class="font-black text-white">{{ $item->show->movie->title }}</h2>
                                <p class="mt-2 text-sm text-gray-300">
                                    {{ $item->show->screen->theater->name }} -
                                    {{ $item->show->show_date->format('D, M j') }}
                                    {{ \Illuminate\Support\Carbon::parse($item->show->show_time)->format('h:i A') }}
                                </p>
                                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-bold">
                                    <span class="rounded-md bg-white/5 px-3 py-1.5 text-gray-200">Seat {{ $item->seat->row_label }}{{ $item->seat->seat_number }}</span>
                                    <span class="rounded-md bg-white/5 px-3 py-1.5 text-gray-200">{{ $item->category->name }}</span>
                                    <span class="rounded-md bg-white/5 px-3 py-1.5 text-gray-200">{{ str($item->ticket_type)->headline() }}</span>
                                    <span class="rounded-md bg-gold/10 px-3 py-1.5 text-gold">PKR {{ number_format((float) $item->price) }}</span>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 md:justify-end">
                                <form method="POST" action="{{ route('user.cart.remove', $item) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-red-500/30 bg-red-950/30 text-red-100 hover:bg-red-900/50" aria-label="Remove this seat">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/></svg>
                                    </button>
                                </form>
                                <a href="{{ route('movies.seats', ['slug' => $item->show->movie->slug, 'show' => $item->show_id]) }}"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white hover:border-red-400/60 hover:bg-white/10" aria-label="Add more seats for this show">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                                </a>
                            </div>
                        </article>
                    @empty
                        <p class="rounded-lg border border-white/10 bg-gray-900 p-6 text-gray-300">Your cart is empty.</p>
                    @endforelse
                </div>
            </div>
            <aside class="rounded-lg border border-white/10 bg-gray-900 p-6">
                <h2 class="text-xl font-black text-white">Summary</h2>
                <div class="mt-5 flex justify-between text-sm text-gray-300"><span>Total</span><span
                        class="font-black text-white">PKR {{ number_format($total) }}</span></div>
                <a href="{{ route('user.checkout') }}"
                    class="mt-6 block rounded-full bg-red-600 px-5 py-3 text-center text-sm font-black text-white hover:bg-red-500 {{ $cartItems->isEmpty() ? 'pointer-events-none opacity-50' : '' }}">Checkout</a>
            </aside>
        </div>
    </section>
@endsection
