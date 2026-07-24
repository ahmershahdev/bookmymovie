@extends('layouts.app')

@section('title', 'Refund Policy | BookMyMovie')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl"><x-section-heading eyebrow="Policy" title="Refund and cancellation"
                description="Cancellation windows, expired carts, and counter payment rules are explained here for customers." />
            <div class="prose prose-invert mt-8 max-w-none">
                <p>COD bookings can be cancelled according to theater rules before the configured cutoff.</p>
            </div>
        </div>
    </section>
@endsection