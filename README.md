# Product Management (Laravel Assessment)

Admin dashboard + RESTful API for managing products, built with **Laravel 13**, **MySQL 8**, **Laravel Sanctum**, **nwidart/laravel-modules**, **Maatwebsite Excel** and **L5-Swagger**.

## Features

| Area | What is included |
|------|------------------|
| Admin dashboard | Login, product list with **status / category / name filters**, **pagination**, create, edit, **soft delete**, **bulk delete** (checkboxes), **Excel export** (respects the active filters) |
| REST API | Login (Bearer token), create / list (filter by category) / detail / update / delete / **bulk delete** products |
| Relationship | `Category` **hasMany** `Product`; product JSON includes `category.id` and `category.name` |
| Validation | Laravel **Form Requests** everywhere (no inline validation) |
| Security | Sanctum tokens (API) + session auth (dashboard), `admin` middleware, login throttling, mass-assignment protection (`is_admin` is not fillable) |
| Docs | Swagger UI at `/api/documentation`, plus this README |
| Tests | 40 PHPUnit tests (feature + unit) |

## Project structure (modular MVC)

```
Modules/
  Auth/      Login (API token + dashboard session), `admin` middleware, admin seeder, users.is_admin migration
  Category/  Category model/migration/factory/seeder, GET /api/v1/categories
  Product/   Product model (SoftDeletes), migrations, Form Requests, API Resource,
             API controller (Api/ProductController), dashboard controller, Blade views,
             ProductsExport (Excel), seeders, tests
```

Each module follows the standard nwidart layout (`app/Http`, `app/Models`, `database`, `routes`, `resources/views`, `tests`).

## Setup (Docker – recommended)

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan l5-swagger:generate
```

- App / dashboard: <http://localhost:8080> (redirects to `/login`)
- Swagger UI: <http://localhost:8080/api/documentation>
- MySQL is exposed on `localhost:3307` (user `product_user`, password `product_password`, db `product_management`).

### Without Docker

Requires PHP 8.3+, Composer, MySQL 8. Create a database, set `DB_HOST=127.0.0.1` and the credentials in `.env`, then:

```bash
composer install
php artisan key:generate
php artisan migrate --seed
php artisan l5-swagger:generate
php artisan serve
```

### Seeded data

| Item | Value |
|------|-------|
| Admin account | `admin@example.com` / `password` |
| Categories | Electronics, Clothing, Books, Home & Garden, Sports |
| Products | 8 per category (6 enabled, 2 disabled) |

## Running the tests

```bash
docker compose exec app php artisan test
# or: php artisan test
```

Tests use an in-memory SQLite database (see `phpunit.xml`), so they never touch your MySQL data. Module tests live in `Modules/*/tests`.

## Admin dashboard

| URL | Purpose |
|-----|---------|
| `/login` | Admin sign-in (non-admin accounts are rejected) |
| `/admin/products` | List with filters (`search`, `category_id`, `status=enabled\|disabled`) and pagination |
| `/admin/products/create`, `/admin/products/{id}/edit` | Create / edit |
| `/admin/products/export` | Download `products.xlsx` (same filters as the list) |

Select rows with the checkboxes and press **Delete selected** for bulk delete.

## API

Base URL `http://localhost:8080/api/v1`. All endpoints except `auth/login` require `Authorization: Bearer <token>` **and** an admin account. Responses are JSON; interactive docs are in Swagger UI.

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/login` | `{email, password}` → `{token}` (admins only, throttled) |
| POST | `/auth/logout` | Revoke current token |
| GET | `/auth/me` | Current admin |
| GET | `/categories` | List categories |
| GET | `/products` | List products. Query: `category_id`, `status`, `search`, `per_page` (1-100, default 15), `page` |
| POST | `/products` | Create product |
| GET | `/products/{id}` | Product detail |
| PUT/PATCH | `/products/{id}` | Update product (PATCH = partial) |
| DELETE | `/products/{id}` | Soft delete product |
| POST | `/products/bulk-delete` | `{ "ids": [1,2,3] }` soft delete many |

Example:

```bash
TOKEN=$(curl -s -X POST localhost:8080/api/v1/auth/login \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"admin@example.com","password":"password"}' | jq -r .token)

curl -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" \
  "localhost:8080/api/v1/products?category_id=1&status=enabled&per_page=5"
```

Product JSON:

```json
{
  "id": 1, "name": "Wireless Mouse", "description": "…", "price": 19.99,
  "stock": 120, "enabled": true,
  "category": { "id": 2, "name": "Electronics" },
  "created_at": "…", "updated_at": "…"
}
```

Status codes: `200/201` success, `401` no/invalid token, `403` authenticated but not an admin, `404` missing (or soft-deleted) product, `422` validation error.

## Assumptions & design choices

- **"Admin"** is a boolean `users.is_admin` flag enforced by an `admin` route middleware; it is not mass-assignable. Role/permission packages seemed overkill for a single role.
- **Two auth mechanisms, one user table**: Sanctum bearer tokens for the API, session login for the dashboard. Both reject non-admins.
- **Modules** (Auth, Category, Product) keep each concern separate; shared filtering lives in `Product::scopeFilter()` and is reused by the API, the dashboard and the Excel export so they cannot drift apart.
- **Soft deletes** everywhere; soft-deleted products are hidden from list/detail and cannot be bulk-deleted again (validated). Categories are not deletable (not requested), and the FK uses `RESTRICT`.
- **"Status" filter** maps to the `enabled` flag (`enabled` / `disabled`).
- **Performance**: eager loading (`with('category:id,name')`, no N+1), pagination capped at 100/page, composite index on `(enabled, category_id)` and an index on `created_at`, Excel export streams via `FromQuery` (chunked by the package) instead of loading everything into memory.
- **Validation** uses Form Requests: `StoreProductRequest`, `UpdateProductRequest`, `ListProductRequest`, `BulkDeleteProductRequest`, `LoginRequest`.
- Bootstrap is loaded from a CDN for the dashboard styling (no front-end build step needed).
