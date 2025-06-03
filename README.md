# Database as a Service (DBaaS) API

## Overview

DBaaS is a RESTful API interface that allows you to interact with databases without writing a full backend. It maps HTTP methods to SQL operations with robust authentication and granular access control.

## Features

- **RESTful API**: Maps HTTP methods to SQL operations
  - GET → SELECT SQL statements
  - POST → INSERT SQL statements
  - PUT → UPDATE SQL statements (with UPSERT functionality)
  - DELETE → DELETE SQL statements
- **Authentication**: API key-based authentication with expiration
- **Role-Based Access Control**: Admin and user roles with different permission levels
- **Granular Permissions**:
  - Table-specific permissions
  - Operation-specific permissions (select, insert, update, delete)
  - Column-level restrictions
  - Conditional access with WHERE clauses
- **Security**: Input validation, query sanitization, and error handling

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

## Documentation

For detailed documentation on how to use the DBaaS API, including all available endpoints, request/response formats, and examples, please refer to the [documentation index](/docs/index.md).

The documentation covers:

- Authentication and authorization
- Database operations (SELECT, INSERT, UPDATE, DELETE)
- Permission management
- Error handling and troubleshooting

## License

This project is licensed under the MIT License - see the LICENSE file for details.

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
