<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;


Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});


Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
            // Routes Commandes (Serveur et Gérant)
    Route::middleware(['role:server,manager'])->group(function () {
        Route::apiResource('orders', OrderController::class)->except(['destroy']);
        Route::put('orders/{id}/status', [OrderController::class, 'updateStatus']);
        Route::post('orders/{id}/items', [OrderController::class, 'addItems']);
        Route::delete('orders/{id}/items/{itemId}', [OrderController::class, 'removeItem']);
        Route::post('orders/{id}/pay', [OrderController::class, 'payOrder']);
        Route::get('orders/{id}/bill', [OrderController::class, 'generateBill']);
        Route::get('tables/{id}/orders', [OrderController::class, 'tableOrders']);
    });
        // Routes Rapports (Gérant uniquement)
    Route::middleware(['role:manager'])->group(function () {
        Route::get('reports/daily', [ReportController::class, 'dailyReport']);
        Route::get('reports/period', [ReportController::class, 'periodReport']);
        Route::get('reports/history', [ReportController::class, 'reportHistory']);
        Route::get('reports/categories', [ReportController::class, 'categoryReport']);
    });
        
        // Inscription staff (uniquement gérant)
        Route::post('/register/staff', [AuthController::class, 'registerStaff'])
            ->middleware('role:manager');
    });
});


// Tables disponibles (public)
Route::get('/tables/available', [TableController::class, 'available']);

// Catégories (public)
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);

// Tables (Serveur et Gérant)
Route::get('/tables', [TableController::class, 'index'])
        ->middleware(CheckRole::class . ':server,manager');
Route::get('/tables/{id}', [TableController::class, 'show'])
        ->middleware(CheckRole::class . ':server,manager');
    
    // (Gérant )
 Route::middleware(CheckRole::class . ':manager')->group(function () {
        Route::post('/tables', [TableController::class, 'store']);
        Route::put('/tables/{id}', [TableController::class, 'update']);
        Route::delete('/tables/{id}', [TableController::class, 'destroy']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
        });