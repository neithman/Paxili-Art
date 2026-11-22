@extends('layouts.app')
@section('title', 'Order ' . $order->order_number)

@section('content')
<div class="container page">
    <div class="page-head">
        <div>
            <h1>Order {{ $order->order_number }}</h1>
            <p class="muted mb-0">Placed {{ $order->created_at->format('M d, Y h:i A') }} · {!! status_badge($order->status) !!}</p>
        </div>
        <a href="{{ route('orders.index') }}" class="btn btn-outline">← All orders</a>
    </div>

    <div class="grid-2">
        {{-- ITEMS --}}
        <div class="card">
            <h3 class="card-title">Items</h3>
            <ul class="list">
                @foreach ($order->items as $item)
                    <li>
                        <div class="cell-flex">
                            @if ($item->artwork && $item->artwork->status !== 'removed')
                                <img src="{{ $item->artwork->previewUrl() }}" class="table-thumb" alt="">
                            @endif
                            <div>
                                <strong>{{ $item->title }}</strong>
                                <div class="muted small">{{ $item->artwork->store->name ?? '' }}</div>
                                @if ($order->isPaid())
                                    <div class="small">{!! $item->download ? status_badge('downloaded') : '<span class="badge">Not downloaded</span>' !!}</div>
                                @endif
                            </div>
                        </div>
                        <strong>{{ money($item->price) }}</strong>
                    </li>
                @endforeach
                <li><span>Total</span><strong style="font-size:1.3rem">{{ money($order->total) }}</strong></li>
            </ul>
        </div>

        {{-- PAYMENT --}}
        <div>
            @if ($order->status === 'pending')
                @php $waiting = $order->payments->first(fn ($p) => $p->status === 'pending' && $p->mode === 'live'); @endphp
                <div class="card">
                    <h3 class="card-title">Pay with GCash</h3>

                    @if ($live)
                        <p>You'll be taken to <strong>GCash</strong> to log in and approve the payment of
                            <strong>{{ money($order->total) }}</strong>. After paying, you'll come back here automatically.</p>
                    @else
                        <div class="test-mode">
                            <strong>🧪 GCASH TEST MODE</strong> – no real money is charged.<br>
                            You'll see a GCash <em>simulator</em> where you can approve or decline the payment.
                        </div>
                    @endif

                    {{-- Paid in GCash but the page was closed before coming back? Check again. --}}
                    @if ($waiting)
                        <div class="notice">
                            A GCash payment ({{ $waiting->reference }}) was started but not confirmed yet.
                            <a href="{{ route('payments.gcash.return', $waiting) }}" class="btn btn-sm btn-dark mt-1">Check payment status</a>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('orders.pay', $order) }}">
                        @csrf
                        <button type="submit" class="btn btn-gcash btn-block">Pay {{ money($order->total) }} with GCash</button>
                    </form>
                    @if ($live)
                        <p class="muted small text-center mt-1 mb-0">🔒 Payments are processed securely by PayMongo.</p>
                    @endif

                    <form method="POST" action="{{ route('orders.cancel', $order) }}" data-confirm="Cancel this order?" class="text-center mt-2">
                        @csrf
                        <button type="submit" class="link-btn small">Cancel order</button>
                    </form>
                </div>
            @elseif ($order->isPaid())
                <div class="card">
                    <h3 class="card-title">Payment confirmed ✔</h3>
                    <p>Paid on {{ $order->paid_at->format('M d, Y h:i A') }}.</p>
                    <a href="{{ route('purchases.index') }}" class="btn btn-block">Go to my downloads</a>
                </div>
            @else
                <div class="card"><p class="mb-0">This order was cancelled.</p></div>
            @endif

            @if ($order->payments->isNotEmpty())
                <div class="card">
                    <h3 class="card-title">Payment attempts</h3>
                    <ul class="list">
                        @foreach ($order->payments as $payment)
                            <li>
                                <div>
                                    <strong>{{ $payment->reference }}</strong>
                                    <div class="muted small">{{ $payment->label() }} · {{ $payment->created_at->format('M d, Y h:i A') }}</div>
                                </div>
                                {!! status_badge($payment->status) !!}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
