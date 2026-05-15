<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ListName;
use App\Models\ListPrice;
use App\Services\PriceListService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ListPriceController extends Controller
{
    public function __construct(protected PriceListService $priceService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 50);
        $listPrices = ListPrice::paginate($perPage);

        return response()->json($listPrices, 200);
    }

    /**
     * Store (or update) a resource.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'list_id' => 'required|exists:list_names,id',
            'price' => 'required|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $normalized = $this->priceService->normalize(
            $request->list_id,
            $request->only(['price', 'unit_price'])
        );

        $list_id = $normalized['list_id'];
        $data = $normalized['data'];

        // Si es una creación y no viene precio base (e.g. viene de lista U), aseguramos 0
        if (!isset($data['price'])) {
            $data['price'] = 0;
        }

        $listPrice = ListPrice::updateOrCreate(
            ['product_id' => $request->product_id, 'list_id' => $list_id],
            $data
        );

        return response()->json($listPrice, $listPrice->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Display the specified resource using product ID.
     */
    public function show($product_id, $list_id)
    {
        $list_id = $this->priceService->resolveBaseListId($list_id);

        $listPrice = ListPrice::where('product_id', $product_id)
            ->where('list_id', $list_id)
            ->firstOrFail();

        return response()->json($listPrice, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $product_id, $list_id)
    {
        $validator = Validator::make($request->all(), [
            'price' => 'numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $normalized = $this->priceService->normalize(
            $list_id,
            $request->only(['price', 'unit_price'])
        );

        $list_id = $normalized['list_id'];
        $data = $normalized['data'];

        $listPrice = ListPrice::where('product_id', $product_id)
            ->where('list_id', $list_id)
            ->firstOrFail();

        $listPrice->update($data);

        return response()->json(['message' => 'OK'], 200);
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
