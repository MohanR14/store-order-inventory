# Store Order & Inventory System

A simple, clean Store Order & Inventory management mini-system built with **Laravel 12** and **PostgreSQL** for the Laravel Developer take-home assignment.

---

## 1. Overview

This application manages product inventory, customer records, order placement with concurrency-safe stock deduction, customer order history retrieval, and low-stock product reporting.

### Core Implemented Functionality
* **Sample Data Population**: Predefined realistic customer and product records via Laravel Database Seeders and Factories.
* **Order Creation**: Create orders with line items, automatically creating or finding customers by email.
* **Stock Validation & Deduction**: Validates stock availability under pessimistic database locks before deducting inventory.
* **Price & Tax Calculations**: Computes item subtotals, item tax, line totals, order subtotals, order tax, and order grand totals.
* **Customer Order History**: Fetch complete order history for a customer using their email address.
* **Low-Stock Reporting**: Query products whose current stock is at or below a configurable threshold.
* **Queued Order Confirmation**: Simulates asynchronous order confirmation email logging via Laravel's background queue.
* **Concurrency-Safe Stock Safety**: Uses PostgreSQL pessimistic row-level locking (`SELECT ... FOR UPDATE`) with deadlock prevention.
* **Web Dashboard UI**: A lightweight, responsive single-page dashboard built with Blade, Vanilla CSS, and Vanilla JavaScript.

*(Note: Product CRUD and Customer CRUD APIs are intentionally omitted as they were outside the scope of this assignment.)*

---

## 2. Tech Stack

* **Language**: PHP 8.2+
* **Framework**: Laravel 12
* **Database**: PostgreSQL
* **ORM**: Eloquent ORM
* **Background Queue**: Laravel Queue (`database` driver)
* **Frontend**: HTML5, Vanilla CSS, Vanilla JavaScript (Fetch API)
* **Testing Framework**: PHPUnit

---

## 3. Features

### Customer
* Customer identity is determined by a unique `email` address.
* Automatically created during order creation if a customer with the given email does not already exist.

### Product
* Contains `name`, unique `code`, `price`, `tax_percentage`, and `stock_on_hand`.

### Order
* A customer can have multiple orders.
* Each order contains one or more line items (`order_items`).
* Computes line item totals, order subtotal, order tax, and grand total.
* Deducts product stock upon successful creation.

### Inventory
* Validates stock availability prior to deduction.
* Engine-level PostgreSQL check constraint (`stock_on_hand >= 0`) prevents negative inventory.
* Configurable low-stock threshold for inventory monitoring.

### Queue
* Dispatches `SendOrderConfirmationJob` upon successful order creation.
* Simulates order confirmation email by writing structured data to Laravel logs (`Log::info`).
* No real SMTP or external mail provider setup required.

### Customer Order History
* Lookup orders by customer email query parameter.

---

## 4. Database Design

### Database Tables
1. **`customers`**: `id`, `name`, `email` (unique), `created_at`, `updated_at`
2. **`products`**: `id`, `name`, `code` (unique), `price`, `tax_percentage`, `stock_on_hand`, `created_at`, `updated_at`
3. **`orders`**: `id`, `customer_id` (FK), `subtotal`, `tax`, `grand_total`, `created_at`, `updated_at`
4. **`order_items`**: `id`, `order_id` (FK), `product_id` (FK), `quantity`, `unit_price`, `tax_percentage`, `subtotal`, `tax`, `total`, `created_at`, `updated_at`

### Eloquent Relationships
* `Customer` `hasMany` `Order`
* `Order` `belongsTo` `Customer`
* `Order` `hasMany` `OrderItem`
* `OrderItem` `belongsTo` `Order`
* `OrderItem` `belongsTo` `Product`
* `Product` `hasMany` `OrderItem`

### Historical Purchase-Time Snapshots
`order_items` explicitly stores `unit_price`, `tax_percentage`, `subtotal`, `tax`, and `total` at the exact moment of purchase. This guarantees that future price or tax adjustments on the `products` table never alter historical financial records or past invoices.

### Database Constraints
* `customers.email`: Unique index (`UNIQUE`)
* `products.code`: Unique index (`UNIQUE`)
* `products.stock_on_hand`: Non-negative constraint (`CHECK (stock_on_hand >= 0)`)
* `order_items.quantity`: Positive quantity constraint (`CHECK (quantity > 0)`)
* Foreign key constraints are enforced with appropriate delete behavior to protect order and inventory history.

---

## 5. Architecture

### Order Creation Execution Flow

```text
HTTP Request (POST /api/orders)
  │
  ▼
Form Request (StoreOrderRequest) -> Validates payload structure & positive quantities
  │
  ▼
Controller (OrderController@store) -> Thin controller delegates to OrderService
  │
  ▼
OrderService (OrderService->createOrder)
  ├─ 1. Opens DB::transaction()
  ├─ 2. Finds or creates Customer by email
  ├─ 3. Aggregates product quantities (combines duplicate product IDs)
  ├─ 4. Sorts Product IDs numerically (Deadlock Prevention)
  ├─ 5. Executes Product::whereIn(...)->orderBy('id')->lockForUpdate()
  ├─ 6. Validates stock sufficiency post-lock acquisition
  ├─ 7. Calculates line item & order totals
  ├─ 8. Atomically decrements product stock_on_hand
  ├─ 9. Persists Order and OrderItems
  └─ 10. Calls SendOrderConfirmationJob::dispatch($order->id)->afterCommit()
  │
  ▼
Database Commit -> Transaction commits cleanly
  │
  ▼
Background Queue -> SendOrderConfirmationJob executes asynchronously
```

