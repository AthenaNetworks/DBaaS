#!/bin/bash

# DBaaS Customer Management Example Setup Script
# This script sets up a complete customer management system with DBaaS

# Set script to exit on error
set -e

# Define colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored messages
print_message() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Get the directory of the script
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
ROOT_DIR="$SCRIPT_DIR/.."

# Convert ROOT_DIR to absolute path
ROOT_DIR="$( cd "$ROOT_DIR" && pwd )"

# Check if we're in the right directory structure
if [[ ! -f "$ROOT_DIR/artisan" ]]; then
    print_error "This script must be run from the example directory of the DBaaS installation."
    exit 1
fi

print_message "Starting DBaaS Customer Management Example Setup..."
print_message "This script will:"
print_message "1. Create customers and orders tables"
print_message "2. Seed the tables with sample data"
print_message "3. Create admin, customer service, and sales user accounts"
print_message "4. Set up appropriate permissions for each user"
print_message "5. Deploy a sample customer management dashboard"
echo ""

# Step 1: Create the customers table
print_message "Creating customers table..."
php "$ROOT_DIR/artisan" dbaas:table create --name=customers --schema='{
    "id": {"type": "increments", "primary": true},
    "name": {"type": "string", "length": 100, "nullable": false},
    "email": {"type": "string", "length": 100, "unique": true, "nullable": false},
    "phone": {"type": "string", "length": 20, "nullable": true},
    "created_at": {"type": "timestamp", "nullable": true},
    "updated_at": {"type": "timestamp", "nullable": true}
}'
print_success "Customers table created successfully!"

# Step 2: Create the orders table
print_message "Creating orders table..."
php "$ROOT_DIR/artisan" dbaas:table create --name=orders --schema='{
    "id": {"type": "increments", "primary": true},
    "customer_id": {"type": "integer", "nullable": false, "foreign": "customers.id"},
    "amount": {"type": "decimal", "precision": 10, "scale": 2, "nullable": false},
    "status": {"type": "string", "length": 20, "default": "pending"},
    "order_date": {"type": "timestamp", "nullable": false, "default": "CURRENT_TIMESTAMP"},
    "created_at": {"type": "timestamp", "nullable": true},
    "updated_at": {"type": "timestamp", "nullable": true}
}'
print_success "Orders table created successfully!"

# Step 3: Seed the tables with sample data
print_message "Seeding tables with sample data..."
php "$ROOT_DIR/artisan" dbaas:table seed --table=customers --count=10
php "$ROOT_DIR/artisan" dbaas:table seed --table=orders --count=25
print_success "Tables seeded with sample data!"

# Step 4: Create users
print_message "Creating users..."

# Check if users already exist
ADMIN_EXISTS=$(php "$ROOT_DIR/artisan" dbaas:user list | grep -c "admin@example.com" || true)
CS_EXISTS=$(php "$ROOT_DIR/artisan" dbaas:user list | grep -c "cs@example.com" || true)
SALES_EXISTS=$(php "$ROOT_DIR/artisan" dbaas:user list | grep -c "sales@example.com" || true)

# Create admin user if it doesn't exist
if [ "$ADMIN_EXISTS" -eq 0 ]; then
    php "$ROOT_DIR/artisan" dbaas:user add --name="Admin User" --email="admin@example.com" --password="secure123" --role="admin"
    print_success "Admin user created with email: admin@example.com and password: secure123"
else
    print_warning "Admin user already exists, skipping creation"
fi

# Create customer service user if it doesn't exist
if [ "$CS_EXISTS" -eq 0 ]; then
    php "$ROOT_DIR/artisan" dbaas:user add --name="Customer Service" --email="cs@example.com" --password="service123" --role="user"
    print_success "Customer Service user created with email: cs@example.com and password: service123"
else
    print_warning "Customer Service user already exists, skipping creation"
fi

# Create sales user if it doesn't exist
if [ "$SALES_EXISTS" -eq 0 ]; then
    php "$ROOT_DIR/artisan" dbaas:user add --name="Sales Rep" --email="sales@example.com" --password="sales123" --role="user"
    print_success "Sales user created with email: sales@example.com and password: sales123"
else
    print_warning "Sales user already exists, skipping creation"
fi

# Step 5: Set up permissions
print_message "Setting up permissions..."

# Customer Service Permissions
print_message "Setting up Customer Service permissions..."
# Allow viewing all customer information
php "$ROOT_DIR/artisan" dbaas:permission grant --user="cs@example.com" --table=customers --operations=select

# Allow viewing orders but not the amount
php "$ROOT_DIR/artisan" dbaas:permission grant --user="cs@example.com" --table=orders --operations=select --columns-denied=amount

# Allow updating order status
php "$ROOT_DIR/artisan" dbaas:permission grant --user="cs@example.com" --table=orders --operations=update --columns-allowed=status

print_success "Customer Service permissions set up successfully!"

# Sales Rep Permissions
print_message "Setting up Sales Rep permissions..."
# Allow full access to customers
php "$ROOT_DIR/artisan" dbaas:permission grant --user="sales@example.com" --table=customers --operations=select,insert,update

# Allow viewing and creating orders
php "$ROOT_DIR/artisan" dbaas:permission grant --user="sales@example.com" --table=orders --operations=select,insert

print_success "Sales Rep permissions set up successfully!"

# Step 6: Copy the dashboard HTML file
print_message "Setting up the customer management dashboard..."
cp "$SCRIPT_DIR/dashboard.html" "$SCRIPT_DIR/index.html"
print_success "Dashboard set up successfully!"

# Step 7: Start a PHP server to serve the dashboard
print_message "Starting PHP server to serve the dashboard..."
print_message "The dashboard will be available at http://localhost:8080"
print_message "You can log in with any of these accounts:"
print_message "- Admin: admin@example.com / secure123"
print_message "- Customer Service: cs@example.com / service123"
print_message "- Sales Rep: sales@example.com / sales123"
print_message ""
print_message "Press Ctrl+C to stop the server when you're done."
print_message ""

# Change to the example directory and start the PHP server
cd "$SCRIPT_DIR"
php -S localhost:8080

# This point is only reached if the PHP server is stopped
print_message "Server stopped. The example setup is complete!"
