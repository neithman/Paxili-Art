@extends('layouts.app')
@section('title', 'Create account')

@section('content')
<div class="container">
    <div class="card auth-card">
        <img src="{{ asset('images/logo.png') }}" alt="PaxiliArt" class="logo">
        <h1>Create your account</h1>
        <p class="text-center muted">One account to buy art <strong>and</strong> open your own store.</p>

        <form method="POST" action="{{ route('register') }}">
            @csrf
            <div class="form-group">
                <label for="name">Full name</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
                       class="@error('name') is-invalid @enderror">
                @error('name')<div class="error-text">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                       class="@error('email') is-invalid @enderror">
                @error('email')<div class="error-text">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="8"
                       class="@error('password') is-invalid @enderror">
                <div class="hint">At least 8 characters.</div>
                @error('password')<div class="error-text">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label for="password_confirmation">Confirm password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required>
            </div>
            <button type="submit" class="btn btn-block">Create account</button>
        </form>

        <p class="text-center mt-2 mb-0">Already registered? <a href="{{ route('login') }}">Log in</a></p>
    </div>
</div>
@endsection
