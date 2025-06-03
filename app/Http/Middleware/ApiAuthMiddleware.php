<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class ApiAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get API key from request header
        $apiKey = $request->header('X-API-Key');
        
        if (!$apiKey) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Missing API key'
            ], 401);
        }
        
        // Find user by API key
        $user = User::where('api_key', $apiKey)->first();
        
        // Check if API key is valid and not expired
        if (!$user || !$user->hasValidApiKey()) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid or expired API key'
            ], 401);
        }
        
        // Add the authenticated user to the request
        $request->merge(['auth_user' => $user]);
        
        // Check operation permissions if this is a database operation
        if ($request->route()->getName() === 'db.operation') {
            $operation = $this->mapHttpMethodToOperation($request->method());
            $table = $request->input('table');
            
            if (!$table) {
                return response()->json([
                    'error' => 'Bad Request',
                    'message' => 'Table name is required'
                ], 400);
            }
            
            // Check if user has permission for this operation on this table
            if (!$user->hasPermission($operation, $table)) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => "You don't have permission to perform {$operation} operations on table {$table}"
                ], 403);
            }
            
            // If there are where conditions in the permission, apply them
            $permission = $user->getPermissionForTable($table);
            if ($permission && !empty($permission->getWhereConditions())) {
                // Merge permission where conditions with request where conditions
                $requestWhere = $request->input('where', []);
                $permissionWhere = $permission->getWhereConditions();
                $request->merge(['where' => array_merge($requestWhere, $permissionWhere)]);
            }
        }
        
        return $next($request);
    }
    
    /**
     * Map HTTP method to database operation
     *
     * @param string $method
     * @return string
     */
    private function mapHttpMethodToOperation(string $method): string
    {
        switch (strtoupper($method)) {
            case 'GET':
                return 'select';
            case 'POST':
                return 'insert';
            case 'PUT':
            case 'PATCH':
                return 'update';
            case 'DELETE':
                return 'delete';
            default:
                return 'select';
        }
    }
}
