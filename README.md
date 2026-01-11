# Symfony Blog Application

A modern, secure blog application built with Symfony 6.1 and PostgreSQL.

## Features

*   **Blog Engine**: Full-featured blog with posts, authors, and categorization.
*   **Admin Dashboard**: Secure administration area to manage content.
*   **User Authentication**: Complete user system with registration, login, and role-based access control.
*   **Row Level Security (RLS)**: Database tables are secured with PostgreSQL RLS to ensure data isolation and safety.
*   **Messenger Integration**: Async message handling support.

## Requirements

*   PHP >= 8.1
*   PostgreSQL >= 16
*   Composer
*   Symfony CLI (recommended)

## Installation

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/reblox01/blogify.git
    cd blogify
    ```

2.  **Install dependencies:**
    ```bash
    composer install
    ```

3.  **Configure Environment:**
    Copy the `.env` file to `.env.local` (if not managing via system envs) and set your database credentials:
    ```bash
    # .env.local
    DATABASE_HOST=localhost
    DATABASE_PORT=5432
    DATABASE_NAME=symfony_blog
    DATABASE_USER=postgres
    DATABASE_PASSWORD=your_password
    APP_SECRET=your_secret_key
    ```
    *Note: The application uses specific `DATABASE_HOST`, `DATABASE_PORT`, etc. variables instead of a single `DATABASE_URL` string for enhanced security and configuration flexibility.*

4.  **Run Migrations:**
    Set up the database schema and enable Row Level Security:
    ```bash
    php bin/console doctrine:migrations:migrate
    ```

5.  **Start the Server:**
    ```bash
    symfony server:start
    ```

## Security Note

This project uses **Row Level Security (RLS)** on the `users`, `posts`, and `messenger_messages` tables.
*   The application connects with a user that has `BYPASS RLS` privileges to function correctly.
*   Ensure your production database user has the appropriate privileges.

## Usage

*   **Homepage**: `http://localhost:8000/`
*   **Admin Panel**: `http://localhost:8000/admin` (Requires login with ADMIN role)

## License

MIT