### Architectural Principles
* **Thin Controllers**: `OrderController`, `CustomerOrderController`, and `ProductController` delegate validation to Form Requests and domain logic to `OrderService`.
* **Form Requests**: `StoreOrderRequest` and `GetCustomerOrderHistoryRequest` handle input validation.
* **Transactional Integrity**: Order persistence and stock deduction are wrapped inside atomic database transactions.
* **No Unnecessary Abstractions**: Keeps codebase readable by avoiding redundant DTOs, Repository interfaces, or complex design patterns.

---

## 6. Concurrency & Stock Safety

### Concurrency Implementation
1. **Pessimistic Locking (`lockForUpdate()`)**: Executed inside `DB::transaction()`. Places exclusive row-level PostgreSQL locks on requested product rows.
2. **Deadlock Prevention**: Product IDs are sorted numerically (`sort($productIds)` & `orderBy('id')`) before acquiring row locks, ensuring competing requests acquire locks in the identical order.
3. **Post-Lock Verification**: Stock is evaluated **after** locks are acquired, ensuring worker processes read updated stock values.
4. **Clean Transaction Rollbacks**: If stock is insufficient, throwing a `ValidationException` rolls back the transaction. Zero records are created, zero stock is altered, and an HTTP `422 Unprocessable Entity` response is returned.
5. **Database Safeguard**: PostgreSQL `CHECK (stock_on_hand >= 0)` constraint prevents negative inventory at the database engine level.

### Single-Stock Unit Scenario
If 1 unit remains in stock and two concurrent requests attempt to purchase 1 unit:
1. **Request #1** acquires the row lock (`SELECT ... FOR UPDATE`), verifies stock (`1 >= 1`), decrements stock to `0`, and commits the transaction.
2. **Request #2** waits at the database level for the row lock to be released.
3. Once **Request #1** commits, **Request #2** acquires the lock, reads the updated stock (`0`), detects insufficient stock (`0 < 1`), and triggers a transaction rollback.
4. **Result**: Exactly one order succeeds, the second order fails cleanly with an HTTP 422 error, stock remains at `0`, and no negative stock or over-selling occurs.

Production code uses PostgreSQL `lockForUpdate()` for real concurrent request safety. The automated test verifies the no-over-selling behavior using the current competing sequential request approach.

---

## 7. Queue Job

### `SendOrderConfirmationJob`
* Implements `Illuminate\Contracts\Queue\ShouldQueue`.
* **Payload**: Receives `$orderId` (integer) rather than serializing the entire `Order` model, preventing stale state and reducing queue payload size.
* **Execution**: Fetches the latest `Order` record with `customer` and `items.product` relationships and logs confirmation details via `Log::info()`.
* **Simulated Email**: Writes structured details (Order ID, Customer Name, Customer Email, Grand Total) to `storage/logs/laravel.log`. No real SMTP or mail provider required.

### Transactional Safety (`afterCommit`)
`afterCommit()` ensures the job is dispatched only after the database transaction successfully commits. If order creation fails and the transaction rolls back, the confirmation job is not dispatched for that failed order.

---

## 8. Configuration

### Low-Stock Threshold
Configured in `config/inventory.php` using the `LOW_STOCK_THRESHOLD` environment variable (default: `5`).

```env
LOW_STOCK_THRESHOLD=5
QUEUE_CONNECTION=database
```

---

## 9. API Endpoints

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| **POST** | `/api/orders` | Create an order with line items & customer details |
| **GET** | `/api/customers/orders?email=customer@example.com` | Retrieve customer order history with items & products |
| **GET** | `/api/products/low-stock` | Retrieve products where stock is $\le$ threshold |

---

## 10. Local Setup & Testing

### Prerequisites
* PHP 8.2+
* PostgreSQL 14+
* Composer

### Setup Instructions

1. **Clone & Install Dependencies**:
   ```bash
   composer install
   ```

