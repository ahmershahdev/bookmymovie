@extends('layouts.app')

@section('title', 'Checkout | BookMyMovie')
@section('meta_description', 'Enter checkout details, verify captcha, review selected seats, and place your BookMyMovie COD booking.')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-6xl gap-8 lg:grid-cols-[1fr_340px]">
            <form method="POST" action="{{ route('user.checkout') }}"
                class="rounded-lg border border-white/10 bg-gray-900 p-6">
                @csrf
                <h1 class="text-3xl font-black text-white">Checkout</h1>
                <p class="mt-2 text-sm text-gray-300">COD only. Pay at cinema counter. Each booking is capped at 4 seats.</p>
                @error('cart')<p class="mt-4 rounded-md border border-red-500/30 bg-red-950/50 p-3 text-sm font-bold text-red-100">{{ $message }}</p>@enderror
                <div class="mt-6 grid gap-4">
                    <label><span class="text-sm font-bold text-gray-300">Full name</span><input name="name"
                            value="{{ old('name', auth()->user()->name) }}"
                            class="mt-2 w-full rounded-md border-white/10 bg-gray-950 text-white focus:border-red-400 focus:ring-red-400"></label>
                    @error('name')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                    <label><span class="text-sm font-bold text-gray-300">Email</span><input name="email" type="email"
                            value="{{ old('email', auth()->user()->email) }}"
                            class="mt-2 w-full rounded-md border-white/10 bg-gray-950 text-white focus:border-red-400 focus:ring-red-400"></label>
                    @error('email')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                    <label><span class="text-sm font-bold text-gray-300">Phone</span><input name="phone"
                            value="{{ old('phone', auth()->user()->phone) }}"
                            class="mt-2 w-full rounded-md border-white/10 bg-gray-950 text-white focus:border-red-400 focus:ring-red-400"></label>
                    @error('phone')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                    <label><span class="text-sm font-bold text-gray-300">Address</span><textarea name="address" rows="3"
                            class="mt-2 w-full rounded-md border-white/10 bg-gray-950 text-white focus:border-red-400 focus:ring-red-400">{{ old('address', auth()->user()->address) }}</textarea></label>
                    @error('address')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                    <label><span class="text-sm font-bold text-gray-300">Coupon code</span><input name="coupon_code"
                            value="{{ old('coupon_code') }}" placeholder="Optional"
                            class="mt-2 w-full rounded-md border-white/10 bg-gray-950 uppercase text-white focus:border-red-400 focus:ring-red-400"></label>
                    @error('coupon_code')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                    <x-recaptcha action="checkout" />
                    <button @disabled($cartItems->isEmpty())
                        class="rounded-full bg-red-600 px-5 py-3 text-sm font-black text-white hover:bg-red-500 disabled:cursor-not-allowed disabled:bg-gray-700">Place
                        COD booking</button>
                </div>
            </form>
            <aside class="rounded-lg border border-white/10 bg-gray-900 p-6">
                <h2 class="font-black text-white">Order total</h2>
                <p class="mt-4 text-3xl font-black text-gold">PKR {{ number_format($total) }}</p>
                <p class="mt-2 text-xs font-bold text-gray-400">Coupon discounts are applied after validation during final booking.</p>
                <div class="mt-5 space-y-2 text-sm text-gray-300">
                    @foreach($cartItems as $item)
                        <p>{{ $item->show->movie->title }} - seat {{ $item->seat->row_label }}{{ $item->seat->seat_number }} - PKR {{ number_format((float) $item->price) }}</p>
                    @endforeach
                </div>
            </aside>
        </div>
    </section>
@endsection
