# Store Order & Inventory Mini-System

A Laravel 8 REST API for managing products, customers, orders, inventory, and simulated order-confirmation jobs.

## Technology Stack

- PHP 8.2
- Laravel 8
- MySQL
- Laravel Queue
- PHPUnit / Laravel Feature Tests

## Features

- Product and customer database models with migrations.
- Sample products and customers using database seeders and factories.
- Order creation with customer details and one or more product items.
- Automatic subtotal, tax, and grand-total calculation.
- Stock validation and inventory deduction within a database transaction.
- Database row locking using `lockForUpdate()` to help prevent overselling.
- Customer order history retrieved by email.
- Low-stock product listing with a configurable threshold.
- Queued order-confirmation job with simulated email logging.
- Feature tests for order creation, insufficient stock, inventory APIs, and validation.

## Requirements

- PHP 8.2
- Composer
- MySQL
- XAMPP or another compatible local development environment

## Installation

1. Clone the repository.
2. Install dependencies:

   ```bash
   composer install
   ```

3. Create a MySQL database named `store_order_system`.
4. Copy `.env.example` to `.env` and configure your database connection:

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=store_order_system
   DB_USERNAME=root
   DB_PASSWORD=
   QUEUE_CONNECTION=database
   ```

5. Generate the application key:

   ```bash
   php artisan key:generate
   ```

6. Run migrations and seed sample data:

   ```bash
   php artisan migrate --seed
   ```

7. Start the Laravel server:

   ```bash
   php artisan serve
   ```

8. In a separate terminal, start the queue worker:

   ```bash
   php artisan queue:work
   ```

## API Endpoints

### Create an order

`POST /api/orders`

Example request:

```json
{
  "customer": {
    "name": "Rahul Sharma",
    "email": "rahul@example.com"
  },
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    }
  ]
}
```

### Customer order history

`GET /api/customers/orders?email=rahul@example.com`

Returns the customer and their orders, including order items.

### Low-stock products

`GET /api/products/low-stock?threshold=5`

Returns products whose stock quantity is below the supplied threshold. The default threshold is `5`.

## Queue Job

The `SendOrderConfirmation` job simulates a confirmation email by writing order details to the Laravel application log.

Check:

`storage/logs/laravel.log`

The job does not send a real email.

## Running Tests

Run the order tests:

```bash
php artisan test --filter=OrderTest
```

Run the inventory API tests:

```bash
php artisan test --filter=InventoryApiTest
```

Run the full test suite:

```bash
php artisan test
```

## Assumptions and Notes

- A customer is identified by their unique email address.
- Product tax percentages are applied to each order line.
- Monetary calculations use integer cents during calculation to reduce floating-point rounding errors.
- Stock must be sufficient for the requested quantity before an order can be created.
- Order creation and stock deductions are performed within a database transaction.
- `lockForUpdate()` relies on database transaction and row-locking support; test concurrency against MySQL/InnoDB rather than assuming SQLite provides equivalent locking.
- Confirmation emails are simulated through logging and are not sent to an external email service.
- A separate test database should be configured when running tests to protect development data.
