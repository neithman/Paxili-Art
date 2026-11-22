@extends('layouts.app')
@section('title', 'Page expired')

@section('content')
<div class="container container-md page text-center">
    <h1 style="font-size:5rem;color:var(--orange);margin:0">419</h1>
    <h2>Page expired</h2>
    <p class="muted">Your session expired. Please go back, refresh the page and try again.</p>
    <a href="{{ route('home') }}" class="btn">Back to home</a>
</div>
@endsection
