<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Category;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    // Liste des plats (public)
    public function index(Request $request)
    {
        $query = MenuItem::with('category');

        // Filtres
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('available') && $request->available == 'true') {
            $query->where('is_available', true);
        }

        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $menuItems = $query->get();

        return response()->json([
            'success' => true,
            'data' => $menuItems
        ]);
    }

    // Détail d'un plat (public)
    public function show($id)
    {
        $menuItem = MenuItem::with(['category', 'ingredients'])->find($id);

        if (!$menuItem) {
            return response()->json(['message' => 'Plat non trouvé'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $menuItem
        ]);
    }

    // Créer un plat (gérant seulement)
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'preparation_time' => 'required|integer|min:1',
            'is_available' => 'boolean',
            'image' => 'nullable|string',
            'ingredients' => 'nullable|array'
        ]);

        $menuItem = MenuItem::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'category_id' => $request->category_id,
            'preparation_time' => $request->preparation_time,
            'is_available' => $request->is_available ?? true,
            'image' => $request->image,
        ]);

        // Ajouter les ingrédients si fournis
        if ($request->has('ingredients')) {
            foreach ($request->ingredients as $ingredient) {
                $menuItem->ingredients()->attach($ingredient['ingredient_id'], [
                    'quantity_needed' => $ingredient['quantity_needed']
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $menuItem->load('ingredients')
        ], 201);
    }

    // Modifier un plat (gérant seulement)
    public function update(Request $request, $id)
    {
        $menuItem = MenuItem::find($id);

        if (!$menuItem) {
            return response()->json(['message' => 'Plat non trouvé'], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'category_id' => 'sometimes|exists:categories,id',
            'preparation_time' => 'sometimes|integer|min:1',
            'is_available' => 'sometimes|boolean',
            'image' => 'nullable|string'
        ]);

        $menuItem->update($request->only([
            'name', 'description', 'price', 'category_id',
            'preparation_time', 'is_available', 'image'
        ]));

        return response()->json([
            'success' => true,
            'data' => $menuItem
        ]);
    }

    // Supprimer un plat (gérant seulement)
    public function destroy($id)
    {
        $menuItem = MenuItem::find($id);

        if (!$menuItem) {
            return response()->json(['message' => 'Plat non trouvé'], 404);
        }

        $menuItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Plat supprimé avec succès'
        ]);
    }

    // Changer disponibilité (gérant/serveur)
    public function toggleAvailability(Request $request, $id)
    {
        $request->validate([
            'is_available' => 'required|boolean'
        ]);

        $menuItem = MenuItem::find($id);

        if (!$menuItem) {
            return response()->json(['message' => 'Plat non trouvé'], 404);
        }

        $menuItem->update(['is_available' => $request->is_available]);

        return response()->json([
            'success' => true,
            'message' => 'Disponibilité mise à jour',
            'data' => $menuItem
        ]);
    }

    // Plats par catégorie (public)
    public function byCategory($categoryId)
    {
        $menuItems = MenuItem::where('category_id', $categoryId)
            ->where('is_available', true)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $menuItems
        ]);
    }
}
