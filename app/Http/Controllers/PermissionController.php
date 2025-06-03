<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    /**
     * Get all permissions for the authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        $permissions = $user->permissions;
        
        return response()->json([
            'permissions' => $permissions,
        ]);
    }

    /**
     * Get permissions for a specific table
     *
     * @param Request $request
     * @param string $table
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, string $table)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        $permission = $user->getPermissionForTable($table);
        
        if (!$permission) {
            return response()->json([
                'message' => 'No permissions found for this table',
            ], 404);
        }
        
        return response()->json([
            'permission' => $permission,
        ]);
    }

    /**
     * Create or update permissions for a user on a table
     * Note: Only admins can set permissions
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'table_name' => 'required|string',
            'can_select' => 'boolean',
            'can_insert' => 'boolean',
            'can_update' => 'boolean',
            'can_delete' => 'boolean',
            'where_conditions' => 'nullable|array',
            'column_restrictions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        
        $targetUser = User::findOrFail($request->user_id);
        
        // Find existing permission or create new one
        $permission = Permission::updateOrCreate(
            [
                'user_id' => $targetUser->id,
                'table_name' => $request->table_name,
            ],
            [
                'can_select' => $request->input('can_select', false),
                'can_insert' => $request->input('can_insert', false),
                'can_update' => $request->input('can_update', false),
                'can_delete' => $request->input('can_delete', false),
                'where_conditions' => $request->input('where_conditions'),
                'column_restrictions' => $request->input('column_restrictions'),
            ]
        );
        
        return response()->json([
            'message' => 'Permission updated successfully',
            'permission' => $permission,
        ]);
    }

    /**
     * Delete a permission
     * Note: Only admins can delete permissions
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, int $id)
    {
        $user = Auth::user();
        
        if (!$user || !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $permission = Permission::findOrFail($id);
        $permission->delete();
        
        return response()->json([
            'message' => 'Permission deleted successfully',
        ]);
    }
}
