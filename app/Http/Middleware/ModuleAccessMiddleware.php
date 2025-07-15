<?php
// app/Http/Middleware/ModuleAccessMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ModuleAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $module
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $module = null)
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Unauthenticated',
                'error' => 'You must be logged in to access this resource'
            ], 401);
        }

        $user = Auth::user();

        // Super admin bypasses all checks
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Determine module and action from route if not provided
        if (!$module) {
            $module = $this->getModuleFromRoute($request);
        }

        if (!$module) {
            return response()->json([
                'message' => 'Forbidden',
                'error' => 'Unable to determine module access requirements'
            ], 403);
        }

        $action = $this->getActionFromMethod($request->method());
        $permission = "{$module}.{$action}";

        // Check if user has the required permission
        if (!$user->hasPermission($permission)) {
            return response()->json([
                'message' => 'Forbidden',
                'error' => "You do not have permission to {$action} in {$module} module",
                'required_permission' => $permission,
                'user_permissions' => $user->getPermissionNames()
            ], 403);
        }

        return $next($request);
    }

    /**
     * Determine module from route
     */
    private function getModuleFromRoute(Request $request)
    {
        $path = $request->path();

        // Module mapping based on your API routes
        $moduleMap = [
            // Inventory
            'items' => 'inventory',
            'item-categories' => 'inventory',
            'unit-of-measures' => 'inventory',
            'warehouses' => 'inventory',
            'stock-transactions' => 'inventory',
            'stock-adjustments' => 'inventory',
            'item-stocks' => 'inventory',

            // Manufacturing
            'products' => 'manufacturing',
            'boms' => 'manufacturing',
            'routings' => 'manufacturing',
            'work-centers' => 'manufacturing',
            'work-orders' => 'manufacturing',
            'production-orders' => 'manufacturing',
            'quality-inspections' => 'manufacturing',
            'maintenance-schedules' => 'manufacturing',
            'material-planning' => 'manufacturing',

            // Sales
            'customers' => 'sales',
            'sales-orders' => 'sales',
            'sales-returns' => 'sales',
            'customer-interactions' => 'sales',
            'sales-commissions' => 'sales',
            'sales-forecasts' => 'sales',

            // Procurement
            'vendors' => 'procurement',
            'purchase-orders' => 'procurement',
            'purchase-receipts' => 'procurement',
            'purchase-returns' => 'procurement',

            // Accounting
            'chart-of-accounts' => 'accounting',
            'journal-entries' => 'accounting',
            'accounting-periods' => 'accounting',
            'customer-receivables' => 'accounting',
            'vendor-payables' => 'accounting',
            'fixed-assets' => 'accounting',
            'budgets' => 'accounting',
            'bank-accounts' => 'accounting',

            // Admin
            'users' => 'admin',
            'roles' => 'admin',
            'permissions' => 'admin',
            'settings' => 'admin',
        ];

        // Extract the first segment of the path (after api/)
        $segments = explode('/', trim($path, '/'));
        if (count($segments) >= 2 && $segments[0] === 'api') {
            $routeSegment = $segments[1];
            return $moduleMap[$routeSegment] ?? null;
        }

        return null;
    }

    /**
     * Determine action from HTTP method
     */
    private function getActionFromMethod($method)
    {
        switch (strtoupper($method)) {
            case 'GET':
                return 'read';
            case 'POST':
                return 'create';
            case 'PUT':
            case 'PATCH':
                return 'update';
            case 'DELETE':
                return 'delete';
            default:
                return 'read';
        }
    }
}
