# Oago - Web Application

## About The Project

This is a web application built with Laravel, Livewire, and other modern web technologies. It´s an e-commerce platform with features like product management, orders, user authentication, and an API (mainly).

## Project Overview and Documentation

For a detailed breakdown of the project's architecture, technology stack, and conventions, please refer to the [Project Characteristics Document](project_characteristics.md).

To review the changes and feature updates across different versions, consult the [Changelog](CHANGELOG.md).


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

### Queue Worker Setup

This project utilizes Laravel Queues for asynchronous task processing, such as sending emails (e.g., order confirmations). By default, `QUEUE_CONNECTION` is set to `database` in `.env.example`.

To ensure queued tasks are processed in a production environment (example for Ubuntu), you need to:

1.  **Ensure Queue Table Exists:**
    If using the `database` queue driver, you must have the jobs table created:
    ```sh
    php artisan queue:table
    php artisan migrate
    ```

2.  **Start a Queue Worker:**
    A long-running process is required to process jobs from the queue. It is highly recommended to use a process monitor like Supervisor to manage your queue workers.

    *   **Install Supervisor (Ubuntu):**
        ```sh
        sudo apt-get update
        sudo apt-get install supervisor
        ```

    *   **Configure Supervisor:**
        Create a new Supervisor configuration file for your project (e.g., `/etc/supervisor/conf.d/laravel-worker.conf`):
        ```ini
        [program:laravel-worker]
        process_name=%(program_name)s_%(process_num)02d
        command=php /path/to/your/project/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
        autostart=true
        autorestart=true
        user=www-data             ; Or your web server user
        numprocs=1                ; Number of worker processes
        redirect_stderr=true
        stdout_logfile=/path/to/your/project/storage/logs/worker.log
        stopasgroup=true
        killasgroup=true
        priority=999
        ```
        *   **Important:** Replace `/path/to/your/project` with the actual absolute path to your Laravel project.
        *   **Tip:** Ensure `user=` matches the owner of the project files (e.g., `oagostini`) to avoid permission issues.
        *   `--sleep=3`: Number of seconds to sleep when no jobs are available.
        *   `--tries=3`: Number of times to attempt a job before marking it as failed.
        *   `--max-time=3600`: Max lifetime of a worker in seconds before it restarts to prevent memory leaks.

    *   **Update and Start Supervisor:**
        ```sh
        sudo supervisorctl reread
        sudo supervisorctl update
        sudo supervisorctl start laravel-worker:*
        ```

3.  **Monitor Queue:**
    You can check the status of your queue workers with:
    ```sh
    sudo supervisorctl status laravel-worker:*
    ```
    Jobs can be viewed in the `jobs` table in your database. Failed jobs will appear in the `failed_jobs` table.

## Database Schema

The database schema is defined by the migration files in `database/migrations`. The main tables are:

-   **`users`**: Stores main user information, including name, email, password, role, and the **`is_internal`** flag (designates internal staff with global access).
-   **`alt_users`**: Stores alternative user accounts, often used for guests or specific access scenarios. Also includes the **`is_internal`** flag.
-   **`products`**: Stores product information, such as name, description, price, and stock. Includes **`bonus_threshold`** and **`bonus_amount`** for quantity-based offers.
-   **`orders`**: Stores order information, including total price, status, and user.
-   **`order_items`**: Stores the items for each order.
-   **`list_names`**: Stores the names of the price lists.
-   **`list_prices`**: Stores the prices for each product in a specific price list.
-   **`shipping_details`**: Stores shipping details for each order.
-   **`settings`**: Stores application-wide settings and configurations.
-   **`achievements`**: Defines achievements or badges available in the system.
-   **`achievables`**: Polymorphic table linking achievements to users or other entities.
-   **`customer_sales_agents`**: Manages the relationship between customers and their assigned sales agents.

## Web Routes

The web routes are defined in `routes/web.php`. They include routes for:

-   Authentication (login, logout, register)
-   Static pages (about, contact)
-   Product and order management (for admins and sales agents)
-   Global Settings management (admin only)
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
