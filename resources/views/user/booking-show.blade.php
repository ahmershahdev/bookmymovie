@extends('layouts.app')

@section('title', 'Booking Detail | BookMyMovie')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-6xl gap-8 lg:grid-cols-[1fr_340px]">
            <div class="rounded-lg border border-white/10 bg-gray-900 p-6">
                <p class="text-sm font-black uppercase tracking-[.22em] text-red-400">Booking Detail</p>
                <h1 class="mt-3 text-4xl font-black text-white">{{ $booking->booking_number }}</h1>
                <dl class="mt-8 grid gap-4 text-sm text-gray-300 sm:grid-cols-2">
                    <div class="rounded-md bg-gray-950 p-4">
                        <dt>Movie</dt>
                        <dd class="mt-1 font-bold text-white">{{ $booking->show->movie->title }}</dd>
                    </div>
                    <div class="rounded-md bg-gray-950 p-4">
                        <dt>Seats</dt>
                        <dd class="mt-1 font-bold text-white">{{ $booking->seats->map(fn($seat) => $seat->seat->row_label.$seat->seat->seat_number)->join(', ') }}</dd>
                    </div>
                    <div class="rounded-md bg-gray-950 p-4">
                        <dt>Payment</dt>
                        <dd class="mt-1 font-bold text-red-300">{{ strtoupper($booking->payment_method) }} - {{ str($booking->payment_status)->headline() }}</dd>
                    </div>
                    <div class="rounded-md bg-gray-950 p-4">
                        <dt>Total</dt>
                        <dd class="mt-1 font-bold text-white">PKR {{ number_format((float) $booking->total_amount) }}</dd>
                    </div>
                </dl>
                <a href="{{ route('user.tracking', $booking->booking_number) }}"
                    class="mt-8 inline-flex rounded-full bg-red-600 px-6 py-3 text-sm font-black text-white hover:bg-red-500">Track
                    booking</a>
            </div>
            <aside class="rounded-lg border border-white/10 bg-gray-900 p-6 text-center">
                <div class="mx-auto grid h-48 w-48 place-items-center rounded-lg bg-white p-4 text-center text-gray-950">
                    <span class="break-all text-sm font-black">{{ $booking->booking_number }}</span>
                </div>
                <p class="mt-4 text-sm text-gray-300">Show this ticket at the cinema counter.</p>
            </aside>
        </div>
    </section>
@endsection
