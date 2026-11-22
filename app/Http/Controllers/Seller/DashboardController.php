<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Artwork;
use App\Models\Download;
use App\Models\OrderItem;
use App\Services\SalesReport;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $store = $request->user()->store;

        // Sold items of THIS store from PAID orders only
        $soldItems = OrderItem::where('store_id', $store->id)
            ->whereHas('order', fn ($q) => $q->where('status', 'paid'));

        $stats = [
            'total' => $store->artworks()->count(),
            'approved' => $store->artworks()->where('status', Artwork::APPROVED)->count(),
            'pending' => $store->artworks()->where('status', Artwork::PENDING)->count(),
            'rejected' => $store->artworks()->where('status', Artwork::REJECTED)->count(),
            'sales' => (clone $soldItems)->count(),
            'earnings' => (float) (clone $soldItems)->sum('price'),
        ];

        $recentOrders = (clone $soldItems)->with(['order.user', 'download'])->latest()->take(6)->get();

        $recentDownloads = Download::with(['user', 'orderItem.order'])
            ->whereHas('orderItem', fn ($q) => $q->where('store_id', $store->id))
            ->latest('downloaded_at')->take(6)->get();

        $chart = SalesReport::chart($request->query('period', 'daily'), $store->id);

        return view('seller.dashboard', compact('store', 'stats', 'recentOrders', 'recentDownloads', 'chart'));
    }

    /** Full list of everything this seller has sold, with download status. */
    public function sales(Request $request)
    {
        $store = $request->user()->store;

        $items = OrderItem::where('store_id', $store->id)
            ->whereHas('order', fn ($q) => $q->where('status', 'paid'))
            ->with(['order.user', 'download'])
            ->latest()
            ->paginate(15);

        return view('seller.sales', compact('items'));
    }
}
