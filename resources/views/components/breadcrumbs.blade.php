@props(['auth' => false])

@php
    $routeName = request()->route()?->getName();
    $segments = collect(request()->segments());
    $titleMap = [
        'movies.index' => 'Movies',
        'movies.compare' => 'Compare Movies',
        'search' => 'Search',
        'about' => 'About',
        'contact' => 'Contact',
        'faq' => 'FAQ',
        'terms' => 'Terms of Service',
        'privacy' => 'Privacy Policy',
        'refund' => 'Refund Policy',
        'eticket.info' => 'E-Ticket Info',
        'user.login' => 'Login',
        'user.register' => 'Register',
        'user.signup' => 'Register',
        'password.request' => 'Forgot Password',
        'password.reset' => 'Reset Password',
        'user.dashboard' => 'Dashboard',
        'user.bookings' => 'Bookings',
        'user.booking.show' => 'Booking Details',
        'user.tracking' => 'Track Booking',
        'user.wishlist' => 'Wishlist',
        'user.cart' => 'Cart',
        'user.checkout' => 'Checkout',
        'user.profile' => 'Profile',
        'admin.login' => 'Admin Login',
        'admin.register' => 'Admin Register',
        'admin.dashboard' => 'Admin Dashboard',
    ];

    $items = [['label' => 'Home', 'url' => route('home')]];

    if (request()->routeIs('movies.show', 'movies.seats')) {
        $items[] = ['label' => 'Movies', 'url' => route('movies.index')];

        if (isset($movie)) {
            $items[] = [
                'label' => $movie->title,
                'url' => request()->routeIs('movies.seats') ? route('movies.show', $movie->slug) : null,
            ];
        }

        if (request()->routeIs('movies.seats')) {
            $items[] = ['label' => 'Select Seats', 'url' => null];
        }
    } elseif (request()->routeIs('movies.compare')) {
        $items[] = ['label' => 'Movies', 'url' => route('movies.index')];
        $items[] = ['label' => 'Compare Movies', 'url' => null];
    } elseif (request()->routeIs('terms', 'privacy', 'refund')) {
        $items[] = ['label' => 'Policies', 'url' => null];
        $items[] = ['label' => $titleMap[$routeName] ?? str($segments->last())->headline(), 'url' => null];
    } elseif (request()->routeIs('faq', 'contact', 'eticket.info')) {
        $items[] = ['label' => 'Help Center', 'url' => null];
        $items[] = ['label' => $titleMap[$routeName] ?? str($segments->last())->headline(), 'url' => null];
    } elseif (request()->routeIs(
        'user.dashboard',
        'user.bookings',
        'user.booking.show',
        'user.tracking',
        'user.wishlist',
        'user.cart',
        'user.checkout',
        'user.profile'
    )) {
        $items[] = ['label' => 'Account', 'url' => null];
        $items[] = ['label' => $titleMap[$routeName] ?? str($segments->last())->headline(), 'url' => null];
    } elseif (request()->routeIs('user.login', 'user.register', 'user.signup', 'password.request', 'password.reset')) {
        $items[] = ['label' => 'Account Access', 'url' => null];
        $items[] = ['label' => $titleMap[$routeName] ?? str($segments->last())->headline(), 'url' => null];
    } elseif (request()->routeIs('admin.login', 'admin.register')) {
        $items[] = ['label' => 'Admin', 'url' => null];
        $items[] = ['label' => $titleMap[$routeName] ?? str($segments->last())->headline(), 'url' => null];
    } elseif (request()->routeIs('admin.dashboard')) {
        $items[] = ['label' => 'Admin', 'url' => null];
        $items[] = ['label' => 'Dashboard', 'url' => null];
    } else {
        $items[] = ['label' => $titleMap[$routeName] ?? str($segments->last() ?: 'Page')->replace('-', ' ')->headline(), 'url' => null];
    }
@endphp

@unless(request()->routeIs('home'))
    <nav
        class="{{ $auth
            ? 'mx-auto mb-4 flex w-full max-w-6xl'
            : 'fixed inset-x-0 top-[8.25rem] z-40 px-4 py-2 lg:top-[4.55rem]' }}"
        aria-label="Breadcrumb">
        <ol
            class="{{ $auth ? 'flex' : 'mx-auto flex w-max max-w-[calc(100vw-2rem)]' }} items-center gap-1.5 overflow-x-auto rounded-full border border-white/10 bg-gray-950/90 px-2 py-2 text-xs font-black uppercase tracking-[.12em] text-gray-400 shadow-2xl shadow-black/40 backdrop-blur-xl">
            @foreach($items as $item)
                <li class="flex items-center gap-1.5">
                    @if(! $loop->first)
                        <svg class="h-3.5 w-3.5 shrink-0 text-gold/80" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M7.21 14.77a.75.75 0 01.02-1.06L11.17 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
                                clip-rule="evenodd" />
                        </svg>
                    @endif

                    @if($item['url'] && ! $loop->last)
                        <a href="{{ $item['url'] }}"
                            class="inline-flex items-center rounded-full border border-white/10 bg-white/[.05] px-3.5 py-1.5 text-gray-200 shadow-sm shadow-black/20 transition hover:-translate-y-0.5 hover:border-red-400/50 hover:bg-red-950/35 hover:text-white focus:outline-none focus:ring-2 focus:ring-red-400">
                            {{ $item['label'] }}
                        </a>
                    @elseif($loop->last)
                        <span class="inline-flex items-center rounded-full border border-gold/35 bg-gradient-to-r from-red-950/70 via-gray-900 to-gold/10 px-3.5 py-1.5 text-red-50 shadow-lg shadow-red-950/20"
                            aria-current="page">
                            {{ $item['label'] }}
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full border border-white/10 bg-black/25 px-3.5 py-1.5 text-gray-300">
                            {{ $item['label'] }}
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endunless
