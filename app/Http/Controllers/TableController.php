<?php

namespace App\Http\Controllers;

use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TableController extends Controller
{
    
     //Liste de toutes les tables
     
    public function index()
    {
        $tables = Table::all();

        return response()->json([
            'success' => true,
            'data' => $tables
        ]);
    }

    
     //Détails d'une table
     
    public function show($id)
    {
        $table = Table::find($id);

        if (!$table) {
            return response()->json([
                'success' => false,
                'message' => 'Table non trouvée'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $table
        ]);
    }

    
     //Créer une nouvelle table (Gérant uniquement)
     
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required|integer|unique:tables,number',
            'capacity' => 'required|integer|min:1',
            'location' => 'nullable|string|in:intérieur,terrasse,salon privé',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $table = Table::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Table créée avec succès',
            'data' => $table
        ], 201);
    }

    
     //Modifier une table (Gérant uniquement)
     
    public function update(Request $request, $id)
    {
        $table = Table::find($id);

        if (!$table) {
            return response()->json([
                'success' => false,
                'message' => 'Table non trouvée'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'number' => 'sometimes|integer|unique:tables,number,' . $id,
            'capacity' => 'sometimes|integer|min:1',
            'location' => 'nullable|string|in:intérieur,terrasse,salon privé',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $table->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Table mise à jour avec succès',
            'data' => $table
        ]);
    }

    
     // Supprimer une table (Gérant uniquement)
     
    public function destroy($id)
    {
        $table = Table::find($id);

        if (!$table) {
            return response()->json([
                'success' => false,
                'message' => 'Table non trouvée'
            ], 404);
        }

        $table->delete();

        return response()->json([
            'success' => true,
            'message' => 'Table supprimée avec succès'
        ]);
    }

    
     // Tables disponibles (Public)
     
    public function available()
    {
        $tables = Table::where('status', 'libre')->get();

        return response()->json([
            'success' => true,
            'data' => $tables
        ]);
    }
}
