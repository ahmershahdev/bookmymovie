@extends('layouts.app')

@section('title', 'Cart | BookMyMovie')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-6xl gap-8 lg:grid-cols-[1fr_340px]">
            <div>
                <x-section-heading eyebrow="Cart" title="Seat cart" description="Held seats expire after 10 minutes." />
                @error('cart')<p class="mt-4 text-sm text-red-300">{{ $message }}</p>@enderror
                <div class="mt-8 space-y-4">
                    @forelse($cartItems as $item)
                        <div class="rounded-lg border border-white/10 bg-gray-900 p-5">
                            <h2 class="font-black text-white">{{ $item->show->movie->title }}</h2>
                            <p class="mt-2 text-sm text-gray-300">
                                Seat {{ $item->seat->row_label }}{{ $item->seat->seat_number }} -
                                {{ $item->category->name }} -
                                {{ $item->show->show_date->format('D, M j') }}
                                {{ \Illuminate\Support\Carbon::parse($item->show->show_time)->format('h:i A') }}
                            </p>
                            <p class="mt-2 text-sm font-black text-gold">PKR {{ number_format((float) $item->price) }}</p>
                        </div>
                    @empty
                        <p class="rounded-lg border border-white/10 bg-gray-900 p-6 text-gray-300">Your cart is empty.</p>
                    @endforelse
                </div>
            </div>
            <aside class="rounded-lg border border-white/10 bg-gray-900 p-6">
                <h2 class="text-xl font-black text-white">Summary</h2>
                <div class="mt-5 flex justify-between text-sm text-gray-300"><span>Total</span><span
                        class="font-black text-white">PKR {{ number_format($total) }}</span></div>
                <a href="{{ route('user.checkout') }}"
                    class="mt-6 block rounded-full bg-red-600 px-5 py-3 text-center text-sm font-black text-white hover:bg-red-500">Checkout</a>
            </aside>
        </div>
    </section>
@endsection
