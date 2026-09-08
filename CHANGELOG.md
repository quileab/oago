# Changelog

Este documento resume los cambios significativos entre la rama `v1` y la rama `main` del proyecto "oago", incluyendo las mejoras de seguridad y características recientes implementadas en el entorno de desarrollo actual.

## Cambios Recientes (Actualizaciones de Seguridad y Funcionalidad)

### 🛠️ Mejoras Técnicas y Búsqueda de Productos

*   **Ordenamiento de Productos por Defecto:** Se actualizó la lógica de búsqueda (`ProductSearchService`) para que, al no haber filtros activos, se prioricen los productos destacados (`featured`) y los actualizados recientemente (`updated_at`).
*   **Actualización de Timestamps:** Se aseguró que la fecha de actualización (`updated_at`) de los productos se modifique (touch) al actualizar sus etiquetas, precios de lista o promociones (extras), para reflejarse correctamente en el ordenamiento.
*   **Comando db:import (DataImport):** Se mejoró el comando `php artisan db:import` para listar los archivos `.sql` disponibles en la raíz del proyecto cuando no se proporciona un nombre de archivo por argumento.
*   **Optimización del ImageProxy frente a Timeouts/Conexiones Lentas:**
    *   Se implementó una caché de fallos de descarga por 1 hora (`image_proxy_failed_{md5}`) para evitar cuellos de botella e hilos de PHP bloqueados debido a reintentos repetitivos sobre servidores externos caídos o extremadamente lentos (como `cURL error 28: Operation timed out`).
    *   Se cambió el nivel de log para errores de timeout y conexión externa de `warning` a `info` para evitar alertas redundantes que contaminen los registros de producción.
### ⚙️ Sistema y Configuración

*   **Colas de Trabajo (Queues):** Se corrigió la configuración de Supervisor en producción (`user=oagostini`) y se estableció la rotación de logs para evitar el consumo excesivo de disco.
*   **Comando make:deploy-zip (MakeDeployZip):** Se documentó formalmente el comando `php artisan make:deploy-zip` (anteriormente referido con la firma obsoleta `app:create-deploy-package`), el cual genera un paquete ZIP de despliegue optimizado, ofreciendo filtros interactivos para discriminar assets de marca o exclusión de temas.

### 🔌 API REST (Compatibilidad y Optimizaciones)

*   **Retrocompatibilidad y Estándares:** 
    *   Se mejoró la API en la rama `dev` logrando una total retrocompatibilidad con las implementaciones cliente de la rama `main`, traduciendo internamente modelos de datos legacy (ej. `products` hacia `items` en `OrderController`).
    *   Se normalizaron los endpoints para mantener respuestas esperadas usando nuevos servicios como `PriceListService` por detrás.
    *   **Visibilidad de Productos Inexistentes:** Se optimizó el endpoint de visibilidad (`changeVisibility`) para realizar búsquedas estrictamente por el ID de producto y eliminar búsquedas redundantes o riesgosas por SKU. Si el producto ya no existe y se intenta establecer su visibilidad a `hidden`, el endpoint responde con `200 OK` (no-op) en lugar de un error `404`, evitando falsas alarmas de fallo de API en producción.
    *   Se restauraron estrictos tipados de retorno en controladores (Ej. `: JsonResponse`) para cumplir los lineamientos del proyecto en PHP 8.4 y evitar quiebres arquitectónicos.
*   **App Móvil (Flutter) — Slider y Catálogo:**
    *   **Slider público:** Nuevo endpoint `GET /api/slider` (sin autenticación) para imágenes del carrusel; el existente `GET /api/customer/slider` (Sanctum) ahora delega en `SliderService` que unifica la lectura de `slider.json` (`slider_public` + fallback `storage/slider`).
    *   **Filtros extendidos:** `GET /api/customer/products` ahora soporta `brand`, `tag` (slug), `featured` (boolean) además de `category`/`search`/`per_page`.
    *   **Detalle y vocabularios:** Nuevos `GET /api/customer/products/{id}` (precio efectivo + tags) y `GET /api/customer/filters` (`{categories, brands, tags: [{slug,name}]}`).
    *   **Documentación:** Expuestos automáticamente en Scramble (`/docs/api`, `/docs/api.json`).

### 🛍️ Productos y Ofertas

*   **Sistema de Bonificaciones (Descuento por Cantidad):**
    *   Se implementó la lógica para manejar ofertas tipo "23 + 1 de regalo".
    *   Nuevos campos `bonus_threshold` y `bonus_amount` en la tabla `products`.
    *   Cálculo automático en el carrito de compras (`Cart.php`) para reflejar las unidades bonificadas en el total.
    *   Visualización de etiquetas de oferta en el listado del carrito.
    *   Gestión de estos campos desde el panel de administración (`products.extras`).

