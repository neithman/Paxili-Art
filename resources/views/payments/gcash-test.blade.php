{{--
    GCash TEST SIMULATOR – only used when PAYMONGO_SECRET_KEY is empty in .env.
    It lets you test the full purchase flow without real money.
    With a PayMongo key, buyers go to the REAL GCash page instead.
--}}
@extends('layouts.app')
@section('title', 'GCash test payment')

@section('content')
<div class="container page">
    <div class="gcash-sim">
        <div class="gcash-sim-head">
            <span class="gcash-mark">G</span>
            <div>
                <strong>GCash payment</strong>
                <div class="small">Test simulator – not the real GCash app</div>
            </div>
        </div>

        <div class="gcash-sim-body">
            <div class="test-mode">
                <strong>🧪 TEST MODE</strong> – no money will move. Use any 11-digit number starting with <code>09</code>.
                Choose <strong>Authorize</strong> for a successful payment or <strong>Decline</strong> to test a failed one.
            </div>

            <ul class="list mb-2">
                <li><span class="muted">Merchant</span><strong>PaxiliArt</strong></li>
                <li><span class="muted">Order</span><strong>{{ $order->order_number }}</strong></li>
                <li><span class="muted">Items</span><strong>{{ $order->items->count() }}</strong></li>
                <li><span class="muted">Amount to pay</span><strong class="gcash-amount">{{ money($order->total) }}</strong></li>
            </ul>

            <form method="POST" action="{{ route('payments.gcash.testPay', $order) }}">
                @csrf
                <div class="form-group">
                    <label for="gcash_number">GCash mobile number</label>
                    <input type="text" id="gcash_number" name="gcash_number" value="{{ old('gcash_number') }}"
                           inputmode="numeric" maxlength="13" placeholder="09XX XXX XXXX" autocomplete="off"
                           class="@error('gcash_number') is-invalid @enderror">
                    @error('gcash_number')<div class="error-text">{{ $message }}</div>@enderror
                </div>

                <button type="submit" name="action" value="authorize" class="btn btn-gcash btn-block">
                    Authorize payment of {{ money($order->total) }}
                </button>
                <button type="submit" name="action" value="decline" class="btn btn-outline btn-block mt-1" formnovalidate>
                    Decline
                </button>
            </form>

            <p class="text-center mt-2 mb-0"><a href="{{ route('orders.show', $order) }}" class="small">← Back to order</a></p>
        </div>
    </div>
</div>
@endsection
