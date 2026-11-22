<?php

namespace App\Services;

use App\Models\OrderItem;
use Carbon\Carbon;

/**
 * Builds the data for the sales graphs (admin + seller dashboards).
 *
 *   SalesReport::chart('daily')            -> whole marketplace
 *   SalesReport::chart('monthly', $storeId) -> only one seller's store
 *
 * Returns: ['labels' => [...], 'revenue' => [...], 'sales' => [...]]
 * Everything comes from REAL paid orders in the database.
 */
class SalesReport
{
    public static function chart(string $period = 'daily', ?int $storeId = null): array
    {
        $now = Carbon::now();

        // 1) Decide the time buckets for the chosen period.
        if ($period === 'monthly') {
            $start = $now->copy()->startOfMonth()->subMonths(11);
            $keyFormat = 'Y-m';
            $labelFormat = 'M Y';
            $step = 'addMonth';
            $count = 12;
        } elseif ($period === 'weekly') {
            $start = $now->copy()->startOfWeek()->subWeeks(11);
            $keyFormat = 'o-W'; // ISO year + week number
            $labelFormat = '\W\k M d';
            $step = 'addWeek';
            $count = 12;
        } else {
            $period = 'daily';
            $start = $now->copy()->startOfDay()->subDays(13);
            $keyFormat = 'Y-m-d';
            $labelFormat = 'M d';
            $step = 'addDay';
            $count = 14;
        }

        // 2) Create empty buckets so days with no sales still appear as 0.
        $buckets = [];
        $cursor = $start->copy();
        for ($i = 0; $i < $count; $i++) {
            $buckets[$cursor->format($keyFormat)] = [
                'label' => $cursor->format($labelFormat),
                'revenue' => 0,
                'sales' => 0,
            ];
            $cursor->$step();
        }

        // 3) Load sold items from PAID orders within the range.
        $items = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'paid')
            ->where('orders.paid_at', '>=', $start)
            ->when($storeId, fn ($q) => $q->where('order_items.store_id', $storeId))
            ->get(['order_items.price', 'orders.paid_at']);

        // 4) Put each sale into its bucket.
        foreach ($items as $item) {
            $key = Carbon::parse($item->paid_at)->format($keyFormat);
            if (isset($buckets[$key])) {
                $buckets[$key]['revenue'] += (float) $item->price;
                $buckets[$key]['sales']++;
            }
        }

        return [
            'period' => $period,
            'labels' => array_column($buckets, 'label'),
            'revenue' => array_map(fn ($v) => round($v, 2), array_column($buckets, 'revenue')),
            'sales' => array_column($buckets, 'sales'),
        ];
    }
}
