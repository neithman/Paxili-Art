@extends('layouts.app')
@section('title', 'My artwork')
@section('subnav') @include('partials.seller-nav') @endsection

@section('content')
<div class="container page">
    <div class="page-head">
        <h1>My artwork</h1>
        <a href="{{ route('seller.artworks.create') }}" class="btn">+ Upload artwork</a>
    </div>

    <div class="flex mb-2">
        @foreach (['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
            <a href="{{ route('seller.artworks.index', $key ? ['status' => $key] : []) }}"
               class="chip {{ request('status', '') === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if ($artworks->isEmpty())
        <div class="empty">
            <h3>No artwork here yet</h3>
            <p>Upload your first piece – it will go live after an admin approves it.</p>
            <a href="{{ route('seller.artworks.create') }}" class="btn">Upload artwork</a>
        </div>
    @else
        <div class="table-wrap">
            <table>
                <thead><tr><th>Artwork</th><th>Category</th><th>Price</th><th>Status</th><th>Sold</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                @foreach ($artworks as $artwork)
                    <tr>
                        <td>
                            <div class="cell-flex">
                                <img src="{{ $artwork->previewUrl() }}" class="table-thumb" alt="">
                                <div>
                                    <strong>{{ $artwork->title }}</strong>
                                    <div class="muted small">Uploaded {{ $artwork->created_at->format('M d, Y') }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $artwork->category->name ?? '—' }}</td>
                        <td>{{ money($artwork->price) }}</td>
                        <td>
                            {!! status_badge($artwork->status) !!}
                            @if ($artwork->status === 'rejected' && $artwork->rejection_reason)
                                <div class="small" style="color:var(--danger);max-width:240px">Reason: {{ $artwork->rejection_reason }}</div>
                            @endif
                        </td>
                        <td>{{ $artwork->salesCount() }}</td>
                        <td class="text-right">
                            <div class="flex" style="justify-content:flex-end">
                                <a href="{{ route('artworks.show', $artwork->slug) }}" class="btn btn-outline btn-sm">View</a>
                                <a href="{{ route('seller.artworks.edit', $artwork) }}" class="btn btn-dark btn-sm">Edit</a>
                                <form method="POST" action="{{ route('seller.artworks.destroy', $artwork) }}" data-confirm="Delete &quot;{{ $artwork->title }}&quot;? This cannot be undone.">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $artworks->links() }}
    @endif
</div>
@endsection
