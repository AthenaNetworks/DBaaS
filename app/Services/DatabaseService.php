<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\QueryException;
use InvalidArgumentException;

class DatabaseService
{
    /**
     * Execute a SELECT query
     *
     * @param string $table
     * @param array $columns
     * @param array $where
     * @param array $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     */
    /**
     * Execute a SELECT query with permission checks
     *
     * @param string $table
     * @param array $columns
     * @param array $where
     * @param array $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @param \App\Models\User|null $user The authenticated user
     * @return array
     */
    public function select(string $table, array $columns = ['*'], array $where = [], array $orderBy = [], ?int $limit = null, ?int $offset = null, ?\App\Models\User $user = null)
    {
        // Check if operation is allowed
        if (!$this->isOperationAllowed('select')) {
            throw new InvalidArgumentException('SELECT operation is not allowed');
        }
        
        // Check if table is allowed
        $this->validateTable($table);
        
        // Apply max records limit
        $maxRecords = Config::get('dbaas.max_records_per_request', 1000);
        if (!$limit || $limit > $maxRecords) {
            $limit = $maxRecords;
        }
        
        // Apply column restrictions if user is provided and not an admin
        if ($user && !$user->isAdmin()) {
            $permission = $user->getPermissionForTable($table);
            if ($permission) {
                // Filter columns based on permissions
                if ($columns[0] === '*') {
                    // Get all table columns
                    $tableColumns = $this->getTableColumns($table);
                    $columns = array_filter($tableColumns, function($column) use ($permission) {
                        return $permission->isColumnAllowed($column);
                    });
                } else {
                    // Filter the requested columns
                    $columns = array_filter($columns, function($column) use ($permission) {
                        return $permission->isColumnAllowed($column);
                    });
                    
                    // If no columns are allowed, throw an exception
                    if (empty($columns)) {
                        throw new InvalidArgumentException('No columns are allowed for this operation');
                    }
                }
            }
        }
        
        $query = DB::table($table)->select($columns);

        // Apply where conditions
        $query = $this->applyWhereConditions($query, $where);

        // Apply order by
        foreach ($orderBy as $order) {
            if (isset($order['column']) && isset($order['direction'])) {
                $query->orderBy($order['column'], $order['direction']);
            }
        }

        // Apply limit and offset
        if ($limit) {
            $query->limit($limit);
        }
        if ($offset) {
            $query->offset($offset);
        }

        return $query->get()->toArray();
    }

    /**
     * Execute an INSERT query
     *
     * @param string $table
     * @param array $data
     * @return int|array ID or array of IDs
     */
    /**
     * Execute an INSERT query with permission checks
     *
     * @param string $table
     * @param array $data
     * @param \App\Models\User|null $user The authenticated user
     * @return int|array ID or array of IDs
     */
    public function insert(string $table, array $data, ?\App\Models\User $user = null)
    {
        // Check if operation is allowed
        if (!$this->isOperationAllowed('insert')) {
            throw new InvalidArgumentException('INSERT operation is not allowed');
        }
        
        // Check if table is allowed
        $this->validateTable($table);
        
        // Apply column restrictions if user is provided and not an admin
        if ($user && !$user->isAdmin()) {
            $permission = $user->getPermissionForTable($table);
            if ($permission) {
                // For batch insert
                if (isset($data[0]) && is_array($data[0])) {
                    foreach ($data as &$row) {
                        $row = $this->filterColumns($row, $permission);
                    }
                } else {
                    // For single insert
                    $data = $this->filterColumns($data, $permission);
                }
            }
        }
        
        // Handle single insert or batch insert
        if (isset($data[0]) && is_array($data[0])) {
            // Batch insert
            $insertedIds = [];
            foreach ($data as $row) {
                $id = DB::table($table)->insertGetId($row);
                $insertedIds[] = $id;
            }
            return $insertedIds;
        } else {
            // Single insert
            return DB::table($table)->insertGetId($data);
        }
    }

