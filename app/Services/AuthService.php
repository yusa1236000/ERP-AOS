<?php
// app/Services/AuthService.php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AuthService
{
    /**
     * Get current authenticated user
     */
    public function user(): ?User
    {
        return Auth::user();
    }

    /**
     * Check if user is authenticated
     */
    public function check(): bool
    {
        return Auth::check();
    }

    /**
     * Check if current user has role
     */
    public function hasRole($roles): bool
    {
        if (!$this->check()) {
            return false;
        }

        return $this->user()->hasRole($roles);
    }

    /**
     * Check if current user has permission
     */
    public function hasPermission($permissions): bool
    {
        if (!$this->check()) {
            return false;
        }

        return $this->user()->hasPermission($permissions);
    }

    /**
     * Check if current user can access module
     */
    public function canAccess($module, $action = 'read'): bool
    {
        if (!$this->check()) {
            return false;
        }

        return $this->user()->canAccess($module, $action);
    }

    /**
     * Get user permissions grouped by module
     */
    public function getUserPermissions(): array
    {
        if (!$this->check()) {
            return [];
        }

        $cacheKey = "user_permissions_{$this->user()->id}";

        return Cache::remember($cacheKey, config('permissions.cache.expiration_time', 3600), function () {
            return $this->user()->permissions()
                ->select('id', 'name', 'display_name', 'module', 'action')
                ->get()
                ->groupBy('module')
                ->toArray();
        });
    }

    /**
     * Get user roles
     */
    public function getUserRoles(): array
    {
        if (!$this->check()) {
            return [];
        }

        $cacheKey = "user_roles_{$this->user()->id}";

        return Cache::remember($cacheKey, config('permissions.cache.expiration_time', 3600), function () {
            return $this->user()->roles()
                ->select('id', 'name', 'display_name')
                ->get()
                ->toArray();
        });
    }

    /**
     * Get user's accessible modules
     */
    public function getAccessibleModules(): array
    {
        if (!$this->check()) {
            return [];
        }

        $permissions = $this->getUserPermissions();
        $modules = config('permissions.modules', []);
        $accessibleModules = [];

        foreach ($modules as $moduleKey => $moduleConfig) {
            if (isset($permissions[$moduleKey]) && !empty($permissions[$moduleKey])) {
                $accessibleModules[$moduleKey] = array_merge($moduleConfig, [
                    'permissions' => $permissions[$moduleKey]
                ]);
            }
        }

        return $accessibleModules;
    }

    /**
     * Check if user can perform action on module
     */
    public function can($module, $action): bool
    {
        return $this->canAccess($module, $action);
    }

    /**
     * Check multiple permissions (OR logic)
     */
    public function hasAnyPermission(array $permissions): bool
    {
        if (!$this->check()) {
            return false;
        }

        return $this->user()->hasAnyPermission($permissions);
    }

    /**
     * Check multiple permissions (AND logic)
     */
    public function hasAllPermissions(array $permissions): bool
    {
        if (!$this->check()) {
            return false;
        }

        return $this->user()->hasAllPermissions($permissions);
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        if (!$this->check()) {
            return false;
        }

        return $this->user()->isSuperAdmin();
    }

    /**
     * Check if user is admin (admin or super admin)
     */
    public function isAdmin(): bool
    {
        if (!$this->check()) {
            return false;
        }

        return $this->user()->isAdmin();
    }

    /**
     * Get navigation menu items based on user permissions
     */
    public function getNavigationMenu(): array
    {
        if (!$this->check()) {
            return [];
        }

        $accessibleModules = $this->getAccessibleModules();
        $navigation = [];

        // Dashboard - always accessible for authenticated users
        $navigation[] = [
            'name' => 'Dashboard',
            'route' => 'dashboard',
            'icon' => 'home',
            'order' => 1
        ];

        // Module-based navigation
        $moduleOrder = [
            'inventory' => 10,
            'manufacturing' => 20,
            'sales' => 30,
            'procurement' => 40,
            'accounting' => 50,
            'admin' => 90
        ];

        foreach ($accessibleModules as $moduleKey => $module) {
            $navigation[] = [
                'name' => $module['display_name'],
                'route' => $moduleKey,
                'icon' => $module['icon'] ?? 'folder',
                'color' => $module['color'] ?? '#6B7280',
                'order' => $moduleOrder[$moduleKey] ?? 99,
                'children' => $this->getModuleSubMenu($moduleKey, $module['permissions'])
            ];
        }

        // Sort by order
        usort($navigation, function ($a, $b) {
            return $a['order'] <=> $b['order'];
        });

        return $navigation;
    }

    /**
     * Get sub-menu items for a module
     */
    private function getModuleSubMenu($module, $permissions): array
    {
        $subMenu = [];
        $hasRead = collect($permissions)->contains('action', 'read');
        $hasCreate = collect($permissions)->contains('action', 'create');

        if ($hasRead) {
            $subMenu[] = [
                'name' => 'View ' . ucfirst($module),
                'route' => "{$module}.index",
                'icon' => 'list'
            ];
        }

        if ($hasCreate) {
            $subMenu[] = [
                'name' => 'Create New',
                'route' => "{$module}.create",
                'icon' => 'plus'
            ];
        }

        // Module-specific menu items
        switch ($module) {
            case 'inventory':
                if ($this->can('inventory', 'read')) {
                    $subMenu = array_merge($subMenu, [
                        ['name' => 'Items', 'route' => 'inventory.items', 'icon' => 'package'],
                        ['name' => 'Warehouses', 'route' => 'inventory.warehouses', 'icon' => 'home'],
                        ['name' => 'Stock Levels', 'route' => 'inventory.stock', 'icon' => 'bar-chart'],
                    ]);
                }
                if ($this->can('inventory', 'transfer')) {
                    $subMenu[] = ['name' => 'Stock Transfers', 'route' => 'inventory.transfers', 'icon' => 'arrow-right'];
                }
                if ($this->can('inventory', 'adjust')) {
                    $subMenu[] = ['name' => 'Stock Adjustments', 'route' => 'inventory.adjustments', 'icon' => 'edit'];
                }
                break;

            case 'manufacturing':
                if ($this->can('manufacturing', 'read')) {
                    $subMenu = array_merge($subMenu, [
                        ['name' => 'Production Orders', 'route' => 'manufacturing.production-orders', 'icon' => 'settings'],
                        ['name' => 'Work Orders', 'route' => 'manufacturing.work-orders', 'icon' => 'tool'],
                        ['name' => 'BOMs', 'route' => 'manufacturing.boms', 'icon' => 'file-text'],
                    ]);
                }
                if ($this->can('manufacturing', 'quality_control')) {
                    $subMenu[] = ['name' => 'Quality Control', 'route' => 'manufacturing.quality', 'icon' => 'award'];
                }
                break;

            case 'admin':
                if ($this->hasPermission('admin.user_management')) {
                    $subMenu[] = ['name' => 'Users', 'route' => 'admin.users', 'icon' => 'users'];
                }
                if ($this->hasPermission('admin.role_management')) {
                    $subMenu = array_merge($subMenu, [
                        ['name' => 'Roles', 'route' => 'admin.roles', 'icon' => 'shield'],
                        ['name' => 'Permissions', 'route' => 'admin.permissions', 'icon' => 'key'],
                    ]);
                }
                if ($this->hasPermission('admin.system_settings')) {
                    $subMenu[] = ['name' => 'System Settings', 'route' => 'admin.settings', 'icon' => 'settings'];
                }
                break;
        }

        return $subMenu;
    }

    /**
     * Clear user permission cache
     */
    public function clearUserCache($userId = null): void
    {
        $userId = $userId ?? $this->user()?->id;

        if ($userId) {
            Cache::forget("user_permissions_{$userId}");
            Cache::forget("user_roles_{$userId}");
        }
    }

    /**
     * Get user's dashboard widgets based on permissions
     */
    public function getDashboardWidgets(): array
    {
        if (!$this->check()) {
            return [];
        }

        $widgets = [];

        // Inventory widgets
        if ($this->can('inventory', 'read')) {
            $widgets[] = [
                'name' => 'Stock Levels',
                'component' => 'StockLevelsWidget',
                'permissions' => ['inventory.read'],
                'size' => 'medium',
                'order' => 1
            ];

            $widgets[] = [
                'name' => 'Low Stock Alerts',
                'component' => 'LowStockWidget',
                'permissions' => ['inventory.read'],
                'size' => 'small',
                'order' => 2
            ];
        }

        // Manufacturing widgets
        if ($this->can('manufacturing', 'read')) {
            $widgets[] = [
                'name' => 'Production Status',
                'component' => 'ProductionStatusWidget',
                'permissions' => ['manufacturing.read'],
                'size' => 'large',
                'order' => 3
            ];

            $widgets[] = [
                'name' => 'Work Orders',
                'component' => 'WorkOrdersWidget',
                'permissions' => ['manufacturing.read'],
                'size' => 'medium',
                'order' => 4
            ];
        }

        // Admin widgets
        if ($this->isAdmin()) {
            $widgets[] = [
                'name' => 'System Overview',
                'component' => 'SystemOverviewWidget',
                'permissions' => ['admin.read'],
                'size' => 'large',
                'order' => 10
            ];
        }

        // Sort by order
        usort($widgets, function ($a, $b) {
            return $a['order'] <=> $b['order'];
        });

        return $widgets;
    }

    /**
     * Generate permission summary for user
     */
    public function getPermissionSummary(): array
    {
        if (!$this->check()) {
            return [];
        }

        $permissions = $this->getUserPermissions();
        $summary = [];

        foreach ($permissions as $module => $modulePermissions) {
            $actions = collect($modulePermissions)->pluck('action')->unique()->values()->toArray();
            $summary[$module] = [
                'module_name' => config("permissions.modules.{$module}.display_name", ucfirst($module)),
                'permissions_count' => count($modulePermissions),
                'actions' => $actions,
                'can_create' => in_array('create', $actions),
                'can_read' => in_array('read', $actions),
                'can_update' => in_array('update', $actions),
                'can_delete' => in_array('delete', $actions),
                'can_approve' => in_array('approve', $actions),
            ];
        }

        return $summary;
    }
}
