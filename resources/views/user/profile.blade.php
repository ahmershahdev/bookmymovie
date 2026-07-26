@extends('layouts.app')

@section('title', 'Profile | BookMyMovie')
@section('meta_description', 'Update your BookMyMovie name, email, phone, password, address, and profile picture.')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('user.profile') }}" enctype="multipart/form-data"
            class="mx-auto max-w-3xl rounded-lg border border-white/10 bg-gray-900 p-6">
            @csrf
            <h1 class="text-3xl font-black text-white">Profile</h1>
            <div class="mt-6 grid gap-4">
                <div class="flex items-center gap-4">
                    @if($user->profile_picture)
                        <img src="{{ asset('storage/' . $user->profile_picture) }}" alt="" class="h-20 w-20 rounded-full object-cover ring-2 ring-red-500/40">
                    @else
                        <div class="flex h-20 w-20 items-center justify-center rounded-full bg-red-600 text-2xl font-black text-white">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    @endif
                    <label class="flex-1"><span class="text-sm font-bold text-gray-300">Profile picture</span><input name="profile_picture" type="file" accept="image/png,image/jpeg,image/webp"
                            class="mt-2 w-full rounded-md border border-white/10 bg-gray-950 px-3 py-2 text-white file:mr-3 file:rounded-md file:border-0 file:bg-red-600 file:px-3 file:py-2 file:text-sm file:font-black file:text-white"></label>
                </div>
                @error('profile_picture')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                <label><span class="text-sm font-bold text-gray-300">Name</span><input name="name"
                        value="{{ old('name', $user->name) }}"
                        class="mt-2 w-full rounded-md border-white/10 bg-gray-950 text-white focus:border-red-400 focus:ring-red-400"></label>
                @error('name')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                <label><span class="text-sm font-bold text-gray-300">Email</span><input name="email" type="email"
                        value="{{ old('email', $user->email) }}"
                        class="mt-2 w-full rounded-md border-white/10 bg-gray-950 text-white focus:border-red-400 focus:ring-red-400"></label>
                @error('email')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                <label><span class="text-sm font-bold text-gray-300">Phone</span><input name="phone" type="tel"
                        value="{{ old('phone', $user->phone) }}"
                        class="mt-2 w-full rounded-md border-white/10 bg-gray-950 text-white focus:border-red-400 focus:ring-red-400"></label>
                @error('phone')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                <label><span class="text-sm font-bold text-gray-300">Address</span><textarea name="address" rows="3"
                        class="mt-2 w-full rounded-md border-white/10 bg-gray-950 text-white focus:border-red-400 focus:ring-red-400">{{ old('address', $user->address) }}</textarea></label>
                @error('address')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                <label><span class="text-sm font-bold text-gray-300">Date of birth</span><input name="date_of_birth"
                        type="date" value="{{ old('date_of_birth', optional($user->date_of_birth)->format('Y-m-d')) }}"
                        class="mt-2 w-full rounded-md border-white/10 bg-gray-950 text-white focus:border-red-400 focus:ring-red-400"></label>
                @error('date_of_birth')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                <div class="grid gap-4 border-t border-white/10 pt-4 sm:grid-cols-2">
                    <label><span class="text-sm font-bold text-gray-300">Current password</span><input name="current_password" type="password"
                            class="mt-2 w-full rounded-md border-white/10 bg-gray-950 text-white focus:border-red-400 focus:ring-red-400"></label>
                    <label><span class="text-sm font-bold text-gray-300">New password</span><input name="password" type="password"
                            class="mt-2 w-full rounded-md border-white/10 bg-gray-950 text-white focus:border-red-400 focus:ring-red-400"></label>
                    <label class="sm:col-span-2"><span class="text-sm font-bold text-gray-300">Confirm new password</span><input name="password_confirmation" type="password"
                            class="mt-2 w-full rounded-md border-white/10 bg-gray-950 text-white focus:border-red-400 focus:ring-red-400"></label>
                </div>
                @error('current_password')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                @error('password')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                <button class="rounded-full bg-red-600 px-5 py-3 text-sm font-black text-white hover:bg-red-500">Save
                    profile</button>
            </div>
        </form>
    </section>
@endsection
