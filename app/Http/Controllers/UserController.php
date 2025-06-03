<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Get all users (admin only)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->auth_user;
        
        if (!$user || !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $users = User::select(['id', 'name', 'email', 'role', 'created_at', 'api_key_expires_at'])
            ->get();
        
        return response()->json([
            'users' => $users,
        ]);
    }
    
    /**
     * Get a specific user (admin only)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, int $id)
    {
        $user = $request->auth_user;
        
        if (!$user || !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $targetUser = User::findOrFail($id);
        
        return response()->json([
            'user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
                'role' => $targetUser->role,
                'created_at' => $targetUser->created_at,
                'api_key_expires_at' => $targetUser->api_key_expires_at,
            ],
        ]);
    }
    
    /**
     * Create a new user (admin only)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = $request->auth_user;
        
        if (!$user || !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:user,admin',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        // Generate API key for the new user
        $apiKey = $newUser->generateApiKey();

        return response()->json([
            'message' => 'User created successfully',
            'user' => [
                'id' => $newUser->id,
                'name' => $newUser->name,
                'email' => $newUser->email,
                'role' => $newUser->role,
            ],
            'api_key' => $apiKey,
        ], 201);
    }
    
    /**
     * Update a user (admin only)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id)
    {
        $user = $request->auth_user;
        
        if (!$user || !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $targetUser = User::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $targetUser->id,
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|string|in:user,admin',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Update user fields if provided
        if ($request->has('name')) {
            $targetUser->name = $request->name;
        }
        
        if ($request->has('email')) {
            $targetUser->email = $request->email;
        }
        
        if ($request->has('password')) {
            $targetUser->password = Hash::make($request->password);
        }
        
        if ($request->has('role')) {
            $targetUser->role = $request->role;
        }
        
        $targetUser->save();

        return response()->json([
            'message' => 'User updated successfully',
            'user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
                'role' => $targetUser->role,
            ],
        ]);
    }
    
    /**
     * Delete a user (admin only)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, int $id)
    {
        $user = $request->auth_user;
        
        if (!$user || !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Prevent deleting yourself
        if ($user->id == $id) {
            return response()->json([
                'error' => 'Cannot delete yourself',
            ], 400);
        }
        
        $targetUser = User::findOrFail($id);
        $targetUser->delete();
        
        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }
    
    /**
     * Reset API key for a user (admin only)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetApiKey(Request $request, int $id)
    {
        $user = $request->auth_user;
        
        if (!$user || !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $targetUser = User::findOrFail($id);
        $apiKey = $targetUser->generateApiKey();
        
        return response()->json([
            'message' => 'API key reset successfully',
            'user_id' => $targetUser->id,
            'api_key' => $apiKey,
        ]);
    }
}
