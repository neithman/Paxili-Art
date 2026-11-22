@extends('layouts.app')
@section('title', 'Admin analytics')
@section('subnav') @include('partials.admin-nav') @endsection

@section('content')
<div class="container page">
    <div class="page-head">
        <div>
            <h1>Analytics</h1>
            <p class="muted mb-0">Live numbers from the database.</p>
        </div>
        @if ($stats['pending'])
            <a href="{{ route('admin.artworks.index') }}" class="btn">{{ $stats['pending'] }} artwork(s) waiting for review →</a>
        @endif
    </div>

    <div class="stats">
        <div class="stat accent"><div class="label">Total revenue</div><div class="value">{{ money($stats['revenue']) }}</div></div>
        <div class="stat dark"><div class="label">Items sold</div><div class="value">{{ $stats['sales'] }}</div></div>
        <div class="stat"><div class="label">Total users</div><div class="value">{{ $stats['users'] }}</div></div>
        <div class="stat"><div class="label">Buyers</div><div class="value">{{ $stats['buyers'] }}</div></div>
        <div class="stat"><div class="label">Sellers</div><div class="value">{{ $stats['sellers'] }}</div></div>
        <div class="stat"><div class="label">Artwork</div><div class="value">{{ $stats['artworks'] }}</div></div>
        <div class="stat"><div class="label">Orders</div><div class="value">{{ $stats['orders'] }}</div></div>
        <div class="stat"><div class="label">Paid orders</div><div class="value">{{ $stats['paid_orders'] }}</div></div>
    </div>

    @include('partials.chart', ['chart' => $chart, 'route' => 'admin.dashboard'])

    <div class="grid-2 mt-3">
        <div class="card" style="margin:0">
            <h3 class="card-title">Best-selling artwork</h3>
            <ul class="list">
                @forelse ($bestSellers as $i => $best)
                    <li>
                        <div class="cell-flex">
                            <span class="rank {{ $i === 0 ? 'first' : '' }}">{{ $i + 1 }}</span>
                            <div>
                                <strong>{{ $best->title }}</strong>
                                <div class="muted small">{{ money($best->revenue) }} revenue</div>
                            </div>
                        </div>
                        <strong>{{ $best->sold }} sold</strong>
                    </li>
                @empty
                    <li class="muted">No sales yet.</li>
                @endforelse
            </ul>
        </div>

        <div class="card" style="margin:0">
            <h3 class="card-title">Recent orders <a href="{{ route('admin.orders.index') }}" class="small">View all</a></h3>
            <ul class="list">
                @forelse ($recentOrders as $order)
                    <li>
                        <div>
                            <a href="{{ route('admin.orders.show', $order) }}"><strong>{{ $order->order_number }}</strong></a>
                            <div class="muted small">{{ $order->user->name }} · {{ $order->created_at->diffForHumans() }}</div>
                        </div>
                        <div class="text-right">
                            <strong>{{ money($order->total) }}</strong><br>{!! status_badge($order->status) !!}
                        </div>
                    </li>
                @empty
                    <li class="muted">No orders yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
