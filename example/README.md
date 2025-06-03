# DBaaS Customer Management Example

This directory contains a complete working example of a customer management system built with DBaaS. It demonstrates how to create tables, set up users with different permission levels, and build a web-based dashboard that interacts with the DBaaS API.

## What's Included

- `setup.sh`: A script that automates the entire setup process
- `dashboard.html`: A complete customer management dashboard with login, customer and order management
- `index.html`: A copy of the dashboard created by the setup script

## Running the Example

1. Make sure your DBaaS server is running on port 8000:
   ```
   cd /var/www/html
   php artisan serve
   ```

2. In a new terminal, run the setup script:
   ```
   cd /var/www/html/example
   chmod +x setup.sh
   ./setup.sh
   ```

3. The script will:
   - Create customers and orders tables
   - Seed the tables with sample data
   - Create admin, customer service, and sales user accounts
   - Set up appropriate permissions for each user
   - Start a PHP server on port 8080 to serve the dashboard

4. Open your browser and navigate to http://localhost:8080

5. Log in with one of the following accounts:
   - Admin: admin@example.com / secure123
   - Customer Service: cs@example.com / service123
   - Sales Rep: sales@example.com / sales123

## Cleaning Up

After you're done experimenting with the example, you can reset your database to its original state:

```
cd /var/www/html/example
chmod +x cleanup.sh
./cleanup.sh
```

The cleanup script will:
1. Remove the example users (admin@example.com, cs@example.com, sales@example.com)
2. Remove all permissions associated with these users
3. Drop the customers and orders tables

This allows you to start fresh or run the setup script again if needed.

## Permission Demonstration

This example demonstrates DBaaS's granular permission system:

- **Admin User**: Full access to all features and data
- **Customer Service**: Can view customer data and orders but cannot see order amounts. Can update order status but cannot create new customers or orders.
- **Sales Rep**: Can view and create customers, view all order details, and create new orders, but cannot update order status.

## Customizing the Example

Feel free to modify the setup script or dashboard to experiment with different permission configurations or to add new features. The dashboard code is fully commented and designed to be easy to understand and extend.

## Troubleshooting

- If you encounter permission errors, make sure your DBaaS server is running and accessible on port 8000
- If the dashboard doesn't connect to the API, check that your API server is running on port 8000
- If you can't log in, the setup script may not have created the users correctly. Check the output of the script for any errors.

## Next Steps

After exploring this example, you can:

1. Extend the dashboard with additional features
2. Create your own tables and permissions
3. Integrate the DBaaS API into your own applications
4. Experiment with different permission configurations
