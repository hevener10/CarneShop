<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Listar pedidos da loja
     */
    public function index(Request $request): JsonResponse
    {
        $store = $request->user()->store;
        
        $query = Order::where('store_id', $store->id)
            ->with('items');

        // Filtros
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Ver detalhes do pedido
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $store = $request->user()->store;
        
        $order = Order::where('store_id', $store->id)
            ->with('items.product')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * Atualizar status do pedido
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $store = $request->user()->store;
        
        $order = Order::where('store_id', $store->id)->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,preparing,ready,dispatched,canceled'],
            'admin_notes' => ['nullable', 'string'],
            'cancellation_reason' => ['nullable', 'string'],
        ]);

        // Validar transição de status
        if ($validated['status'] === 'canceled' && !$order->canBeCanceled()) {
            return response()->json([
                'success' => false,
                'message' => 'Este pedido não pode ser cancelado.',
            ], 422);
        }

        if ($validated['status'] === 'canceled' && empty($validated['cancellation_reason'])) {
            $validated['cancellation_reason'] = 'Cancelado pelo estabelecimento';
        }

        $order->update($validated);

        return response()->json([
            'success' => true,
            'data' => $order->fresh(),
            'message' => 'Status atualizado com sucesso',
        ]);
    }

    /**
     * Atualizar pedido (ex: ajuste de peso)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $store = $request->user()->store;
        
        $order = Order::where('store_id', $store->id)->findOrFail($id);

        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Recalcular total se houver mudanças
        if (isset($validated['delivery_fee']) || isset($validated['discount'])) {
            $order->delivery_fee = $validated['delivery_fee'] ?? $order->delivery_fee;
            $order->discount = $validated['discount'] ?? $order->discount;
            $order->total = $order->calculateTotal();
        }

        $order->update($validated);

        return response()->json([
            'success' => true,
            'data' => $order->fresh(),
            'message' => 'Pedido atualizado com sucesso',
        ]);
    }

    /**
     * Estatísticas de pedidos
     */
    public function stats(Request $request): JsonResponse
    {
        $store = $request->user()->store;
        
        $today = now()->startOfDay();
        $week = now()->startOfWeek();
        $month = now()->startOfMonth();

        // Hoje
        $todayOrders = Order::where('store_id', $store->id)
            ->whereDate('created_at', $today)
            ->whereNotIn('status', ['canceled'])
            ->count();

        $todayRevenue = Order::where('store_id', $store->id)
            ->whereDate('created_at', $today)
            ->whereNotIn('status', ['canceled'])
            ->sum('total');

        // Esta semana
        $weekOrders = Order::where('store_id', $store->id)
            ->whereDate('created_at', '>=', $week)
            ->whereNotIn('status', ['canceled'])
            ->count();

        $weekRevenue = Order::where('store_id', $store->id)
            ->whereDate('created_at', '>=', $week)
            ->whereNotIn('status', ['canceled'])
            ->sum('total');

        // Este mês
        $monthOrders = Order::where('store_id', $store->id)
            ->whereDate('created_at', '>=', $month)
            ->whereNotIn('status', ['canceled'])
            ->count();

        $monthRevenue = Order::where('store_id', $store->id)
            ->whereDate('created_at', '>=', $month)
            ->whereNotIn('status', ['canceled'])
            ->sum('total');

        // Pendentes
        $pendingOrders = Order::where('store_id', $store->id)
            ->where('status', 'pending')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'today' => [
                    'orders' => $todayOrders,
                    'revenue' => $todayRevenue,
                ],
                'week' => [
                    'orders' => $weekOrders,
                    'revenue' => $weekRevenue,
                ],
                'month' => [
                    'orders' => $monthOrders,
                    'revenue' => $monthRevenue,
                ],
                'pending' => $pendingOrders,
            ],
        ]);
    }

    /**
     * Gerar mensagem WhatsApp do pedido
     */
    public function whatsappMessage(Request $request, int $id): JsonResponse
    {
        $store = $request->user()->store;
        
        $order = Order::where('store_id', $store->id)
            ->with('items')
            ->findOrFail($id);

        $message = $order->generateWhatsappMessage();
        
        // Codificar para URL
        $whatsappUrl = $store->getWhatsappLink();
        $encodedMessage = rawurlencode($message);

        return response()->json([
            'success' => true,
            'data' => [
                'message' => $message,
                'whatsapp_url' => $whatsappUrl ? "{$whatsappUrl}?text={$encodedMessage}" : null,
            ],
        ]);
    }
}
