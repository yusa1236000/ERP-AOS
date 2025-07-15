<?php
// app/Http/Controllers/Api/Admin/PermissionController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Permission::query();

        // Filter by module
        if ($request->has('module')) {
            $query->where('module', $request->module);
        }

        // Filter by action
        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

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

        $permissions = $query->get();

        // Group by module if requested
        if ($request->boolean('group_by_module')) {
            $permissions = $permissions->groupBy('module');
        }

        return response()->json([
            'data' => $permissions,
            'message' => 'Permissions retrieved successfully'
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'display_name' => 'required|string|max:255',
            'module' => 'required|string|max:255',
            'action' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $permission = Permission::create([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'module' => $request->module,
            'action' => $request->action,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'data' => $permission,
            'message' => 'Permission created successfully'
        ], 201);
    }

    public function show(Permission $permission)
    {
        $permission->load('roles:id,name,display_name');

        return response()->json([
            'data' => $permission,
            'message' => 'Permission retrieved successfully'
        ]);
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions')->ignore($permission->id)],
            'display_name' => 'required|string|max:255',
            'module' => 'required|string|max:255',
            'action' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $permission->update([
            'name' => $request->name,
            'display_name' => $request->display_name,
            'module' => $request->module,
            'action' => $request->action,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active'),
        ]);

        return response()->json([
            'data' => $permission,
            'message' => 'Permission updated successfully'
        ]);
    }

    public function destroy(Permission $permission)
    {
        // Check if permission is assigned to any roles
        if ($permission->roles()->exists()) {
            return response()->json([
                'message' => 'Cannot delete permission that is assigned to roles',
                'error' => 'Permission is currently assigned to one or more roles'
            ], 422);
        }

        $permission->delete();

        return response()->json([
            'message' => 'Permission deleted successfully'
        ]);
    }

    public function getModules()
    {
        $modules = Permission::getModules();

        return response()->json([
            'data' => $modules,
            'message' => 'Modules retrieved successfully'
        ]);
    }

    public function getActions()
    {
        $actions = Permission::getActions();

        return response()->json([
            'data' => $actions,
            'message' => 'Actions retrieved successfully'
        ]);
    }

    public function getByModule(Request $request, $module)
    {
        $permissions = Permission::byModule($module)->get();

        return response()->json([
            'data' => $permissions,
            'message' => "Permissions for {$module} module retrieved successfully"
        ]);
    }

    public function bulkCreate(Request $request)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*.name' => 'required|string|max:255|unique:permissions,name',
            'permissions.*.display_name' => 'required|string|max:255',
            'permissions.*.module' => 'required|string|max:255',
            'permissions.*.action' => 'required|string|max:255',
            'permissions.*.description' => 'nullable|string',
        ]);

        $permissions = [];
        foreach ($request->permissions as $permissionData) {
            $permissions[] = Permission::create([
                'name' => $permissionData['name'],
                'display_name' => $permissionData['display_name'],
                'module' => $permissionData['module'],
                'action' => $permissionData['action'],
                'description' => $permissionData['description'] ?? null,
                'is_active' => true,
            ]);
        }

        return response()->json([
            'data' => $permissions,
            'message' => 'Permissions created successfully'
        ], 201);
    }

    public function generateModulePermissions(Request $request)
    {
        $request->validate([
            'module' => 'required|string|max:255',
            'actions' => 'required|array',
            'actions.*' => 'required|string|in:create,read,update,delete,approve,export,import',
        ]);

        $module = $request->module;
        $actions = $request->actions;
        $permissions = [];

        foreach ($actions as $action) {
            $name = "{$module}.{$action}";

            // Check if permission already exists
            if (Permission::where('name', $name)->exists()) {
                continue;
            }

            $displayName = ucwords(str_replace('_', ' ', $action)) . " " . ucfirst($module);

            $permissions[] = Permission::create([
                'name' => $name,
                'display_name' => $displayName,
                'module' => $module,
                'action' => $action,
                'description' => "Permission to {$action} in {$module} module",
                'is_active' => true,
            ]);
        }

        return response()->json([
            'data' => $permissions,
            'message' => "Permissions for {$module} module generated successfully"
        ], 201);
    }
}
