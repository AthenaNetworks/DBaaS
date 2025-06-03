# Delete Operations

This document provides comprehensive documentation for the delete operations in the DBaaS API.

## Overview

Delete operations allow you to remove records from a database table. In the DBaaS API, these operations are mapped to HTTP DELETE requests.

## Endpoint

```
DELETE /api/db
```

## Headers

| Header      | Required | Description                                      |
|-------------|----------|--------------------------------------------------|
| X-API-Key   | Yes      | Your API key for authentication                  |
| Content-Type| Yes      | Must be `application/json`                       |
| Accept      | No       | Set to `application/json` for JSON responses     |

## Request Body

The request body must be a JSON object with the following structure:

```json
{
  "table": "users",
  "where": [
    {
      "column": "id",
      "operator": "=",
      "value": 1
    }
  ]
}
```

### Required Parameters

| Parameter | Type   | Description                                        |
|-----------|--------|----------------------------------------------------|
| table     | string | The name of the table to delete data from          |
| where     | array  | Conditions to identify which records to delete (see Where Conditions section) |

**IMPORTANT**: For safety reasons, the `where` parameter is required. To delete all records from a table, you must explicitly specify a condition that matches all records.

## Where Conditions

Where conditions are specified as an array of objects, each with the following properties:

```json
{
  "where": [
    {
      "column": "id",
      "operator": "=",
      "value": 1
    },
    {
      "column": "status",
      "operator": "=",
      "value": "inactive"
    }
  ]
}
```

### Supported Operators

| Operator | Description           | Example                                |
|----------|-----------------------|----------------------------------------|
| =        | Equal                 | `{"operator": "=", "value": 10}`       |
| !=       | Not equal             | `{"operator": "!=", "value": 10}`      |
| >        | Greater than          | `{"operator": ">", "value": 10}`       |
| >=       | Greater than or equal | `{"operator": ">=", "value": 10}`      |
| <        | Less than             | `{"operator": "<", "value": 10}`       |
| <=       | Less than or equal    | `{"operator": "<=", "value": 10}`      |
| like     | LIKE pattern match    | `{"operator": "like", "value": "%a%"}` |
| in       | IN list               | `{"operator": "in", "value": [1, 2]}`  |
| not in   | NOT IN list           | `{"operator": "not in", "value": [1, 2]}` |
| between  | Between two values    | `{"operator": "between", "value": [1, 10]}` |

## Response

### Success Response (200 OK)

```json
{
  "success": true,
  "message": "2 record(s) deleted",
  "affected": 2
}
```

### Error Response (400 Bad Request)

```json
{
  "error": "Invalid argument",
  "message": "DELETE operations require where conditions"
}
```

### Error Response (401 Unauthorized)

```json
{
  "error": "Unauthorized",
  "message": "Invalid or expired API key"
}
```

### Error Response (403 Forbidden)

```json
{
  "error": "Forbidden",
  "message": "You do not have permission to delete records from this table"
}
```

## Examples

### Delete a Single Record

**Request:**

```http
DELETE /api/db HTTP/1.1
Host: your-dbaas-api.com
X-API-Key: your-api-key
Content-Type: application/json

{
  "table": "products",
  "where": [
    {
      "column": "id",
      "operator": "=",
      "value": 15
    }
  ]
}
```

**Response:**

```json
{
  "success": true,
  "message": "1 record(s) deleted",
  "affected": 1
}
```

### Delete Multiple Records

**Request:**

```http
DELETE /api/db HTTP/1.1
Host: your-dbaas-api.com
X-API-Key: your-api-key
Content-Type: application/json

{
  "table": "products",
  "where": [
    {
      "column": "category_id",
      "operator": "=",
      "value": 3
    },
    {
      "column": "in_stock",
      "operator": "=",
      "value": false
    }
  ]
}
```

**Response:**

```json
{
  "success": true,
  "message": "5 record(s) deleted",
  "affected": 5
}
```

### Delete Records with Complex Conditions

**Request:**

```http
DELETE /api/db HTTP/1.1
Host: your-dbaas-api.com
X-API-Key: your-api-key
Content-Type: application/json

{
  "table": "orders",
  "where": [
    {
      "column": "status",
      "operator": "=",
      "value": "cancelled"
    },
    {
      "column": "created_at",
      "operator": "<",
      "value": "2025-01-01 00:00:00"
    }
  ]
}
```

**Response:**

```json
{
  "success": true,
  "message": "12 record(s) deleted",
  "affected": 12
}
```

### Delete All Records (Explicit)

**Request:**

```http
DELETE /api/db HTTP/1.1
Host: your-dbaas-api.com
X-API-Key: your-api-key
Content-Type: application/json

{
  "table": "temporary_logs",
  "where": [
    {
      "column": "id",
      "operator": ">",
      "value": 0
    }
  ]
}
```

**Response:**

```json
{
  "success": true,
  "message": "156 record(s) deleted",
  "affected": 156
}
```

## Safety Considerations

1. **Required Where Conditions**: The API requires where conditions for all delete operations to prevent accidental deletion of all records.
2. **Permissions**: Users must have explicit delete permissions for the table.
3. **Soft Deletes**: If the table uses Laravel's soft delete feature, records will be soft-deleted (marked as deleted) rather than permanently removed.
4. **Foreign Key Constraints**: Delete operations will fail if they would violate foreign key constraints, unless the database is configured with cascading deletes.

## Permissions

To perform delete operations, the authenticated user must have:

1. The `can_delete` permission for the specific table
2. The operation must satisfy any WHERE conditions in the permission

## Error Codes and Troubleshooting

| Error Code | Description                        | Solution                                      |
|------------|------------------------------------|-----------------------------------------------|
| 400        | Invalid request format             | Check your JSON structure and data types      |
| 400        | Missing where conditions           | Add where conditions to specify which records to delete |
| 401        | Authentication failure             | Verify your API key is valid and not expired  |
| 403        | Permission denied                  | Ensure you have delete permissions            |
| 404        | Table not found                    | Verify the table name exists                  |
| 422        | Validation error                   | Check the error message for specific issues   |
| 500        | Server error                       | Contact the administrator                     |
