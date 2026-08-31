<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    private function deleteImageCache($url)
    {
        if (! $url) {
            return;
        }
        $hash = md5($url);
        $part1 = substr($hash, 0, 2);
        $part2 = substr($hash, 2, 2);
        Storage::disk('public')->delete("image_cache/{$part1}/{$part2}/{$hash}.webp");
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->has('page') || $request->has('per_page')) {
            $perPage = $request->input('per_page', 50);
            $products = Product::paginate($perPage);
        } else {
            $products = Product::all();
        }

        return response()->json($products, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request): JsonResponse
    {
        $originalPrice = $request->input('price');

        $product_exists = Product::find($request->input('id'));
        if ($product_exists instanceof Product) {
            return $this->update($request, $product_exists);
        }

        // Evitar alta de productos con model "consumo interno"
        if (strcasecmp($request->input('model', ''), 'consumo interno') === 0) {
            return response()->json([
                'errors' => [
                    'model' => ['No está permitido dar de alta productos con el modelo "consumo interno".'],
                ],
            ], 422);
        }

        $product = Product::create($request->validated());

        if ($request->has('image_url') && $request->image_url) {
            $this->deleteImageCache($request->image_url);
        }

        $responseData = $product->toArray();
        if (is_null($originalPrice) || (float) $originalPrice == 0) {
            $responseData['warning'] = 'El producto fue registrado con precio 0 o nulo. Recuerde asociar un precio en las listas correspondientes.';
        }

        return response()->json($responseData, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): JsonResponse
    {
        // return proper response if product not found
        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product, 200);
    }

    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $originalPrice = $request->input('price');

        if ($request->has('image_url') && $request->image_url) {
            $this->deleteImageCache($request->image_url);
        }
        if ($product->image_url) {
            $this->deleteImageCache($product->image_url);
        }

        $product->update($request->validated());
        $product->touch();

        $responseData = ['message' => 'OK'];
        if (is_null($originalPrice) || (float) $originalPrice == 0) {
            $responseData['warning'] = 'El producto fue actualizado con precio 0 o nulo. Recuerde asociar un precio en las listas correspondientes.';
        }

        return response()->json($responseData, 200);
    }

    // Eliminar un producto
    public function destroy(Product $product): JsonResponse
    {
        if ($product->image_url) {
            $this->deleteImageCache($product->image_url);
        }
        $product->delete();

        return response()->json(['message' => 'Producto eliminado correctamente'], 200);
    }

    // Subir la imagen de un producto
    public function uploadImage(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            // Eliminar imagen anterior si existe
            if ($product->image_url) {
                Storage::delete($product->image_url);
                $this->deleteImageCache($product->image_url);
            }

            // Almacenar la nueva imagen
            $path = $request->file('image')->store('images/products');
            $product->update(['image_url' => $path]);
            $product->touch();

            return response()->json(['message' => 'Imagen subida correctamente', 'path' => $path], 200);
        }

        return response()->json(['message' => 'No se pudo subir la imagen'], 500);
    }

    public function changeVisibility(Request $request, $product): JsonResponse
    {
        $product = Product::find($product);

        $request->validate([
            'sku' => 'nullable|string|max:50',
            'visibility' => 'required|string|in:visible,catalog,hidden',
        ]);

        if (! $product) {
            if ($request->visibility === 'hidden') {
                return response()->json([
                    'message' => 'El producto no existe, por lo tanto ya se encuentra oculto.',
                ], 200);
            }

            return response()->json(['message' => 'Producto no encontrado'], 404);
        }

        $product->visibility = $request->visibility;
        $product->save();
        $product->touch();

        return response()->json(['message' => 'Visibilidad actualizada correctamente', 'product' => $product], 200);
    }
}
