<?php

use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\PlanController;
use App\Http\Controllers\Api\Admin\StoreController;
use App\Http\Controllers\Api\Public\StorefrontController;
use App\Http\Controllers\Api\Store\CategoryController;
use App\Http\Controllers\Api\Store\OrderController;
use App\Http\Controllers\Api\Store\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Versão 1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    
    // ============================================
    // ROTAS PÚBLICAS (Sem autenticação)
    // ============================================
    Route::prefix('public')->group(function () {
        // Verificar disponibilidade de slug
        Route::get('/stores/check-slug/{slug}', [StoreController::class, 'checkSlug']);

        // Loja pública (subdomain)
        Route::prefix('stores/{subdomain}')->group(function () {
            Route::get('/', [StorefrontController::class, 'show']);
            Route::get('/categories', [StorefrontController::class, 'categories']);
            Route::get('/products', [StorefrontController::class, 'products']);
            Route::get('/products/{slug}', [StorefrontController::class, 'product']);
            Route::get('/kits', [StorefrontController::class, 'kits']);
            Route::get('/banners', [StorefrontController::class, 'banners']);
            Route::get('/neighborhoods', [StorefrontController::class, 'neighborhoods']);
            Route::post('/delivery/calculate', [StorefrontController::class, 'calculateDelivery']);
            Route::post('/checkout', [StorefrontController::class, 'checkout']);
        });
    });

    // ============================================
    // ROTAS DE AUTENTICAÇÃO
    // ============================================
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        
        // Rotas autenticadas
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::put('/profile', [AuthController::class, 'updateProfile']);
            Route::put('/password', [AuthController::class, 'changePassword']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    // ============================================
    // ROTAS DO SUPER ADMIN
    // ============================================
    Route::middleware(['auth:sanctum', 'role:super_admin'])->prefix('admin')->group(function () {
        
        // Gestão de Lojas
        Route::apiResource('stores', StoreController::class);
        Route::post('/stores/{id}/pause', [StoreController::class, 'pause']);
        Route::post('/stores/{id}/resume', [StoreController::class, 'resume']);
        
        // Gestão de Planos
        Route::apiResource('plans', PlanController::class);
    });

    // ============================================
    // ROTAS DO DONO DA LOJA (Store Owner)
    // ============================================
    Route::middleware(['auth:sanctum', 'role:store_owner', 'store'])->prefix('stores')->group(function () {
        
        // Minha loja
        Route::get('/me', [StoreController::class, 'me']);
        Route::put('/me', [StoreController::class, 'updateMe']);
        
        // Categorias
        Route::apiResource('categories', CategoryController::class);
        Route::post('/categories/reorder', [CategoryController::class, 'reorder']);
        
        // Produtos
        Route::apiResource('products', ProductController::class);
        Route::post('/products/{product}/toggle', [ProductController::class, 'toggle']);
        
        // Variações de produto
        Route::get('/products/{product}/variations', [ProductController::class, 'variations']);
        Route::post('/products/{product}/variations', [ProductController::class, 'storeVariation']);
        Route::put('/products/{product}/variations/{variation}', [ProductController::class, 'updateVariation']);
        Route::delete('/products/{product}/variations/{variation}', [ProductController::class, 'destroyVariation']);

        // Pedidos
        Route::apiResource('orders', OrderController::class);
        Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']);
        Route::get('/orders/{order}/whatsapp', [OrderController::class, 'whatsappMessage']);
        Route::get('/stats', [OrderController::class, 'stats']);
    });
});
