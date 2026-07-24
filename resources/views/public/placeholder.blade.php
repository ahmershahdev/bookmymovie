@extends('layouts.app')

@section('title', $title . ' | BookMyMovie')

@section('content')
    <section
        class="relative flex min-h-[75vh] items-center justify-center overflow-hidden bg-gray-950 px-6 py-24 text-center text-white">
        <!-- Ambient Background Glows -->
        <div
            class="pointer-events-none absolute -top-40 left-1/2 -z-10 h-[500px] w-[500px] -translate-x-1/2 rounded-full bg-red-600/20 blur-[120px]">
        </div>
        <div
            class="pointer-events-none absolute -bottom-40 right-10 -z-10 h-[350px] w-[350px] rounded-full bg-amber-500/10 blur-[100px]">
        </div>

        <!-- Subtle Background Grid Pattern -->
        <div
            class="absolute inset-0 -z-10 bg-[linear-gradient(to_right,#1f293715_1px,transparent_1px),linear-gradient(to_bottom,#1f293715_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_50%,#000_70%,transparent_100%)]">
        </div>

        <div class="mx-auto max-w-3xl">
            <!-- Glassmorphic Badge -->
            <div
                class="inline-flex items-center gap-2 rounded-full border border-red-500/30 bg-red-500/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-red-400 backdrop-blur-md">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-red-500"></span>
                </span>
                BookMyMovie
            </div>

            <!-- Gradient Title -->
            <h1
                class="mt-6 bg-gradient-to-b from-white via-gray-100 to-gray-400 bg-clip-text text-4xl font-black tracking-tight text-transparent sm:text-6xl lg:text-7xl">
                {{ $title }}
            </h1>

            <!-- Subtitle Description -->
            <p class="mx-auto mt-6 max-w-xl text-base leading-relaxed text-gray-400 sm:text-lg">
                This route is reserved for Section 4 and ready for the next Blade template. Experience seamless cinema
                booking, real-time seats, and instant tickets.
            </p>

            <!-- Action Buttons -->
            <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row sm:gap-4">
                <a href="{{ route('home') }}"
                    class="group inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-red-600 to-red-500 px-7 py-3.5 text-sm font-bold text-white shadow-lg shadow-red-600/30 transition-all duration-300 hover:scale-[1.03] hover:shadow-red-600/50 active:scale-[0.98]">
                    <svg class="h-4 w-4 transition-transform group-hover:-translate-x-1" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Home
                </a>

                <a href="#"
                    class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-7 py-3.5 text-sm font-semibold text-gray-300 backdrop-blur-md transition-all duration-300 hover:border-white/20 hover:bg-white/10 hover:text-white">
                    Explore Movies
                </a>
            </div>
        </div>
    </section>
@endsection