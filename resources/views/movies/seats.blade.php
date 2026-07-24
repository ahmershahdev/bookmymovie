@extends('layouts.app')

@section('title', 'Choose Seats | BookMyMovie')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl">
            <x-section-heading eyebrow="Seat Selection" title="Choose your seats"
                description="{{ $movie->title }} at {{ $showDetails->theater_name }} - {{ \Illuminate\Support\Carbon::parse($showDetails->show_date)->format('D, M j') }} {{ \Illuminate\Support\Carbon::parse($showDetails->show_time)->format('h:i A') }}" />
            <form method="POST" action="{{ route('user.cart') }}" class="mt-8 grid gap-8 lg:grid-cols-[1fr_320px]">
                @csrf
                <x-seat-picker :seats="$seats" />
                <aside class="rounded-lg border border-white/10 bg-gray-900 p-6">
                    <h2 class="text-xl font-black text-white">Booking summary</h2>
                    <dl class="mt-5 space-y-3 text-sm text-gray-300">
                        <div class="flex justify-between">
                            <dt>Movie</dt>
                            <dd class="font-bold text-white">{{ $movie->title }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt>Theater</dt>
                            <dd class="font-bold text-white">{{ $showDetails->theater_name }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt>Show</dt>
                            <dd class="font-bold text-white">{{ \Illuminate\Support\Carbon::parse($showDetails->show_time)->format('h:i A') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt>Seat hold</dt>
                            <dd class="font-bold text-red-300">10 minutes</dd>
                        </div>
                    </dl>
                    <input type="hidden" name="show_id" value="{{ $show }}">
                    <label class="mt-6 block">
                        <span class="text-sm font-bold text-gray-300">Ticket type</span>
                        <select name="ticket_type"
                            class="mt-2 w-full rounded-md border-white/10 bg-gray-950 text-white focus:border-red-400 focus:ring-red-400">
                            <option value="adult">Adult</option>
                            <option value="kid">Kid</option>
                        </select>
                    </label>
                    @error('show_id')<p class="mt-3 text-sm text-red-300">{{ $message }}</p>@enderror
                    <button type="submit"
                        class="mt-6 w-full rounded-full bg-red-600 px-5 py-3 text-sm font-black text-white hover:bg-red-500">Add
                        to cart</button>
                </aside>
            </form>
        </div>
    </section>
@endsection
