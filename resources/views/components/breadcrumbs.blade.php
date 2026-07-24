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
        $items[] = ['label' => 'Account', 'url' => route('user.dashboard')];

        if (! request()->routeIs('user.dashboard')) {
            $items[] = ['label' => $titleMap[$routeName] ?? str($segments->last())->headline(), 'url' => null];
        }
    } elseif (request()->routeIs('admin.dashboard')) {
        $items[] = ['label' => 'Admin Dashboard', 'url' => null];
    } else {
        $items[] = ['label' => $titleMap[$routeName] ?? str($segments->last() ?: 'Page')->replace('-', ' ')->headline(), 'url' => null];
    }
@endphp

@unless(request()->routeIs('home'))
    <nav
        class="{{ $auth
            ? 'mx-auto mb-4 w-full max-w-6xl'
            : 'fixed inset-x-0 top-[8.25rem] z-40 border-y border-white/10 bg-gray-950/88 px-4 py-2 backdrop-blur-xl lg:top-[4.55rem]' }}"
        aria-label="Breadcrumb">
        <ol
            class="{{ $auth ? 'flex' : 'mx-auto flex max-w-7xl' }} items-center gap-2 overflow-x-auto whitespace-nowrap text-xs font-black uppercase tracking-[.12em] text-gray-400">
            @foreach($items as $item)
                <li class="flex items-center gap-2">
                    @if(! $loop->first)
                        <svg class="h-3.5 w-3.5 text-red-400/70" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M7.21 14.77a.75.75 0 01.02-1.06L11.17 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
                                clip-rule="evenodd" />
                        </svg>
                    @endif

                    @if($item['url'] && ! $loop->last)
                        <a href="{{ $item['url'] }}"
                            class="rounded-full border border-white/10 bg-white/[.04] px-3 py-1.5 text-gray-200 shadow-lg shadow-black/20 transition hover:-translate-y-0.5 hover:border-red-400/50 hover:bg-red-950/30 hover:text-white focus:outline-none focus:ring-2 focus:ring-red-400">
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span class="rounded-full border border-red-500/25 bg-red-950/30 px-3 py-1.5 text-red-100 shadow-lg shadow-red-950/20"
                            aria-current="page">
                            {{ $item['label'] }}
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endunless
