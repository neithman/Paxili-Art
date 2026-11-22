<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\OrderItem;
use App\Models\Review;
use Illuminate\Http\Request;

/**
 * Create OR edit a review. Only the verified buyer of a PAID order item can review.
 */
class ReviewController extends Controller
{
    public function save(Request $request, OrderItem $orderItem)
    {
        $user = $request->user();

        abort_unless($orderItem->order->user_id === $user->id, 403);
        abort_unless($orderItem->order->isPaid(), 403, 'Only verified buyers can review.');
        abort_unless($orderItem->artwork, 404);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], ['rating.required' => 'Please choose 1 to 5 stars.']);

        // One review per buyer per artwork: update it if it already exists.
        $review = Review::where('user_id', $user->id)->where('artwork_id', $orderItem->artwork_id)->first();

        if ($review) {
            $review->update($data);

            return back()->with('success', 'Your review was updated.');
        }

        Review::create($data + [
            'user_id' => $user->id,
            'artwork_id' => $orderItem->artwork_id,
            'order_item_id' => $orderItem->id,
        ]);

        if ($orderItem->store) {
            Notification::send($orderItem->store->user, 'new_review', 'New review ' . str_repeat('★', $data['rating']),
                "{$user->name} reviewed \"{$orderItem->title}\".",
                route('artworks.show', $orderItem->artwork->slug));
        }

        return back()->with('success', 'Thanks for your review!');
    }
}
