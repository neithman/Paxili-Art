@extends('layouts.app')
@section('title', 'Your cart')

@section('content')
<div class="container container-md page">
    <h1>Your cart</h1>

    @if ($cart->items->isEmpty())
        <div class="empty">
            <h3>Your cart is empty</h3>
            <p>Find something you love in the marketplace.</p>
            <a href="{{ route('marketplace') }}" class="btn">Browse artwork</a>
        </div>
    @else
        <div class="table-wrap">
            <table>
                <thead><tr><th>Artwork</th><th>Seller</th><th class="text-right">Price</th><th></th></tr></thead>
                <tbody>
                @foreach ($cart->items as $item)
                    <tr>
                        <td>
                            <a href="{{ route('artworks.show', $item->artwork->slug) }}" class="cell-flex" style="color:inherit">
                                <img src="{{ $item->artwork->previewUrl() }}" class="table-thumb" alt="">
                                <strong>{{ $item->artwork->title }}</strong>
                            </a>
                        </td>
                        <td>{{ $item->artwork->store->name }}</td>
                        <td class="text-right">{{ money($item->artwork->price) }}</td>
                        <td class="text-right">
                            <form method="POST" action="{{ route('cart.remove', $item) }}">
                                @csrf @method('DELETE')
                                <button class="link-btn" type="submit">Remove</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="card mt-3">
            <div class="flex between">
                <span>Total ({{ $cart->items->count() }} item(s))</span>
                <strong style="font-size:1.5rem">{{ money($cart->total()) }}</strong>
            </div>
            <form method="POST" action="{{ route('checkout') }}" class="mt-2">
                @csrf
                <button type="submit" class="btn btn-block">Place order & continue to payment</button>
            </form>
            <p class="muted small text-center mt-1 mb-0">Each artwork can be downloaded once after a successful payment.</p>
        </div>
    @endif
</div>
@endsection
