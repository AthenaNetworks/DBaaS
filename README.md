# Laravel Database as a Service (DBaaS) API

## Overview

This Laravel-based Database as a Service (DBaaS) API provides a RESTful interface to interact with databases without writing a full backend. It maps HTTP methods to SQL operations with robust authentication and granular access control.

## Features

- **RESTful API**: Maps HTTP methods to SQL operations
  - GET → SELECT
  - POST → INSERT
  - PUT → UPDATE (with optional UPSERT)
  - DELETE → DELETE
- **Authentication**: API key-based authentication with expiration
- **Role-Based Access Control**: Admin and user roles with different permission levels
- **Granular Permissions**:
  - Table-specific permissions
  - Operation-specific permissions (select, insert, update, delete)
  - Column-level restrictions
  - Conditional access with WHERE clauses
- **Interactive Schema Management**:
  - Create tables with custom columns and constraints
  - List and inspect database tables
  - Modify existing tables (add/modify/drop columns)
  - Delete tables
- **User Management**:
  - Admin users can create and manage other users
  - Role assignment (admin/user)
  - API key management
- **Security**: Input validation, query sanitization, and error handling

## Installation

1. Clone the repository
2. Install dependencies: `composer install`
3. Copy `.env.example` to `.env` and configure your database connection
4. Run migrations: `php artisan migrate`
5. Seed the database with test users and permissions: `php artisan db:seed`

## Table Management

The system provides interactive commands to manage database tables:

### Create a New Table

```bash
php artisan dbaas:table:create
```

This command will guide you through creating a new table with custom columns, types, and constraints.

### List Tables

```bash
php artisan dbaas:table:list
```

To see detailed column information for all tables:

```bash
php artisan dbaas:table:list --details
```

### Modify an Existing Table

```bash
php artisan dbaas:table:modify {table_name}
```

This command allows you to:
- Add new columns
- Modify existing columns (type, length, nullable, default values)
- Drop columns

### Delete a Table

```bash
php artisan dbaas:table:delete {table_name}
```

You can also run the command without specifying a table name to select from a list:

```bash
php artisan dbaas:table:delete
```

### Seed Data into a Table

```bash
php artisan dbaas:table:seed {table_name}
```

This interactive command will guide you through adding test data to your tables:
- Select a table to seed data into
- Specify how many records to create
- Enter values for each column (with smart defaults based on column names and types)
- Review the data before insertion

### Export Table Data

```bash
php artisan dbaas:table:export {table_name} --format=json|csv --output=storage/exports --filename=custom_name
```

Options:
- `--format`: Export format (json or csv, defaults to json)
- `--output`: Output directory (defaults to storage/exports)
- `--filename`: Custom filename without extension (defaults to table_name_date)

Example:
```bash
php artisan dbaas:table:export users --format=csv --output=storage/app/exports
```

## Authentication

All API requests (except registration and login) require an API key sent in the `X-API-Key` header.

### Registration

```
POST /api/auth/register
```

Payload:
```json
{
  "name": "User Name",
  "email": "user@example.com",
  "password": "password"
}
```

### Login

```
POST /api/auth/login
```

Payload:
```json
{
  "email": "user@example.com",
  "password": "password"
}
```

Response includes an API key that must be used for subsequent requests.

## Database Operations

### SELECT

```
GET /api/db
```

Payload:
```json
{
  "table": "users",
  "columns": ["id", "name", "email"],
  "where": [
    ["role", "=", "user"]
  ],
  "order_by": [{"column": "name", "direction": "asc"}],
  "limit": 10,
  "offset": 0
}
```

### INSERT

```
POST /api/db
```

Payload:
```json
{
  "table": "products",
  "data": {
    "name": "Product Name",
    "price": 99.99,
    "description": "Product description"
  }
}
```

For batch insert, use an array of objects for `data`.

### UPDATE

```
PUT /api/db
```

Payload:
```json
{
  "table": "products",
  "data": {
    "price": 89.99
  },
  "where": [
    ["id", "=", 1]
  ],
  "upsert": false
}
```

### DELETE

```
DELETE /api/db
```

Payload:
```json
{
  "table": "products",
  "where": [
    ["id", "=", 1]
  ]
}
```

## User Management

Only admins can manage users in the system. The following API endpoints are available:

### List All Users

```
GET /api/users
X-API-Key: [admin_api_key]
```

### Get User Details

```
GET /api/users/{id}
X-API-Key: [admin_api_key]
```

### Create New User

```
POST /api/users
X-API-Key: [admin_api_key]
```

Payload:
```json
{
  "name": "New User",
  "email": "newuser@example.com",
  "password": "password123",
  "role": "user"  // or "admin" to create another admin
}
```

### Update User

```
PUT /api/users/{id}
X-API-Key: [admin_api_key]
```

Payload (all fields optional):
```json
{
  "name": "Updated Name",
  "email": "updated@example.com",
  "password": "newpassword",
  "role": "admin"  // change user role
}
```

### Delete User

```
DELETE /api/users/{id}
X-API-Key: [admin_api_key]
```

### Reset User's API Key

```
POST /api/users/{id}/reset-key
X-API-Key: [admin_api_key]
```

## Permission Management

Only admins can manage permissions for users.

### List User Permissions

```
GET /api/permissions
X-API-Key: [admin_api_key]
```

### Get Table Permissions

```
GET /api/permissions/{table_name}
X-API-Key: [admin_api_key]
```

### Set Permissions

```
POST /api/permissions
X-API-Key: [admin_api_key]
```

Payload:
```json
{
  "user_id": 2,
  "table_name": "products",
  "can_select": true,
  "can_insert": true,
  "can_update": true,
  "can_delete": false,
  "where_conditions": [
    ["user_id", "=", 2]
  ],
  "column_restrictions": {
    "allowed": ["id", "name", "price"]
  }
}
```

## Test Users

The seeder creates two test users:

1. **Admin User**
   - Email: admin@example.com
   - Password: password
   - Role: admin

2. **Regular User**
   - Email: user@example.com
   - Password: password
   - Role: user
   - Has specific permissions on users, products, and orders tables

## Security

- API keys expire after 30 days by default
- Input validation prevents SQL injection
- Role-based access control restricts operations
- Column-level permissions prevent unauthorized data access

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
