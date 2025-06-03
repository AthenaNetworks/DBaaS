<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;

class TableCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dbaas:table:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new database table interactively';
    
    /**
     * List of Laravel's built-in tables to prevent conflicts
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
        $this->info('DBaaS Table Creator');
        $this->line('This command will guide you through creating a new database table.');
        $this->newLine();

        // Get table name
        $tableName = $this->askForTableName();

        // Get columns
        $columns = $this->askForColumns();

        // Confirm creation
        $this->displayTableSummary($tableName, $columns);
        
        if (!$this->confirm('Do you want to create this table?', true)) {
            $this->info('Table creation cancelled.');
            return 1;
        }

        // Create the table
        $this->createTable($tableName, $columns);

        $this->info("Table '{$tableName}' created successfully!");
        return 0;
    }

    /**
     * Ask for a valid table name
     */
    protected function askForTableName()
    {
        $tableName = '';
        
        while (empty($tableName)) {
            $tableName = $this->ask('Enter table name (plural, snake_case)');
            
            // Validate table name
            if (empty($tableName)) {
                $this->error('Table name cannot be empty.');
                continue;
            }
            
            // Validate table name format
            if (!preg_match('/^[a-z][a-z0-9_]*$/', $tableName)) {
                $this->error('Table name must start with a letter and contain only lowercase letters, numbers, and underscores.');
                $tableName = '';
                continue;
            }
            
            // Check if table name is a Laravel built-in table
            if (in_array($tableName, $this->laravelTables)) {
                $this->error("'{$tableName}' is a Laravel system table name and cannot be used.");
                $tableName = '';
                continue;
            }
            
            // Check if table already exists
            if (Schema::hasTable($tableName)) {
                $this->error("Table '{$tableName}' already exists.");
                $tableName = '';
                continue;
            }
            
            // Suggest snake_case and plural if not already
            $suggestedName = Str::snake(Str::plural($tableName));
            if ($suggestedName !== $tableName) {
                if ($this->confirm("Would you like to use '{$suggestedName}' instead of '{$tableName}'?", true)) {
                    // Check if suggested name is a Laravel built-in table
                    if (in_array($suggestedName, $this->laravelTables)) {
                        $this->error("'{$suggestedName}' is a Laravel system table name and cannot be used.");
                        $tableName = '';
                        continue;
                    }
                    
                    // Check if suggested table already exists
                    if (Schema::hasTable($suggestedName)) {
                        $this->error("Table '{$suggestedName}' already exists.");
                        $tableName = '';
                        continue;
                    }
                    
                    $tableName = $suggestedName;
                }
            }
        }
        
        return $tableName;
    }

    /**
     * Ask for column definitions
     */
    protected function askForColumns()
    {
        $columns = [];
        $this->info('Now, let\'s define the columns for your table.');
        $this->line('For each column, you\'ll need to provide a name and type.');
        $this->newLine();

        // Always add id as primary key
        $columns[] = [
            'name' => 'id',
            'type' => 'id',
            'length' => null,
            'nullable' => false,
            'default' => null,
            'primary' => true,
            'index' => false,
            'unique' => false,
        ];
        
        $this->info('Column "id" (primary key) has been automatically added.');
        
        $addMoreColumns = true;
        
        while ($addMoreColumns) {
            $column = $this->askForColumnDetails();
            $columns[] = $column;
            
            $addMoreColumns = $this->confirm('Do you want to add another column?', true);
        }
        
        // Always add timestamps
        $columns[] = [
            'name' => 'created_at',
            'type' => 'timestamp',
            'length' => null,
            'nullable' => true,
            'default' => null,
            'primary' => false,
            'index' => false,
            'unique' => false,
        ];
        
        $columns[] = [
            'name' => 'updated_at',
            'type' => 'timestamp',
            'length' => null,
            'nullable' => true,
            'default' => null,
            'primary' => false,
            'index' => false,
            'unique' => false,
        ];
        
        $this->info('Columns "created_at" and "updated_at" have been automatically added.');
        
        return $columns;
    }

    /**
     * Ask for details of a single column
     */
    protected function askForColumnDetails()
    {
        $column = [];
        
        // Column name
        $column['name'] = $this->ask('Column name (snake_case recommended)');
        $suggestedName = Str::snake($column['name']);
        if ($suggestedName !== $column['name']) {
            if ($this->confirm("Would you like to use '{$suggestedName}' instead of '{$column['name']}'?", true)) {
                $column['name'] = $suggestedName;
            }
        }
        
        // Column type
        $types = [
            'string', 'integer', 'bigInteger', 'boolean', 'date', 'dateTime', 
            'decimal', 'float', 'text', 'timestamp', 'json', 'foreignId'
        ];
        
        $column['type'] = $this->choice('Column type', $types, 0);
        
        // Handle foreign key references
        if ($column['type'] === 'foreignId') {
            // Get available tables for reference
            $tables = $this->getTables();
            
            if (empty($tables)) {
                $this->warn('No tables available to reference. Creating a regular bigInteger column instead.');
                $column['type'] = 'bigInteger';
            } else {
                // Add 'users' table if it's not in the list (it might be filtered out as a Laravel table)
                if (!in_array('users', $tables)) {
                    $tables[] = 'users';
                }
                
                // Ask which table to reference
                $column['references'] = $this->choice(
                    'Which table does this foreign key reference?',
                    $tables,
                    0
                );
                
                // Ask for on delete behavior
                $onDeleteOptions = ['restrict', 'cascade', 'set null', 'none'];
                $onDelete = $this->choice('On delete behavior', $onDeleteOptions, 0);
                if ($onDelete !== 'none') {
                    $column['onDelete'] = $onDelete;
                }
                
                // Ask for on update behavior
                $onUpdateOptions = ['restrict', 'cascade', 'none'];
                $onUpdate = $this->choice('On update behavior', $onUpdateOptions, 0);
                if ($onUpdate !== 'none') {
                    $column['onUpdate'] = $onUpdate;
                }
            }
        }
        
        // Length/precision for certain types
        $column['length'] = null;
        if (in_array($column['type'], ['string', 'decimal'])) {
            if ($column['type'] === 'string') {
                $column['length'] = $this->ask('Maximum length', 255);
            } else if ($column['type'] === 'decimal') {
                $precision = $this->ask('Precision (total digits)', 8);
                $scale = $this->ask('Scale (decimal digits)', 2);
                $column['length'] = [$precision, $scale];
            }
        }
        
        // Nullable
        $column['nullable'] = $this->confirm('Can this column be null?', false);
        
        // Default value
        $column['default'] = null;
        if ($this->confirm('Do you want to set a default value?', false)) {
            $column['default'] = $this->ask('Enter the default value');
            
            // Convert to appropriate type
            if ($column['type'] === 'boolean') {
                $column['default'] = filter_var($column['default'], FILTER_VALIDATE_BOOLEAN);
            } elseif (in_array($column['type'], ['integer', 'bigInteger'])) {
                $column['default'] = (int)$column['default'];
            } elseif (in_array($column['type'], ['decimal', 'float'])) {
                $column['default'] = (float)$column['default'];
            }
        }
        
        // Indexes
        $column['primary'] = false; // We already have id as primary key
        $column['unique'] = $this->confirm('Should this column have a unique constraint?', false);
        $column['index'] = !$column['unique'] && $this->confirm('Should this column be indexed?', false);
        
        return $column;
    }

    /**
     * Display a summary of the table to be created
     */
    protected function displayTableSummary($tableName, $columns)
    {
        $this->newLine();
        $this->info('Table Summary:');
        $this->line("Table name: {$tableName}");
        $this->line('Columns:');
        
        $headers = ['Name', 'Type', 'Length', 'Nullable', 'Default', 'Indexes'];
        $rows = [];
        
        foreach ($columns as $column) {
            $indexes = [];
            if ($column['primary']) $indexes[] = 'PRIMARY';
            if ($column['unique']) $indexes[] = 'UNIQUE';
            if ($column['index']) $indexes[] = 'INDEX';
            
            $length = $column['length'];
            if (is_array($length)) {
                $length = implode(',', $length);
            }
            
            $rows[] = [
                $column['name'],
                $column['type'],
                $length ?: '-',
                $column['nullable'] ? 'YES' : 'NO',
                $column['default'] ?? 'NULL',
                empty($indexes) ? '-' : implode(', ', $indexes),
            ];
        }
        
        $this->table($headers, $rows);
    }

    /**
     * Create the table in the database
     */
    protected function createTable($tableName, $columns)
    {
        Schema::create($tableName, function (Blueprint $table) use ($columns) {
            foreach ($columns as $column) {
                $this->addColumnToTable($table, $column);
            }
        });
    }

    /**
     * Add a column to the table blueprint
     */
    protected function addColumnToTable(Blueprint $table, $column)
    {
        $name = $column['name'];
        $type = $column['type'];
        
        // Handle special column types
        if ($type === 'id') {
            $table->id();
            return;
        }
        
        if ($type === 'timestamp' && in_array($name, ['created_at', 'updated_at'])) {
            $table->timestamps();
            return;
        }
        
        // Create the column with the appropriate type
        $tableColumn = null;
        
        switch ($type) {
            case 'string':
                $tableColumn = $table->string($name, $column['length']);
                break;
            case 'integer':
                $tableColumn = $table->integer($name);
                break;
            case 'bigInteger':
                $tableColumn = $table->bigInteger($name);
                break;
            case 'boolean':
                $tableColumn = $table->boolean($name);
                break;
            case 'date':
                $tableColumn = $table->date($name);
                break;
            case 'dateTime':
                $tableColumn = $table->dateTime($name);
                break;
            case 'decimal':
                $length = $column['length'] ?: [8, 2];
                $tableColumn = $table->decimal($name, $length[0], $length[1]);
                break;
            case 'float':
                $tableColumn = $table->float($name);
                break;
            case 'text':
                $tableColumn = $table->text($name);
                break;
            case 'json':
                $tableColumn = $table->json($name);
                break;
            case 'jsonb':
                $tableColumn = $table->jsonb($name);
                break;
            case 'foreignId':
                $tableColumn = $table->foreignId($name);
                
                // Get the referenced table
                if (isset($column['references'])) {
                    $references = $column['references'];
                    $tableColumn->constrained($references);
                    
                    // Add onDelete and onUpdate if specified
                    if (isset($column['onDelete']) && $column['onDelete'] === 'cascade') {
                        $tableColumn->cascadeOnDelete();
                    } else if (isset($column['onDelete']) && $column['onDelete'] === 'restrict') {
                        $tableColumn->restrictOnDelete();
                    } else if (isset($column['onDelete']) && $column['onDelete'] === 'set null') {
                        $tableColumn->nullOnDelete();
                    }
                    
                    if (isset($column['onUpdate']) && $column['onUpdate'] === 'cascade') {
                        $tableColumn->cascadeOnUpdate();
                    }
                }
                break;
            default:
                $tableColumn = $table->string($name);
        }
        
        // Apply column modifiers
        if ($column['nullable']) {
            $tableColumn->nullable();
        }
        
        if (isset($column['default'])) {
            $tableColumn->default($column['default']);
        }
        
        // Apply indexes
        if ($column['unique']) {
            $tableColumn->unique();
        } elseif ($column['index']) {
            $tableColumn->index();
        }
    }
}
