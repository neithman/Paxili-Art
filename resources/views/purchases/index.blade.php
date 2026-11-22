@extends('layouts.app')
@section('title', 'My purchases')

@section('content')
<div class="container page">
    <div class="page-head">
        <div>
            <h1>My purchases & downloads</h1>
            <p class="muted mb-0">Each purchased artwork can be downloaded <strong>one time</strong>. Save the file somewhere safe!</p>
        </div>
        <a href="{{ route('orders.index') }}" class="btn btn-outline">My orders</a>
    </div>

    @if ($items->isEmpty())
        <div class="empty">
            <h3>No purchases yet</h3>
            <p>Artwork you buy will appear here, ready to download.</p>
            <a href="{{ route('marketplace') }}" class="btn">Browse artwork</a>
        </div>
    @else
        <div class="table-wrap">
            <table>
                <thead><tr><th>Artwork</th><th>Order</th><th>Price</th><th>Download</th><th>Review</th></tr></thead>
                <tbody>
                @foreach ($items as $item)
                    @php
                        $art = $item->artwork;
                        $available = $art && $art->status !== 'removed';
                        // Data for the review popup (filled in by public/js/app.js)
                        $reviewData = [
                            'action' => route('reviews.save', $item),
                            'title' => $item->title,
                            'rating' => $item->review->rating ?? null,
                            'comment' => $item->review->comment ?? '',
                        ];
                    @endphp
                    <tr>
                        <td>
                            <div class="cell-flex">
                                @if ($available)
                                    <img src="{{ $art->previewUrl() }}" class="table-thumb" alt="">
                                @endif
                                <div>
                                    @if ($available && ! $art->trashed())
                                        <a href="{{ route('artworks.show', $art->slug) }}"><strong>{{ $item->title }}</strong></a>
                                    @else
                                        <strong>{{ $item->title }}</strong>
                                    @endif
                                    <div class="muted small">{{ $art->store->name ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="{{ route('orders.show', $item->order) }}">{{ $item->order->order_number }}</a>
                            <div class="muted small">{{ $item->order->paid_at?->format('M d, Y') }}</div>
                        </td>
                        <td>{{ money($item->price) }}</td>
                        <td>
                            @if ($item->download)
                                <button class="btn btn-sm" disabled>✔ Downloaded</button>
                                <div class="muted small">{{ $item->download->downloaded_at->format('M d, Y h:i A') }}</div>
                            @elseif (! $available)
                                <span class="badge badge-removed">No longer available</span>
                            @else
                                <form method="POST" action="{{ route('purchases.download', $item) }}"
                                      data-download="{{ $item->id }}"
                                      data-reviewed="{{ $item->review ? 1 : 0 }}"
                                      data-review-data='@json($reviewData)'>
                                    @csrf
                                    <button type="submit" class="btn btn-sm">⬇ Download</button>
                                </form>
                                <div class="muted small" data-download-status="{{ $item->id }}">1 download left</div>
                            @endif
                        </td>
                        <td>
                            @if ($available)
                                @if ($item->review)
                                    @include('partials.stars', ['rating' => $item->review->rating])
                                    <div><button type="button" class="link-btn small" data-review='@json($reviewData)'>Edit review</button></div>
                                @else
                                    <button type="button" class="link-btn" data-review='@json($reviewData)'>☆ Rate it</button>
                                @endif
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    @endif
</div>
@endsection

{{-- REVIEW POPUP (one modal, reused for every row) --}}
@push('modals')
<div class="modal" id="reviewModal">
    <div class="modal-box">
        <h2 class="text-center" data-review-heading>Enjoying your new artwork?</h2>
        <p class="text-center muted">Rate <strong data-review-title></strong>. It's optional, but it helps the artist!</p>

        <form method="POST" action="">
            @csrf
            <div class="star-input">
                {{-- Reversed order so the CSS "~" selector can highlight stars to the left --}}
                @for ($i = 5; $i >= 1; $i--)
                    <input type="radio" id="star{{ $i }}" name="rating" value="{{ $i }}" required>
                    <label for="star{{ $i }}" title="{{ $i }} star(s)">★</label>
                @endfor
            </div>
            <div class="form-group">
                <label for="comment">Your review (optional)</label>
                <textarea id="comment" name="comment" maxlength="1000" placeholder="What did you like about it?"></textarea>
            </div>
            <div class="flex between">
                <button type="button" class="btn btn-outline" data-modal-close>Skip</button>
                <button type="submit" class="btn">Submit review</button>
            </div>
        </form>
    </div>
</div>
@endpush
