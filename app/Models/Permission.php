<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Permission extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'table_name',
        'can_select',
        'can_insert',
        'can_update',
        'can_delete',
        'where_conditions',
        'column_restrictions',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'can_select' => 'boolean',
        'can_insert' => 'boolean',
        'can_update' => 'boolean',
        'can_delete' => 'boolean',
        'where_conditions' => 'json',
        'column_restrictions' => 'json',
    ];

    /**
     * Get the user that owns the permission.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the permission allows a specific operation
     *
     * @param string $operation One of: select, insert, update, delete
     * @return bool
     */
    public function allows(string $operation): bool
    {
        $permissionField = 'can_' . $operation;
        return isset($this->$permissionField) && $this->$permissionField === true;
    }

    /**
     * Get the where conditions for this permission.
     *
     * @return array
     */
    public function getWhereConditions(): array
    {
        return $this->where_conditions ?? [];
    }

    /**
     * Get the column restrictions for this permission.
     *
     * @return array
     */
    public function getColumnRestrictions(): array
    {
        return $this->column_restrictions ?? [];
    }

    /**
     * Check if a column is allowed for this permission.
     *
     * @param string $column
     * @return bool
     */
    public function isColumnAllowed(string $column): bool
    {
        $restrictions = $this->getColumnRestrictions();
        
        // If no restrictions are defined, all columns are allowed
        if (empty($restrictions['allowed']) && empty($restrictions['denied'])) {
            return true;
        }
        
        // If allowed columns are defined, check if the column is in the list
        if (!empty($restrictions['allowed'])) {
            return in_array($column, $restrictions['allowed']);
        }
        
        // If denied columns are defined, check if the column is not in the list
        if (!empty($restrictions['denied'])) {
            return !in_array($column, $restrictions['denied']);
        }
        
        return true;
    }
}
