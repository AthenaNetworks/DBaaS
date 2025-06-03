<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TableList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dbaas:table:list {--details : Show column details for each table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all tables in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tables = $this->getTables();
        
        if (empty($tables)) {
            $this->info('No tables found in the database.');
            return 0;
        }
        
        $showDetails = $this->option('details');
        
        if ($showDetails) {
            $this->displayTablesWithDetails($tables);
        } else {
            $this->displayTables($tables);
            $this->line('');
            $this->line('Use --details option to see column information for each table.');
        }
        
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
        $laravelTables = [
            'cache', 'cache_locks', 'failed_jobs', 'job_batches', 'jobs',
            'migrations', 'password_reset_tokens', 'personal_access_tokens',
            'sessions'
        ];
        
        return array_values(array_diff($tables, $laravelTables));
    }
    
    /**
     * Display tables as a simple list
     */
    protected function displayTables(array $tables)
    {
        $this->info('Database Tables:');
        
        $headers = ['Table Name'];
        $rows = array_map(function($table) {
            return [$table];
        }, $tables);
        
        $this->table($headers, $rows);
    }
    
    /**
     * Display tables with column details
     */
    protected function displayTablesWithDetails(array $tables)
    {
        $this->info('Database Tables with Column Details:');
        
        foreach ($tables as $table) {
            $this->line('');
            $this->info("Table: {$table}");
            
            $columns = $this->getTableColumns($table);
            
            $headers = ['Column', 'Type', 'Nullable', 'Default', 'Key'];
            $this->table($headers, $columns);
        }
    }
    
    /**
     * Get column information for a table
     */
    protected function getTableColumns(string $table)
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        $columns = [];
        
        if ($driver === 'mysql') {
            $columnData = DB::select("SHOW COLUMNS FROM {$table}");
            
            foreach ($columnData as $column) {
                $columns[] = [
                    'name' => $column->Field,
                    'type' => $column->Type,
                    'nullable' => $column->Null === 'YES' ? 'YES' : 'NO',
                    'default' => $column->Default ?? 'NULL',
                    'key' => $column->Key ?: '-',
                ];
            }
        } else if ($driver === 'sqlite') {
            $columnData = DB::select("PRAGMA table_info({$table})");
            
            foreach ($columnData as $column) {
                $columns[] = [
                    'name' => $column->name,
                    'type' => $column->type,
                    'nullable' => $column->notnull ? 'NO' : 'YES',
                    'default' => $column->dflt_value ?? 'NULL',
                    'key' => $column->pk ? 'PK' : '-',
                ];
            }
        } else if ($driver === 'pgsql') {
            $columnData = DB::select("
                SELECT 
                    column_name, 
                    data_type, 
                    is_nullable,
                    column_default,
                    (CASE WHEN pk.column_name IS NOT NULL THEN 'PK' ELSE '' END) as key
                FROM information_schema.columns
                LEFT JOIN (
                    SELECT pg_attribute.attname as column_name
                    FROM pg_index, pg_class, pg_attribute, pg_namespace
                    WHERE pg_class.oid = '{$table}'::regclass
                    AND indrelid = pg_class.oid
                    AND pg_class.relnamespace = pg_namespace.oid
                    AND pg_attribute.attrelid = pg_class.oid
                    AND pg_attribute.attnum = any(pg_index.indkey)
                    AND indisprimary
                ) pk ON pk.column_name = columns.column_name
                WHERE table_name = '{$table}'
                ORDER BY ordinal_position
            ");
            
            foreach ($columnData as $column) {
                $columns[] = [
                    'name' => $column->column_name,
                    'type' => $column->data_type,
                    'nullable' => $column->is_nullable === 'YES' ? 'YES' : 'NO',
                    'default' => $column->column_default ?? 'NULL',
                    'key' => $column->key ?: '-',
                ];
            }
        }
        
        return $columns;
    }
}
