# Gemini Guidelines for the oago Project

## About the Project

This is a web application built with the TALL stack (Tailwind CSS, Alpine.js, Laravel, Livewire). It is an e-commerce, API based catalog site.

## General Instructions

-   **Language:** Keep all code, comments, and commit messages in English unless specified otherwise. User-facing text should be managed through Laravel's localization files in `resources/lang`.
-   **Style:** Adhere strictly to the existing code style. For PHP, follow PSR-12 conventions.
-   **Dependencies:** Before adding new dependencies, check `composer.json` and `package.json` to see if a suitable library is already available.

## Backend (Laravel)

-   **Framework:** This is a Laravel project. Use idiomatic Laravel conventions.
-   **PHP Version:** Check `composer.json` for the required PHP version.
-   **Models:** Eloquent models are located in `app/Models`. Keep them lean. Business logic should be in services or action classes.
-   **Controllers:** Controllers are in `app/Http/Controllers`.
-   **Livewire:** A significant portion of the frontend logic is handled by Livewire components in `app/Livewire`. When adding new interactive UI, prefer creating a Livewire component.
    -   **Volt:** The project uses class-based Volt components, where logic and the Blade view are in the same file.
-   **Database:** Use Laravel Migrations for schema changes (`database/migrations`). Use Eloquent for database interaction.
-   **Routes:** Web routes are in `routes/web.php`, API routes in `routes/api.php`.
-   **Configuration:** Use `.env` for environment-specific variables. Do not commit `.env` files. Use `config/*.php` files for application configuration.

## Frontend

-   **UI Components:** The project uses [MaryUI](https://mary-ui.com/docs/components) for UI components. Please use its components for building interfaces.
-   **Styling:** The project likely uses Tailwind CSS. Main CSS file is `resources/css/app.css`.
-   **JavaScript:** Frontend JavaScript is in `resources/js`. The project uses Vite for asset bundling (`vite.config.js`).
-   **Views:** Blade templates are located in `resources/views`. Livewire components have their views in `resources/views/livewire`.

## Testing

-   **Framework:** The project uses Pest for testing. Test files are in the `tests/` directory.
-   **New Features:** Any new feature or bug fix should ideally be accompanied by a corresponding test.
-   **Running Tests:** Run tests using the `php artisan test` command.

## Commits

-   **Messages:** Write clear and concise commit messages. The first line should be a short summary (max 50 chars). Add more details in the body if needed.
