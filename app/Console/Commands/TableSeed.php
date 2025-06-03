<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TableSeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dbaas:table:seed {table? : The table to seed data into}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed data into a database table';

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
        $tableName = $this->argument('table');
        
        if (!$tableName) {
            // Get all tables
            $tables = $this->getTables();
            
            if (empty($tables)) {
                $this->error('No tables found in the database.');
                return 1;
            }
            
            $tableName = $this->choice('Select table to seed data into', $tables, 0);
        }
        
        // Check if the table is a Laravel built-in table
        if (in_array($tableName, $this->laravelTables)) {
            $this->error("Table '{$tableName}' is a Laravel system table and cannot be seeded.");
            return 1;
        }
        
        if (!Schema::hasTable($tableName)) {
            $this->error("Table '{$tableName}' does not exist.");
            return 1;
        }
        
        // Get the table columns
        $columns = Schema::getColumnListing($tableName);
        
        // Remove id, created_at, updated_at columns as they'll be auto-generated
        $columns = array_diff($columns, ['id', 'created_at', 'updated_at']);
        
        if (empty($columns)) {
            $this->error("Table '{$tableName}' has no columns to seed (excluding id and timestamps).");
            return 1;
        }
        
        // Ask how many records to seed
        $recordCount = $this->ask('How many records do you want to seed?', 5);
        $recordCount = max(1, intval($recordCount));
        
        $this->info("You are about to seed {$recordCount} records into table '{$tableName}'.");
        
        // Ask for data for each record
        $records = [];
        
        for ($i = 0; $i < $recordCount; $i++) {
            $this->line("\nRecord #" . ($i + 1));
            $record = [];
            
            foreach ($columns as $column) {
                // Get column type
                $columnType = Schema::getColumnType($tableName, $column);
                
                // Suggest a default value based on column type
                $default = $this->suggestDefaultValue($column, $columnType);
                
                // Ask for the value
                $value = $this->ask("Value for '{$column}' ({$columnType})", $default);
                
                // Convert to appropriate type
                $record[$column] = $this->convertValueToType($value, $columnType);
            }
            
            $records[] = $record;
        }
        
        // Confirm before inserting
        $this->table(
            $columns,
            array_map(function($record) use ($columns) {
                return array_map(function($column) use ($record) {
                    return $record[$column] ?? 'NULL';
                }, $columns);
            }, $records)
        );
        
        if (!$this->confirm('Do you want to insert these records?', true)) {
            $this->info('Seeding cancelled.');
            return 0;
        }
        
        // Insert the records
        foreach ($records as $record) {
            DB::table($tableName)->insert($record);
        }
        
        $this->info("Successfully seeded {$recordCount} records into table '{$tableName}'.");
        return 0;
    }
    
    /**
     * Get all tables in the database
     */
    protected function getTables()
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        
        $tables = [];
        
        if ($driver === 'sqlite') {
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
            $tables = array_map(function($table) {
                return $table->name;
            }, $tables);
        } else if ($driver === 'mysql') {
            $database = config("database.connections.{$connection}.database");
            $tablesResult = DB::select('SHOW TABLES');
            $column = 'Tables_in_' . $database;
            $tables = array_map(function($table) use ($column) {
                return $table->$column;
            }, $tablesResult);
        } else if ($driver === 'pgsql') {
            $tablesResult = DB::select("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public' ORDER BY tablename");
            $tables = array_map(function($table) {
                return $table->tablename;
            }, $tablesResult);
        }
        
        // Filter out Laravel's built-in tables
        return array_values(array_diff($tables, $this->laravelTables));
    }
    
    /**
     * Suggest a default value based on column name and type
     */
    protected function suggestDefaultValue($column, $type)
    {
        // Common patterns
        if (Str::endsWith($column, '_id')) {
            return 1;
        }
        
        if (Str::contains($column, 'email')) {
            return 'user' . rand(1, 100) . '@example.com';
        }
        
        if (Str::contains($column, 'name')) {
            return 'Test Name';
        }
        
        if (Str::contains($column, 'description') || Str::contains($column, 'content')) {
            return 'This is sample content for testing purposes.';
        }
        
        if (Str::contains($column, 'price') || Str::contains($column, 'amount')) {
            return rand(1, 1000) / 100;
        }
        
        // Based on type
        switch ($type) {
            case 'string':
                return 'Sample ' . Str::title($column);
            case 'integer':
            case 'bigint':
                return rand(1, 100);
            case 'boolean':
                return true;
            case 'date':
                return date('Y-m-d');
            case 'datetime':
                return date('Y-m-d H:i:s');
            case 'decimal':
            case 'float':
                return rand(100, 10000) / 100;
            case 'json':
                return '{"key": "value"}';
            default:
                return 'Sample data';
        }
    }
    
    /**
     * Convert a value to the appropriate type
     */
    protected function convertValueToType($value, $type)
    {
        switch ($type) {
            case 'integer':
            case 'bigint':
                return intval($value);
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'decimal':
            case 'float':
            case 'double':
                return floatval($value);
            case 'json':
                // If it's already a JSON string, return it
                if (is_string($value) && $this->isJson($value)) {
                    return $value;
                }
                // Otherwise, convert to JSON
                return json_encode(['value' => $value]);
            default:
                return $value;
        }
    }
    
    /**
     * Check if a string is valid JSON
     */
    protected function isJson($string)
    {
        if (!is_string($string)) {
            return false;
        }
        
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
