<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ListPrice;
use Illuminate\Support\Facades\Validator;

class ListPriceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $listPrices = ListPrice::all();
        return response()->json($listPrices, 200);
    }

    /**
     * Store (or update) a resource.
     */
    public function store(Request $request)
    {
        // check if product exists where product_id and list_id
        $listPrice_exists=ListPrice::where('product_id', $request->product_id)->where('list_id', $request->list_id)->first();
        if($listPrice_exists){
            return $this->update($request, $listPrice_exists->product_id, $listPrice_exists->list_id);
        }
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'list_id' => 'required|exists:list_names,id',
            'price' => 'required|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Crear o actualizar el precio (por restricción única en `product_id` y `list_id`)
        $listPrice = ListPrice::updateOrCreate(
            ['product_id' => $request->product_id, 'list_id' => $request->list_id],
            $request->only(['price', 'unit_price'])
        );

        return response()->json($listPrice, 201);
    }

    /**
     * Display the specified resource using product ID.
     */
    public function show($product_id,$list_id)
    {
        $listPrice = ListPrice
            ::where('product_id', $product_id)
            ->where('list_id', $list_id)
            ->firstOrFail();
        // return price of product_id
        return response()->json($listPrice, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $product_id, $list_id)
    {
        $listPrice = ListPrice::where('product_id', $product_id)
                               ->where('list_id', $list_id)
                               ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'price' => 'numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $listPrice->update($request->only(['price', 'unit_price']));
        return response()->json($listPrice, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($product_id, $list_id)
    {
        $listPrice = ListPrice::where('product_id', $product_id)
                               ->where('list_id', $list_id)
                               ->firstOrFail();

        $listPrice->delete();
        return response()->json(['message' => 'Precio de lista eliminado correctamente'], 200);
    }
}
