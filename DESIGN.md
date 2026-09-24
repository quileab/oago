---
name: OAGO — EncendidorQTA
description: Catálogo B2B dual-brand (ER/CIBAT) técnico y ordenado para reposición mayorista.
colors:
  cibat-navy: "#1b365d"
  cibat-blue: "#3d548f"
  er-red: "#a6282e"
  er-red-bright: "#cc0000"
  secondary-burgundy: "#8a053c"
  success: "#3c8e26"
  ink: "#111111"
  muted: "#8c8c8c"
  surface: "#ffffff"
  page-bg: "#e5e7eb"
  navbar-stone: "#cccccc"
  border-light: "#e5e7eb"
  overlay: "rgba(0,0,0,0.30)"
typography:
  display:
    fontFamily: "Inter, ui-sans-serif, system-ui, -apple-system, sans-serif"
    fontSize: "clamp(1.875rem, 4vw, 2.5rem)"
    fontWeight: 900
    lineHeight: 1.1
    letterSpacing: "0.04em"
  headline:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "clamp(1.25rem, 3vw, 1.875rem)"
    fontWeight: 900
    lineHeight: 1.2
    letterSpacing: "0.06em"
  title:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "13px"
    fontWeight: 900
    lineHeight: 1.3
    letterSpacing: "0.02em"
  body:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "14px"
    fontWeight: 400
    lineHeight: 1.5
    letterSpacing: "normal"
  label:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "11px"
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: "0.08em"
rounded:
  sm: "6px"
  md: "8px"
  lg: "12px"
  xl: "16px"
  2xl: "16px"
  full: "9999px"
spacing:
  xs: "4px"
  sm: "8px"
  md: "16px"
  lg: "24px"
  xl: "32px"
  2xl: "48px"
components:
  button-primary:
    backgroundColor: "{colors.ink}"
    textColor: "{colors.surface}"
    rounded: "{rounded.full}"
    padding: "10px 32px"
  button-primary-hover:
    backgroundColor: "#222222"
    textColor: "{colors.surface}"
    rounded: "{rounded.full}"
    padding: "10px 32px"
  button-brand-cibat:
    backgroundColor: "{colors.cibat-navy}"
    textColor: "{colors.surface}"
    rounded: "{rounded.lg}"
    padding: "10px 16px"
  button-brand-er:
    backgroundColor: "{colors.er-red}"
    textColor: "{colors.surface}"
    rounded: "{rounded.lg}"
    padding: "10px 16px"
  button-ghost:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.muted}"
    rounded: "{rounded.full}"
    padding: "6px 14px"
  card-product:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.2xl}"
    padding: "16px"
  input-search:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.full}"
    padding: "4px 16px"
  chip-tag:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.muted}"
    rounded: "{rounded.full}"
    padding: "4px 12px"
  chip-tag-active-cibat:
    backgroundColor: "{colors.cibat-blue}"
    textColor: "{colors.surface}"
    rounded: "{rounded.full}"
    padding: "4px 12px"
  chip-tag-active-er:
    backgroundColor: "{colors.er-red}"
    textColor: "{colors.surface}"
    rounded: "{rounded.full}"
    padding: "4px 12px"
---

# Design System: OAGO — EncendidorQTA

## Overview

**Creative North Star: "Dual Brand Marketplace"**

OAGO EncendidorQTA es un marketplace B2B que alberga dos universos de marca — ER (rojo) y CIBAT (azul navy) — dentro de una sola arquitectura de catálogo. No es una tienda aspiracional; es un mostrador mayorista técnico y ordenado, pensado para reponer stock rápido. La interfaz prioriza densidad legible sobre teatralidad: grilla estricta, tarjetas verticales 4/4, precio en negro puro y badges por marca que funcionan como wayfinding.

El sistema nace de `resources/css/app.css:14` (Tailwind v4 + daisyUI dark/light con `--color-primary #002b6b`) pero el tema activo `encendidorqta` lo especializa: navbar con `backgroundnavbar.webp` + centro piedra `bg-[#cccccc] rounded-b-xl`, buscador pill flotante con `backdrop-blur-md` y `shadow-lg`, y footer `rounded-t-[3rem]` oscuro. Todo lo administrable vive en settings; lo visualmente distintivo vive en `resources/views/themes/encendidorqta/`.

