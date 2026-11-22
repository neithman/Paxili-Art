<?php

namespace App\Http\Controllers;

use App\Models\Artwork;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Payment;
use App\Services\GcashGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Checkout -> Order (pending) -> GCash payment -> Order (paid)
 *
 * The GCash part lives in:
 *   app/Services/GcashGateway.php      talks to PayMongo (real GCash)
 *   app/Http/Controllers/PaymentController.php  GCash test simulator + return from GCash
 *   app/Services/PaymentService.php    marks the order paid + sends notifications
 */
class OrderController extends Controller
{
    const GCASH_MINIMUM = 20; // pesos

    public function index(Request $request)
    {
        $orders = $request->user()->orders()->withCount('items')->latest()->paginate(10);

        return view('orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        $order->load(['items.artwork.store', 'items.download', 'payments']);

        return view('orders.show', ['order' => $order, 'live' => GcashGateway::isLive()]);
    }

    /** Turn the cart into a pending order. */
    public function checkout(Request $request)
    {
        $user = $request->user();
        $cart = $user->getCart()->load('items.artwork');

        // Re-check every item on the server before creating the order.
        $removed = [];
        foreach ($cart->items as $item) {
            $artwork = $item->artwork;
            $invalid = ! $artwork
                || $artwork->status !== Artwork::APPROVED
                || ($user->store && $user->store->id === $artwork->store_id)
                || $user->hasPurchased($artwork);

            if ($invalid) {
                $removed[] = $artwork->title ?? 'An item';
                $item->delete();
            }
        }
        $cart->load('items.artwork');

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', $removed
                ? 'Some items are no longer available and were removed: ' . implode(', ', $removed)
                : 'Your cart is empty.');
        }

        // DB::transaction = all of this succeeds together, or nothing is saved.
        $order = DB::transaction(function () use ($user, $cart) {
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => 'PXA-' . now()->format('ymd') . '-' . strtoupper(Str::random(5)),
                'total' => $cart->total(),
                'status' => Order::PENDING,
            ]);

            foreach ($cart->items as $item) {
                $order->items()->create([
                    'artwork_id' => $item->artwork->id,
                    'store_id' => $item->artwork->store_id,
                    'title' => $item->artwork->title,
                    'price' => $item->artwork->price,
                ]);
            }

            $cart->items()->delete(); // empty the cart

            return $order;
        });

        Notification::send($user, 'order_placed', 'Order placed',
            "Order {$order->order_number} was created. Complete the payment to get your downloads.",
            route('orders.show', $order));
        Notification::toAdmins('new_order', 'New order',
            "{$user->name} placed order {$order->order_number} (" . money($order->total) . ").",
            route('admin.orders.show', $order));

        return redirect()->route('orders.show', $order)
            ->with('success', 'Order created. Please complete the payment below.');
    }

    /**
     * Start a GCash payment for a pending order.
     *   TEST mode (no PayMongo key) -> open our GCash simulator page
     *   LIVE mode                   -> create a PayMongo checkout and send the buyer to GCash
     */
    public function pay(Request $request, Order $order)
    {
        $user = $request->user();
        abort_unless($order->user_id === $user->id, 403);

        if ($order->status !== Order::PENDING) {
            return back()->with('error', 'This order cannot be paid (status: ' . $order->status . ').');
        }

        // Make sure every artwork in the order can still be delivered.
        foreach ($order->items as $item) {
            if (! $item->artwork || $item->artwork->trashed() || $item->artwork->status === Artwork::REMOVED) {
                return back()->with('error', "\"{$item->title}\" is no longer available. Please cancel this order and checkout again.");
            }
        }

        // PayMongo does not accept GCash payments below ₱20.00
        if ($order->total < self::GCASH_MINIMUM) {
            return back()->with('error', 'GCash payments must be at least ' . money(self::GCASH_MINIMUM) . '. Add more artwork to your order.');
        }

        if (! GcashGateway::isLive()) {
            return redirect()->route('payments.gcash.test', $order);
        }

        // LIVE: save a "pending" payment, then ask PayMongo for a GCash checkout page.
        $payment = $order->payments()->create([
            'user_id' => $user->id,
            'method' => 'gcash',
            'mode' => 'live',
            'reference' => 'TXN-' . strtoupper(Str::random(12)),
            'amount' => $order->total,
            'status' => Payment::PENDING,
        ]);

        try {
            [$sessionId, $checkoutUrl] = GcashGateway::createCheckout($payment);
        } catch (\Throwable $e) {
            report($e); // writes the real error to storage/logs/laravel.log
            $payment->update(['status' => Payment::FAILED]);

            return back()->with('error', 'GCash is not available right now. Please try again in a few minutes.');
        }

        $payment->update(['provider_reference' => $sessionId]);

        return redirect()->away($checkoutUrl); // leave our site -> GCash
    }

    public function cancel(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        if ($order->status !== Order::PENDING) {
            return back()->with('error', 'Only unpaid orders can be cancelled.');
        }

        $order->update(['status' => Order::CANCELLED]);
        Notification::send($request->user(), 'order_update', 'Order cancelled',
            "Order {$order->order_number} was cancelled.", route('orders.show', $order));

        return redirect()->route('orders.index')->with('success', 'Order cancelled.');
    }
}
