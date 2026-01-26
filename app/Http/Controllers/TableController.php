<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Table;
use Illuminate\Http\Request;

class TableController extends Controller
{
    // Liste des tables
    public function index()
    {
        try {
            $tables = Table::all();

            return response()->json([
                'success' => true,
                'data' => $tables
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

    // Créer une table
    public function store(Request $request)
    {
        try {
            // Validation
            $validated = $request->validate([
                'number' => 'required|integer|unique:tables',
                'capacity' => 'required|integer|min:1',
                'location' => 'nullable|string|max:255'
            ]);

            // Création
            $table = Table::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Table créée avec succès',
                'data' => $table
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

    // Voir une table
    public function show($id)
    {
        try {
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

    // Modifier une table
    public function update(Request $request, $id)
    {
        try {
            $table = Table::find($id);

            if (!$table) {
                return response()->json([
                    'success' => false,
                    'message' => 'Table non trouvée'
                ], 404);
            }

            $validated = $request->validate([
                'number' => 'sometimes|integer|unique:tables,number,' . $id,
                'capacity' => 'sometimes|integer|min:1',
                'location' => 'nullable|string|max:255'
            ]);

            $table->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Table modifiée avec succès',
                'data' => $table
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

    // Supprimer une table
    public function destroy($id)
    {
        try {
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

    // Tables disponibles
    public function available()
    {
        try {
            $tables = Table::all(); // Pour simplifier, retourne toutes les tables

            return response()->json([
                'success' => true,
                'data' => $tables
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }
}
