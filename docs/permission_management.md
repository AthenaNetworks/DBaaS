# Permission Management

## Overview

The DBaaS system provides a powerful Artisan command for managing user permissions with granular control. This document provides detailed information about the `dbaas:permission` command, its capabilities, and examples of common use cases.

## Command Signature

```bash
php artisan dbaas:permission {action?} [options]
```

Where `{action?}` is one of:
- `grant` - Grant permissions to a user
- `revoke` - Remove permissions from a user
- `list` - List all permissions
- `show` - Show detailed information about a specific permission

## Options

| Option | Description |
|--------|-------------|
| `--user=` | User ID or email address |
| `--table=` | Table name |
| `--operations=` | Comma-separated list of operations (select,insert,update,delete) |
| `--columns-allowed=` | Comma-separated list of allowed columns |
| `--columns-denied=` | Comma-separated list of denied columns |
| `--where=` | JSON-encoded where conditions |
| `--id=` | Permission ID (for show/revoke) |

## Understanding Permission Components

### Operations

Operations define what actions a user can perform on a table:

- `select` - Ability to read data from the table
- `insert` - Ability to add new records to the table
- `update` - Ability to modify existing records in the table
- `delete` - Ability to remove records from the table

### Column Restrictions

Column restrictions limit which columns a user can access. There are two types:

1. **Allowed Columns**: Only specified columns can be accessed. All other columns are automatically denied.
2. **Denied Columns**: Specified columns cannot be accessed. All other columns are automatically allowed.

**Note**: You should use either allowed columns OR denied columns, not both simultaneously.

### Where Conditions (Row-Level Access)

Where conditions filter which rows a user can access. They are defined as JSON arrays with the following structure:

```json
[
  ["column_name", "operator", "value"],
  ["another_column", "another_operator", "another_value"]
]
```

Each condition is an array with three elements:
1. Column name
2. Operator (=, !=, >, <, >=, <=, in, not in, like, not like)
3. Value (can be a scalar or an array for 'in' and 'not in' operators)

Multiple conditions are combined with AND logic.

## Detailed Usage

### Interactive Mode

Running the command without arguments enters interactive mode:

```bash
php artisan dbaas:permission
```

This will guide you through the process with prompts for:
1. Selecting an action
2. Choosing a user
3. Selecting a table
4. Specifying operations
5. Setting column restrictions (if desired)
6. Defining where conditions (if desired)

### Listing Permissions

#### List All Permissions

```bash
php artisan dbaas:permission list
```

This displays a table with:
- Permission ID
- User name and email
- Table name
- Allowed operations
- Indicators for column restrictions and where conditions

#### List Permissions for a Specific User

```bash
php artisan dbaas:permission list --user=5
```

Or by email:

```bash
php artisan dbaas:permission list --user=user@example.com
```

#### List Permissions for a Specific Table

```bash
php artisan dbaas:permission list --table=customers
```

### Viewing Permission Details

To see detailed information about a specific permission:

```bash
php artisan dbaas:permission show --id=3
```

This displays:
- User information
- Table name
- Allowed operations
- Column restrictions (if any)
- Where conditions (if any)
- Creation and update timestamps

### Granting Permissions

#### Basic Permission Grant

Grant a user permission to select and update records in a table:

```bash
php artisan dbaas:permission grant --user=5 --table=customers --operations=select,update
```

#### Grant All Operations

Grant a user permission to perform all operations on a table:

```bash
php artisan dbaas:permission grant --user=admin@example.com --table=products --operations=select,insert,update,delete
```

#### Column Restrictions

Allow access to only specific columns:

```bash
php artisan dbaas:permission grant --user=5 --table=customers --operations=select --columns-allowed=id,name,email,phone
```

Deny access to sensitive columns:

```bash
php artisan dbaas:permission grant --user=5 --table=customers --operations=select,update --columns-denied=credit_card,ssn,password
```

#### Row-Level Access Control

Restrict a user to only see their own records:

```bash
php artisan dbaas:permission grant --user=5 --table=orders --operations=select,update --where='[["user_id","=",5]]'
```

Restrict access to only active records:

```bash
php artisan dbaas:permission grant --user=5 --table=products --operations=select --where='[["status","=","active"]]'
```

Allow access to records with specific statuses:

```bash
php artisan dbaas:permission grant --user=5 --table=orders --operations=select,update --where='[["status","in",["pending","processing","shipped"]]]'
```

Multiple conditions (user can only access active products in their department):

```bash
php artisan dbaas:permission grant --user=5 --table=products --operations=select,update --where='[["status","=","active"],["department_id","=",3]]'
```

#### Combined Restrictions

Grant select and update permissions with both column and row restrictions:

