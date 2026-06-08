<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreListPriceRequest;
use App\Models\ListPrice;
use App\Services\PriceListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ListPriceController extends Controller
{
    public function __construct(protected PriceListService $priceService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->has('page') || $request->has('per_page')) {
            $perPage = $request->input('per_page', 50);
            $listPrices = ListPrice::paginate($perPage);
        } else {
            $listPrices = ListPrice::all();
        }

        return response()->json($listPrices, 200);
    }

    /**
     * Store (or update) a resource.
     */
    public function store(StoreListPriceRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        $normalized = $this->priceService->normalize(
            $validatedData['list_id'],
            array_intersect_key($validatedData, array_flip(['price', 'unit_price']))
        );

        $list_id = $normalized['list_id'];
        $data = $normalized['data'];

        $existingRecord = ListPrice::where('product_id', $validatedData['product_id'])
            ->where('list_id', $list_id)
            ->first();

        if (! isset($data['price']) && ! $existingRecord) {
            $data['price'] = 0;
        }

        $listPrice = ListPrice::updateOrCreate(
            ['product_id' => $validatedData['product_id'], 'list_id' => $list_id],
            $data
        );

        return response()->json($listPrice, $listPrice->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Display the specified resource using product ID.
     */
    public function show(int $product_id, int $list_id): JsonResponse
    {
        $requestedListId = (int) $list_id;
        $baseListId = $this->priceService->resolveBaseListId($requestedListId);

        $listPrice = ListPrice::where('product_id', $product_id)
            ->where('list_id', $baseListId)
            ->firstOrFail();

        $listPrice->list_id = $requestedListId;

        return response()->json($listPrice, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $product_id, int $list_id): JsonResponse
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
    public function destroy(int $product_id, int $list_id): JsonResponse
    {
        $listPrice = ListPrice::where('product_id', $product_id)
            ->where('list_id', $list_id)
            ->firstOrFail();

        $listPrice->delete();

        return response()->json(['message' => 'Precio de lista eliminado correctamente'], 200);
    }
}
