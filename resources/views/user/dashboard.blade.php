@extends('layouts.app')

@section('title', 'Account | BookMyMovie')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <x-section-heading eyebrow="Account" title="Your dashboard"
                description="Overview of bookings, spending, wishlist, and COD ticket status." />
            <div class="mt-8 grid gap-4 md:grid-cols-4">
                <x-stat-card label="Bookings" :value="$stats['bookings']" />
                <x-stat-card label="Spent" value="PKR {{ number_format($stats['spent']) }}" />
                <x-stat-card label="Wishlist" :value="$stats['wishlist']" />
                <x-stat-card label="Active cart" :value="$stats['cart']" />
            </div>
            <div class="mt-8 grid gap-5 lg:grid-cols-3">
                <a href="{{ route('user.bookings') }}"
                    class="rounded-lg border border-white/10 bg-gray-900 p-6 hover:border-red-500/50">
                    <h2 class="font-black text-white">Recent bookings</h2>
                    <p class="mt-2 text-sm text-gray-300">View tickets and tracking.</p>
                </a>
                <a href="{{ route('user.wishlist') }}"
                    class="rounded-lg border border-white/10 bg-gray-900 p-6 hover:border-red-500/50">
                    <h2 class="font-black text-white">Wishlist</h2>
                    <p class="mt-2 text-sm text-gray-300">Book saved movies quickly.</p>
                </a>
                <a href="{{ route('user.profile') }}"
                    class="rounded-lg border border-white/10 bg-gray-900 p-6 hover:border-red-500/50">
                    <h2 class="font-black text-white">Profile</h2>
                    <p class="mt-2 text-sm text-gray-300">Keep contact details current.</p>
                </a>
            </div>
        </div>
    </section>
@endsection
