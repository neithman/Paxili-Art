<?php

namespace App\Http\Controllers;

use App\Models\Artwork;
use App\Models\Download;
use App\Models\Notification;
use App\Models\OrderItem;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * "My Purchases" and the ONE-TIME secure download.
 */
class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $items = OrderItem::with(['order', 'artwork.store', 'download', 'review'])
            ->whereHas('order', fn ($q) => $q->where('user_id', $request->user()->id)->where('status', 'paid'))
            ->latest()
            ->paginate(12);

        return view('purchases.index', compact('items'));
    }

    public function download(Request $request, OrderItem $orderItem)
    {
        $user = $request->user();
        $order = $orderItem->order;

        // RULE 1: it must be YOUR purchase.
        abort_unless($order->user_id === $user->id, 403);

        // RULE 2: payment must be verified.
        if (! $order->isPaymentVerified()) {
            return back()->with('error', 'Payment for this order has not been verified yet.');
        }

        // RULE 3: only ONE download per purchased artwork.
        if ($orderItem->download) {
            return back()->with('error', 'You already downloaded this artwork on '
                . $orderItem->download->downloaded_at->format('M d, Y h:i A') . '.');
        }

        // RULE 4: the file must still exist (admin may have removed prohibited content).
        $artwork = $orderItem->artwork;
        if (! $artwork || $artwork->status === Artwork::REMOVED || ! Storage::disk('local')->exists($artwork->file_path)) {
            return back()->with('error', 'This file is no longer available. Please contact support.');
        }

        // Prepare the PRIVATE file response FIRST, so if anything goes wrong here
        // the buyer's one download is NOT used up.
        $response = Storage::disk('local')->download($artwork->file_path, $artwork->file_name);

        // A cookie tells our JavaScript the download started, so it can disable
        // the button and show the review popup (see public/js/app.js).
        $response->headers->setCookie(cookie('download_done', (string) $orderItem->id, 1, null, null, false, false));

        // Record the download. The UNIQUE index on order_item_id makes a double-click
        // or two tabs at the same time impossible to abuse.
        try {
            $download = Download::create([
                'order_item_id' => $orderItem->id,
                'user_id' => $user->id,
                'artwork_id' => $artwork->id,
                'ip_address' => $request->ip(),
                'downloaded_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException $e) {
            return back()->with('error', 'This artwork was already downloaded.');
        }

        $this->sendDownloadNotifications($orderItem, $download);

        return $response;
    }

    private function sendDownloadNotifications(OrderItem $orderItem, Download $download): void
    {
        $buyer = $download->user;
        $order = $orderItem->order;
        $when = $download->downloaded_at->format('M d, Y h:i A');

        Notification::send($buyer, 'download_confirmed', 'Download complete',
            "You downloaded \"{$orderItem->title}\" on {$when}.", route('purchases.index'));

        if (! $orderItem->review) {
            Notification::send($buyer, 'review_reminder', 'How was it?',
                "Leave a rating for \"{$orderItem->title}\" – it helps the artist!",
                route('artworks.show', $orderItem->artwork->slug));
        }

        if ($orderItem->store) {
            Notification::send($orderItem->store->user, 'artwork_downloaded', 'Artwork downloaded',
                "{$buyer->name} downloaded \"{$orderItem->title}\" (order {$order->order_number}) on {$when}.",
                route('seller.sales'));
        }

        Notification::toAdmins('download', 'Download activity',
            "{$buyer->name} downloaded \"{$orderItem->title}\" (order {$order->order_number}).",
            route('admin.downloads.index'));
    }
}
