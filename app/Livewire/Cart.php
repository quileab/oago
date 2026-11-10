<?php

namespace App\Livewire;

use App\Enums\OrderStatus;
use App\Models\AltOrder;
use App\Models\AltUser;
use App\Models\Order;
use App\Models\Product;
use App\Services\CartStorageService;
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
        if ($user = current_user()) {
            app(CartStorageService::class)->restoreIfMissing($user);
        }

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

        $productId = is_numeric($product) ? (int) $product : (int) ($product['id'] ?? 0);
        $productModel = $productId ? Product::find($productId) : null;

        // Precio 0 = producto sin precio: no se vende.
        $priceListService = app(PriceListService::class);
        if (! $productModel || ! $priceListService->isSellable($priceListService->resolveListId($user), $productModel)) {
            $this->error('Este producto no está disponible para la venta.');

            return;
        }

        $userPrice = $user->getProductPrice($productModel);
        $description = is_array($product) && ! empty($product['description'])
            ? $product['description']
            : $productModel->description;

        $cart = Session::get('cart', []);
        $qttyPackage = max(1, (int) ($productModel->qtty_package ?? 1));

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $quantity;
        } else {
            $cart[$productId] = [
                'product_id' => $productId,
                'name' => $description,
                'price' => $userPrice,
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
        if (isset($cart[$productId]) && $user = current_user()) {
            $productModel = Product::find($productId);

            // Precio 0 = producto sin precio: dejó de venderse, se saca del carrito.
            if (! $productModel || ! app(PriceListService::class)->isSellable(app(PriceListService::class)->resolveListId($user), $productModel)) {
                unset($cart[$productId]);
                $this->error('El producto ya no está disponible para la venta. Se retiró del carrito.');
                $this->updateCartAndNotify($cart);

                return;
            }

            $cart[$productId]['quantity'] = max(1, (int) $quantity);
            $cart[$productId]['price'] = $user->getProductPrice($productModel);

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
        $savedOrders = $this->getSavedOrders();

        return view('livewire.cart', [
            'cart' => $data['items'],
            'total' => $data['total'],
            'savedOrders' => $savedOrders,
            'latestSavedOrder' => $savedOrders->first(),
        ]);
    }

    /**
     * Obtiene los pedidos guardados (status = saved) del usuario actual.
     */
    public function getSavedOrders(): Collection
    {
        $user = current_user();
        if (! $user) {
            return new Collection;
        }

        $isAlt = $user instanceof AltUser;
        $orderModel = $isAlt ? AltOrder::class : Order::class;
        $foreignKey = $isAlt ? 'alt_user_id' : 'user_id';

        return $orderModel::with(['items.product'])
            ->where($foreignKey, $user->id)
            ->where('status', OrderStatus::SAVED)
            ->latest()
            ->get();
    }

    /**
     * Retoma un pedido guardado cargándolo al carrito de sesión.
     */
    public function resumeSavedOrder(int $orderId): void
    {
        $user = current_user();
        if (! $user) {
            $this->warning('Debe iniciar sesión para realizar esta acción.');

            return;
        }

        $isAlt = $user instanceof AltUser;
        $orderModel = $isAlt ? AltOrder::class : Order::class;
        $order = $orderModel::with(['items.product'])->find($orderId);

        $ownerId = $order ? ($isAlt ? $order->alt_user_id : $order->user_id) : null;

        if (! $order || $ownerId !== $user->id || $order->status !== OrderStatus::SAVED) {
            $this->error('No se encontró el carrito guardado o no tiene permisos.');

            return;
        }

        $cartItems = [];
        $priceService = app(PriceListService::class);
        $listId = $priceService->resolveListId($user);

        foreach ($order->items as $item) {
            $product = $item->product ?? Product::find($item->product_id);
            if (! $product || ! $priceService->isSellable($listId, $product)) {
                continue;
            }

            $currentPrice = $user->getProductPrice($product);
            $qttyPackage = max(1, (int) ($product->qtty_package ?? 1));

            $cartItems[$item->product_id] = [
                'product_id' => $item->product_id,
                'name' => $product->description,
                'quantity' => $item->quantity,
                'price' => $currentPrice,
                'bulkQuantity' => $qttyPackage,
                'byBulk' => ($item->quantity % $qttyPackage) === 0,
            ];
        }

        if (empty($cartItems)) {
            $this->warning('Los productos de este carrito guardado ya no se encuentran disponibles.');

            return;
        }

        Session::put('cart', $cartItems);
        Session::put('updateOrder', $order->id);

        app(CartStorageService::class)->persistSessionCart($user);

        $this->refreshCartData();
        $this->dispatch('cart-updated');

        $this->success("¡Carrito guardado #{$order->id} retomado con éxito!", position: 'toast-bottom');
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
        if ($user = current_user()) {
            app(CartStorageService::class)->persistSessionCart($user);
        }
    }

    public function jsonCartDelete()
    {
        if ($user = current_user()) {
            app(CartStorageService::class)->forget($user);
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
