<?php

namespace App\Http\Controllers;

use App\Models\Artwork;
use App\Models\CartItem;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart = $request->user()->getCart()->load('items.artwork.store');

        return view('cart.index', compact('cart'));
    }

    public function add(Request $request, Artwork $artwork)
    {
        $user = $request->user();

        // Server-side rules (never trust the button alone):
        if ($artwork->status !== Artwork::APPROVED) {
            return back()->with('error', 'This artwork is not available for sale.');
        }
        if ($user->store && $user->store->id === $artwork->store_id) {
            return back()->with('error', 'You cannot buy your own artwork.');
        }
        if ($user->hasPurchased($artwork)) {
            return back()->with('error', 'You already own this artwork. Find it in My Purchases.');
        }

        $user->getCart()->items()->firstOrCreate(['artwork_id' => $artwork->id]);

        return back()->with('success', "\"{$artwork->title}\" was added to your cart.");
    }

    public function remove(Request $request, CartItem $cartItem)
    {
        abort_unless($cartItem->cart->user_id === $request->user()->id, 403);

        $cartItem->delete();

        return back()->with('success', 'Item removed from cart.');
    }
}
