# Authentication and Authorization

## Overview

The DBaaS API uses API key-based authentication to secure all endpoints. This document explains how to login and use API keys to authenticate your requests, as well as how the permission system works to control access to database operations.

> **Note**: User registration is handled by administrators through the admin interface. New users should contact their system administrator to obtain credentials.

## Authentication Endpoints

### User Login

#### Endpoint

```
POST /api/auth/login
```

#### Headers

| Header       | Value            | Required | Description                    |
|--------------|------------------|----------|--------------------------------|
| Content-Type | application/json | Yes      | Indicates JSON request payload |

#### Request Body

| Parameter | Type   | Required | Description           |
|-----------|--------|----------|-----------------------|
| email     | string | Yes      | User's email address  |
| password  | string | Yes      | User's password       |

#### Response

**Success (200 OK)**

```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "user"
  },
  "api_key": "your-api-key-here"
}
```

**Error (401 Unauthorized)**

```json
{
  "message": "Invalid login credentials"
}
```

### Refresh API Key

#### Endpoint

```
POST /api/auth/refresh-key
```

#### Headers

| Header       | Value            | Required | Description                       |
|--------------|------------------|----------|-----------------------------------|
| Content-Type | application/json | Yes      | Indicates JSON request payload    |
| X-API-Key    | your-api-key     | Yes      | Your current valid API key        |

#### Response

**Success (200 OK)**

```json
{
  "message": "API key refreshed successfully",
  "api_key": "your-new-api-key-here"
}
```

**Error (401 Unauthorized)**

```json
{
  "message": "Unauthenticated"
}
```

### Get Current User

#### Endpoint

```
GET /api/auth/me
```

#### Headers

| Header    | Value        | Required | Description                |
|-----------|--------------|----------|----------------------------|
| X-API-Key | your-api-key | Yes      | Your valid API key         |

#### Response

**Success (200 OK)**

```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "user"
  }
}
```

**Error (401 Unauthorized)**

```json
{
  "message": "Unauthenticated"
}
```

## Using API Keys

### API Key Format

API keys are 64-character random strings that are generated when a user registers or logs in. They are also refreshed when requested through the refresh endpoint.

### API Key Expiration

By default, API keys expire after 30 days from the date of creation. You should refresh your API key before it expires to maintain uninterrupted access to the API.

### Including API Keys in Requests

To authenticate your requests, include your API key in the `X-API-Key` header:

```
X-API-Key: your-api-key-here
```

Example using cURL:

```bash
curl -X GET \
  https://your-dbaas-instance.com/api/db \
  -H 'X-API-Key: your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{
    "table": "users",
    "columns": ["id", "name", "email"]
  }'
```

## Authorization System

### Role-Based Access Control

The DBaaS API implements role-based access control with two primary roles:

1. **Admin**: Has full access to all tables and operations
2. **User**: Has access based on specific permissions granted by admins

### Permission Model

Permissions in DBaaS are granular and can be configured at multiple levels:

#### 1. Operation-Level Permissions

Each user can be granted or denied permission to perform specific operations on tables:

- `can_select`: Permission to query data (SELECT)
- `can_insert`: Permission to add data (INSERT)
- `can_update`: Permission to modify data (UPDATE)
- `can_delete`: Permission to remove data (DELETE)

#### 2. Table-Level Permissions

Permissions are defined per table, allowing administrators to control which tables a user can access.

#### 3. Column-Level Restrictions

For each table permission, column access can be restricted in two ways:

- **Allowed Columns**: Only specified columns can be accessed
- **Denied Columns**: All columns except specified ones can be accessed

#### 4. Row-Level Restrictions (Conditional Access)

Permissions can include WHERE conditions that are automatically applied to all operations, restricting access to specific rows based on conditions.

### Permission Examples

#### Basic Table Permission

```json
{
  "user_id": 2,
  "table_name": "products",
  "can_select": true,
  "can_insert": true,
  "can_update": true,
  "can_delete": false
}
```

#### Permission with Column Restrictions

```json
{
  "user_id": 2,
  "table_name": "users",
  "can_select": true,
  "can_insert": false,
  "can_update": false,
  "can_delete": false,
  "column_restrictions": {
    "allowed": ["id", "name", "email"]
  }
}
```

#### Permission with Row-Level Restrictions

```json
{
  "user_id": 2,
  "table_name": "orders",
  "can_select": true,
  "can_insert": true,
  "can_update": true,
  "can_delete": false,
  "where_conditions": [
    ["user_id", "=", 2]
  ]
}
```

## Error Codes and Troubleshooting

| Status Code | Error                 | Description                                          | Solution                                            |
|-------------|------------------------|------------------------------------------------------|-----------------------------------------------------|
| 401         | Missing API key        | The X-API-Key header is missing                      | Include your API key in the X-API-Key header        |
| 401         | Invalid or expired key | The provided API key is not valid or has expired     | Login again or refresh your API key                 |
| 403         | Forbidden              | You don't have permission for the requested operation | Request access from an administrator                |
| 422         | Validation error       | The request data failed validation                   | Check the error details and fix your request format |

## User Management via CLI

Administrators can manage users through a custom Artisan command. This provides a convenient command-line interface for user management operations without requiring API access.

### Command Syntax

```bash
php artisan dbaas:user {action?} [options]
```

### Available Actions

- **add**: Create a new user
- **remove**: Delete an existing user
- **update**: Modify an existing user's details
- **list**: Display all users (default action)

### Options

| Option        | Description                           |
|---------------|---------------------------------------|
| `--id=`       | User ID for update/remove operations  |
| `--name=`     | User name                             |
| `--email=`    | User email                            |
| `--password=` | User password                         |
| `--role=`     | User role (admin or user)             |
| `--refresh-key` | Generate a new API key              |

### Examples

**List all users:**
```bash
php artisan dbaas:user list
```

**Add a new user:**
```bash
php artisan dbaas:user add --name="Admin User" --email="admin@example.com" --password="secure123" --role="admin"
```

**Update a user:**
```bash
php artisan dbaas:user update --id=1 --role="admin" --refresh-key
```

**Remove a user:**
```bash
php artisan dbaas:user remove --id=2
```

**Interactive mode:**
If you run the command without specifying all required options, it will prompt you for the missing information:
```bash
php artisan dbaas:user
```

## Best Practices

1. **Store API Keys Securely**: Never expose your API key in client-side code or public repositories.

2. **Refresh Keys Regularly**: Implement a routine to refresh your API key before it expires.

3. **Use HTTPS**: Always use HTTPS when communicating with the DBaaS API to protect your API key in transit.

4. **Implement Least Privilege**: Request only the minimum permissions needed for your application.

5. **Handle Expired Keys**: Implement proper error handling for expired API keys in your application.

6. **User Management**: Use the Artisan command `php artisan dbaas:user` to manage users (add, remove, update, list).
