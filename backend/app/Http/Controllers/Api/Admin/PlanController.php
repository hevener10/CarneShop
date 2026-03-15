<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * Listar planos
     */
    public function index(): JsonResponse
    {
        $plans = Plan::orderBy('order')->get();

        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
    }

    /**
     * Criar plano
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'limit_products' => ['nullable', 'integer', 'min:0'],
            'limit_categories' => ['nullable', 'integer', 'min:0'],
            'has_domain' => ['nullable', 'boolean'],
            'has_api' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $plan = Plan::create($validated);

        return response()->json([
            'success' => true,
            'data' => $plan,
            'message' => 'Plano criado com sucesso',
        ], 201);
    }

    /**
     * Ver plano
     */
    public function show(int $id): JsonResponse
    {
        $plan = Plan::withCount('stores')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $plan,
        ]);
    }

    /**
     * Atualizar plano
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'limit_products' => ['nullable', 'integer', 'min:0'],
            'limit_categories' => ['nullable', 'integer', 'min:0'],
            'has_domain' => ['nullable', 'boolean'],
            'has_api' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $plan->update($validated);

        return response()->json([
            'success' => true,
            'data' => $plan,
            'message' => 'Plano atualizado com sucesso',
        ]);
    }

    /**
     * Deletar plano
     */
    public function destroy(int $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);

        // Verificar se tem lojas usando
        if ($plan->stores()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir plano com lojas associadas.',
            ], 422);
        }

        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Plano excluído com sucesso',
        ]);
    }
}
