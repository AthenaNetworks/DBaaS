<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - DBaaS Documentation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            padding-top: 20px;
            padding-bottom: 40px;
        }
        .container {
            max-width: 960px;
        }
        .doc-sidebar {
            position: sticky;
            top: 20px;
            height: calc(100vh - 40px);
            overflow-y: auto;
        }
        .doc-content {
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        h1, h2, h3, h4, h5, h6 {
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            font-weight: 600;
            line-height: 1.25;
        }
        h1 {
            font-size: 2em;
            padding-bottom: 0.3em;
            border-bottom: 1px solid #eaecef;
        }
        h2 {
            font-size: 1.5em;
            padding-bottom: 0.3em;
            border-bottom: 1px solid #eaecef;
        }
        h3 {
            font-size: 1.25em;
        }
        h4 {
            font-size: 1em;
        }
        a {
            color: #0366d6;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        pre {
            background-color: #f6f8fa;
            border-radius: 3px;
            padding: 16px;
            overflow: auto;
            font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 85%;
            line-height: 1.45;
        }
        code {
            background-color: rgba(27, 31, 35, 0.05);
            border-radius: 3px;
            font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 85%;
            padding: 0.2em 0.4em;
        }
        pre code {
            background-color: transparent;
            padding: 0;
        }
        blockquote {
            padding: 0 1em;
            color: #6a737d;
            border-left: 0.25em solid #dfe2e5;
            margin: 0 0 16px 0;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 16px;
        }
        table th, table td {
            padding: 6px 13px;
            border: 1px solid #dfe2e5;
        }
        table tr {
            background-color: #fff;
            border-top: 1px solid #c6cbd1;
        }
        table tr:nth-child(2n) {
            background-color: #f6f8fa;
        }
        img {
            max-width: 100%;
            box-sizing: border-box;
        }
        .heading-permalink {
            opacity: 0;
            font-size: 0.85em;
            margin-left: 0.2em;
            transition: opacity 0.2s;
        }
        h1:hover .heading-permalink,
        h2:hover .heading-permalink,
        h3:hover .heading-permalink,
        h4:hover .heading-permalink,
        h5:hover .heading-permalink,
        h6:hover .heading-permalink {
            opacity: 1;
        }
        .doc-nav {
            margin-bottom: 20px;
        }
        .doc-nav a {
            display: block;
            padding: 5px 10px;
            margin: 2px 0;
            border-radius: 3px;
        }
        .doc-nav a:hover {
            background-color: #f6f8fa;
            text-decoration: none;
        }
        .doc-nav .active {
            background-color: #0366d6;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-3 doc-sidebar">
                <h5>DBaaS Documentation</h5>
                <div class="doc-nav">
                    <a href="{{ route('documentation.show', 'index.md') }}" class="{{ $filename === 'index.md' ? 'active' : '' }}">Documentation Home</a>
                    <a href="{{ route('documentation.show', 'getting_started.md') }}" class="{{ $filename === 'getting_started.md' ? 'active' : '' }}">Getting Started Guide</a>
                    <h6 class="mt-3 mb-2">Authentication & Permissions</h6>
                    <a href="{{ route('documentation.show', 'authentication.md') }}" class="{{ $filename === 'authentication.md' ? 'active' : '' }}">Authentication</a>
                    <a href="{{ route('documentation.show', 'user_roles.md') }}" class="{{ $filename === 'user_roles.md' ? 'active' : '' }}">User Roles</a>
                    <a href="{{ route('documentation.show', 'permission_management.md') }}" class="{{ $filename === 'permission_management.md' ? 'active' : '' }}">Permission Management</a>
                    <h6 class="mt-3 mb-2">Command Reference</h6>
                    <a href="{{ route('documentation.show', 'artisan_commands.md') }}" class="{{ $filename === 'artisan_commands.md' ? 'active' : '' }}">Artisan Commands</a>
                    <h6 class="mt-3 mb-2">Database Operations</h6>
                    <a href="{{ route('documentation.show', 'select_operations.md') }}" class="{{ $filename === 'select_operations.md' ? 'active' : '' }}">SELECT Operations</a>
                    <a href="{{ route('documentation.show', 'insert_operations.md') }}" class="{{ $filename === 'insert_operations.md' ? 'active' : '' }}">INSERT Operations</a>
                    <a href="{{ route('documentation.show', 'update_operations.md') }}" class="{{ $filename === 'update_operations.md' ? 'active' : '' }}">UPDATE Operations</a>
                    <a href="{{ route('documentation.show', 'delete_operations.md') }}" class="{{ $filename === 'delete_operations.md' ? 'active' : '' }}">DELETE Operations</a>
                </div>
                <div class="mt-4">
                    <a href="/" class="btn btn-outline-secondary btn-sm">Back to Home</a>
                </div>
            </div>
            <div class="col-md-9">
                <div class="doc-content">
                    {!! $content !!}
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
