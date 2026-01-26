<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
    // Liste des ingrédients (gérant)
    public function index()
    {
        $ingredients = Ingredient::all();

        return response()->json([
            'success' => true,
            'data' => $ingredients
        ]);
    }

    // Détail d'un ingrédient (gérant)
    public function show($id)
    {
        $ingredient = Ingredient::find($id);

        if (!$ingredient) {
            return response()->json(['message' => 'Ingrédient non trouvé'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $ingredient
        ]);
    }

    // Créer un ingrédient (gérant)
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:ingredients',
            'unit' => 'required|string|max:50',
            'stock_quantity' => 'required|numeric|min:0',
            'min_stock' => 'required|numeric|min:0',
            'cost_per_unit' => 'required|numeric|min:0'
        ]);

        $ingredient = Ingredient::create([
            'name' => $request->name,
            'unit' => $request->unit,
            'stock_quantity' => $request->stock_quantity,
            'min_stock' => $request->min_stock,
            'cost_per_unit' => $request->cost_per_unit
        ]);

        return response()->json([
            'success' => true,
            'data' => $ingredient
        ], 201);
    }

    // Modifier un ingrédient (gérant)
    public function update(Request $request, $id)
    {
        $ingredient = Ingredient::find($id);

        if (!$ingredient) {
            return response()->json(['message' => 'Ingrédient non trouvé'], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255|unique:ingredients,name,' . $id,
            'unit' => 'sometimes|string|max:50',
            'stock_quantity' => 'sometimes|numeric|min:0',
            'min_stock' => 'sometimes|numeric|min:0',
            'cost_per_unit' => 'sometimes|numeric|min:0'
        ]);

        $ingredient->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $ingredient
        ]);
    }

    // Supprimer un ingrédient (gérant)
    public function destroy($id)
    {
        $ingredient = Ingredient::find($id);

        if (!$ingredient) {
            return response()->json(['message' => 'Ingrédient non trouvé'], 404);
        }

        $ingredient->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ingrédient supprimé avec succès'
        ]);
    }

    // Mettre à jour le stock (gérant)
    public function updateStock(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:0',
            'operation' => 'required|in:add,subtract,set'
        ]);

        $ingredient = Ingredient::find($id);

        if (!$ingredient) {
            return response()->json(['message' => 'Ingrédient non trouvé'], 404);
        }

        $newQuantity = $ingredient->stock_quantity;

        switch ($request->operation) {
            case 'add':
                $newQuantity += $request->quantity;
                break;
            case 'subtract':
                $newQuantity -= $request->quantity;
                if ($newQuantity < 0) $newQuantity = 0;
                break;
            case 'set':
                $newQuantity = $request->quantity;
                break;
        }

        $ingredient->update(['stock_quantity' => $newQuantity]);

        return response()->json([
            'success' => true,
            'message' => 'Stock mis à jour',
            'data' => $ingredient
        ]);
    }

    // Ingrédients en rupture de stock (gérant)
    public function lowStock()
    {
        $ingredients = Ingredient::where('stock_quantity', '<=', DB::raw('min_stock'))->get();

        return response()->json([
            'success' => true,
            'data' => $ingredients
        ]);
    }
}
