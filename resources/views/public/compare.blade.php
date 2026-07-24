@extends('layouts.app')

@section('title', 'Compare Movies | BookMyMovie')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <x-section-heading eyebrow="Compare" title="Compare movies"
                description="Side-by-side comparison for runtime, certificate, ticket price, rating, and status." />
            <div class="mt-8 overflow-hidden rounded-lg border border-white/10 bg-gray-900">
                <table class="w-full min-w-[760px] text-left text-sm text-gray-300">
                    <thead class="bg-gray-950 text-xs uppercase tracking-[.18em] text-red-300">
                        <tr>
                            <th class="p-4">Movie</th>
                            <th class="p-4">Rating</th>
                            <th class="p-4">Runtime</th>
                            <th class="p-4">Price</th>
                            <th class="p-4">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @foreach($movies as $movie)
                            <tr>
                                <td class="p-4 font-bold text-white">{{ $movie->title }}</td>
                                <td class="p-4">{{ number_format((float) $movie->average_rating, 1) }}</td>
                                <td class="p-4">{{ intdiv($movie->duration_minutes, 60) }}h {{ $movie->duration_minutes % 60 }}m</td>
                                <td class="p-4">PKR {{ number_format($movie->cardPrice()) }}</td>
                                <td class="p-4"><a href="{{ route('movies.show', $movie->slug) }}"
                                        class="font-bold text-red-300 hover:text-red-200">View</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
