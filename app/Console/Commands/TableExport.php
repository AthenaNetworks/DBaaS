<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

class TableExport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dbaas:table:export 
                            {table? : The table to export data from}
                            {--format=json : Export format (json, csv)}
                            {--output=storage/exports : Output directory}
                            {--filename= : Custom filename (without extension)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export table data to JSON or CSV format';

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
            
            $tableName = $this->choice('Select table to export data from', $tables, 0);
        }
        
        // Check if the table is a Laravel built-in table
        if (in_array($tableName, $this->laravelTables)) {
            $this->error("Table '{$tableName}' is a Laravel system table and cannot be exported.");
            return 1;
        }
        
        if (!Schema::hasTable($tableName)) {
            $this->error("Table '{$tableName}' does not exist.");
            return 1;
        }
        
        // Get format
        $format = strtolower($this->option('format'));
        if (!in_array($format, ['json', 'csv'])) {
            $this->error("Invalid format '{$format}'. Supported formats: json, csv");
            return 1;
        }
        
        // Get output directory
        $outputDir = $this->option('output');
        if (!File::exists($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }
        
        // Get filename
        $filename = $this->option('filename') ?: $tableName . '_' . date('Y-m-d_His');
        $fullPath = $outputDir . '/' . $filename . '.' . $format;
        
        // Get data
        $data = DB::table($tableName)->get();
        
        if ($data->isEmpty()) {
            $this->warn("Table '{$tableName}' has no data to export.");
            return 0;
        }
        
        // Export data
        if ($format === 'json') {
            $this->exportJson($data, $fullPath);
        } else {
            $this->exportCsv($data, $fullPath);
        }
        
        $this->info("Table '{$tableName}' data exported to {$fullPath}");
        return 0;
    }
    
    /**
     * Export data to JSON format
     */
    protected function exportJson($data, $path)
    {
        $jsonData = $data->toJson(JSON_PRETTY_PRINT);
        File::put($path, $jsonData);
    }
    
    /**
     * Export data to CSV format
     */
    protected function exportCsv($data, $path)
    {
        $handle = fopen($path, 'w');
        
        // If there's no data, just create an empty file with headers
        if ($data->isEmpty()) {
            fclose($handle);
            return;
        }
        
        // Write headers
        $firstRow = $data->first();
        $headers = array_keys(get_object_vars($firstRow));
        fputcsv($handle, $headers);
        
        // Write data
        foreach ($data as $row) {
            fputcsv($handle, get_object_vars($row));
        }
        
        fclose($handle);
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
}
