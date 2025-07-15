<?php
// app/Http/Controllers/Api/Admin/UserController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['roles:id,name,display_name']);

        // Filter by active status
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Filter by role
        if ($request->has('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->get();

        return response()->json([
            'data' => $users,
            'message' => 'Users retrieved successfully'
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,name',
            'is_active' => 'boolean',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Assign roles if provided
        if ($request->has('roles')) {
            foreach ($request->roles as $roleName) {
                $user->assignRole($roleName, auth()->id());
            }
        }

        $user->load('roles:id,name,display_name');

        return response()->json([
            'data' => $user,
            'message' => 'User created successfully'
        ], 201);
    }

    public function show(User $user)
    {
        $user->load([
            'roles:id,name,display_name,description',
            'roles.permissions:id,name,display_name,module,action'
        ]);

        // Get user permissions (flattened from roles)
        $permissions = $user->permissions()->get(['id', 'name', 'display_name', 'module', 'action']);

        return response()->json([
            'data' => array_merge($user->toArray(), [
                'permissions' => $permissions->groupBy('module')
            ]),
            'message' => 'User retrieved successfully'
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,name',
            'is_active' => 'boolean',
        ]);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        // Update roles if provided
        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        }

        $user->load('roles:id,name,display_name');

        return response()->json([
            'data' => $user,
            'message' => 'User updated successfully'
        ]);
    }

    public function destroy(User $user)
    {
        // Prevent deletion of super admin
        if ($user->isSuperAdmin() && User::whereHas('roles', function ($q) {
            $q->where('name', 'super_admin');
        })->count() <= 1) {
            return response()->json([
                'message' => 'Cannot delete the last super admin user',
                'error' => 'At least one super admin must exist in the system'
            ], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    public function assignRoles(Request $request, User $user)
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
        ]);

        foreach ($request->roles as $roleName) {
            $user->assignRole($roleName, auth()->id());
        }

        $user->load('roles:id,name,display_name');

        return response()->json([
            'data' => $user,
            'message' => 'Roles assigned successfully'
        ]);
    }

    public function removeRoles(Request $request, User $user)
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
        ]);

        // Prevent removal of super admin role from last super admin
        if (
            in_array('super_admin', $request->roles) &&
            $user->isSuperAdmin() &&
            User::whereHas('roles', function ($q) {
                $q->where('name', 'super_admin');
            })->count() <= 1
        ) {
            return response()->json([
                'message' => 'Cannot remove super admin role from the last super admin',
                'error' => 'At least one super admin must exist in the system'
            ], 422);
        }

        foreach ($request->roles as $roleName) {
            $user->removeRole($roleName);
        }

        $user->load('roles:id,name,display_name');

        return response()->json([
            'data' => $user,
            'message' => 'Roles removed successfully'
        ]);
    }

    public function syncRoles(Request $request, User $user)
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
        ]);

        // Check if trying to remove super admin role from last super admin
        if (
            $user->isSuperAdmin() &&
            !in_array('super_admin', $request->roles) &&
            User::whereHas('roles', function ($q) {
                $q->where('name', 'super_admin');
            })->count() <= 1
        ) {
            return response()->json([
                'message' => 'Cannot remove super admin role from the last super admin',
                'error' => 'At least one super admin must exist in the system'
            ], 422);
        }

        $user->syncRoles($request->roles);
        $user->load('roles:id,name,display_name');

        return response()->json([
            'data' => $user,
            'message' => 'User roles synchronized successfully'
        ]);
    }

    public function getUserRoles(User $user)
    {
        $roles = $user->roles()->select('id', 'name', 'display_name', 'description')->get();

        return response()->json([
            'data' => $roles,
            'message' => 'User roles retrieved successfully'
        ]);
    }

    public function getUserPermissions(User $user)
    {
        $permissions = $user->permissions()
            ->select('id', 'name', 'display_name', 'module', 'action', 'description')
            ->get()
            ->groupBy('module');

        return response()->json([
            'data' => $permissions,
            'message' => 'User permissions retrieved successfully'
        ]);
    }

    public function toggleStatus(User $user)
    {
        // Prevent deactivation of last super admin
        if (
            $user->isSuperAdmin() &&
            $user->is_active &&
            User::whereHas('roles', function ($q) {
                $q->where('name', 'super_admin');
            })->where('is_active', true)->count() <= 1
        ) {
            return response()->json([
                'message' => 'Cannot deactivate the last active super admin',
                'error' => 'At least one active super admin must exist in the system'
            ], 422);
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'data' => $user,
            'message' => $user->is_active ? 'User activated successfully' : 'User deactivated successfully'
        ]);
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'message' => 'Password reset successfully'
        ]);
    }

    public function getProfile(Request $request)
    {
        $user = $request->user();
        $user->load([
            'roles:id,name,display_name',
            'roles.permissions:id,name,display_name,module,action'
        ]);

        // Get user permissions grouped by module
        $permissions = $user->permissions()
            ->select('id', 'name', 'display_name', 'module', 'action')
            ->get()
            ->groupBy('module');

        return response()->json([
            'data' => array_merge($user->toArray(), [
                'permissions' => $permissions
            ]),
            'message' => 'User profile retrieved successfully'
        ]);
    }
}
