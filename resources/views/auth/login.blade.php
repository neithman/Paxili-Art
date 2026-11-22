@extends('layouts.app')
@section('title', 'Log in')

@section('content')
<div class="container">
    <div class="card auth-card">
        <img src="{{ asset('images/logo.png') }}" alt="PaxiliArt" class="logo">
        <h1>Welcome back</h1>
        <p class="text-center muted">Log in to buy, sell and download your art.</p>

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                       class="@error('email') is-invalid @enderror">
                @error('email')<div class="error-text">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group flex between">
                <label class="checkbox"><input type="checkbox" name="remember"> Remember me</label>
                <a href="{{ route('password.request') }}" class="small">Forgot password?</a>
            </div>
            <button type="submit" class="btn btn-block">Log in</button>
        </form>

        <p class="text-center mt-2 mb-0">New here? <a href="{{ route('register') }}">Create an account</a></p>
    </div>
</div>
@endsection
