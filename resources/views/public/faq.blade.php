@extends('layouts.app')

@section('title', 'FAQ | BookMyMovie')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl">
            <x-section-heading eyebrow="FAQ" title="Common questions" description="Answers managed from the database." />
            <div class="mt-8 space-y-3" x-data="{ open: 1 }">
                @foreach($faqs as $faq)
                    <article class="rounded-lg border border-white/10 bg-gray-900">
                        <button type="button" @click="open = open === {{ $loop->iteration }} ? 0 : {{ $loop->iteration }}"
                            class="flex w-full items-center justify-between p-5 text-left font-bold text-white">
                            {{ $faq->question }} <span x-text="open === {{ $loop->iteration }} ? '-' : '+'"></span>
                        </button>
                        <p x-show="open === {{ $loop->iteration }}" class="px-5 pb-5 text-sm leading-6 text-gray-300">{{ $faq->answer }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endsection
