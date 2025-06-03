<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel DBaaS API</title>

        <style>
            body {
                font-family: 'Nunito', sans-serif;
                margin: 0;
                padding: 0;
                background-color: #f7f7f7;
                color: #333;
            }
            .container {
                max-width: 800px;
                margin: 0 auto;
                padding: 2rem;
            }
            .header {
                text-align: center;
                margin-bottom: 2rem;
            }
            h1 {
                font-size: 2.5rem;
                margin-bottom: 0.5rem;
                color: #e74430;
            }
            p {
                font-size: 1.1rem;
                line-height: 1.6;
                margin-bottom: 1.5rem;
            }
            .card {
                background-color: white;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                padding: 2rem;
                margin-bottom: 1.5rem;
            }
            .card h2 {
                color: #e74430;
                margin-top: 0;
                border-bottom: 1px solid #eee;
                padding-bottom: 0.5rem;
            }
            code {
                background-color: #f5f5f5;
                padding: 0.2rem 0.4rem;
                border-radius: 3px;
                font-family: monospace;
            }
            .footer {
                text-align: center;
                margin-top: 2rem;
                font-size: 0.9rem;
                color: #666;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Laravel DBaaS API</h1>
                <p>Database as a Service API for Laravel applications</p>
            </div>

            <div class="card">
                <h2>API Documentation</h2>
                <p>The DBaaS API provides a simple and flexible way to interact with your database through HTTP requests. For detailed documentation, visit the <a href="/docs">API documentation</a>.</p>
            </div>

            <div class="card">
                <h2>Features</h2>
                <ul>
                    <li>Full CRUD operations via RESTful API</li>
                    <li>Unified POST endpoint for both SELECT and INSERT operations</li>
                    <li>Role-based and permission-based access control</li>
                    <li>API key authentication</li>
                    <li>Comprehensive test coverage</li>
                </ul>
            </div>

            <div class="footer">
                <p>&copy; {{ date('Y') }} DBaaS API. All rights reserved.</p>
            </div>
        </div>
    </body>
</html>
