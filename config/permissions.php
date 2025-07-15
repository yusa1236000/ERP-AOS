<?php
// config/permissions.php

return [
    /*
    |--------------------------------------------------------------------------
    | Permission Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the role-based access control
    | system. You can define modules, default permissions, and other settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Super Admin Bypass
    |--------------------------------------------------------------------------
    |
    | If set to true, users with the 'super_admin' role will bypass all
    | permission checks. Set to false if you want super admins to also
    | be subject to permission restrictions.
    |
    */
    'super_admin_bypass' => true,

    /*
    |--------------------------------------------------------------------------
    | Default Roles
    |--------------------------------------------------------------------------
    |
    | These roles will be created automatically when the seeder runs.
    |
    */
    'default_roles' => [
        'super_admin' => [
            'display_name' => 'Super Administrator',
            'description' => 'Full system access with all permissions',
        ],
        'admin' => [
            'display_name' => 'Administrator',
            'description' => 'Administrative access to most system functions',
        ],
        'viewer' => [
            'display_name' => 'Viewer',
            'description' => 'Read-only access to system data',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | System Modules
    |--------------------------------------------------------------------------
    |
    | Define all the modules in your system. These will be used to generate
    | permissions and organize the access control system.
    |
    */
    'modules' => [
        'inventory' => [
            'display_name' => 'Inventory Management',
            'description' => 'Manage items, stock, warehouses, and inventory operations',
            'icon' => 'package',
            'color' => '#10B981',
        ],
        'manufacturing' => [
            'display_name' => 'Manufacturing',
            'description' => 'Production orders, BOMs, work orders, and quality control',
            'icon' => 'settings',
            'color' => '#3B82F6',
        ],
        'sales' => [
            'display_name' => 'Sales Management',
            'description' => 'Customer management, sales orders, and sales operations',
            'icon' => 'trending-up',
            'color' => '#EF4444',
        ],
        'procurement' => [
            'display_name' => 'Procurement',
            'description' => 'Vendor management, purchase orders, and procurement processes',
            'icon' => 'shopping-cart',
            'color' => '#F59E0B',
        ],
        'accounting' => [
            'display_name' => 'Accounting & Finance',
            'description' => 'Financial management, journal entries, and accounting operations',
            'icon' => 'calculator',
            'color' => '#8B5CF6',
        ],
        'admin' => [
            'display_name' => 'System Administration',
            'description' => 'User management, system settings, and administrative functions',
            'icon' => 'shield',
            'color' => '#6B7280',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Standard Actions
    |--------------------------------------------------------------------------
    |
    | Define the standard actions that can be performed in each module.
    | These will be used to generate permissions.
    |
    */
    'standard_actions' => [
        'create' => [
            'display_name' => 'Create',
            'description' => 'Create new records',
            'icon' => 'plus',
        ],
        'read' => [
            'display_name' => 'Read',
            'description' => 'View and read records',
            'icon' => 'eye',
        ],
        'update' => [
            'display_name' => 'Update',
            'description' => 'Edit and update records',
            'icon' => 'edit',
        ],
        'delete' => [
            'display_name' => 'Delete',
            'description' => 'Delete records',
            'icon' => 'trash',
        ],
        'approve' => [
            'display_name' => 'Approve',
            'description' => 'Approve transactions and operations',
            'icon' => 'check-circle',
        ],
        'export' => [
            'display_name' => 'Export',
            'description' => 'Export data to external formats',
            'icon' => 'download',
        ],
        'import' => [
            'display_name' => 'Import',
            'description' => 'Import data from external sources',
            'icon' => 'upload',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Module-Specific Actions
    |--------------------------------------------------------------------------
    |
    | Define actions that are specific to certain modules.
    |
    */
    'module_specific_actions' => [
        'inventory' => [
            'transfer' => [
                'display_name' => 'Transfer Stock',
                'description' => 'Transfer stock between warehouses',
                'icon' => 'arrow-right',
            ],
            'adjust' => [
                'display_name' => 'Adjust Stock',
                'description' => 'Adjust stock levels',
                'icon' => 'edit-3',
            ],
        ],
        'manufacturing' => [
            'start_production' => [
                'display_name' => 'Start Production',
                'description' => 'Start production processes',
                'icon' => 'play',
            ],
            'complete_production' => [
                'display_name' => 'Complete Production',
                'description' => 'Complete production orders',
                'icon' => 'check',
            ],
            'quality_control' => [
                'display_name' => 'Quality Control',
                'description' => 'Perform quality inspections',
                'icon' => 'award',
            ],
            'maintenance' => [
                'display_name' => 'Maintenance',
                'description' => 'Manage maintenance schedules',
                'icon' => 'tool',
            ],
        ],
        'sales' => [
            'discount' => [
                'display_name' => 'Apply Discounts',
                'description' => 'Apply discounts to sales orders',
                'icon' => 'percent',
            ],
            'commission' => [
                'display_name' => 'Manage Commissions',
                'description' => 'Manage sales commissions',
                'icon' => 'dollar-sign',
            ],
            'forecast' => [
                'display_name' => 'Sales Forecasting',
                'description' => 'Create and manage sales forecasts',
                'icon' => 'trending-up',
            ],
        ],
        'procurement' => [
            'receive' => [
                'display_name' => 'Receive Goods',
                'description' => 'Receive purchased goods',
                'icon' => 'package',
            ],
            'return' => [
                'display_name' => 'Process Returns',
                'description' => 'Process purchase returns',
                'icon' => 'rotate-ccw',
            ],
        ],
        'accounting' => [
            'close_period' => [
                'display_name' => 'Close Period',
                'description' => 'Close accounting periods',
                'icon' => 'lock',
            ],
            'reconcile' => [
                'display_name' => 'Reconcile',
                'description' => 'Perform bank reconciliations',
                'icon' => 'check-square',
            ],
            'budget' => [
                'display_name' => 'Budget Management',
                'description' => 'Manage budgets and forecasts',
                'icon' => 'pie-chart',
            ],
            'asset_management' => [
                'display_name' => 'Asset Management',
                'description' => 'Manage fixed assets',
                'icon' => 'home',
            ],
        ],
        'admin' => [
            'user_management' => [
                'display_name' => 'User Management',
                'description' => 'Manage user accounts',
                'icon' => 'users',
            ],
            'role_management' => [
                'display_name' => 'Role Management',
                'description' => 'Manage roles and permissions',
                'icon' => 'shield',
            ],
            'system_settings' => [
                'display_name' => 'System Settings',
                'description' => 'Configure system settings',
                'icon' => 'settings',
            ],
            'backup' => [
                'display_name' => 'System Backup',
                'description' => 'Perform system backups',
                'icon' => 'hard-drive',
            ],
            'audit' => [
                'display_name' => 'Audit Logs',
                'description' => 'View audit logs',
                'icon' => 'file-text',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Mappings
    |--------------------------------------------------------------------------
    |
    | Define which permissions should be assigned to which roles by default.
    |
    */
    'role_permission_mappings' => [
        'super_admin' => '*', // All permissions
        'admin' => [
            'inventory.*',
            'manufacturing.*',
            'sales.*',
            'procurement.*',
            'accounting.*',
            'admin.create',
            'admin.read',
            'admin.update',
            'admin.user_management',
            'admin.role_management',
        ],
        'inventory_manager' => [
            'inventory.*',
        ],
        'inventory_staff' => [
            'inventory.read',
            'inventory.create',
            'inventory.update',
            'inventory.transfer',
        ],
        'production_manager' => [
            'manufacturing.*',
        ],
        'production_supervisor' => [
            'manufacturing.read',
            'manufacturing.update',
            'manufacturing.approve',
            'manufacturing.start_production',
            'manufacturing.complete_production',
            'manufacturing.quality_control',
        ],
        'production_operator' => [
            'manufacturing.read',
            'manufacturing.update',
            'manufacturing.start_production',
        ],
        'sales_manager' => [
            'sales.*',
        ],
        'sales_staff' => [
            'sales.read',
            'sales.create',
            'sales.update',
        ],
        'procurement_manager' => [
            'procurement.*',
        ],
        'procurement_staff' => [
            'procurement.read',
            'procurement.create',
            'procurement.update',
            'procurement.receive',
        ],
        'accountant' => [
            'accounting.*',
        ],
        'finance_manager' => [
            'accounting.read',
            'accounting.approve',
            'accounting.budget',
            'accounting.reconcile',
        ],
        'warehouse_manager' => [
            'inventory.read',
            'inventory.update',
            'inventory.transfer',
            'inventory.adjust',
            'inventory.approve',
        ],
        'quality_inspector' => [
            'manufacturing.read',
            'manufacturing.quality_control',
        ],
        'viewer' => [
            '*.read',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure caching for roles and permissions to improve performance.
    |
    */
    'cache' => [
        'enabled' => true,
        'expiration_time' => 3600, // 1 hour
        'key_prefix' => 'rbac_',
        'store' => null, // Use default cache store
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Settings
    |--------------------------------------------------------------------------
    |
    | Configure database table names if you need to customize them.
    |
    */
    'table_names' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'role_permission' => 'role_permission',
        'user_role' => 'user_role',
    ],
];
