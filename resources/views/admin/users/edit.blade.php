@extends('layouts.app')
@section('title', 'Edit ' . $user->name)
@section('subnav') @include('partials.admin-nav') @endsection

@section('content')
<div class="container page">
    <div class="page-head">
        <div>
            <h1>{{ $user->name }}</h1>
            <p class="muted mb-0">Member since {{ $user->created_at->format('M d, Y') }} ·
                {{ $user->orders_count }} order(s) · {{ $user->downloads_count }} download(s) · {{ $user->reviews_count }} review(s)
                @if ($user->store) · Store: <a href="{{ route('stores.show', $user->store) }}">{{ $user->store->name }}</a>@endif
            </p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline">← All users</a>
    </div>

    <div class="grid-2">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="card" style="margin:0">
            @csrf @method('PUT')
            <h3 class="card-title">Account information</h3>
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                @error('name')<div class="error-text">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                @error('email')<div class="error-text">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <input type="hidden" name="is_admin" value="0">
                <label class="checkbox"><input type="checkbox" name="is_admin" value="1" @checked(old('is_admin', $user->is_admin))> Administrator</label>
            </div>
            <button type="submit" class="btn">Save</button>
        </form>

        <form method="POST" action="{{ route('admin.users.password', $user) }}" class="card" style="margin:0">
            @csrf @method('PUT')
            <h3 class="card-title">Reset password</h3>
            <p class="muted small">Set a new password for this user. They will get a notification.</p>
            <div class="form-group">
                <label for="password">New password</label>
                <input type="password" id="password" name="password" required minlength="8">
                @error('password')<div class="error-text">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label for="password_confirmation">Confirm new password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required>
            </div>
            <button type="submit" class="btn btn-dark">Reset password</button>
        </form>
    </div>
</div>
@endsection
