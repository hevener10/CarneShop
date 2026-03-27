<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    /**
     * Listar todas as lojas (Super Admin).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Store::with(['user', 'plan', 'currentSubscription']);

        // Filtros.
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->has('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        $stores = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $stores,
        ]);
    }

    /**
     * Criar nova loja (Super Admin).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:stores,slug'],
            'plan_id' => ['sometimes', 'exists:plans,id'],
            'whatsapp' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'state' => ['nullable', 'string', 'size:2'],
            'minimum_order' => ['nullable', 'numeric', 'min:0'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Gerar slug automático.
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);

            // Garantir slug único.
            $count = Store::where('slug', 'like', $validated['slug'] . '%')->count();
            if ($count > 0) {
                $validated['slug'] .= '-' . ($count + 1);
            }
        }

        $store = Store::create($validated);

        return response()->json([
            'success' => true,
            'data' => $store->load(['user', 'plan']),
            'message' => 'Loja criada com sucesso',
        ], 201);
    }

    /**
     * Ver detalhes de uma loja.
     */
    public function show(int $id): JsonResponse
    {
        $store = Store::with(['user', 'plan', 'subscriptions', 'currentSubscription'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $store,
        ]);
    }

    /**
     * Atualizar loja.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $store = Store::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:stores,slug,' . $id],
            'plan_id' => ['sometimes', 'exists:plans,id'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'string'],
            'primary_color' => ['nullable', 'string', 'max:7'],
            'whatsapp' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'state' => ['nullable', 'string', 'size:2'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'minimum_order' => ['nullable', 'numeric', 'min:0'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'free_delivery' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $store->update($validated);

        return response()->json([
            'success' => true,
            'data' => $store->fresh(['user', 'plan']),
            'message' => 'Loja atualizada com sucesso',
        ]);
    }

    /**
     * Deletar loja.
     */
    public function destroy(int $id): JsonResponse
    {
        $store = Store::findOrFail($id);

        // Verificar se tem pedidos recentes.
        $recentOrders = $store->orders()->where('created_at', '>=', now()->subDays(30))->count();

        if ($recentOrders > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir loja com pedidos recentes. Considere suspendê-la.',
            ], 422);
        }

        $store->delete();

        return response()->json([
            'success' => true,
            'message' => 'Loja excluída com sucesso',
        ]);
    }

    /**
     * Pausar loja.
     */
    public function pause(int $id): JsonResponse
    {
        $store = Store::findOrFail($id);

        $store->update([
            'is_suspended' => true,
            'suspension_reason' => request('reason', 'Pausada pelo administrador'),
        ]);

        return response()->json([
            'success' => true,
            'data' => $store,
            'message' => 'Loja pausada com sucesso',
        ]);
    }

    /**
     * Ativar loja.
     */
    public function resume(int $id): JsonResponse
    {
        $store = Store::findOrFail($id);

        $store->update([
            'is_suspended' => false,
            'suspension_reason' => null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $store,
            'message' => 'Loja ativada com sucesso',
        ]);
    }

    /**
     * Verificar disponibilidade de slug.
     */
    public function checkSlug(string $slug): JsonResponse
    {
        $exists = Store::where('slug', $slug)->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'available' => !$exists,
                'slug' => $slug,
            ],
        ]);
    }

    // ============================================
    // ROTAS DO DONO DA LOJA
    // ============================================

    /**
     * Minha loja (Store Owner).
     */
    public function me(Request $request): JsonResponse
    {
        $store = $request->user()->store;

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhuma loja encontrada para este usuário.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $store->load(['plan', 'categories', 'neighborhoods']),
        ]);
    }

    /**
     * Atualizar minha loja (Store Owner).
     */
    public function updateMe(Request $request): JsonResponse
    {
        $store = $request->user()->store;

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhuma loja encontrada para este usuário.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'string'],
            'primary_color' => ['nullable', 'string', 'max:7'],
            'whatsapp' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'state' => ['nullable', 'string', 'size:2'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'minimum_order' => ['nullable', 'numeric', 'min:0'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'free_delivery' => ['nullable', 'boolean'],
        ]);

        $store->update($validated);

        return response()->json([
            'success' => true,
            'data' => $store->fresh(),
            'message' => 'Loja atualizada com sucesso',
        ]);
    }
}
