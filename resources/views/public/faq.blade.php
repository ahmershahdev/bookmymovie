@extends('layouts.app')

@section('title', 'FAQ | BookMyMovie')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-8 lg:grid-cols-[.8fr_1.2fr]">
                <div>
                    <p class="text-sm font-black uppercase tracking-[.22em] text-gold">Help center</p>
                    <h1 class="mt-4 text-4xl font-black leading-tight text-white sm:text-5xl">Frequently asked questions</h1>
                    <p class="mt-5 text-sm leading-7 text-gray-300">
                        Quick answers for booking, account access, seat selection, COD payment, cancellations, and support.
                        Use this page before contacting support when you need a fast answer.
                    </p>

                    <div class="mt-8 grid gap-4">
                        @foreach([
                            ['title' => 'Booking flow', 'copy' => 'Browse movies, choose a show, select seats, add to cart, and checkout.'],
                            ['title' => 'Account help', 'copy' => 'Login, register, password reset, wishlist, profile, and booking history.'],
                            ['title' => 'Ticket support', 'copy' => 'Use your booking number when asking about counter collection or refunds.'],
                        ] as $card)
                            <article class="premium-tilt rounded-lg border border-white/10 bg-gray-900 p-5">
                                <h2 class="text-lg font-black text-white">{{ $card['title'] }}</h2>
                                <p class="mt-2 text-sm leading-6 text-gray-400">{{ $card['copy'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-lg border border-white/10 bg-gray-900 p-5 shadow-2xl shadow-black/30">
                    <div class="overflow-hidden rounded-lg border border-white/10">
                        <table class="w-full min-w-[640px] text-left text-sm text-gray-300">
                            <thead class="bg-gray-950 text-xs uppercase tracking-[.18em] text-red-300">
                                <tr>
                                    <th class="p-4">Topic</th>
                                    <th class="p-4">Best action</th>
                                    <th class="p-4">Useful detail</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                @foreach([
                                    ['Booking issue', 'Check cart and booking page', 'Booking number, movie, city, show time'],
                                    ['Seat question', 'Open seat map again', 'Seat row, category, and screen'],
                                    ['Refund request', 'Read refund policy first', 'Cancellation time and payment mode'],
                                    ['Login problem', 'Use forgot password', 'Registered email address'],
                                ] as $row)
                                    <tr class="transition hover:bg-white/[.03]">
                                        <td class="p-4 font-bold text-white">{{ $row[0] }}</td>
                                        <td class="p-4">{{ $row[1] }}</td>
                                        <td class="p-4">{{ $row[2] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @php
                $defaultFaqs = collect([
                    ['question' => 'How do I book movie tickets?', 'answer' => 'Open Movies, choose a movie, select an available show, pick seats, add them to cart, and complete checkout from your account.'],
                    ['question' => 'Can I use temporary email addresses?', 'answer' => 'No. BookMyMovie blocks disposable email domains because support replies, reset links, and booking records must remain reachable.'],
                    ['question' => 'How does COD ticket collection work?', 'answer' => 'After checkout, keep your booking number ready and pay at the cinema counter according to the selected show and theater rules.'],
                    ['question' => 'Can I cancel a booking?', 'answer' => 'Cancellation depends on the show cutoff and theater policy. Read the refund policy before sending a cancellation request.'],
                    ['question' => 'What should I include in a support message?', 'answer' => 'Include your city, booking number, movie name, show date, show time, and a short description of the issue.'],
                    ['question' => 'Where can I see my previous bookings?', 'answer' => 'Login to your account dashboard and open the bookings section to review booking history and ticket details.'],
                ]);
                $visibleFaqs = $faqs->isNotEmpty() ? $faqs : $defaultFaqs;
            @endphp

            <div class="mt-10 grid gap-4 lg:grid-cols-2" x-data="{ open: 1 }">
                @foreach($visibleFaqs as $faq)
                    <article class="premium-tilt rounded-lg border border-white/10 bg-gray-900">
                        <button type="button" @click="open = open === {{ $loop->iteration }} ? 0 : {{ $loop->iteration }}"
                            class="flex w-full items-center justify-between gap-4 p-5 text-left">
                            <span class="text-base font-black text-white">{{ is_array($faq) ? $faq['question'] : $faq->question }}</span>
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/[.04] text-lg font-black text-red-200"
                                x-text="open === {{ $loop->iteration }} ? '-' : '+'"></span>
                        </button>
                        <p x-show="open === {{ $loop->iteration }}" x-transition
                            class="px-5 pb-5 text-sm leading-7 text-gray-300">
                            {{ is_array($faq) ? $faq['answer'] : $faq->answer }}
                        </p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endsection
