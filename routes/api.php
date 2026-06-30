<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Shared routes (caissier + admin)
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/suppliers', [SupplierController::class, 'index']);
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Sales (caissier + admin)
    Route::post('/sales', [SaleController::class, 'store']);
    Route::post('/sales/batch', [SaleController::class, 'storeBatch']);
    Route::get('/sales/history', [SaleController::class, 'history']);
    Route::get('/sales/{sale}', [SaleController::class, 'show']);

    // Admin only routes
    Route::middleware('role:admin')->group(function () {
        // Products management
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);

        // Stock management
        Route::post('/stock/add-depot', [StockController::class, 'addDepot']);
        Route::post('/stock/transfer-to-boutique', [StockController::class, 'transferToBoutique']);
        Route::post('/stock/initial-boutique', [StockController::class, 'setInitialBoutiqueStock']);
        Route::get('/stock/movements', [StockController::class, 'movements']);
        Route::get('/stock/pending-payments', [StockController::class, 'pendingPayments']);
        Route::post('/stock/movements/{movement}/mark-paid', [StockController::class, 'markPaid']);

        // Categories
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        // Suppliers
        Route::post('/suppliers', [SupplierController::class, 'store']);
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update']);
        Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy']);

        // Webhooks
        Route::get('/webhooks', [WebhookController::class, 'index']);
        Route::post('/webhooks', [WebhookController::class, 'store']);
        Route::put('/webhooks/{webhookConfig}', [WebhookController::class, 'update']);
        Route::delete('/webhooks/{webhookConfig}', [WebhookController::class, 'destroy']);

        // Users
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });
});
