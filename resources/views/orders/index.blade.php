@extends('layouts.app')
@section('title', 'My orders')

@section('content')
<div class="container page">
    <div class="page-head">
        <h1>My orders</h1>
        <a href="{{ route('purchases.index') }}" class="btn btn-outline">My downloads →</a>
    </div>

    @if ($orders->isEmpty())
        <div class="empty"><h3>No orders yet</h3><a href="{{ route('marketplace') }}" class="btn mt-1">Start shopping</a></div>
    @else
        <div class="table-wrap">
            <table>
                <thead><tr><th>Order #</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @foreach ($orders as $order)
                    <tr>
                        <td><strong>{{ $order->order_number }}</strong></td>
                        <td>{{ $order->created_at->format('M d, Y') }}</td>
                        <td>{{ $order->items_count }}</td>
                        <td>{{ money($order->total) }}</td>
                        <td>{!! status_badge($order->status) !!}</td>
                        <td class="text-right">
                            <a href="{{ route('orders.show', $order) }}" class="btn btn-sm {{ $order->status === 'pending' ? '' : 'btn-outline' }}">
                                {{ $order->status === 'pending' ? 'Pay now' : 'View' }}
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $orders->links() }}
    @endif
</div>
@endsection
