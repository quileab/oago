<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class OrderController extends Controller
{
    // Listar pedidos pendientes
    public function listPendingOrders()
    {
        $orders = Order::where('status', 'pending')->with('items.product')->get();
        
        return response()->json($orders, 200);
    }

    // Actualizar el estado de un pedido
    public function updateOrderStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|string|max:20'
        ]);

        $order->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Estado del pedido actualizado con éxito.',
            'order' => $order
        ], 200);
    }

    // Actualizar los productos en un pedido
    public function updateOrderProducts(Request $request, Order $order)
    {
        Log::info("Request: { json_encode($request) }");
        try {
        $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:0',  // Permitir 0 para eliminar
            'products.*.price' => 'required|numeric|min:0'
        ]);
        }
        catch (Exception $e) {
        return response()->json([
            'message' => $e->getMessage(),
        ], 400);
        }
        Log::info("Actualizando productos en el pedido: {$order->id}");

        foreach ($request->products as $productData) {
            $item = $order->items()->where('product_id', $productData['id'])->first();

            if ($item) {
                // Si la cantidad es 0, eliminar el item
                if ($productData['quantity'] === 0) {
                    $item->delete();
                } else {
                    // Si existe, actualizar la cantidad y el precio
                    $item->update([
                        'quantity' => $productData['quantity'],
                        'price' => $productData['price']
                    ]);
                }
            } else {
                // Si no existe, agregar el item al pedido
                if ($productData['quantity'] > 0) {
                    $order->items()->create([
                        'product_id' => $productData['id'],
                        'quantity' => $productData['quantity'],
                        'price' => $productData['price']
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Productos del pedido actualizados con éxito.',
            'order' => $order->load('items.product')
        ], 200);
    }
}
