<?php

namespace App\Services;

use App\Models\AltUser;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Session;

/**
 * Fuente única de verdad para persistir el carrito en disco (JSON) y
 * rehidratarlo en la sesión.
 */
class CartStorageService
{
    /**
     * Identificador de carrito aislado por tipo de usuario: web_{id} o alt_{id}.
     */
    public function key(User|AltUser $user): string
    {
        $prefix = ($user instanceof AltUser) ? 'alt' : 'web';

        return "{$prefix}_{$user->id}";
    }

    public function path(User|AltUser $user): string
    {
        return storage_path('app/private/'.$this->key($user).'_cart.json');
    }

    /**
     * Lee el carrito persistido del usuario.
     *
     * @return array<int|string, array<string, mixed>>
     */
    public function load(User|AltUser $user): array
    {
        $path = $this->path($user);

        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Persiste el carrito de forma atómica. Un carrito vacío elimina el archivo.
     *
     * @param  array<int|string, array<string, mixed>>|null  $cart
     */
    public function save(User|AltUser $user, ?array $cart): void
    {
        if (empty($cart)) {
            $this->forget($user);

            return;
        }

        $path = $this->path($user);
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $temporaryPath = $path.'.'.bin2hex(random_bytes(4)).'.tmp';
        file_put_contents($temporaryPath, json_encode($cart), LOCK_EX);
        rename($temporaryPath, $path);
    }

    public function forget(User|AltUser $user): void
    {
        $path = $this->path($user);

        if (is_file($path)) {
            unlink($path);
        }
    }

    /**
     * Persiste el carrito actual de la sesión para el usuario indicado.
     */
    public function persistSessionCart(User|AltUser $user): void
    {
        $this->save($user, Session::get('cart'));
    }

    /**
     * Carga el carrito persistido en la sesión, descartando productos no vendibles
     * y recalculando precios con la lista del usuario.
     */
    public function restoreIntoSession(User|AltUser $user): void
    {
        $cart = $this->load($user);

        if ($cart === []) {
            return;
        }

        $priceService = app(PriceListService::class);
        $listId = $priceService->resolveListId($user);

        foreach ($cart as $key => $item) {
            $product = Product::find($item['product_id'] ?? 0);

            if (! $product || ! $priceService->isSellable($listId, $product)) {
                unset($cart[$key]);

                continue;
            }

            $cart[$key]['price'] = $user->getProductPrice($product);
        }

        Session::put('cart', $cart);
        $this->save($user, $cart);
    }

    /**
     * Restaura el carrito solo si la sesión no tiene uno (p. ej. sesión nueva).
     */
    public function restoreIfMissing(User|AltUser $user): void
    {
        if (! Session::has('cart')) {
            $this->restoreIntoSession($user);
        }
    }
}
