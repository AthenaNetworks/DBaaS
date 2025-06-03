# Insert Operations

This document provides comprehensive documentation for the insert operations in the DBaaS API.

## Overview

Insert operations allow you to add new records to a database table. In the DBaaS API, these operations are mapped to HTTP POST requests.

## Endpoint

```
POST /api/data/{table_name}
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
  "data": {
    "column1": "value1",
    "column2": "value2",
    ...
  },
  "options": {
    "returning": ["column1", "column2"],
    "ignore_duplicates": false
  }
}
```

### Data Object

The `data` object contains the column names and values to insert. Each key represents a column name, and its value is the data to insert.

### Options Object

| Option           | Type    | Default | Description                                                                  |
|------------------|---------|---------|------------------------------------------------------------------------------|
| returning        | array   | []      | Array of column names to return after insertion                              |
| ignore_duplicates| boolean | false   | If true, duplicate key errors will be ignored (similar to INSERT IGNORE)     |

## Response

### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Record inserted successfully",
  "data": {
    "id": 1,
    "column1": "value1",
    "column2": "value2",
    ...
  },
  "meta": {
    "affected_rows": 1
  }
}
```

The `data` object will only include the columns specified in the `returning` option, plus the primary key.

### Error Response (400 Bad Request)

```json
{
  "success": false,
  "message": "Error inserting record",
  "errors": {
    "column1": ["The column1 field is required."],
    ...
  }
}
```

### Error Response (401 Unauthorized)

```json
{
  "success": false,
  "message": "Unauthorized",
  "errors": ["Invalid or expired API key"]
}
```

### Error Response (403 Forbidden)

```json
{
  "success": false,
  "message": "Forbidden",
  "errors": ["You do not have permission to insert records into this table"]
}
```

## Examples

### Basic Insert

**Request:**

```http
POST /api/data/products HTTP/1.1
Host: your-dbaas-api.com
X-API-Key: your-api-key
Content-Type: application/json

{
  "data": {
    "name": "Smartphone X",
    "price": 999.99,
    "category_id": 2,
    "description": "Latest model with advanced features",
    "in_stock": true
  }
}
```

**Response:**

```json
{
  "success": true,
  "message": "Record inserted successfully",
  "data": {
    "id": 15
  },
  "meta": {
    "affected_rows": 1
  }
}
```

### Insert with Returning Columns

**Request:**

```http
POST /api/data/products HTTP/1.1
Host: your-dbaas-api.com
X-API-Key: your-api-key
Content-Type: application/json

{
  "data": {
    "name": "Smartphone Y",
    "price": 1299.99,
    "category_id": 2,
    "description": "Premium model with enhanced features",
    "in_stock": true
  },
  "options": {
    "returning": ["name", "price", "created_at"]
  }
}
```

**Response:**

```json
{
  "success": true,
  "message": "Record inserted successfully",
  "data": {
    "id": 16,
    "name": "Smartphone Y",
    "price": 1299.99,
    "created_at": "2025-06-03T11:10:15.000000Z"
  },
  "meta": {
    "affected_rows": 1
  }
}
```

### Insert with Ignore Duplicates

**Request:**

```http
POST /api/data/products HTTP/1.1
Host: your-dbaas-api.com
X-API-Key: your-api-key
Content-Type: application/json

{
  "data": {
    "id": 15,  // This ID already exists
    "name": "Smartphone Z",
    "price": 799.99,
    "category_id": 3
  },
  "options": {
    "ignore_duplicates": true
  }
}
```

**Response (when duplicate exists):**

```json
{
  "success": true,
  "message": "Operation completed with warnings",
  "data": null,
  "meta": {
    "affected_rows": 0,
    "warnings": ["Duplicate entry for key 'PRIMARY'"]
  }
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

To perform insert operations, the authenticated user must have:

1. The `can_insert` permission for the specific table
2. Access to all columns being inserted (not in column restrictions)
3. The operation must satisfy any WHERE conditions in the permission

## Error Codes and Troubleshooting

| Error Code | Description                        | Solution                                      |
|------------|------------------------------------|-----------------------------------------------|
| 400        | Invalid request format             | Check your JSON structure and data types      |
| 401        | Authentication failure             | Verify your API key is valid and not expired  |
| 403        | Permission denied                  | Ensure you have insert permissions            |
| 404        | Table not found                    | Verify the table name exists                  |
| 422        | Validation error                   | Check the error message for specific issues   |
| 500        | Server error                       | Contact the administrator                     |
