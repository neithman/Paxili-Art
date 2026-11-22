<?php

namespace App\Http\Controllers;

use App\Models\Artwork;
use App\Models\Category;
use App\Models\Store;

class HomeController extends Controller
{
    public function index()
    {
        $latest = Artwork::approved()->with('store')->latest()->take(8)->get();

        $categories = Category::withCount(['artworks' => fn ($q) => $q->approved()])
            ->orderBy('name')->get();

        // Stores that have at least one approved artwork
        $stores = Store::whereHas('artworks', fn ($q) => $q->approved())
            ->withCount(['artworks' => fn ($q) => $q->approved()])
            ->orderByDesc('artworks_count')
            ->take(4)->get();

        return view('home', compact('latest', 'categories', 'stores'));
    }
}
