@extends('layouts.app')

@section('title', 'Privacy Policy | BookMyMovie')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-8 lg:grid-cols-[.8fr_1.2fr]">
                <div>
                    <p class="text-sm font-black uppercase tracking-[.22em] text-gold">Legal center</p>
                    <h1 class="mt-4 text-4xl font-black leading-tight text-white sm:text-5xl">Privacy Policy</h1>
                    <p class="mt-5 text-sm leading-7 text-gray-300">
                        This policy explains how BookMyMovie handles account, booking, contact, wishlist, review, and
                        checkout information inside this Laravel cinema booking project.
                    </p>
                    <div class="mt-6 rounded-lg border border-red-500/20 bg-red-950/20 p-5">
                        <p class="text-sm font-black uppercase text-red-200">Last updated</p>
                        <p class="mt-2 text-2xl font-black text-white">July 24, 2026</p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach([
                        ['title' => 'Data minimization', 'copy' => 'Forms collect only the details needed for accounts, booking, and support.'],
                        ['title' => 'No disposable emails', 'copy' => 'Temporary email domains are blocked to keep resets and support replies reliable.'],
                        ['title' => 'Captcha checks', 'copy' => 'Google reCAPTCHA v2 and v3 can protect public forms when keys are configured.'],
                        ['title' => 'User control', 'copy' => 'Users can manage profile, wishlist, cart, and booking activity from the account area.'],
                    ] as $card)
                        <article class="premium-tilt rounded-lg border border-white/10 bg-gray-900 p-5">
                            <h2 class="text-lg font-black text-white">{{ $card['title'] }}</h2>
                            <p class="mt-3 text-sm leading-6 text-gray-400">{{ $card['copy'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>

            <div class="mt-10 overflow-hidden rounded-lg border border-white/10 bg-gray-900">
                <table class="w-full min-w-[780px] text-left text-sm text-gray-300">
                    <thead class="bg-gray-950 text-xs uppercase tracking-[.18em] text-red-300">
                        <tr>
                            <th class="p-4">Data type</th>
                            <th class="p-4">Purpose</th>
                            <th class="p-4">Example fields</th>
                            <th class="p-4">Retention approach</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @foreach([
                            ['Account', 'Login, dashboard, booking history', 'Name, email, phone, password hash', 'Kept while the account exists'],
                            ['Booking', 'Seat reservation and ticket tracking', 'Movie, show, seats, booking number', 'Kept for records and project reports'],
                            ['Support', 'Answer contact form messages', 'Name, email, message body', 'Kept until resolved or cleaned by admin'],
                            ['Security', 'Spam and abuse prevention', 'Captcha token result, IP during verification', 'Used at submission time'],
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
                    ['title' => 'How information is used', 'items' => ['Create and authenticate user accounts.', 'Maintain carts, wishlists, reviews, and bookings.', 'Send support responses and reset-password links.', 'Improve project usability and admin workflows.']],
                    ['title' => 'How information is protected', 'items' => ['Passwords are stored using Laravel hashing.', 'Forms use CSRF protection and validation rules.', 'Captcha can be enabled through environment keys.', 'Admin workflows separate operational records from public pages.']],
                    ['title' => 'Your responsibilities', 'items' => ['Use a real email address you can access.', 'Keep your password private and unique.', 'Do not submit fake, abusive, or misleading contact messages.', 'Contact support with enough booking detail to verify requests.']],
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
