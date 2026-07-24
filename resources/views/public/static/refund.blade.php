@extends('layouts.app')

@section('title', 'Refund Policy | BookMyMovie')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-8 lg:grid-cols-[.85fr_1.15fr]">
                <div>
                    <p class="text-sm font-black uppercase tracking-[.22em] text-gold">Policy center</p>
                    <h1 class="mt-4 text-4xl font-black leading-tight text-white sm:text-5xl">Refund and cancellation policy</h1>
                    <p class="mt-5 text-sm leading-7 text-gray-300">
                        BookMyMovie supports clear cancellation guidance for COD bookings, expired carts, unavailable
                        seats, duplicate submissions, and theater-side schedule changes.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach([
                        ['value' => 'COD', 'label' => 'Primary project payment mode'],
                        ['value' => 'Before cutoff', 'label' => 'Best time to request cancellation'],
                        ['value' => 'Booking #', 'label' => 'Required support reference'],
                    ] as $metric)
                        <article class="premium-tilt rounded-lg border border-white/10 bg-gray-900 p-5">
                            <p class="text-2xl font-black text-white">{{ $metric['value'] }}</p>
                            <p class="mt-2 text-xs font-bold uppercase tracking-[.14em] text-gray-400">{{ $metric['label'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>

            <div class="mt-10 overflow-hidden rounded-lg border border-white/10 bg-gray-900">
                <table class="w-full min-w-[780px] text-left text-sm text-gray-300">
                    <thead class="bg-gray-950 text-xs uppercase tracking-[.18em] text-red-300">
                        <tr>
                            <th class="p-4">Scenario</th>
                            <th class="p-4">Eligibility</th>
                            <th class="p-4">Customer action</th>
                            <th class="p-4">Expected result</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @foreach([
                            ['Cancel before show cutoff', 'May be accepted based on theater rules', 'Send booking number and show time', 'Booking can be cancelled by support/admin'],
                            ['Show already started', 'Usually not eligible', 'Contact cinema counter for exception', 'Refund may be declined'],
                            ['Expired cart', 'No refund needed', 'Start a fresh seat selection', 'Seats become available again'],
                            ['Duplicate booking request', 'Reviewed case by case', 'Share both booking numbers', 'One request may be cancelled if valid'],
                            ['Theater schedule change', 'Usually eligible for help', 'Mention city, movie, and show', 'Support can advise reschedule or cancellation'],
                        ] as $row)
                            <tr class="transition hover:bg-white/[.03]">
                                <td class="p-4 font-bold text-white">{{ $row[0] }}</td>
                                <td class="p-4">{{ $row[1] }}</td>
                                <td class="p-4">{{ $row[2] }}</td>
                                <td class="p-4">{{ $row[3] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-10 grid gap-4 lg:grid-cols-3">
                @foreach([
                    ['title' => 'Before requesting', 'items' => ['Check the show date and time.', 'Confirm your city and theater.', 'Keep your booking number ready.', 'Read any cinema counter instructions.']],
                    ['title' => 'Not usually refundable', 'items' => ['Missed shows after start time.', 'Wrong email entered by the customer.', 'Requests without a booking reference.', 'Policy exceptions denied by theater rules.']],
                    ['title' => 'Support review', 'items' => ['Support checks booking status.', 'Admin may confirm seat and show records.', 'Eligible requests are marked for cancellation.', 'Customer receives the final support response.']],
                ] as $group)
                    <article class="premium-tilt rounded-lg border border-white/10 bg-gray-900 p-6">
                        <h2 class="text-xl font-black text-white">{{ $group['title'] }}</h2>
                        <ul class="mt-4 space-y-3 text-sm leading-6 text-gray-300">
                            @foreach($group['items'] as $item)
                                <li class="rounded-md border border-white/10 bg-white/[.03] p-3">{{ $item }}</li>
                            @endforeach
                        </ul>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endsection
