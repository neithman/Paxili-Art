@extends('layouts.app')
@section('title', 'Seller dashboard')
@section('subnav') @include('partials.seller-nav') @endsection

@section('content')
<div class="container page">
    <div class="page-head">
        <div>
            <h1>{{ $store->name }}</h1>
            <p class="muted mb-0">Your seller dashboard</p>
        </div>
        <a href="{{ route('seller.artworks.create') }}" class="btn">+ Upload artwork</a>
    </div>

    <div class="stats">
        <div class="stat accent"><div class="label">Total earnings</div><div class="value">{{ money($stats['earnings']) }}</div></div>
        <div class="stat dark"><div class="label">Items sold</div><div class="value">{{ $stats['sales'] }}</div></div>
        <div class="stat"><div class="label">Total products</div><div class="value">{{ $stats['total'] }}</div></div>
        <div class="stat"><div class="label">Approved</div><div class="value">{{ $stats['approved'] }}</div></div>
        <div class="stat"><div class="label">Pending</div><div class="value">{{ $stats['pending'] }}</div></div>
        <div class="stat"><div class="label">Rejected</div><div class="value">{{ $stats['rejected'] }}</div></div>
    </div>

    @include('partials.chart', ['chart' => $chart, 'route' => 'seller.dashboard'])

    <div class="grid-2 mt-3">
        <div class="card" style="margin:0">
            <h3 class="card-title">Recent orders <a href="{{ route('seller.sales') }}" class="small">View all</a></h3>
            <ul class="list">
                @forelse ($recentOrders as $item)
                    <li>
                        <div>
                            <strong>{{ $item->title }}</strong>
                            <div class="muted small">{{ $item->order->order_number }} · {{ $item->order->user->name }} · {{ $item->order->paid_at?->format('M d, Y') }}</div>
                        </div>
                        <strong>{{ money($item->price) }}</strong>
                    </li>
                @empty
                    <li class="muted">No sales yet.</li>
                @endforelse
            </ul>
        </div>

        <div class="card" style="margin:0">
            <h3 class="card-title">Download notifications</h3>
            <ul class="list">
                @forelse ($recentDownloads as $download)
                    <li>
                        <div>
                            <strong>{{ $download->orderItem->title }}</strong>
                            <div class="muted small">Downloaded by {{ $download->user->name }} · order {{ $download->orderItem->order->order_number }}</div>
                        </div>
                        <span class="small muted text-right">{{ $download->downloaded_at->format('M d, Y') }}<br>{{ $download->downloaded_at->format('h:i A') }}</span>
                    </li>
                @empty
                    <li class="muted">No downloads yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
