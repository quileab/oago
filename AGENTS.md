<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.20
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- livewire/livewire (LIVEWIRE) - v3
- livewire/volt (VOLT) - v1
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v3
- phpunit/phpunit (PHPUNIT) - v11
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `livewire-development` — Develops reactive Livewire 3 components. Activates when creating, updating, or modifying Livewire components; working with wire:model, wire:click, wire:loading, or any wire: directives; adding real-time updates, loading states, or reactivity; debugging component behavior; writing Livewire tests; or when the user mentions Livewire, component, counter, or reactive UI.
- `volt-development` — Develops single-file Livewire components with Volt. Activates when creating Volt components, converting Livewire to Volt, working with @volt directive, functional or class-based Volt APIs; or when the user mentions Volt, single-file components, functional Livewire, or inline component logic in Blade files.
- `pest-testing` — Tests applications using the Pest 3 PHP framework. Activates when writing tests, creating unit or feature tests, adding assertions, testing Livewire components, architecture testing, debugging test failures, working with datasets or mocking; or when the user mentions test, spec, TDD, expects, assertion, coverage, or needs to verify functionality works.
- `tailwindcss-development` — Styles applications using Tailwind CSS v4 utilities. Activates when adding styles, restyling components, working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors, typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle, hero section, cards, buttons, or any visual/UI changes.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan

- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging

- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.
- Use the `database-schema` tool to inspect table structure before writing migrations or models.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - `public function __construct(public GitHub $github) { }`
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<!-- Explicit Return Types and Method Params -->
```php
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
```

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== livewire/core rules ===

# Livewire

