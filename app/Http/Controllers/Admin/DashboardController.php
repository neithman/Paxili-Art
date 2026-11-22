<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Artwork;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\User;
use App\Services\SalesReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $paidItems = OrderItem::whereHas('order', fn ($q) => $q->where('status', 'paid'));

        // Every number below is calculated from the database – nothing is hard-coded.
        $stats = [
            'users' => User::count(),
            'buyers' => Order::where('status', 'paid')->distinct('user_id')->count('user_id'),
            'sellers' => Store::count(),
            'artworks' => Artwork::count(),
            'pending' => Artwork::where('status', Artwork::PENDING)->count(),
            'orders' => Order::count(),
            'paid_orders' => Order::where('status', 'paid')->count(),
            'sales' => (clone $paidItems)->count(),
            'revenue' => (float) Order::where('status', 'paid')->sum('total'),
        ];

        $bestSellers = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'paid')
            ->whereNotNull('order_items.artwork_id')
            ->select('order_items.artwork_id', DB::raw('MAX(order_items.title) as title'),
                DB::raw('COUNT(*) as sold'), DB::raw('SUM(order_items.price) as revenue'))
            ->groupBy('order_items.artwork_id')
            ->orderByDesc('sold')
            ->take(5)
            ->get();

        $recentOrders = Order::with('user')->latest()->take(6)->get();

        $chart = SalesReport::chart($request->query('period', 'daily'));

        return view('admin.dashboard', compact('stats', 'bestSellers', 'recentOrders', 'chart'));
    }
}
