<?php
// routes/api.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

// Admin Controllers
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\PermissionController;
use App\Http\Controllers\Api\Admin\SystemSettingController;

// Inventory Controllers
use App\Http\Controllers\Api\Inventory\ItemController;
use App\Http\Controllers\Api\Inventory\ItemCategoryController;
use App\Http\Controllers\Api\Inventory\UnitOfMeasureController;
use App\Http\Controllers\Api\Inventory\WarehouseController;
use App\Http\Controllers\Api\Inventory\StockTransactionController;
use App\Http\Controllers\Api\Inventory\StockAdjustmentController;
use App\Http\Controllers\Api\Inventory\ItemStockController;
use App\Http\Controllers\Api\Inventory\ItemPriceController;

// Manufacturing Controllers
use App\Http\Controllers\Api\Manufacturing\ProductController;
use App\Http\Controllers\Api\Manufacturing\BOMController;
use App\Http\Controllers\Api\Manufacturing\BOMLineController;
use App\Http\Controllers\Api\Manufacturing\RoutingController;
use App\Http\Controllers\Api\Manufacturing\WorkCenterController;
use App\Http\Controllers\Api\Manufacturing\RoutingOperationController;
use App\Http\Controllers\Api\Manufacturing\WorkOrderController;
use App\Http\Controllers\Api\Manufacturing\WorkOrderOperationController;
use App\Http\Controllers\Api\Manufacturing\ProductionOrderController;
use App\Http\Controllers\Api\Manufacturing\ProductionConsumptionController;
use App\Http\Controllers\Api\Manufacturing\QualityInspectionController;
use App\Http\Controllers\Api\Manufacturing\QualityParameterController;
use App\Http\Controllers\Api\Manufacturing\MaintenanceScheduleController;
use App\Http\Controllers\Api\Manufacturing\MaterialPlanningController;
use App\Http\Controllers\Api\Manufacturing\JobTicketController;

// Sales Controllers
use App\Http\Controllers\Api\Sales\CustomerController;
use App\Http\Controllers\Api\Sales\SalesOrderController;
use App\Http\Controllers\Api\Sales\SalesReturnController;
use App\Http\Controllers\Api\Sales\CustomerInteractionController;
use App\Http\Controllers\Api\Sales\SalesCommissionController;
use App\Http\Controllers\Api\Sales\SalesForecastController;
use App\Http\Controllers\Api\Sales\AIExcelForecastController;

// Procurement Controllers
use App\Http\Controllers\Api\Procurement\VendorController;
use App\Http\Controllers\Api\Procurement\PurchaseOrderController;
use App\Http\Controllers\Api\Procurement\PurchaseReceiptController;
use App\Http\Controllers\Api\Procurement\PurchaseReturnController;
use App\Http\Controllers\Api\Procurement\PurchaseRequisitionController;

// Accounting Controllers
use App\Http\Controllers\Api\Accounting\ChartOfAccountController;
use App\Http\Controllers\Api\Accounting\AccountingPeriodController;
use App\Http\Controllers\Api\Accounting\JournalEntryController;
use App\Http\Controllers\Api\Accounting\CustomerReceivableController;
use App\Http\Controllers\Api\Accounting\ReceivablePaymentController;
use App\Http\Controllers\Api\Accounting\VendorPayableController;
use App\Http\Controllers\Api\Accounting\PayablePaymentController;
use App\Http\Controllers\Api\Accounting\TaxTransactionController;
use App\Http\Controllers\Api\Accounting\FixedAssetController;
use App\Http\Controllers\Api\Accounting\AssetDepreciationController;
use App\Http\Controllers\Api\Accounting\BudgetController;
use App\Http\Controllers\Api\Accounting\BankAccountController;
use App\Http\Controllers\Api\Accounting\BankReconciliationController;
use App\Http\Controllers\Api\Accounting\FinancialReportController;

// Special Controllers
use App\Http\Controllers\Api\PDFOrderCaptureController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/

// Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

