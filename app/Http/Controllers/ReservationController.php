<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReservationController extends Controller
{
    // Créer une réservation (client)
    public function store(Request $request)
    {
        $request->validate([
            'table_id' => 'required|exists:tables,id',
            'date' => 'required|date|after:today',
            'time' => 'required|date_format:H:i',
            'guests_count' => 'required|integer|min:1',
            'special_requests' => 'nullable|string|max:500'
        ]);

        // Vérifier que la table est disponible
        $table = Table::find($request->table_id);
        if (!$table) {
            return response()->json(['message' => 'Table non trouvée'], 404);
        }

        // Vérifier la capacité
        if ($request->guests_count > $table->capacity) {
            return response()->json(['message' => 'Nombre de convives supérieur à la capacité de la table'], 400);
        }

        // Vérifier si la table est déjà réservée à cette date/heure
        $existingReservation = Reservation::where('table_id', $request->table_id)
            ->where('date', $request->date)
            ->where('time', $request->time)
            ->whereIn('status', ['pending', 'confirmed'])
            ->first();

        if ($existingReservation) {
            return response()->json(['message' => 'Table déjà réservée à cette date/heure'], 400);
        }

        $reservation = Reservation::create([
            'user_id' => Auth::id(),
            'table_id' => $request->table_id,
            'date' => $request->date,
            'time' => $request->time,
            'guests_count' => $request->guests_count,
            'special_requests' => $request->special_requests,
            'status' => 'pending'
        ]);

        return response()->json([
            'success' => true,
            'data' => $reservation
        ], 201);
    }

    // Mes réservations (client)
    public function myReservations()
    {
        $reservations = Reservation::where('user_id', Auth::id())
            ->with('table')
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reservations
        ]);
    }

    // Changer le statut d'une réservation (serveur/gérant)
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled,completed'
        ]);

        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json(['message' => 'Réservation non trouvée'], 404);
        }

        $reservation->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Statut de la réservation mis à jour',
            'data' => $reservation
        ]);
    }

    // Liste des réservations (serveur/gérant)
    public function index(Request $request)
    {
        $query = Reservation::with(['user', 'table']);

        if ($request->has('date')) {
            $query->where('date', $request->date);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $reservations = $query->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reservations
        ]);
    }

    // Détail d'une réservation (authentifié)
    public function show($id)
    {
        $reservation = Reservation::with(['user', 'table'])->find($id);

        if (!$reservation) {
            return response()->json(['message' => 'Réservation non trouvée'], 404);
        }

        // Vérifier que l'utilisateur a le droit de voir cette réservation
        if (Auth::user()->role === 'client' && $reservation->user_id !== Auth::id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $reservation
        ]);
    }

    // Annuler une réservation (client)
    public function cancel($id)
    {
        $reservation = Reservation::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();

        if (!$reservation) {
            return response()->json(['message' => 'Réservation non trouvée'], 404);
        }

        if ($reservation->status === 'cancelled') {
            return response()->json(['message' => 'Réservation déjà annulée'], 400);
        }

        $reservation->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Réservation annulée avec succès',
            'data' => $reservation
        ]);
    }
}
