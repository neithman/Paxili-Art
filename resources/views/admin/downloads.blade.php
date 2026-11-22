@extends('layouts.app')
@section('title', 'Download tracking')
@section('subnav') @include('partials.admin-nav') @endsection

@section('content')
<div class="container page">
    <h1>Download tracking</h1>
    <p class="muted">Every successful one-time download, newest first.</p>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Date & time</th><th>Artwork</th><th>Buyer</th><th>Seller</th><th>Order</th><th>IP address</th></tr></thead>
            <tbody>
            @forelse ($downloads as $download)
                <tr>
                    <td>{{ $download->downloaded_at->format('M d, Y h:i:s A') }}</td>
                    <td><strong>{{ $download->orderItem->title }}</strong></td>
                    <td>{{ $download->user->name }}</td>
                    <td>{{ $download->orderItem->store->name ?? '—' }}</td>
                    <td><a href="{{ route('admin.orders.show', $download->orderItem->order) }}">{{ $download->orderItem->order->order_number }}</a></td>
                    <td class="muted">{{ $download->ip_address }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center muted">No downloads yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $downloads->links() }}
</div>
@endsection
