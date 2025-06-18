<?php
// buscador principal de productos
namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductSearchService
{
  public function searchProducts(array $params, int $itemsPerPage, bool &$featured): LengthAwarePaginator
  {
    $filter['published'] = 1;

    if (empty($params['search']) && empty($params['category']) && empty($params['brand']) && empty($params['similar']) && empty($params['tag'])) {
      $filter['featured'] = 1;
      $featured = true;
      return Product::where('visibility', '!=', 'hidden')
        ->where($filter)
        ->where('model', '!=', 'consumo interno')
        ->orderBy('featured', 'desc')
        ->orderBy('updated_at', 'desc')
        ->paginate($itemsPerPage);
    } else {
      $featured = false;
    }

    $query = Product::query()
      ->where('visibility', '!=', 'hidden')
      ->where('model', '!=', 'consumo interno')
      ->where($filter);

    if (!empty($params['tag'])) {
      $query->where('tags', 'like', '%' . $params['tag'] . '%');
    }

    if (!empty($params['category'])) {
      $query->where('category', $params['category']);
    }

    if (!empty($params['brand'])) {
      $query->where('brand', $params['brand']);
    }

    if (!empty($params['search'])) {
      $searchMultiple = array_filter(explode(' ', $params['search']));
      $query->where(function ($q) use ($searchMultiple) {
        foreach ($searchMultiple as $word) {
          $q->where(
            DB::raw('concat(description, " ", model, " ", brand," ",product_type," ",category," ",ifnull(tags,""))'),
            'like',
            '%' . $word . '%'
          );
        }
      });
    }

    if (!empty($params['similar'])) {
      $query->where('model', $params['similar']);
    }

    if ($user = auth()->user()) {
      $query->leftJoin('list_prices', function ($join) use ($user) {
        $join->on('products.id', '=', 'list_prices.product_id')
          ->where('list_prices.list_id', $user->list_id);
      })
        ->select('products.*', 'list_prices.price as user_price');
    }

    return $query->orderBy('description', 'asc')->paginate($itemsPerPage);
  }

  public function searchProductById(int $id): ?Product
  {
    $query = Product::where('id', $id)
      ->where('visibility', '!=', 'hidden')
      ->where('model', '!=', 'consumo interno');
    if ($user = auth()->user()) {
      $query->leftJoin('list_prices', function ($join) use ($user) {
        $join->on('products.id', '=', 'list_prices.product_id')
          ->where('list_prices.list_id', $user->list_id);
      })
        ->select('products.*', 'list_prices.price as user_price');
    }
    return $query->first();
  }
  public function searchRelatedProduct(Product $product, $limit = 9)
  {
    $query = Product::query()
      ->where('published', true)
      ->where('product_type', $product->product_type)
      ->where('visibility', '!=', 'hidden')
      ->where('id', '!=', $product->id)
      ->inRandomOrder()
      ->limit($limit); // aún es builder

    if ($user = auth()->user()) {
      $query->leftJoin('list_prices', function ($join) use ($user) {
        $join->on('products.id', '=', 'list_prices.product_id')
          ->where('list_prices.list_id', $user->list_id);
      });

      $query->select('products.*', 'list_prices.price as user_price');
    }

    $products = $query->get();

    // mapear qtty si lo necesitás
    $products->map(function ($product) {
      $product->qtty = $product->qtty_package;
      return $product;
    });

    return $products;
  }
}
