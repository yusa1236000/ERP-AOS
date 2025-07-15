<?php
// app/Models/Role.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_role')
            ->withPivot('assigned_at', 'assigned_by')
            ->withTimestamps();
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission')
            ->withTimestamps();
    }

    // Permission management methods
    public function givePermissionTo($permissionName)
    {
        $permission = Permission::where('name', $permissionName)->first();
        if ($permission && !$this->hasPermission($permissionName)) {
            $this->permissions()->attach($permission->id);
        }
        return $this;
    }

    public function revokePermissionTo($permissionName)
    {
        $permission = Permission::where('name', $permissionName)->first();
        if ($permission) {
            $this->permissions()->detach($permission->id);
        }
        return $this;
    }

    public function syncPermissions($permissionNames)
    {
        $permissions = Permission::whereIn('name', $permissionNames)->pluck('id')->toArray();
        $this->permissions()->sync($permissions);
        return $this;
    }

    public function hasPermission($permissionName)
    {
        if (is_array($permissionName)) {
            return $this->permissions()->whereIn('name', $permissionName)->exists();
        }
        return $this->permissions()->where('name', $permissionName)->exists();
    }

    public function hasAnyPermission($permissions)
    {
        return $this->hasPermission($permissions);
    }

    public function hasAllPermissions($permissions)
    {
        if (!is_array($permissions)) {
            $permissions = [$permissions];
        }

        $rolePermissions = $this->permissions()->pluck('name')->toArray();
        return empty(array_diff($permissions, $rolePermissions));
    }

    // Get permission names
    public function getPermissionNames()
    {
        return $this->permissions()->pluck('name')->toArray();
    }

    // Scope for active roles
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Check if role can access module
    public function canAccess($module, $action = 'read')
    {
        $permissionName = "{$module}.{$action}";
        return $this->hasPermission($permissionName);
    }
}