    /**
     * Execute an UPDATE query
     *
     * @param string $table
     * @param array $data
     * @param array $where
     * @param bool $upsert
     * @return int Number of affected rows
     */
    /**
     * Execute an UPDATE query with permission checks
     *
     * @param string $table
     * @param array $data
     * @param array $where
     * @param bool $upsert
     * @param \App\Models\User|null $user The authenticated user
     * @return int Number of affected rows
     */
    public function update(string $table, array $data, array $where = [], bool $upsert = false, ?\App\Models\User $user = null)
    {
        // Check if operation is allowed
        if (!$this->isOperationAllowed('update')) {
            throw new InvalidArgumentException('UPDATE operation is not allowed');
        }
        
        // Check if table is allowed
        $this->validateTable($table);
        
        // Apply column restrictions if user is provided and not an admin
        if ($user && !$user->isAdmin()) {
            $permission = $user->getPermissionForTable($table);
            if ($permission) {
                $data = $this->filterColumns($data, $permission);
                
                // If no columns are allowed to be updated, throw an exception
                if (empty($data)) {
                    throw new InvalidArgumentException('No columns are allowed for this operation');
                }
            }
        }
        
        $query = DB::table($table);

        // Apply where conditions
        $query = $this->applyWhereConditions($query, $where);

        // Check if record exists for upsert
        if ($upsert && count($where) > 0) {
            $exists = (clone $query)->exists();
            if (!$exists) {
                // Insert if not exists
                return DB::table($table)->insertGetId($data);
            }
        }

        // Update the record(s)
        return $query->update($data);
    }

    /**
     * Execute a DELETE query with permission checks
     *
     * @param string $table
     * @param array $where
     * @param \App\Models\User|null $user The authenticated user
     * @return int Number of affected rows
     */
    public function delete(string $table, array $where = [], ?\App\Models\User $user = null)
    {
        // Check if operation is allowed
        if (!$this->isOperationAllowed('delete')) {
            throw new InvalidArgumentException('DELETE operation is not allowed');
        }
        
        // Check if table is allowed
        $this->validateTable($table);
        
        // Require where conditions for safety
        if (empty($where)) {
            throw new InvalidArgumentException('DELETE operations require where conditions');
        }
        
        $query = DB::table($table);

        // Apply where conditions
        $query = $this->applyWhereConditions($query, $where);

        // Delete the record(s)
        return $query->delete();
    }

    /**
     * Apply where conditions to a query
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $where
     * @return \Illuminate\Database\Query\Builder
     */
    /**
     * Check if a database operation is allowed
     *
     * @param string $operation
     * @return bool
     */
    private function isOperationAllowed(string $operation): bool
    {
        return Config::get("dbaas.allowed_operations.{$operation}", false);
    }
    
    /**
     * Validate if a table is allowed to be accessed
     *
     * @param string $table
     * @return bool
     * @throws InvalidArgumentException
     */
    private function validateTable(string $table): bool
    {
        // Check if table is in restricted list
        $restrictedTables = Config::get('dbaas.restricted_tables', []);
        if (in_array($table, $restrictedTables)) {
            throw new InvalidArgumentException("Access to table '{$table}' is restricted");
        }
        
        // Check if table is in allowed list (if specified)
        $allowedTables = Config::get('dbaas.allowed_tables', []);
        if (!empty($allowedTables) && !in_array($table, $allowedTables)) {
            throw new InvalidArgumentException("Access to table '{$table}' is not allowed");
        }
        
        return true;
    }
    
    /**
     * Get all columns for a table
     *
     * @param string $table
     * @return array
     */
    private function getTableColumns(string $table): array
    {
        return DB::getSchemaBuilder()->getColumnListing($table);
    }
    
    /**
     * Filter data array based on column permissions
     *
     * @param array $data
     * @param \App\Models\Permission $permission
     * @return array
     */
    private function filterColumns(array $data, \App\Models\Permission $permission): array
    {
        return array_filter($data, function($value, $column) use ($permission) {
            return $permission->isColumnAllowed($column);
        }, ARRAY_FILTER_USE_BOTH);
    }
    
    private function applyWhereConditions($query, array $where)
    {
        foreach ($where as $condition) {
            if (isset($condition['column']) && isset($condition['operator']) && isset($condition['value'])) {
                $query->where($condition['column'], $condition['operator'], $condition['value']);
            } elseif (isset($condition['raw'])) {
                $query->whereRaw($condition['raw']);
            } elseif (isset($condition['or']) && is_array($condition['or'])) {
                $query->orWhere(function($q) use ($condition) {
                    foreach ($condition['or'] as $orCondition) {
                        if (isset($orCondition['column']) && isset($orCondition['operator']) && isset($orCondition['value'])) {
                            $q->orWhere($orCondition['column'], $orCondition['operator'], $orCondition['value']);
                        }
                    }
                });
            } elseif (isset($condition['and']) && is_array($condition['and'])) {
                $query->where(function($q) use ($condition) {
                    foreach ($condition['and'] as $andCondition) {
                        if (isset($andCondition['column']) && isset($andCondition['operator']) && isset($andCondition['value'])) {
                            $q->where($andCondition['column'], $andCondition['operator'], $andCondition['value']);
                        }
                    }
                });
            }
        }

        return $query;
    }
}
