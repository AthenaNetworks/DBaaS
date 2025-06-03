<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class TableModify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dbaas:table:modify {table : The table to modify}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Modify an existing database table';

    /**
     * Execute the console command.
     */
    /**
     * List of Laravel's built-in tables to hide from the user
     */
    protected $laravelTables = [
        'cache', 'cache_locks', 'failed_jobs', 'job_batches', 'jobs',
        'migrations', 'password_reset_tokens', 'personal_access_tokens',
        'sessions'
    ];
    
    public function handle()
    {
        $tableName = $this->argument('table');
        
        // Check if the table is a Laravel built-in table
        if (in_array($tableName, $this->laravelTables)) {
            $this->error("Table '{$tableName}' is a Laravel system table and cannot be modified.");
            return 1;
        }
        
        if (!Schema::hasTable($tableName)) {
            $this->error("Table '{$tableName}' does not exist.");
            return 1;
        }
        
        $this->info("Modifying table: {$tableName}");
        
        $action = $this->choice(
            'What would you like to do?',
            ['Add column', 'Modify column', 'Drop column', 'Cancel'],
            0
        );
        
        switch ($action) {
            case 'Add column':
                $this->addColumn($tableName);
                break;
            case 'Modify column':
                $this->modifyColumn($tableName);
                break;
            case 'Drop column':
                $this->dropColumn($tableName);
                break;
            case 'Cancel':
                $this->info('Operation cancelled.');
                return 0;
        }
        
        return 0;
    }
    
    /**
     * Add a new column to the table
     */
    protected function addColumn(string $tableName)
    {
        $columnName = $this->ask('Enter the name for the new column');
        
        $types = [
            'string', 'integer', 'bigInteger', 'boolean', 'date', 'dateTime', 
            'decimal', 'float', 'text', 'json', 'jsonb'
        ];
        
        $type = $this->choice('Column type', $types, 0);
        
        $length = null;
        if (in_array($type, ['string', 'decimal'])) {
            if ($type === 'string') {
                $length = $this->ask('Maximum length', 255);
            } else if ($type === 'decimal') {
                $precision = $this->ask('Precision (total digits)', 8);
                $scale = $this->ask('Scale (decimal digits)', 2);
                $length = [$precision, $scale];
            }
        }
        
        $nullable = $this->confirm('Can this column be null?', false);
        
        $default = null;
        if ($this->confirm('Do you want to set a default value?', false)) {
            $default = $this->ask('Enter the default value');
        }
        
        $unique = $this->confirm('Should this column have a unique constraint?', false);
        $index = !$unique && $this->confirm('Should this column be indexed?', false);
        
        // Confirm the operation
        $this->info("About to add column '{$columnName}' to table '{$tableName}'");
        $this->line("Type: {$type}");
        if ($length) {
            $lengthStr = is_array($length) ? implode(',', $length) : $length;
            $this->line("Length: {$lengthStr}");
        }
        $this->line("Nullable: " . ($nullable ? 'Yes' : 'No'));
        $this->line("Default: " . ($default ?? 'NULL'));
        $this->line("Unique: " . ($unique ? 'Yes' : 'No'));
        $this->line("Index: " . ($index ? 'Yes' : 'No'));
        
        if (!$this->confirm('Proceed with adding this column?', true)) {
            $this->info('Operation cancelled.');
            return;
        }
        
        Schema::table($tableName, function (Blueprint $table) use ($columnName, $type, $length, $nullable, $default, $unique, $index) {
            $column = null;
            
            switch ($type) {
                case 'string':
                    $column = $table->string($columnName, $length);
                    break;
                case 'integer':
                    $column = $table->integer($columnName);
                    break;
                case 'bigInteger':
                    $column = $table->bigInteger($columnName);
                    break;
                case 'boolean':
                    $column = $table->boolean($columnName);
                    break;
                case 'date':
                    $column = $table->date($columnName);
                    break;
                case 'dateTime':
                    $column = $table->dateTime($columnName);
                    break;
                case 'decimal':
                    $column = $table->decimal($columnName, $length[0], $length[1]);
                    break;
                case 'float':
                    $column = $table->float($columnName);
                    break;
                case 'text':
                    $column = $table->text($columnName);
                    break;
                case 'json':
                    $column = $table->json($columnName);
                    break;
                case 'jsonb':
                    $column = $table->jsonb($columnName);
                    break;
            }
            
            if ($nullable) {
                $column->nullable();
            }
            
            if ($default !== null) {
                $column->default($default);
            }
            
            if ($unique) {
                $column->unique();
            } elseif ($index) {
                $column->index();
            }
        });
        
        $this->info("Column '{$columnName}' added to table '{$tableName}' successfully.");
    }
    
    /**
     * Modify an existing column in the table
     */
    protected function modifyColumn(string $tableName)
    {
        // Get existing columns
        $columns = Schema::getColumnListing($tableName);
        
        if (empty($columns)) {
            $this->error("No columns found in table '{$tableName}'.");
            return;
        }
        
        // Remove id, created_at, updated_at from the list as they shouldn't be modified
        $columns = array_diff($columns, ['id', 'created_at', 'updated_at']);
        
        if (empty($columns)) {
            $this->error("No modifiable columns found in table '{$tableName}'.");
            return;
        }
        
        $columnName = $this->choice('Select column to modify', $columns, 0);
        
        $this->warn("Modifying column '{$columnName}' in table '{$tableName}'.");
        $this->line("Note: Some modifications might cause data loss if not compatible with existing data.");
        
        if (!$this->confirm('Do you want to continue?', true)) {
            $this->info('Operation cancelled.');
            return;
        }
        
        $types = [
            'string', 'integer', 'bigInteger', 'boolean', 'date', 'dateTime', 
            'decimal', 'float', 'text', 'json', 'jsonb'
        ];
        
        $type = $this->choice('New column type', $types, 0);
        
        $length = null;
        if (in_array($type, ['string', 'decimal'])) {
            if ($type === 'string') {
                $length = $this->ask('Maximum length', 255);
            } else if ($type === 'decimal') {
                $precision = $this->ask('Precision (total digits)', 8);
                $scale = $this->ask('Scale (decimal digits)', 2);
                $length = [$precision, $scale];
            }
        }
        
        $nullable = $this->confirm('Can this column be null?', false);
        
        $default = null;
        if ($this->confirm('Do you want to set a default value?', false)) {
            $default = $this->ask('Enter the default value');
        }
        
        // Confirm the operation
        $this->info("About to modify column '{$columnName}' in table '{$tableName}'");
        $this->line("New type: {$type}");
        if ($length) {
            $lengthStr = is_array($length) ? implode(',', $length) : $length;
            $this->line("Length: {$lengthStr}");
        }
        $this->line("Nullable: " . ($nullable ? 'Yes' : 'No'));
        $this->line("Default: " . ($default ?? 'NULL'));
        
        if (!$this->confirm('Proceed with modifying this column?', true)) {
            $this->info('Operation cancelled.');
            return;
        }
        
        Schema::table($tableName, function (Blueprint $table) use ($columnName, $type, $length, $nullable, $default) {
            $column = null;
            
            switch ($type) {
                case 'string':
                    $column = $table->string($columnName, $length)->change();
                    break;
                case 'integer':
                    $column = $table->integer($columnName)->change();
                    break;
                case 'bigInteger':
                    $column = $table->bigInteger($columnName)->change();
                    break;
                case 'boolean':
                    $column = $table->boolean($columnName)->change();
                    break;
                case 'date':
                    $column = $table->date($columnName)->change();
                    break;
                case 'dateTime':
                    $column = $table->dateTime($columnName)->change();
                    break;
                case 'decimal':
                    $column = $table->decimal($columnName, $length[0], $length[1])->change();
                    break;
                case 'float':
                    $column = $table->float($columnName)->change();
                    break;
                case 'text':
                    $column = $table->text($columnName)->change();
                    break;
                case 'json':
                    $column = $table->json($columnName)->change();
                    break;
                case 'jsonb':
                    $column = $table->jsonb($columnName)->change();
                    break;
            }
            
            if ($nullable) {
                $column->nullable()->change();
            } else {
                $column->nullable(false)->change();
            }
            
            if ($default !== null) {
                $column->default($default)->change();
            }
        });
        
        $this->info("Column '{$columnName}' in table '{$tableName}' modified successfully.");
    }
    
    /**
     * Drop a column from the table
     */
    protected function dropColumn(string $tableName)
    {
        // Get existing columns
        $columns = Schema::getColumnListing($tableName);
        
        if (empty($columns)) {
            $this->error("No columns found in table '{$tableName}'.");
            return;
        }
        
        // Remove id, created_at, updated_at from the list as they shouldn't be dropped
        $columns = array_diff($columns, ['id', 'created_at', 'updated_at']);
        
        if (empty($columns)) {
            $this->error("No droppable columns found in table '{$tableName}'.");
            return;
        }
        
        $columnName = $this->choice('Select column to drop', $columns, 0);
        
        $this->warn("You are about to drop column '{$columnName}' from table '{$tableName}'.");
        $this->warn("This will permanently delete all data in this column and cannot be undone!");
        
        if (!$this->confirm('Are you absolutely sure you want to drop this column?', false)) {
            $this->info('Operation cancelled.');
            return;
        }
        
        Schema::table($tableName, function (Blueprint $table) use ($columnName) {
            $table->dropColumn($columnName);
        });
        
        $this->info("Column '{$columnName}' dropped from table '{$tableName}' successfully.");
    }
}
