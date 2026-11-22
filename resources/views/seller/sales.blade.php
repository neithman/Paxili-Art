@extends('layouts.app')
@section('title', 'Sales & downloads')
@section('subnav') @include('partials.seller-nav') @endsection

@section('content')
<div class="container page">
    <h1>Sales & downloads</h1>
    <p class="muted">Every paid purchase of your artwork and whether the buyer has downloaded it.</p>

    @if ($items->isEmpty())
        <div class="empty"><h3>No sales yet</h3><p class="mb-0">When someone buys your artwork it will show up here.</p></div>
    @else
        <div class="table-wrap">
            <table>
                <thead><tr><th>Artwork</th><th>Order</th><th>Buyer</th><th>Paid on</th><th>Price</th><th>Download</th></tr></thead>
                <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td><strong>{{ $item->title }}</strong></td>
                        <td>{{ $item->order->order_number }}</td>
                        <td>{{ $item->order->user->name }}</td>
                        <td>{{ $item->order->paid_at?->format('M d, Y h:i A') }}</td>
                        <td>{{ money($item->price) }}</td>
                        <td>
                            @if ($item->download)
                                {!! status_badge('downloaded') !!}
                                <div class="muted small">{{ $item->download->downloaded_at->format('M d, Y h:i A') }}</div>
                            @else
                                <span class="badge">Not yet</span>
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
