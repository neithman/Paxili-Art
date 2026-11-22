@extends('layouts.app')
@section('title', $artwork->title)

@section('content')
<div class="container page">
    @if ($artwork->status !== 'approved')
        <div class="notice">
            Preview only – this artwork is <strong>{{ $artwork->status }}</strong> and is not visible to the public.
            @if ($artwork->rejection_reason) Reason: {{ $artwork->rejection_reason }} @endif
        </div>
    @endif

    <div class="detail">
        {{-- LEFT: preview image --}}
        <div>
            <div class="detail-image">
                <img src="{{ $artwork->previewUrl() }}" alt="{{ $artwork->title }}">
                <div class="watermark">PaxiliArt preview</div>
            </div>
        </div>

        {{-- RIGHT: info + buy --}}
        <div>
            @if ($artwork->category)
                <a href="{{ route('marketplace', ['category' => $artwork->category->slug]) }}" class="badge">{{ $artwork->category->name }}</a>
            @endif
            <h1 class="mt-1">{{ $artwork->title }}</h1>

            @php $avg = $artwork->averageRating(); $reviewCount = $artwork->reviews->count(); @endphp
            <div class="flex small">
                @include('partials.stars', ['rating' => $avg])
                <span class="muted">{{ $reviewCount ? "$avg / 5 · $reviewCount review(s)" : 'No reviews yet' }}</span>
            </div>

            <div class="detail-price">{{ money($artwork->price) }}</div>

            {{-- Buy button – shows the right thing for each situation --}}
            @guest
                <a href="{{ route('login') }}" class="btn btn-block">Log in to buy</a>
            @else
                @if ($isOwner)
                    <a href="{{ route('seller.artworks.edit', $artwork) }}" class="btn btn-dark btn-block">This is your artwork – Edit</a>
                @elseif ($purchased)
                    <a href="{{ route('purchases.index') }}" class="btn btn-success btn-block">✔ You own this – go to My Purchases</a>
                @elseif ($inCart)
                    <a href="{{ route('cart.index') }}" class="btn btn-dark btn-block">In your cart – Checkout</a>
                @elseif ($artwork->status === 'approved')
                    <form method="POST" action="{{ route('cart.add', $artwork) }}">
                        @csrf
                        <button type="submit" class="btn btn-block">Add to cart</button>
                    </form>
                @endif
            @endguest

            <p class="muted small mt-1">Digital download · You get the original high-quality file after payment (1 download).</p>

            @if ($artwork->description)
                <h3 class="mt-3">About this artwork</h3>
                <p style="white-space:pre-line">{{ $artwork->description }}</p>
            @endif

            @if ($artwork->tagList())
                <div class="mt-2">
                    @foreach ($artwork->tagList() as $tag)
                        <a href="{{ route('marketplace', ['q' => $tag]) }}" class="tag">#{{ $tag }}</a>
                    @endforeach
                </div>
            @endif

            {{-- Seller info --}}
            <a href="{{ route('stores.show', $artwork->store) }}" class="seller-box" style="color:inherit">
                @if ($artwork->store->logoUrl())
                    <img src="{{ $artwork->store->logoUrl() }}" class="avatar" alt="">
                @else
                    <span class="avatar">{{ strtoupper(substr($artwork->store->name, 0, 1)) }}</span>
                @endif
                <div>
                    <div class="muted small">Sold by</div>
                    <strong>{{ $artwork->store->name }}</strong>
                    <div class="muted small">by {{ $artwork->store->user->name }} · Visit store →</div>
                </div>
            </a>
        </div>
    </div>

    {{-- REVIEWS --}}
    <div class="card mt-3">
        <h2 class="card-title">Reviews ({{ $reviewCount }})</h2>
        @forelse ($artwork->reviews as $review)
            <div class="review">
                <div class="flex between">
                    <strong>{{ $review->user->name }}</strong>
                    <span class="muted small">{{ $review->updated_at->diffForHumans() }}</span>
                </div>
                @include('partials.stars', ['rating' => $review->rating])
                <span class="badge badge-success" style="margin-left:6px">Verified buyer</span>
                @if ($review->comment)<p class="mt-1 mb-0">{{ $review->comment }}</p>@endif
            </div>
        @empty
            <p class="muted mb-0">No reviews yet. Only buyers who purchased this artwork can leave a review.</p>
        @endforelse
        @if ($purchased)
            <p class="mt-2 mb-0"><a href="{{ route('purchases.index') }}">Write or edit your review from My Purchases →</a></p>
        @endif
    </div>

    {{-- RELATED --}}
    @if ($related->isNotEmpty())
        <div class="section-head mt-3"><h2>You may also like</h2></div>
        <div class="art-grid">
            @foreach ($related as $item)
                @include('partials.art-card', ['artwork' => $item])
            @endforeach
        </div>
    @endif
</div>
@endsection
