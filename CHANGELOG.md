# Changelog

Este documento resume los cambios significativos entre la rama `v1` y la rama `main` del proyecto "oago", incluyendo las mejoras de seguridad y características recientes implementadas en el entorno de desarrollo actual.

## Cambios Recientes (Actualizaciones de Seguridad y Funcionalidad)

### ⚙️ Sistema y Configuración

*   **Colas de Trabajo (Queues):** Se corrigió la configuración de Supervisor en producción (`user=oagostini`) y se estableció la rotación de logs para evitar el consumo excesivo de disco.

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