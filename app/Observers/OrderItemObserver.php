<?php
namespace App\Observers;

use App\Models\Order;
use App\Models\DailyReport;
use Carbon\Carbon;

class OrderItemObserver
{
    public function updated(Order $order)
    {
        // Si une commande est marquée comme complétée et payée
        if ($order->status === 'completed' && $order->payment_status === 'paid') {
            $this->updateDailyReport($order);
        }
    }

    private function updateDailyReport(Order $order)
    {
        $date = $order->created_at->toDateString();

        $report = DailyReport::firstOrCreate(
            ['date' => $date],
            [
                'total_orders' => 0,
                'total_revenue' => 0,
                'total_customers' => 0
            ]
        );

        // Incrémenter les compteurs
        $report->increment('total_orders');
        $report->increment('total_revenue', $order->total);

        // Compter les clients uniques
        if ($order->user_id) {
            $ordersOfDay = Order::whereDate('created_at', $date)
                ->where('status', 'completed')
                ->where('payment_status', 'paid')
                ->pluck('user_id')
                ->unique()
                ->count();

            $report->total_customers = $ordersOfDay;
        }

        // Trouver le meilleur vendeur du jour
        $bestSeller = $this->getBestSellerOfDay($date);
        if ($bestSeller) {
            $report->best_seller_id = $bestSeller;
        }

        $report->save();
    }

    private function getBestSellerOfDay($date)
    {
        $bestSeller = \App\Models\OrderItem::whereHas('order', function ($query) use ($date) {
                $query->whereDate('created_at', $date)
                    ->where('status', 'completed')
                    ->where('payment_status', 'paid');
            })
            ->select('menu_item_id', \DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('menu_item_id')
            ->orderByDesc('total_quantity')
            ->first();

        return $bestSeller ? $bestSeller->menu_item_id : null;
    }
}
