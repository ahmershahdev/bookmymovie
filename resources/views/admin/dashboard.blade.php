@extends('layouts.admin')

@section('title', 'Admin Dashboard | BookMyMovie')

@section('content')
@php
    $tabs = [
        'dashboard' => 'Dashboard',
        'movies' => 'Movies',
        'trashbin' => 'Trashbin',
        'shows' => 'Theaters & Shows',
        'bookings' => 'Bookings',
        'coupons' => 'Coupons',
        'users' => 'Users',
        'customers' => 'Customer Info',
        'reviews' => 'Reviews',
        'notifications' => 'Notifications',
        'profile' => 'Admin Profile',
    ];
@endphp

<section x-data="{ tab: 'dashboard' }">
    <div class="flex flex-col gap-4 border-b border-white/10 pb-6 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-sm font-black uppercase tracking-[.22em] text-red-400">Admin</p>
            <h1 class="mt-2 text-4xl font-black text-white">Control center</h1>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('home') }}"
                class="rounded-full border border-white/10 bg-white/5 px-5 py-3 text-sm font-black text-white hover:border-red-400/60">View site</a>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="rounded-full bg-red-600 px-5 py-3 text-sm font-black text-white hover:bg-red-500">Logout</button>
            </form>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[260px_1fr]">
        <div class="rounded-lg border border-white/10 bg-gray-900 p-3">
            @foreach($tabs as $key => $label)
                <button type="button" @click="tab = '{{ $key }}'"
                    class="mb-1 flex w-full items-center justify-between rounded-md px-4 py-3 text-left text-sm font-bold transition last:mb-0"
                    :class="tab === '{{ $key }}' ? 'bg-red-600 text-white' : 'text-gray-300 hover:bg-red-950/30 hover:text-white'">
                    {{ $label }}
                    <span class="text-xs">{{ $loop->iteration }}</span>
                </button>
            @endforeach
        </div>

        <div class="min-h-[640px] rounded-lg border border-white/10 bg-gray-900 p-5">
            <div x-show="tab === 'dashboard'">
                <x-section-heading eyebrow="Tab 1" title="Dashboard stats"
                    description="Revenue, bookings, users, and top movies summary." />
                <div class="mt-6 grid gap-4 md:grid-cols-4">
                    <x-stat-card label="Revenue" value="PKR {{ number_format($stats['revenue']) }}" />
                    <x-stat-card label="Bookings" :value="$stats['bookings']" />
                    <x-stat-card label="Users" :value="$stats['users']" />
                    <x-stat-card label="Top movie" :value="$stats['topMovie']" />
                </div>
            </div>

            <div x-show="tab === 'movies'">
                <x-section-heading eyebrow="Tab 2" title="Movies" description="Add movies and review recent records." />
                <form method="POST" action="{{ route('admin.dashboard') }}"
                    class="mt-6 grid gap-4 rounded-lg bg-gray-950 p-4 md:grid-cols-2">
                    @csrf
                    <label><span class="text-sm font-bold text-gray-300">Title</span><input name="title"
                            class="mt-2 w-full rounded-md border-white/10 bg-gray-900 text-white focus:border-red-400 focus:ring-red-400"></label>
                    @error('title')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                    <label><span class="text-sm font-bold text-gray-300">Slug</span><input name="slug"
                            class="mt-2 w-full rounded-md border-white/10 bg-gray-900 text-white focus:border-red-400 focus:ring-red-400"></label>
                    @error('slug')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                    <button
                        class="rounded-full bg-red-600 px-5 py-3 text-sm font-black text-white hover:bg-red-500 md:col-span-2">Save movie</button>
                </form>
                <x-admin-list :items="$moviesList" action="Recent" />
            </div>

            <div x-show="tab === 'trashbin'">
                <x-section-heading eyebrow="Tab 3" title="Trashbin" description="Soft-deleted movies." />
                <x-admin-list :items="$trashedMovies" action="Restore" />
            </div>

            <div x-show="tab === 'shows'">
                <x-section-heading eyebrow="Tab 4" title="Theaters and shows" description="Upcoming show schedule." />
                <x-admin-list :items="$showsList" action="Schedule" />
            </div>

            <div x-show="tab === 'bookings'">
                <x-section-heading eyebrow="Tab 5" title="Bookings" description="Recent booking numbers." />
                <x-admin-list :items="$bookingsList" action="Open" />
            </div>

            <div x-show="tab === 'coupons'">
                <x-section-heading eyebrow="Tab 6" title="Coupons" description="Create coupons and review existing codes." />
                <form method="POST" action="{{ route('admin.dashboard') }}"
                    class="mt-6 grid gap-4 rounded-lg bg-gray-950 p-4 md:grid-cols-3">
                    @csrf
                    <label><span class="text-sm font-bold text-gray-300">Code</span><input name="code"
                            class="mt-2 w-full rounded-md border-white/10 bg-gray-900 text-white focus:border-red-400 focus:ring-red-400"></label>
                    @error('code')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                    <label><span class="text-sm font-bold text-gray-300">Discount %</span><input name="discount"
                            class="mt-2 w-full rounded-md border-white/10 bg-gray-900 text-white focus:border-red-400 focus:ring-red-400"></label>
                    @error('discount')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                    <button
                        class="self-end rounded-full bg-red-600 px-5 py-3 text-sm font-black text-white hover:bg-red-500">Create coupon</button>
                </form>
                <x-admin-list :items="$couponsList" action="Code" />
            </div>

            <div x-show="tab === 'users'">
                <x-section-heading eyebrow="Tab 7" title="Users" description="Recent user accounts." />
                <x-admin-list :items="$usersList" action="Manage" />
            </div>

            <div x-show="tab === 'customers'">
                <x-section-heading eyebrow="Tab 8" title="Customer info" description="Top customers by spend." />
                <x-admin-list :items="$customerList" action="Stats" />
            </div>

            <div x-show="tab === 'reviews'">
                <x-section-heading eyebrow="Tab 9" title="Reviews" description="Latest review activity." />
                <x-admin-list :items="$reviewsList" action="Moderate" />
            </div>

            <div x-show="tab === 'notifications'">
                <x-section-heading eyebrow="Tab 10" title="Notifications" description="Send a message to all users." />
                <form method="POST" action="{{ route('admin.dashboard') }}"
                    class="mt-6 grid gap-4 rounded-lg bg-gray-950 p-4">
                    @csrf
                    <label><span class="text-sm font-bold text-gray-300">Message</span><textarea name="message" rows="4"
                            class="mt-2 w-full rounded-md border-white/10 bg-gray-900 text-white focus:border-red-400 focus:ring-red-400"></textarea></label>
                    @error('message')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                    <button
                        class="w-max rounded-full bg-red-600 px-5 py-3 text-sm font-black text-white hover:bg-red-500">Send notification</button>
                </form>
            </div>

            <div x-show="tab === 'profile'">
                <x-section-heading eyebrow="Tab 11" title="Admin profile" description="Update name, email, and password." />
                <form method="POST" action="{{ route('admin.dashboard') }}"
                    class="mt-6 grid gap-4 rounded-lg bg-gray-950 p-4 md:grid-cols-2">
                    @csrf
                    <label><span class="text-sm font-bold text-gray-300">Name</span><input name="name"
                            value="{{ old('name', $admin->name) }}"
                            class="mt-2 w-full rounded-md border-white/10 bg-gray-900 text-white focus:border-red-400 focus:ring-red-400"></label>
                    @error('name')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                    <label><span class="text-sm font-bold text-gray-300">Email</span><input name="email" type="email"
                            value="{{ old('email', $admin->email) }}"
                            class="mt-2 w-full rounded-md border-white/10 bg-gray-900 text-white focus:border-red-400 focus:ring-red-400"></label>
                    @error('email')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                    <label class="md:col-span-2"><span class="text-sm font-bold text-gray-300">New password</span><input name="password" type="password"
                            class="mt-2 w-full rounded-md border-white/10 bg-gray-900 text-white focus:border-red-400 focus:ring-red-400"></label>
                    @error('password')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                    <button
                        class="rounded-full bg-red-600 px-5 py-3 text-sm font-black text-white hover:bg-red-500 md:col-span-2">Save profile</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
