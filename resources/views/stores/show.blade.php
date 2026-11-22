@extends('layouts.app')
@section('title', $store->name)

@section('content')
<div class="store-banner" @if($store->bannerUrl()) style="background-image:url('{{ $store->bannerUrl() }}')" @endif></div>

<div class="container">
    <div class="store-header">
        @if ($store->logoUrl())
            <img src="{{ $store->logoUrl() }}" alt="{{ $store->name }}" class="avatar avatar-lg">
        @else
            <span class="avatar avatar-lg">{{ strtoupper(substr($store->name, 0, 1)) }}</span>
        @endif
        <div>
            <h1>{{ $store->name }}</h1>
            <p class="muted mb-0">by {{ $store->user->name }} · {{ $artworks->total() }} artwork(s) · since {{ $store->created_at->format('M Y') }}</p>
        </div>
        @if (auth()->id() === $store->user_id)
            <a href="{{ route('store.edit') }}" class="btn btn-outline btn-sm" style="margin-left:auto">Edit store</a>
        @endif
    </div>

    @if ($store->description)
        <p style="max-width:760px;white-space:pre-line">{{ $store->description }}</p>
    @endif

    <div class="page" style="padding-top:12px">
        @if ($artworks->isEmpty())
            <div class="empty"><h3>No artwork for sale yet</h3><p>Check back soon!</p></div>
        @else
            <div class="art-grid">
                @foreach ($artworks as $artwork)
                    @include('partials.art-card', ['artwork' => $artwork->setRelation('store', $store)])
                @endforeach
            </div>
            {{ $artworks->links() }}
        @endif
    </div>
</div>
@endsection
