@extends('layouts.app')

@section('title', 'Terms of Service | BookMyMovie')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-8 lg:grid-cols-[.8fr_1.2fr]">
                <div>
                    <p class="text-sm font-black uppercase tracking-[.22em] text-gold">Legal center</p>
                    <h1 class="mt-4 text-4xl font-black leading-tight text-white sm:text-5xl">Terms of Service</h1>
                    <p class="mt-5 text-sm leading-7 text-gray-300">
                        These terms describe responsible use of BookMyMovie, including account access, seat selection,
                        COD checkout, bookings, reviews, support messages, and admin-managed content.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach([
                        ['title' => 'Use real details', 'copy' => 'Bookings and support messages require a reachable email and accurate customer information.'],
                        ['title' => 'Respect seat holds', 'copy' => 'Seat selections may expire or change until checkout is completed.'],
                        ['title' => 'Follow theater rules', 'copy' => 'Counter collection, timing, and cancellation rules may depend on the selected cinema.'],
                        ['title' => 'Keep accounts secure', 'copy' => 'Users are responsible for protecting passwords and account sessions.'],
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
                            <th class="p-4">Area</th>
                            <th class="p-4">Allowed use</th>
                            <th class="p-4">Not allowed</th>
                            <th class="p-4">Why it matters</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @foreach([
                            ['Accounts', 'Register with accurate details', 'Fake identity or disposable email use', 'Keeps support and resets reliable'],
                            ['Bookings', 'Reserve seats for real cinema visits', 'Holding seats without intent to attend', 'Keeps availability fair'],
                            ['Reviews', 'Share genuine movie feedback', 'Spam, abuse, or misleading claims', 'Keeps ratings useful'],
                            ['Contact form', 'Ask concise support questions', 'Harassment, spam, or unrelated promotion', 'Protects support quality'],
                            ['Admin data', 'Authorized admin management only', 'Unauthorized access or tampering', 'Protects project records'],
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
                    ['title' => 'Booking terms', 'items' => ['A booking is created only after checkout completes.', 'Seat availability can change before checkout.', 'COD collection follows cinema counter rules.', 'Booking numbers should be saved for support.']],
                    ['title' => 'Account terms', 'items' => ['You must use a valid email address.', 'You are responsible for account activity.', 'Blocked accounts may lose access to booking tools.', 'Password reset requires the registered email.']],
                    ['title' => 'Project scope', 'items' => ['BookMyMovie is an academic Laravel cinema booking project.', 'Policy text supports project demonstration and UX completeness.', 'Final legal review is recommended before production use.', 'Feature availability depends on database and admin configuration.']],
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
