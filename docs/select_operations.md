# Select Operations

This document provides comprehensive documentation for the select operations in the DBaaS API.

## Overview

Select operations allow you to retrieve records from a database table. In the DBaaS API, these operations can be performed using either HTTP GET or POST requests.

## Endpoints

```
GET /api/db
POST /api/db  (with "method": "select" in the request body)
```

## Method Selection

The DBaaS API offers two ways to perform SELECT operations:

1. **GET Method**: Traditional RESTful approach using query parameters
2. **POST Method**: JSON-based approach for complex queries

Choose the method that best fits your needs:
- Use **GET** for simple queries and better cacheability
- Use **POST** for complex queries, avoiding URL length limitations, and when you need a more structured query format

## Headers

| Header      | Required | Description                                      |
|-------------|----------|--------------------------------------------------|
| X-API-Key   | Yes      | Your API key for authentication                  |
| Accept      | No       | Set to `application/json` for JSON responses     |

## Request Parameters

Select operations use query parameters to define the selection criteria:

```
GET /api/db?table=users&columns[]=id&columns[]=name&where[column]=age&where[operator]=>&where[value]=18&limit=10&offset=0
```

### Required Parameters

| Parameter | Type   | Description                                        |
|-----------|--------|----------------------------------------------------|
| table     | string | The name of the table to select data from          |

### Optional Parameters

| Parameter | Type    | Default | Description                                                                  |
|-----------|---------|---------|------------------------------------------------------------------------------|
| columns   | array   | ['*']   | Array of column names to return (default is all columns)                     |
| where     | array   | []      | Conditions to filter the results (see Where Conditions section)              |
| order_by  | array   | []      | Columns to sort by (see Order By section)                                    |
| limit     | integer | null    | Maximum number of records to return                                          |
| offset    | integer | 0       | Number of records to skip (for pagination)                                   |

## Where Conditions

Where conditions can be specified in two formats:

### Simple Format

```
where[column]=name&where[operator]=like&where[value]=%John%
```

### Advanced Format (Multiple Conditions)

```
where[0][column]=age&where[0][operator]=>&where[0][value]=18&where[1][column]=status&where[1][operator]===&where[1][value]=active
```

### Supported Operators

| Operator | Description           | Example                                |
|----------|-----------------------|----------------------------------------|
| =        | Equal                 | `where[operator]===&where[value]=10`   |
| !=       | Not equal             | `where[operator]=!=&where[value]=10`   |
| >        | Greater than          | `where[operator]=>&where[value]=10`    |
| >=       | Greater than or equal | `where[operator]=>%3D&where[value]=10` |
| <        | Less than             | `where[operator]=<&where[value]=10`    |
| <=       | Less than or equal    | `where[operator]=<%3D&where[value]=10` |
| like     | LIKE pattern match    | `where[operator]=like&where[value]=%a%`|
| in       | IN list               | `where[operator]=in&where[value][]=1&where[value][]=2` |
| not in   | NOT IN list           | `where[operator]=not in&where[value][]=1&where[value][]=2` |
| between  | Between two values    | `where[operator]=between&where[value][]=1&where[value][]=10` |

## Order By

Order by parameters can be specified in two formats:

### Simple Format (Single Column)

```
order_by[column]=name&order_by[direction]=asc
```

### Advanced Format (Multiple Columns)

```
order_by[0][column]=name&order_by[0][direction]=asc&order_by[1][column]=created_at&order_by[1][direction]=desc
```

The `direction` parameter can be either `asc` (ascending) or `desc` (descending).

## Response

### Success Response (200 OK)

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      ...
    },
    {
      "id": 2,
      "name": "Jane Smith",
      "email": "jane@example.com",
      ...
    }
  ],
  "count": 2
}
```

### Empty Result (200 OK)

```json
{
  "success": true,
  "data": [],
  "count": 0
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
  "message": "You do not have permission to select from this table"
}
```

## SELECT via POST Method

For complex queries or to avoid URL length limitations, you can use the POST method to perform SELECT operations.

### Request Body

The request body must be a JSON object with the following structure:

```json
{
  "method": "select",
  "table": "users",
  "columns": ["id", "name", "email"],
  "where": [
    {
      "column": "age",
      "operator": ">",
      "value": 18
    }
  ],
  "order_by": [
    {
      "column": "name",
      "direction": "asc"
    }
  ],
  "limit": 10,
  "offset": 0
}
```

### Required Parameters

| Parameter | Type   | Description                                        |
|-----------|--------|-------------------------------------------------|
| method    | string | Must be "select"                                   |
| table     | string | The name of the table to select data from          |

### Optional Parameters

The optional parameters are the same as for the GET method:

| Parameter | Type    | Default | Description                                                                  |
|-----------|---------|---------|----------------------------------------------------------------------------|
| columns   | array   | ['*']   | Array of column names to return (default is all columns)                     |
| where     | array   | []      | Conditions to filter the results (see Where Conditions section)              |
| order_by  | array   | []      | Columns to sort by (see Order By section)                                    |
| limit     | integer | null    | Maximum number of records to return                                          |
| offset    | integer | 0       | Number of records to skip (for pagination)                                   |

The format for where conditions and order_by is the same as described in the previous sections.

### Example: Complex SELECT via POST

**Request:**

```http
POST /api/db HTTP/1.1
Host: your-dbaas-api.com
X-API-Key: your-api-key
Content-Type: application/json

