<?php
// buscador principal de productos
namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductSearchService
{
  public function searchProducts(array $params, int $itemsPerPage): Product|LengthAwarePaginator|null
  {
    $user = null;
    if (Auth::guard('web')->check()) {
        $user = Auth::guard('web')->user();
    } elseif (Auth::guard('guest')->check()) {
        $user = Auth::guard('guest')->user();
    }

    $query = Product::query()
      ->where('published', 1)
      ->where('visibility', '!=', 'hidden')
      ->where('model', '!=', 'consumo interno');

    // 🔍 Buscar por ID
    if (isset($params['id'])) {
      $query->where('products.id', $params['id']);

      if ($user) {
        $this->addUserPriceJoin($query, $user);
      }

      return $query->first();
    }

    // ⭐ Mostrar destacados y ultimos productos si no hay filtros
    if (
      !isset($params['featured']) &&
      empty($params['search']) &&
      empty($params['category']) &&
      empty($params['brand']) &&
      empty($params['similar']) &&
      empty($params['tag'])
    ) {
      // last created products and featured products first
      $query->orderBy('featured', 'desc')
        ->orderBy('created_at', 'desc');
    }

    // 🔍 Filtros básicos
    if (!empty($params['featured'])) {
      $query->where('featured', 1);
    }

    if (!empty($params['tag'])) {
      $query->where('tags', 'like', '%' . $params['tag'] . '%');
    }

    foreach (['category', 'brand', 'similar'] as $filter) {
      if (!empty($params[$filter])) {
        $query->where($filter === 'similar' ? 'model' : $filter, $params[$filter]);
      }
    }

    if (!empty($params['search'])) {
      $terms = array_filter(explode(' ', $params['search']));
      $query->where(function ($q) use ($terms) {
        foreach ($terms as $term) {
          $q->where(DB::raw('concat(description, " ", model, " ", brand, " ", product_type, " ", category, " ", ifnull(tags, ""))'), 'like', "%$term%");
        }
      });
    }

    // 💰 Agregar precios si hay usuario
    if ($user) {
      $this->addUserPriceJoin($query, $user);
    }

    // 🧭 Ordenamiento
    $orderBy = $params['order_by'] ?? 'description';
    $orderDirection = $params['order_direction'] ?? 'asc';
    $query->orderBy("products.$orderBy", $orderDirection);

    return $itemsPerPage === 1
      ? $query->first()
      : $query->paginate($itemsPerPage);
  }

  public function searchRelatedProducts(Product $product, int $limit = 9)
  {
    $user = null;
    if (Auth::guard('web')->check()) {
        $user = Auth::guard('web')->user();
    } elseif (Auth::guard('guest')->check()) {
        $user = Auth::guard('guest')->user();
    }

    $query = Product::query()
      ->where('published', 1)
      ->where('product_type', $product->product_type)
      ->where('visibility', '!=', 'hidden')
      ->where('products.id', '!=', $product->id)
      ->inRandomOrder()
      ->limit($limit);

    if ($user) {
      $this->addUserPriceJoin($query, $user);
    }

    return $query->get()->map(function ($product) {
      $product->qtty = $product->qtty_package;
      return $product;
    });
  }

  protected function addUserPriceJoin($query, $user): void
  {
    $query->leftJoin('list_prices', function ($join) use ($user) {
      $join->on('products.id', '=', 'list_prices.product_id')
        ->where('list_prices.list_id', $user->list_id);
    });

    $query->select('products.*', 'list_prices.price as user_price');
  }
}
