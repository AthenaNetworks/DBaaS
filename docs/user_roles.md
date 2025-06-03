# User Roles and Permissions

## Overview

The DBaaS API implements a robust role-based access control (RBAC) system to manage user privileges. This document explains the available user roles, their capabilities, and how permissions are enforced throughout the system.

## Available Roles

The DBaaS system has two primary user roles:

| Role  | Description |
|-------|-------------|
| admin | System administrators with full access to all features and data |
| user  | Regular users with limited access based on explicitly granted permissions |

### Admin Role

Administrators have unrestricted access to the entire system. Key capabilities include:

- Full access to all database tables and operations
- Ability to create, modify, and delete any table
- User management (create, update, delete users)
- Permission management for other users
- Access to system configuration and settings

Admins bypass all permission checks in the system. This means that regardless of the specific permissions set in the database, users with the admin role will always have access to all operations.

### User Role

Regular users have limited access based on explicitly granted permissions. By default, a user has no access to any tables until permissions are granted by an administrator. User capabilities include:

- Access only to tables they have been granted permission for
- Limited to operations (select, insert, update, delete) explicitly allowed
- May be restricted to specific columns within tables
- May have row-level restrictions via WHERE conditions
- Cannot manage other users or their permissions

## Permission System

### Permission Structure

Permissions in DBaaS are defined at multiple levels:

1. **Operation Level**: Each permission specifies which operations (select, insert, update, delete) are allowed
2. **Table Level**: Permissions are granted per table
3. **Column Level**: Access can be restricted to specific columns
4. **Row Level**: WHERE conditions can limit which rows a user can access

### Permission Model

The Permission model includes the following key attributes:

| Attribute           | Type    | Description |
|---------------------|---------|-------------|
| user_id             | integer | The user this permission applies to |
| table_name          | string  | The database table this permission applies to |
| can_select          | boolean | Whether the user can perform SELECT operations |
| can_insert          | boolean | Whether the user can perform INSERT operations |
| can_update          | boolean | Whether the user can perform UPDATE operations |
| can_delete          | boolean | Whether the user can perform DELETE operations |
| where_conditions    | json    | Optional conditions that restrict row access |
| column_restrictions | json    | Optional restrictions on column access |

### Column Restrictions

Column restrictions can be defined in two ways:

1. **Allowed Columns**: Only specified columns can be accessed
   ```json
   {
     "allowed": ["id", "name", "email"]
   }
   ```

2. **Denied Columns**: All columns except specified ones can be accessed
   ```json
   {
     "denied": ["password", "secret_key"]
   }
   ```

### Row-Level Restrictions

Row-level restrictions are defined as WHERE conditions that are automatically applied to all operations:

```json
[
  ["user_id", "=", 5],
  ["status", "in", ["active", "pending"]]
]
```

These conditions are combined with AND logic and applied to all operations on the table.

## Permission Enforcement

Permissions are enforced through the `ApiAuthMiddleware` which:

1. Authenticates the user via API key
2. Checks if the user has permission for the requested operation on the target table
3. Applies column restrictions to limit which fields can be accessed
4. Adds WHERE conditions to limit row access
5. Rejects the request if any permission check fails

### Permission Check Flow

1. If the user is an admin, all permission checks are bypassed
2. For regular users, the system checks if they have permission for the specific operation on the requested table
3. If permission exists, column restrictions are applied
4. WHERE conditions are added to the query
5. The operation is allowed to proceed if all checks pass

## Managing Permissions

### Via API (Admin Only)

Administrators can manage permissions through the following API endpoints:

| Endpoint                | Method | Description |
|-------------------------|--------|-------------|
| `/api/permissions`      | GET    | List all permissions |
| `/api/permissions/{table}` | GET  | Get permissions for a specific table |
| `/api/permissions`      | POST   | Create a new permission |
| `/api/permissions/{id}` | DELETE | Delete a permission |

### Via Artisan Commands

Permissions can also be managed indirectly through user role assignment using the `dbaas:user` Artisan command:

```bash
php artisan dbaas:user update --id=1 --role="admin"
```

## Examples

### Admin User

An admin user has full access to all tables and operations without any explicit permissions needed:

```php
$user = User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => Hash::make('password'),
    'role' => 'admin',
]);
```

### Regular User with Specific Permissions

A regular user needs explicit permissions for each table they should access:

```php
$user = User::create([
    'name' => 'Regular User',
    'email' => 'user@example.com',
    'password' => Hash::make('password'),
    'role' => 'user',
]);

// Grant permission to the 'products' table
Permission::create([
    'user_id' => $user->id,
    'table_name' => 'products',
    'can_select' => true,
    'can_insert' => true,
    'can_update' => true,
    'can_delete' => false,
    'column_restrictions' => [
        'denied' => ['cost_price', 'supplier_id']
    ],
    'where_conditions' => [
        ['status', '=', 'active']
    ]
]);
```

## Best Practices

1. **Principle of Least Privilege**: Grant users only the minimum permissions they need to perform their tasks.

2. **Use Column Restrictions**: Limit access to sensitive columns using column restrictions.

3. **Implement Row-Level Security**: Use WHERE conditions to implement data isolation between users.

4. **Limit Admin Users**: Restrict the admin role to a small number of trusted users.

5. **Audit Permissions Regularly**: Periodically review and audit user permissions to ensure they remain appropriate.

6. **Document Permission Policies**: Maintain documentation about which roles should have which permissions in your organization.
