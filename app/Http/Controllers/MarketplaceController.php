<?php

namespace App\Http\Controllers;

use App\Models\Artwork;
use App\Models\Category;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    /** Browse + search + filter APPROVED artwork. */
    public function index(Request $request)
    {
        $query = Artwork::approved()->with(['store', 'category']);

        // Search by title or tags
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(fn ($w) => $w->where('title', 'like', "%{$q}%")->orWhere('tags', 'like', "%{$q}%"));
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->whereHas('category', fn ($c) => $c->where('slug', $request->category));
        }

        // Filter by artist (store name or the seller's name)
        if ($request->filled('artist')) {
            $artist = $request->artist;
            $query->whereHas('store', fn ($s) => $s->where('name', 'like', "%{$artist}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$artist}%")));
        }

        // Filter by price range
        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->max_price);
        }

        // Sorting
        match ($request->sort) {
            'price_low' => $query->orderBy('price'),
            'price_high' => $query->orderByDesc('price'),
            'oldest' => $query->oldest(),
            default => $query->latest(),
        };

        $artworks = $query->paginate(12)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return view('marketplace.index', compact('artworks', 'categories'));
    }

    /** Artwork detail page. */
    public function show(Artwork $artwork)
    {
        $user = auth()->user();
        $isOwner = $user && $user->store && $user->store->id === $artwork->store_id;

        // Not approved? Only the owner or an admin may preview it.
        if ($artwork->status !== Artwork::APPROVED && ! $isOwner && ! ($user && $user->isAdmin())) {
            abort(404);
        }

        $artwork->load(['store.user', 'category', 'reviews.user']);

        $purchased = $user ? $user->hasPurchased($artwork) : false;
        $inCart = $user ? $user->getCart()->items()->where('artwork_id', $artwork->id)->exists() : false;

        $related = Artwork::approved()
            ->where('id', '!=', $artwork->id)
            ->where(fn ($q) => $q->where('category_id', $artwork->category_id)->orWhere('store_id', $artwork->store_id))
            ->latest()->take(4)->get();

        return view('marketplace.show', compact('artwork', 'isOwner', 'purchased', 'inCart', 'related'));
    }
}
