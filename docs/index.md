# DBaaS API Documentation

Welcome to the Database as a Service (DBaaS) API documentation. This API provides a RESTful interface to interact with databases without writing a full backend.

## Overview

The DBaaS API maps HTTP methods to SQL operations:

| HTTP Method | SQL Operation | Description                                |
|-------------|---------------|--------------------------------------------|
| GET         | SELECT        | Retrieve records from a table              |
| POST        | INSERT        | Add new records to a table                 |
| PUT         | UPDATE        | Modify existing records in a table         |
| DELETE      | DELETE        | Remove records from a table                |

## Authentication

All API requests (except registration and login) require an API key sent in the `X-API-Key` header.

```http
X-API-Key: your-api-key-here
```

To obtain an API key, you must register and log in using the authentication endpoints.

## API Endpoints

### Data Operations

| Operation | Documentation                                    | Endpoint      | HTTP Method |
|-----------|--------------------------------------------------|---------------|-------------|
| Select    | [Select Operations](select_operations.md)        | `/api/db`     | GET/POST    |
| Insert    | [Insert Operations](insert_operations.md)        | `/api/db`     | POST        |
| Update    | [Update Operations](update_operations.md)        | `/api/db`     | PUT         |
| Delete    | [Delete Operations](delete_operations.md)        | `/api/db`     | DELETE      |

**Note**: The POST endpoint now supports both INSERT and SELECT operations using the `method` parameter. This is particularly useful for complex SELECT operations. See [Select Operations](select_operations.md) for details.

### Authentication

| Endpoint                | Method | Description                                  |
|-------------------------|--------|----------------------------------------------|
| `/api/auth/register`    | POST   | Register a new user                          |
| `/api/auth/login`       | POST   | Login and obtain an API key                  |
| `/api/auth/me`          | GET    | Get information about the authenticated user |
| `/api/auth/refresh-key` | POST   | Generate a new API key                       |

### User Management (Admin Only)

| Endpoint                    | Method | Description                              |
|-----------------------------|--------|------------------------------------------|
| `/api/users`                | GET    | List all users                           |
| `/api/users/{id}`           | GET    | Get information about a specific user    |
| `/api/users`                | POST   | Create a new user                        |
| `/api/users/{id}`           | PUT    | Update a user                            |
| `/api/users/{id}`           | DELETE | Delete a user                            |
| `/api/users/{id}/reset-key` | POST   | Reset a user's API key                   |

### Permission Management

| Endpoint                | Method | Description                                   |
|-------------------------|--------|-----------------------------------------------|
| `/api/permissions`      | GET    | List all permissions                          |
| `/api/permissions/{table}` | GET  | Get permissions for a specific table         |
| `/api/permissions`      | POST   | Create a new permission (Admin only)          |
| `/api/permissions/{id}` | DELETE | Delete a permission (Admin only)              |

## Data Types

The DBaaS API automatically handles type conversion for common data types:

| Database Type | JSON Type    | Notes                                |
|---------------|--------------|--------------------------------------|
| INTEGER       | number       | Whole numbers                        |
| DECIMAL/FLOAT | number       | Decimal numbers                      |
| VARCHAR/TEXT  | string       | Text values                          |
| BOOLEAN       | boolean      | true/false values                    |
| DATE          | string       | Format: "YYYY-MM-DD"                 |
| DATETIME      | string       | Format: "YYYY-MM-DD HH:MM:SS"        |
| JSON          | object/array | Will be stored as JSON               |

## Permissions System

The DBaaS API includes a robust permissions system that controls what operations users can perform on which tables:

1. **Role-Based Access Control**: Users can have admin or regular user roles
2. **Table-Specific Permissions**: Permissions can be granted for specific tables
3. **Operation-Specific Permissions**: Each permission specifies which operations (select, insert, update, delete) are allowed
4. **Column Restrictions**: Permissions can restrict access to specific columns
5. **Conditional Access**: Permissions can include WHERE conditions that must be satisfied

## Error Handling

All API endpoints return consistent error responses:

```json
{
  "error": "Error type",
  "message": "Detailed error message"
}
```

Common HTTP status codes:

| Status Code | Description                                           |
|-------------|-------------------------------------------------------|
| 200         | Success                                               |
| 201         | Resource created successfully                         |
| 400         | Bad request (invalid parameters or format)            |
| 401         | Unauthorized (missing or invalid API key)             |
| 403         | Forbidden (insufficient permissions)                  |
| 404         | Resource not found                                    |
| 422         | Validation error                                      |
| 500         | Server error                                          |

## Schema Management

The DBaaS system provides interactive Artisan commands for database schema management:

| Command                  | Description                                   |
|--------------------------|-----------------------------------------------|
| `dbaas:table:create`     | Create a new database table interactively     |
| `dbaas:table:list`       | List all tables in the database               |
| `dbaas:table:modify`     | Modify an existing database table             |
| `dbaas:table:delete`     | Delete an existing database table             |
| `dbaas:table:seed`       | Seed data into a database table               |
| `dbaas:table:export`     | Export table data to JSON or CSV format       |

For more information on these commands, refer to the [CLI Commands](cli_commands.md) documentation.

## Getting Started

1. Register a user account using the `/api/auth/register` endpoint
2. Log in to obtain an API key using the `/api/auth/login` endpoint
3. Include your API key in the `X-API-Key` header for all subsequent requests
4. Use the appropriate HTTP method and endpoint for your desired operation

## Examples

### Example: Creating a New Record

```http
POST /api/db HTTP/1.1
Host: your-dbaas-api.com
X-API-Key: your-api-key
Content-Type: application/json

{
  "table": "products",
  "data": {
    "name": "Smartphone X",
    "price": 999.99,
    "category_id": 2,
    "description": "Latest model with advanced features",
    "in_stock": true
  }
}
```

### Example: Retrieving Records

```http
GET /api/db?table=products&where[column]=price&where[operator]=>&where[value]=500&limit=5 HTTP/1.1
Host: your-dbaas-api.com
X-API-Key: your-api-key
```

For more detailed examples, refer to the specific operation documentation linked above.