### 🎨 Mejoras de Interfaz (UI/UX)

*   **Gestión de Atributos de Producto:**
    *   Se rediseñó el panel de "Atributos" (`products.extras`) para usar un botón cíclico de 3 estados (Ignorar -> Aplicar -> Remover) en lugar de grupos de radio buttons, mejorando la usabilidad y el espacio.
    *   Se amplió el ancho del drawer de atributos (`lg:w-1/2`) para mayor comodidad.

### 👥 Roles y Permisos

*   **Acceso de Vendedores a Pedidos:**
    *   Se habilitó el acceso a la ruta `/orders` para el rol `sales` (Vendedores).
    *   Se actualizó la lógica de filtrado de pedidos para soportar la **impersonación** de clientes por parte de vendedores, utilizando el helper `current_user()` para resolver correctamente la identidad del usuario activo.

### 🐛 Correcciones de Errores
 
*   **Aislamiento de Sesión y Autenticación Dual (User vs AltUser):**
    *   Se eliminó la suplantación automática entre `alt_users` y `users` por coincidencia de email en el flujo de inicio de sesión (`login.blade.php`), previniendo que un cliente acceda con datos de otro.
    *   Se forzó la regeneración de sesión y la limpieza de flags residuales (`is_alt_login`, `sales_acting_as_customer_id`) en cada autenticación exitosa.
    *   Se estandarizó el uso del helper `current_user()` en lugar de `Auth::user()` en componentes clave (`users.profile`, `web-product-card`, `web-product-detail`) y en los temas asociados.
*   **Creación de Usuarios Alternativos (AltUser):** Se solucionó un error SQL (`Field 'password' doesn't have a default value`) al crear nuevos usuarios alternativos, generando ahora una contraseña aleatoria segura automáticamente si no se proporciona una.

---

### 🛡️ Seguridad (API y Modelos)

*   **Protección contra Escalada de Privilegios:**
    *   Se implementó protección de Asignación Masiva (Mass Assignment) en los modelos `User` y `AltUser` cambiando `$guarded = []` por `$fillable = [...]`. Esto evita que usuarios malintencionados se asignen el rol de `admin` a través de la API.
    *   Se endureció el `UserController` para que solo los administradores puedan modificar el rol de un usuario.
    *   El método `store` de la API ahora asigna por defecto el rol `customer` a nuevos registros, ignorando intentos de establecer roles superiores sin autorización.

*   **Mitigación de Vulnerabilidades (IDOR & SSRF):**
    *   **IDOR (Insecure Direct Object Reference):** Se añadieron verificaciones en los métodos `show`, `update` y `destroy` del `UserController`. Ahora, los usuarios solo pueden acceder y modificar su propio perfil, mientras que los administradores mantienen acceso global.
    *   **SSRF (Server-Side Request Forgery):** Se aseguró el `ImageProxyController` validando esquemas de URL (solo http/https) y bloqueando direcciones IP privadas/locales para prevenir el escaneo de la red interna.
    *   **Eliminación de Código Vulnerable:** Se eliminó el script heredado `public/qb/proxyImg.php` que contenía una vulnerabilidad crítica de Inclusión de Archivos Locales (LFI) y SSRF.

*   **Protección de Datos:**
    *   Se protegió el modelo `Order` contra asignación masiva, definiendo explícitamente los campos `$fillable` según el esquema de la base de datos.
    *   Se aumentó la longitud mínima de contraseña requerida a 8 caracteres en la API.

### 👥 Gestión de Usuarios y Roles ("Sales")

*   **Soporte de Roles:** La aplicación y la API soportan explícitamente el rol `sales` (Ventas), permitiendo diferenciar entre clientes regulares, invitados, administradores y agentes de ventas en la lógica de negocio y permisos.
*   **Asignación de Vendedores:** (Observado en componentes) Funcionalidad para asignar vendedores a clientes específicos (`/users/{id}/sales-assign`), permitiendo una gestión de relaciones comerciales más granular.

---

## v1.0.0 (Base de la rama `main`)

### Feat

*   Establecimiento de la nueva base de código principal del proyecto. Esta versión representa la fundación de la aplicación "oago" con la implementación inicial de sus características y estructura.

### Fix

*   Ajuste en la condición de opacidad de la imagen de la tarjeta del producto y mejora del formato. Este cambio aborda un detalle visual y de presentación en las tarjetas de producto.

---

**Nota:** Este changelog incluye tanto los cambios confirmados en el historial de Git como las modificaciones críticas de seguridad aplicadas en el entorno de desarrollo actual.