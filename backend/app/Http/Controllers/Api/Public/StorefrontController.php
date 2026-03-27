<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Kit;
use App\Models\Neighborhood;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StorefrontController extends Controller
{
    /**
     * Verifica se um slug público está disponível após a normalização.
     */
    public function checkSlug(string $slug): JsonResponse
    {
        $normalizedSlug = Str::slug($slug);

        if ($normalizedSlug === '') {
            throw ValidationException::withMessages([
                'slug' => ['Slug inválido.'],
            ]);
        }

        $exists = Store::where('slug', $normalizedSlug)->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'available' => !$exists,
                'slug' => $normalizedSlug,
            ],
        ]);
    }

    /**
     * Ver dados públicos da loja.
     */
    public function show(string $subdomain): JsonResponse
    {
        $store = Store::active()
            ->where('slug', $subdomain)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'description' => $store->description,
                'logo' => $store->logo,
                'primary_color' => $store->primary_color,
                'whatsapp' => $store->whatsapp,
                'whatsapp_link' => $store->getWhatsappLink(),
                'address' => $store->address,
                'city' => $store->city,
                'state' => $store->state,
                'minimum_order' => $store->minimum_order,
                'delivery_fee' => $store->delivery_fee,
                'free_delivery' => $store->free_delivery,
            ],
        ]);
    }

    /**
     * Listar categorias ativas.
     */
    public function categories(string $subdomain): JsonResponse
    {
        $store = Store::active()
            ->where('slug', $subdomain)
            ->firstOrFail();

        $categories = Category::where('store_id', $store->id)
            ->active()
            ->withCount('activeProducts')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Listar produtos.
     */
    public function products(Request $request, string $subdomain): JsonResponse
    {
        $store = Store::active()
            ->where('slug', $subdomain)
            ->firstOrFail();

        $query = Product::where('store_id', $store->id)
            ->active()
            ->with('category', 'variations');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('featured') && $request->featured === 'true') {
            $query->featured();
        }

        $products = $query->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Ver produto específico.
     */
    public function product(string $subdomain, string $slug): JsonResponse
    {
        $store = Store::active()
            ->where('slug', $subdomain)
            ->firstOrFail();

        $product = Product::where('store_id', $store->id)
            ->active()
            ->with('category', 'variations')
            ->where('slug', $slug)
            ->firstOrFail();

        $gramages = $product->getGramages();

        return response()->json([
            'success' => true,
            'data' => array_merge($product->toArray(), [
                'gramages' => $gramages,
                'final_price' => $product->final_price,
                'has_discount' => $product->has_discount,
            ]),
        ]);
    }

    /**
     * Listar kits.
     */
    public function kits(string $subdomain): JsonResponse
    {
        $store = Store::active()
            ->where('slug', $subdomain)
            ->firstOrFail();

        $kits = Kit::where('store_id', $store->id)
            ->active()
            ->with('items.product')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $kits,
        ]);
    }

    /**
     * Listar banners.
     */
    public function banners(string $subdomain): JsonResponse
    {
        $store = Store::active()
            ->where('slug', $subdomain)
            ->firstOrFail();

        $banners = Banner::where('store_id', $store->id)
            ->active()
            ->orderBy('order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $banners,
        ]);
    }

    /**
     * Listar bairros de entrega.
     */
    public function neighborhoods(string $subdomain): JsonResponse
    {
        $store = Store::active()
            ->where('slug', $subdomain)
            ->firstOrFail();

        $neighborhoods = Neighborhood::where('store_id', $store->id)
            ->active()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $neighborhoods,
        ]);
    }

    /**
     * Calcular entrega.
     */
    public function calculateDelivery(Request $request, string $subdomain): JsonResponse
    {
        $store = Store::active()
            ->where('slug', $subdomain)
            ->firstOrFail();

        $validated = $request->validate([
            'neighborhood' => ['nullable', 'string'],
        ]);

        $deliveryFee = $store->delivery_fee;
        $minimumOrder = $store->minimum_order;

        if (!empty($validated['neighborhood'])) {
            $neighborhood = Neighborhood::where('store_id', $store->id)
                ->active()
                ->where('name', 'like', '%' . $validated['neighborhood'] . '%')
                ->first();

            if ($neighborhood) {
                $deliveryFee = $neighborhood->delivery_fee;
                $minimumOrder = $neighborhood->minimum_order ?? $store->minimum_order;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'delivery_fee' => $deliveryFee,
                'minimum_order' => $minimumOrder,
                'free_delivery' => $store->free_delivery,
            ],
        ]);
    }

    /**
     * Fazer pedido (checkout).
     */
    public function checkout(Request $request, string $subdomain): JsonResponse
    {
        $store = Store::active()
            ->where('slug', $subdomain)
            ->firstOrFail();

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_email' => ['nullable', 'email'],
            'delivery_address' => ['nullable', 'string', 'max:255'],
            'delivery_number' => ['nullable', 'string', 'max:20'],
            'delivery_complement' => ['nullable', 'string', 'max:100'],
            'delivery_neighborhood' => ['nullable', 'string', 'max:100'],
            'delivery_city' => ['nullable', 'string', 'max:100'],
            'delivery_state' => ['nullable', 'string', 'size:2'],
            'delivery_reference' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.variation_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'min:1'],
            'items.*.gramage' => ['nullable', 'integer', 'min:1'],
            'items.*.observations' => ['nullable', 'string'],
            'payment_method' => ['required', 'in:money,pix,card,transfer'],
            'change_for' => ['nullable', 'numeric', 'min:0'],
            // Compatibilidade com payloads antigos. O backend ignora esses valores.
            'items.*.product_name' => ['nullable', 'string'],
            'items.*.variation_name' => ['nullable', 'string'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.subtotal' => ['nullable', 'numeric', 'min:0'],
        ]);

        $deliveryFee = $store->delivery_fee;
        $minimumOrder = $store->minimum_order;

        if (!empty($validated['delivery_neighborhood'])) {
            $neighborhood = Neighborhood::where('store_id', $store->id)
                ->active()
                ->where('name', 'like', '%' . $validated['delivery_neighborhood'] . '%')
                ->first();

            if ($neighborhood) {
                $deliveryFee = $neighborhood->delivery_fee;
                $minimumOrder = $neighborhood->minimum_order ?? $store->minimum_order;
            }
        }

        $resolvedItems = $this->resolveCheckoutItems($store, $validated['items']);
        $subtotal = round($resolvedItems->sum('subtotal'), 2);

        if ($subtotal < $minimumOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido mínimo: R$ ' . number_format((float) $minimumOrder, 2, ',', '.'),
            ], 422);
        }

        $order = Order::create([
            'store_id' => $store->id,
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'customer_email' => $validated['customer_email'] ?? null,
            'delivery_address' => $validated['delivery_address'] ?? null,
            'delivery_number' => $validated['delivery_number'] ?? null,
            'delivery_complement' => $validated['delivery_complement'] ?? null,
            'delivery_neighborhood' => $validated['delivery_neighborhood'] ?? null,
            'delivery_city' => $validated['delivery_city'] ?? $store->city,
            'delivery_state' => $validated['delivery_state'] ?? $store->state,
            'delivery_reference' => $validated['delivery_reference'] ?? null,
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'discount' => 0,
            'total' => round($subtotal + (float) $deliveryFee, 2),
            'payment_method' => $validated['payment_method'],
            'change_for' => $validated['change_for'] ?? null,
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        foreach ($resolvedItems as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'variation_name' => $item['variation_name'],
                'quantity' => $item['quantity'],
                'gramage' => $item['gramage'],
                'unit_price' => $item['unit_price'],
                'subtotal' => $item['subtotal'],
                'observations' => $item['observations'],
            ]);
        }

        $order->load('items');
        $whatsappMessage = $order->generateWhatsappMessage();
        $order->update(['whatsapp_message' => $whatsappMessage]);

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $order,
                'whatsapp_message' => $whatsappMessage,
                'whatsapp_link' => $store->getWhatsappLink()
                    ? $store->getWhatsappLink() . '?text=' . rawurlencode($whatsappMessage)
                    : null,
            ],
            'message' => 'Pedido realizado com sucesso!',
        ], 201);
    }

    /**
     * Resolve os itens do checkout usando apenas dados válidos da loja.
     */
    private function resolveCheckoutItems(Store $store, array $rawItems): Collection
    {
        $productIds = collect($rawItems)
            ->pluck('product_id')
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values();

        $products = Product::where('store_id', $store->id)
            ->active()
            ->with(['allVariations' => fn ($query) => $query->active()])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        return collect($rawItems)->map(function (array $item, int $index) use ($products) {
            $product = $products->get((int) $item['product_id']);

            if (!$product) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => ['Produto inválido para esta loja.'],
                ]);
            }

            $variation = $this->resolveVariation($product, $item, $index);
            $gramage = $this->resolveGramage($product, $item, $index);
            $quantity = (float) $item['quantity'];
            $unitPrice = $this->calculateUnitPrice($product, $variation, $gramage);

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'variation_name' => $variation?->name,
                'quantity' => $quantity,
                'gramage' => $gramage,
                'unit_price' => round($unitPrice, 2),
                'subtotal' => round($unitPrice * $quantity, 2),
                'observations' => $item['observations'] ?? null,
            ];
        });
    }

    /**
     * Resolve a variação ativa informada no item do checkout.
     */
    private function resolveVariation(Product $product, array $item, int $index): ?ProductVariation
    {
        if (empty($item['variation_id'])) {
            return null;
        }

        $variation = $product->allVariations->firstWhere('id', (int) $item['variation_id']);

        if (!$variation) {
            throw ValidationException::withMessages([
                "items.{$index}.variation_id" => ['Variação inválida para este produto.'],
            ]);
        }

        return $variation;
    }

    /**
     * Resolve a gramatura do item e protege o checkout de produtos mal configurados.
     */
    private function resolveGramage(Product $product, array $item, int $index): int
    {
        if (
            $product->min_gramage === null
            || $product->max_gramage === null
            || $product->gramage_step === null
            || $product->gramage_step <= 0
        ) {
            throw ValidationException::withMessages([
                "items.{$index}.gramage" => ['Produto sem configuração de gramatura válida.'],
            ]);
        }

        $gramage = (int) ($item['gramage'] ?? $product->min_gramage);

        if ($gramage < $product->min_gramage || $gramage > $product->max_gramage) {
            throw ValidationException::withMessages([
                "items.{$index}.gramage" => ['Gramatura fora do intervalo permitido para este produto.'],
            ]);
        }

        if ((($gramage - $product->min_gramage) % $product->gramage_step) !== 0) {
            throw ValidationException::withMessages([
                "items.{$index}.gramage" => ['Gramatura inválida para o step configurado deste produto.'],
            ]);
        }

        return $gramage;
    }

    /**
     * Calcula o preço unitário do item com base em gramatura e variação.
     */
    private function calculateUnitPrice(Product $product, ?ProductVariation $variation, int $gramage): float
    {
        $basePrice = (float) ($product->discount_price ?? $product->price);
        $variationAdjust = (float) ($variation?->price_adjust ?? 0);
        $pricePerGram = ($basePrice + $variationAdjust) / 1000;

        return $pricePerGram * $gramage;
    }
}
