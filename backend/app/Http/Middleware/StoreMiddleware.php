<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StoreMiddleware
{
    /**
     * Verificar se o usuário tem uma loja ativa
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $store = $request->user()->store;

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Você não possui uma loja.',
            ], 403);
        }

        if (!$store->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Sua loja está inativa ou suspensa.',
            ], 403);
        }

        return $next($request);
    }
}
