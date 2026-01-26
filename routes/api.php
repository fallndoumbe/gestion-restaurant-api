<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ReportController;

// Public Routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/menu', [MenuController::class, 'index']);
Route::get('/menu/{id}', [MenuController::class, 'show']);
Route::get('/menu/category/{id}', [MenuController::class, 'byCategory']);
Route::get('/tables/available', [TableController::class, 'available']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);


// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    Route::get('/test-auth', function (Request $request) {
        return response()->json([
            'user' => $request->user(),
            'role' => $request->user()->role
        ]);
    });

    // Client-only
    Route::middleware('role:client')->group(function () {
        Route::post('/reservations', [ReservationController::class, 'store']);
        Route::get('/my-reservations', [ReservationController::class, 'myReservations']);
        Route::post('/orders', [OrderController::class, 'store']);

    });

    // Server + Manager
    Route::middleware('role:server,manager')->group(function () {
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);
        Route::post('/orders/{id}/items', [OrderController::class, 'addItems']);
        Route::delete('/orders/{id}/items/{itemId}', [OrderController::class, 'removeItem']);
        Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);// Client peut créer commande
        Route::post('/orders/{id}/pay', [OrderController::class, 'pay']);
        Route::get('/orders/{id}/bill', [OrderController::class, 'bill']);
        Route::get('/tables/{id}/orders', [OrderController::class, 'ordersByTable']);
        Route::put('/reservations/{id}/status', [ReservationController::class, 'updateStatus']);
        Route::put('/menu/{id}/availability', [MenuController::class, 'toggleAvailability']);
        Route::get('/tables', [TableController::class, 'index']);
        Route::get('/tables/{id}', [TableController::class, 'show']);
    });

    // Manager-only
    Route::middleware('role:manager')->group(function () {
        Route::post('/auth/register/staff', [AuthController::class, 'registerStaff']);
        Route::apiResource('menu', MenuController::class)->except(['index', 'show']);
        Route::apiResource('tables', TableController::class)->except(['index']);
        Route::apiResource('ingredients', IngredientController::class);
        Route::get('/reports/daily', [ReportController::class, 'dailyReport']);
        Route::put('/ingredients/{id}/stock', [IngredientController::class, 'updateStock']);
        Route::get('/ingredients/low-stock', [IngredientController::class, 'lowStock']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

});
    });