**Key Characteristics:**
- Dualidad cromática como navegación (ER `#a6282e`/`#cc0000` vs CIBAT `#1b365d`/`#3d548f`) aplicada a badges, CTAs y estados activos.
- Grilla catálogo 2 → 4 columnas, tarjetas `rounded-2xl` blancas sobre `bg-gray-200` con imagen 4/4 `bg-gray-100/50` + `mix-blend-multiply`.
- Tipografía sans black uppercase ultra-condensada para títulos, con precio `text-lg font-black` negro y tachado gris.
- Elevación suave y funcional: sombras sólo para despegar buscador/cards/drawer del fondo plano.
- Motion mínimo: `hover:scale-110` en imagen, `hover:brightness-110` en CTA, `x-transition` en menú mobile.

## Colors

Paleta dual-brand sobre base neutra piedra/gris — el color nunca decora, siempre señala a qué marca pertenece el producto.

### Primary
- **CIBAT Navy** (`#1b365d`): marca CIBAT principal. Badges, CTA `AGREGAR AL CARRITO` y estados activos cuando `brand` contiene CIBAT o descripción contiene BATERIA (`web-product-card.blade.php:2`). Alterna con `#3d548f` en listados (`webproductsmain.blade.php:16`).
- **ER Red** (`#a6282e` / brillante `#cc0000`): marca ER principal. Mismo rol espejado para ER. `#cc0000` es el CTA vivo en card, `#a6282e` el estado activo en filtros y listado.

### Secondary
- **Burgundy** (`#8a053c`): `--color-secondary` de `app.css:15`, reservado para admin/daisyUI, no usado en storefront activo. Mantener para backoffice.
- **Success Green** (`#3c8e26`): `--color-success`, para confirmaciones y stock OK.

### Neutral
- **Ink** (`#111111`): botón `BUSCAR` primario del buscador (`web-search-filter.blade.php:41`), texto precio, títulos. Negro casi-puro para máximo contraste.
- **Muted** (`#8c8c8c`): texto secundario, placeholders, links del centro navbar piedra, selects `CATEGORÍAS/MARCAS`.
- **Stone** (`#cccccc`): centro navbar `rounded-b-xl` y dropdowns (`web-navbar.blade.php:20`). Piedra cálida que separa navegación del background image.
- **Surface** (`#ffffff`): cards `bg-white rounded-2xl`, buscador `bg-white border-gray-300`.
- **Page BG** (`#e5e7eb` / `bg-gray-200`): fondo catálogo por defecto (`webproductsmain.blade.php:1`), `bg-slate-50` en drawer cart.
- **Border Light** (`#e5e7eb`): `border-gray-200` en cards, `border-gray-300` en buscador y tags.
- **Overlay** (`rgba(0,0,0,0.30)`): `bg-black/30 backdrop-grayscale` sobre hero `frentelocal.png`.

### Named Rules
**The Two Inks Rule.** Nunca mezclar universos: una tarjeta, un filtro o un CTA pertenece a CIBAT *o* ER, nunca a ambos. El `brandColor` se resuelve una vez por producto y tiñe badge + CTA + estado activo de forma coherente.
**The Black Price Rule.** El precio siempre es negro (`text-gray-900` / `#111111`) con tachado gris, nunca en color de marca. La marca tiñe la acción (agregar), no el valor.
**The Stone Navigation Rule.** El centro de navegación piedra (`#cccccc`) es el único elemento no-blanco/no-transparente del header; no agregar un segundo color sólido en el navbar.

## Typography

**Display Font:** Inter, ui-sans-serif (con system-ui fallback)
**Body Font:** Inter, ui-sans-serif (mismo stack)
**Label Font:** Inter, 11px bold uppercase con tracking 0.08em

**Character:** Sans geométrica extra-black para wayfinding (UPPERCASE, `tracking-widest`, `font-black italic` en stats hero). Cuerpo regular 14px para legibilidad operativa; todo lo interactivo es label 11px bold. Sin serifas, sin display decorativa: técnico y ordenado.