- Livewire allows you to build dynamic, reactive interfaces using only PHP — no JavaScript required.
- Instead of writing frontend code in JavaScript frameworks, you use Alpine.js to build the UI when client-side interactions are required.
- State lives on the server; the UI reflects it. Validate and authorize in actions (they're like HTTP requests).
- IMPORTANT: Activate `livewire-development` every time you're working with Livewire-related tasks.

=== volt/core rules ===

# Livewire Volt

- Single-file Livewire components: PHP logic and Blade templates in one file.
- Always check existing Volt components to determine functional vs class-based style.
- IMPORTANT: Always use `search-docs` tool for version-specific Volt documentation and updated code examples.
- IMPORTANT: Activate `volt-development` every time you're working with a Volt or single-file component-related task.

=== pint/core rules ===

# Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.
- CRITICAL: ALWAYS use `search-docs` tool for version-specific Pest documentation and updated code examples.
- IMPORTANT: Activate `pest-testing` every time you're working with a Pest or testing-related task.

=== tailwindcss/core rules ===

# Tailwind CSS

- Always use existing Tailwind conventions; check project patterns before adding new ones.
- IMPORTANT: Always use `search-docs` tool for version-specific Tailwind CSS documentation and updated code examples. Never rely on training data.
- IMPORTANT: Activate `tailwindcss-development` every time you're working with a Tailwind CSS or styling-related task.

=== livewire/volt rules ===

# Livewire Volt

- Single-file Livewire components: PHP logic and Blade templates in one file.
- Always check existing Volt components to determine functional vs class-based style.
- IMPORTANT: Always use `search-docs` tool for version-specific Volt documentation and updated code examples.
- IMPORTANT: Activate `volt-development` every time you're working with a Volt or single-file component-related task.
</laravel-boost-guidelines>

---

## OAGO — Project Context

> Auto-generated context for AI agents. Updated: 2026-06-27.

---

### Domain & Purpose

OAGO is a **B2B e-commerce catalog** (TALL stack). Key actors:
- **Admin** — full system control (products, users, price lists, achievements, settings, slider, logs).
- **Sales agent** — manages assigned customers, can **impersonate** them to place orders on their behalf.
- **Customer** — browses catalog with personalized pricing, adds to cart, places orders.
- **Guest / AltUser** — trial access with configurable TTL; activates via email token.
- UI language is **Spanish**; code language is **English**.
- REST API (Sanctum) with auto-generated OpenAPI docs via Scramble at `/api/documentation`.

---

### Auth System — CRITICAL

**Two user types, two guards, one helper:**

| Type | Model | Guard | Table |
|------|-------|-------|-------|
| Main users | `User` | `web`, `sanctum` | `users` |
| Guest/trial | `AltUser` | `alt` | `alt_users` |

**Always use `current_user()` instead of `Auth::user()`.**
- Checks guards in order: `web` → `sanctum` → `alt`.
- If role is `sales` AND `session('sales_acting_as_customer_id')` is set → returns the impersonated `User` transparently.
- `AuthServiceProvider` sets the `alt` guard as default when `session('is_alt_login')` is present.

**Roles** (`App\Enums\Role` — string-backed enum):
| Case | Value | Notes |
|------|-------|-------|
| `None` | `'none'` | No access |
| `Guest` | `'guest'` | Time-limited trial; checked by `CheckGuestExpiration` middleware |
| `Customer` | `'customer'` | Catalog + orders + profile |
| `Sales` | `'sales'` | Customer management + impersonation |
| `Admin` | `'admin'` | Full access |

**Sales impersonation flow:**
1. Sales logs in → `WebNavbar` auto-selects first assigned customer on mount.
2. `setActingCustomer($id)` validates via `getManagedCustomersQuery()` → stores in session.
3. `current_user()` returns the customer transparently.
4. Cart is **cleared** on customer switch to prevent price conflicts.

**Guest trial:**
- `AltUser` with `role=guest` and `!is_internal` → `CheckGuestExpiration` middleware checks TTL.
- TTL from `SettingsHelper::settings('guest_access_ttl_days', 10)`.
- Internal users (`is_internal=true`) never expire.
- Activation: `GET /activate-account/{token}` → changes role to `customer`.

---

### Data Model & Relationships

```
users ──────────────────────────── list_names (list_id FK)
  │                                    │
  ├─ orders ── order_items              ├─ list_prices (product_id + list_id)
  │               │                    │       │
  │               └─ products ─────────┘       └─ products
  │
  ├─ achievables (morph) ── achievements
  │
  └─ customer_sales_agents (customer_id FK) ←── sales_agent morph (User|AltUser)

alt_users ─ same structure as users ─── alt_orders ── alt_order_items
                                              └─ shipping_details (also for orders)
```

**Key model details:**

`User` / `AltUser` (shared traits: `HasPricingList`, `HasAchievements`, `HasProfileData`, `ManagesCustomers`):
- `fullName` accessor → `"Lastname, Name"`
- `list()` → `BelongsTo(ListName, 'list_id')`
- `achievements()` → `MorphToMany(Achievement, 'achievable')`
- `assignedCustomers()` → `MorphMany(CustomerSalesAgent, 'sales_agent')` (only on sales role)
- `getManagedCustomersQuery()` → admin sees all, sales sees only assigned

`Product`:
- `hasBonus()` / `getBonusLabelAttribute()` — e.g. `"23 + 1 off !!"`
- `getMediaAttribute()` — cached array of images + YouTube thumbnails (`product_media_{id}`, 1 day TTL)
- `qtty_package` — package size for bulk pricing
- `published`, `featured`, `visibility` — display control
- Cache cleared on `save` / `delete` via model boot

`Order` / `AltOrder`:
- `Order::placeOrder(array $shipping)` → delegates to `OrderService::placeOrder()`
- status cast to `OrderStatus` enum
- `shipping()` → `HasOne(ShippingDetail)`

`ListName`:
- Lists ending in `" U"` are **unit price lists** that resolve back to a base list

`Setting`:
- `key`, `value`, `type` (`number|boolean|json|string`), `text`, `description`

---

### Helpers

**`app/Helpers/helpers.php`** (auto-loaded):

```php
// Always use instead of Auth::user(). Handles guards + sales impersonation.
function current_user(): User|AltUser|null

// Returns "web_{userId}" or "alt_{userId}" — cart JSON file key.
function current_user_cart_id(): string|null
```

**`app/Helpers/SettingsHelper.php`** (auto-loaded):

```php
SettingsHelper::settings(string $key, mixed $default = null): mixed
// Cached forever per key. Cast by 'type': number→float, boolean→bool, json→array.

SettingsHelper::update_setting(string $key, mixed $value): void
// UpdateOrCreate + flushes cache for that key.

SettingsHelper::getProductTags(): array
// Shortcut for settings('product_tags', []).
```

**Known settings keys:**
| Key | Type | Default | Purpose |
|-----|------|---------|---------|
| `guest_access_ttl_days` | number | 10 | Guest trial duration |
| `order_placed_mail` | string | — | Admin email CC on orders |
| `product_tags` | json | [] | Available product tags |
| `image_proxy_allowed_hosts` | json | [] | SSRF whitelist for image proxy |

---

### Pricing System (complex — read carefully)

1. Every user has `list_id` → `ListName`.
2. Lists ending in `" U"` = unit price lists (resolve to base list).
3. `PriceListService::getEffectivePrice($listId, $productId)` — resolves bulk vs unit.
4. `PriceListService::calculateItemPrice($listId, Product, $quantity)`:
   - Full packages → `price` (bulk); remainder units → `unit_price`.
   - Package size from `Product::qtty_package`.
5. `ProductSearchService::hydratePrices()` — bulk loads list prices for paginated results (N+1 prevention).
6. `User::getProductPrice(Product)` (via `HasPricingList`) — single product lookup.

**Bonus/offer system:**
- `Product::bonus_threshold > 0` AND `bonus_amount > 0` → quantity offer active.
- Billable qty = ordered qty − free units (calculated in `Cart` and `OrderService`).

---

### Cart

- Session-based (`session('cart')` keyed by `product_id`).
- Also persisted to `storage/app/private/{cart_id}_cart.json`.
- Cart ID = `current_user_cart_id()`.
- **Cleared on customer switch** (sales impersonation) to prevent price conflicts.

---

### Order Flow

1. `Cart` validates → `OrderService::validateCart()` (stock, price drift, bonus).
2. DB transaction: create/update `Order`|`AltOrder`, bulk-insert `OrderItem`|`AltOrderItem`, upsert `ShippingDetail`.
3. Queued `OrderMail` sent (user + optional admin CC from `order_placed_mail` setting).
4. Session + JSON cart file cleared.
5. Redirect to `route('ordersuccess')`.

---

### Middleware

Registered in `bootstrap/app.php`:

| Alias | Class | Behavior |
|-------|-------|----------|
| `is_admin` | `IsAdminMiddleware` | `current_user()->role->value === 'admin'` → else abort(404) |
| `is_role` | `IsRoleMiddleware` | Variadic: `is_role:admin,sales` |
| `check_guest` | `CheckGuestExpiration` | Logs out expired guest AltUser accounts |
| `ApiLoggerMiddleware` | (prepended to api) | Logs all API requests; masks secrets on 4xx/5xx |
| `StartSession` | (prepended to api) | Session available in API routes (needed for impersonation) |

---

### Route Groups Summary

```
Public:           /login, /register, /about, /activate-account/{token}, /proxy-image
Auth+check_guest: /user/profile, /orders, /alt-orders, /checkout, /product/details/{id?}, etc.
Admin-only:       /users, /products, /dashboard, /settings, /logs, /slider, /export/*
API public:       POST /api/register, POST /api/login
API Sanctum:      /api/user, /api/users/{user}
API admin:        /api/orders/*, /api/products/*, /api/list-prices/*, /api/users
```

---

### Components Map

**Standard Livewire (`app/Livewire/`):**
| Class | Purpose |
|-------|---------|
| `Cart` | Cart add/remove/update, bonus calc, JSON persistence |
| `Dashboard` | Admin charts (ApexCharts), top-5 products |
| `WebNavbar` | Nav, sales customer switcher, trial counter |
| `WebProductCard` | Product card with qty controls, dispatches `addToCart` |
| `WebSearchFilter` | Filters: category, brand, tag, text → dispatches `updateProducts` |

**Livewire Traits (`app/Livewire/Traits/`):**
- `ManagesModelCrud` — generic `save()` with Toast + optional redirect.
- `ManagesModelIndex` — generic `delete()`, paginated search with `searchableColumns`.

**Volt Single-File Components** (class-based style, in `resources/views/livewire/`):
- Auth: `login`, `register`
- Orders: `orders`, `alt-orders`, `orderitems`, `alt-orderitems`, `checkout`
- Products (admin): `products/index`, `products/crud`, `products/extras`, `products/lists`
- Users (admin): `users/index`, `users/crud`, `users/profile`, `users/sales-assign`, `users/sales-assigned`, `users/alts/index`, `users/alts/crud`
- Catalog: `web-product-detail`, `webproductsmain`, `webslider`, `cart`
- Other: `achievements/*`, `assign-achievement`, `settings/crud`, `admin/logs`, `slider`, `dashboard`

**Blade Layouts** (`resources/views/components/layouts/`):
- `app.blade.php` — full layout (navbar + cart)
- `clean.blade.php` — minimal (login/register)
- `empty.blade.php` — bare

**View Components** (`app/View/Components/`):
- `AppBrand` — logo/brand
- `ImageProxy` → `<x-image-proxy>` — wraps proxy URL

---

### Services

| Service | Key Methods |
|---------|-------------|
| `OrderService` | `placeOrder()`, `validateCart()` |
| `PriceListService` | `getEffectivePrice()`, `calculateItemPrice()` |
| `ProductSearchService` | `hydratePrices()` (N+1 prevention for catalog) |

---

### Mail & Jobs

- `OrderMail` — order confirmation; queued; user + admin CC.
- `AltUserWelcomeMail` — welcome + activation link for new AltUser.

---

### Console Commands

| Command | Purpose |
|---------|---------|
| `CreateAdminUser` | Creates admin user interactively |
| `DataImport` | Legacy v1 migration |
| `MakeDeployZip` | Builds optimized deployment ZIP |
| `NormalizePriceListsCommand` | Normalizes price list data |
| `ResetCustomerPasswords` | Bulk password reset |

---

### Key Packages

| Package | Purpose |
|---------|---------|
| `robsontenorio/mary` ^2.0 | MaryUI components — use for all UI elements |
| `dedoc/scramble` ^0.12 | Auto OpenAPI docs → `/api/documentation` |
| `laravel/pail` ^1.2 | Log tailing in dev |
| `daisyui` ^5.0 | Tailwind component themes |
| `apexcharts` ^5.3 | Dashboard charts |
| `sortablejs` ^1.15 | Drag-and-drop sorting (slider, etc.) |

---

### Test Coverage (`tests/Feature/`)

Existing tests (do **not** delete without approval):
`ApiOrderUpdateTest`, `AuthSecurityTest`, `CartDeepTest`, `CartPersistenceTest`, `CheckoutTest`, `ComponentTest`, `CustomerSalesAgentTest`, `ImageProxySecurityTest`, `OrderStatusValidationTest`, `OrderTotalConsistencyTest`, `OrderValidationTest`, `PermissionsTest`, `ProductSearchServiceTest`, `UserControllerEscalationTest`, `WebProductsMainTest` + `tests/Feature/Api/`

---

### Database Tables

| Table | Model | Notes |
|-------|-------|-------|
| `users` | `User` | Main users |
| `alt_users` | `AltUser` | Guest/trial users; has `activation_token` |
| `products` | `Product` | Catalog |
| `list_names` | `ListName` | Price list groups |
| `list_prices` | `ListPrice` | Per-product, per-list pricing |
| `orders` | `Order` | Main user orders |
| `alt_orders` | `AltOrder` | AltUser orders |
| `order_items` | `OrderItem` | |
| `alt_order_items` | `AltOrderItem` | |
| `shipping_details` | `ShippingDetail` | Shared by both order types |
| `achievements` | `Achievement` | data cast to array |
| `achievables` | — | Polymorphic pivot (User, AltUser) |
| `customer_sales_agents` | `CustomerSalesAgent` | Polymorphic sales_agent (User\|AltUser) |
| `settings` | `Setting` | App config via `SettingsHelper` |
| `cache`, `jobs`, `sessions` | — | Laravel infrastructure |
| `personal_access_tokens` | — | Sanctum |

---

### External Integrations

- **YouTube**: thumbnails only — `https://img.youtube.com/vi/{videoId}/mqdefault.jpg`
- **Image Proxy** (`ImageProxyController`): fetches/resizes remote product images; SSRF-protected (no private IPs, host whitelist from `image_proxy_allowed_hosts` setting).

