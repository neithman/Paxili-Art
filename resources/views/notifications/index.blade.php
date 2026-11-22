@extends('layouts.app')
@section('title', 'Notifications')

@section('content')
<div class="container container-md page">
    <div class="page-head">
        <h1>Notifications</h1>
        <form method="POST" action="{{ route('notifications.readAll') }}">
            @csrf
            <button type="submit" class="btn btn-outline btn-sm">Mark all as read</button>
        </form>
    </div>

    <div class="flex mb-2">
        <a href="{{ route('notifications.index') }}" class="chip {{ $filter !== 'unread' ? 'active' : '' }}">All</a>
        <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" class="chip {{ $filter === 'unread' ? 'active' : '' }}">
            Unread <small>{{ auth()->user()->unreadNotificationsCount() }}</small>
        </a>
    </div>

    <div class="card" style="padding:0;overflow:hidden">
        @forelse ($notifications as $notification)
            <div class="notif {{ $notification->isRead() ? '' : 'unread' }}">
                <span class="dot"></span>
                <div class="content">
                    {{-- Clicking marks it as read, then opens the link --}}
                    <form method="POST" action="{{ route('notifications.read', $notification) }}">
                        @csrf
                        <button type="submit" class="notif-btn">
                            <strong>{{ $notification->title }}</strong>
                            <span>{{ $notification->message }}</span>
                        </button>
                    </form>
                    <div class="muted small">{{ $notification->created_at->diffForHumans() }}</div>
                </div>
            </div>
        @empty
            <div class="empty" style="border:0">
                <h3>You're all caught up</h3>
                <p class="mb-0">New notifications will appear here.</p>
            </div>
        @endforelse
    </div>
    {{ $notifications->links() }}
</div>
@endsection
