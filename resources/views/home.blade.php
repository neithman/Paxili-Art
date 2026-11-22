@extends('layouts.app')
@section('title', 'Original digital art from independent artists')

@section('content')
{{-- HERO --}}
<section class="hero">
    <div class="container">
        <div>
            <h1>Original <em>digital art</em>, straight from the artist.</h1>
            <p>Discover illustrations, paintings and designs from independent creators. Buy securely, download instantly – or open your own store in minutes.</p>
            <div class="flex mt-3">
                <a href="{{ route('marketplace') }}" class="btn">Browse the marketplace</a>
                @auth
                    <a href="{{ auth()->user()->store ? route('seller.dashboard') : route('store.create') }}" class="btn btn-light">
                        {{ auth()->user()->store ? 'Go to my store' : 'Start selling' }}
                    </a>
                @else
                    <a href="{{ route('register') }}" class="btn btn-light">Start selling</a>
                @endauth
            </div>
        </div>
        <div class="hero-art">
            @foreach ($latest->take(4) as $art)
                <img src="{{ $art->previewUrl() }}" alt="{{ $art->title }}">
            @endforeach
        </div>
    </div>
</section>

{{-- CATEGORIES --}}
@if ($categories->isNotEmpty())
<section class="section">
    <div class="container">
        <div class="section-head"><h2>Browse by category</h2></div>
        <div class="category-chips">
            @foreach ($categories as $category)
                <a href="{{ route('marketplace', ['category' => $category->slug]) }}" class="chip">
                    {{ $category->name }} <small>{{ $category->artworks_count }}</small>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- LATEST ARTWORK --}}
<section class="section" style="padding-top:0">
    <div class="container">
        <div class="section-head">
            <h2>Fresh in the marketplace</h2>
            <a href="{{ route('marketplace') }}">See all →</a>
        </div>
        @if ($latest->isEmpty())
            <div class="empty">
                <h3>No artwork yet</h3>
                <p>Be the first artist to open a store and upload your work!</p>
            </div>
        @else
            <div class="art-grid">
                @foreach ($latest as $artwork)
                    @include('partials.art-card', ['artwork' => $artwork])
                @endforeach
            </div>
        @endif
    </div>
</section>

{{-- FEATURED STORES --}}
@if ($stores->isNotEmpty())
<section class="section" style="padding-top:0">
    <div class="container">
        <div class="section-head"><h2>Featured artists</h2></div>
        <div class="art-grid">
            @foreach ($stores as $store)
                <a href="{{ route('stores.show', $store) }}" class="store-card">
                    @if ($store->logoUrl())
                        <img src="{{ $store->logoUrl() }}" alt="" class="avatar">
                    @else
                        <span class="avatar">{{ strtoupper(substr($store->name, 0, 1)) }}</span>
                    @endif
                    <div>
                        <strong>{{ $store->name }}</strong>
                        <div class="muted small">{{ $store->artworks_count }} artwork(s)</div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- HOW IT WORKS --}}
<section class="section" style="padding-top:0">
    <div class="container">
        <div class="section-head"><h2>How it works</h2></div>
        <div class="steps">
            <div class="step"><b>1</b><h3>Create one account</h3><p class="mb-0 muted">The same account lets you buy artwork and run your own store.</p></div>
            <div class="step"><b>2</b><h3>Buy or sell</h3><p class="mb-0 muted">Every artwork is reviewed by our team before it goes live in the marketplace.</p></div>
            <div class="step"><b>3</b><h3>Secure download</h3><p class="mb-0 muted">After payment, download your original file once – safely and privately.</p></div>
        </div>
    </div>
</section>
@endsection
