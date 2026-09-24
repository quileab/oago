# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

**Co-primarios (éxito compartido):**

- **Customer B2B (comercio minorista / kiosco):** Repone stock periódicamente desde catálogo mayorista. Navega con precio personalizado por lista, agrega por bulto/unidad, aprovecha bonus por cantidad, y cierra pedido con datos de envío. Valora rapidez, precio correcto y claridad de ofertas.
- **Sales Agent (vendedor de Agostini):** Gestiona cartera de clientes asignados (`customer_sales_agents` polimórfico). Impersona transparentemente (`session('sales_acting_as_customer_id')` → `current_user()`) para cotizar y cargar pedidos en nombre del cliente, con carrito aislado por identidad y limpieza al cambiar de cliente.

**Secundarios:**

- **Admin (Distribuidora Agostini):** Control total — productos, listas de precios, usuarios (User/AltUser), pedidos, logros, settings, slider y logs. Define TTL de prueba, listas promo y comportamiento de catálogo.
- **Guest / AltUser (trial):** Acceso limitado por tiempo (`guest_access_ttl_days`, default 10 días; `is_internal` nunca expira). Se activa vía token `/activate-account/{token}` y opera contra tablas espejo `alt_orders`/`alt_order_items`. Puede o no ver precios según `show_prices_to_guests`.

Idioma UI: español. Idioma código: inglés.

## Product Purpose

OAGO es el **catálogo y e-commerce B2B white-label** de Distribuidora Agostini, construido sobre TALL stack. Existe para digitalizar la reposición mayorista: reemplazar pedidos por WhatsApp/planilla con un flujo self-service confiable, con pricing por cliente y operación asistida por vendedores.

Éxito = cliente encuentra el producto, ve su precio real (bulto vs unidad + bonus), carga sin fricción y el pedido llega consistente a administración; vendedor resuelve en segundos sin fugas de datos entre clientes; admin opera catálogo y precios sin soporte técnico.

## Positioning

Un e-commerce B2B genérico no podría copiar OAGO sin reimplementar tres mecanismos entrelazados:

1. **Precio como identidad:** cada `User`/`AltUser` tiene `list_id → ListName`; listas terminadas en `" U"` son unitarias derivadas. `PriceListService::calculateItemPrice()` resuelve bulto ( `price` ) vs unidad ( `unit_price` ) según `Product.qtty_package` y resto. `ProductSearchService::hydratePrices()` hidrata en bloque para evitar N+1.
2. **Impersonación con aislamiento real:** sales actúa como customer sin romper guards (`web`/`sanctum`/`alt` + `current_user()`/`current_user_cart_id()`), con carrito en sesión + JSON `storage/app/private/{cart_id}_cart.json` limpiado al cambiar de cliente.
3. **White-label por temas:** `resources/views` es motor core; `resources/views/themes/{theme}` lo overridea vía `View::prependLocation()`. Permite clonar la misma lógica para nuevos clientes (p. ej. `encendido`, `encendidorqta`) sin tocar el core. Tema activo define navbar, cards, detalle, slider y footer sin fork del backend.

## Operating Context

- **Workflows clave:** (1) Customer self-service: búsqueda filtrada (`category`, `brand`, `tag`, `featured`, texto) → `webproductsmain` + `web-search-filter` → `web-product-card` → `Cart` → `checkout` → `OrderService::placeOrder()` en transacción (valida stock/drift/bonus, crea `Order`/`AltOrder` + `OrderItem` + `ShippingDetail`, encola `OrderMail`, limpia carrito). (2) Sales asistido: `WebNavbar` auto-selecciona primer cliente asignado → `setActingCustomer()` → opera como customer. (3) Guest trial: `AltUserWelcomeMail` → activación → expiración vigilada por `CheckGuestExpiration`.
- **Entorno:** web desktop/mobile, Tailwind v4 + DaisyUI + MaryUI 2.x, Vite build. Uso en depósito/mostrador con conectividad variable; imágenes externas via `ImageProxyController` con whitelist SSRF (`image_proxy_allowed_hosts`) y cache de fallos 1h.
- **Herramientas integradas:** ApexCharts (Dashboard top-5), SortableJS (slider), Scramble OpenAPI en `/docs/api`, Sanctum API (`/api/customer/products`, `/api/customer/orders`, etc.).
- **Rituales operativos:** `php artisan app:sync-settings` (sincroniza `config/default_settings.php` → `settings`), `php artisan db:import` lo invoca; `queue:work` para mails; `fallback.webp` como imagen y favicon global.

## Capabilities and Constraints

