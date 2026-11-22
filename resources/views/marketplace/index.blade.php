@extends('layouts.app')
@section('title', 'Marketplace')

@section('content')
<div class="container page">
    <div class="page-head">
        <div>
            <h1>Marketplace</h1>
            <p class="muted mb-0">{{ $artworks->total() }} artwork(s) found</p>
        </div>
    </div>

    <div class="grid-sidebar">
        {{-- FILTERS (a normal GET form, so filters appear in the URL) --}}
        <form method="GET" action="{{ route('marketplace') }}" class="card filters">
            <h3>Filter</h3>
            <div class="form-group">
                <label for="q">Title or tag</label>
                <input type="search" id="q" name="q" value="{{ request('q') }}" placeholder="e.g. sunset">
            </div>
            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="artist">Artist / store</label>
                <input type="text" id="artist" name="artist" value="{{ request('artist') }}" placeholder="Artist name">
            </div>
            <div class="form-group">
                <label>Price range</label>
                <div class="flex" style="flex-wrap:nowrap">
                    <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="Min" min="0">
                    <span>–</span>
                    <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Max" min="0">
                </div>
            </div>
            <div class="form-group">
                <label for="sort">Sort by</label>
                <select id="sort" name="sort">
                    <option value="newest" @selected(request('sort') === 'newest')>Newest</option>
                    <option value="oldest" @selected(request('sort') === 'oldest')>Oldest</option>
                    <option value="price_low" @selected(request('sort') === 'price_low')>Price: low to high</option>
                    <option value="price_high" @selected(request('sort') === 'price_high')>Price: high to low</option>
                </select>
            </div>
            <button type="submit" class="btn btn-block">Apply filters</button>
            <a href="{{ route('marketplace') }}" class="btn btn-outline btn-block mt-1">Clear</a>
        </form>

        {{-- RESULTS --}}
        <div>
            @if ($artworks->isEmpty())
                <div class="empty">
                    <h3>Nothing matches your search</h3>
                    <p>Try other keywords or clear the filters.</p>
                </div>
            @else
                <div class="art-grid">
                    @foreach ($artworks as $artwork)
                        @include('partials.art-card', ['artwork' => $artwork])
                    @endforeach
                </div>
                {{ $artworks->links() }}
            @endif
        </div>
    </div>
</div>
@endsection
