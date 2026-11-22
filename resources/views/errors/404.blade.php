@extends('layouts.app')
@section('title', 'Page not found')

@section('content')
<div class="container container-md page text-center">
    <h1 style="font-size:5rem;color:var(--orange);margin:0">404</h1>
    <h2>Page not found</h2>
    <p class="muted">We couldn't find what you were looking for.</p>
    <a href="{{ route('home') }}" class="btn">Back to home</a>
</div>
@endsection
