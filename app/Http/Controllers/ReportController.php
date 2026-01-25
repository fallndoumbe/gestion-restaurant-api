<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItem;
use App\Models\DailyReport;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function dailyReport(Request $request)
    {
        $date = $request->date ?? today();

        $orders = Order::whereDate('created_at', $date)
            ->where('status', 'completed')
            ->where('payment_status', 'paid')
            ->get();

        $itemsSold = OrderItem::whereHas('order', function ($query) use ($date) {
            $query->whereDate('created_at', $date)
                  ->where('status', 'completed');
        })->selectRaw('menu_item_id, SUM(quantity) as total_quantity')
          ->groupBy('menu_item_id')
          ->orderByDesc('total_quantity')
          ->first();

        $report = [
            'date' => $date->format('Y-m-d'),
            'total_orders' => $orders->count(),
            'total_revenue' => $orders->sum('total'),
            'total_customers' => $orders->unique('user_id')->count(),
            'average_order_value' => $orders->avg('total'),
            'best_seller' => $itemsSold ? MenuItem::find($itemsSold->menu_item_id) : null,
            'payment_breakdown' => [
                'cash' => $orders->where('payment_method', 'cash')->sum('total'),
                'card' => $orders->where('payment_method', 'card')->sum('total'),
                'mobile_money' => $orders->where('payment_method', 'mobile_money')->sum('total')
            ]
        ];

        // Sauvegarder le rapport
        DailyReport::updateOrCreate(
            ['date' => $date],
            [
                'total_orders' => $report['total_orders'],
                'total_revenue' => $report['total_revenue'],
                'total_customers' => $report['total_customers'],
                'best_seller_id' => $itemsSold?->menu_item_id
            ]
        );

        return response()->json(['success' => true, 'data' => $report]);
    }
}
