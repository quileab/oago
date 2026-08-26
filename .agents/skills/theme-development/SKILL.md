---
name: theme-development
description: Develops and customizes client themes overriding core views. Activates when creating, updating, or modifying themes, client customizations, or when the user mentions "theme", "tema", "cliente", "client", "override".
---

# Theme Development Guidelines

You are working on a custom theme for OAGO. 

## CRITICAL RULE: NEVER MODIFY THE CORE ENGINE
The base views are located in `resources/views/`. These files are the core engine of the application. 
**When working on a theme, you MUST NOT modify these base files.**

## Overriding Mechanism
OAGO uses Laravel's `View::prependLocation()` to load views. This means that a theme completely overrides the core files by matching their directory structure.

Themes are located in `resources/views/themes/{theme_name}/`.

### How to Override a Component
To customize a view (e.g., `resources/views/livewire/web-product-detail.blade.php`) for a theme named `my-theme`:
1. Find the base file in the core engine.
2. Replicate the path inside the theme directory: `resources/views/themes/my-theme/livewire/web-product-detail.blade.php`.
3. If the file does not exist in the theme AND you need to change it for this theme, COPY the content from the core file into the theme file. If no changes are needed, DO NOT copy it (the system automatically falls back to the core file).
4. Apply your customizations ONLY to the theme file.

## Static Assets
If you need to add CSS or images specific to a theme, ask the user what the convention is for public assets, or check if they are compiled via Vite to a specific theme directory.
