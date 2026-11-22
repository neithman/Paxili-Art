@extends('layouts.app')
@section('title', 'Forgot password')

@section('content')
<div class="container">
    <div class="card auth-card">
        <h1>Forgot password?</h1>
        <p class="text-center muted">Enter your email and we'll send you a link to choose a new password.</p>

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
                @error('email')<div class="error-text">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-block">Send reset link</button>
        </form>

        <p class="text-center mt-2 mb-0"><a href="{{ route('login') }}">← Back to log in</a></p>
    </div>
</div>
@endsection