{
  "method": "select",
  "table": "orders",
  "columns": ["id", "customer_name", "total", "created_at"],
  "where": [
    {
      "column": "status",
      "operator": "=",
      "value": "completed"
    },
    {
      "column": "total",
      "operator": ">",
      "value": 100
    },
    {
      "column": "created_at",
      "operator": ">=",
      "value": "2025-01-01"
    }
  ],
  "order_by": [
    {
      "column": "created_at",
      "direction": "desc"
    }
  ],
  "limit": 20,
  "offset": 0
}
```

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1045,
      "customer_name": "John Doe",
      "total": 299.99,
      "created_at": "2025-05-15 14:30:00"
    },
    {
      "id": 1032,
      "customer_name": "Jane Smith",
      "total": 149.99,
      "created_at": "2025-05-10 09:15:00"
    }
  ],
  "count": 2
}
```

### Advantages of Using POST for SELECT

1. **Complex Queries**: Easily construct complex queries with multiple conditions and sorting options
2. **No URL Length Limitations**: Avoid issues with long query strings exceeding URL length limits
3. **Structured Data**: JSON provides a more structured format for complex query parameters
4. **Consistent Interface**: Use the same endpoint format for both data retrieval and insertion

## Examples of GET Method

### Basic Select (All Columns)

**Request:**

```http
GET /api/db?table=products HTTP/1.1
Host: your-dbaas-api.com
X-API-Key: your-api-key
```

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Smartphone X",
      "price": 999.99,
      "category_id": 2,
      "description": "Latest model with advanced features",
      "in_stock": true,
      "created_at": "2025-05-15T10:30:00.000000Z",
      "updated_at": "2025-06-01T14:20:15.000000Z"
    },
    {
      "id": 2,
      "name": "Laptop Pro",
      "price": 1499.99,
      "category_id": 1,
      "description": "High-performance laptop for professionals",
      "in_stock": true,
      "created_at": "2025-05-20T09:15:00.000000Z",
      "updated_at": "2025-05-20T09:15:00.000000Z"
    }
  ],
  "count": 2
}
```

### Select Specific Columns

**Request:**

```http
GET /api/db?table=products&columns[]=id&columns[]=name&columns[]=price HTTP/1.1
Host: your-dbaas-api.com
X-API-Key: your-api-key
```

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Smartphone X",
      "price": 999.99
    },
    {
      "id": 2,
      "name": "Laptop Pro",
      "price": 1499.99
    }
  ],
  "count": 2
}
```

### Filter with Where Condition

**Request:**

```http
GET /api/db?table=products&where[column]=price&where[operator]=>&where[value]=1000 HTTP/1.1
Host: your-dbaas-api.com
X-API-Key: your-api-key
```

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 2,
      "name": "Laptop Pro",
      "price": 1499.99,
      "category_id": 1,
      "description": "High-performance laptop for professionals",
      "in_stock": true,
      "created_at": "2025-05-20T09:15:00.000000Z",
      "updated_at": "2025-05-20T09:15:00.000000Z"
    }
  ],
  "count": 1
}
```

### Multiple Where Conditions

**Request:**

```http
GET /api/db?table=products&where[0][column]=category_id&where[0][operator]===&where[0][value]=1&where[1][column]=in_stock&where[1][operator]===&where[1][value]=true HTTP/1.1
Host: your-dbaas-api.com
X-API-Key: your-api-key
```

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 2,
      "name": "Laptop Pro",
      "price": 1499.99,
      "category_id": 1,
      "description": "High-performance laptop for professionals",
      "in_stock": true,
      "created_at": "2025-05-20T09:15:00.000000Z",
      "updated_at": "2025-05-20T09:15:00.000000Z"
    }
  ],
  "count": 1
}
```

### Order By with Limit and Offset

**Request:**

```http
GET /api/db?table=products&order_by[column]=price&order_by[direction]=desc&limit=1&offset=1 HTTP/1.1
Host: your-dbaas-api.com
X-API-Key: your-api-key
```

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Smartphone X",
      "price": 999.99,
      "category_id": 2,
      "description": "Latest model with advanced features",
      "in_stock": true,
      "created_at": "2025-05-15T10:30:00.000000Z",
      "updated_at": "2025-06-01T14:20:15.000000Z"
    }
  ],
  "count": 1
}
```

## Permissions

To perform select operations, the authenticated user must have:

1. The `can_select` permission for the specific table
2. Access to all columns being selected (not in column restrictions)
3. The operation must satisfy any WHERE conditions in the permission

## Error Codes and Troubleshooting

| Error Code | Description                        | Solution                                      |
|------------|------------------------------------|-----------------------------------------------|
| 400        | Invalid request format             | Check your query parameters and data types    |
| 401        | Authentication failure             | Verify your API key is valid and not expired  |
| 403        | Permission denied                  | Ensure you have select permissions            |
| 404        | Table not found                    | Verify the table name exists                  |
| 422        | Validation error                   | Check the error message for specific issues   |
| 500        | Server error                       | Contact the administrator                     |