### Hierarchy
- **Display** (900, `clamp(1.875rem,4vw,2.5rem)`, 1.1): hero `50 AÑOS` / `25.000 PRODUCTOS` (`index.blade.php:7`) — hero stats con `drop-shadow-[0_3px_6px_rgba(0,0,0,0.8)]` sobre foto local.
- **Headline** (900, `clamp(1.25rem,3vw,1.875rem)`, 1.2, 0.06em uppercase): secciones `PRODUCTOS DESTACADOS` / `NUESTRO CATÁLOGO` con hexágono `clip-path: polygon(25% 0% ...)` gris (`web-product-slider.blade.php:2`).
- **Title** (900, 13px, 1.3): `h2` de card producto (`web-product-card.blade.php:61`) — `uppercase line-clamp-2` negro, `hover:text-[#1b365d]/[#cc0000]` por marca.
- **Body** (400, 14px, 1.5): descripciones, selects, contenido drawer cart. Máx 65ch en detalle producto.
- **Label** (700, 11px, 0.08em, uppercase): badges `OFERTA`/tags (`text-[10px] font-black`), precio `text-lg font-black`, CTAs `text-[11px] font-black uppercase`, filtros `text-sm font-bold`.

### Named Rules
**The Uppercase Wayfinding Rule.** Todo lo que navega es uppercase bold con tracking: categorías, marcas, tags, CTAs. El contenido (descripción) es la única excepción en sentence case.
**The Price Is Black Rule.** Ver Colors: precio nunca usa color de marca ni se combina con peso menor a 900.

## Layout

Grilla rígida operativa, no editorial. `max-w-7xl mx-auto` con `px-4` como contenedor canónico. Densidad media: gaps `gap-4` en catálogo, `gap-6` en hero stats, `gap-2` en filtros join.

- **Navbar:** `min-h-[80px] md:min-h-[150px]` con `bg-cover` background image, centro piedra `rounded-b-xl shadow-lg` auto-centrado, logos ER/CIBAT simétricos flanqueando links `PRODUCTOS`. Mobile: hamburguesa izquierda, logos centrados `gap-16`, drawer `bg-[#cccccc] rounded-b-xl` con `x-show/x-transition` (`web-navbar.blade.php:84`).
- **Search Filter:** `sticky top-0 z-40 bg-white/90 backdrop-blur-md shadow-md` (`web-search-filter.blade.php:1`). Buscador pill `rounded-full border border-gray-300 shadow-lg` (`w-full lg:w-3/4 p-1`). Tags `join flex-wrap gap-2 justify-center`.
- **Catálogo:** `grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4` con imagen `aspect-ratio 4/4` (`web-product-card.blade.php:36`). Sin paginación visible: `infinite_scroll` con `x-intersect $wire.loadMore()` e `items +=15`.
- **Sections:** hero `aspect-ratio 1919/899` con overlay + stats `grid-cols-2 md:grid-cols-4` (-mt-56 overlap), slider horizontal `overflow-x-auto` con flechas `group-hover:opacity-100`.
- **Cart Drawer:** `w-full md:w-3/4 lg:w-1/2 xl:w-5/12` `border-l` con header `bg-slate-50 border-b` y grid interna `grid-cols-[80px_1fr_100px_120px_100px_48px]` en desktop.

**Responsive:** mobile 2-col, tablet 3-col, desktop 4-col. Navbar y buscador colapsan a pill único + selects hidden en mobile. Spacing rítmico 8/16/24/32, nunca 5/7.

## Elevation & Depth

Sistema plano con elevación puntual y funcional — no hay drama de profundidad, hay separación.

El fondo `bg-gray-200` y las cards `bg-white` crean layering tonal. La elevación aparece sólo donde un elemento debe despegarse del scroll: buscador (`shadow-lg` + `focus-within:shadow-xl`), cards (`shadow-md hover:shadow-xl`), navbar piedra (`shadow-lg`), drawer cart (`shadow-2xl`), footer `shadow-[0_-20px_50px_rgba(0,0,0,0.6)]`. El hero usa `backdrop-blur-md` y `backdrop-grayscale` para legibilidad sobre foto, no como decoración.

