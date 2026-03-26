<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Listar produtos da loja
     */
    public function index(Request $request): JsonResponse
    {
        $store = $request->user()->store;
        
        $query = Product::where('store_id', $store->id)
            ->with('category', 'variations');

        // Filtros
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->active === 'true');
        }

        if ($request->has('featured')) {
            $query->where('is_featured', $request->featured === 'true');
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Criar produto
     */
    public function store(Request $request): JsonResponse
    {
        $store = $request->user()->store;

        // Verificar limite do plano
        if (!$store->canAddProduct()) {
            return response()->json([
                'success' => false,
                'message' => 'Limite de produtos do plano atingido.',
            ], 422);
        }

        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'image' => ['nullable', 'string'],
            'images' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'min_gramage' => ['nullable', 'integer', 'min:100'],
            'max_gramage' => ['nullable', 'integer', 'min:100'],
            'gramage_step' => ['nullable', 'integer', 'min:100'],
            'variations' => ['nullable', 'array'],
            'variations.*.name' => ['required', 'string'],
            'variations.*.price_adjust' => ['nullable', 'numeric'],
        ]);

        $validated['store_id'] = $store->id;
        $validated['slug'] = Str::slug($validated['name']);

        // Garantir slug único
        $count = Product::where('store_id', $store->id)
            ->where('slug', 'like', $validated['slug'] . '%')
            ->count();
        if ($count > 0) {
            $validated['slug'] .= '-' . ($count + 1);
        }

        $product = Product::create($validated);

        // Criar variações
        if (!empty($validated['variations'])) {
            foreach ($validated['variations'] as $variation) {
                ProductVariation::create([
                    'product_id' => $product->id,
                    'name' => $variation['name'],
                    'price_adjust' => $variation['price_adjust'] ?? 0,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $product->load('variations', 'category'),
            'message' => 'Produto criado com sucesso',
        ], 201);
    }

    /**
     * Ver produto
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $store = $request->user()->store;
        
        $product = Product::where('store_id', $store->id)
            ->with('category', 'variations', 'allVariations')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * Atualizar produto
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $store = $request->user()->store;
        
        $product = Product::where('store_id', $store->id)->findOrFail($id);

        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'image' => ['nullable', 'string'],
            'images' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'min_gramage' => ['nullable', 'integer', 'min:100'],
            'max_gramage' => ['nullable', 'integer', 'min:100'],
            'gramage_step' => ['nullable', 'integer', 'min:100'],
        ]);

        if (isset($validated['name']) && $validated['name'] !== $product->name) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $product->update($validated);

        return response()->json([
            'success' => true,
            'data' => $product->fresh('variations', 'category'),
            'message' => 'Produto atualizado com sucesso',
        ]);
    }

    /**
     * Deletar produto
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $store = $request->user()->store;
        
        $product = Product::where('store_id', $store->id)->findOrFail($id);

        // Verificar se tem pedidos
        if ($product->orderItems()->count() > 0) {
            // Só desativar ao invés de excluir
            $product->update(['is_active' => false]);
            
            return response()->json([
                'success' => true,
                'message' => 'Produto desativado (possui pedidos associados)',
            ]);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produto excluído com sucesso',
        ]);
    }

    /**
     * Ativar/Desativar produto
     */
    public function toggle(Request $request, int $id): JsonResponse
    {
        $store = $request->user()->store;
        
        $product = Product::where('store_id', $store->id)->findOrFail($id);

        $product->update(['is_active' => !$product->is_active]);

        return response()->json([
            'success' => true,
            'data' => $product,
            'message' => $product->is_active ? 'Produto ativado' : 'Produto desativado',
        ]);
    }

    // Variações
    public function variations(Request $request, int $productId): JsonResponse
    {
        $store = $request->user()->store;
        
        $product = Product::where('store_id', $store->id)->findOrFail($productId);
        
        $variations = $product->allVariations;

        return response()->json([
            'success' => true,
            'data' => $variations,
        ]);
    }

    public function storeVariation(Request $request, int $productId): JsonResponse
    {
        $store = $request->user()->store;
        
        $product = Product::where('store_id', $store->id)->findOrFail($productId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price_adjust' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $variation = $product->allVariations()->create($validated);

        return response()->json([
            'success' => true,
            'data' => $variation,
            'message' => 'Variação criada com sucesso',
        ], 201);
    }

    public function updateVariation(Request $request, int $productId, int $variationId): JsonResponse
    {
        $store = $request->user()->store;
        
        $product = Product::where('store_id', $store->id)->findOrFail($productId);
        $variation = $product->allVariations()->findOrFail($variationId);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'price_adjust' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $variation->update($validated);

        return response()->json([
            'success' => true,
            'data' => $variation,
            'message' => 'Variação atualizada com sucesso',
        ]);
    }

    public function destroyVariation(Request $request, int $productId, int $variationId): JsonResponse
    {
        $store = $request->user()->store;
        
        $product = Product::where('store_id', $store->id)->findOrFail($productId);
        $variation = $product->allVariations()->findOrFail($variationId);

        $variation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Variação excluída com sucesso',
        ]);
    }
}
