@extends('layouts.app')

@section('title', 'My Bookings | BookMyMovie')
@section('meta_description', 'Review BookMyMovie order history, payment status, selected movie tickets, and booking details.')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl">
            <x-section-heading eyebrow="Bookings" title="My bookings"
                description="All confirmed, pending COD, and cancelled bookings." />
            <div class="mt-8 space-y-4">
                @forelse($bookings as $booking)
                    <article
                        class="grid gap-4 rounded-lg border border-white/10 bg-gray-900 p-5 md:grid-cols-[1fr_auto_auto] md:items-center">
                        <div>
                            <h2 class="font-black text-white">{{ $booking->booking_number }}</h2>
                            <p class="mt-1 text-sm text-gray-300">
                                {{ $booking->show->movie->title }} -
                                {{ $booking->show->show_date->format('D, M j') }}
                                {{ \Illuminate\Support\Carbon::parse($booking->show->show_time)->format('h:i A') }}
                            </p>
                        </div>
                        <span class="rounded-full bg-red-950 px-4 py-2 text-sm font-bold text-red-200">{{ str($booking->payment_status)->headline() }}</span>
                        <a href="{{ route('user.booking.show', $booking->booking_number) }}"
                            class="rounded-full bg-red-600 px-5 py-2 text-center text-sm font-black text-white hover:bg-red-500">View
                            ticket</a>
                    </article>
                @empty
                    <p class="rounded-lg border border-white/10 bg-gray-900 p-6 text-gray-300">No bookings yet.</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection
