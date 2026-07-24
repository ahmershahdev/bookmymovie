@extends('layouts.app')

@section('title', 'Contact | BookMyMovie')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-6xl gap-8 lg:grid-cols-[.8fr_1.2fr]">
            <div>
                <x-section-heading eyebrow="Contact" title="Talk to support"
                    description="For support, partnership, or booking questions in Pakistan." />
                <p class="mt-6 text-gray-300">Email: <a href="mailto:support@ahmershah.dev"
                        class="font-bold text-red-300">support@ahmershah.dev</a></p>
            </div>
            <form method="POST" action="{{ route('contact') }}" class="rounded-lg border border-white/10 bg-gray-900 p-6">
                @csrf
                <div class="grid gap-4">
                    <label><span class="text-sm font-bold text-gray-300">Name</span><input name="name"
                            class="mt-2 w-full rounded-md border-white/10 bg-gray-950 text-white focus:border-red-400 focus:ring-red-400"></label>
                    @error('name')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                    <label><span class="text-sm font-bold text-gray-300">Email</span><input name="email" type="email"
                            class="mt-2 w-full rounded-md border-white/10 bg-gray-950 text-white focus:border-red-400 focus:ring-red-400"></label>
                    @error('email')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                    <label><span class="text-sm font-bold text-gray-300">Message</span><textarea name="message" rows="5"
                            class="mt-2 w-full rounded-md border-white/10 bg-gray-950 text-white focus:border-red-400 focus:ring-red-400"></textarea></label>
                    @error('message')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                    <button class="rounded-full bg-red-600 px-6 py-3 text-sm font-black text-white hover:bg-red-500">Send
                        message</button>
                </div>
            </form>
        </div>
    </section>
@endsection