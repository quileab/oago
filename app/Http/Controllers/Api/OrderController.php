<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /**
     * Listar pedidos pendientes.
     *
     * @return JsonResponse<array<int, mixed>>
     */
    public function listPendingOrders(Request $request): JsonResponse
    {
        $orders = Order::with(['user', 'items.product', 'shipping'])->where('status', OrderStatus::PENDING)->get();

        $filteredOrders = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'user' => $order->user,
                'items' => $order->items,
                'shipping' => $order->shipping,
                'total_price' => $order->total_price,
                'status' => $order->status,
                'created_at' => $order->created_at,
            ];
        });

        return response()->json($filteredOrders, 200);
    }

    /**
     * Actualizar el estado de un pedido.
     *
     * @return array{message: string}
     */
    public function updateOrderStatus(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
        ]);

        $order->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Estado del pedido '.$order->id.' => '.$request->status.': OK',
        ], 200);
    }

    /**
     * Actualizar los productos en un pedido.
     */
    public function updateOrderProducts(Request $request, Order $order): JsonResponse
    {
        Log::info("Request: { json_encode($request) }");
        try {
            $request->validate([
                'items' => 'required|array',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.price' => 'required|numeric|min:0',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error de validación: '.$e->getMessage(),
            ], 400);
        }

        try {
            DB::transaction(function () use ($request, $order) {
                $order->items()->delete();

                $total = 0;
                $productIds = array_column($request->items, 'product_id');
                $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

                foreach ($request->items as $item) {
                    $order->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                    ]);

                    $product = $products->get($item['product_id']);
                    $orderedQuantity = (int) $item['quantity'];
                    $billableQuantity = $orderedQuantity;

                    if ($product && $product->hasBonus() && $product->bonus_threshold > 0) {
                        $bonusThreshold = $product->bonus_threshold + $product->bonus_amount;
                        $timesBonusApplies = floor($orderedQuantity / $bonusThreshold);
                        $freeUnits = $timesBonusApplies * $product->bonus_amount;
                        $billableQuantity = $orderedQuantity - $freeUnits;
                    }

                    $total += (float) $item['price'] * $billableQuantity;
                }

                $order->update(['total_price' => $total]);
            });

            return response()->json([
                'message' => 'Productos del pedido '.$order->id.' actualizados: OK',
                'order' => $order->load('items.product'),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error actualizando productos de orden API: '.$e->getMessage());

            return response()->json([
                'message' => 'Error interno al actualizar el pedido.',
            ], 500);
        }
    }
}
