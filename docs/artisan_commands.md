# DBaaS Artisan Commands

This document provides detailed information about the Artisan commands available in the DBaaS project for managing database tables and users.

## Table Management Commands

DBaaS provides several Artisan commands to help you manage database tables directly from the command line.

### Table Listing

#### Command: `dbaas:table:list`

Lists all tables in the database with optional column details.

**Options:**
- `--details` - Show column details for each table

**Examples:**

List all tables:
```bash
php artisan dbaas:table:list
```

List all tables with column details:
```bash
php artisan dbaas:table:list --details
```

### Table Creation

#### Command: `dbaas:table:create`

Creates a new database table interactively.

This command guides you through the process of creating a new table by asking for:
- Table name
- Column definitions (name, type, length, nullable, default, etc.)
- Primary key
- Indexes

**Example:**
```bash
php artisan dbaas:table:create
```

The command will prompt you for all necessary information to create the table.

### Table Modification

#### Command: `dbaas:table:modify`

Modifies an existing database table structure.

**Arguments:**
- `table` - The name of the table to modify

**Example:**
```bash
php artisan dbaas:table:modify users
```

This command provides an interactive interface to:
- Add new columns
- Modify existing columns
- Drop columns
- Add or remove indexes
- Rename the table

### Table Deletion

#### Command: `dbaas:table:delete`

Deletes an existing database table.

**Arguments:**
- `table` (optional) - The name of the table to delete

**Examples:**

Delete a specific table:
```bash
php artisan dbaas:table:delete users
```

Interactive mode (will prompt for table selection):
```bash
php artisan dbaas:table:delete
```

### Table Data Export

#### Command: `dbaas:table:export`

Exports table data to JSON or CSV format.

**Arguments:**
- `table` (optional) - The table to export data from

**Options:**
- `--format=json` - Export format (json, csv)
- `--output=storage/exports` - Output directory
- `--filename=` - Custom filename (without extension)

**Examples:**

Export a table to JSON:
```bash
php artisan dbaas:table:export users --format=json
```

Export a table to CSV with custom filename and location:
```bash
php artisan dbaas:table:export products --format=csv --output=storage/backups --filename=products_backup
```

### Table Data Seeding

#### Command: `dbaas:table:seed`

Seeds data into a database table.

**Arguments:**
- `table` (optional) - The table to seed data into

**Example:**
```bash
php artisan dbaas:table:seed users
```

This command provides an interactive interface to:
- Generate random data
- Specify the number of records to create
- Customize data generation for specific columns

## User and Permission Management Commands

### User Management

#### Command: `dbaas:user`

Manages DBaaS users (add, remove, update, list).

**Arguments:**
- `action` (optional) - Action to perform (add, remove, update, list)

**Options:**
- `--id=` - User ID for update/remove operations
- `--name=` - User name
- `--email=` - User email
- `--password=` - User password
- `--role=` - User role (admin or user)
- `--refresh-key` - Generate a new API key

**Examples:**

List all users:
```bash
php artisan dbaas:user list
```

Add a new user:
```bash
php artisan dbaas:user add --name="Admin User" --email="admin@example.com" --password="secure123" --role="admin"
```

Update a user:
```bash
php artisan dbaas:user update --id=1 --role="admin" --refresh-key
```

Remove a user:
```bash
php artisan dbaas:user remove --id=2
```

Interactive mode:
```bash
php artisan dbaas:user
```

### Permission Management

#### Command: `dbaas:permission`

Manages user permissions with granular control over operations, columns, and row-level access.

**Arguments:**
- `action` (optional) - Action to perform (grant, revoke, list, show)

**Options:**
- `--user=` - User ID or email
- `--table=` - Table name
- `--operations=` - Comma-separated list of operations (select,insert,update,delete)
- `--columns-allowed=` - Comma-separated list of allowed columns
- `--columns-denied=` - Comma-separated list of denied columns
- `--where=` - JSON-encoded where conditions
- `--id=` - Permission ID (for show/revoke)

**Examples:**

List all permissions:
```bash
php artisan dbaas:permission list
```

List permissions for a specific user:
```bash
php artisan dbaas:permission list --user=user@example.com
```

Grant permissions to a user:
```bash
php artisan dbaas:permission grant --user=5 --table=customers --operations=select,update
```

Grant permissions with column restrictions:
```bash
php artisan dbaas:permission grant --user=user@example.com --table=orders --operations=select,insert,update --columns-denied=credit_card_number,cvv
```

Grant permissions with WHERE conditions (row-level access control):
```bash
php artisan dbaas:permission grant --user=5 --table=orders --operations=select,update --where='[["user_id","=",5]]'
```

Allow a user to access all records (not just their own):
```bash
php artisan dbaas:permission grant --user=manager@example.com --table=customers --operations=select,update,delete
```

Revoke a permission:
```bash
php artisan dbaas:permission revoke --id=3
```

Show detailed permission information:
```bash
php artisan dbaas:permission show --id=2
```

Interactive mode:
```bash
php artisan dbaas:permission
```

## Best Practices

1. **Backup Before Destructive Operations**: Always back up your data before using commands that modify or delete tables.

2. **Use Interactive Mode for Complex Operations**: For complex table creation or modification, use the interactive mode to ensure all settings are correct.

3. **Automate Common Tasks**: Use these commands in scripts to automate common database management tasks.

4. **Secure User Management**: When creating admin users, use strong passwords and limit the number of admin accounts.

5. **Export Data Regularly**: Use the export command to create regular backups of your important data.

6. **Test in Development First**: Always test table modifications in a development environment before applying them to production.
