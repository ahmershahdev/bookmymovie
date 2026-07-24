@extends('layouts.app')

@section('title', '404 | BookMyMovie')

@section('content')
    <section class="flex min-h-[70vh] items-center justify-center bg-gray-950 px-4 pt-28 text-center">
        <div>
            <p class="text-sm font-black uppercase tracking-[.25em] text-red-400">404</p>
            <h1 class="mt-4 text-5xl font-black text-white">Page not found</h1>
            <p class="mt-4 text-gray-300">This screen is ready as the custom not-found page from Section 4.</p>
            <a href="{{ route('home') }}"
                class="mt-8 inline-flex rounded-full bg-red-600 px-6 py-3 text-sm font-black text-white hover:bg-red-500">Go
                home</a>
        </div>
    </section>
@endsection