<?php
// database/seeders/RolesAndPermissionsSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        $this->createPermissions();
        $this->createRoles();
        $this->assignPermissionsToRoles();
        $this->createDefaultSuperAdmin();
    }

    private function createPermissions()
    {
        $modules = [
            'inventory' => [
                'create' => 'Create inventory items and manage stock',
                'read' => 'View inventory items and stock levels',
                'update' => 'Update inventory items and stock',
                'delete' => 'Delete inventory items',
                'approve' => 'Approve stock adjustments',
                'transfer' => 'Transfer stock between warehouses',
                'adjust' => 'Adjust stock levels',
            ],
            'manufacturing' => [
                'create' => 'Create production orders and BOMs',
                'read' => 'View manufacturing data',
                'update' => 'Update production orders and manufacturing data',
                'delete' => 'Delete manufacturing records',
                'approve' => 'Approve production orders',
                'start_production' => 'Start production processes',
                'complete_production' => 'Complete production orders',
                'quality_control' => 'Perform quality inspections',
                'maintenance' => 'Manage maintenance schedules',
            ],
            'sales' => [
                'create' => 'Create sales orders and customer records',
                'read' => 'View sales data and customer information',
                'update' => 'Update sales orders and customer data',
                'delete' => 'Delete sales records',
                'approve' => 'Approve sales orders and returns',
                'discount' => 'Apply discounts to sales orders',
                'commission' => 'Manage sales commissions',
                'forecast' => 'Create and manage sales forecasts',
            ],
            'procurement' => [
                'create' => 'Create purchase orders and vendor records',
                'read' => 'View procurement data',
                'update' => 'Update purchase orders and vendor data',
                'delete' => 'Delete procurement records',
                'approve' => 'Approve purchase orders and receipts',
                'receive' => 'Receive purchased goods',
                'return' => 'Process purchase returns',
            ],
            'accounting' => [
                'create' => 'Create journal entries and financial records',
                'read' => 'View financial data and reports',
                'update' => 'Update accounting entries',
                'delete' => 'Delete accounting records',
                'approve' => 'Approve journal entries and transactions',
                'close_period' => 'Close accounting periods',
                'reconcile' => 'Perform bank reconciliations',
                'budget' => 'Manage budgets and forecasts',
                'asset_management' => 'Manage fixed assets',
            ],
            'admin' => [
                'create' => 'Create system users and configurations',
                'read' => 'View system administration data',
                'update' => 'Update system settings and user data',
                'delete' => 'Delete system records',
                'user_management' => 'Manage user accounts',
                'role_management' => 'Manage roles and permissions',
                'system_settings' => 'Configure system settings',
                'backup' => 'Perform system backups',
                'audit' => 'View audit logs',
            ],
        ];

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action => $description) {
                Permission::firstOrCreate([
                    'name' => "{$module}.{$action}",
                ], [
                    'display_name' => ucwords(str_replace('_', ' ', $action)) . " " . ucfirst($module),
                    'module' => $module,
                    'action' => $action,
                    'description' => $description,
                    'is_active' => true,
                ]);
            }
        }
    }

    private function createRoles()
    {
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Administrator',
                'description' => 'Full system access with all permissions',
            ],
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Administrative access to most system functions',
            ],
            [
                'name' => 'inventory_manager',
                'display_name' => 'Inventory Manager',
                'description' => 'Full access to inventory management',
            ],
            [
                'name' => 'inventory_staff',
                'display_name' => 'Inventory Staff',
                'description' => 'Basic inventory operations',
            ],
            [
                'name' => 'production_manager',
                'display_name' => 'Production Manager',
                'description' => 'Full access to manufacturing operations',
            ],
            [
                'name' => 'production_supervisor',
                'display_name' => 'Production Supervisor',
                'description' => 'Production supervision and quality control',
            ],
            [
                'name' => 'production_operator',
                'display_name' => 'Production Operator',
                'description' => 'Basic production operations',
            ],
            [
                'name' => 'sales_manager',
                'display_name' => 'Sales Manager',
                'description' => 'Full access to sales operations',
            ],
            [
                'name' => 'sales_staff',
                'display_name' => 'Sales Staff',
                'description' => 'Basic sales operations',
            ],
            [
                'name' => 'procurement_manager',
                'display_name' => 'Procurement Manager',
                'description' => 'Full access to procurement operations',
            ],
            [
                'name' => 'procurement_staff',
                'display_name' => 'Procurement Staff',
                'description' => 'Basic procurement operations',
            ],
            [
                'name' => 'accountant',
                'display_name' => 'Accountant',
                'description' => 'Full access to accounting functions',
            ],
            [
                'name' => 'finance_manager',
                'display_name' => 'Finance Manager',
                'description' => 'Financial oversight and budget management',
            ],
            [
                'name' => 'warehouse_manager',
                'display_name' => 'Warehouse Manager',
                'description' => 'Warehouse operations and stock management',
            ],
            [
                'name' => 'quality_inspector',
                'display_name' => 'Quality Inspector',
                'description' => 'Quality control and inspections',
            ],
            [
                'name' => 'viewer',
                'display_name' => 'Viewer',
                'description' => 'Read-only access to system data',
            ],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate([
                'name' => $roleData['name'],
            ], $roleData);
        }
    }

    private function assignPermissionsToRoles()
    {
        // Super Admin - All permissions
        $superAdmin = Role::where('name', 'super_admin')->first();
        $allPermissions = Permission::all();
        $superAdmin->permissions()->sync($allPermissions->pluck('id'));

        // Admin - All except super admin specific
        $admin = Role::where('name', 'admin')->first();
        $adminPermissions = Permission::whereNotIn('name', [
            'admin.backup',
            'admin.system_settings'
        ])->get();
        $admin->permissions()->sync($adminPermissions->pluck('id'));

        // Inventory Manager
        $inventoryManager = Role::where('name', 'inventory_manager')->first();
        $inventoryPermissions = Permission::where('module', 'inventory')->get();
        $inventoryManager->permissions()->sync($inventoryPermissions->pluck('id'));

        // Inventory Staff
        $inventoryStaff = Role::where('name', 'inventory_staff')->first();
        $inventoryStaffPermissions = Permission::where('module', 'inventory')
            ->whereIn('action', ['read', 'create', 'update', 'transfer'])
            ->get();
        $inventoryStaff->permissions()->sync($inventoryStaffPermissions->pluck('id'));

        // Production Manager
        $productionManager = Role::where('name', 'production_manager')->first();
        $productionPermissions = Permission::where('module', 'manufacturing')->get();
        $productionManager->permissions()->sync($productionPermissions->pluck('id'));

        // Production Supervisor
        $productionSupervisor = Role::where('name', 'production_supervisor')->first();
        $supervisorPermissions = Permission::where('module', 'manufacturing')
            ->whereIn('action', ['read', 'update', 'approve', 'start_production', 'complete_production', 'quality_control'])
            ->get();
        $productionSupervisor->permissions()->sync($supervisorPermissions->pluck('id'));

        // Production Operator
        $productionOperator = Role::where('name', 'production_operator')->first();
        $operatorPermissions = Permission::where('module', 'manufacturing')
            ->whereIn('action', ['read', 'update', 'start_production'])
            ->get();
        $productionOperator->permissions()->sync($operatorPermissions->pluck('id'));

        // Sales Manager
        $salesManager = Role::where('name', 'sales_manager')->first();
        $salesPermissions = Permission::where('module', 'sales')->get();
        $salesManager->permissions()->sync($salesPermissions->pluck('id'));

        // Sales Staff
        $salesStaff = Role::where('name', 'sales_staff')->first();
        $salesStaffPermissions = Permission::where('module', 'sales')
            ->whereIn('action', ['read', 'create', 'update'])
            ->get();
        $salesStaff->permissions()->sync($salesStaffPermissions->pluck('id'));

        // Procurement Manager
        $procurementManager = Role::where('name', 'procurement_manager')->first();
        $procurementPermissions = Permission::where('module', 'procurement')->get();
        $procurementManager->permissions()->sync($procurementPermissions->pluck('id'));

        // Procurement Staff
        $procurementStaff = Role::where('name', 'procurement_staff')->first();
        $procurementStaffPermissions = Permission::where('module', 'procurement')
            ->whereIn('action', ['read', 'create', 'update', 'receive'])
            ->get();
        $procurementStaff->permissions()->sync($procurementStaffPermissions->pluck('id'));

        // Accountant
        $accountant = Role::where('name', 'accountant')->first();
        $accountingPermissions = Permission::where('module', 'accounting')->get();
        $accountant->permissions()->sync($accountingPermissions->pluck('id'));

        // Finance Manager
        $financeManager = Role::where('name', 'finance_manager')->first();
        $financePermissions = Permission::where('module', 'accounting')
            ->whereIn('action', ['read', 'approve', 'budget', 'reconcile'])
            ->get();
        $financeManager->permissions()->sync($financePermissions->pluck('id'));

        // Warehouse Manager
        $warehouseManager = Role::where('name', 'warehouse_manager')->first();
        $warehousePermissions = Permission::where('module', 'inventory')
            ->whereIn('action', ['read', 'update', 'transfer', 'adjust', 'approve'])
            ->get();
        $warehouseManager->permissions()->sync($warehousePermissions->pluck('id'));

        // Quality Inspector
        $qualityInspector = Role::where('name', 'quality_inspector')->first();
        $qualityPermissions = Permission::where('module', 'manufacturing')
            ->whereIn('action', ['read', 'quality_control'])
            ->get();
        $qualityInspector->permissions()->sync($qualityPermissions->pluck('id'));

        // Viewer - Read-only access to all modules
        $viewer = Role::where('name', 'viewer')->first();
        $viewerPermissions = Permission::where('action', 'read')->get();
        $viewer->permissions()->sync($viewerPermissions->pluck('id'));
    }

    private function createDefaultSuperAdmin()
    {
        $superAdminUser = User::firstOrCreate([
            'email' => 'admin@system.com',
        ], [
            'name' => 'System Administrator',
            'password' => bcrypt('admin123'),
            'email_verified_at' => now(),
        ]);

        $superAdminRole = Role::where('name', 'super_admin')->first();
        if (!$superAdminUser->hasRole('super_admin')) {
            $superAdminUser->assignRole('super_admin');
        }
    }
}
