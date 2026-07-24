@extends('layouts.app')

@section('title', $title . ' | BookMyMovie')

@section('content')
    <section class="min-h-[60vh] bg-gray-950 px-4 py-32 text-center text-white">
        <p class="text-sm font-bold uppercase tracking-[.22em] text-red-400">BookMyMovie</p>
        <h1 class="mt-4 text-4xl font-black sm:text-5xl">{{ $title }}</h1>
        <p class="mx-auto mt-4 max-w-xl text-gray-300">This route is reserved from Section 4 and ready for the next Blade
            page.</p>
        <a href="{{ route('home') }}"
            class="mt-8 inline-flex rounded-full bg-red-600 px-6 py-3 text-sm font-bold text-white hover:bg-red-500">Back
            home</a>
    </section>
@endsection