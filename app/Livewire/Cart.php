<?php

namespace App\Livewire;

use App\Enums\OrderStatus;
use App\Models\AltOrder;
use App\Models\AltUser;
use App\Models\Order;
use App\Models\Product;
use App\Services\PriceListService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;

class Cart extends Component
{
    use Toast;

    public bool $showCart = false;

    public $total = 0;

    /**
     * Cache de productos hidratados para el ciclo de vida del request.
     * Evita relanzar la misma consulta de productos/precios en cada fase.
     */
    protected ?Collection $hydratedProducts = null;

    public function mount()
    {
        $this->refreshCartData();
    }

    #[On('addToCart')]
    public function onAddToCart($product, int $quantity = 1): void
    {
        $user = current_user();
        if (! $user || $user->role->value === 'guest') {
            $this->warning('Debe iniciar sesión para comprar');

            return;
        }

        if (is_numeric($product)) {
            $productModel = Product::find($product);
            if (! $productModel) {
                return;
            }

            $product = [
                'id' => $productModel->id,
                'description' => $productModel->description,
                'user_price' => current_user()->getProductPrice($productModel),
                'qtty_package' => $productModel->qtty_package,
            ];
        }

        $productId = $product['id'];
        $productModel = Product::find($productId);
        if ($productModel) {
            $product['user_price'] = current_user()?->getProductPrice($productModel) ?? $product['user_price'];
            $product['qtty_package'] = $productModel->qtty_package;
            $product['description'] = $product['description'] ?? $productModel->description;
        }
        $cart = Session::get('cart', []);
        $qttyPackage = max(1, $product['qtty_package'] ?? 1);

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $quantity;
        } else {
            $cart[$productId] = [
                'product_id' => $productId,
                'name' => $product['description'],
                'price' => $product['user_price'],
                'bulkQuantity' => $qttyPackage,
                'quantity' => $quantity,
            ];
        }

        $this->updateCartAndNotify($cart);
    }

    public function removeFromCart($productId)
    {
        $cart = Session::get('cart', []);
        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            $this->updateCartAndNotify($cart);
        }
    }

    public function updateQuantity($productId, $quantity)
    {
        $cart = Session::get('cart', []);
        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] = max(1, (int) $quantity);
            $this->updateCartAndNotify($cart);
        }
    }

    private function updateCartAndNotify($cart)
    {
        foreach ($cart as $id => $item) {
            $cart[$id]['byBulk'] = ($item['quantity'] % ($item['bulkQuantity'] ?? 1)) == 0;
        }

        Session::put('cart', $cart);
        $this->refreshCartData();

        // Notificar cambios para que la UI reaccione
        $this->dispatch('cart-updated');

        // Guardar temporalmente el carrito en un JSON
        $this->jsonCartUpdate();
    }

    /**
     * Obtiene los productos del carrito hidratados una sola vez por request.
     * Si aparecen IDs nuevos (ej. tras addToCart), los completa sin rehacer todo.
     */
    protected function getHydratedProducts(array $productIds): Collection
    {
        $ids = array_map('intval', array_filter($productIds));

        if ($this->hydratedProducts === null) {
            $this->hydratedProducts = Product::whereIn('id', $ids)->get()->keyBy('id');

            return $this->hydratedProducts;
        }

        $missing = collect($ids)->diff($this->hydratedProducts->keys());
        if ($missing->isNotEmpty()) {
            $extra = Product::whereIn('id', $missing->all())->get()->keyBy('id');
            $this->hydratedProducts = $this->hydratedProducts->merge($extra);
        }

        return $this->hydratedProducts;
    }

    /**
     * Calcula el total y los datos de vista del carrito en una sola pasada.
     * calculateTotal() y render() comparten esto; ya no duplican queries ni precios.
     *
     * @return array{items: array, total: float}
     */
    protected function refreshCartData(): array
    {
        $cart = Session::get('cart', []);
        $products = $this->getHydratedProducts(array_column(array_values($cart), 'product_id'));
        $user = current_user();
        $priceListService = app(PriceListService::class);

        $enrichedCart = [];
        $total = 0;

        foreach ($cart as $productId => $item) {
            $product = $products->get($productId);
            $orderedQuantity = (int) $item['quantity'];
            $billableQuantity = $priceListService->calculateBillableQuantity($product, $orderedQuantity);

            if ($product && $user) {
                $item['product_model'] = $product;
                $item['current_price'] = $user?->getProductPrice($product);
                $item['is_price_changed'] = abs((float) $item['price'] - (float) ($item['current_price'] ?? 0)) > 0.01;
                $line = $priceListService->calculateLine(
                    $priceListService->resolveListId($user),
                    $product,
                    $orderedQuantity
                );
                $item['total_price'] = $line['subtotal'];
                $item['is_stock_insufficient'] = $product->stock < $item['quantity'];
                $item['available_stock'] = $product->stock;
            } else {
                $item['total_price'] = (float) $item['price'] * $billableQuantity;
            }

            $total += $item['total_price'];
            $enrichedCart[$productId] = $item;
        }

        $this->total = $total;

        return ['items' => $enrichedCart, 'total' => $total];
    }

    public function calculateTotal()
    {
        $this->refreshCartData();
    }

    public function render()
    {
        $data = $this->refreshCartData();

        return view('livewire.cart', [
            'cart' => $data['items'],
            'total' => $data['total'],
        ]);
    }

    public function emptyCart()
    {
        Session::forget('cart');
        Session::forget('updateOrder');
        $this->total = 0;
        $this->showCart = false;
        $this->dispatch('cart-updated');
        $this->info('Carrito vacío');
        $this->jsonCartDelete();
    }

    public function jsonCartUpdate()
    {
        if (current_user_cart_id()) {
            $jsonCart = json_encode(Session::get('cart'));
            file_put_contents(storage_path('app/private/'.current_user_cart_id().'_cart.json'), $jsonCart);
        }
    }

    public function jsonCartDelete()
    {
        if (current_user_cart_id() && file_exists(storage_path('app/private/'.current_user_cart_id().'_cart.json'))) {
            unlink(storage_path('app/private/'.current_user_cart_id().'_cart.json'));
        }
    }

    public function saveCart()
    {
        $user = current_user();

        if (! $user) {
            return;
        }

        $orderModel = $user instanceof AltUser ? AltOrder::class : Order::class;

        try {
            $order = $orderModel::placeOrder(
                ['status' => OrderStatus::SAVED->value],
                redirect: false
            );
        } catch (ValidationException $e) {
            $this->error($e->getMessage(), position: 'toast-bottom');

            return;
        }

        $this->refreshCartData();
        $this->dispatch('cart-updated');

        $this->success(
            "Carrito guardado como pedido #{$order->id}. Lo encontrás en Mis pedidos.",
            position: 'toast-bottom'
        );
    }
}
