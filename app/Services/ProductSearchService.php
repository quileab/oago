<?php

// buscador principal de productos

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductSearchService
{
    public function searchProducts(array $params, int $itemsPerPage = 15, bool $featured = false): Product|LengthAwarePaginator|null
    {
        $query = Product::query()
            ->where('published', 1)
            ->where('visibility', '!=', 'hidden')
            ->where(DB::raw('ifnull(model, "")'), '!=', 'consumo interno');

        // merge featured from parameter if set
        if ($featured) {
            $params['featured'] = true;
        }

        // 🔍 Buscar por ID
        if (isset($params['id'])) {
            $query->where('products.id', $params['id']);
            $product = $query->first();

            if ($product) {
                $this->hydratePrices(collect([$product]));
            }

            return $product;
        }

        // ⭐ Mostrar destacados y ultimos productos si no hay filtros
        if (
            ! isset($params['featured']) &&
            empty($params['search']) &&
            empty($params['category']) &&
            empty($params['brand']) &&
            empty($params['similar']) &&
            empty($params['tag'])
        ) {
            $query->orderBy('featured', 'desc')
                ->orderBy('created_at', 'desc');
        }

        // 🔍 Filtros básicos
        if (! empty($params['featured'])) {
            $query->where('featured', 1);
        }

        if (! empty($params['tag'])) {
            $query->where('tags', 'like', '%'.$params['tag'].'%');
        }

        foreach (['category', 'brand', 'similar'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter === 'similar' ? 'model' : $filter, $params[$filter]);
            }
        }

        if (! empty($params['search'])) {
            $terms = array_filter(explode(' ', $params['search']));
            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where(DB::raw('concat(description, " ", ifnull(model, ""), " ", ifnull(brand, ""), " ", ifnull(product_type, ""), " ", ifnull(category, ""), " ", ifnull(tags, ""))'), 'like', "%$term%");
                }
            });
        }

        // 🧭 Ordenamiento
        $allowedColumns = ['description', 'created_at', 'featured', 'price', 'brand', 'category', 'model'];
        $orderBy = $params['order_by'] ?? 'description';
        $rawDirection = $params['order_direction'] ?? '';
        $orderDirection = in_array($rawDirection, ['asc', 'desc']) ? $rawDirection : 'asc';

        if (in_array($orderBy, $allowedColumns)) {
            $query->orderBy("products.$orderBy", $orderDirection);
        } else {
            $query->orderBy('products.description', $orderDirection);
        }

        $results = $itemsPerPage === 1
          ? $query->first()
          : $query->paginate($itemsPerPage);

        // 💰 Hidratar precios de forma masiva
        if ($results instanceof LengthAwarePaginator) {
            $this->hydratePrices($results->getCollection());
        } elseif ($results instanceof Product) {
            $this->hydratePrices(collect([$results]));
        }

        return $results;
    }

    public function searchRelatedProducts(Product $product, int $limit = 9)
    {
        $products = Product::query()
            ->where('published', 1)
            ->where('product_type', $product->product_type)
            ->where('visibility', '!=', 'hidden')
            ->where('products.id', '!=', $product->id)
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        $this->hydratePrices($products);

        return $products->map(function ($product) {
            $product->qtty = $product->qtty_package;

            return $product;
        });
    }

    /**
     * Carga de forma eficiente los precios específicos del usuario para una colección de productos.
     */
    protected function hydratePrices($products): void
    {
        if ($products->isEmpty()) {
            return;
        }

        $user = current_user();
        if (! $user || ! $user->list_id) {
            foreach ($products as $product) {
                $product->user_price = (float) $product->price;
            }

            return;
        }

        $priceService = app(PriceListService::class);
        $baseListId = $priceService->resolveBaseListId($user->list_id);

        // Cargar precios base en una sola consulta
        $listPrices = DB::table('list_prices')
            ->where('list_id', $baseListId)
            ->whereIn('product_id', $products->pluck('id'))
            ->get()
            ->keyBy('product_id');

        $isUnitList = str_ends_with(trim($user->list->name ?? ''), 'U');

        foreach ($products as $product) {
            $lp = $listPrices->get($product->id);
            if ($lp) {
                if ($isUnitList) {
                    $product->user_price = (float) ($lp->unit_price ?: $lp->price);
                } else {
                    $product->user_price = (float) $lp->price;
                }
            } else {
                $product->user_price = (float) $product->price;
            }
        }
    }

    protected function addUserPriceJoin($query, $user): void
    {
        // Este método queda obsoleto con la nueva hidratación masiva
    }
}
