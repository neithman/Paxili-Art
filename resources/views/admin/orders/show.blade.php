@extends('layouts.app')
@section('title', 'Order ' . $order->order_number)
@section('subnav') @include('partials.admin-nav') @endsection

@section('content')
<div class="container page">
    <div class="page-head">
        <div>
            <h1>Order {{ $order->order_number }}</h1>
            <p class="muted mb-0">{!! status_badge($order->status) !!} · Buyer:
                <a href="{{ route('admin.users.edit', $order->user) }}">{{ $order->user->name }}</a> ({{ $order->user->email }})
                · Created {{ $order->created_at->format('M d, Y h:i A') }}
                @if ($order->paid_at) · Paid {{ $order->paid_at->format('M d, Y h:i A') }} @endif
            </p>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline">← All orders</a>
    </div>

    <h3>Items, sellers & downloads</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Artwork</th><th>Seller</th><th>Price</th><th>Downloaded?</th><th>Download date & time</th></tr></thead>
            <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td><strong>{{ $item->title }}</strong></td>
                    <td>{{ $item->store->name ?? 'Deleted store' }}<div class="muted small">{{ $item->store->user->name ?? '' }}</div></td>
                    <td>{{ money($item->price) }}</td>
                    <td>{!! $item->download ? status_badge('downloaded') : '<span class="badge">No</span>' !!}</td>
                    <td>
                        @if ($item->download)
                            {{ $item->download->downloaded_at->format('M d, Y h:i:s A') }}
                            <div class="muted small">IP {{ $item->download->ip_address }}</div>
                        @else — @endif
                    </td>
                </tr>
            @endforeach
                <tr><td colspan="2"><strong>Total</strong></td><td colspan="3"><strong>{{ money($order->total) }}</strong></td></tr>
            </tbody>
        </table>
    </div>

    <h3 class="mt-3">Payment transactions</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Reference</th><th>Method</th><th>GCash account</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            @forelse ($order->payments as $payment)
                <tr>
                    <td>{{ $payment->reference }}</td>
                    <td>{{ strtoupper($payment->method) }} <span class="badge">{{ strtoupper($payment->mode) }}</span>@if($payment->provider_reference)<div class="muted small">{{ $payment->provider_reference }}</div>@endif</td>
                    <td>{{ $payment->account_number ?? "—" }}</td>
                    <td>{{ money($payment->amount) }}</td>
                    <td>{!! status_badge($payment->status) !!}</td>
                    <td>{{ $payment->created_at->format('M d, Y h:i A') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center muted">No payment attempts yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