// Health Check
Route::get('health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'version' => config('app.version', '1.0.0')
    ]);
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Authentication Required)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Basic Auth Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
        Route::post('verify-email', [AuthController::class, 'verifyEmail']);
        Route::post('resend-verification', [AuthController::class, 'resendVerification']);
    });

    // User Profile Routes
    Route::get('user', [AuthController::class, 'user']);
    Route::get('profile', [UserController::class, 'getProfile']);
    Route::put('profile', [UserController::class, 'updateProfile']);

    // Dashboard Routes
    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('dashboard/stats', [DashboardController::class, 'getStats']);
    Route::get('dashboard/widgets', [DashboardController::class, 'getWidgets']);

    /*
    |--------------------------------------------------------------------------
    | Administration Routes
    |--------------------------------------------------------------------------
    | Requires: super_admin or admin role
    */
    Route::middleware(['role:super_admin,admin'])->prefix('admin')->group(function () {

        // User Management
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index'])->middleware('permission:admin.user_management');
            Route::post('/', [UserController::class, 'store'])->middleware('permission:admin.user_management');
            Route::get('/{user}', [UserController::class, 'show'])->middleware('permission:admin.user_management');
            Route::put('/{user}', [UserController::class, 'update'])->middleware('permission:admin.user_management');
            Route::delete('/{user}', [UserController::class, 'destroy'])->middleware('permission:admin.user_management');

            // User Role Management
            Route::post('/{user}/assign-roles', [UserController::class, 'assignRoles'])->middleware('permission:admin.user_management');
            Route::post('/{user}/remove-roles', [UserController::class, 'removeRoles'])->middleware('permission:admin.user_management');
            Route::post('/{user}/sync-roles', [UserController::class, 'syncRoles'])->middleware('permission:admin.user_management');
            Route::get('/{user}/roles', [UserController::class, 'getUserRoles'])->middleware('permission:admin.user_management');
            Route::get('/{user}/permissions', [UserController::class, 'getUserPermissions'])->middleware('permission:admin.user_management');

            // User Status Management
            Route::patch('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->middleware('permission:admin.user_management');
            Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('permission:admin.user_management');

            // Bulk Operations
            Route::post('/bulk-assign-roles', [UserController::class, 'bulkAssignRoles'])->middleware('permission:admin.user_management');
            Route::post('/bulk-remove-roles', [UserController::class, 'bulkRemoveRoles'])->middleware('permission:admin.user_management');
            Route::post('/bulk-activate', [UserController::class, 'bulkActivate'])->middleware('permission:admin.user_management');
            Route::post('/bulk-deactivate', [UserController::class, 'bulkDeactivate'])->middleware('permission:admin.user_management');

            // Export/Import
            Route::get('/export', [UserController::class, 'export'])->middleware('permission:admin.user_management');
            Route::post('/import', [UserController::class, 'import'])->middleware('permission:admin.user_management');
        });

        // Role Management
        Route::prefix('roles')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->middleware('permission:admin.role_management');
            Route::post('/', [RoleController::class, 'store'])->middleware('permission:admin.role_management');
            Route::get('/{role}', [RoleController::class, 'show'])->middleware('permission:admin.role_management');
            Route::put('/{role}', [RoleController::class, 'update'])->middleware('permission:admin.role_management');
            Route::delete('/{role}', [RoleController::class, 'destroy'])->middleware('permission:admin.role_management');

            // Role Permission Management
            Route::post('/{role}/assign-permissions', [RoleController::class, 'assignPermissions'])->middleware('permission:admin.role_management');
            Route::post('/{role}/remove-permissions', [RoleController::class, 'removePermissions'])->middleware('permission:admin.role_management');
            Route::get('/{role}/permissions', [RoleController::class, 'getPermissions'])->middleware('permission:admin.role_management');
            Route::get('/{role}/available-permissions', [RoleController::class, 'getAvailablePermissions'])->middleware('permission:admin.role_management');

            // Role Utilities
            Route::post('/{role}/duplicate', [RoleController::class, 'duplicate'])->middleware('permission:admin.role_management');
            Route::get('/{role}/users', [RoleController::class, 'getUsers'])->middleware('permission:admin.role_management');
        });

        // Permission Management
        Route::prefix('permissions')->group(function () {
            Route::get('/', [PermissionController::class, 'index'])->middleware('permission:admin.role_management');
            Route::post('/', [PermissionController::class, 'store'])->middleware('permission:admin.role_management');
            Route::get('/{permission}', [PermissionController::class, 'show'])->middleware('permission:admin.role_management');
            Route::put('/{permission}', [PermissionController::class, 'update'])->middleware('permission:admin.role_management');
            Route::delete('/{permission}', [PermissionController::class, 'destroy'])->middleware('permission:admin.role_management');

            // Permission Utilities
            Route::get('/modules', [PermissionController::class, 'getModules'])->middleware('permission:admin.role_management');
            Route::get('/actions', [PermissionController::class, 'getActions'])->middleware('permission:admin.role_management');
            Route::get('/module/{module}', [PermissionController::class, 'getByModule'])->middleware('permission:admin.role_management');
            Route::post('/bulk-create', [PermissionController::class, 'bulkCreate'])->middleware('permission:admin.role_management');
            Route::post('/generate-module', [PermissionController::class, 'generateModulePermissions'])->middleware('permission:admin.role_management');
        });

        // System Settings (Super Admin Only)
        Route::middleware(['role:super_admin'])->prefix('settings')->group(function () {
            Route::get('/', [SystemSettingController::class, 'index']);
            Route::put('/', [SystemSettingController::class, 'update']);
            Route::put('/batch', [SystemSettingController::class, 'updateMultiple']);
            Route::get('/group/{group}', [SystemSettingController::class, 'getByGroup']);
            Route::get('/inventory', [SystemSettingController::class, 'getInventorySettings']);
            Route::put('/inventory', [SystemSettingController::class, 'updateInventorySettings']);

            // System Maintenance
            Route::post('/clear-cache', [SystemSettingController::class, 'clearCache']);
            Route::post('/backup', [SystemSettingController::class, 'backup']);
            Route::get('/audit-logs', [SystemSettingController::class, 'getAuditLogs']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Inventory Management Routes
    |--------------------------------------------------------------------------
    | Module Access Control: inventory
    */
    Route::middleware(['module_access:inventory'])->prefix('inventory')->group(function () {

        // Items Management
        Route::prefix('items')->group(function () {
            Route::get('/', [ItemController::class, 'index']); // inventory.read
            Route::post('/', [ItemController::class, 'store'])->middleware('permission:inventory.create');
            Route::get('/stock-status', [ItemController::class, 'getStockStatus']); // inventory.read
            Route::get('/purchasable', [ItemController::class, 'getPurchasableItems']); // inventory.read
            Route::get('/sellable', [ItemController::class, 'getSellableItems']); // inventory.read
            Route::get('/{item}', [ItemController::class, 'show']); // inventory.read
            Route::put('/{item}', [ItemController::class, 'update'])->middleware('permission:inventory.update');
            Route::delete('/{item}', [ItemController::class, 'destroy'])->middleware('permission:inventory.delete');

            // Item Pricing
            Route::get('/{item}/prices-in-currencies', [ItemController::class, 'getPricesInCurrencies']); // inventory.read
            Route::get('/{item}/prices', [ItemPriceController::class, 'index']); // inventory.read
            Route::post('/{item}/prices', [ItemPriceController::class, 'store'])->middleware('permission:inventory.update');
            Route::get('/{item}/prices/{price}', [ItemPriceController::class, 'show']); // inventory.read
            Route::put('/{item}/prices/{price}', [ItemPriceController::class, 'update'])->middleware('permission:inventory.update');
            Route::delete('/{item}/prices/{price}', [ItemPriceController::class, 'destroy'])->middleware('permission:inventory.delete');

            // Bulk Operations
            Route::post('/bulk-update', [ItemController::class, 'bulkUpdate'])->middleware('permission:inventory.update');
            Route::post('/bulk-delete', [ItemController::class, 'bulkDelete'])->middleware('permission:inventory.delete');
            Route::post('/import', [ItemController::class, 'import'])->middleware('permission:inventory.import');
            Route::get('/export', [ItemController::class, 'export'])->middleware('permission:inventory.export');
        });

        // Item Categories
        Route::prefix('categories')->group(function () {
            Route::get('/', [ItemCategoryController::class, 'index']); // inventory.read
            Route::post('/', [ItemCategoryController::class, 'store'])->middleware('permission:inventory.create');
            Route::get('/{category}', [ItemCategoryController::class, 'show']); // inventory.read
            Route::put('/{category}', [ItemCategoryController::class, 'update'])->middleware('permission:inventory.update');
            Route::delete('/{category}', [ItemCategoryController::class, 'destroy'])->middleware('permission:inventory.delete');
        });

        // Unit of Measures
        Route::prefix('uom')->group(function () {
            Route::get('/', [UnitOfMeasureController::class, 'index']); // inventory.read
            Route::post('/', [UnitOfMeasureController::class, 'store'])->middleware('permission:inventory.create');
            Route::get('/{uom}', [UnitOfMeasureController::class, 'show']); // inventory.read
            Route::put('/{uom}', [UnitOfMeasureController::class, 'update'])->middleware('permission:inventory.update');
            Route::delete('/{uom}', [UnitOfMeasureController::class, 'destroy'])->middleware('permission:inventory.delete');
        });

        // Warehouses
        Route::prefix('warehouses')->group(function () {
            Route::get('/', [WarehouseController::class, 'index']); // inventory.read
            Route::post('/', [WarehouseController::class, 'store'])->middleware('permission:inventory.create');
            Route::get('/{warehouse}', [WarehouseController::class, 'show']); // inventory.read
            Route::put('/{warehouse}', [WarehouseController::class, 'update'])->middleware('permission:inventory.update');
            Route::delete('/{warehouse}', [WarehouseController::class, 'destroy'])->middleware('permission:inventory.delete');

            // Warehouse Stock
            Route::get('/{warehouse}/stock', [WarehouseController::class, 'getStock']); // inventory.read
            Route::get('/{warehouse}/stock/{item}', [WarehouseController::class, 'getItemStock']); // inventory.read
        });

        // Stock Transactions
        Route::prefix('stock-transactions')->group(function () {
            Route::get('/', [StockTransactionController::class, 'index']); // inventory.read
            Route::post('/', [StockTransactionController::class, 'store'])->middleware('permission:inventory.create');
            Route::get('/{transaction}', [StockTransactionController::class, 'show']); // inventory.read
            Route::put('/{transaction}', [StockTransactionController::class, 'update'])->middleware('permission:inventory.update');
            Route::delete('/{transaction}', [StockTransactionController::class, 'destroy'])->middleware('permission:inventory.delete');

            // Transaction Operations
            Route::post('/{transaction}/confirm', [StockTransactionController::class, 'confirm'])->middleware('permission:inventory.approve');
            Route::post('/{transaction}/cancel', [StockTransactionController::class, 'cancel'])->middleware('permission:inventory.update');
            Route::post('/bulk-confirm', [StockTransactionController::class, 'bulkConfirm'])->middleware('permission:inventory.approve');
            Route::get('/pending', [StockTransactionController::class, 'getPending']); // inventory.read

            // Stock Operations
            Route::post('/transfer', [StockTransactionController::class, 'transfer'])->middleware('permission:inventory.transfer');
            Route::get('/item/{item}', [StockTransactionController::class, 'itemTransactions']); // inventory.read
            Route::get('/item/{item}/movement', [StockTransactionController::class, 'itemMovement']); // inventory.read
            Route::get('/warehouse/{warehouse}', [StockTransactionController::class, 'getWarehouseTransactions']); // inventory.read
        });

        // Stock Adjustments
        Route::prefix('stock-adjustments')->group(function () {
            Route::get('/', [StockAdjustmentController::class, 'index']); // inventory.read
            Route::post('/', [StockAdjustmentController::class, 'store'])->middleware('permission:inventory.create');
            Route::get('/{adjustment}', [StockAdjustmentController::class, 'show']); // inventory.read
            Route::put('/{adjustment}', [StockAdjustmentController::class, 'update'])->middleware('permission:inventory.update');
            Route::delete('/{adjustment}', [StockAdjustmentController::class, 'destroy'])->middleware('permission:inventory.delete');

            // Adjustment Operations
            Route::post('/{adjustment}/submit', [StockAdjustmentController::class, 'submit'])->middleware('permission:inventory.adjust');
            Route::post('/{adjustment}/approve', [StockAdjustmentController::class, 'approve'])->middleware('permission:inventory.approve');
            Route::post('/{adjustment}/reject', [StockAdjustmentController::class, 'reject'])->middleware('permission:inventory.approve');
            Route::post('/{adjustment}/process', [StockAdjustmentController::class, 'process'])->middleware('permission:inventory.approve');
        });

        // Item Stock Management
        Route::prefix('item-stocks')->group(function () {
            Route::get('/', [ItemStockController::class, 'index']); // inventory.read
            Route::get('/item/{item}', [ItemStockController::class, 'getItemStock']); // inventory.read
            Route::get('/warehouse/{warehouse}', [ItemStockController::class, 'getWarehouseStock']); // inventory.read
            Route::get('/negative', [ItemStockController::class, 'getNegativeStocks']); // inventory.read
            Route::get('/negative-stock-summary', [ItemStockController::class, 'getNegativeStockSummary']); // inventory.read

            // Stock Operations
            Route::post('/transfer', [ItemStockController::class, 'transferStock'])->middleware('permission:inventory.transfer');
            Route::post('/adjust', [ItemStockController::class, 'adjustStock'])->middleware('permission:inventory.adjust');
            Route::post('/reserve', [ItemStockController::class, 'reserveStock'])->middleware('permission:inventory.update');
            Route::post('/release-reservation', [ItemStockController::class, 'releaseReservation'])->middleware('permission:inventory.update');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Manufacturing Management Routes
    |--------------------------------------------------------------------------
    | Module Access Control: manufacturing
    */
    Route::middleware(['module_access:manufacturing'])->prefix('manufacturing')->group(function () {

        // Products
        Route::prefix('products')->group(function () {
            Route::get('/', [ProductController::class, 'index']); // manufacturing.read
            Route::post('/', [ProductController::class, 'store'])->middleware('permission:manufacturing.create');
            Route::get('/{product}', [ProductController::class, 'show']); // manufacturing.read
            Route::put('/{product}', [ProductController::class, 'update'])->middleware('permission:manufacturing.update');
            Route::delete('/{product}', [ProductController::class, 'destroy'])->middleware('permission:manufacturing.delete');
        });

        // Bill of Materials (BOMs)
        Route::prefix('boms')->group(function () {
            Route::get('/', [BOMController::class, 'index']); // manufacturing.read
            Route::post('/', [BOMController::class, 'store'])->middleware('permission:manufacturing.create');
            Route::get('/{bom}', [BOMController::class, 'show']); // manufacturing.read
            Route::put('/{bom}', [BOMController::class, 'update'])->middleware('permission:manufacturing.update');
            Route::delete('/{bom}', [BOMController::class, 'destroy'])->middleware('permission:manufacturing.delete');

            // BOM Lines
            Route::get('/{bom}/lines', [BOMLineController::class, 'index']); // manufacturing.read
            Route::post('/{bom}/lines', [BOMLineController::class, 'store'])->middleware('permission:manufacturing.create');
            Route::get('/{bom}/lines/{line}', [BOMLineController::class, 'show']); // manufacturing.read
            Route::put('/{bom}/lines/{line}', [BOMLineController::class, 'update'])->middleware('permission:manufacturing.update');
            Route::delete('/{bom}/lines/{line}', [BOMLineController::class, 'destroy'])->middleware('permission:manufacturing.delete');

            // BOM Calculations
            Route::post('/{bom}/lines/{line}/calculate-yield', [BOMLineController::class, 'calculateYield']); // manufacturing.read
            Route::get('/{bom}/maximum-yield', [BOMLineController::class, 'calculateMaximumYield']); // manufacturing.read
            Route::post('/yield-based', [BOMController::class, 'createYieldBased'])->middleware('permission:manufacturing.create');
        });

        // Routings
        Route::prefix('routings')->group(function () {
            Route::get('/', [RoutingController::class, 'index']); // manufacturing.read
            Route::post('/', [RoutingController::class, 'store'])->middleware('permission:manufacturing.create');
            Route::get('/{routing}', [RoutingController::class, 'show']); // manufacturing.read
            Route::put('/{routing}', [RoutingController::class, 'update'])->middleware('permission:manufacturing.update');
            Route::delete('/{routing}', [RoutingController::class, 'destroy'])->middleware('permission:manufacturing.delete');

            // Routing Operations
            Route::get('/{routing}/operations', [RoutingOperationController::class, 'index']); // manufacturing.read
            Route::post('/{routing}/operations', [RoutingOperationController::class, 'store'])->middleware('permission:manufacturing.create');
            Route::get('/{routing}/operations/{operation}', [RoutingOperationController::class, 'show']); // manufacturing.read
            Route::put('/{routing}/operations/{operation}', [RoutingOperationController::class, 'update'])->middleware('permission:manufacturing.update');
            Route::delete('/{routing}/operations/{operation}', [RoutingOperationController::class, 'destroy'])->middleware('permission:manufacturing.delete');
        });

        // Work Centers
        Route::prefix('work-centers')->group(function () {
            Route::get('/', [WorkCenterController::class, 'index']); // manufacturing.read
            Route::post('/', [WorkCenterController::class, 'store'])->middleware('permission:manufacturing.create');
            Route::get('/{workCenter}', [WorkCenterController::class, 'show']); // manufacturing.read
            Route::put('/{workCenter}', [WorkCenterController::class, 'update'])->middleware('permission:manufacturing.update');
            Route::delete('/{workCenter}', [WorkCenterController::class, 'destroy'])->middleware('permission:manufacturing.delete');

            // Work Center Maintenance
            Route::get('/{workCenter}/maintenance-schedules', [MaintenanceScheduleController::class, 'byWorkCenter'])->middleware('permission:manufacturing.maintenance');
        });

        // Work Orders
        Route::prefix('work-orders')->group(function () {
            Route::get('/', [WorkOrderController::class, 'index']); // manufacturing.read
            Route::post('/', [WorkOrderController::class, 'store'])->middleware('permission:manufacturing.create');
            Route::get('/next-number', [WorkOrderController::class, 'getNextWorkOrderNumber']); // manufacturing.read
            Route::get('/{workOrder}', [WorkOrderController::class, 'show']); // manufacturing.read
            Route::put('/{workOrder}', [WorkOrderController::class, 'update'])->middleware('permission:manufacturing.update');
            Route::delete('/{workOrder}', [WorkOrderController::class, 'destroy'])->middleware('permission:manufacturing.delete');

            // Work Order Operations
            Route::get('/{workOrder}/operations', [WorkOrderOperationController::class, 'index']); // manufacturing.read
            Route::get('/{workOrder}/operations/{operation}', [WorkOrderOperationController::class, 'show']); // manufacturing.read
            Route::put('/{workOrder}/operations/{operation}', [WorkOrderOperationController::class, 'update'])->middleware('permission:manufacturing.update');
        });

        // Production Orders
        Route::prefix('production-orders')->group(function () {
            Route::get('/', [ProductionOrderController::class, 'index']); // manufacturing.read
            Route::post('/', [ProductionOrderController::class, 'store'])->middleware('permission:manufacturing.create');
            Route::get('/next-number', [ProductionOrderController::class, 'getNextProductionNumber']); // manufacturing.read
            Route::get('/{productionOrder}', [ProductionOrderController::class, 'show']); // manufacturing.read
            Route::put('/{productionOrder}', [ProductionOrderController::class, 'update'])->middleware('permission:manufacturing.update');
            Route::delete('/{productionOrder}', [ProductionOrderController::class, 'destroy'])->middleware('permission:manufacturing.delete');

            // Production Flow Operations
            Route::patch('/{productionOrder}/status', [ProductionOrderController::class, 'updateStatus'])->middleware('permission:manufacturing.approve');
            Route::post('/{productionOrder}/issue-materials', [ProductionOrderController::class, 'issueMaterials'])->middleware('permission:manufacturing.start_production');
            Route::post('/{productionOrder}/start-production', [ProductionOrderController::class, 'startProduction'])->middleware('permission:manufacturing.start_production');
            Route::post('/{productionOrder}/complete', [ProductionOrderController::class, 'complete'])->middleware('permission:manufacturing.complete_production');

            // Production Information
            Route::get('/{productionOrder}/material-status', [ProductionOrderController::class, 'getMaterialStatus']); // manufacturing.read
            Route::get('/{productionOrder}/production-summary', [ProductionOrderController::class, 'getProductionSummary']); // manufacturing.read

            // Bulk Operations
            Route::post('/bulk/issue-materials', [ProductionOrderController::class, 'bulkIssueMaterials'])->middleware('permission:manufacturing.start_production');
            Route::post('/bulk/start-production', [ProductionOrderController::class, 'bulkStartProduction'])->middleware('permission:manufacturing.start_production');

            // Reports
            Route::get('/reports/material-consumption', [ProductionOrderController::class, 'materialConsumptionReport']); // manufacturing.read
            Route::get('/reports/production-efficiency', [ProductionOrderController::class, 'productionEfficiencyReport']); // manufacturing.read

            // Production Consumptions
            Route::prefix('{productionOrder}/consumptions')->group(function () {
                Route::get('/', [ProductionConsumptionController::class, 'index']); // manufacturing.read
                Route::post('/', [ProductionConsumptionController::class, 'store'])->middleware('permission:manufacturing.update');
                Route::get('/{consumption}', [ProductionConsumptionController::class, 'show']); // manufacturing.read
                Route::put('/{consumption}', [ProductionConsumptionController::class, 'update'])->middleware('permission:manufacturing.update');
                Route::delete('/{consumption}', [ProductionConsumptionController::class, 'destroy'])->middleware('permission:manufacturing.delete');
            });
        });

        // Quality Control
        Route::prefix('quality')->group(function () {
            // Quality Inspections
            Route::prefix('inspections')->group(function () {
                Route::get('/', [QualityInspectionController::class, 'index'])->middleware('permission:manufacturing.quality_control');
                Route::post('/', [QualityInspectionController::class, 'store'])->middleware('permission:manufacturing.quality_control');
                Route::get('/{inspection}', [QualityInspectionController::class, 'show'])->middleware('permission:manufacturing.quality_control');
                Route::put('/{inspection}', [QualityInspectionController::class, 'update'])->middleware('permission:manufacturing.quality_control');
                Route::delete('/{inspection}', [QualityInspectionController::class, 'destroy'])->middleware('permission:manufacturing.quality_control');

                Route::get('/by-reference/{referenceType}/{referenceId}', [QualityInspectionController::class, 'byReference'])->middleware('permission:manufacturing.quality_control');

                // Quality Parameters
                Route::prefix('{inspection}/parameters')->group(function () {
                    Route::get('/', [QualityParameterController::class, 'index'])->middleware('permission:manufacturing.quality_control');
                    Route::post('/', [QualityParameterController::class, 'store'])->middleware('permission:manufacturing.quality_control');
                    Route::get('/{parameter}', [QualityParameterController::class, 'show'])->middleware('permission:manufacturing.quality_control');
                    Route::put('/{parameter}', [QualityParameterController::class, 'update'])->middleware('permission:manufacturing.quality_control');
                    Route::delete('/{parameter}', [QualityParameterController::class, 'destroy'])->middleware('permission:manufacturing.quality_control');
                });
            });

            // Quality Parameters
            Route::get('/parameters/categories', [QualityParameterController::class, 'categories'])->middleware('permission:manufacturing.quality_control');
            Route::get('/parameters/items', [QualityParameterController::class, 'getItems'])->middleware('permission:manufacturing.quality_control');
        });

        // Maintenance Schedules
        Route::middleware(['permission:manufacturing.maintenance'])->prefix('maintenance')->group(function () {
            Route::get('/', [MaintenanceScheduleController::class, 'index']);
            Route::post('/', [MaintenanceScheduleController::class, 'store']);
            Route::get('/{schedule}', [MaintenanceScheduleController::class, 'show']);
            Route::put('/{schedule}', [MaintenanceScheduleController::class, 'update']);
            Route::delete('/{schedule}', [MaintenanceScheduleController::class, 'destroy']);
        });

        // Material Planning
        Route::prefix('material-planning')->group(function () {
            Route::get('/', [MaterialPlanningController::class, 'index']); // manufacturing.read
            Route::get('/{plan}', [MaterialPlanningController::class, 'show']); // manufacturing.read
            Route::delete('/{plan}', [MaterialPlanningController::class, 'destroy'])->middleware('permission:manufacturing.delete');

            // Planning Operations
            Route::post('/generate', [MaterialPlanningController::class, 'generateMaterialPlans'])->middleware('permission:manufacturing.create');
            Route::post('/calculate-max-production', [MaterialPlanningController::class, 'calculateMaximumProduction']); // manufacturing.read
            Route::post('/bom-explosion', [MaterialPlanningController::class, 'getBOMExplosion']); // manufacturing.read

            // Generate Operations (require approval)
            Route::post('/generate-pr', [MaterialPlanningController::class, 'generatePurchaseRequisitions'])->middleware('permission:manufacturing.approve');
            Route::post('/generate-pr-by-period', [MaterialPlanningController::class, 'generatePurchaseRequisitionsByPeriod'])->middleware('permission:manufacturing.approve');
            Route::post('/generate-wo', [MaterialPlanningController::class, 'generateWorkOrders'])->middleware('permission:manufacturing.approve');
            Route::post('/generate-wo-by-period', [MaterialPlanningController::class, 'generateWorkOrdersByPeriod'])->middleware('permission:manufacturing.approve');
        });

        // Job Tickets
        Route::prefix('job-tickets')->group(function () {
            Route::get('/', [JobTicketController::class, 'index']); // manufacturing.read
            Route::post('/', [JobTicketController::class, 'store'])->middleware('permission:manufacturing.create');
            Route::get('/{jobTicket}', [JobTicketController::class, 'show']); // manufacturing.read
            Route::put('/{jobTicket}', [JobTicketController::class, 'update'])->middleware('permission:manufacturing.update');
            Route::delete('/{jobTicket}', [JobTicketController::class, 'destroy'])->middleware('permission:manufacturing.delete');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Sales Management Routes
    |--------------------------------------------------------------------------
    | Module Access Control: sales
    */
    Route::middleware(['module_access:sales'])->prefix('sales')->group(function () {

        // Customers
        Route::prefix('customers')->group(function () {
            Route::get('/', [CustomerController::class, 'index']); // sales.read
            Route::post('/', [CustomerController::class, 'store'])->middleware('permission:sales.create');
            Route::get('/{customer}', [CustomerController::class, 'show']); // sales.read
            Route::put('/{customer}', [CustomerController::class, 'update'])->middleware('permission:sales.update');
            Route::delete('/{customer}', [CustomerController::class, 'destroy'])->middleware('permission:sales.delete');

            // Customer Operations
            Route::get('/{customer}/orders', [CustomerController::class, 'getOrders']); // sales.read
            Route::get('/{customer}/balance', [CustomerController::class, 'getBalance']); // sales.read
            Route::post('/{customer}/credit-limit', [CustomerController::class, 'updateCreditLimit'])->middleware('permission:sales.approve');
        });

        // Sales Orders
        Route::prefix('orders')->group(function () {
            Route::get('/', [SalesOrderController::class, 'index']); // sales.read
            Route::post('/', [SalesOrderController::class, 'store'])->middleware('permission:sales.create');
            Route::get('/next-number', [SalesOrderController::class, 'getNextOrderNumber']); // sales.read
            Route::get('/{order}', [SalesOrderController::class, 'show']); // sales.read
            Route::put('/{order}', [SalesOrderController::class, 'update'])->middleware('permission:sales.update');
            Route::delete('/{order}', [SalesOrderController::class, 'destroy'])->middleware('permission:sales.delete');

            // Order Operations
            Route::post('/{order}/confirm', [SalesOrderController::class, 'confirm'])->middleware('permission:sales.approve');
            Route::post('/{order}/cancel', [SalesOrderController::class, 'cancel'])->middleware('permission:sales.update');
            Route::post('/{order}/deliver', [SalesOrderController::class, 'deliver'])->middleware('permission:sales.update');
            Route::post('/{order}/invoice', [SalesOrderController::class, 'generateInvoice'])->middleware('permission:sales.approve');

            // Order Discounts
            Route::post('/{order}/apply-discount', [SalesOrderController::class, 'applyDiscount'])->middleware('permission:sales.discount');
        });

        // Sales Returns
        Route::prefix('returns')->group(function () {
            Route::get('/', [SalesReturnController::class, 'index']); // sales.read
            Route::post('/', [SalesReturnController::class, 'store'])->middleware('permission:sales.create');
            Route::get('/{return}', [SalesReturnController::class, 'show']); // sales.read
            Route::put('/{return}', [SalesReturnController::class, 'update'])->middleware('permission:sales.update');
            Route::delete('/{return}', [SalesReturnController::class, 'destroy'])->middleware('permission:sales.delete');

            Route::post('/{return}/approve', [SalesReturnController::class, 'approve'])->middleware('permission:sales.approve');
            Route::post('/{return}/process', [SalesReturnController::class, 'process'])->middleware('permission:sales.approve');
        });

        // Customer Interactions
        Route::prefix('interactions')->group(function () {
            Route::get('/', [CustomerInteractionController::class, 'index']); // sales.read
            Route::post('/', [CustomerInteractionController::class, 'store'])->middleware('permission:sales.create');
            Route::get('/{interaction}', [CustomerInteractionController::class, 'show']); // sales.read
            Route::put('/{interaction}', [CustomerInteractionController::class, 'update'])->middleware('permission:sales.update');
            Route::delete('/{interaction}', [CustomerInteractionController::class, 'destroy'])->middleware('permission:sales.delete');
        });

        // Sales Commissions
        Route::middleware(['permission:sales.commission'])->prefix('commissions')->group(function () {
            Route::get('/', [SalesCommissionController::class, 'index']);
            Route::post('/', [SalesCommissionController::class, 'store']);
            Route::get('/{commission}', [SalesCommissionController::class, 'show']);
            Route::put('/{commission}', [SalesCommissionController::class, 'update']);
            Route::delete('/{commission}', [SalesCommissionController::class, 'destroy']);

            Route::post('/calculate', [SalesCommissionController::class, 'calculate']);
            Route::post('/{commission}/approve', [SalesCommissionController::class, 'approve']);
            Route::post('/{commission}/pay', [SalesCommissionController::class, 'markAsPaid']);
        });

        // Sales Forecasts
        Route::middleware(['permission:sales.forecast'])->prefix('forecasts')->group(function () {
            Route::get('/', [SalesForecastController::class, 'index']);
            Route::post('/', [SalesForecastController::class, 'store']);
            Route::get('/{forecast}', [SalesForecastController::class, 'show']);
            Route::put('/{forecast}', [SalesForecastController::class, 'update']);
            Route::delete('/{forecast}', [SalesForecastController::class, 'destroy']);

            // AI Forecast
            Route::post('/ai-excel-forecast', [AIExcelForecastController::class, 'process']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Procurement Management Routes
    |--------------------------------------------------------------------------
    | Module Access Control: procurement
    */
    Route::middleware(['module_access:procurement'])->prefix('procurement')->group(function () {

        // Vendors
        Route::prefix('vendors')->group(function () {
            Route::get('/', [VendorController::class, 'index']); // procurement.read
            Route::post('/', [VendorController::class, 'store'])->middleware('permission:procurement.create');
            Route::get('/{vendor}', [VendorController::class, 'show']); // procurement.read
            Route::put('/{vendor}', [VendorController::class, 'update'])->middleware('permission:procurement.update');
            Route::delete('/{vendor}', [VendorController::class, 'destroy'])->middleware('permission:procurement.delete');
        });

        // Purchase Requisitions
        Route::prefix('requisitions')->group(function () {
            Route::get('/', [PurchaseRequisitionController::class, 'index']); // procurement.read
            Route::post('/', [PurchaseRequisitionController::class, 'store'])->middleware('permission:procurement.create');
            Route::get('/{requisition}', [PurchaseRequisitionController::class, 'show']); // procurement.read
            Route::put('/{requisition}', [PurchaseRequisitionController::class, 'update'])->middleware('permission:procurement.update');
            Route::delete('/{requisition}', [PurchaseRequisitionController::class, 'destroy'])->middleware('permission:procurement.delete');

            Route::post('/{requisition}/approve', [PurchaseRequisitionController::class, 'approve'])->middleware('permission:procurement.approve');
            Route::post('/{requisition}/reject', [PurchaseRequisitionController::class, 'reject'])->middleware('permission:procurement.approve');
            Route::post('/{requisition}/convert-to-po', [PurchaseRequisitionController::class, 'convertToPO'])->middleware('permission:procurement.create');
        });

        // Purchase Orders
        Route::prefix('orders')->group(function () {
            Route::get('/', [PurchaseOrderController::class, 'index']); // procurement.read
            Route::post('/', [PurchaseOrderController::class, 'store'])->middleware('permission:procurement.create');
            Route::get('/next-number', [PurchaseOrderController::class, 'getNextOrderNumber']); // procurement.read
            Route::get('/{order}', [PurchaseOrderController::class, 'show']); // procurement.read
            Route::put('/{order}', [PurchaseOrderController::class, 'update'])->middleware('permission:procurement.update');
            Route::delete('/{order}', [PurchaseOrderController::class, 'destroy'])->middleware('permission:procurement.delete');

            // Order Operations
            Route::post('/{order}/confirm', [PurchaseOrderController::class, 'confirm'])->middleware('permission:procurement.approve');
            Route::post('/{order}/cancel', [PurchaseOrderController::class, 'cancel'])->middleware('permission:procurement.update');
            Route::post('/{order}/close', [PurchaseOrderController::class, 'close'])->middleware('permission:procurement.approve');
        });

        // Purchase Receipts
        Route::prefix('receipts')->group(function () {
            Route::get('/', [PurchaseReceiptController::class, 'index']); // procurement.read
            Route::post('/', [PurchaseReceiptController::class, 'store'])->middleware('permission:procurement.receive');
            Route::get('/{receipt}', [PurchaseReceiptController::class, 'show']); // procurement.read
            Route::put('/{receipt}', [PurchaseReceiptController::class, 'update'])->middleware('permission:procurement.receive');
            Route::delete('/{receipt}', [PurchaseReceiptController::class, 'destroy'])->middleware('permission:procurement.delete');

            Route::post('/{receipt}/confirm', [PurchaseReceiptController::class, 'confirm'])->middleware('permission:procurement.approve');
        });

        // Purchase Returns
        Route::prefix('returns')->group(function () {
            Route::get('/', [PurchaseReturnController::class, 'index']); // procurement.read
            Route::post('/', [PurchaseReturnController::class, 'store'])->middleware('permission:procurement.return');
            Route::get('/{return}', [PurchaseReturnController::class, 'show']); // procurement.read
            Route::put('/{return}', [PurchaseReturnController::class, 'update'])->middleware('permission:procurement.return');
            Route::delete('/{return}', [PurchaseReturnController::class, 'destroy'])->middleware('permission:procurement.delete');

            Route::post('/{return}/approve', [PurchaseReturnController::class, 'approve'])->middleware('permission:procurement.approve');
            Route::post('/{return}/process', [PurchaseReturnController::class, 'process'])->middleware('permission:procurement.approve');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Accounting & Finance Routes
    |--------------------------------------------------------------------------
    | Module Access Control: accounting
    */
    Route::middleware(['module_access:accounting'])->prefix('accounting')->group(function () {

        // Chart of Accounts
        Route::prefix('accounts')->group(function () {
            Route::get('/', [ChartOfAccountController::class, 'index']); // accounting.read
            Route::post('/', [ChartOfAccountController::class, 'store'])->middleware('permission:accounting.create');
            Route::get('/{account}', [ChartOfAccountController::class, 'show']); // accounting.read
            Route::put('/{account}', [ChartOfAccountController::class, 'update'])->middleware('permission:accounting.update');
            Route::delete('/{account}', [ChartOfAccountController::class, 'destroy'])->middleware('permission:accounting.delete');
        });

        // Accounting Periods
        Route::prefix('periods')->group(function () {
            Route::get('/', [AccountingPeriodController::class, 'index']); // accounting.read
            Route::post('/', [AccountingPeriodController::class, 'store'])->middleware('permission:accounting.create');
            Route::get('/{period}', [AccountingPeriodController::class, 'show']); // accounting.read
            Route::put('/{period}', [AccountingPeriodController::class, 'update'])->middleware('permission:accounting.update');
            Route::delete('/{period}', [AccountingPeriodController::class, 'destroy'])->middleware('permission:accounting.delete');

            Route::post('/{period}/close', [AccountingPeriodController::class, 'close'])->middleware('permission:accounting.close_period');
            Route::post('/{period}/reopen', [AccountingPeriodController::class, 'reopen'])->middleware('permission:accounting.close_period');
        });

        // Journal Entries
        Route::prefix('journal-entries')->group(function () {
            Route::get('/', [JournalEntryController::class, 'index']); // accounting.read
            Route::post('/', [JournalEntryController::class, 'store'])->middleware('permission:accounting.create');
            Route::get('/{entry}', [JournalEntryController::class, 'show']); // accounting.read
            Route::put('/{entry}', [JournalEntryController::class, 'update'])->middleware('permission:accounting.update');
            Route::delete('/{entry}', [JournalEntryController::class, 'destroy'])->middleware('permission:accounting.delete');

            Route::post('/{entry}/post', [JournalEntryController::class, 'post'])->middleware('permission:accounting.approve');
            Route::post('/{entry}/reverse', [JournalEntryController::class, 'reverse'])->middleware('permission:accounting.approve');
        });

        // Customer Receivables
        Route::prefix('receivables')->group(function () {
            Route::get('/', [CustomerReceivableController::class, 'index']); // accounting.read
            Route::post('/', [CustomerReceivableController::class, 'store'])->middleware('permission:accounting.create');
            Route::get('/{receivable}', [CustomerReceivableController::class, 'show']); // accounting.read
            Route::put('/{receivable}', [CustomerReceivableController::class, 'update'])->middleware('permission:accounting.update');
            Route::delete('/{receivable}', [CustomerReceivableController::class, 'destroy'])->middleware('permission:accounting.delete');

            // Receivable Payments
            Route::prefix('{receivable}/payments')->group(function () {
                Route::get('/', [ReceivablePaymentController::class, 'index']); // accounting.read
                Route::post('/', [ReceivablePaymentController::class, 'store'])->middleware('permission:accounting.create');
                Route::get('/{payment}', [ReceivablePaymentController::class, 'show']); // accounting.read
                Route::put('/{payment}', [ReceivablePaymentController::class, 'update'])->middleware('permission:accounting.update');
                Route::delete('/{payment}', [ReceivablePaymentController::class, 'destroy'])->middleware('permission:accounting.delete');
            });
        });

        // Vendor Payables
        Route::prefix('payables')->group(function () {
            Route::get('/', [VendorPayableController::class, 'index']); // accounting.read
            Route::post('/', [VendorPayableController::class, 'store'])->middleware('permission:accounting.create');
            Route::get('/{payable}', [VendorPayableController::class, 'show']); // accounting.read
            Route::put('/{payable}', [VendorPayableController::class, 'update'])->middleware('permission:accounting.update');
            Route::delete('/{payable}', [VendorPayableController::class, 'destroy'])->middleware('permission:accounting.delete');

            // Payable Payments
            Route::prefix('{payable}/payments')->group(function () {
                Route::get('/', [PayablePaymentController::class, 'index']); // accounting.read
                Route::post('/', [PayablePaymentController::class, 'store'])->middleware('permission:accounting.create');
                Route::get('/{payment}', [PayablePaymentController::class, 'show']); // accounting.read
                Route::put('/{payment}', [PayablePaymentController::class, 'update'])->middleware('permission:accounting.update');
                Route::delete('/{payment}', [PayablePaymentController::class, 'destroy'])->middleware('permission:accounting.delete');
            });
        });

        // Tax Transactions
        Route::prefix('taxes')->group(function () {
            Route::get('/', [TaxTransactionController::class, 'index']); // accounting.read
            Route::post('/', [TaxTransactionController::class, 'store'])->middleware('permission:accounting.create');
            Route::get('/{tax}', [TaxTransactionController::class, 'show']); // accounting.read
            Route::put('/{tax}', [TaxTransactionController::class, 'update'])->middleware('permission:accounting.update');
            Route::delete('/{tax}', [TaxTransactionController::class, 'destroy'])->middleware('permission:accounting.delete');
        });

        // Fixed Assets
        Route::prefix('fixed-assets')->group(function () {
            Route::get('/', [FixedAssetController::class, 'index'])->middleware('permission:accounting.asset_management');
            Route::post('/', [FixedAssetController::class, 'store'])->middleware('permission:accounting.asset_management');
            Route::get('/{asset}', [FixedAssetController::class, 'show'])->middleware('permission:accounting.asset_management');
            Route::put('/{asset}', [FixedAssetController::class, 'update'])->middleware('permission:accounting.asset_management');
            Route::delete('/{asset}', [FixedAssetController::class, 'destroy'])->middleware('permission:accounting.asset_management');

            // Asset Depreciation
            Route::prefix('{asset}/depreciation')->group(function () {
                Route::get('/', [AssetDepreciationController::class, 'index'])->middleware('permission:accounting.asset_management');
                Route::post('/', [AssetDepreciationController::class, 'store'])->middleware('permission:accounting.asset_management');
                Route::get('/{depreciation}', [AssetDepreciationController::class, 'show'])->middleware('permission:accounting.asset_management');
                Route::put('/{depreciation}', [AssetDepreciationController::class, 'update'])->middleware('permission:accounting.asset_management');
                Route::delete('/{depreciation}', [AssetDepreciationController::class, 'destroy'])->middleware('permission:accounting.asset_management');
            });
        });

        // Budgets
        Route::middleware(['permission:accounting.budget'])->prefix('budgets')->group(function () {
            Route::get('/', [BudgetController::class, 'index']);
            Route::post('/', [BudgetController::class, 'store']);
            Route::get('/{budget}', [BudgetController::class, 'show']);
            Route::put('/{budget}', [BudgetController::class, 'update']);
            Route::delete('/{budget}', [BudgetController::class, 'destroy']);

            Route::post('/{budget}/approve', [BudgetController::class, 'approve']);
            Route::get('/{budget}/variance', [BudgetController::class, 'getVariance']);
        });

        // Bank Accounts
        Route::prefix('bank-accounts')->group(function () {
            Route::get('/', [BankAccountController::class, 'index']); // accounting.read
            Route::post('/', [BankAccountController::class, 'store'])->middleware('permission:accounting.create');
            Route::get('/{account}', [BankAccountController::class, 'show']); // accounting.read
            Route::put('/{account}', [BankAccountController::class, 'update'])->middleware('permission:accounting.update');
            Route::delete('/{account}', [BankAccountController::class, 'destroy'])->middleware('permission:accounting.delete');

            // Bank Reconciliation
            Route::prefix('{account}/reconciliation')->group(function () {
                Route::get('/', [BankReconciliationController::class, 'index'])->middleware('permission:accounting.reconcile');
                Route::post('/', [BankReconciliationController::class, 'store'])->middleware('permission:accounting.reconcile');
                Route::get('/{reconciliation}', [BankReconciliationController::class, 'show'])->middleware('permission:accounting.reconcile');
                Route::put('/{reconciliation}', [BankReconciliationController::class, 'update'])->middleware('permission:accounting.reconcile');
                Route::delete('/{reconciliation}', [BankReconciliationController::class, 'destroy'])->middleware('permission:accounting.reconcile');
            });
        });

        // Financial Reports
        Route::prefix('reports')->group(function () {
            Route::get('/trial-balance', [FinancialReportController::class, 'trialBalance']); // accounting.read
            Route::get('/income-statement', [FinancialReportController::class, 'incomeStatement']); // accounting.read
            Route::get('/balance-sheet', [FinancialReportController::class, 'balanceSheet']); // accounting.read
            Route::get('/cash-flow', [FinancialReportController::class, 'cashFlow']); // accounting.read
            Route::get('/general-ledger', [FinancialReportController::class, 'generalLedger']); // accounting.read
            Route::get('/accounts-receivable-aging', [FinancialReportController::class, 'receivableAging']); // accounting.read
            Route::get('/accounts-payable-aging', [FinancialReportController::class, 'payableAging']); // accounting.read
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Special Routes
    |--------------------------------------------------------------------------
    */

    // PDF Order Capture (AI Processing)
    Route::middleware(['permission:procurement.create,sales.create'])->prefix('ai')->group(function () {
        Route::post('/pdf-order-capture', [PDFOrderCaptureController::class, 'process']);
        Route::get('/pdf-order-capture/history', [PDFOrderCaptureController::class, 'getHistory']);
    });

    // Global Reports (Multi-module access)
    Route::middleware(['role:admin,super_admin,inventory_manager,production_manager,sales_manager,finance_manager'])->prefix('reports')->group(function () {
        Route::get('/executive-summary', [ReportController::class, 'executiveSummary']);
        Route::get('/kpi-dashboard', [ReportController::class, 'kpiDashboard']);
        Route::get('/cross-module-analytics', [ReportController::class, 'crossModuleAnalytics']);
        Route::post('/custom-report', [ReportController::class, 'generateCustomReport']);
    });

    // System Utilities
    Route::prefix('system')->group(function () {
        Route::get('/modules', function () {
            return response()->json([
                'data' => config('permissions.modules', [])
            ]);
        });

        Route::get('/user-navigation', function () {
            $authService = app(\App\Services\AuthService::class);
            return response()->json([
                'data' => $authService->getNavigationMenu()
            ]);
        });

        Route::get('/user-widgets', function () {
            $authService = app(\App\Services\AuthService::class);
            return response()->json([
                'data' => $authService->getDashboardWidgets()
            ]);
        });

        Route::get('/user-permissions-summary', function () {
            $authService = app(\App\Services\AuthService::class);
            return response()->json([
                'data' => $authService->getPermissionSummary()
            ]);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | File Upload/Download Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('files')->group(function () {
        Route::post('/upload', function (Request $request) {
            // Handle file upload with permission checking
            $request->validate([
                'file' => 'required|file|max:10240', // 10MB max
                'module' => 'string|nullable'
            ]);

            $module = $request->input('module', 'general');

            // Check upload permission for specific module
            $authService = app(\App\Services\AuthService::class);
            if (!$authService->hasPermission("{$module}.create") && !$authService->hasPermission('admin.create')) {
                return response()->json(['message' => 'Upload permission denied'], 403);
            }

            // Process file upload
            $file = $request->file('file');
            $path = $file->store("uploads/{$module}", 'public');

            return response()->json([
                'message' => 'File uploaded successfully',
                'data' => [
                    'path' => $path,
                    'url' => Storage::url($path),
                    'filename' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType()
                ]
            ]);
        });

        Route::get('/download/{module}/{filename}', function ($module, $filename) {
            $authService = app(\App\Services\AuthService::class);

            // Check download permission
            if (!$authService->hasPermission("{$module}.read") && !$authService->hasPermission('admin.read')) {
                return response()->json(['message' => 'Download permission denied'], 403);
            }

            $path = "uploads/{$module}/{$filename}";

            if (!Storage::disk('public')->exists($path)) {
                return response()->json(['message' => 'File not found'], 404);
            }

            return Storage::disk('public')->download($path);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Audit Log Routes (Super Admin Only)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:super_admin'])->prefix('audit')->group(function () {
        Route::get('/logs', function (Request $request) {
            // Return audit logs with filtering
            return response()->json([
                'data' => [],
                'message' => 'Audit logs retrieved successfully'
            ]);
        });

        Route::get('/user-activity/{user}', function ($userId, Request $request) {
            // Return specific user activity
            return response()->json([
                'data' => [],
                'message' => 'User activity retrieved successfully'
            ]);
        });
    });
});

/*
|--------------------------------------------------------------------------
| Fallback Route
|--------------------------------------------------------------------------
*/

Route::fallback(function () {
    return response()->json([
        'message' => 'API endpoint not found',
        'error' => 'The requested API endpoint does not exist'
    ], 404);
});
