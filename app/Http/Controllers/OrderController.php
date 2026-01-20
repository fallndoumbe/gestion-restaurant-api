<?php
namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    // Lister toutes les commandes (Serveur/Gérant)
    public function index(Request $request)
    {
        $query = Order::with(['user', 'table', 'server', 'items.menuItem'])
            ->orderBy('created_at', 'desc');

        // Filtres
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->has('table_id')) {
            $query->where('table_id', $request->table_id);
        }

        if (Auth::user()->role === 'server') {
            $query->where('server_id', Auth::id());
        }

        $orders = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    // Afficher une commande spécifique
    public function show($id)
    {
        $order = Order::with(['user', 'table', 'server', 'items.menuItem'])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée'
            ], 404);
        }

        // Vérifier les autorisations
        $user = Auth::user();
        if ($user->role === 'client' && $order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    // Créer une nouvelle commande
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

        try {
            DB::beginTransaction();

            // Vérifier si la table est disponible
            $table = Table::find($request->table_id);
            if (!$table || $table->status === 'reserved') {
                throw new \Exception('Cette table n\'est pas disponible');
            }

            // Créer la commande
            $order = Order::create([
                'user_id' => Auth::user()->role === 'client' ? Auth::id() : null,
                'table_id' => $request->table_id,
                'server_id' => Auth::user()->role === 'server' ? Auth::id() : null,
                'status' => 'pending',
                'payment_status' => 'pending',
                'notes' => $request->notes
            ]);

            // Ajouter les items
            $subtotal = 0;
            foreach ($request->items as $item) {
                $menuItem = MenuItem::find($item['menu_item_id']);
                
                if (!$menuItem || !$menuItem->is_available) {
                    throw new \Exception("{$menuItem->name} n'est pas disponible");
                }

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $item['menu_item_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $menuItem->price,
                    'special_notes' => $item['special_notes'] ?? null,
                    'status' => 'pending'
                ]);

                $subtotal += $menuItem->price * $item['quantity'];
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
                'message' => 'Commande créée avec succès',
                'data' => $order->load('items.menuItem')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    // Mettre à jour une commande
    public function update(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée'
            ], 404);
        }

        // Vérifier si la commande peut être modifiée
        if (!in_array($order->status, ['pending', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande ne peut plus être modifiée'
            ], 400);
        }

        $request->validate([
            'notes' => 'nullable|string|max:500'
        ]);

        $order->update([
            'notes' => $request->notes
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Commande mise à jour',
            'data' => $order
        ]);
    }

    // Changer le statut d'une commande
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,preparing,ready,served,completed,cancelled'
        ]);

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée'
            ], 404);
        }

        // Logique de transition d'états
        $allowedTransitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['preparing', 'cancelled'],
            'preparing' => ['ready', 'cancelled'],
            'ready' => ['served', 'cancelled'],
            'served' => ['completed'],
            'completed' => [],
            'cancelled' => []
        ];

        if (!in_array($request->status, $allowedTransitions[$order->status])) {
            return response()->json([
                'success' => false,
                'message' => 'Transition de statut non autorisée'
            ], 400);
        }

        // Si on confirme la commande, vérifier le stock
        if ($request->status === 'confirmed') {
            $this->checkAndUpdateStock($order);
        }

        // Si on annule, restaurer le stock
        if ($request->status === 'cancelled' && $order->status === 'confirmed') {
            $this->restoreStock($order);
        }

        $order->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour',
            'data' => $order
        ]);
    }

    // Ajouter des items à une commande
    public function addItems(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée'
            ], 404);
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.special_notes' => 'nullable|string|max:255'
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->items as $item) {
                $menuItem = MenuItem::find($item['menu_item_id']);
                
                if (!$menuItem->is_available) {
                    throw new \Exception("{$menuItem->name} n'est pas disponible");
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $item['menu_item_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $menuItem->price,
                    'special_notes' => $item['special_notes'] ?? null,
                    'status' => 'pending'
                ]);
            }

            // Recalculer les totaux
            $this->calculateTotals($order);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Items ajoutés avec succès',
                'data' => $order->fresh()->load('items.menuItem')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    // Retirer un item d'une commande
    public function removeItem($orderId, $itemId)
    {
        $orderItem = OrderItem::where('order_id', $orderId)
            ->where('id', $itemId)
            ->first();

        if (!$orderItem) {
            return response()->json([
                'success' => false,
                'message' => 'Item non trouvé'
            ], 404);
        }

        // Vérifier si la commande peut être modifiée
        $order = $orderItem->order;
        if (!in_array($order->status, ['pending', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande ne peut plus être modifiée'
            ], 400);
        }

        $orderItem->delete();
        
        // Recalculer les totaux
        $this->calculateTotals($order);

        return response()->json([
            'success' => true,
            'message' => 'Item retiré avec succès',
            'data' => $order->fresh()->load('items.menuItem')
        ]);
    }

    // Payer une commande
    public function payOrder(Request $request, $id)
    {
        $request->validate([
            'payment_method' => 'required|in:cash,card,mobile_money'
        ]);

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée'
            ], 404);
        }

        if ($order->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'La commande doit être complétée avant paiement'
            ], 400);
        }

        $order->update([
            'payment_method' => $request->payment_method,
            'payment_status' => 'paid'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Commande payée avec succès',
            'data' => $order
        ]);
    }

    // Générer une facture
    public function generateFacture($id)
    {
        $order = Order::with(['user', 'table', 'server', 'items.menuItem'])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée'
            ], 404);
        }

        $facture = [
            'order_id' => $order->id,
            'table_number' => $order->table->number,
            'date' => $order->created_at->format('Y-m-d'),
            'time' => $order->created_at->format('H:i'),
            'server' => $order->server ? $order->server->name : 'N/A',
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

    // Commandes d'une table spécifique
    public function tableOrders($tableId)
    {
        $orders = Order::with(['user', 'server', 'items.menuItem'])
            ->where('table_id', $tableId)
            ->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready', 'served'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    // Méthodes privées
    private function calculateTotals(Order $order)
    {
        $subtotal = $order->items->sum(function ($item) {
            return $item->unit_price * $item->quantity;
        });
        
        $taxRate = 0.18;
        $tax = $subtotal * $taxRate;
        $total = $subtotal + $tax;
        
        $order->update([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total
        ]);
    }

    private function checkAndUpdateStock(Order $order)
    {
        foreach ($order->items as $item) {
            $menuItem = $item->menuItem;
            
            foreach ($menuItem->ingredients as $ingredient) {
                $quantityNeeded = $ingredient->pivot->quantity_needed * $item->quantity;
                
                if ($ingredient->stock_quantity < $quantityNeeded) {
                    throw new \Exception("Stock insuffisant pour {$ingredient->name}");
                }
                
                $ingredient->decrement('stock_quantity', $quantityNeeded);
                
               
            }
        }
    }

    private function restoreStock(Order $order)
    {
        foreach ($order->items as $item) {
            $menuItem = $item->menuItem;
            
            foreach ($menuItem->ingredients as $ingredient) {
                $quantityNeeded = $ingredient->pivot->quantity_needed * $item->quantity;
                $ingredient->increment('stock_quantity', $quantityNeeded);
            }
        }
    }
}