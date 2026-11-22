<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\GcashGateway;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * GCash payment pages.
 *
 * TEST MODE (PAYMONGO_SECRET_KEY is empty)
 *   testPage() / testPay()  -> a GCash SIMULATOR. No real money. You choose Authorize or Decline.
 *
 * LIVE MODE (PAYMONGO_SECRET_KEY is set)
 *   gcashReturn()  -> GCash/PayMongo sends the buyer back here after paying.
 *                     We ask PayMongo if it was really paid before unlocking downloads.
 *   gcashCancel()  -> the buyer cancelled on the GCash page.
 */
class PaymentController extends Controller
{
    // ===================== TEST MODE: GCash simulator =====================

    public function testPage(Request $request, Order $order)
    {
        $this->checkOrder($request, $order);

        if ($order->status !== Order::PENDING) {
            return redirect()->route('orders.show', $order);
        }

        return view('payments.gcash-test', compact('order'));
    }

    public function testPay(Request $request, Order $order)
    {
        $this->checkOrder($request, $order);

        if ($order->status !== Order::PENDING) {
            return redirect()->route('orders.show', $order)->with('error', 'This order is no longer waiting for payment.');
        }

        // "0917 123 4567" -> "09171234567"
        $request->merge(['gcash_number' => preg_replace('/\D/', '', (string) $request->gcash_number)]);
        $data = $request->validate([
            'action' => ['required', 'in:authorize,decline'],
            'gcash_number' => ['required_if:action,authorize', 'nullable', 'regex:/^09\d{9}$/'],
        ], [
            'gcash_number.required_if' => 'Enter your GCash mobile number.',
            'gcash_number.regex' => 'Enter an 11-digit GCash number that starts with 09, e.g. 09171234567.',
        ]);

        $payment = $order->payments()->create([
            'user_id' => $request->user()->id,
            'method' => 'gcash',
            'mode' => 'test',
            'reference' => 'TXN-' . strtoupper(Str::random(12)),
            'amount' => $order->total,
            'status' => Payment::PENDING,
            'account_number' => $data['gcash_number'] ? Payment::maskNumber($data['gcash_number']) : null,
        ]);

        if ($data['action'] === 'decline') {
            PaymentService::fail($payment, 'You declined the GCash payment.');

            return redirect()->route('orders.show', $order)->with('error', 'GCash payment declined. You can try again.');
        }

        PaymentService::complete($payment);

        return redirect()->route('purchases.index')
            ->with('success', "GCash payment successful! Reference {$payment->reference}. Your artwork is ready to download.");
    }

    // ===================== LIVE MODE: back from GCash =====================

    public function gcashReturn(Request $request, Payment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        if ($payment->status === Payment::SUCCESS) {
            return redirect()->route('purchases.index')->with('success', 'This GCash payment is already confirmed.');
        }

        if ($payment->mode !== 'live' || ! $payment->provider_reference) {
            return redirect()->route('orders.show', $payment->order);
        }

        try {
            $paid = GcashGateway::isPaid($payment); // ask PayMongo – never trust the redirect alone
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('orders.show', $payment->order)
                ->with('info', 'We could not reach GCash to confirm your payment. Please click "Check payment status" in a moment.');
        }

        if (! $paid) {
            return redirect()->route('orders.show', $payment->order)
                ->with('info', 'Your GCash payment is not confirmed yet. If you already paid, click "Check payment status" in a moment.');
        }

        PaymentService::complete($payment);

        return redirect()->route('purchases.index')
            ->with('success', "GCash payment successful! Reference {$payment->reference}. Your artwork is ready to download.");
    }

    public function gcashCancel(Request $request, Payment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        PaymentService::fail($payment, 'The GCash payment was cancelled.');

        return redirect()->route('orders.show', $payment->order)->with('error', 'GCash payment cancelled. You can try again.');
    }

    // ===================== helpers =====================

    private function checkOrder(Request $request, Order $order): void
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        abort_if(GcashGateway::isLive(), 404); // the simulator is switched off when real GCash is on
    }
}
