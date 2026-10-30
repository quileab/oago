# Catálogo Integral de Servicios, Helpers y Métodos por Capa en OAGO

Este documento detalla cada uno de los servicios, funciones helper y métodos organizados en las 4 capas del sistema.

---

## 1. Capa de Autenticación, Usuarios y Sesión

### A. Helpers Globales (`app/Helpers/helpers.php`)

| Función Helper | Firma | Descripción y Función |
| :--- | :--- | :--- |
| `real_user()` | `real_user(): User\|AltUser\|null` | Obtiene el usuario autenticado real evaluando secuencialmente los guards `web` -> `sanctum` -> `alt`. **Nunca es alterado por impersonación.** |
| `current_user()` | `current_user(): User\|AltUser\|null` | Obtiene el usuario en cuyo contexto se opera. Si el usuario real es `role=sales` y tiene una sesión activa de cliente (`sales_acting_as_customer_id`), **devuelve transparentemente la instancia de `User` del cliente suplantado**. |
| `current_user_cart_id()` | `current_user_cart_id(): ?string` | Retorna el identificador de carrito en sesión prefijado con el guard (`web_{id}` o `alt_{id}`) para aislar completamente carritos entre clientes e invitados. |

---

### B. Helper de Configuración Dinámica (`app/Helpers/SettingsHelper.php`)

| Método | Firma | Descripción y Función |
| :--- | :--- | :--- |
| `settings()` | `settings(string $key, mixed $default = null): mixed` | Consulta un valor en la tabla `settings` utilizando caché perpetua (`Cache::rememberForever`). Realiza casteo automático según el tipo definido (`number`, `boolean`, `json`, `string`). |
| `update_setting()` | `update_setting(string $key, mixed $value): void` | Crea o actualiza una clave en la tabla `settings`, codifica arrays a JSON y purga la clave en caché (`Cache::forget`). |

---

### C. Traits de Usuarios y Cartera

#### `App\Traits\ManagesCustomers` (Usado por `User` y `AltUser`)
- **`assignedCustomers(): MorphMany`**: Relación polimórfica que devuelve los registros de `CustomerSalesAgent` donde el usuario actúa como agente de ventas.
- **`getManagedCustomersQuery(): Builder`**: Retorna el query de clientes asignados al vendedor. Si el usuario tiene `is_internal = true` o es `admin`, retorna todos los `User` con `role = customer`. En caso contrario, retorna únicamente los clientes asignados en `customer_sales_agents`.

#### `App\Traits\HasProfileData` (Usado por `User` y `AltUser`)
- **`getFullNameAttribute(): string`**: Accessor para `"Apellido, Nombre"`. Si falta alguno, antepone `"✨SYS: {name}"`.
- **`getCommonFillable(): array`**: Lista consolidada de los atributos comunes de perfil (`name`, `email`, `role`, `list_id`, etc.).
- **`getProfileRules(string $table, ?int $ignoreId = null, string $prefix = ''): array`**: Genera reglas de validación alineadas a las longitudes SQL (`30`, `100`, `10`, `50`) y unicidad de email por tabla.
- **`getProfileMessages(string $prefix = ''): array`**: Devuelve los mensajes de validación estándar en español.

---

## 2. Capa de Catálogo y Precios

### A. Servicio de Precios (`app/Services/PriceListService.php`)

Servicio centralizado para la resolución jerárquica de precios (Base -> Lista -> Unidad -> Promoción -> Bonificación).

