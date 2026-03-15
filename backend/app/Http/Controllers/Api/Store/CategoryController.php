<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Listar categorias da loja
     */
    public function index(Request $request): JsonResponse
    {
        $store = $request->user()->store;
        
        $categories = Category::where('store_id', $store->id)
            ->when($request->has('active'), function ($query) use ($request) {
                return $query->where('is_active', $request->active === 'true');
            })
            ->orderBy('order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Criar categoria
     */
    public function store(Request $request): JsonResponse
    {
        $store = $request->user()->store;

        // Verificar limite do plano
        if (!$store->canAddCategory()) {
            return response()->json([
                'success' => false,
                'message' => 'Limite de categorias do plano atingido.',
            ], 422);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['store_id'] = $store->id;

        $category = Category::create($validated);

        return response()->json([
            'success' => true,
            'data' => $category,
            'message' => 'Categoria criada com sucesso',
        ], 201);
    }

    /**
     * Ver categoria
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $store = $request->user()->store;
        
        $category = Category::where('store_id', $store->id)
            ->with('products')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $category,
        ]);
    }

    /**
     * Atualizar categoria
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $store = $request->user()->store;
        
        $category = Category::where('store_id', $store->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update($validated);

        return response()->json([
            'success' => true,
            'data' => $category,
            'message' => 'Categoria atualizada com sucesso',
        ]);
    }

    /**
     * Deletar categoria
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $store = $request->user()->store;
        
        $category = Category::where('store_id', $store->id)->findOrFail($id);

        // Verificar se tem produtos
        if ($category->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir categoria com produtos.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Categoria excluída com sucesso',
        ]);
    }

    /**
     * Reordenar categorias
     */
    public function reorder(Request $request): JsonResponse
    {
        $store = $request->user()->store;

        $validated = $request->validate([
            'categories' => ['required', 'array'],
            'categories.*.id' => ['required', 'integer'],
            'categories.*.order' => ['required', 'integer'],
        ]);

        foreach ($validated['categories'] as $cat) {
            Category::where('store_id', $store->id)
                ->where('id', $cat['id'])
                ->update(['order' => $cat['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Categorias reordenadas com sucesso',
        ]);
    }
}
