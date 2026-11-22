<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

/**
 * What happens after GCash answers "paid" or "failed".
 * Used by BOTH the test simulator and the live PayMongo flow,
 * so the rest of the site (downloads, dashboards…) works the same way.
 */
class PaymentService
{
    /** Mark a payment as successful and the order as paid, then notify everyone. */
    public static function complete(Payment $payment, ?string $accountNumber = null): void
    {
        $order = $payment->order;

        if ($payment->status === Payment::SUCCESS) {
            return; // already processed (e.g. the buyer refreshed the return page)
        }

        DB::transaction(function () use ($payment, $order, $accountNumber) {
            $payment->update([
                'status' => Payment::SUCCESS,
                'paid_at' => now(),
                'account_number' => $accountNumber ?? $payment->account_number,
            ]);
            $order->update(['status' => Order::PAID, 'paid_at' => now()]);
        });

        self::notifyPaid($order->fresh(), $payment);
    }

    public static function fail(Payment $payment, string $reason = 'The GCash payment was not completed.'): void
    {
        if ($payment->status !== Payment::PENDING) {
            return;
        }

        $payment->update(['status' => Payment::FAILED]);

        Notification::send($payment->user, 'payment_failed', 'GCash payment failed',
            "{$reason} Order {$payment->order->order_number} is still waiting – you can try again.",
            route('orders.show', $payment->order));
    }

    private static function notifyPaid(Order $order, Payment $payment): void
    {
        $buyer = $order->user;
        $amount = money($order->total);

        // Buyer
        Notification::send($buyer, 'payment_confirmed', 'GCash payment confirmed',
            "We received {$amount} via GCash for order {$order->order_number} (ref {$payment->reference}).",
            route('orders.show', $order));
        Notification::send($buyer, 'download_ready', 'Your artwork is ready',
            'Your purchased artwork is now available. Remember: each item can be downloaded ONCE.',
            route('purchases.index'));

        // Each seller in this order
        foreach ($order->items()->with('store.user')->get()->groupBy('store_id') as $items) {
            $store = $items->first()->store;
            if (! $store) {
                continue;
            }
            Notification::send($store->user, 'new_purchase', 'New purchase! 🎉',
                "{$buyer->name} bought: " . $items->pluck('title')->implode(', ') . " (order {$order->order_number}).",
                route('seller.sales'));
        }

        // Admins
        Notification::toAdmins('new_payment', 'GCash payment received',
            "Order {$order->order_number} was paid ({$amount}) by {$buyer->name} via GCash.",
            route('admin.orders.show', $order));
    }
}