### Shadow Vocabulary
- **Card Rest** (`box-shadow: 0 4px 6px rgba(0,0,0,0.07), 0 2px 4px rgba(0,0,0,0.06)`): `shadow-md` en `card rounded-2xl` — elevación base.
- **Card Hover** (`box-shadow: 0 20px 25px rgba(0,0,0,0.10), 0 10px 10px rgba(0,0,0,0.04)`): `hover:shadow-xl` — sólo en card producto.
- **Search Elevated** (`box-shadow: 0 10px 15px rgba(0,0,0,0.10), 0 4px 6px rgba(0,0,0,0.05)`): `shadow-lg` en pill buscador, `shadow-xl` en focus.
- **Drawer/Modal** (`box-shadow: 0 25px 50px rgba(0,0,0,0.25)`): `shadow-2xl` en `x-drawer` cart.
- **Navbar Stone** (`box-shadow: 0 10px 15px rgba(0,0,0,0.10)`): `shadow-lg` en centro piedra.

### Named Rules
**The Functional Lift Rule.** Las sombras sólo aparecen en respuesta a estado (hover, focus, sticky, drawer). En reposo, el sistema es flat con bordes `border-gray-100/200`. Si no interactúa, no flota.

## Shapes

Lenguaje redondeado contenido: nada es sharp, nada es blob.

- **Full Pill** (`9999px`): buscador principal, botones `BUSCAR`, `Limpiar Filtros`, tags/chips `rounded-full`. Es la firma del sistema de filtrado.
- **2xl Card** (`16px`): `rounded-2xl` en todas las product cards (`web-product-card.blade.php:9`), así como filtros laterales `rounded-2xl shadow-sm` (`webproductsmain.blade.php:13`).
- **xl Navbar** (`12px`): `rounded-b-xl` en centro piedra navbar y drawer mobile — único caso de redondeo sólo inferior.
- **3rem Footer** (`48px`): `rounded-t-[3rem]` en `web-footer.blade.php:1` para anclaje dramático.
- **Hexagon Accent** (`clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%)`): decorativo en headlines (`web-product-slider.blade.php:4`), siempre `bg-gray-400 w-8 h-8`.

Bordes `border border-gray-100` en cards y `border-gray-300` en buscador — nunca `border-2` salvo en `Agotado` (`border-2 border-gray-400 rounded-lg`).

## Components

### Buttons
- **Shape:** pill `rounded-full (9999px)` para búsqueda/filtros, `rounded-lg (8px)` para CTA card.
- **Primary (BUSCAR):** `bg-[#111111] text-white px-6 md:px-10 py-2 md:py-3 text-sm font-bold rounded-full` (`web-search-filter.blade.php:41`), hover `bg-gray-800`, sin sombra.
- **Brand CTA:** `w-full py-2.5 text-white text-[11px] font-black rounded-lg shadow-md hover:brightness-110 active:scale-95` — `bg-[#1b365d]` CIBAT o `bg-[#cc0000]`/`#a6282e` ER (`web-product-card.blade.php:110`). Deshabilitado `opacity-50 pointer-events-none` si guest o sin precio.
- **Secondary (REGISTRARSE):** `bg-gray-400 hover:bg-gray-500 text-white` pill secundario encima del brand CTA cuando guest.
- **Hover / Focus:** `transition-colors` 200ms, `hover:brightness-110` en brand, `hover:bg-gray-800` en primary. Focus visible hereda `focus-within:shadow-xl` en contenedor.

### Chips
- **Style:** `bg-white border-gray-300 text-gray-600` outline, `rounded-full` `gap-2` (`web-search-filter.blade.php:58`), `btn-sm join-item`.
- **State Active:** `bg-[#3d548f] text-white border-transparent` CIBAT o `bg-[#a6282e]` ER según `?store` (`web-search-filter.blade.php:50`). Transición `transition-colors`.
- **No hay chips tonal:** sólo outline o sólido por marca.

### Cards / Containers
- **Corner Style:** `rounded-2xl (16px)` `overflow-hidden` con `border border-gray-100` y `shadow-md`.
- **Background:** `bg-white` card sobre `bg-gray-100/50` para área imagen; fallback `brightness-0 opacity-20` logo cuando sin imagen (`web-product-card.blade.php:42`).
- **Shadow Strategy:** `hover:shadow-xl` + `transition-all duration-300`; image `hover:scale-110 duration-500` con `mix-blend-multiply`.
- **Border:** `border border-gray-100` sutil, `hr border-gray-200 w-full mb-3` separador interno.
- **Internal Padding:** imagen `p-4` 4/4, contenido `p-4 flex-col flex-grow`, footer `px-4 pb-4 gap-2`.

