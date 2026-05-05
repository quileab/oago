# Cómo usar Multimedia Adicional (Imágenes y Videos)

Este proyecto utiliza un sistema de **"Convención sobre Configuración"** para gestionar múltiples imágenes y videos por producto sin necesidad de tablas adicionales en la base de datos.

## 📁 La Convención de Nombres

El sistema busca archivos en la misma carpeta donde reside la imagen principal del producto (`image_url`).

Si la imagen principal es: `productos/01/af/abcdef12345.webp`
El sistema buscará automáticamente:
- `abcdef12345-1.webp` (Imagen extra)
- `abcdef12345-2.url` (Archivo de texto con URL de YouTube)
- `abcdef12345-3.webp` (Otra imagen)

---

## 🛠️ Gestión desde el Panel de Administración

Para gestionar estos archivos, dirígete a la edición de cualquier producto (`/product/{id}`). En la columna izquierda encontrarás la tarjeta **"Multimedia Extra"**.

### 1. Agregar Imágenes
- Utiliza el campo **"Fotos Adicionales"**.
- Puedes seleccionar múltiples archivos a la vez.
- Al guardar, el sistema:
    1. Las convierte automáticamente a formato **WebP**.
    2. Las optimiza para la web.
    3. Les asigna el siguiente índice disponible (ej. si ya existe el `-1`, creará el `-2`).

### 2. Agregar Videos de YouTube
- Haz clic en el botón **(+)** en la sección de videos.
- Pega la URL completa de YouTube (ej: `https://www.youtube.com/watch?v=dQw4w9WgXcQ`).
- Puedes añadir múltiples filas de video.
- Al guardar, se creará un archivo `.url` que contiene el enlace.

### 3. Eliminar Contenido
- En la cuadrícula de "Imágenes Existentes", pasa el ratón sobre una miniatura.
- Haz clic en el icono de la **papelera roja**.
- El archivo se eliminará físicamente del servidor y el caché se refrescará automáticamente.

---

## 🚀 Visualización en la Tienda

En la vista de detalle del producto (`/product/details/{id}`):
- Si solo hay una imagen, se muestra de forma sencilla.
- Si hay contenido extra, aparece una **Galería Interactiva**.
- Las miniaturas permiten cambiar la vista principal.
- Los videos cargan automáticamente un reproductor de YouTube embebido.

---

## 💡 Notas Técnicas
- **Caché:** Los resultados del escaneo de disco se guardan en caché por 24 horas para máximo rendimiento. Guardar el producto desde el panel de administración limpia este caché automáticamente.
- **Formatos:** Se recomienda subir imágenes en alta resolución; el sistema se encarga de la conversión.
- **Almacenamiento:** Los archivos se guardan en el disco `public` de Laravel.
