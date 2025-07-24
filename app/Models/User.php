<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    // Role relationships
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role')
            ->withPivot('assigned_at', 'assigned_by')
            ->withTimestamps();
    }

    public function permissions()
    {
        return $this->hasManyThrough(
            Permission::class,
            Role::class,
            'id', // Foreign key on roles table
            'id', // Foreign key on permissions table
            'id', // Local key on users table
            'id'  // Local key on roles table
        )->join('role_permission', 'permissions.id', '=', 'role_permission.permission_id')
            ->join('user_role', 'roles.id', '=', 'user_role.role_id')
            ->where('user_role.user_id', $this->id)
            ->select('permissions.*'); // pastikan select dari permissions saja untuk menghindari ambiguitas
    }

    // Role checking methods
    public function hasRole($roleName)
    {
        if (is_array($roleName)) {
            return $this->roles()->whereIn('name', $roleName)->exists();
        }
        return $this->roles()->where('name', $roleName)->exists();
    }

    public function hasAnyRole($roles)
    {
        return $this->hasRole($roles);
    }

    public function hasAllRoles($roles)
    {
        if (!is_array($roles)) {
            $roles = [$roles];
        }

        $userRoles = $this->roles()->pluck('name')->toArray();
        return empty(array_diff($roles, $userRoles));
    }

    // Permission checking methods
    public function hasPermission($permissionName)
    {
        if (is_array($permissionName)) {
            return $this->permissions()->whereIn('permissions.name', $permissionName)->exists();
        }
        return $this->permissions()->where('permissions.name', $permissionName)->exists();
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

        $userPermissions = $this->permissions()->pluck('name')->toArray();
        return empty(array_diff($permissions, $userPermissions));
    }

    // Module-specific permission checking
    public function canAccess($module, $action = 'read')
    {
        $permissionName = "{$module}.{$action}";
        return $this->hasPermission($permissionName);
    }

    public function canCreate($module)
    {
        return $this->canAccess($module, 'create');
    }

    public function canRead($module)
    {
        return $this->canAccess($module, 'read');
    }

    public function canUpdate($module)
    {
        return $this->canAccess($module, 'update');
    }

    public function canDelete($module)
    {
        return $this->canAccess($module, 'delete');
    }

    public function canApprove($module)
    {
        return $this->canAccess($module, 'approve');
    }

    // Role assignment methods
    public function assignRole($roleName, $assignedBy = null)
    {
        $role = Role::where('name', $roleName)->first();
        if ($role && !$this->hasRole($roleName)) {
            $this->roles()->attach($role->id, [
                'assigned_by' => $assignedBy,
                'assigned_at' => now()
            ]);
        }
        return $this;
    }

    public function removeRole($roleName)
    {
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $this->roles()->detach($role->id);
        }
        return $this;
    }

    public function syncRoles($roleNames)
    {
        $roles = Role::whereIn('name', $roleNames)->pluck('id')->toArray();
        $this->roles()->sync($roles);
        return $this;
    }

    // Check if user is super admin
    public function isSuperAdmin()
    {
        return $this->hasRole('super_admin');
    }

    // Check if user is admin
    public function isAdmin()
    {
        return $this->hasRole(['super_admin', 'admin']);
    }

    // Get user's role names
    public function getRoleNames()
    {
        return $this->roles()->pluck('name')->toArray();
    }

    // Get user's permission names
    public function getPermissionNames()
    {
        return $this->permissions()->pluck('name')->toArray();
    }
}