| Método | Firma | Descripción y Función |
| :--- | :--- | :--- |
| `resolveListId()` | `resolveListId(?Model $user): ?int` | Determina el `list_id` efectivo del usuario. Si es nulo o 0, cae a la lista por defecto configurada (`alt_user_default_price`). |
| `getBasePrice()` | `getBasePrice(?int $listId, Product $product): float` | Obtiene el precio base para una lista desde `list_prices`. Si el precio es 0 o no existe, usa `product->price`. |
| `isSellable()` | `isSellable(?int $listId, Product $product): bool` | Regla de negocio que comprueba si un producto está habilitado para la venta (su precio base debe ser estrictamente mayor a 0). |
| `getPromoPrice()` | `getPromoPrice(?int $listId, Product $product, float $basePrice): ?float` | Evalúa si existe una lista de promoción configurada (`promo_list_id`) y retorna su precio solo si es > 0 y menor al precio base. |
| `getEffectivePrice()` | `getEffectivePrice(int $listId, int $productId, bool $skipPromo = false): ?float` | Retorna el precio final aplicable considerando o salteando promociones vigentes. |
| `calculateBillableQuantity()`| `calculateBillableQuantity(?Product $product, int $orderedQty): int` | Calcula las unidades a cobrar aplicando ofertas por cantidad tipo bulto bonificado (ej. `"23 + 1"`). |
| `calculateItemPrice()` | `calculateItemPrice(?int $listId, Product $product, int $quantity): float` | Calcula el total para una cantidad aplicando cobro mixto: precio de bulto cerrado (`bulk`) para múltiplos del empaque y precio unitario (`unit_price`) para el remanente. |
| `calculateLine()` | `calculateLine(?int $listId, ?Product $product, int $orderedQuantity): array` | Retorna el detalle completo de línea de pedido: unitario promedio efectivo, subtotal redondeado y cantidad facturable. |

---

### B. Servicio de Búsqueda de Productos (`app/Services/ProductSearchService.php`)

| Método | Firma | Descripción y Función |
| :--- | :--- | :--- |
| `searchProducts()` | `searchProducts(array $params, int $perPage, bool $featured, bool $paginate)` | Motor principal de catálogo. Aplica filtros por categoría, marca, modelo similar, tags (`taggables`), búsqueda de texto por palabras y ordenamiento, paginando los resultados. |
| `hydratePrices()` | `hydratePrices(Collection $products): void` | Hidratación masiva de precios en batch para evitar problemas N+1, precargando precios de lista y promociones para los productos recuperados. |
| `searchRelatedProducts()` | `searchRelatedProducts(Product $product, int $limit = 9)` | Busca productos relacionados por categoría y marca excluyendo el producto actual. |

---

### C. Servicio de Galería y Sliders (`app/Services/SliderService.php`)

| Método | Firma | Descripción y Función |
| :--- | :--- | :--- |
| `getSlides()` | `getSlides(): Collection` | Retorna los banners activos configurados para el slider de la página principal. |
| `saveSlides()` | `saveSlides(array $slides): void` | Persiste la configuración de diapositivas y limpia su correspondiente entrada en caché. |

---

### D. Servicio de Importación de Imágenes (`app/Services/ImageImportService.php`)

| Método | Firma | Descripción y Función |
| :--- | :--- | :--- |
| `processDirectory()` | `processDirectory(string $path, array $options): array` | Recorre una carpeta del servidor local, asocia imágenes a productos mediante expresiones regulares, las convierte a WebP (máx 1200px) y genera la ruta pública en caché hash (`storage/image_cache/{p1}/{p2}/{hash}.webp`). |

---

## 3. Capa Transaccional (Carrito, Checkout y Pedidos)

### A. Servicio de Pedidos (`app/Services/OrderService.php`)

| Método | Firma | Descripción y Función |
| :--- | :--- | :--- |
| `placeOrder()` | `placeOrder(array $shippingData, bool $redirect = true)` | Procesa la conversión del carrito en pedido formal. Valida stock, deriva entre `Order` o `AltOrder` según el guard autenticado, crea el registro de `ShippingDetail`, limpia el carrito y despacha el correo de confirmación. |
| `validateCart()` | `validateCart(array $cart, Model $user, bool $enforceStock, bool $checkPriceDrift)` | Valida consistencia de precios del carrito recalculándolos al momento de compra y comprueba disponibilidad de stock. |
| `calculateTotals()` | `calculateTotals(array $items): array` | Totaliza los subtotales, descuentos de bonificación e importes finales de la orden. |
| `resolveStatusForPersistence()` | `resolveStatusForPersistence(?Model $existing, OrderStatus $requested)` | Controla transiciones de estado de pedido (ej. de borrador `SAVED` a `PENDING`). |
| `ensureOrderBelongsToUser()` | `ensureOrderBelongsToUser(Model $order, bool $isAlt): void` | Política de seguridad que aborta con `403 Forbidden` si un usuario intenta editar o finalizar un pedido ajeno. |

