@extends('layouts.app')
@section('title', 'Orders & payments')
@section('subnav') @include('partials.admin-nav') @endsection

@section('content')
<div class="container page">
    <div class="page-head">
        <h1>Orders & payments</h1>
        <form method="GET" class="flex">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Order # or buyer" style="width:220px">
            <select name="status" style="width:auto">
                <option value="">All statuses</option>
                @foreach (['pending', 'paid', 'cancelled'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm" type="submit">Filter</button>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Order #</th><th>Buyer</th><th>Items</th><th>Total</th><th>Order status</th><th>Last payment</th><th>Date</th><th></th></tr></thead>
            <tbody>
            @forelse ($orders as $order)
                @php $payment = $order->payments->first(); @endphp
                <tr>
                    <td><strong>{{ $order->order_number }}</strong></td>
                    <td>{{ $order->user->name }}<div class="muted small">{{ $order->user->email }}</div></td>
                    <td>{{ $order->items_count }}</td>
                    <td>{{ money($order->total) }}</td>
                    <td>{!! status_badge($order->status) !!}</td>
                    <td>
                        @if ($payment)
                            {!! status_badge($payment->status) !!}
                            <div class="muted small">{{ $payment->reference }}</div>
                        @else
                            <span class="muted">No payment</span>
                        @endif
                    </td>
                    <td>{{ $order->created_at->format('M d, Y h:i A') }}</td>
                    <td class="text-right"><a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline">Details</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center muted">No orders found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $orders->links() }}
</div>
@endsection
