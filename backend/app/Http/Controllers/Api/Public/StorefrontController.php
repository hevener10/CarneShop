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
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StorefrontController extends Controller
{
    /**
     * Ver dados públicos da loja
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
     * Listar categorias ativas
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
     * Listar produtos
     */
    public function products(Request $request, string $subdomain): JsonResponse
    {
        $store = Store::active()
            ->where('slug', $subdomain)
            ->firstOrFail();

        $query = Product::where('store_id', $store->id)
            ->active()
            ->with('category', 'variations');

        // Filtrar por categoria
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Buscar
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Em destaque
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
     * Ver produto específico
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

        // Calcular opções de gramatura
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
     * Listar kits
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
     * Listar banners
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
     * Listar bairros de entrega
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
     * Calcular entrega
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

        // Se tiver bairro específico
        if ($validated['neighborhood']) {
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
     * Fazer pedido (checkout)
     */
    public function checkout(Request $request, string $subdomain): JsonResponse
    {
        $store = Store::active()
            ->where('slug', $subdomain)
            ->firstOrFail();

        $validated = $request->validate([
            // Cliente
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_email' => ['nullable', 'email'],

            // Entrega
            'delivery_address' => ['nullable', 'string', 'max:255'],
            'delivery_number' => ['nullable', 'string', 'max:20'],
            'delivery_complement' => ['nullable', 'string', 'max:100'],
            'delivery_neighborhood' => ['nullable', 'string', 'max:100'],
            'delivery_city' => ['nullable', 'string', 'max:100'],
            'delivery_state' => ['nullable', 'string', 'size:2'],
            'delivery_reference' => ['nullable', 'string', 'max:255'],

            // Itens
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.product_name' => ['required', 'string'],
            'items.*.variation_name' => ['nullable', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.gramage' => ['nullable', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.subtotal' => ['required', 'numeric', 'min:0'],
            'items.*.observations' => ['nullable', 'string'],

            // Pagamento
            'payment_method' => ['required', 'in:money,pix,card,transfer'],
            'change_for' => ['nullable', 'numeric', 'min:0'],

            // Entrega
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'neighborhood' => ['nullable', 'string'],
        ]);

        // Calcular entrega
        $deliveryFee = $validated['delivery_fee'] ?? $store->delivery_fee;
        $minimumOrder = $store->minimum_order;

        // Verificar bairro
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

        // Calcular subtotal
        $subtotal = collect($validated['items'])->sum('subtotal');

        // Verificar pedido mínimo
        if ($subtotal < $minimumOrder) {
            return response()->json([
                'success' => false,
                'message' => "Pedido mínimo: R\$ " . number_format($minimumOrder, 2, ',', '.'),
            ], 422);
        }

        // Criar pedido
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
            'total' => $subtotal + $deliveryFee,
            'payment_method' => $validated['payment_method'],
            'change_for' => $validated['change_for'] ?? null,
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        // Criar itens
        foreach ($validated['items'] as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'variation_name' => $item['variation_name'] ?? null,
                'quantity' => $item['quantity'],
                'gramage' => $item['gramage'] ?? null,
                'unit_price' => $item['unit_price'],
                'subtotal' => $item['subtotal'],
                'observations' => $item['observations'] ?? null,
            ]);
        }

        // Gerar mensagem WhatsApp
        $order->load('items');
        $whatsappMessage = $order->generateWhatsappMessage();
        
        // Salvar mensagem
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
}
