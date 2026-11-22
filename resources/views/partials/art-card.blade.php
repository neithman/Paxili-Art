{{-- One artwork card. Usage: @include('partials.art-card', ['artwork' => $artwork]) --}}
<a href="{{ route('artworks.show', $artwork->slug) }}" class="art-card">
    <div class="thumb">
        <img src="{{ $artwork->previewUrl() }}" alt="{{ $artwork->title }}" loading="lazy">
    </div>
    <div class="body">
        <span class="title">{{ $artwork->title }}</span>
        <span class="artist">by {{ $artwork->store->name ?? 'Unknown' }}</span>
        <span class="price">{{ money($artwork->price) }}</span>
    </div>
</a>