---

## 4. Capa Frontend (Livewire Volt & Traits Reutilizables)

### A. Trait de Formularios Administrativos (`app/Livewire/Traits/ManagesModelCrud.php`)

Compartido por `users/crud.blade.php` y `users/alts/crud.blade.php`:

| Método | Firma | Descripción y Función |
| :--- | :--- | :--- |
| `initializeUserForm()` | `initializeUserForm(?int $id, bool $allowCreate)` | Carga un registro para edición o inicializa el array `$formData`. Bloquea la creación si `allowCreate = false` (caso tabla `users`). |
| `isAltUser()` | `isAltUser(): bool` | Helper de vista que retorna `true` si el componente gestiona `AltUser`. |
| `roles()` | `roles(): array` | Propiedad computada que devuelve los casos del enum `Role` para los desplegables. |
| `priceLists()` | `priceLists(): array` | Propiedad computada que consulta las listas de precios en `list_names`. |
| `save()` | `save(): void` | Valida `$formData` contra las reglas de `HasProfileData`, sanitiza `list_id`, persiste en base de datos y lanza toast informativo. |
| `changePassword()` | `changePassword(): void` | Valida y actualiza la contraseña del usuario seleccionado. |
| `delete()` | `delete(): void` | Elimina el registro seleccionado y redirige al listado principal. |
| `resetDate()` | `resetDate(): void` | *(Exclusivo AltUser)* Reinicia `created_at` para renovar el período de prueba. |
| `sendWelcomeEmail()` | `sendWelcomeEmail(): void` | *(Exclusivo AltUser)* Genera contraseña temporal, asigna rol `guest`, nuevo token de activación y envía correo `AltUserWelcomeMail`. |

---

### B. Trait de Tablas y Paginación (`app/Livewire/Traits/ManagesModelIndex.php`)

Compartido por `users/index.blade.php` y `users/alts/index.blade.php`:

| Método | Firma | Descripción y Función |
| :--- | :--- | :--- |
| `getPaginatedRecords()` | `getPaginatedRecords(array $sortBy, int $paginate): LengthAwarePaginator` | Aplica filtro de búsqueda concatenada `name + lastname + email`, ordena según la columna indicada y pagina los resultados. |
| `delete()` | `delete(int\|string $id): void` | Elimina un registro por ID y emite un toast de confirmación. |
| `updated()` | `updated(mixed $property): void` | Hook de Livewire que resetea la paginación a la página 1 cuando cambia el texto de búsqueda. |

---

### C. Barra de Navegación (`app/Livewire/WebNavbar.php`)

| Método | Firma | Descripción y Función |
| :--- | :--- | :--- |
| `mount()` | `mount(ThemePageService $themePageService): void` | Carga páginas del tema activo, detecta si el usuario es vendedor para autoseleccionar su primer cliente y calcula días de prueba para invitados. |
| `loadCustomers()` | `loadCustomers(): void` | Ejecuta la consulta de clientes asignados (`getManagedCustomersQuery`) con soporte de búsqueda en tiempo real (`searchCustomer`). |
| `setActingCustomer()` | `setActingCustomer($id): void` | Valida que el cliente pertenezca a la cartera del vendedor, lo almacena en sesión (`sales_acting_as_customer_id`), purga el carrito para evitar conflictos de precio y recarga la página. |
