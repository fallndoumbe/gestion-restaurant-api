<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItem;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // Liste des commandes (serveur/gérant)
    public function index()
    {
        $orders = Order::with(['user', 'table', 'server', 'items.menuItem'])->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    // Détail d'une commande (authentifié)
    public function show($id)
    {
        $order = Order::with(['user', 'table', 'server', 'items.menuItem'])->find($id);

        if (!$order) {
            return response()->json(['message' => 'Commande non trouvée'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    // Créer une commande (client/serveur)
    public function store(Request $request)
    {
        $request->validate([
            'table_id' => 'required|exists:tables,id',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.special_notes' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500'
        ]);

        // Vérifier que la table existe
        $table = Table::find($request->table_id);
        if (!$table) {
            return response()->json(['message' => 'Table non trouvée'], 404);
        }

        // Vérifier que les plats sont disponibles
        foreach ($request->items as $item) {
            $menuItem = MenuItem::find($item['menu_item_id']);
            if (!$menuItem || !$menuItem->is_available) {
                return response()->json([
                    'message' => "Le plat {$menuItem->name} n'est pas disponible"
                ], 400);
            }
        }

        DB::beginTransaction();

        try {
            // Créer la commande
            $order = Order::create([
                'user_id' => auth()->id(),
                'table_id' => $request->table_id,
                'server_id' => auth()->user()->role === 'server' ? auth()->id() : null,
                'status' => 'pending',
                'notes' => $request->notes,
                'subtotal' => 0,
                'tax' => 0,
                'total' => 0
            ]);

            $subtotal = 0;

            // Ajouter les items
            foreach ($request->items as $itemData) {
                $menuItem = MenuItem::find($itemData['menu_item_id']);

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $itemData['menu_item_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $menuItem->price,
                    'special_notes' => $itemData['special_notes'] ?? null,
                    'status' => 'pending'
                ]);

                $subtotal += $menuItem->price * $itemData['quantity'];
            }

            // Calculer les totaux
            $taxRate = 0.18; // 18% TVA
            $tax = $subtotal * $taxRate;
            $total = $subtotal + $tax;

            $order->update([
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $order->load('items.menuItem')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de la création: ' . $e->getMessage()], 500);
        }
    }

    // Modifier une commande (serveur)
    public function update(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Commande non trouvée'], 404);
        }

        // Seuls les serveurs peuvent modifier les commandes
        if (auth()->user()->role !== 'server' && auth()->user()->role !== 'manager') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $request->validate([
            'notes' => 'nullable|string|max:500',
            'server_id' => 'nullable|exists:users,id'
        ]);

        $order->update($request->only(['notes', 'server_id']));

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    // Changer le statut d'une commande (serveur)
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,preparing,ready,served,completed,cancelled'
        ]);

        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Commande non trouvée'], 404);
        }

        $order->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour',
            'data' => $order
        ]);
    }

    // Ajouter des items à une commande (serveur)
    public function addItems(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Commande non trouvée'], 404);
        }

        if ($order->status === 'completed' || $order->status === 'cancelled') {
            return response()->json(['message' => 'Impossible de modifier une commande terminée'], 400);
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.special_notes' => 'nullable|string|max:255'
        ]);

        DB::beginTransaction();

        try {
            foreach ($request->items as $itemData) {
                $menuItem = MenuItem::find($itemData['menu_item_id']);

                if (!$menuItem->is_available) {
                    throw new \Exception("{$menuItem->name} n'est pas disponible");
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $itemData['menu_item_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $menuItem->price,
                    'special_notes' => $itemData['special_notes'] ?? null,
                    'status' => 'pending'
                ]);
            }

            // Recalculer les totaux
            $order->calculateTotals();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Items ajoutés avec succès',
                'data' => $order->load('items.menuItem')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    // Retirer un item d'une commande (serveur)
    public function removeItem($orderId, $itemId)
    {
        $orderItem = OrderItem::where('order_id', $orderId)
            ->where('id', $itemId)
            ->first();

        if (!$orderItem) {
            return response()->json(['message' => 'Item non trouvé'], 404);
        }

        $order = Order::find($orderId);

        if ($order->status === 'completed' || $order->status === 'cancelled') {
            return response()->json(['message' => 'Impossible de modifier une commande terminée'], 400);
        }

        $orderItem->delete();

        // Recalculer les totaux
        $order->calculateTotals();

        return response()->json([
            'success' => true,
            'message' => 'Item retiré avec succès'
        ]);
    }

    // Payer une commande (serveur)
    public function pay(Request $request, $id)
    {
        $request->validate([
            'payment_method' => 'required|in:cash,card,mobile_money'
        ]);

        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Commande non trouvée'], 404);
        }

        $order->update([
            'payment_method' => $request->payment_method,
            'payment_status' => 'paid',
            'status' => 'completed'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Paiement effectué avec succès',
            'data' => $order
        ]);
    }

    // Générer la facture (serveur)
    public function bill($id)
    {
        $order = Order::with(['table', 'server', 'items.menuItem'])->find($id);

        if (!$order) {
            return response()->json(['message' => 'Commande non trouvée'], 404);
        }

        $bill = [
            'order_id' => $order->id,
            'table_number' => $order->table->number,
            'date' => $order->created_at->format('Y-m-d'),
            'time' => $order->created_at->format('H:i'),
            'server' => $order->server ? $order->server->name : $order->user->name,
            'items' => $order->items->map(function ($item) {
                return [
                    'name' => $item->menuItem->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total' => $item->unit_price * $item->quantity
                ];
            }),
            'subtotal' => $order->subtotal,
            'tax' => $order->tax,
            'total' => $order->total,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status
        ];

        return response()->json([
            'success' => true,
            'data' => $bill
        ]);
    }

    // Commandes d'une table (serveur)
    public function tableOrders($tableId)
    {
        $orders = Order::where('table_id', $tableId)
            ->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready', 'served'])
            ->with('items.menuItem')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }
}
