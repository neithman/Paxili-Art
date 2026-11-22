<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;

/**
 * Talks to PayMongo, the payment gateway that processes GCash.
 * Uses Laravel's built-in HTTP client – no extra packages needed.
 *
 * How a LIVE GCash payment works:
 *   1. createCheckout()  -> PayMongo gives us a checkout_url
 *   2. We send the buyer there. They log in to GCash and approve the payment.
 *   3. PayMongo sends the buyer back to our "return" page.
 *   4. isPaid()          -> we ASK PayMongo if it was really paid (never trust the redirect alone).
 *
 * If PAYMONGO_SECRET_KEY is empty, the site uses the GCash TEST simulator instead.
 * Docs: https://developers.paymongo.com/docs/checkout-api
 */
class GcashGateway
{
    const API = 'https://api.paymongo.com/v1';

    /** true = real GCash via PayMongo, false = test simulator */
    public static function isLive(): bool
    {
        return ! empty(config('services.paymongo.secret_key'));
    }

    /**
     * Creates a PayMongo Checkout Session that only allows GCash.
     * Returns [checkout session id, URL to send the buyer to].
     */
    public static function createCheckout(Payment $payment): array
    {
        $order = $payment->order()->with('items', 'user')->first();

        $lineItems = $order->items->map(fn ($item) => [
            'name' => $item->title,
            'amount' => (int) round($item->price * 100), // PayMongo uses centavos: ₱150.00 = 15000
            'currency' => 'PHP',
            'quantity' => 1,
        ])->values()->all();

        $response = self::client()->post(self::API . '/checkout_sessions', [
            'data' => ['attributes' => [
                'line_items' => $lineItems,
                'payment_method_types' => ['gcash'],
                'description' => "PaxiliArt order {$order->order_number}",
                'reference_number' => $payment->reference,
                'billing' => ['name' => $order->user->name, 'email' => $order->user->email],
                'send_email_receipt' => true,
                'show_line_items' => true,
                'success_url' => route('payments.gcash.return', $payment),
                'cancel_url' => route('payments.gcash.cancel', $payment),
            ]],
        ])->throw(); // stops with an error if PayMongo says no

        return [$response->json('data.id'), $response->json('data.attributes.checkout_url')];
    }

    /** Asks PayMongo whether the checkout session was paid. */
    public static function isPaid(Payment $payment): bool
    {
        $response = self::client()
            ->get(self::API . '/checkout_sessions/' . $payment->provider_reference)
            ->throw();

        foreach ($response->json('data.attributes.payments', []) as $paid) {
            if (($paid['attributes']['status'] ?? null) === 'paid') {
                return true;
            }
        }

        return $response->json('data.attributes.payment_intent.attributes.status') === 'succeeded';
    }

    private static function client()
    {
        // PayMongo uses "Basic Auth": the secret key is the username, password is empty.
        return Http::withBasicAuth(config('services.paymongo.secret_key'), '')
            ->acceptJson()
            ->timeout(20);
    }
}