```bash
php artisan dbaas:permission grant --user=5 --table=customers --operations=select,update --columns-denied=credit_card,ssn --where='[["region_id","=",2]]'
```

### Revoking Permissions

Revoke a permission using its ID:

```bash
php artisan dbaas:permission revoke --id=3
```

## Advanced Examples

### Example 1: Customer Service Representative

A customer service representative should only see active customers in their assigned region, and should not see financial information:

```bash
php artisan dbaas:permission grant --user=rep@example.com --table=customers --operations=select,update --columns-denied=credit_card,bank_account,ssn --where='[["status","=","active"],["region_id","=",2]]'
```

### Example 2: Regional Manager

A regional manager should be able to see all customers in their region, including inactive ones, and can perform all operations except deletion:

```bash
php artisan dbaas:permission grant --user=manager@example.com --table=customers --operations=select,insert,update --where='[["region_id","=",2]]'
```

### Example 3: Finance Department

Finance department needs read-only access to all customer records, including financial information, but only for active accounts:

```bash
php artisan dbaas:permission grant --user=finance@example.com --table=customers --operations=select --where='[["status","=","active"]]'
```

### Example 4: Product Manager

A product manager needs full access to products in their category:

```bash
php artisan dbaas:permission grant --user=product_manager@example.com --table=products --operations=select,insert,update,delete --where='[["category_id","=",5]]'
```

### Example 5: Sales Representative

A sales representative can see and update their own orders and view (but not modify) all products:

```bash
# Orders permission (own orders only)
php artisan dbaas:permission grant --user=sales@example.com --table=orders --operations=select,update --where='[["sales_rep_id","=",10]]'

# Products permission (read-only for all products)
php artisan dbaas:permission grant --user=sales@example.com --table=products --operations=select
```

## Permission Inheritance and Role Interaction

### Admin Users

Users with the `admin` role automatically have full access to all tables and operations. However, you can still create explicit permissions for admin users if needed for specific restrictions.

When granting permissions to an admin user, the system will warn you that the user already has full access but will allow you to create the explicit permission if confirmed.

### Permission Conflicts

If multiple permissions exist for the same user and table, the system will use the most restrictive combination:

- Operations are combined with OR logic (if any permission allows an operation, it's allowed)
- Column restrictions are combined with AND logic (a column must be allowed by all permissions to be accessible)
- Where conditions are combined with AND logic (all conditions from all permissions must be satisfied)

## Best Practices

1. **Principle of Least Privilege**: Grant users only the minimum permissions they need to perform their tasks.

2. **Prefer Column Denial Over Allowance**: It's generally safer to use the `--columns-denied` option rather than `--columns-allowed`, as the latter might inadvertently restrict access to new columns added to the table in the future.

3. **Use Meaningful WHERE Conditions**: Design your where conditions carefully to ensure users can access exactly what they need and nothing more.

4. **Audit Permissions Regularly**: Use the `list` and `show` actions to regularly review permissions and ensure they remain appropriate.

5. **Document Your Permission Structure**: Maintain documentation about which roles should have which permissions in your organization.

6. **Consider Data Sensitivity**: Apply stricter permissions to tables containing sensitive information.

7. **Test Permissions**: After setting up permissions, test them thoroughly to ensure they work as expected.

## Troubleshooting

### Permission Not Taking Effect

If a permission doesn't seem to be working:

1. Check if the user is an admin (admins bypass permission checks)
2. Verify the permission exists using `dbaas:permission list --user=X --table=Y`
3. Check for conflicting permissions
4. Ensure the where conditions are correctly formatted
5. Verify that column restrictions aren't preventing access to required columns

### JSON Formatting for WHERE Conditions

The WHERE conditions must be valid JSON. Common issues include:
- Missing quotes around strings
- Using single quotes instead of double quotes for JSON strings
- Incorrect nesting of arrays

Example of correct formatting:

```bash
--where='[["column","=","value"],["another_column","in",["value1","value2"]]]'
```

### Permission ID Not Found

If you get an error that a permission ID doesn't exist:
1. Use `dbaas:permission list` to see all available permissions
2. Check if the permission was already deleted
3. Verify you're using the correct ID

## Command Reference

### Full Command Signature

```
dbaas:permission 
    {action? : Action to perform (grant, revoke, list, show)}
    {--user= : User ID or email}
    {--table= : Table name}
    {--operations= : Comma-separated list of operations (select,insert,update,delete)}
    {--columns-allowed= : Comma-separated list of allowed columns}
    {--columns-denied= : Comma-separated list of denied columns}
    {--where= : JSON-encoded where conditions}
    {--id= : Permission ID (for show/revoke)}
```