**Capacidades confirmadas:**
- TALL + Volt single-file components (class-based) + MaryUI; `app/Livewire` (Cart, Dashboard, WebNavbar, WebProductCard, WebSearchFilter) y `resources/views/livewire/**` (auth, orders, productos, users, catálogo).
- Doble modelo espejo: `User`/`AltUser` comparten traits `HasPricingList`, `HasAchievements`, `HasProfileData`, `ManagesCustomers`; dos pipelines de pedidos y pricing.
- Ofertas por cantidad (`bonus_threshold`/`bonus_amount`) con cálculo de unidades bonificadas sin alterar precio base.
- Settings tipados (`number`/`boolean`/`json`/`string`) cacheados forever vía `SettingsHelper`; claves conocidas: `guest_access_ttl_days`, `show_prices_to_guests`, `alt_user_default_price`, `promo_list_id`, `order_placed_mail`, `catalog_display_mode` (`infinite_scroll`/`paginated`), `catalog_items_per_page`, `product_tags`, `image_proxy_allowed_hosts`, datos de empresa y redes.
- Middleware: `is_admin`, `is_role`, `check_guest`, `ApiLoggerMiddleware`, `StartSession` en API.

**Restricciones / decisiones explícitas:**
- No usar `Auth::user()` en vistas/Livewire; siempre `current_user()` para respetar `alt` guard e impersonación.
- Separación estricta de guards: no vincular `AltUser ↔ User` por email sin autenticación explícita del guard correspondiente.
- Limpieza de sesión en login: `web` regenera y borra `is_alt_login`/`sales_acting_as_customer_id`; `alt` regenera, setea `is_alt_login=true` y borra impersonación.
- Nunca modificar `resources/views/` para un cliente; copiar a `themes/{theme}/` y editar allí.
- Performance: paginación hídrica de precios, cache de `product_media_{id}` (1 día), límite de eager load nativo Laravel 12.
- Stack existente no se migra sin aprobación. Pest 4 / PHPUnit 11 para tests (`tests/Feature`).

**Indecisos registrados:** precio default para futuros verticales fuera de Agostini; estrategia de internacionalización más allá de español; alcance de logros/achievements como gamificación.

## Brand Commitments

- **Nombre y voz:** Distribuidora Agostini (`company_name` default). Tono B2B argentino, directo, en español. Código y commits en inglés.
- **Activos:** `public/imgs/fallback.webp` como fallback e ícono; logos vía `<x-app-brand>`; `company_phone`, `company_email`, `company_address`, `company_map_iframe`, `social_networks`, `copyright` gestionados por settings.
- **Temas vivos:** `encendido` y `encendidorqta` son implementaciones de referencia; cualquier cambio a `login.blade.php`, `web-product-card.blade.php`, `web-product-detail.blade.php` debe reflejarse en temas activos.
- **Paleta/tipografía:** no hay rebranding aprobado; preservar DaisyUI/Tailwind base hasta que DESIGN.md lo reemplace explícitamente. No inventar testimonios, métricas ni claims de deployment.

## Evidence on Hand

- Código TALL funcional: `routes/web.php` (login, register, about, activate-account, proxy-image, profile/orders/checkout/product/details), `routes/api.php` (Sanctum + admin), `app/Models` (User, AltUser, Product, ListName, ListPrice, Order/AltOrder, etc.), `app/Services` (OrderService, PriceListService, ProductSearchService, SliderService), `app/Helpers` (helpers.php, SettingsHelper.php).
- Vistas: `resources/views/livewire/**`, `resources/views/themes/encendido/**`, `resources/views/themes/encendidorqta/**`, layouts `app`/`clean`/`empty`.
- Tests existentes: `ApiOrderUpdateTest`, `AuthSecurityTest`, `CartDeepTest`, `CartPersistenceTest`, `CheckoutTest`, `CustomerSalesAgentTest`, `ImageProxySecurityTest`, etc. (no borrar sin aprobación).
- Docs: `README.md`, `project_characteristics.md`, `AGENTS.md`, `config/default_settings.php`.
- Ausencias que no deben fabricarse: métricas de conversión reales, NPS, testimonios de clientes, benchmarks de performance en producción.

## Product Principles

1. **Precio correcto o nada:** cada render de precio debe resolver lista efectiva, bulto/unidad y bonus de forma idempotente; preferimos no mostrar precio que mostrar uno equivocado.
2. **Aislamiento antes que conveniencia:** guards, carrito y sesión nunca fugan datos entre identidades; la impersonación es transparente solo hacia arriba (`current_user()`), nunca entre clientes.
3. **Core intacto, tema expresa:** la lógica vive en `resources/views` y `app/`; la identidad de cada cliente vive en su tema. No bifurcar el motor para un detalle visual.
4. **B2B primero, no retail disfrazado:** optimizar para reposición rápida (búsqueda, bulk, repetir pedido) sobre descubrimiento aspiracional.
5. **Operable por no-desarrolladores:** admin cambia catálogo, listas, slider y settings sin deploys; el sistema debe seguir vendiendo aunque el asset remoto falle (proxy + fallback).

## Accessibility & Inclusion

Requisito implícito web: contraste y navegación por teclado vía DaisyUI/MaryUI, formularios validados con Form Requests y mensajes en español. Sin estándar formal declarado (p. ej. WCAG 2.1 AA); registrar como deuda auditar y fijar nivel objetivo en próximo DESIGN.md si el cliente lo exige.
