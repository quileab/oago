<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

use function Pest\Laravel\json;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::all();
        return response()->json($products, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'id' => 'nullable',
                'barcode' => 'nullable|string|max:50',
                'sku' => 'nullable|string|max:50',
                'product_type' => 'nullable|string|max:30',
                'brand' => 'nullable|string|max:30',
                'model' => 'nullable|string|max:130',
                'category' => 'nullable|string|max:50',
                'description' => 'required|string|max:100',
                'description_html' => 'nullable|string|max:250',
                'published' => 'required|boolean',
                'featured' => 'required|boolean',
                'visibility' => 'required|string|max:10',
                'offer_start' => 'nullable|date',
                'offer_end' => 'nullable|date',
                'tax_status' => 'required|string|max:10',
                'in_stock' => 'required|boolean',
                'stock' => 'required|integer|min:0',
                'allow_reservation' => 'required|boolean',
                'qtty_package' => 'required|integer|min:1',
                'qtty_unit' => 'required|integer|min:1',
                'by_bulk' => 'boolean',
                'weight' => 'nullable|numeric|min:0',
                'lenght' => 'nullable|numeric|min:0',
                'width' => 'nullable|numeric|min:0',
                'height' => 'nullable|numeric|min:0',
                'price' => 'required|numeric|min:0',
                'offer_price' => 'nullable|numeric|min:0',
                'tags' => 'nullable|string|max:50',
                'image_url' => 'nullable|string|max:250',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        //check if product exists
        $product_exists = Product::find($request->id);
        if ($product_exists instanceof Product) {
            return $this->update($request, $product_exists);
        }

        //create new product
        $product = Product::create($request->all());
        return response()->json($product, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        // return proper response if product not found
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        return response()->json($product, 200);
    }

    // Actualizar un producto
    public function update(Request $request, Product $product)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'id' => 'nullable',
                'barcode' => 'nullable|string|max:50',
                'sku' => 'nullable|string|max:50',
                'product_type' => 'nullable|string|max:30',
                'brand' => 'nullable|string|max:30',
                'model' => 'nullable|string|max:130',
                'category' => 'nullable|string|max:50',
                'description' => 'required|string|max:100',
                'description_html' => 'nullable|string|max:250',
                'published' => 'required|boolean',
                'featured' => 'required|boolean',
                'visibility' => 'required|string|max:10',
                'offer_start' => 'nullable|date',
                'offer_end' => 'nullable|date',
                'tax_status' => 'required|string|max:10',
                'in_stock' => 'required|boolean',
                'stock' => 'required|integer|min:0',
                'allow_reservation' => 'required|boolean',
                'qtty_package' => 'required|integer|min:1',
                'qtty_unit' => 'required|integer|min:1',
                'by_bulk' => 'boolean',
                'weight' => 'nullable|numeric|min:0',
                'lenght' => 'nullable|numeric|min:0',
                'width' => 'nullable|numeric|min:0',
                'height' => 'nullable|numeric|min:0',
                'price' => 'required|numeric|min:0',
                'offer_price' => 'nullable|numeric|min:0',
                'tags' => 'nullable|string|max:50',
                'image_url' => 'nullable|string|max:250',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product->update($request->all());
        return response()->json($product, 200);
    }

    // Eliminar un producto
    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['message' => 'Producto eliminado correctamente'], 200);
    }

    // Subir la imagen de un producto
    public function uploadImage(Request $request, Product $product)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            // Eliminar imagen anterior si existe
            if ($product->image_url) {
                Storage::delete($product->image_url);
            }

            // Almacenar la nueva imagen
            $path = $request->file('image')->store('images/products');
            $product->update(['image_url' => $path]);

            return response()->json(['message' => 'Imagen subida correctamente', 'path' => $path], 200);
        }

        return response()->json(['message' => 'No se pudo subir la imagen'], 500);
    }

    public function changeVisibility(Request $request, $product)
    {
        $product = Product::find($product);
        //return response()->json(['message' => json_encode($request)], 200);

        $request->validate([
            'sku' => 'nullable|string|max:50',
            'visibility' => 'required|string|in:visible,catalog,hidden',
        ]);

        // check if product not exists try to find sku
        if (!$product) {
            $product = Product::where('sku', $request->sku)->first();
            if (!$product) {
                return response()->json(['message' => 'Producto no encontrado'], 404);
            }
        }

        $product->visibility = $request->visibility;
        $product->save();

        return response()->json(['message' => 'Visibilidad actualizada correctamente', 'product' => $product], 200);
    }
}
