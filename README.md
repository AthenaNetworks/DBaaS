# Database as a Service (DBaaS) API

## Overview

DBaaS is a powerful RESTful API interface that allows you to interact with databases without writing a full backend. It maps HTTP methods to SQL operations with robust authentication and granular access control, providing a secure and flexible way to manage database operations through a standardized API.

## Features

### Database Operations

- **RESTful API**: Maps HTTP methods to SQL operations
  - GET → SELECT SQL statements with support for filtering, sorting, and pagination
  - POST → INSERT SQL statements with validation and bulk insertion capabilities
  - PUT → UPDATE SQL statements with UPSERT functionality for create-or-update operations
  - DELETE → DELETE SQL statements with conditional deletion
- **Complex Queries**: Support for complex WHERE conditions, JOINs, and aggregate functions
- **Data Export**: Export table data to JSON or CSV formats

### Security & Access Control

- **Authentication**: API key-based authentication with 30-day expiration and refresh capabilities
- **Role-Based Access Control**: Two-tier role system
  - **Admin**: Full system access with user management capabilities
  - **User**: Limited access based on explicitly granted permissions
- **Granular Permissions**:
  - Table-specific permissions for precise access control
  - Operation-specific permissions (select, insert, update, delete)
  - Column-level restrictions to hide sensitive data
  - Row-level filtering with WHERE conditions for data isolation
- **Security**: Input validation, query sanitization, prepared statements, and comprehensive error handling

### Administration

- **CLI Tools**: Comprehensive Artisan commands for administration
  - User management (add, remove, update, list)
  - Permission management with granular control
  - Table management (create, modify, delete, list, export, seed)
- **Interactive Mode**: All commands support both interactive and non-interactive usage
- **Detailed Logging**: Track all database operations with user attribution

## Installation

### Requirements

- PHP 8.1 or higher
- Composer
- MySQL, PostgreSQL, or SQLite database
- Web server (Apache, Nginx, etc.)

### Step-by-Step Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/AthenaNetworks/DBaaS.git
   cd DBaaS
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Set up environment variables**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   
4. **Configure your database connection in the .env file**
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=dbaas
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Run migrations and seed the database**
   ```bash
   php artisan migrate
   php artisan db:seed --class=UsersAndPermissionsSeeder
   ```

6. **Configure your web server**
   
   For Apache, ensure the document root points to the `/public` directory.
   
   For Nginx, use a configuration similar to:
   ```
   server {
       listen 80;
       server_name dbaas.example.com;
       root /path/to/DBaaS/public;

       add_header X-Frame-Options "SAMEORIGIN";
       add_header X-Content-Type-Options "nosniff";

       index index.php;

       charset utf-8;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location = /favicon.ico { access_log off; log_not_found off; }
       location = /robots.txt  { access_log off; log_not_found off; }

       error_page 404 /index.php;

       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
           fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
           include fastcgi_params;
       }

       location ~ /\.(?!well-known).* {
           deny all;
       }
   }
   ```

7. **Start the development server (for local development)**
   ```bash
   php artisan serve
   ```
   
   Your API will be available at `http://localhost:8000`

## User and Permission Management

### User Management

DBaaS provides a powerful Artisan command for managing users without requiring API access:

```bash
php artisan dbaas:user {action?} [options]
```

Available actions:
- `add` - Create a new user with name, email, password, and role
- `remove` - Delete an existing user with confirmation
- `update` - Modify user details including role and API key refresh
- `list` - Display all users with their details (default)

Examples:

```bash
# Create an admin user
php artisan dbaas:user add --name="Admin User" --email="admin@example.com" --password="secure123" --role="admin"

# Update a user's role and refresh their API key
php artisan dbaas:user update --id=1 --role="admin" --refresh-key

# List all users with their details
php artisan dbaas:user list
```

### Permission Management

The granular permission system is managed through a dedicated Artisan command:

```bash
php artisan dbaas:permission {action?} [options]
```

Available actions:
- `grant` - Grant permissions to a user with precise control
- `revoke` - Remove permissions from a user
- `list` - List all permissions with filtering options
- `show` - Show detailed information about a specific permission

Examples:

```bash
# Grant a user permission to select and update records in a table
php artisan dbaas:permission grant --user=5 --table=customers --operations=select,update

# Grant permissions with column restrictions (hide sensitive data)
php artisan dbaas:permission grant --user=user@example.com --table=orders --operations=select,insert,update --columns-denied=credit_card_number,cvv

# Grant permissions with row-level filtering (user can only see their own records)
php artisan dbaas:permission grant --user=5 --table=orders --operations=select,update --where='[["user_id","=",5]]'
```

For more details on user and permission management, see the [authentication documentation](/docs/authentication.md) and [permission management documentation](/docs/permission_management.md).

## Documentation

For detailed documentation on how to use the DBaaS API, including all available endpoints, request/response formats, and examples, please refer to the [documentation index](/docs/index.md).

The documentation includes:

- [Authentication and Authorization](docs/authentication.md) - API key management and security
- [User Roles and Permissions](docs/user_roles.md) - Role-based access control system
- [Permission Management](docs/permission_management.md) - Granular permission control
- [Artisan Commands](docs/artisan_commands.md) - CLI tools for administration
- Database operations:
  - SELECT operations with filtering, sorting, and pagination
  - INSERT operations with validation
  - UPDATE operations with upsert functionality
  - DELETE operations with conditions
- Error handling and troubleshooting

## License

This project is licensed under the MIT License - see the LICENSE file for details.

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
