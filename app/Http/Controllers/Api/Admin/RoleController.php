<?php
// app/Http/Controllers/Api/Admin/RoleController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $query = Role::with(['permissions:id,name,display_name,module,action']);

        // Filter by active status
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Search by name or display name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('display_name', 'like', "%{$search}%");
            });
        }

        $roles = $query->get();

        return response()->json([
            'data' => $roles,
            'message' => 'Roles retrieved successfully'
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'is_active' => 'boolean',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Assign permissions if provided
        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        $role->load('permissions:id,name,display_name,module,action');

        return response()->json([
            'data' => $role,
            'message' => 'Role created successfully'
        ], 201);
    }

    public function show(Role $role)
    {
        $role->load([
            'permissions:id,name,display_name,module,action',
            'users:id,name,email'
        ]);

        return response()->json([
            'data' => $role,
            'message' => 'Role retrieved successfully'
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->ignore($role->id)],
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'is_active' => 'boolean',
        ]);

        $role->update([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active'),
        ]);

        // Update permissions if provided
        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        $role->load('permissions:id,name,display_name,module,action');

        return response()->json([
            'data' => $role,
            'message' => 'Role updated successfully'
        ]);
    }

    public function destroy(Role $role)
    {
        // Check if role is assigned to any users
        if ($role->users()->exists()) {
            return response()->json([
                'message' => 'Cannot delete role that is assigned to users',
                'error' => 'Role is currently assigned to one or more users'
            ], 422);
        }

        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully'
        ]);
    }

    public function assignPermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->permissions()->sync($request->permissions);
        $role->load('permissions:id,name,display_name,module,action');

        return response()->json([
            'data' => $role,
            'message' => 'Permissions assigned successfully'
        ]);
    }

    public function removePermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->permissions()->detach($request->permissions);
        $role->load('permissions:id,name,display_name,module,action');

        return response()->json([
            'data' => $role,
            'message' => 'Permissions removed successfully'
        ]);
    }

    public function getPermissions(Role $role)
    {
        $permissions = $role->permissions()
            ->select('id', 'name', 'display_name', 'module', 'action', 'description')
            ->get()
            ->groupBy('module');

        return response()->json([
            'data' => $permissions,
            'message' => 'Role permissions retrieved successfully'
        ]);
    }

    public function getAvailablePermissions(Role $role)
    {
        $assignedPermissionIds = $role->permissions()->pluck('permissions.id');

        $availablePermissions = Permission::whereNotIn('id', $assignedPermissionIds)
            ->select('id', 'name', 'display_name', 'module', 'action', 'description')
            ->get()
            ->groupBy('module');

        return response()->json([
            'data' => $availablePermissions,
            'message' => 'Available permissions retrieved successfully'
        ]);
    }

    public function duplicate(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $newRole = Role::create([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
            'is_active' => true,
        ]);

        // Copy permissions from original role
        $permissions = $role->permissions()->pluck('permissions.id');
        $newRole->permissions()->sync($permissions);

        $newRole->load('permissions:id,name,display_name,module,action');

        return response()->json([
            'data' => $newRole,
            'message' => 'Role duplicated successfully'
        ], 201);
    }
}
