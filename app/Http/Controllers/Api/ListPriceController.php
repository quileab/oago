<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ListPrice;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ListPriceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[Response(status: 200, type: 'App\Models\ListPrice[]')]
    public function index(): JsonResponse
    {
        $listPrices = ListPrice::all();

        return response()->json($listPrices, 200);
    }

    /**
     * Store (or update) a resource.
     */
    #[Response(status: 201, type: 'App\Models\ListPrice')]
    public function store(Request $request): JsonResponse
    {
        // check if product exists where product_id and list_id
        $listPrice_exists = ListPrice::where('product_id', $request->product_id)->where('list_id', $request->list_id)->first();
        if ($listPrice_exists) {
            return $this->update($request, (int) $listPrice_exists->product_id, (int) $listPrice_exists->list_id);
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
    #[Response(status: 200, type: 'App\Models\ListPrice')]
    public function show(int $product_id, int $list_id): JsonResponse
    {
        $listPrice = ListPrice::where('product_id', $product_id)
            ->where('list_id', $list_id)
            ->firstOrFail();

        // return price of product_id
        return response()->json($listPrice, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $product_id, int $list_id): JsonResponse
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

        // return response()->json($listPrice, 200);
        // just return ok when no errors
        return response()->json(['message' => 'OK'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $product_id, int $list_id): JsonResponse
    {
        $listPrice = ListPrice::where('product_id', $product_id)
            ->where('list_id', $list_id)
            ->firstOrFail();

        $listPrice->delete();

        return response()->json(['message' => 'Precio de lista eliminado correctamente'], 200);
    }
}
