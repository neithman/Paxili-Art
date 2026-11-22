@extends('layouts.app')
@section('title', 'Review: ' . $artwork->title)
@section('subnav') @include('partials.admin-nav') @endsection

@section('content')
<div class="container page">
    <div class="page-head">
        <div>
            <h1>{{ $artwork->title }}</h1>
            <p class="muted mb-0">{!! status_badge($artwork->status) !!} · submitted by
                <a href="{{ route('admin.users.edit', $artwork->store->user) }}">{{ $artwork->store->user->name }}</a>
                ({{ $artwork->store->name }}) · {{ $artwork->updated_at->format('M d, Y h:i A') }}</p>
        </div>
        <a href="{{ route('admin.artworks.index') }}" class="btn btn-outline">← Back to list</a>
    </div>

    <div class="detail">
        <div>
            <div class="detail-image"><img src="{{ $artwork->previewUrl() }}" alt=""></div>
            <p class="mt-1">
                <a href="{{ route('admin.artworks.file', $artwork) }}" target="_blank" class="btn btn-outline btn-sm">🔒 Open original file ({{ $artwork->file_name }})</a>
            </p>
        </div>

        <div>
            <div class="card">
                <h3 class="card-title">Details</h3>
                <p><strong>Price:</strong> {{ money($artwork->price) }}</p>
                <p><strong>Category:</strong> {{ $artwork->category->name ?? '—' }}</p>
                <p><strong>Tags:</strong> {{ $artwork->tags ?: '—' }}</p>
                <p class="mb-0" style="white-space:pre-line"><strong>Description:</strong> {{ $artwork->description ?: '—' }}</p>
            </div>

            <div class="card">
                <h3 class="card-title">Moderation</h3>

                @if ($artwork->status !== 'approved')
                    <form method="POST" action="{{ route('admin.artworks.approve', $artwork) }}" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-success btn-block">✔ Approve & publish</button>
                    </form>
                @endif

                @if ($artwork->status !== 'rejected')
                    <form method="POST" action="{{ route('admin.artworks.reject', $artwork) }}" class="mb-2">
                        @csrf
                        <div class="form-group">
                            <label for="reject_reason">Reject – reason (sent to the seller)</label>
                            <textarea id="reject_reason" name="reason" required style="min-height:70px" placeholder="e.g. Preview image is too low quality.">{{ old('reason') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-dark btn-block">Reject</button>
                    </form>
                @endif

                <hr>
                <form method="POST" action="{{ route('admin.artworks.destroy', $artwork) }}" data-confirm="Permanently remove this artwork and its files?">
                    @csrf @method('DELETE')
                    <div class="form-group">
                        <label for="delete_reason">Delete prohibited content – reason</label>
                        <textarea id="delete_reason" name="reason" required style="min-height:70px" placeholder="e.g. Copyright violation."></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger btn-block">Delete artwork</button>
                </form>
            </div>

            <div class="card">
                <h3 class="card-title">Moderation history</h3>
                <ul class="list">
                    @forelse ($artwork->moderationLogs as $log)
                        <li>
                            <div>
                                {!! status_badge($log->action) !!} by {{ $log->admin->name ?? 'deleted admin' }}
                                @if ($log->reason)<div class="small">{{ $log->reason }}</div>@endif
                            </div>
                            <span class="muted small">{{ $log->created_at->format('M d, Y h:i A') }}</span>
                        </li>
                    @empty
                        <li class="muted">No actions yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
