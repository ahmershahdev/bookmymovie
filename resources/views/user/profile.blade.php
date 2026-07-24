@extends('layouts.app')

@section('title', 'Profile | BookMyMovie')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('user.profile') }}"
            class="mx-auto max-w-3xl rounded-lg border border-white/10 bg-gray-900 p-6">
            @csrf
            <h1 class="text-3xl font-black text-white">Profile</h1>
            <div class="mt-6 grid gap-4">
                <label><span class="text-sm font-bold text-gray-300">Name</span><input name="name"
                        value="{{ old('name', $user->name) }}"
                        class="mt-2 w-full rounded-md border-white/10 bg-gray-950 text-white focus:border-red-400 focus:ring-red-400"></label>
                @error('name')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                <label><span class="text-sm font-bold text-gray-300">Email</span><input name="email" type="email"
                        value="{{ old('email', $user->email) }}"
                        class="mt-2 w-full rounded-md border-white/10 bg-gray-950 text-white focus:border-red-400 focus:ring-red-400"></label>
                @error('email')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                <label><span class="text-sm font-bold text-gray-300">Date of birth</span><input name="date_of_birth"
                        type="date" value="{{ old('date_of_birth', optional($user->date_of_birth)->format('Y-m-d')) }}"
                        class="mt-2 w-full rounded-md border-white/10 bg-gray-950 text-white focus:border-red-400 focus:ring-red-400"></label>
                @error('date_of_birth')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                <button class="rounded-full bg-red-600 px-5 py-3 text-sm font-black text-white hover:bg-red-500">Save
                    profile</button>
            </div>
        </form>
    </section>
@endsection
