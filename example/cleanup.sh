#!/bin/bash

# DBaaS Customer Management Example Cleanup Script
# This script resets the database after running the example

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

print_message "Starting DBaaS Example Cleanup..."
print_message "This script will:"
print_message "1. Remove the example users (admin@example.com, cs@example.com, sales@example.com)"
print_message "2. Remove all permissions associated with these users"
print_message "3. Drop the customers and orders tables"
echo ""

# Confirm before proceeding
read -p "This will remove all data created by the example. Continue? (y/n) " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    print_message "Cleanup cancelled."
    exit 0
fi

# Step 1: Remove the example users
print_message "Removing example users..."

# Check if users exist
ADMIN_EXISTS=$(php "$ROOT_DIR/artisan" dbaas:user list | grep -c "admin@example.com" || true)
CS_EXISTS=$(php "$ROOT_DIR/artisan" dbaas:user list | grep -c "cs@example.com" || true)
SALES_EXISTS=$(php "$ROOT_DIR/artisan" dbaas:user list | grep -c "sales@example.com" || true)

# Remove users if they exist
if [ "$ADMIN_EXISTS" -gt 0 ]; then
    php "$ROOT_DIR/artisan" dbaas:user remove --email="admin@example.com" --confirm
    print_success "Admin user removed"
else
    print_warning "Admin user not found, skipping"
fi

if [ "$CS_EXISTS" -gt 0 ]; then
    php "$ROOT_DIR/artisan" dbaas:user remove --email="cs@example.com" --confirm
    print_success "Customer Service user removed"
else
    print_warning "Customer Service user not found, skipping"
fi

if [ "$SALES_EXISTS" -gt 0 ]; then
    php "$ROOT_DIR/artisan" dbaas:user remove --email="sales@example.com" --confirm
    print_success "Sales user removed"
else
    print_warning "Sales user not found, skipping"
fi

# Step 2: Drop the tables
print_message "Dropping example tables..."

# Check if tables exist
CUSTOMERS_EXISTS=$(php "$ROOT_DIR/artisan" dbaas:table list | grep -c "customers" || true)
ORDERS_EXISTS=$(php "$ROOT_DIR/artisan" dbaas:table list | grep -c "orders" || true)

# Drop tables if they exist - drop orders first due to foreign key constraints
if [ "$ORDERS_EXISTS" -gt 0 ]; then
    php "$ROOT_DIR/artisan" dbaas:table drop --name=orders --force
    print_success "Orders table dropped"
else
    print_warning "Orders table not found, skipping"
fi

if [ "$CUSTOMERS_EXISTS" -gt 0 ]; then
    php "$ROOT_DIR/artisan" dbaas:table drop --name=customers --force
    print_success "Customers table dropped"
else
    print_warning "Customers table not found, skipping"
fi

print_success "Cleanup complete! The database has been reset to its state before running the example."
print_message "You can run ./setup.sh again to recreate the example environment."