2. **Configure Environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Update database credentials in `.env` for PostgreSQL (`DB_CONNECTION=pgsql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).*

3. **Run Database Migrations & Seeders**:
   ```bash
   php artisan migrate:fresh --seed
   ```

4. **Run Automated Test Suite**:
   ```bash
   php artisan test
   ```

5. **Start Background Queue Worker**:
   ```bash
   php artisan queue:work
   ```

6. **Start Development Server**:
   ```bash
   php artisan serve
   ```

7. **Access Web Dashboard**:
   Open browser at **`http://localhost:8000`**.

---

## 11. UI (Web Dashboard)

The application includes a clean single-page dashboard built into `resources/views/welcome.blade.php`:

1. **Create Order**: Form accepting Customer Name, Customer Email, Product ID, and Quantity. Contains an in-memory draft item builder table and submits orders to `POST /api/orders`. Displays order totals on success or detailed error lists on failure.
2. **Customer Order History**: Search form calling `GET /api/customers/orders?email=...` that renders customer details, order cards, and line item product breakdowns.
3. **Low Stock Products**: Table listing products at or below the configured threshold from `GET /api/products/low-stock`, displaying threshold metadata and a manual refresh button.

### Design Decisions
* The Create Order UI accepts Product ID and quantity because the order creation API expects `product_id` for each order item.
* Product autocomplete or a product-selection dropdown is not implemented in the current scope. The assignment allows flexible UI/layout and does not require a product-list/autocomplete feature.

---

## 12. Test Coverage

Automated tests are located in `tests/Feature/`:

* **Successful Order Creation** (`OrderCreationTest.php`): Verifies HTTP 201 response, database persistence of customer/order/items, subtotal/tax/grand total calculations, and stock deduction.
* **Validation** (`OrderValidationTest.php`): Tests 422 errors for missing/invalid email, missing customer name, empty items, invalid product ID, and quantity `< 1`.
* **Insufficient Stock** (`OrderValidationTest.php`): Asserts clean failure when requested quantity exceeds stock, verifying no order/items are created and stock is untouched.
* **Multiple Products** (`OrderCreationTest.php`): Tests orders with multiple distinct products having different prices and tax percentages.
* **Duplicate Product IDs** (`OrderCreationTest.php`): Verifies duplicate product IDs in a single request are combined and stock is deducted once.
* **Customer Order History** (`CustomerOrderHistoryTest.php`): Tests fetching order history by email and returns 404 for unknown customers.
* **Low-Stock Products** (`LowStockProductTest.php`): Verifies filtering products based on the configured `LOW_STOCK_THRESHOLD`.
* **Queue Job Dispatch** (`OrderJobDispatchTest.php`): Asserts `SendOrderConfirmationJob` is queued on order success, omitted on failure, and verifies log payload formatting.
* **Stock/Concurrency Safety** (`StockConcurrencyTest.php`): Asserts that when two competing requests target a single remaining stock unit, only one order succeeds and the second fails cleanly without over-selling.

---

## 13. Design Decisions & Assumptions

* **Monetary Precision**: Prices, tax percentages, line item subtotals/tax/totals, and order totals are rounded to 2 decimal places (`decimal(12, 2)`).
* **Purchase-Time Snapshots**: Unit prices and tax percentages are stored on `order_items` at purchase time so future product updates do not alter past orders.
* **Pragmatic Architecture**: Kept controllers thin and used Form Requests and `OrderService` without introducing unnecessary Repository, DTO, or Interface abstractions.

---

## 14. AI-Assisted Development

This solution was designed and implemented using Antigravity AI assistant following a clean, step-by-step workflow: architecture planning, database migrations and Eloquent models, API endpoints, transactional concurrency logic, queue job integration, automated feature tests, and single-page dashboard UI.

Prompt screenshots documenting the AI-assisted development workflow are included in the `prompts/` directory.

---

## 15. Local Development Notes

* During local automated testing, migration statements for PostgreSQL check constraints (`ALTER TABLE ... ADD CONSTRAINT`) are conditionally executed when the driver is `pgsql` to allow seamless test execution against in-memory SQLite (`:memory:`).
* The queue driver is configured to `database`, requiring `php artisan queue:work` during manual testing to process `SendOrderConfirmationJob` instances.

---

## 16. Project Structure

```text
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── CustomerOrderController.php
│   │       ├── OrderController.php
│   │       └── ProductController.php
│   └── Requests/
│       ├── GetCustomerOrderHistoryRequest.php
│       └── StoreOrderRequest.php
├── Jobs/
│   └── SendOrderConfirmationJob.php
├── Models/
│   ├── Customer.php
│   ├── Order.php
│   ├── OrderItem.php
│   └── Product.php
└── Services/
    └── OrderService.php
config/
└── inventory.php
database/
├── factories/
│   ├── CustomerFactory.php
│   ├── OrderFactory.php
│   ├── OrderItemFactory.php
│   └── ProductFactory.php
├── migrations/
└── seeders/
    └── DatabaseSeeder.php
resources/
└── views/
    └── welcome.blade.php
routes/
├── api.php
└── web.php
tests/
└── Feature/
    ├── CustomerOrderHistoryTest.php
    ├── LowStockProductTest.php
    ├── OrderCreationTest.php
    ├── OrderJobDispatchTest.php
    ├── OrderValidationTest.php
    └── StockConcurrencyTest.php
```

---

## 17. Submission Notes

The implementation covers the functional and technical requirements of the Store Order & Inventory Laravel take-home assignment, including:
* Concurrency-safe pessimistic locking (`lockForUpdate()`) with deadlock prevention.
* Atomic database transactions with post-lock validation.
* Purchase-time item price/tax snapshots.
* Queued confirmation simulation via `afterCommit()`.
* Clean API endpoints and single-page dashboard UI.
* Complete automated feature test suite (18 tests passed).
