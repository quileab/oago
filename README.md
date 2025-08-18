# Oago - Web Application

## About The Project

This is a web application built with Laravel, Livewire, and other modern web technologies. It´s an e-commerce platform with features like product management, orders, user authentication, and an API (mainly).

## Built With

-   [Laravel](https://laravel.com/)
-   [Livewire](https://laravel-livewire.com/)
-   [Volt](https://volt.laravel.com/)
-   [Mary UI](https://mary-ui.com/)
-   [Tailwind CSS](https://tailwindcss.com/)
-   [Alpine.js](https://alpinejs.dev/)

## Getting Started

To get a local copy up and running follow these simple steps.

### Prerequisites

-   PHP >= 8.2
-   Composer
-   Node.js & npm

### Installation

1. Clone the repo
    ```sh
    git clone https://github.com/quileab/oago.git
    ```
2. Install PHP dependencies
    ```sh
    composer install
    ```
3. Install NPM packages
    ```sh
    npm install
    ```
4. Create a copy of your .env file
    ```sh
    cp .env.example .env
    ```
5. Generate an app encryption key
    ```sh
    php artisan key:generate
    ```
6. Create a database and add credentials to .env
7. Run the database migrations
    ```sh
    php artisan migrate
    ```
8. Start the development server
    ```sh
    npm run dev
    ```

## Database Schema

The database schema is defined by the migration files in `database/migrations`. The main tables are:

-   **`users`**: Stores user information, including name, email, password, and role.
-   **`products`**: Stores product information, such as name, description, price, and stock.
-   **`orders`**: Stores order information, including total price, status, and user.
-   **`order_items`**: Stores the items for each order.
-   **`list_names`**: Stores the names of the price lists.
-   **`list_prices`**: Stores the prices for each product in a specific price list.
-   **`guest_users`**: Stores information about guest users.
-   **`shipping_details`**: Stores shipping details for each order.

## Web Routes

The web routes are defined in `routes/web.php`. They include routes for:

-   Authentication (login, logout, register)
-   Static pages (about, contact)
-   Product and order management (for admins)
-   User profile
-   Checkout process

## API Routes

The API routes are defined in `routes/api.php` and are protected by Sanctum. They include routes for:

-   Authentication (login, logout, register)
-   Product management (CRUD)
-   Order management
-   User management
-   Price list management

## Dependencies

Key dependencies from `composer.json`:

-   `php: ^8.2`
-   `laravel/framework: ^12.0`
-   `livewire/livewire: ^3.5`
-   `livewire/volt: ^1.6`
-   `robsontenorio/mary: ^2.0`
-   `dedoc/scramble: ^0.12.17`
