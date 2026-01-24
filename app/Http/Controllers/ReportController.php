<?php
namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // Rapport journalier
    public function dailyReport(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date'
        ]);

        $date = $request->date ? \Carbon\Carbon::parse($request->date) : today();

        // Récupérer les commandes du jour
        $orders = Order::whereDate('created_at', $date)
            ->where('status', 'completed')
            ->where('payment_status', 'paid')
            ->with('items.menuItem')
            ->get();

        // Calculer le meilleur vendeur
        $bestSeller = OrderItem::whereHas('order', function ($query) use ($date) {
                $query->whereDate('created_at', $date)
                    ->where('status', 'completed');
            })
            ->select('menu_item_id', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('menu_item_id')
            ->orderByDesc('total_quantity')
            ->first();

        // Statistiques
        $report = [
            'date' => $date->format('Y-m-d'),
            'total_orders' => $orders->count(),
            'total_revenue' => $orders->sum('total'),
            'total_customers' => $orders->unique('user_id')->count(),
            'average_order_value' => $orders->count() > 0 ? $orders->avg('total') : 0,
            'best_seller' => $bestSeller ? MenuItem::find($bestSeller->menu_item_id) : null,
            'best_seller_quantity' => $bestSeller ? $bestSeller->total_quantity : 0,
            'payment_breakdown' => [
                'cash' => $orders->where('payment_method', 'cash')->sum('total'),
                'card' => $orders->where('payment_method', 'card')->sum('total'),
                'mobile_money' => $orders->where('payment_method', 'mobile_money')->sum('total')
            ],
            'order_status_distribution' => [
                'pending' => Order::whereDate('created_at', $date)->where('status', 'pending')->count(),
                'confirmed' => Order::whereDate('created_at', $date)->where('status', 'confirmed')->count(),
                'preparing' => Order::whereDate('created_at', $date)->where('status', 'preparing')->count(),
                'ready' => Order::whereDate('created_at', $date)->where('status', 'ready')->count(),
                'served' => Order::whereDate('created_at', $date)->where('status', 'served')->count(),
                'completed' => $orders->count(),
                'cancelled' => Order::whereDate('created_at', $date)->where('status', 'cancelled')->count()
            ]
        ];

        // Détails par heure (pour graphique)
        $hourlyData = Order::whereDate('created_at', $date)
            ->where('status', 'completed')
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderBy('hour')
            ->get();

        $report['hourly_data'] = $hourlyData;

        // Sauvegarder le rapport
        DailyReport::updateOrCreate(
            ['date' => $date],
            [
                'total_orders' => $report['total_orders'],
                'total_revenue' => $report['total_revenue'],
                'total_customers' => $report['total_customers'],
                'best_seller_id' => $bestSeller?->menu_item_id
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    // Rapport par période
    public function periodReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        // Statistiques générales
        $stats = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->select([
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total) as total_revenue'),
                DB::raw('AVG(total) as average_order_value'),
                DB::raw('COUNT(DISTINCT user_id) as total_customers')
            ])
            ->first();

        // Meilleurs vendeurs
        $topSellers = OrderItem::whereHas('order', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', 'completed');
            })
            ->with('menuItem')
            ->select([
                'menu_item_id',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(quantity * unit_price) as total_revenue')
            ])
            ->groupBy('menu_item_id')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        // Répartition par jour
        $dailyTrends = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as daily_revenue')
            ])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $report = [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days_count' => \Carbon\Carbon::parse($startDate)->diffInDays($endDate) + 1
            ],
            'statistics' => $stats,
            'top_sellers' => $topSellers,
            'daily_trends' => $dailyTrends,
            'payment_methods' => [
                'cash' => Order::whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', 'completed')
                    ->where('payment_method', 'cash')
                    ->sum('total'),
                'card' => Order::whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', 'completed')
                    ->where('payment_method', 'card')
                    ->sum('total'),
                'mobile_money' => Order::whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', 'completed')
                    ->where('payment_method', 'mobile_money')
                    ->sum('total')
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    // Historique des rapports
    public function reportHistory(Request $request)
    {
        $reports = DailyReport::with('bestSeller')
            ->orderBy('date', 'desc')
            ->paginate(30);

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    // Rapport de vente par catégorie
    public function categoryReport(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
        ]);

        $startDate = $request->start_date ?? today()->subMonth();
        $endDate = $request->end_date ?? today();

        $categorySales = OrderItem::whereHas('order', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', 'completed');
            })
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->join('categories', 'menu_items.category_id', '=', 'categories.id')
            ->select([
                'categories.id',
                'categories.name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_revenue'),
                DB::raw('COUNT(DISTINCT order_items.order_id) as order_count')
            ])
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_revenue')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => ['start_date' => $startDate, 'end_date' => $endDate],
                'category_sales' => $categorySales
            ]
        ]);
    }
}