<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'api_key',
        'api_key_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'api_key',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'api_key_expires_at' => 'datetime',
        ];
    }
    
    /**
     * Generate a new API key for the user
     *
     * @param int $expiresInDays Number of days until the API key expires
     * @return string The generated API key
     */
    public function generateApiKey(int $expiresInDays = 30): string
    {
        $apiKey = Str::random(64);
        
        $this->api_key = $apiKey;
        $this->api_key_expires_at = now()->addDays($expiresInDays);
        $this->save();
        
        return $apiKey;
    }
    
    /**
     * Check if the user's API key is valid
     *
     * @return bool
     */
    public function hasValidApiKey(): bool
    {
        return $this->api_key && $this->api_key_expires_at && $this->api_key_expires_at->isFuture();
    }
    
    /**
     * Check if the user has the given role
     *
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }
    
    /**
     * Check if the user is an admin
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }
    
    /**
     * Get the permissions for the user.
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }
    
    /**
     * Check if the user has permission to perform an operation on a table.
     *
     * @param string $operation One of: select, insert, update, delete
     * @param string $table The table name
     * @return bool
     */
    public function hasPermission(string $operation, string $table): bool
    {
        // Admins have all permissions
        if ($this->isAdmin()) {
            return true;
        }
        
        $permission = $this->permissions()->where('table_name', $table)->first();
        
        if (!$permission) {
            return false;
        }
        
        return $permission->allows($operation);
    }
    
    /**
     * Get the permission for a specific table.
     *
     * @param string $table
     * @return Permission|null
     */
    public function getPermissionForTable(string $table)
    {
        return $this->permissions()->where('table_name', $table)->first();
    }
}
