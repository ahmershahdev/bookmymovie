@extends('layouts.app')

@section('title', 'Track Booking | BookMyMovie')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl">
            <x-section-heading eyebrow="Tracking" title="Booking progress"
                description="Track COD status and ticket readiness." />
            @php
                $steps = [
                    'Booking placed' => true,
                    'Seats locked' => $booking->booking_status === 'confirmed',
                    'COD pending at counter' => $booking->payment_status === 'pending',
                    'Ticket collected' => $booking->payment_status === 'paid',
                ];
            @endphp
            <div class="mt-8 space-y-4">
                @foreach($steps as $step => $done)
                    <div class="flex items-center gap-4 rounded-lg border border-white/10 bg-gray-900 p-5">
                        <span
                            class="grid h-10 w-10 place-items-center rounded-full {{ $done ? 'bg-red-600' : 'bg-gray-800' }} text-sm font-black text-white">{{ $loop->iteration }}</span>
                        <div>
                            <h2 class="font-black text-white">{{ $step }}</h2>
                            <p class="text-sm text-gray-400">Booking {{ $booking->booking_number }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
