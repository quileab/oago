<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
        //check if product exists
        $product_exists=Product::find($request->id);
        if($product_exists){
            return $this->update($request, $product_exists->id);
        }
        
        $validator = Validator::make($request->all(), [
            'description' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'visibility' => 'required|string|max:10',
            'tax_status' => 'required|string|max:10',
            // otras validaciones según la estructura de la tabla
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = Product::create($request->all());
        return response()->json($product, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return response()->json($product, 200);
    }

    // Actualizar un producto
    public function update(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'string|max:100',
            'price' => 'numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0',
            'stock' => 'integer|min:0',
            'visibility' => 'string|max:10',
            'tax_status' => 'string|max:10',
            // otras validaciones
        ]);

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
}
