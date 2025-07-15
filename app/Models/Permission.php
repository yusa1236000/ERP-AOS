<?php
// app/Models/Permission.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'module',
        'action',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permission')
            ->withTimestamps();
    }

    // Scope for active permissions
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope by module
    public function scopeByModule($query, $module)
    {
        return $query->where('module', $module);
    }

    // Scope by action
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    // Get all modules
    public static function getModules()
    {
        return self::distinct()->pluck('module')->sort()->values();
    }

    // Get all actions
    public static function getActions()
    {
        return self::distinct()->pluck('action')->sort()->values();
    }

    // Get permissions by module
    public static function getByModule($module)
    {
        return self::byModule($module)->get();
    }

    // Create permission name from module and action
    public static function createName($module, $action)
    {
        return "{$module}.{$action}";
    }

    // Parse permission name to get module and action
    public function parsePermissionName()
    {
        $parts = explode('.', $this->name);
        return [
            'module' => $parts[0] ?? null,
            'action' => $parts[1] ?? null
        ];
    }
}
