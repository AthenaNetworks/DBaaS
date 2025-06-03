<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Permission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PermissionManage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dbaas:permission 
                            {action? : Action to perform (grant, revoke, list, show)}
                            {--user= : User ID or email}
                            {--table= : Table name}
                            {--operations= : Comma-separated list of operations (select,insert,update,delete)}
                            {--columns-allowed= : Comma-separated list of allowed columns}
                            {--columns-denied= : Comma-separated list of denied columns}
                            {--where= : JSON-encoded where conditions}
                            {--id= : Permission ID (for show/revoke)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage user permissions with granular control';

    /**
     * List of Laravel's built-in tables to hide from the user
     */
    protected $laravelTables = [
        'cache', 'cache_locks', 'failed_jobs', 'job_batches', 'jobs',
        'migrations', 'password_reset_tokens', 'personal_access_tokens',
        'sessions'
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        
        if (!$action) {
            $action = $this->choice(
                'What action would you like to perform?',
                ['grant', 'revoke', 'list', 'show'],
                'list'
            );
        }
        
        switch ($action) {
            case 'grant':
                $this->grantPermission();
                break;
            case 'revoke':
                $this->revokePermission();
                break;
            case 'list':
                $this->listPermissions();
                break;
            case 'show':
                $this->showPermission();
                break;
            default:
                $this->error("Invalid action: {$action}");
                return 1;
        }
        
        return 0;
    }
    
    /**
     * Grant a permission to a user
     */
    protected function grantPermission()
    {
        // Get user
        $user = $this->getUser();
        if (!$user) return;
        
        // Check if user is admin
        if ($user->isAdmin()) {
            $this->warn("Note: User '{$user->name}' is an admin and already has full access to all tables.");
            if (!$this->confirm('Do you still want to create an explicit permission?', false)) {
                return;
            }
        }
        
        // Get table
        $table = $this->getTable();
        if (!$table) return;
        
        // Check if permission already exists
        $existingPermission = $user->getPermissionForTable($table);
        if ($existingPermission) {
            $this->warn("User already has permissions for table '{$table}'.");
            if (!$this->confirm('Do you want to update the existing permission?', true)) {
                return;
            }
        }
        
        // Get operations
        $operations = $this->getOperations();
        
        // Get column restrictions
        $columnRestrictions = $this->getColumnRestrictions();
        
        // Get where conditions
        $whereConditions = $this->getWhereConditions();
        
        // Create or update permission
        $permissionData = [
            'user_id' => $user->id,
            'table_name' => $table,
            'can_select' => in_array('select', $operations),
            'can_insert' => in_array('insert', $operations),
            'can_update' => in_array('update', $operations),
            'can_delete' => in_array('delete', $operations),
        ];
        
        if (!empty($columnRestrictions)) {
            $permissionData['column_restrictions'] = $columnRestrictions;
        }
        
        if (!empty($whereConditions)) {
            $permissionData['where_conditions'] = $whereConditions;
        }
        
        if ($existingPermission) {
            $existingPermission->update($permissionData);
            $permission = $existingPermission->fresh();
            $this->info("Permission updated successfully!");
        } else {
            $permission = Permission::create($permissionData);
            $this->info("Permission granted successfully!");
        }
        
        $this->displayPermissionDetails($permission);
    }
    
    /**
     * Revoke a permission from a user
     */
    protected function revokePermission()
    {
        $permissionId = $this->option('id');
        
        if (!$permissionId) {
            // List permissions for selection
            $this->listPermissions();
            $permissionId = $this->ask('Enter the ID of the permission to revoke');
        }
        
        $permission = Permission::find($permissionId);
        
        if (!$permission) {
            $this->error("Permission with ID {$permissionId} not found!");
            return;
        }
        
        // Get user
        $user = User::find($permission->user_id);
        $userName = $user ? $user->name : 'Unknown User';
        
        // Confirm deletion
        if (!$this->confirm("Are you sure you want to revoke permission for table '{$permission->table_name}' from user '{$userName}'?")) {
            $this->info('Operation cancelled.');
            return;
        }
        
        $permission->delete();
        $this->info("Permission for table '{$permission->table_name}' has been revoked from user '{$userName}'.");
    }
    
    /**
     * List all permissions
     */
    protected function listPermissions()
    {
        $userId = null;
        $tableName = null;
        
        if ($this->option('user')) {
            $user = $this->getUser();
            if ($user) {
                $userId = $user->id;
            }
        }
        
        if ($this->option('table')) {
            $tableName = $this->option('table');
        }
        
        $query = Permission::query();
        
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        if ($tableName) {
            $query->where('table_name', $tableName);
        }
        
        $permissions = $query->get();
        
        if ($permissions->isEmpty()) {
            $this->info('No permissions found.');
            return;
        }
        
        $tableData = [];
        
        foreach ($permissions as $permission) {
            $user = User::find($permission->user_id);
            $userName = $user ? $user->name : 'Unknown User';
            $userEmail = $user ? $user->email : 'Unknown Email';
            
            $operations = [];
            if ($permission->can_select) $operations[] = 'select';
            if ($permission->can_insert) $operations[] = 'insert';
            if ($permission->can_update) $operations[] = 'update';
            if ($permission->can_delete) $operations[] = 'delete';
            
            $tableData[] = [
                'ID' => $permission->id,
                'User' => $userName . ' (' . $userEmail . ')',
                'Table' => $permission->table_name,
                'Operations' => implode(', ', $operations),
                'Has Conditions' => !empty($permission->where_conditions) ? 'Yes' : 'No',
                'Has Column Restrictions' => !empty($permission->column_restrictions) ? 'Yes' : 'No',
            ];
        }
        
        $this->table(
            array_keys($tableData[0]),
            array_map(function ($item) {
                return array_values($item);
            }, $tableData)
        );
        
        $this->info("Total permissions: " . count($tableData));
        $this->line('');
        $this->line('Use "dbaas:permission show --id=<id>" to view detailed permission information.');
    }
    
    /**
     * Show detailed information about a permission
     */
    protected function showPermission()
    {
        $permissionId = $this->option('id');
        
        if (!$permissionId) {
            // List permissions for selection
            $this->listPermissions();
            $permissionId = $this->ask('Enter the ID of the permission to show');
        }
        
        $permission = Permission::find($permissionId);
        
        if (!$permission) {
            $this->error("Permission with ID {$permissionId} not found!");
            return;
        }
        
        $this->displayPermissionDetails($permission);
    }
    
    /**
     * Display detailed information about a permission
     */
    protected function displayPermissionDetails($permission)
    {
        $user = User::find($permission->user_id);
        $userName = $user ? $user->name : 'Unknown User';
        $userEmail = $user ? $user->email : 'Unknown Email';
        
        $this->line('');
        $this->info("Permission Details (ID: {$permission->id})");
        $this->line('----------------------------------------');
        
        $this->line("<fg=yellow>User:</> {$userName} ({$userEmail})");
        $this->line("<fg=yellow>Table:</> {$permission->table_name}");
        $this->line('');
        
        $this->line("<fg=yellow>Operations:</>");
        $this->line("  SELECT: " . ($permission->can_select ? 'Allowed' : 'Denied'));
        $this->line("  INSERT: " . ($permission->can_insert ? 'Allowed' : 'Denied'));
        $this->line("  UPDATE: " . ($permission->can_update ? 'Allowed' : 'Denied'));
        $this->line("  DELETE: " . ($permission->can_delete ? 'Allowed' : 'Denied'));
        $this->line('');
        
        if (!empty($permission->column_restrictions)) {
            $this->line("<fg=yellow>Column Restrictions:</>");
            
            if (!empty($permission->column_restrictions['allowed'])) {
                $this->line("  Allowed Columns: " . implode(', ', $permission->column_restrictions['allowed']));
            }
            
            if (!empty($permission->column_restrictions['denied'])) {
                $this->line("  Denied Columns: " . implode(', ', $permission->column_restrictions['denied']));
            }
            
            $this->line('');
        }
        
        if (!empty($permission->where_conditions)) {
            $this->line("<fg=yellow>Where Conditions:</>");
            
            foreach ($permission->where_conditions as $index => $condition) {
                $operator = $condition[1];
                $value = is_array($condition[2]) ? '[' . implode(', ', $condition[2]) . ']' : $condition[2];
                $this->line("  Condition " . ($index + 1) . ": {$condition[0]} {$operator} {$value}");
            }
            
            $this->line('');
        }
        
        $this->line("<fg=yellow>Created:</> " . $permission->created_at->format('Y-m-d H:i:s'));
        $this->line("<fg=yellow>Updated:</> " . $permission->updated_at->format('Y-m-d H:i:s'));
    }
    
    /**
     * Get a user by ID or email
     */
    protected function getUser()
    {
        $userIdentifier = $this->option('user');
        
        if (!$userIdentifier) {
            // List users for selection
            $users = User::all(['id', 'name', 'email', 'role']);
            
            $choices = $users->map(function ($user) {
                return $user->name . ' (' . $user->email . ') - ' . $user->role;
            })->toArray();
            
            $selectedIndex = array_search(
                $this->choice('Select a user', $choices, 0),
                $choices
            );
            
            return $users[$selectedIndex];
        }
        
        // Check if it's an ID or email
        if (is_numeric($userIdentifier)) {
            $user = User::find($userIdentifier);
        } else {
            $user = User::where('email', $userIdentifier)->first();
        }
        
        if (!$user) {
            $this->error("User not found with identifier: {$userIdentifier}");
            return null;
        }
        
        return $user;
    }
    
    /**
     * Get a table name
     */
    protected function getTable()
    {
        $tableName = $this->option('table');
        
        if (!$tableName) {
            // Get all tables
            $tables = $this->getTables();
            
            if (empty($tables)) {
                $this->error('No tables found in the database.');
                return null;
            }
            
            $tableName = $this->choice('Select table', $tables, 0);
        }
        
        if (!Schema::hasTable($tableName)) {
            $this->error("Table '{$tableName}' does not exist.");
            return null;
        }
        
        return $tableName;
    }
    
    /**
     * Get operations from user input
     */
    protected function getOperations()
    {
        $operationsInput = $this->option('operations');
        $validOperations = ['select', 'insert', 'update', 'delete'];
        
        if (!$operationsInput) {
            $operations = [];
            
            $this->line('Select operations to allow:');
            
            foreach ($validOperations as $operation) {
                if ($this->confirm("Allow {$operation} operation?", true)) {
                    $operations[] = $operation;
                }
            }
            
            return $operations;
        }
        
        $operations = explode(',', $operationsInput);
        $operations = array_map('trim', $operations);
        $operations = array_filter($operations, function ($op) use ($validOperations) {
            return in_array($op, $validOperations);
        });
        
        return $operations;
    }
    
    /**
     * Get column restrictions from user input
     */
    protected function getColumnRestrictions()
    {
        $allowedColumns = $this->option('columns-allowed');
        $deniedColumns = $this->option('columns-denied');
        
        if (!$allowedColumns && !$deniedColumns) {
            if (!$this->confirm('Do you want to set column restrictions?', false)) {
                return [];
            }
            
            $restrictionType = $this->choice(
                'What type of column restriction do you want to set?',
                ['allowed', 'denied', 'none'],
                'none'
            );
            
            if ($restrictionType === 'none') {
                return [];
            }
            
            $tableName = $this->option('table');
            $columns = Schema::getColumnListing($tableName);
            
            if ($restrictionType === 'allowed') {
                $selectedColumns = $this->choice(
                    'Select columns to allow (multiple, comma-separated)',
                    $columns,
                    null,
                    null,
                    true
                );
                
                return ['allowed' => $selectedColumns];
            } else {
                $selectedColumns = $this->choice(
                    'Select columns to deny (multiple, comma-separated)',
                    $columns,
                    null,
                    null,
                    true
                );
                
                return ['denied' => $selectedColumns];
            }
        }
        
        $restrictions = [];
        
        if ($allowedColumns) {
            $restrictions['allowed'] = explode(',', $allowedColumns);
            $restrictions['allowed'] = array_map('trim', $restrictions['allowed']);
        }
        
        if ($deniedColumns) {
            $restrictions['denied'] = explode(',', $deniedColumns);
            $restrictions['denied'] = array_map('trim', $restrictions['denied']);
        }
        
        return $restrictions;
    }
    
    /**
     * Get where conditions from user input
     */
    protected function getWhereConditions()
    {
        $whereInput = $this->option('where');
        
        if (!$whereInput) {
            if (!$this->confirm('Do you want to set where conditions?', false)) {
                return [];
            }
            
            $tableName = $this->option('table');
            $columns = Schema::getColumnListing($tableName);
            
            $conditions = [];
            $addMore = true;
            
            while ($addMore) {
                $column = $this->choice('Select column', $columns, 0);
                
                $operator = $this->choice(
                    'Select operator',
                    ['=', '!=', '>', '<', '>=', '<=', 'in', 'not in', 'like', 'not like'],
                    '='
                );
                
                if (in_array($operator, ['in', 'not in'])) {
                    $valueInput = $this->ask('Enter values (comma-separated)');
                    $value = explode(',', $valueInput);
                    $value = array_map('trim', $value);
                } else {
                    $value = $this->ask('Enter value');
                }
                
                $conditions[] = [$column, $operator, $value];
                
                $addMore = $this->confirm('Add another condition?', false);
            }
            
            return $conditions;
        }
        
        try {
            return json_decode($whereInput, true);
        } catch (\Exception $e) {
            $this->error('Invalid JSON format for where conditions.');
            return [];
        }
    }
    
    /**
     * Get all tables in the database
     */
    protected function getTables()
    {
        $connection = config('database.default');
        $tables = [];
        
        switch (config("database.connections.{$connection}.driver")) {
            case 'mysql':
                $tables = DB::select("SHOW TABLES");
                $tables = array_map(function ($table) {
                    $table = (array) $table;
                    return reset($table);
                }, $tables);
                break;
            
            case 'pgsql':
                $tables = DB::select("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname != 'pg_catalog' AND schemaname != 'information_schema'");
                $tables = array_map(function ($table) {
                    return $table->tablename;
                }, $tables);
                break;
            
            case 'sqlite':
                $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                $tables = array_map(function ($table) {
                    return $table->name;
                }, $tables);
                break;
            
            default:
                $this->error('Unsupported database driver.');
                return [];
        }
        
        // Filter out Laravel system tables
        $tables = array_filter($tables, function ($table) {
            return !in_array($table, $this->laravelTables);
        });
        
        return $tables;
    }
}
