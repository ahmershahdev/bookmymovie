@extends('layouts.app')

@section('title', 'E-Ticket Info | BookMyMovie')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl"><x-section-heading eyebrow="E-Ticket" title="E-ticket delivery information"
                description="After checkout, users receive a booking number and QR-ready ticket detail page." />
            <div class="mt-8 rounded-lg border border-white/10 bg-gray-900 p-6 text-gray-300">Show your booking number at
                the counter and pay COD to collect tickets.</div>
        </div>
    </section>
@endsection