# Update Operations

This document provides comprehensive documentation for the update operations in the DBaaS API.

## Overview

Update operations allow you to modify existing records in a database table. In the DBaaS API, these operations are mapped to HTTP PUT requests.

## Endpoint

```
PUT /api/db
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
  "data": {
    "name": "Updated Name",
    "email": "updated@example.com"
  },
  "where": [
    {
      "column": "id",
      "operator": "=",
      "value": 1
    }
  ],
  "upsert": false
}
```

### Required Parameters

| Parameter | Type   | Description                                        |
|-----------|--------|----------------------------------------------------|
| table     | string | The name of the table to update data in            |
| data      | object | Key-value pairs of column names and their new values |

### Optional Parameters

| Parameter | Type    | Default | Description                                                                  |
|-----------|---------|---------|------------------------------------------------------------------------------|
| where     | array   | []      | Conditions to identify which records to update (see Where Conditions section) |
| upsert    | boolean | false   | If true, performs an insert if no records match the where conditions         |

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
      "value": "active"
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
  "message": "2 record(s) updated",
  "affected": 2
}
```

### Success Response - Upsert Insert (201 Created)

```json
{
  "success": true,
  "message": "Record inserted (upsert)",
  "id": 15
}
```

### Error Response (400 Bad Request)

```json
{
  "error": "Invalid argument",
  "message": "Table 'non_existent_table' does not exist"
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
  "message": "You do not have permission to update records in this table"
}
```

## Examples

### Basic Update

**Request:**

```http
PUT /api/db HTTP/1.1
Host: your-dbaas-api.com
X-API-Key: your-api-key
Content-Type: application/json

{
  "table": "products",
  "data": {
    "price": 1099.99,
    "description": "Updated description with new features"
  },
  "where": [
    {
      "column": "id",
      "operator": "=",
      "value": 1
    }
  ]
}
```

**Response:**

```json
{
  "success": true,
  "message": "1 record(s) updated",
  "affected": 1
}
```

### Update Multiple Records

**Request:**

```http
PUT /api/db HTTP/1.1
Host: your-dbaas-api.com
X-API-Key: your-api-key
Content-Type: application/json

{
  "table": "products",
  "data": {
    "in_stock": false
  },
  "where": [
    {
      "column": "category_id",
      "operator": "=",
      "value": 3
    },
    {
      "column": "price",
      "operator": "<",
      "value": 500
    }
  ]
}
```

**Response:**

```json
{
  "success": true,
  "message": "5 record(s) updated",
  "affected": 5
}
```

### Upsert Operation (Update or Insert)

**Request:**

```http
PUT /api/db HTTP/1.1
Host: your-dbaas-api.com
X-API-Key: your-api-key
Content-Type: application/json

{
  "table": "products",
  "data": {
    "name": "New Tablet",
    "price": 499.99,
    "category_id": 2,
    "description": "Latest tablet model",
    "in_stock": true
  },
  "where": [
    {
      "column": "id",
      "operator": "=",
      "value": 100
    }
  ],
  "upsert": true
}
```

**Response (when record doesn't exist):**

```json
{
  "success": true,
  "message": "Record inserted (upsert)",
  "id": 100
}
```

**Response (when record exists):**

```json
{
  "success": true,
  "message": "1 record(s) updated",
  "affected": 1
}
```

## Data Types and Validation

The DBaaS API automatically handles type conversion for common data types:

| Database Type | JSON Type  | Notes                                          |
|---------------|------------|------------------------------------------------|
| INTEGER       | number     | Whole numbers                                  |
| DECIMAL/FLOAT | number     | Decimal numbers                                |
| VARCHAR/TEXT  | string     | Text values                                    |
| BOOLEAN       | boolean    | true/false values                              |
| DATE          | string     | Format: "YYYY-MM-DD"                           |
| DATETIME      | string     | Format: "YYYY-MM-DD HH:MM:SS"                  |
| JSON          | object/array| Will be stored as JSON                        |

## Permissions

To perform update operations, the authenticated user must have:

1. The `can_update` permission for the specific table
2. Access to all columns being updated (not in column restrictions)
3. The operation must satisfy any WHERE conditions in the permission

## Error Codes and Troubleshooting

| Error Code | Description                        | Solution                                      |
|------------|------------------------------------|-----------------------------------------------|
| 400        | Invalid request format             | Check your JSON structure and data types      |
| 401        | Authentication failure             | Verify your API key is valid and not expired  |
| 403        | Permission denied                  | Ensure you have update permissions            |
| 404        | Table not found                    | Verify the table name exists                  |
| 422        | Validation error                   | Check the error message for specific issues   |
| 500        | Server error                       | Contact the administrator                     |
