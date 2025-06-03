<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
        
        // Generate API key for admin
        $admin->generateApiKey();
        
        // Create regular user
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);
        
        // Generate API key for user
        $user->generateApiKey();
        
        // Create permissions for regular user
        
        // Permission for users table - read only, limited columns
        Permission::create([
            'user_id' => $user->id,
            'table_name' => 'users',
            'can_select' => true,
            'can_insert' => false,
            'can_update' => false,
            'can_delete' => false,
            'column_restrictions' => [
                'allowed' => ['id', 'name', 'email', 'role', 'created_at']
            ],
        ]);
        
        // Permission for products table - full access
        Permission::create([
            'user_id' => $user->id,
            'table_name' => 'products',
            'can_select' => true,
            'can_insert' => true,
            'can_update' => true,
            'can_delete' => true,
        ]);
        
        // Permission for orders table - conditional access
        Permission::create([
            'user_id' => $user->id,
            'table_name' => 'orders',
            'can_select' => true,
            'can_insert' => true,
            'can_update' => true,
            'can_delete' => false,
            'where_conditions' => [
                ['user_id', '=', $user->id]
            ],
        ]);
        
        $this->command->info('Users and permissions seeded successfully!');
    }
}
