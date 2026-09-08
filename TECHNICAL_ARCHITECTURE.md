# Arquitectura Técnica - Proyecto OAGO

Este documento proporciona una descripción técnica detallada de la arquitectura, funcionalidades y flujos del sistema.

## 1. Stack Tecnológico (TALL Stack)
- **Framework PHP:** Laravel 11.x
- **Frontend Interactivo:** Livewire 3.x (incluyendo componentes Volt SFC)
- **Estilos:** Tailwind CSS con MaryUI
- **Base de Datos:** MySQL / MariaDB
- **Motor de Plantillas:** Blade

## 2. Modelos de Datos y Entidades Principales (`app/Models`)
- **User:** Entidad principal para clientes, vendedores y administradores. Incluye lógica para roles (Enum `Role`), cálculo de precios personalizados y gestión de puntos (`achievements`).
- **AltUser:** Usuarios invitados con lógica de tiempo de prueba limitado (10 días).
- **Product:** Catálogo de productos.
- **Order / OrderItem:** Gestión de pedidos y sus líneas de detalle.
- **CustomerSalesAgent:** Tabla de relación que vincula clientes con sus respectivos agentes de venta (Vendedores).
- **Setting:** Almacenamiento de configuraciones globales del sistema (Key-Value).

## 3. Lógica de Autenticación, Guards y Roles (`app/Enums/Role.php`)
El sistema utiliza un sistema dual de modelos y guards:
- `User` (`web`, `sanctum`): Clientes registrados, vendedores y administradores.
- `AltUser` (`alt`): Clientes invitados con tiempo de prueba limitado o cuentas alternas.

Roles basados en Enums:
- `ADMIN`: Acceso total al panel de administración.
- `SALES`: Agentes de venta que pueden "actuar como" clientes.
- `CUSTOMER`: Clientes finales que realizan pedidos.
- `GUEST`: Usuarios con acceso limitado y temporal.

### Reglas Críticas de Autenticación y Aislamiento de Sesión
1. **Helper Universal `current_user()`:** Se debe utilizar siempre `current_user()` en lugar de `Auth::user()`. Resuelve la precedencia de guards (`web` -> `sanctum` -> `alt`) y la suplantación de vendedores (`sales_acting_as_customer_id`).
2. **Aislamiento Estricto en Login:**
   - No se deben intercambiar ni suplantar identidades automáticamente por coincidencia de email entre tablas `users` y `alt_users` sin validación explícita de credenciales.
   - Todo inicio de sesión debe regenerar el ID de sesión (`session()->regenerate()`) y limpiar variables de impersonación residuales (`sales_acting_as_customer_id`).
   - El guard alternativo se activa exclusivamente mediante la bandera `is_alt_login` (manejada por `AuthServiceProvider`). Al loguear un usuario `web`, esta bandera debe ser eliminada.

### Impersonación de Vendedores
El sistema permite a los usuarios con rol `SALES` seleccionar un cliente de su lista asignada. Esta lógica se maneja mediante:
- **Helper `current_user()`:** (`app/Helpers/helpers.php`) Resuelve si el usuario es un vendedor actuando como cliente, devolviendo el modelo del cliente suplantado si existe una sesión activa (`sales_acting_as_customer_id`).

## 4. Middlewares Personalizados (`app/Http/Middleware`)
- **IsAdminMiddleware:** Restringe el acceso a rutas administrativas solo a usuarios con `Role::ADMIN`.
- **IsRoleMiddleware:** Middleware genérico para validar roles específicos en rutas dinámicas.

## 5. Componentes Livewire y Volt (`app/Livewire`)
El frontend es altamente dinámico gracias a Livewire:
- **WebNavbar:** Gestiona la navegación, búsqueda de clientes para vendedores y visualización de días de prueba para invitados.
- **Cart:** Maneja la lógica del carrito de compras en tiempo real, validando precios según el cliente activo.
- **WebProduct / WebProductCard:** Visualización de catálogo con precios dinámicos calculados por `getProductPrice()`.
- **Volt Components:** El proyecto utiliza componentes Volt (Single File Components) que combinan lógica PHP y vista Blade en un solo archivo para mayor agilidad.

## 6. API y Servicios (`app/Http/Controllers/Api`)
- **Retrocompatibilidad:** La API soporta internamente payloads legacy para mantener compatibilidad total con integraciones anteriores (ej. traducción automática de estructuras de pedidos).
- **ProductSearchService:** Servicio dedicado para la búsqueda avanzada de productos.
- **Controllers:** Gestión de endpoints para integración con sistemas externos o aplicaciones móviles. Públicos: `POST /api/login|register`, `GET /api/slider` (carrusel sin auth). Protegidos (Sanctum): `GET /api/customer/products[?search,category,brand,tag,featured,per_page]`, `GET /api/customer/products/{id}`, `GET /api/customer/filters`, `GET|POST /api/customer/orders` (+ `SliderService` compartido entre web y API).
- **ImageProxyController:** Controlador para gestionar la carga y redimensionamiento de imágenes de productos de forma eficiente. Cuenta con protección SSRF y una caché que almacena fallos de descarga por 1 hora para evitar cuellos de botella y reintentos innecesarios en peticiones fallidas o lentas.
- **Documentación:** Auto-generada vía Scramble en `/docs/api` y `/docs/api.json`.

## 7. Comandos de Consola (`app/Console/Commands`)
- **db:import (DataImport):** Importador universal de datos con detección dinámica de tablas y secuencia inteligente de múltiples archivos. Muestra interactivamente los archivos `.sql` disponibles en la raíz.
- **ResetCustomerPasswords:** Utilidad para resetear credenciales de clientes de forma masiva.
- **make:deploy-zip (MakeDeployZip):** Genera un paquete ZIP de despliegue optimizado detectando el mejor método disponible (7-Zip o Nativo), ofreciendo opciones para omitir recursos de marca o de temas de clientes.

## 8. Sistema de Precios y Listas
Cada usuario (`User`) tiene asignada una `list_id` que vincula con `ListPrice`. El precio de un producto para un usuario específico se resuelve mediante la relación `list->listPrices()` en el modelo `User`.

## 9. Localización (`lang/`)
El sistema soporta multi-idioma mediante archivos de traducción en `lang/es` y `lang/en`, permitiendo que la interfaz se muestre íntegramente en español mientras el código mantiene estándares en inglés.

## 10. Pruebas (`tests/`)
Se utiliza **Pest** como framework de testing, con pruebas de feature para flujos críticos como la asignación de agentes de venta y la gestión de pedidos.

## 11. Sistema de Temas y Variantes Estacionales (`resources/views/themes`)
El sistema permite personalizar el frontend para diferentes clientes sin modificar el código base común:
- **`APP_THEME`**: Define el tema principal del cliente (p. ej. `cliente_a`). Las vistas ubicadas en `resources/views/themes/{APP_THEME}/` sobreescriben automáticamente a las de `resources/views/`.
- **`APP_THEME_VARIANT`**: Define una variante estacional o evento especial (p. ej. `navidad`, `cybermonday`). Las vistas en `resources/views/themes/{APP_THEME}/{APP_THEME_VARIANT}/` tienen máxima prioridad.
- **Rendimiento Cero-Peaje**: Funciona nativamente con el motor de vistas de Laravel (`View::prependLocation`), manteniendo el rendimiento de Blade y la compilación en caché (`php artisan view:cache`).
