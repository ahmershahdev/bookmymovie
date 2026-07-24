@extends('layouts.app')

@section('title', 'Select Seats | ' . $movie->title)

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 text-gray-100 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[1fr_360px]">
            <div>
                <x-section-heading eyebrow="Seat Selection" :title="$movie->title"
                    description="Choose up to 8 available seats. Held seats expire after 10 minutes." />

                <form method="POST" action="{{ route('user.cart') }}" class="mt-8 space-y-6">
                    @csrf
                    <input type="hidden" name="show_id" value="{{ $showModel->id }}">

                    <div class="rounded-lg border border-white/10 bg-gray-900 p-5">
                        <label class="text-sm font-black text-white" for="ticket_type">Ticket type</label>
                        <select id="ticket_type" name="ticket_type"
                            class="mt-2 w-full rounded-md border-white/10 bg-gray-950 text-white focus:border-red-400 focus:ring-red-400">
                            <option value="adult" @selected(old('ticket_type') === 'adult')>Adult ticket</option>
                            <option value="kid" @selected(old('ticket_type') === 'kid')>Kid ticket</option>
                        </select>
                        @error('ticket_type')<p class="mt-2 text-sm text-red-300">{{ $message }}</p>@enderror
                    </div>

                    <x-seat-picker :seats="$seats" />

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit"
                            class="rounded-full bg-red-600 px-6 py-3 text-sm font-black text-white hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-400">
                            Add selected seats to cart
                        </button>
                        <a href="{{ route('movies.show', $movie->slug) }}"
                            class="rounded-full border border-white/10 bg-white/5 px-6 py-3 text-sm font-black text-white hover:border-red-400/60">
                            Back to showtimes
                        </a>
                    </div>
                </form>
            </div>

            <aside class="h-max rounded-lg border border-white/10 bg-gray-900 p-6 shadow-2xl shadow-black/30">
                <p class="text-xs font-black uppercase tracking-[.22em] text-red-400">Show Details</p>
                <h2 class="mt-3 text-2xl font-black text-white">{{ $showDetails->theater_name }}</h2>
                <dl class="mt-5 space-y-4 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-400">Screen</dt>
                        <dd class="font-bold text-white">{{ $showDetails->screen_name }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-400">Date</dt>
                        <dd class="font-bold text-white">{{ \Illuminate\Support\Carbon::parse($showDetails->show_date)->format('D, M j, Y') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-400">Time</dt>
                        <dd class="font-bold text-white">{{ \Illuminate\Support\Carbon::parse($showDetails->show_time)->format('h:i A') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-400">Seats left</dt>
                        <dd class="font-bold text-emerald-300">{{ $showDetails->available_seats }}</dd>
                    </div>
                </dl>

                <div class="mt-6 rounded-lg border border-white/10 bg-gray-950 p-4 text-xs leading-6 text-gray-300">
                    Prices come from the `show_seat_prices` table. Sale prices are used automatically during cart and
                    checkout.
                </div>
            </aside>
        </div>
    </section>
@endsection
