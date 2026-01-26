<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function dailyReport(Request $request)
    {
        $date = $request->filled('date') ? now()->parse($request->date) : now();

        $orders = Order::whereDate('created_at', $date)
            ->where('status', 'completed')
            ->where('payment_status', 'paid')
            ->get();

        $itemsSold = OrderItem::whereHas('order', function ($query) use ($date) {
                $query->whereDate('created_at', $date)
                      ->where('status', 'completed');
            })
            ->selectRaw('menu_item_id, SUM(quantity) as total_quantity')
            ->groupBy('menu_item_id')
            ->orderByDesc('total_quantity')
            ->first();

        $bestSeller = $itemsSold ? MenuItem::find($itemsSold->menu_item_id) : null;

        $report = [
            'date' => $date->format('Y-m-d'),
            'total_orders' => $orders->count(),
            'total_revenue' => (float)$orders->sum('total'),
            'total_customers' => $orders->unique('user_id')->count(),
            'average_order_value' => (float)($orders->count() ? $orders->avg('total') : 0),
            'best_seller' => $bestSeller,
            'payment_breakdown' => [
                'cash' => (float)$orders->where('payment_method', 'cash')->sum('total'),
                'card' => (float)$orders->where('payment_method', 'card')->sum('total'),
                'mobile_money' => (float)$orders->where('payment_method', 'mobile_money')->sum('total'),
            ]
        ];

        DailyReport::updateOrCreate(
            ['date' => $date->format('Y-m-d')],
            [
                'total_orders' => $report['total_orders'],
                'total_revenue' => $report['total_revenue'],
                'total_customers' => $report['total_customers'],
                'best_seller_id' => $bestSeller?->id,
            ]
        );

        return response()->json(['success' => true, 'data' => $report]);
    }
}
