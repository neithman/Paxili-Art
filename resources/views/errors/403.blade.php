@extends('layouts.app')
@section('title', 'Not allowed')

@section('content')
<div class="container container-md page text-center">
    <h1 style="font-size:5rem;color:var(--orange);margin:0">403</h1>
    <h2>Not allowed</h2>
    <p class="muted">{{ $exception->getMessage() ?: "You do not have permission to view this page." }}</p>
    <a href="{{ route('home') }}" class="btn">Back to home</a>
</div>
@endsection
