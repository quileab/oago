<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ProductRequest;
use App\Models\Product;
use App\Services\ImageImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
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
            $products = Product::with('tags')->paginate($perPage);
        } else {
            $products = Product::with('tags')->get();
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

        $attributes = Arr::except($request->validated(), 'tags');

        $product = Product::create($attributes);

        if ($request->hasTagsInput()) {
            $product->syncTagsById($request->validated()['tags'] ?? []);
        }

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

        $attributes = Arr::except($request->validated(), 'tags');

        if ($attributes) {
            $product->update($attributes);
        }

        // Only sync when the caller actually sent the field, so an omitted
        // `tags` never wipes the product's existing tags.
        if ($request->hasTagsInput()) {
            $product->syncTagsById($request->validated()['tags'] ?? []);
        }

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

    // Subir la imagen principal de un producto en formato WebP bajo image_cache
    public function uploadImage(Request $request, Product $product, ImageImportService $importService): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
        ]);

        if ($request->hasFile('image')) {
            $result = $importService->processSingleImage(
                filePath: $request->file('image')->getRealPath(),
                productId: $product->id,
                imageIndex: 1,
                overwrite: true
            );

            $product->touch();

            return response()->json([
                'message' => 'Imagen subida correctamente',
                'path' => $result['public_url'] ?? $product->fresh()->image_url,
            ], 200);
        }

        return response()->json(['message' => 'No se pudo subir la imagen'], 500);
    }

    /**
     * Subir múltiples imágenes para la galería de un producto.
     *
     * Almacena las imágenes en `storage/app/public/image_cache/{p1}/{p2}/`,
     * purga la caché de medios `product_media_{id}` y la caché de optimización de imágenes.
     *
     * @response array{message: string, product_id: int, uploaded_count: int, files: array<int, string>, media: array<int, array<string, mixed>>}
     */
    public function uploadMedia(Request $request, Product $product, ImageImportService $importService): JsonResponse
    {
        $request->validate([
            /** Lista de archivos de imagen para la galería del producto. */
            'images' => 'required|array|min:1',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240',
            /** Si es verdadero, elimina todas las imágenes anteriores de la galería antes de agregar las nuevas. */
            'clean_previous' => 'nullable|boolean',
            /** Si es verdadero, establece la primera imagen subida como la imagen de portada principal del producto. */
            'set_first_as_cover' => 'nullable|boolean',
        ]);

        // Si se solicita limpiar las anteriores, se borran los archivos extras en image_cache
        if ($request->boolean('clean_previous')) {
            $importService->deleteProductMedia($product->id, includeMain: false);
        }

        $uploadedPaths = [];
        $files = $request->file('images', []);
        $setAsCover = $request->boolean('set_first_as_cover');
        $nextExtraIndex = $request->boolean('clean_previous') ? 1 : $importService->getNextExtraIndex($product->id);

        foreach ($files as $index => $file) {
            if ($index === 0 && $setAsCover) {
                $imageIndex = 1;
            } else {
                $imageIndex = $nextExtraIndex + 1;
                $nextExtraIndex++;
            }

            $result = $importService->processSingleImage(
                filePath: $file->getRealPath(),
                productId: $product->id,
                imageIndex: $imageIndex,
                overwrite: true
            );

            $uploadedPaths[] = $result['public_url'] ?? str_replace('Guardado en ', '', $result['message']);
        }

        // Limpiar caché de media del producto y tocar el timestamp
        Cache::forget("product_media_{$product->id}");
        $product->touch();

        return response()->json([
            'message' => 'Imágenes subidas y asociadas a la galería correctamente en formato WebP',
            'product_id' => $product->id,
            'uploaded_count' => count($uploadedPaths),
            'files' => $uploadedPaths,
            'media' => $product->fresh()->media,
        ], 200);
    }

    /**
     * Eliminar la galería de imágenes adicionales de un producto.
     *
     * Elimina los archivos adicionales en `storage/app/public/image_cache/{p1}/{p2}/` y purga las cachés asociadas.
     *
     * @response array{message: string, product_id: int}
     */
    public function deleteMedia(Request $request, Product $product, ImageImportService $importService): JsonResponse
    {
        $importService->deleteProductMedia($product->id, includeMain: false);
        $product->touch();

        return response()->json([
            'message' => 'Galería de imágenes eliminada correctamente',
            'product_id' => $product->id,
        ], 200);
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
