# Características del Proyecto "oago"

Este documento detalla las características principales del proyecto web "oago", una aplicación de comercio electrónico y catálogo basada en API, construida sobre el stack TALL.

## 🚀 Visión General

"oago" es una aplicación web moderna y reactiva diseñada para funcionar como un sitio de catálogo y comercio electrónico, con una sólida base de API. Se centra en ofrecer una experiencia de usuario fluida y una arquitectura escalable.

## 🛠️ Stack Tecnológico

El proyecto se basa en el **TALL stack**, una combinación de tecnologías populares para el desarrollo web:

*   **T**ailwind CSS: Un framework CSS utilitario para un diseño rápido y personalizado.
*   **A**lpine.js: Un framework JavaScript ligero para añadir interactividad a las vistas de forma declarativa.
*   **L**aravel: Un potente framework de PHP para el desarrollo backend.
*   **L**ivewire: Un framework de pila completa para Laravel que permite construir interfaces dinámicas usando PHP.

## 🛍️ Gestión de Productos

*   **Atributos y Etiquetas:** Sistema flexible de etiquetas para gestionar estados como "Destacado", "Publicado" y otros atributos personalizados.
*   **Ofertas por Cantidad:** Funcionalidad nativa para configurar bonificaciones por volumen (ej: "Compra 23 y lleva 1 de regalo"). El sistema calcula automáticamente las unidades bonificadas en el carrito sin afectar el precio unitario base.

## 👥 Roles y Permisos

La aplicación maneja roles definidos (`admin`, `sales`, `customer`, `guest`) para controlar el acceso:

*   **Admin:** Acceso total al sistema.
*   **Sales (Vendedores):**
    *   Pueden gestionar sus propios clientes asignados.
    *   Capacidad de "impersonar" (actuar en nombre de) sus clientes para realizar pedidos o revisar su historial.
    *   Acceso a rutas de pedidos filtradas según el cliente que están gestionando.
*   **Customer:** Acceso a su propio catálogo, precios personalizados y pedidos.

## 📦 Backend (Laravel)

El backend de la aplicación se construye con Laravel, siguiendo sus convenciones idiomáticas:

*   **Modelos Eloquent:** Ubicados en `app/Models`, se mantienen "delgados" (lean models). La lógica de negocio compleja se delega a servicios o clases de acción.
*   **Controladores:** Localizados en `app/Http/Controllers`, manejan la lógica de las peticiones HTTP.
*   **Componentes Livewire:** Una parte significativa de la interactividad del frontend se gestiona mediante componentes Livewire en `app/Livewire`. El proyecto utiliza **Volt**, un sistema para componentes basados en clases donde la lógica y la vista Blade residen en el mismo archivo.
*   **Base de Datos:** Se utiliza Laravel Migrations (`database/migrations`) para la gestión del esquema de la base de datos y Eloquent ORM para la interacción con la misma.
*   **Rutas:** Las rutas web se definen en `routes/web.php` y las rutas API en `routes/api.php`.
*   **Configuración:** Las variables de entorno se gestionan mediante `.env` y la configuración de la aplicación a través de los archivos `config/*.php`.

## 🎨 Frontend

La interfaz de usuario y la experiencia de usuario (UX) son fundamentales en "oago":

*   **Componentes UI:** El proyecto emplea los componentes de la biblioteca **MaryUI** para construir interfaces consistentes y visualmente atractivas.
*   **Estilismo:** Se utiliza **Tailwind CSS** para una estilización eficiente y altamente personalizable. El archivo CSS principal es `resources/css/app.css`.
*   **JavaScript:** El JavaScript del frontend se encuentra en `resources/js`. **Vite** se utiliza para el empaquetado y la compilación de activos.
*   **Vistas:** Las plantillas Blade se ubican en `resources/views`, con componentes Livewire en `resources/views/livewire`.

### 🖼️ Gestión de Imágenes y Recursos Visuales

*   **Fallback Global (`fallback.webp`):** Se utiliza una imagen centralizada (`public/imgs/fallback.webp`) con propósitos múltiples para asegurar la consistencia visual y mitigar errores en la UI:
    *   **Imágenes Rotas/Ausentes:** Funciona como imagen de reemplazo automática (a través de `ImageProxyController` y el componente `<x-image-proxy>`) cuando la imagen de un producto no existe, su formato es inválido o no puede ser descargada desde orígenes remotos.
    *   **Favicon:** Se utiliza como el ícono principal de la aplicación en las pestañas del navegador, estando presente en todos los layouts base (`app.blade.php`, `index.blade.php`, `clean.blade.php`, `empty.blade.php`).

## 🧪 Pruebas

La calidad del código se asegura mediante pruebas:

*   **Framework de Pruebas:** Se utiliza **Pest** para escribir y ejecutar pruebas.
*   **Ubicación:** Los archivos de prueba se encuentran en el directorio `tests/`.
*   **Ejecución:** Las pruebas se ejecutan mediante el comando `php artisan test`.

## 🗣️ Idioma

*   El código, los comentarios y los mensajes de commit se mantienen en **inglés**.
*   El texto de cara al usuario en la interfaz se gestiona a través de los archivos de localización de Laravel en `resources/lang`, con preferencia por el **español** en la interfaz.
