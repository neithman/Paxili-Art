<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Download;
use App\Models\Order;
use Illuminate\Http\Request;

/**
 * Order, payment and download tracking for admins.
 */
class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with(['user', 'payments'])
            ->withCount('items')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('order_number', 'like', "%{$s}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"))))
            ->latest()->paginate(15)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['user', 'items.store.user', 'items.download', 'payments']);

        return view('admin.orders.show', compact('order'));
    }

    public function downloads()
    {
        $downloads = Download::with(['user', 'orderItem.order', 'orderItem.store'])
            ->latest('downloaded_at')->paginate(20);

        return view('admin.downloads', compact('downloads'));
    }
}
