<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Dedoc\Scramble\Attributes\Response;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Listar pedidos pendientes.
     */
    #[Response(status: 200, type: 'App\Models\Order[]')]
    public function listPendingOrders(): JsonResponse
    {
        $orders = Order::where('status', 'pending')->with('items.product')->get();

        $filteredOrders = $orders->map(function ($order) {
            $order->items = $order->items->filter(function ($item) {
                return $item->quantity > 0 && $item->price > 0;
            })->values(); // Re-index the collection after filtering

            return $order;
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
            'status' => 'required|string|max:20',
        ]);

        $order->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Estado del pedido '.$order->id.' => '.$request->status.': OK',
        ], 200);
    }

    /**
     * Actualizar los productos en un pedido.
     */
    #[Response(status: 200, type: 'App\Models\Order')]
    public function updateOrderProducts(Request $request, Order $order): JsonResponse
    {
        Log::info("Request: { json_encode($request) }");
        try {
            $request->validate([
                'products' => 'required|array',
                'products.*.id' => 'required|exists:products,id',
                'products.*.quantity' => 'required|integer|min:0',  // Permitir 0 para eliminar
                'products.*.price' => 'required|numeric|min:0',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
        Log::info("Actualizando productos en el pedido: {$order->id}");

        $existingItems = $order->items()->get()->keyBy('product_id');

        foreach ($request->products as $productData) {
            $item = $existingItems->get($productData['id']);

            if ($item) {
                // Si la cantidad es 0, eliminar el item
                if ($productData['quantity'] === 0) {
                    $item->delete();
                } else {
                    // Si existe, actualizar la cantidad y el precio
                    $item->update([
                        'quantity' => $productData['quantity'],
                        'price' => $productData['price'],
                    ]);
                }
            } else {
                // Si no existe, agregar el item al pedido
                if ($productData['quantity'] > 0) {
                    $order->items()->create([
                        'product_id' => $productData['id'],
                        'quantity' => $productData['quantity'],
                        'price' => $productData['price'],
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Productos del pedido actualizados con éxito.',
            'order' => $order->load('items.product'),
        ], 200);
    }
}
