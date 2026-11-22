@extends('layouts.app')
@section('title', 'Artwork moderation')
@section('subnav') @include('partials.admin-nav') @endsection

@section('content')
<div class="container page">
    <div class="page-head">
        <h1>Artwork moderation</h1>
        <form method="GET" class="flex">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search title" style="width:220px">
            <button class="btn btn-sm" type="submit">Search</button>
        </form>
    </div>

    <div class="flex mb-2">
        @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $key => $label)
            <a href="{{ route('admin.artworks.index', ['status' => $key]) }}" class="chip {{ $status === $key ? 'active' : '' }}">
                {{ $label }} @if ($key !== 'all')<small>{{ $counts[$key] ?? 0 }}</small>@endif
            </a>
        @endforeach
    </div>

    @if ($artworks->isEmpty())
        <div class="empty"><h3>Nothing here</h3><p class="mb-0">{{ $status === 'pending' ? 'No artwork is waiting for review. 🎉' : 'No artwork found.' }}</p></div>
    @else
        <div class="table-wrap">
            <table>
                <thead><tr><th>Artwork</th><th>Store</th><th>Category</th><th>Price</th><th>Submitted</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @foreach ($artworks as $artwork)
                    <tr>
                        <td>
                            <div class="cell-flex">
                                <img src="{{ $artwork->previewUrl() }}" class="table-thumb" alt="">
                                <strong>{{ $artwork->title }}</strong>
                            </div>
                        </td>
                        <td>{{ $artwork->store->name }}</td>
                        <td>{{ $artwork->category->name ?? '—' }}</td>
                        <td>{{ money($artwork->price) }}</td>
                        <td>{{ $artwork->updated_at->diffForHumans() }}</td>
                        <td>{!! status_badge($artwork->status) !!}</td>
                        <td class="text-right"><a href="{{ route('admin.artworks.show', $artwork) }}" class="btn btn-sm">Review</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $artworks->links() }}
    @endif
</div>
@endsection