### Inputs / Fields
- **Style:** buscador pill `bg-white border border-gray-300 rounded-full p-1 flex shadow-lg` con input `w-full outline-none px-3 py-2 text-sm font-semibold bg-transparent` (`web-search-filter.blade.php:11`).
- **Focus:** wrapper `focus-within:shadow-xl transition-shadow`, icon `o-magnifying-glass w-6 h-6 text-[#8c8c8c]` fijo a la izquierda, clear `o-x-mark` a la derecha sólo si hay texto.
- **Error / Disabled:** no documentado; usar `border-red-300` y `opacity-50` por convención daisyUI.

### Navigation
- **Desktop:** dual-logo simétrico con centro piedra `bg-[#cccccc] rounded-b-xl shadow-lg` conteniendo `Home/Nosotros/Locales/Login/PANEL` en `text-xs font-bold uppercase text-[#8c8c8c] hover:text-white` (`web-navbar.blade.php:22`). Links laterales `PRODUCTOS` `text-white hover:text-gray-300 text-xs tracking-wide self-start pt-3`.
- **Mobile:** `x-data {mobileMenuOpen:false}` con `x-show x-transition x-cloak` drawer `bg-[#cccccc] shadow-2xl rounded-b-xl` (`web-navbar.blade.php:84`), links `uppercase font-bold text-sm tracking-wide` en piedra, salida en `text-[#a6282e]`.
- **Sticky:** `sticky top-0 z-50 bg-cover` con background image; `z-40` para buscador debajo.

### Search Filter (Signature Component)
- **Character:** pill monolítica que unifica input + selects + botón — es el corazón operativo del catálogo.
- **Shape:** `rounded-full` exterior, `border-l border-gray-300 hidden md:flex` para selects internos.
- **Behavior:** `wire:model` live para category/brand, `wire:keydown.enter` para búsqueda, `clearSearch()` y `clearFilters()` como resets.

## Do's and Don'ts

### Do:
- **Do** usar `brandColor` coherente por producto (badge + CTA + tag activo comparten el mismo `#1b365d` o `#a6282e`) — The Two Inks Rule.
- **Do** mantener precio en negro `text-lg font-black text-gray-900` con tachado `text-[11px] text-gray-400 line-through` encima cuando hay oferta (`web-product-card.blade.php:75`).
- **Do** usar `rounded-2xl` en cards y `rounded-full` en todo lo filtrable — no inventar `rounded-xl` intermedio para tags.
- **Do** elevar sólo buscador/cards/drawer con `shadow-lg/xl`; deja el resto flat con `border-gray-200`.
- **Do** mantener títulos en `uppercase font-black line-clamp-2` con `hover:text-[#1b365d]` por marca para señalizar clic.
- **Do** mostrar `IMAGEN NO DISPONIBLE` con logo `brightness-0 opacity-20` y `tracking-widest` cuando `image_url` es null (`web-product-card.blade.php:42`).
- **Do** respetar `View::prependLocation()` — todo cambio visual para cliente va en `themes/encendidorqta/`, nunca en `resources/views/livewire/` core.

### Don't:
- **Don't** pintar precio con color de marca — viola Black Price Rule y confunde valor con acción.
- **Don't** duplicar bg sólido en navbar más allá de la piedra central — el background image debe respirar.
- **Don't** agregar toggle bulto/unidad (`qtty_package`) al theme sin re-hidratar `ProductSearchService::hydratePrices()` — el core lo tiene (`web-product-card.blade.php:130`) pero el theme lo omite intencionalmente por simplicidad vertical.
- **Don't** usar `Auth::user()` en Blade — siempre `current_user()` para respetar guard `alt` e impersonación sales (`web-product-card.blade.php:105`).
- **Don't** introducir serifas o fonts decorativas — el sistema es sans técnico; cualquier display debe ser `Inter` extra-black italic como en hero stats.
- **Don't** usar sombras coloreadas o `shadow-primary` en storefront — reservado para backoffice `app.blade.php:48` (`shadow-primary/20`).
- **Don't** crear nuevos offsets de color sin tonal ramp — extiende `#1b365d`/`#a6282e` en OKLCH, no hardcodees `#002b6b` del core si no es la marca del tema.
