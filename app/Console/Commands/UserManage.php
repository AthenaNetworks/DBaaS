<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserManage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dbaas:user {action? : Action to perform (add, remove, update, list)} {--id= : User ID for update/remove} {--name= : User name} {--email= : User email} {--password= : User password} {--role= : User role (admin or user)} {--refresh-key : Generate a new API key}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage DBaaS users (add, remove, update, list)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        
        if (!$action) {
            $action = $this->choice(
                'What action would you like to perform?',
                ['add', 'remove', 'update', 'list'],
                'list'
            );
        }
        
        switch ($action) {
            case 'add':
                $this->addUser();
                break;
            case 'remove':
                $this->removeUser();
                break;
            case 'update':
                $this->updateUser();
                break;
            case 'list':
                $this->listUsers();
                break;
            default:
                $this->error("Invalid action: {$action}");
                return 1;
        }
        
        return 0;
    }
    
    /**
     * Add a new user
     */
    protected function addUser()
    {
        $name = $this->option('name') ?: $this->ask('Enter user name');
        $email = $this->option('email') ?: $this->ask('Enter user email');
        $password = $this->option('password') ?: $this->secret('Enter user password (min 8 characters)');
        $role = $this->option('role') ?: $this->choice('Select user role', ['user', 'admin'], 'user');
        
        // Validate input
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in(['user', 'admin'])],
        ]);
        
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return;
        }
        
        // Create user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => $role,
        ]);
        
        // Generate API key
        $apiKey = $user->generateApiKey();
        
        $this->info("User created successfully!");
        $this->table(
            ['ID', 'Name', 'Email', 'Role', 'API Key'],
            [[$user->id, $user->name, $user->email, $user->role, $apiKey]]
        );
        
        $this->warn("Please save the API key as it won't be shown again!");
    }
    
    /**
     * Remove an existing user
     */
    protected function removeUser()
    {
        $id = $this->option('id');
        
        if (!$id) {
            $this->listUsers();
            $id = $this->ask('Enter the ID of the user to remove');
        }
        
        $user = User::find($id);
        
        if (!$user) {
            $this->error("User with ID {$id} not found!");
            return;
        }
        
        // Confirm deletion
        if (!$this->confirm("Are you sure you want to delete user {$user->name} ({$user->email})?")) {
            $this->info('Operation cancelled.');
            return;
        }
        
        $user->delete();
        $this->info("User {$user->name} ({$user->email}) has been deleted.");
    }
    
    /**
     * Update an existing user
     */
    protected function updateUser()
    {
        $id = $this->option('id');
        
        if (!$id) {
            $this->listUsers();
            $id = $this->ask('Enter the ID of the user to update');
        }
        
        $user = User::find($id);
        
        if (!$user) {
            $this->error("User with ID {$id} not found!");
            return;
        }
        
        $this->info("Updating user: {$user->name} ({$user->email})");
        
        // Get update fields
        $name = $this->option('name');
        if (!$name && $this->confirm('Update name?', false)) {
            $name = $this->ask('Enter new name', $user->name);
        }
        
        $email = $this->option('email');
        if (!$email && $this->confirm('Update email?', false)) {
            $email = $this->ask('Enter new email', $user->email);
        }
        
        $password = $this->option('password');
        if (!$password && $this->confirm('Update password?', false)) {
            $password = $this->secret('Enter new password (min 8 characters)');
        }
        
        $role = $this->option('role');
        if (!$role && $this->confirm('Update role?', false)) {
            $role = $this->choice('Select new role', ['user', 'admin'], $user->role);
        }
        
        $refreshKey = $this->option('refresh-key');
        if (!$refreshKey && $this->confirm('Generate new API key?', false)) {
            $refreshKey = true;
        }
        
        // Prepare data for update
        $data = [];
        if ($name) $data['name'] = $name;
        if ($email) $data['email'] = $email;
        if ($password) $data['password'] = Hash::make($password);
        if ($role) $data['role'] = $role;
        
        // Validate input
        $validator = Validator::make($data, [
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'sometimes|string|min:8',
            'role' => ['sometimes', Rule::in(['user', 'admin'])],
        ]);
        
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return;
        }
        
        // Update user
        $user->update($data);
        
        // Generate new API key if requested
        $apiKey = null;
        if ($refreshKey) {
            $apiKey = $user->generateApiKey();
        }
        
        $this->info("User updated successfully!");
        
        $displayData = [
            'ID' => $user->id,
            'Name' => $user->name,
            'Email' => $user->email,
            'Role' => $user->role,
        ];
        
        if ($apiKey) {
            $displayData['New API Key'] = $apiKey;
            $this->warn("Please save the new API key as it won't be shown again!");
        }
        
        $this->table(
            array_keys($displayData),
            [array_values($displayData)]
        );
    }
    
    /**
     * List all users
     */
    protected function listUsers()
    {
        $users = User::all(['id', 'name', 'email', 'role', 'created_at', 'api_key_expires_at']);
        
        if ($users->isEmpty()) {
            $this->info('No users found.');
            return;
        }
        
        $this->info("Total users: {$users->count()}");
        
        $tableData = $users->map(function ($user) {
            return [
                'ID' => $user->id,
                'Name' => $user->name,
                'Email' => $user->email,
                'Role' => $user->role,
                'Created' => $user->created_at->format('Y-m-d H:i'),
                'API Key Expires' => $user->api_key_expires_at ? $user->api_key_expires_at->format('Y-m-d H:i') : 'N/A',
                'Key Valid' => $user->hasValidApiKey() ? 'Yes' : 'No',
            ];
        })->toArray();
        
        $this->table(
            array_keys($tableData[0]),
            array_map(function ($item) {
                return array_values($item);
            }, $tableData)
        );
    }
}
