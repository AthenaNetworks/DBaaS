<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TableDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dbaas:table:delete {table? : The table to delete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete an existing database table';

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
            
            $tableName = $this->choice('Select table to delete', $tables, 0);
        }
        
        if (!Schema::hasTable($tableName)) {
            $this->error("Table '{$tableName}' does not exist.");
            return 1;
        }
        
        $this->warn("You are about to delete the table '{$tableName}' and ALL its data!");
        $this->warn("This action cannot be undone!");
        
        // Ask for confirmation with table name
        $confirmation = $this->ask("To confirm, please type the table name '{$tableName}'");
        
        if ($confirmation !== $tableName) {
            $this->info('Table deletion cancelled.');
            return 0;
        }
        
        // Double-check
        if (!$this->confirm('Are you absolutely sure you want to delete this table?', false)) {
            $this->info('Table deletion cancelled.');
            return 0;
        }
        
        // Delete the table
        Schema::dropIfExists($tableName);
        
        $this->info("Table '{$tableName}' has been deleted successfully.");
        return 0;
    }
    
    /**
     * List of Laravel's built-in tables to hide from the user
     */
    protected $laravelTables = [
        'cache', 'cache_locks', 'failed_jobs', 'job_batches', 'jobs',
        'migrations', 'password_reset_tokens', 'personal_access_tokens',
        'sessions'
    ];
    
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
}
