# CHRONOS - Luxury Watch Store

A PHP E-commerce application for a Watch Store with User, Seller, and Admin roles.

## Prerequisites

- PHP 8.0+
- MySQL (via XAMPP, WAMP, or Docker)
- Composer
- LocalStack (for S3 emulation)

## Setup Instructions

### 1. Database Setup
1. Start your MySQL server.
2. Create a database named `watch_store` or import the provided SQL file directly:
   ```bash
   mysql -u root -p < database.sql
   ```
   (Default config assumes user `root` with no password on `127.0.0.1`. Edit `config/database.php` if different).

### 2. Install Dependencies
Run the following command to install the AWS SDK for PHP (required for image uploads):
```bash
composer install
```
*Note: If `composer install` fails, ensure you have Composer installed globally.*

### 3. LocalStack S3 Setup
Ensure LocalStack is running for image storage emulation:
```bash
localstack start -d
```
The application is configured to look for LocalStack at `http://localhost:4566`.

### 4. Run the Application
Start the built-in PHP development server pointing to the `public` directory:
```bash
php -S localhost:8000 -t public
```

### 5. Access
Open your browser to [http://localhost:8000](http://localhost:8000).

**Default Admin Credentials:**
- Email: `admin@timepiece.com`
- Password: `password123`

## Features scope
- **Users**: Browse, Add to Cart (Session based for demo), Checkout (UI only).
- **Sellers**: Dashboard to list watches with image uploads to S3.
- **Admin**: View all users and system stats.
- **Design**: Premium "Dark Luxury" aesthetic with Vanilla CSS.
