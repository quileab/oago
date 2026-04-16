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

## 3. Lógica de Autenticación y Roles (`app/Enums/Role.php`)
El sistema utiliza un sistema de roles basado en Enums:
- `ADMIN`: Acceso total al panel de administración.
- `SALES`: Agentes de venta que pueden "actuar como" clientes.
- `CUSTOMER`: Clientes finales que realizan pedidos.
- `GUEST`: Usuarios con acceso limitado y temporal.

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
- **ProductSearchService:** Servicio dedicado para la búsqueda avanzada de productos.
- **Controllers:** Gestión de endpoints para integración con sistemas externos o aplicaciones móviles.
- **ImageProxyController:** Controlador para gestionar la carga y redimensionamiento de imágenes de productos de forma eficiente.

## 7. Comandos de Consola (`app/Console/Commands`)
- **ImportV1Data:** Script de migración de datos desde la versión anterior del sistema.
- **ResetCustomerPasswords:** Utilidad para resetear credenciales de clientes de forma masiva.
- **app:create-deploy-package:** (Personalizado) Genera un paquete ZIP de despliegue optimizado.

## 8. Sistema de Precios y Listas
Cada usuario (`User`) tiene asignada una `list_id` que vincula con `ListPrice`. El precio de un producto para un usuario específico se resuelve mediante la relación `list->listPrices()` en el modelo `User`.

## 9. Localización (`lang/`)
El sistema soporta multi-idioma mediante archivos de traducción en `lang/es` y `lang/en`, permitiendo que la interfaz se muestre íntegramente en español mientras el código mantiene estándares en inglés.

## 10. Pruebas (`tests/`)
Se utiliza **Pest** como framework de testing, con pruebas de feature para flujos críticos como la asignación de agentes de venta y la gestión de pedidos.
