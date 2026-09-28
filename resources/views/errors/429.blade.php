@extends('errors.layout')

@section('code', '429')
@section('title', 'Easy there, speed reader.')
@section('message')
    We received a lot of requests from your connection in a short time, so we paused things to keep the box office fast for everyone.
    @isset($retryAfter) Please try again in about {{ max(1, (int) ceil($retryAfter / 60)) }} {{ \Illuminate\Support\Str::plural('minute', max(1, (int) ceil($retryAfter / 60))) }}. @else Please wait a minute and try again. @endisset
@endsection
