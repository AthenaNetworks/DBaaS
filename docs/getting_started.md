# Getting Started with DBaaS

This guide will walk you through the process of setting up and using the Database as a Service (DBaaS) API. By the end, you'll understand how to create tables, set up users with different permission levels, and make authenticated API requests.

## Quick Start with Working Example

Want to see a complete working example right away? We've included an automated setup script that creates a fully functional customer management system:

```bash
# Make sure your DBaaS server is running on port 8000 first
cd /var/www/html
php artisan serve

# In a new terminal, run the example setup
cd /var/www/html/example
./setup.sh
```

The script will create tables, set up users with different permission levels, and start a web server on port 8080 where you can access a complete customer management dashboard.

For more details, see the [example README](/example/README.md).

## Step-by-Step Guide

If you prefer to understand each step in detail, continue with the guide below:

## Prerequisites

Before you begin, make sure you have:

- DBaaS installed and running (see [Installation Guide](/docs/index.md#installation))
- Admin access to the system
- Basic understanding of SQL and REST APIs

## Step 1: Creating Your First Tables

Let's start by creating a few tables for a simple customer management system. We'll use the `dbaas:table` Artisan command to create these tables.

### Creating a Customers Table

```bash
php artisan dbaas:table create --name=customers --schema='
{
    "id": {"type": "increments", "primary": true},
    "name": {"type": "string", "length": 100, "nullable": false},
    "email": {"type": "string", "length": 100, "unique": true, "nullable": false},
    "phone": {"type": "string", "length": 20, "nullable": true},
    "created_at": {"type": "timestamp", "nullable": true},
    "updated_at": {"type": "timestamp", "nullable": true}
}'
```

### Creating an Orders Table

```bash
php artisan dbaas:table create --name=orders --schema='
{
    "id": {"type": "increments", "primary": true},
    "customer_id": {"type": "integer", "nullable": false, "foreign": "customers.id"},
    "amount": {"type": "decimal", "precision": 10, "scale": 2, "nullable": false},
    "status": {"type": "string", "length": 20, "default": "pending"},
    "order_date": {"type": "timestamp", "nullable": false, "default": "CURRENT_TIMESTAMP"},
    "created_at": {"type": "timestamp", "nullable": true},
    "updated_at": {"type": "timestamp", "nullable": true}
}'
```

### Seeding the Tables with Sample Data

Let's add some sample data to our tables:

```bash
php artisan dbaas:table seed --table=customers --count=10
php artisan dbaas:table seed --table=orders --count=25
```

## Step 2: Setting Up Users and Permissions

Now let's create users with different roles and permissions.

### Creating an Admin User

```bash
php artisan dbaas:user add --name="Admin User" --email="admin@example.com" --password="secure123" --role="admin"
```

### Creating Regular Users

```bash
php artisan dbaas:user add --name="Customer Service" --email="cs@example.com" --password="service123" --role="user"

php artisan dbaas:user add --name="Sales Rep" --email="sales@example.com" --password="sales123" --role="user"
```

### Setting Up Permissions

Let's set up appropriate permissions for our users:

#### Customer Service Permissions
```bash
# Allow viewing all customer information
php artisan dbaas:permission grant --user="cs@example.com" --table=customers --operations=select

# Allow viewing orders but not the amount
php artisan dbaas:permission grant --user="cs@example.com" --table=orders --operations=select --columns-denied=amount

# Allow updating order status
php artisan dbaas:permission grant --user="cs@example.com" --table=orders --operations=update --columns-allowed=status
```

#### Sales Rep Permissions
```bash
# Allow full access to customers
php artisan dbaas:permission grant --user="sales@example.com" --table=customers --operations=select,insert,update

# Allow viewing and creating orders
php artisan dbaas:permission grant --user="sales@example.com" --table=orders --operations=select,insert
```

## Step 3: Making API Requests with JavaScript/jQuery

Now that we have our tables, users, and permissions set up, let's see how to interact with the API using JavaScript and jQuery.

### Authentication

First, you need to authenticate and get an API key:

```javascript
// Login and get API key
$.ajax({
    url: 'http://your-dbaas-server.com/api/auth/login',
    type: 'POST',
    contentType: 'application/json',
    data: JSON.stringify({
        email: 'sales@example.com',
        password: 'sales123'
    }),
    success: function(response) {
        // Store the API key for future requests
        localStorage.setItem('dbaas_api_key', response.api_key);
        console.log('Successfully logged in with API key:', response.api_key);
    },
    error: function(xhr) {
        console.error('Login failed:', xhr.responseText);
    }
});
```

### Setting Up a Request Helper

Let's create a helper function to simplify making authenticated requests:

```javascript
function dbaasRequest(endpoint, method, data, callback) {
    const apiKey = localStorage.getItem('dbaas_api_key');
    
    if (!apiKey) {
        console.error('No API key found. Please login first.');
        return;
    }
    
    $.ajax({
        url: 'http://your-dbaas-server.com/api/' + endpoint,
        type: method,
        contentType: 'application/json',
        headers: {
            'Authorization': 'Bearer ' + apiKey
        },
        data: data ? JSON.stringify(data) : null,
        success: function(response) {
            callback(null, response);
        },
        error: function(xhr) {
            callback(xhr.responseJSON || xhr.responseText, null);
        }
    });
}
```

### Example: Fetching Customers

```javascript
// Get all customers
dbaasRequest('db/customers', 'GET', null, function(err, data) {
    if (err) {
        console.error('Error fetching customers:', err);
        return;
    }
    
    // Display customers in a table
    let html = '<table class="table"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th></tr></thead><tbody>';
    
    data.data.forEach(function(customer) {
        html += `<tr>
            <td>${customer.id}</td>
            <td>${customer.name}</td>
            <td>${customer.email}</td>
            <td>${customer.phone || 'N/A'}</td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    $('#customers-container').html(html);
});
```

### Example: Filtering Data

```javascript
// Get customers with name containing "Smith"
dbaasRequest('db/customers', 'POST', {
    method: 'select',
    where: [['name', 'like', '%Smith%']]
}, function(err, data) {
    if (err) {
        console.error('Error searching customers:', err);
        return;
    }
    
    console.log('Found customers:', data.data);
});
```

### Example: Creating a New Customer

```javascript
// Add a new customer
dbaasRequest('db/customers', 'POST', {
    method: 'insert',
    data: {
        name: 'Jane Doe',
        email: 'jane.doe@example.com',
        phone: '555-123-4567'
    }
}, function(err, data) {
    if (err) {
        console.error('Error creating customer:', err);
        return;
    }
    
    console.log('Customer created successfully:', data);
    alert('New customer added with ID: ' + data.id);
});
```

### Example: Updating a Customer

```javascript
// Update a customer's phone number
dbaasRequest('db/customers', 'PUT', {
    where: [['id', '=', 5]],
    data: {
        phone: '555-987-6543'
    }
}, function(err, data) {
    if (err) {
        console.error('Error updating customer:', err);
        return;
    }
    
    console.log('Customer updated successfully:', data);
});
```

### Example: Creating an Order

```javascript
// Create a new order
dbaasRequest('db/orders', 'POST', {
    method: 'insert',
    data: {
        customer_id: 3,
        amount: 199.99,
        status: 'new'
    }
}, function(err, data) {
    if (err) {
        console.error('Error creating order:', err);
        return;
    }
    
    console.log('Order created successfully:', data);
});
```

### Example: Getting Orders for a Customer

```javascript
// Get all orders for a specific customer
dbaasRequest('db/orders', 'POST', {
    method: 'select',
    where: [['customer_id', '=', 3]]
}, function(err, data) {
    if (err) {
        console.error('Error fetching orders:', err);
        return;
    }
    
    console.log('Customer orders:', data.data);
    
    // Display orders in a table
    let html = '<table class="table"><thead><tr><th>Order ID</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead><tbody>';
    
    data.data.forEach(function(order) {
        html += `<tr>
            <td>${order.id}</td>
            <td>$${parseFloat(order.amount).toFixed(2)}</td>
            <td>${order.status}</td>
            <td>${new Date(order.order_date).toLocaleDateString()}</td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    $('#orders-container').html(html);
});
```

## Step 4: Building a Simple Dashboard

Let's put everything together in a simple dashboard. Here's an example HTML page with jQuery:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DBaaS Customer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container mt-4">
        <h1>Customer Management Dashboard</h1>
        
        <!-- Login Form -->
        <div id="login-section" class="card mb-4">
            <div class="card-header">Login</div>
            <div class="card-body">
                <form id="login-form">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
            </div>
        </div>
        
        <!-- Dashboard (initially hidden) -->
        <div id="dashboard" style="display: none;">
            <div class="row">
                <!-- Customer Management -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Customers</span>
                            <button id="add-customer-btn" class="btn btn-sm btn-primary">Add Customer</button>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <input type="text" id="customer-search" class="form-control" placeholder="Search customers...">
                            </div>
                            <div id="customers-container">
                                <!-- Customer data will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Management -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Orders</span>
                            <button id="add-order-btn" class="btn btn-sm btn-primary">New Order</button>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <select id="customer-filter" class="form-select">
                                    <option value="">All Customers</option>
                                    <!-- Customer options will be loaded here -->
                                </select>
                            </div>
                            <div id="orders-container">
                                <!-- Order data will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Add Customer Modal -->
        <div class="modal fade" id="add-customer-modal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Customer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="add-customer-form">
                            <div class="mb-3">
                                <label for="customer-name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="customer-name" required>
                            </div>
                            <div class="mb-3">
                                <label for="customer-email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="customer-email" required>
                            </div>
                            <div class="mb-3">
                                <label for="customer-phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="customer-phone">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="save-customer-btn" class="btn btn-primary">Save Customer</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Add Order Modal -->
        <div class="modal fade" id="add-order-modal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Order</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="add-order-form">
                            <div class="mb-3">
                                <label for="order-customer" class="form-label">Customer</label>
                                <select class="form-select" id="order-customer" required>
                                    <!-- Customer options will be loaded here -->
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="order-amount" class="form-label">Amount</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="order-amount" required>
                            </div>
                            <div class="mb-3">
                                <label for="order-status" class="form-label">Status</label>
                                <select class="form-select" id="order-status">
                                    <option value="new">New</option>
                                    <option value="processing">Processing</option>
                                    <option value="shipped">Shipped</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="save-order-btn" class="btn btn-primary">Create Order</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // DBaaS API helper function
        function dbaasRequest(endpoint, method, data, callback) {
            const apiKey = localStorage.getItem('dbaas_api_key');
            
            if (!apiKey) {
                console.error('No API key found. Please login first.');
                return;
            }
            
            $.ajax({
                url: 'http://localhost:8002/api/' + endpoint,
                type: method,
                contentType: 'application/json',
                headers: {
                    'Authorization': 'Bearer ' + apiKey
                },
                data: data ? JSON.stringify(data) : null,
                success: function(response) {
                    callback(null, response);
                },
                error: function(xhr) {
                    callback(xhr.responseJSON || xhr.responseText, null);
                }
            });
        }
        
        // Check if user is already logged in
        $(document).ready(function() {
            const apiKey = localStorage.getItem('dbaas_api_key');
            
            if (apiKey) {
                // Verify the API key is still valid
                $.ajax({
                    url: 'http://localhost:8002/api/auth/user',
                    type: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + apiKey
                    },
                    success: function() {
                        // API key is valid, show dashboard
                        $('#login-section').hide();
                        $('#dashboard').show();
                        loadCustomers();
                        loadOrders();
                    },
                    error: function() {
                        // API key is invalid, clear it
                        localStorage.removeItem('dbaas_api_key');
                    }
                });
            }
            
            // Login form submission
            $('#login-form').on('submit', function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: 'http://localhost:8002/api/auth/login',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        email: $('#email').val(),
                        password: $('#password').val()
                    }),
                    success: function(response) {
                        localStorage.setItem('dbaas_api_key', response.api_key);
                        $('#login-section').hide();
                        $('#dashboard').show();
                        loadCustomers();
                        loadOrders();
                    },
                    error: function(xhr) {
                        alert('Login failed: ' + (xhr.responseJSON?.message || 'Unknown error'));
                    }
                });
            });
            
            // Load customers
            function loadCustomers() {
                dbaasRequest('db/customers', 'GET', null, function(err, data) {
                    if (err) {
                        console.error('Error loading customers:', err);
                        return;
                    }
                    
                    // Populate customer table
                    let html = '<table class="table"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th></tr></thead><tbody>';
                    
                    data.data.forEach(function(customer) {
                        html += `<tr data-id="${customer.id}">
                            <td>${customer.id}</td>
                            <td>${customer.name}</td>
                            <td>${customer.email}</td>
                            <td>${customer.phone || 'N/A'}</td>
                        </tr>`;
                    });
                    
                    html += '</tbody></table>';
                    $('#customers-container').html(html);
                    
                    // Populate customer dropdowns
                    let options = '<option value="">Select Customer</option>';
                    let filterOptions = '<option value="">All Customers</option>';
                    
                    data.data.forEach(function(customer) {
                        options += `<option value="${customer.id}">${customer.name}</option>`;
                        filterOptions += `<option value="${customer.id}">${customer.name}</option>`;
                    });
                    
                    $('#order-customer').html(options);
                    $('#customer-filter').html(filterOptions);
                    
                    // Add click handler for customer rows
                    $('#customers-container tr[data-id]').on('click', function() {
                        const customerId = $(this).data('id');
                        $('#customer-filter').val(customerId).trigger('change');
                    });
                });
            }
            
            // Load orders
            function loadOrders(customerId = null) {
                let requestData = { method: 'select' };
                
                if (customerId) {
                    requestData.where = [['customer_id', '=', customerId]];
                }
                
                dbaasRequest('db/orders', 'POST', requestData, function(err, data) {
                    if (err) {
                        console.error('Error loading orders:', err);
                        return;
                    }
                    
                    if (data.data.length === 0) {
                        $('#orders-container').html('<p>No orders found.</p>');
                        return;
                    }
                    
                    // Populate orders table
                    let html = '<table class="table"><thead><tr><th>ID</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead><tbody>';
                    
                    // We need to get customer names
                    dbaasRequest('db/customers', 'GET', null, function(err, customerData) {
                        if (err) {
                            console.error('Error loading customer data for orders:', err);
                            return;
                        }
                        
                        // Create a map of customer IDs to names
                        const customerMap = {};
                        customerData.data.forEach(function(customer) {
                            customerMap[customer.id] = customer.name;
                        });
                        
                        data.data.forEach(function(order) {
                            const customerName = customerMap[order.customer_id] || 'Unknown';
                            html += `<tr>
                                <td>${order.id}</td>
                                <td>${customerName}</td>
                                <td>$${parseFloat(order.amount).toFixed(2)}</td>
                                <td><span class="badge bg-${getStatusColor(order.status)}">${order.status}</span></td>
                                <td>${new Date(order.order_date).toLocaleDateString()}</td>
                            </tr>`;
                        });
                        
                        html += '</tbody></table>';
                        $('#orders-container').html(html);
                    });
                });
            }
            
            // Helper function for order status colors
            function getStatusColor(status) {
                switch (status) {
                    case 'new': return 'primary';
                    case 'processing': return 'info';
                    case 'shipped': return 'warning';
                    case 'delivered': return 'success';
                    case 'cancelled': return 'danger';
                    default: return 'secondary';
                }
            }
            
            // Customer search
            $('#customer-search').on('keyup', function() {
                const searchTerm = $(this).val().toLowerCase();
                
                if (searchTerm.length > 0) {
                    dbaasRequest('db/customers', 'POST', {
                        method: 'select',
                        where: [['name', 'like', `%${searchTerm}%`]]
                    }, function(err, data) {
                        if (err) {
                            console.error('Error searching customers:', err);
                            return;
                        }
                        
                        // Update customer table with search results
                        let html = '<table class="table"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th></tr></thead><tbody>';
                        
                        data.data.forEach(function(customer) {
                            html += `<tr data-id="${customer.id}">
                                <td>${customer.id}</td>
                                <td>${customer.name}</td>
                                <td>${customer.email}</td>
                                <td>${customer.phone || 'N/A'}</td>
                            </tr>`;
                        });
                        
                        html += '</tbody></table>';
                        $('#customers-container').html(html);
                        
                        // Re-add click handler for customer rows
                        $('#customers-container tr[data-id]').on('click', function() {
                            const customerId = $(this).data('id');
                            $('#customer-filter').val(customerId).trigger('change');
                        });
                    });
                } else {
                    loadCustomers();
                }
            });
            
            // Customer filter for orders
            $('#customer-filter').on('change', function() {
                const customerId = $(this).val();
                loadOrders(customerId ? parseInt(customerId) : null);
            });
            
            // Add customer button
            $('#add-customer-btn').on('click', function() {
                $('#add-customer-form')[0].reset();
                new bootstrap.Modal('#add-customer-modal').show();
            });
            
            // Save customer button
            $('#save-customer-btn').on('click', function() {
                const customerData = {
                    name: $('#customer-name').val(),
                    email: $('#customer-email').val(),
                    phone: $('#customer-phone').val()
                };
                
                dbaasRequest('db/customers', 'POST', {
                    method: 'insert',
                    data: customerData
                }, function(err, data) {
                    if (err) {
                        alert('Error creating customer: ' + JSON.stringify(err));
                        return;
                    }
                    
                    bootstrap.Modal.getInstance('#add-customer-modal').hide();
                    loadCustomers();
                });
            });
            
            // Add order button
            $('#add-order-btn').on('click', function() {
                $('#add-order-form')[0].reset();
                new bootstrap.Modal('#add-order-modal').show();
            });
            
            // Save order button
            $('#save-order-btn').on('click', function() {
                const orderData = {
                    customer_id: $('#order-customer').val(),
                    amount: $('#order-amount').val(),
                    status: $('#order-status').val()
                };
                
                dbaasRequest('db/orders', 'POST', {
                    method: 'insert',
                    data: orderData
                }, function(err, data) {
                    if (err) {
                        alert('Error creating order: ' + JSON.stringify(err));
                        return;
                    }
                    
                    bootstrap.Modal.getInstance('#add-order-modal').hide();
                    loadOrders($('#customer-filter').val() || null);
                });
            });
        });
    </script>
</body>
</html>
```

## Next Steps

Now that you have a basic understanding of how to set up tables, users, permissions, and make API requests, you can:

1. **Explore Advanced Features**:
   - Try using more complex WHERE conditions
   - Experiment with JOINs to query related data
   - Use aggregate functions like COUNT, SUM, etc.

2. **Enhance Security**:
   - Implement more granular permissions
   - Add row-level security for multi-tenant applications
   - Set up proper error handling and validation

3. **Build a Complete Application**:
   - Add more tables and relationships
   - Implement a full frontend with authentication
   - Add reporting and analytics features

For more detailed information on specific features, refer to the other documentation pages:

- [Authentication and Authorization](/docs/authentication.md)
- [User Roles and Permissions](/docs/user_roles.md)
- [Permission Management](/docs/permission_management.md)
- [Artisan Commands](/docs/artisan_commands.md)

Happy building with DBaaS!
