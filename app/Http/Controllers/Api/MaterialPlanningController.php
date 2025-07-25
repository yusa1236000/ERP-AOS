<?php
// app/Http/Controllers/Api/MaterialPlanningController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaterialPlan;
use App\Models\Sales\SalesForecast;
use App\Models\Item;
use App\Models\Manufacturing\BOM;
use App\Models\Manufacturing\BOMLine;
use App\Models\Manufacturing\WorkOrder;
use App\Models\Manufacturing\WorkOrderOperation;
use App\Models\Manufacturing\Routing;
use App\Models\PurchaseRequisition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class MaterialPlanningController extends Controller
{
    /**
     * List material plans with optional filters and pagination
     */
    public function index(Request $request)
    {
        $query = MaterialPlan::with('item');

        // Apply filters if present
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('item', function ($q) use ($search) {
                $q->where('item_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('planningPeriod')) {
            $query->where('planning_period', $request->input('planningPeriod'));
        }

        if ($request->filled('materialType')) {
            $query->where('material_type', $request->input('materialType'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Get per_page parameter, default to 50, max 2000
        $perPage = min((int) $request->input('per_page', 50), 2000);
        $page = (int) $request->input('page', 1);
        if ($page < 1) {
            $page = 1;
        }

        $plans = $query->orderBy('planning_period', 'asc')
            ->orderBy('material_type', 'desc') // FG first, then RM
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($plans);
    }

    /**
     * Show a single material plan by id
     */
    public function show($id)
    {
        $plan = MaterialPlan::with(['item', 'bom', 'bom.bomLines', 'bom.bomLines.item', 'bom.bomLines.unitOfMeasure'])
            ->find($id);

        if (!$plan) {
            return response()->json(['message' => 'Material plan not found'], 404);
        }

        return response()->json(['data' => $plan]);
    }

    /**
     * Generate material plans based on sales forecasts for finished goods
     * and calculate raw material requirements using BOM with yield-based calculations
     */
    public function generateMaterialPlans(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_period' => 'required|date',
            'buffer_percentage' => 'required|numeric|min:0|max:100',
            'item_ids' => 'nullable|array',
            'item_ids.*' => 'exists:items,item_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $startPeriod = Carbon::parse($request->start_period)->startOfMonth();
        $endPeriod = $startPeriod->copy()->addMonths(5);
        $bufferPercentage = $request->buffer_percentage;

        try {
            DB::beginTransaction();

            // Step 1: Process Finished Goods Forecasts
            $fgPlans = $this->processFinishedGoodsForecasts(
                $startPeriod,
                $endPeriod,
                $bufferPercentage,
                $request->item_ids ?? []
            );

            // Step 2: Calculate Raw Material Requirements from BOM (FIXED for multi-level)
            $rawMaterialPlans = $this->calculateRawMaterialRequirements(
                $fgPlans,
                $bufferPercentage
            );

            DB::commit();

            return response()->json([
                'message' => count($fgPlans) . " finished goods plans and " .
                    count($rawMaterialPlans) . " material plans generated successfully",
                'data' => [
                    'finished_goods' => $fgPlans,
                    'materials' => $rawMaterialPlans
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to generate material plans', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Process forecasts for finished goods
     */
    private function processFinishedGoodsForecasts($startPeriod, $endPeriod, $bufferPercentage, $itemIds = [])
    {
        // Get forecasts for the specified period
        $query = SalesForecast::whereBetween('forecast_period', [
            $startPeriod->format('Y-m-d'),
            $endPeriod->format('Y-m-d')
        ])
            ->where('is_current_version', true)
            ->with('item');

        // Filter by items if specified
        if (!empty($itemIds)) {
            $query->whereIn('item_id', $itemIds);
        }

        // Group forecasts by item and period
        $forecasts = $query->get()
            ->groupBy(['item_id', function ($item) {
                return Carbon::parse($item->forecast_period)->format('Y-m-d');
            }]);

        $fgPlans = [];

        foreach ($forecasts as $itemId => $periodForecasts) {
            $item = Item::find($itemId);
            if (!$item) continue;

            // Check if this item has a BOM (is a finished good)
            $bom = $this->getActiveBOM($itemId);
            if (!$bom) continue; // Skip items without BOMs

            $availableStock = $item->current_stock;

            // Sort periods
            $periods = array_keys($periodForecasts->toArray());
            sort($periods);

            foreach ($periods as $periodIndex => $period) {
                $periodForecastsData = $forecasts[$itemId][$period];

                // Calculate total forecast for this period
                $totalForecast = $periodForecastsData->sum('forecast_quantity');

                // Get WIP stock
                $wipStock = $this->getWIPStock($itemId, $period);

                // Calculate buffer using NEXT PERIOD's forecast
                $bufferQty = 0;
                $nextPeriodForecast = 0;

                // Check if there's a next period
                if (isset($periods[$periodIndex + 1])) {
                    $nextPeriod = $periods[$periodIndex + 1];
                    $nextPeriodForecastsData = $forecasts[$itemId][$nextPeriod];
                    $nextPeriodForecast = $nextPeriodForecastsData->sum('forecast_quantity');
                    $bufferQty = ($nextPeriodForecast * $bufferPercentage) / 100;
                } else {
                    // If this is the last period, try to get forecast from one month ahead
                    $currentPeriodDate = Carbon::parse($period);
                    $nextMonthPeriod = $currentPeriodDate->copy()->addMonth()->format('Y-m-d');

                    // Query for next month's forecast
                    $nextMonthForecast = SalesForecast::where('item_id', $itemId)
                        ->where('forecast_period', $nextMonthPeriod)
                        ->where('is_current_version', true)
                        ->sum('forecast_quantity');

                    if ($nextMonthForecast > 0) {
                        $nextPeriodForecast = $nextMonthForecast;
                        $bufferQty = ($nextPeriodForecast * $bufferPercentage) / 100;
                    } else {
                        // Fallback: use current period if no next period data available
                        $nextPeriodForecast = $totalForecast;
                        $bufferQty = ($totalForecast * $bufferPercentage) / 100;
                    }
                }

                // Calculate net requirement
                $netRequirement = $totalForecast - $availableStock - $wipStock + $bufferQty;
                $netRequirement = max(0, $netRequirement);
                $netRequirement = ceil($netRequirement);

                // Create or update material plan
                $plan = MaterialPlan::updateOrCreate(
                    [
                        'item_id' => $itemId,
                        'planning_period' => $period,
                        'material_type' => 'FG', // Finished Good
                    ],
                    [
                        'forecast_quantity' => $totalForecast,
                        'available_stock' => $availableStock,
                        'wip_stock' => $wipStock,
                        'buffer_percentage' => $bufferPercentage,
                        'buffer_quantity' => $bufferQty,
                        'net_requirement' => $netRequirement,
                        'planned_order_quantity' => $netRequirement,
                        'bom_id' => $bom->bom_id,
                        'status' => 'Draft',
                        // Optional: Store next period forecast for reference
                        'notes' => "Buffer based on next period forecast: {$nextPeriodForecast}"
                    ]
                );

                $fgPlans[] = $plan;

                // Update available stock for next period
                // Available = current - forecast + planned order
                $availableStock = $availableStock - $totalForecast + $netRequirement;
            }
        }

        return $fgPlans;
    }

    /**
     * Calculate raw material requirements based on BOM (with yield-based calculations)
     * FIXED: Support for multi-level BOM - Components with BOM are treated as FG/Sub-Assembly
     */
    private function calculateRawMaterialRequirements($fgPlans, $bufferPercentage)
    {
        $materialRequirements = [];
        $processedSubAssemblies = []; // Track sub-assemblies to avoid duplicate processing

        // Group FG plans by period
        $periodPlans = collect($fgPlans)->groupBy(function ($plan) {
            return $plan->planning_period;
        });

        foreach ($periodPlans as $period => $plans) {
            $materialNeeds = [];
            $subAssemblyNeeds = [];

            // For each FG in the period, explode its BOM
            foreach ($plans as $plan) {
                if ($plan->net_requirement <= 0) continue;

                $bom = BOM::with('bomLines.item')->find($plan->bom_id);
                if (!$bom) continue;

                $productionQty = $plan->planned_order_quantity;

                // Calculate materials needed based on BOM
                foreach ($bom->bomLines as $bomLine) {
                    $componentItemId = $bomLine->item_id;
                    $componentItem = $bomLine->item;

                    // Check if this component has its own BOM (multi-level BOM)
                    $componentBOM = $this->getActiveBOM($componentItemId);
                    $isSubAssembly = !is_null($componentBOM);

                    // Calculate required quantity for this component
                    if ($bomLine->is_yield_based) {
                        // For yield-based: How many units of material needed to produce desired quantity
                        $shrinkageFactor = $bomLine->shrinkage_factor ?? 0;
                        $effectiveFactor = (1 - $shrinkageFactor);

                        if ($bomLine->yield_ratio > 0) {
                            $requiredQty = $productionQty / $bomLine->yield_ratio / $effectiveFactor;
                        } else {
                            $requiredQty = $bomLine->quantity * ($productionQty / $bom->standard_quantity);
                        }
                    } else {
                        // Traditional BOM calculation
                        $requiredQty = ($bomLine->quantity / $bom->standard_quantity) * $productionQty;
                    }

                    if ($isSubAssembly) {
                        // This component is a sub-assembly (has its own BOM)
                        // Treat it as a Finished Good that needs to be produced
                        if (!isset($subAssemblyNeeds[$componentItemId])) {
                            $subAssemblyNeeds[$componentItemId] = 0;
                        }
                        $subAssemblyNeeds[$componentItemId] += $requiredQty;
                    } else {
                        // This is a true raw material (no BOM)
                        if (!isset($materialNeeds[$componentItemId])) {
                            $materialNeeds[$componentItemId] = 0;
                        }
                        $materialNeeds[$componentItemId] += $requiredQty;
                    }
                }
            }

            // Create material plans for sub-assemblies (FG)
            foreach ($subAssemblyNeeds as $subAssemblyItemId => $requiredQty) {
                $subAssembly = Item::find($subAssemblyItemId);
                if (!$subAssembly) continue;

                $availableStock = $subAssembly->current_stock;
                $wipStock = $this->getWIPStock($subAssemblyItemId, $period);

                // Calculate buffer
                $bufferQty = ($requiredQty * $bufferPercentage) / 100;

                // Calculate net requirement
                $netRequirement = $requiredQty - $availableStock - $wipStock + $bufferQty;
                $netRequirement = max(0, $netRequirement);
                $netRequirement = ceil($netRequirement);

                $subAssemblyBOM = $this->getActiveBOM($subAssemblyItemId);

                // Create or update material plan for sub-assembly
                $plan = MaterialPlan::updateOrCreate(
                    [
                        'item_id' => $subAssemblyItemId,
                        'planning_period' => $period,
                        'material_type' => 'FG', // Sub-assembly is treated as Finished Good
                    ],
                    [
                        'forecast_quantity' => $requiredQty,
                        'available_stock' => $availableStock,
                        'wip_stock' => $wipStock,
                        'buffer_percentage' => $bufferPercentage,
                        'buffer_quantity' => $bufferQty,
                        'net_requirement' => $netRequirement,
                        'planned_order_quantity' => $netRequirement,
                        'bom_id' => $subAssemblyBOM ? $subAssemblyBOM->bom_id : null,
                        'status' => 'Draft'
                    ]
                );

                $materialRequirements[] = $plan;

                // Track processed sub-assemblies
                $processedSubAssemblies[] = $subAssemblyItemId;

                // Recursively explode sub-assembly BOM if it has net requirement > 0
                if ($netRequirement > 0 && $subAssemblyBOM) {
                    $subAssemblyRequirements = $this->explodeSubAssemblyBOM(
                        $subAssemblyBOM,
                        $netRequirement,
                        $period,
                        $bufferPercentage,
                        $processedSubAssemblies
                    );
                    $materialRequirements = array_merge($materialRequirements, $subAssemblyRequirements);
                }
            }

            // Create material plans for true raw materials (RM)
            foreach ($materialNeeds as $materialItemId => $requiredQty) {
                $material = Item::find($materialItemId);
                if (!$material) continue;

                $availableStock = $material->current_stock;

                // Calculate buffer
                $bufferQty = ($requiredQty * $bufferPercentage) / 100;

                // Calculate net requirement
                $netRequirement = $requiredQty - $availableStock + $bufferQty;
                $netRequirement = max(0, $netRequirement);
                $netRequirement = ceil($netRequirement);

                // Create or update material plan for raw material
                $plan = MaterialPlan::updateOrCreate(
                    [
                        'item_id' => $materialItemId,
                        'planning_period' => $period,
                        'material_type' => 'RM', // True Raw Material
                    ],
                    [
                        'forecast_quantity' => $requiredQty,
                        'available_stock' => $availableStock,
                        'wip_stock' => 0, // Typically not applicable for raw materials
                        'buffer_percentage' => $bufferPercentage,
                        'buffer_quantity' => $bufferQty,
                        'net_requirement' => $netRequirement,
                        'planned_order_quantity' => $netRequirement,
                        'bom_id' => null, // Raw materials don't have BOMs
                        'status' => 'Draft'
                    ]
                );

                $materialRequirements[] = $plan;
            }
        }

        return $materialRequirements;
    }

    /**
     * NEW METHOD: Recursively explode sub-assembly BOM to get raw material requirements
     */
    private function explodeSubAssemblyBOM($subAssemblyBOM, $requiredQuantity, $period, $bufferPercentage, &$processedSubAssemblies)
    {
        $requirements = [];
        $materialNeeds = [];
        $subAssemblyNeeds = [];

        // Calculate materials needed for this sub-assembly
        foreach ($subAssemblyBOM->bomLines as $bomLine) {
            $componentItemId = $bomLine->item_id;
            $componentItem = $bomLine->item;

            // Check if this component has its own BOM
            $componentBOM = $this->getActiveBOM($componentItemId);
            $isSubAssembly = !is_null($componentBOM);

            // Calculate required quantity for this component
            if ($bomLine->is_yield_based) {
                $shrinkageFactor = $bomLine->shrinkage_factor ?? 0;
                $effectiveFactor = (1 - $shrinkageFactor);

                if ($bomLine->yield_ratio > 0) {
                    $componentRequiredQty = $requiredQuantity / $bomLine->yield_ratio / $effectiveFactor;
                } else {
                    $componentRequiredQty = $bomLine->quantity * ($requiredQuantity / $subAssemblyBOM->standard_quantity);
                }
            } else {
                $componentRequiredQty = ($bomLine->quantity / $subAssemblyBOM->standard_quantity) * $requiredQuantity;
            }

            if ($isSubAssembly && !in_array($componentItemId, $processedSubAssemblies)) {
                // This is another sub-assembly
                if (!isset($subAssemblyNeeds[$componentItemId])) {
                    $subAssemblyNeeds[$componentItemId] = 0;
                }
                $subAssemblyNeeds[$componentItemId] += $componentRequiredQty;
            } else {
                // This is a raw material
                if (!isset($materialNeeds[$componentItemId])) {
                    $materialNeeds[$componentItemId] = 0;
                }
                $materialNeeds[$componentItemId] += $componentRequiredQty;
            }
        }

        // Process sub-assemblies
        foreach ($subAssemblyNeeds as $subItemId => $reqQty) {
            $subItem = Item::find($subItemId);
            if (!$subItem) continue;

            $availableStock = $subItem->current_stock;
            $wipStock = $this->getWIPStock($subItemId, $period);
            $bufferQty = ($reqQty * $bufferPercentage) / 100;
            $netRequirement = $reqQty - $availableStock - $wipStock + $bufferQty;
            $netRequirement = max(0, ceil($netRequirement));

            $subBOM = $this->getActiveBOM($subItemId);

            $plan = MaterialPlan::updateOrCreate(
                [
                    'item_id' => $subItemId,
                    'planning_period' => $period,
                    'material_type' => 'FG',
                ],
                [
                    'forecast_quantity' => $reqQty,
                    'available_stock' => $availableStock,
                    'wip_stock' => $wipStock,
                    'buffer_percentage' => $bufferPercentage,
                    'buffer_quantity' => $bufferQty,
                    'net_requirement' => $netRequirement,
                    'planned_order_quantity' => $netRequirement,
                    'bom_id' => $subBOM ? $subBOM->bom_id : null,
                    'status' => 'Draft'
                ]
            );

            $requirements[] = $plan;
            $processedSubAssemblies[] = $subItemId;

            // Recursively explode if needed
            if ($netRequirement > 0 && $subBOM) {
                $subRequirements = $this->explodeSubAssemblyBOM(
                    $subBOM,
                    $netRequirement,
                    $period,
                    $bufferPercentage,
                    $processedSubAssemblies
                );
                $requirements = array_merge($requirements, $subRequirements);
            }
        }

        // Process raw materials
        foreach ($materialNeeds as $materialItemId => $reqQty) {
            $material = Item::find($materialItemId);
            if (!$material) continue;

            $availableStock = $material->current_stock;
            $bufferQty = ($reqQty * $bufferPercentage) / 100;
            $netRequirement = $reqQty - $availableStock + $bufferQty;
            $netRequirement = max(0, ceil($netRequirement));

            $plan = MaterialPlan::updateOrCreate(
                [
                    'item_id' => $materialItemId,
                    'planning_period' => $period,
                    'material_type' => 'RM',
                ],
                [
                    'forecast_quantity' => $reqQty,
                    'available_stock' => $availableStock,
                    'wip_stock' => 0,
                    'buffer_percentage' => $bufferPercentage,
                    'buffer_quantity' => $bufferQty,
                    'net_requirement' => $netRequirement,
                    'planned_order_quantity' => $netRequirement,
                    'bom_id' => null,
                    'status' => 'Draft'
                ]
            );

            $requirements[] = $plan;
        }

        return $requirements;
    }

    /**
     * NEW METHOD: Get BOM explosion summary for analysis
     */
    public function getBOMExplosion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bom_id' => 'required|exists:boms,bom_id',
            'quantity' => 'required|numeric|min:0.01',
            'levels' => 'sometimes|integer|min:1|max:10'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $bom = BOM::with(['item', 'unitOfMeasure'])->find($request->bom_id);
            $quantity = $request->quantity;
            $maxLevels = $request->levels ?? 5;

            if (!$bom) {
                return response()->json(['message' => 'BOM not found'], 404);
            }

            $explosion = $this->explodeBOMRecursive($bom, $quantity, 1, $maxLevels);

            return response()->json([
                'data' => [
                    'parent_item' => [
                        'item_id' => $bom->item->item_id,
                        'item_code' => $bom->item->item_code,
                        'name' => $bom->item->name,
                        'quantity' => $quantity,
                        'uom' => $bom->unitOfMeasure->symbol
                    ],
                    'explosion' => $explosion,
                    'summary' => $this->getBOMSummary($explosion)
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to explode BOM', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Recursively explode BOM for analysis
     */
    private function explodeBOMRecursive($bom, $quantity, $level, $maxLevels, $processed = [])
    {
        if ($level > $maxLevels || in_array($bom->bom_id, $processed)) {
            return [];
        }

        $processed[] = $bom->bom_id;
        $explosion = [];

        foreach ($bom->bomLines as $bomLine) {
            $componentItem = $bomLine->item;
            $componentBOM = $this->getActiveBOM($componentItem->item_id);

            // Calculate required quantity
            if ($bomLine->is_yield_based) {
                $shrinkageFactor = $bomLine->shrinkage_factor ?? 0;
                $effectiveFactor = (1 - $shrinkageFactor);

                if ($bomLine->yield_ratio > 0) {
                    $requiredQty = $quantity / $bomLine->yield_ratio / $effectiveFactor;
                } else {
                    $requiredQty = $bomLine->quantity * ($quantity / $bom->standard_quantity);
                }
            } else {
                $requiredQty = ($bomLine->quantity / $bom->standard_quantity) * $quantity;
            }

            $componentData = [
                'level' => $level,
                'item_id' => $componentItem->item_id,
                'item_code' => $componentItem->item_code,
                'name' => $componentItem->name,
                'quantity_in_bom' => $bomLine->quantity,
                'required_quantity' => $requiredQty,
                'uom' => $bomLine->unitOfMeasure->symbol,
                'has_bom' => !is_null($componentBOM),
                'material_type' => $componentBOM ? 'FG/Sub-Assembly' : 'Raw Material',
                'is_yield_based' => $bomLine->is_yield_based,
                'yield_ratio' => $bomLine->yield_ratio,
                'shrinkage_factor' => $bomLine->shrinkage_factor,
                'current_stock' => $componentItem->current_stock,
                'children' => []
            ];

            // Recursively explode if component has BOM
            if ($componentBOM && $level < $maxLevels) {
                $componentData['children'] = $this->explodeBOMRecursive(
                    $componentBOM,
                    $requiredQty,
                    $level + 1,
                    $maxLevels,
                    $processed
                );
            }

            $explosion[] = $componentData;
        }

        return $explosion;
    }

    /**
     * Get BOM explosion summary
     */
    private function getBOMSummary($explosion)
    {
        $summary = [
            'total_components' => 0,
            'raw_materials' => 0,
            'sub_assemblies' => 0,
            'max_level' => 0,
            'yield_based_components' => 0
        ];

        $this->processSummaryRecursive($explosion, $summary);

        return $summary;
    }

    /**
     * Process summary recursively
     */
    private function processSummaryRecursive($explosion, &$summary)
    {
        foreach ($explosion as $component) {
            $summary['total_components']++;
            $summary['max_level'] = max($summary['max_level'], $component['level']);

            if ($component['has_bom']) {
                $summary['sub_assemblies']++;
            } else {
                $summary['raw_materials']++;
            }

            if ($component['is_yield_based']) {
                $summary['yield_based_components']++;
            }

            if (!empty($component['children'])) {
                $this->processSummaryRecursive($component['children'], $summary);
            }
        }
    }

    /**
     * Get active BOM for an item
     */
    private function getActiveBOM($itemId)
    {
        return BOM::where('item_id', $itemId)
            ->where('status', 'Active')
            ->orderBy('effective_date', 'desc')
            ->first();
    }

    /**
     * Get WIP (Work in Progress) stock for an item
     */
    private function getWIPStock($itemId, $period)
    {
        $periodDate = Carbon::parse($period);
        $periodStartDate = $periodDate->copy()->startOfMonth();
        $periodEndDate = $periodDate->copy()->endOfMonth();

        // 1. Get Work Orders yang sedang dalam proses untuk item ini
        $wipFromWorkOrders = DB::table('work_orders')
            ->where('item_id', $itemId)
            ->whereIn('status', ['In Progress', 'Started']) // Status WIP
            ->where('planned_end_date', '<=', $periodEndDate) // Selesai dalam periode ini
            ->sum('planned_quantity');

        // 2. Get Production Orders yang sedang dalam proses
        $wipFromProductionOrders = DB::table('production_orders')
            ->where('production_orders.status', 'In Process')
            ->join('work_orders', 'production_orders.wo_id', '=', 'work_orders.wo_id')
            ->where('work_orders.item_id', $itemId)
            ->where('production_orders.production_date', '<=', $periodEndDate)
            ->sum(DB::raw('production_orders.planned_quantity - COALESCE(production_orders.actual_quantity, 0)'));

        // 3. Hitung perkiraan WIP berdasarkan persentase penyelesaian operasi
        $wipFromOperations = DB::table('work_order_operations')
            ->join('work_orders', 'work_order_operations.wo_id', '=', 'work_orders.wo_id')
            ->where('work_orders.item_id', $itemId)
            ->whereIn('work_orders.status', ['In Progress', 'Started'])
            ->where('work_orders.planned_end_date', '<=', $periodEndDate)
            ->select(
                'work_orders.wo_id',
                'work_orders.planned_quantity',
                DB::raw("COUNT(CASE WHEN work_order_operations.status = 'Completed' THEN 1 END) as completed_operations"),
                DB::raw('COUNT(*) as total_operations')
            )
            ->groupBy('work_orders.wo_id', 'work_orders.planned_quantity')
            ->get()
            ->map(function ($wo) {
                // Hitung persentase penyelesaian
                $completionPercent = $wo->total_operations > 0
                    ? $wo->completed_operations / $wo->total_operations
                    : 0;

                // Hitung WIP berdasarkan persentase penyelesaian
                return $wo->planned_quantity * $completionPercent;
            })
            ->sum();

        // Ambil nilai tertinggi dari ketiga metode perhitungan
        // (atau bisa juga gunakan rata-rata atau metode lain sesuai kebutuhan bisnis)
        return max($wipFromWorkOrders, $wipFromProductionOrders, $wipFromOperations);
    }

    /**
     * Calculate maximum production capacity based on available materials
     */
    public function calculateMaximumProduction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bom_id' => 'required|exists:boms,bom_id',
            'check_stock' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $bom = BOM::with(['item', 'unitOfMeasure', 'bomLines.item', 'bomLines.unitOfMeasure'])
                ->find($request->bom_id);

            if (!$bom) {
                return response()->json(['message' => 'BOM not found'], 404);
            }

            // Check if there are yield-based lines
            $yieldBasedLines = $bom->bomLines->where('is_yield_based', true);
            $hasYieldLines = $yieldBasedLines->count() > 0;

            $materials = [];
            $maxProduction = null;
            $checkStock = $request->check_stock ?? true;

            foreach ($bom->bomLines as $line) {
                $item = $line->item;
                $currentStock = $checkStock ? $item->current_stock : null;

                if ($line->is_yield_based) {
                    // For yield-based materials
                    $shrinkageFactor = $line->shrinkage_factor ?? 0;
                    $effectiveFactor = (1 - $shrinkageFactor);

                    if ($checkStock && $currentStock !== null) {
                        // How many products can be made from current stock
                        $effectiveStock = $currentStock * $effectiveFactor;
                        $potentialYield = $line->yield_ratio * $effectiveStock;
                    } else {
                        // How many products could be made from the quantity in BOM
                        $effectiveQty = $line->quantity * $effectiveFactor;
                        $potentialYield = $line->yield_ratio * $effectiveQty;
                    }
                } else {
                    // For traditional BOM materials
                    if ($checkStock && $currentStock !== null) {
                        // How many standard batches can be made from current stock
                        $potentialBatches = $currentStock / $line->quantity;
                        $potentialYield = $potentialBatches * $bom->standard_quantity;
                    } else {
                        // This is fixed at standard_quantity for traditional BOM
                        $potentialYield = $bom->standard_quantity;
                    }
                }

                // Format material data for response
                $materialData = [
                    'item_id' => $item->item_id,
                    'item_code' => $item->item_code,
                    'item_name' => $item->name,
                    'quantity_in_bom' => $line->quantity,
                    'uom' => $line->unitOfMeasure->symbol,
                    'is_yield_based' => $line->is_yield_based
                ];

                if ($line->is_yield_based) {
                    $materialData['yield_ratio'] = $line->yield_ratio;
                    $materialData['shrinkage_factor'] = $line->shrinkage_factor ?? 0;
                }

                if ($checkStock) {
                    $materialData['current_stock'] = $currentStock;
                    $materialData['potential_yield'] = $potentialYield;

                    // Track the minimum potential yield across all materials
                    // This represents the maximum possible production
                    if ($maxProduction === null || $potentialYield < $maxProduction) {
                        $maxProduction = $potentialYield;
                    }
                }

                $materials[] = $materialData;
            }

            // Result data
            $result = [
                'finished_product' => [
                    'item_id' => $bom->item->item_id,
                    'item_code' => $bom->item->item_code,
                    'item_name' => $bom->item->name,
                    'bom_id' => $bom->bom_id,
                    'bom_code' => $bom->bom_code,
                    'standard_quantity' => $bom->standard_quantity,
                    'uom' => $bom->unitOfMeasure->symbol,
                ],
                'has_yield_based_materials' => $hasYieldLines,
                'materials' => $materials
            ];

            if ($checkStock) {
                $result['maximum_yield'] = $maxProduction;
            }

            return response()->json([
                'data' => $result,
                'message' => 'Maximum production capacity calculated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to calculate production capacity', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate purchase requisitions from material plans (for Raw Materials)
     * PER ITEM - Modified to support single item generation
     */
    public function generatePurchaseRequisitions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period' => 'required|date',
            'lead_time_days' => 'required|integer|min:0',
            'item_id' => 'sometimes|exists:items,item_id', // Optional: for single item generation
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Build query for material plans
            $plansQuery = MaterialPlan::where('planning_period', $request->period)
                ->where('material_type', 'RM')  // Only raw materials
                ->where('net_requirement', '>', 0)
                ->where('status', 'Draft')
                ->with('item');

            // If item_id is provided, filter for that specific item
            if ($request->filled('item_id')) {
                $plansQuery->where('item_id', $request->item_id);
            }

            $plans = $plansQuery->get();

            if ($plans->isEmpty()) {
                $message = $request->filled('item_id')
                    ? 'No material plan found for this item requiring purchase'
                    : 'No material plans found requiring purchase';
                return response()->json(['message' => $message], 404);
            }

            $prNumber = $this->generatePRNumber();
            $requiredDate = Carbon::parse($request->period)->addDays($request->lead_time_days);

            // Create purchase requisition header
            $notesSuffix = $request->filled('item_id') ? ' (Single Item)' : '';
            $pr = PurchaseRequisition::create([
                'pr_number' => $prNumber,
                'pr_date' => now(),
                'requester_id' => auth()->id() ?? 1, // Use authenticated user or default
                'status' => 'draft',
                'notes' => 'Auto-generated from material planning' . $notesSuffix
            ]);

            // Create PR lines
            foreach ($plans as $plan) {
                // Create PR line
                $pr->lines()->create([
                    'item_id' => $plan->item_id,
                    'quantity' => $plan->planned_order_quantity,
                    'uom_id' => $plan->item->uom_id,
                    'required_date' => $requiredDate,
                    'notes' => "Based on material planning for {$request->period}"
                ]);

                // Update plan status
                $plan->update(['status' => 'Requisitioned']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Purchase requisition generated successfully',
                'data' => $pr->load(['lines.item'])
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to generate purchase requisition', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate purchase requisitions by period (for all items in a period)
     * NEW METHOD FOR PERIOD-BASED GENERATION
     */
    public function generatePurchaseRequisitionsByPeriod(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period' => 'required|date',
            'material_type' => 'required|in:RM,ALL',
            'lead_time_days' => 'required|integer|min:0',
            'only_draft_status' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $period = Carbon::parse($request->period)->format('Y-m-d');
            $materialType = $request->material_type;
            $onlyDraft = $request->only_draft_status ?? true;

            // Build query for material plans
            $plansQuery = MaterialPlan::where('planning_period', $period)
                ->where('net_requirement', '>', 0)
                ->with('item');

            // Filter by material type
            if ($materialType === 'RM') {
                $plansQuery->where('material_type', 'RM');
            } elseif ($materialType === 'ALL') {
                // Include all types with positive net requirement
                $plansQuery->whereIn('material_type', ['RM', 'FG']);
            }

            // Filter by status if requested
            if ($onlyDraft) {
                $plansQuery->where('status', 'Draft');
            }

            $plans = $plansQuery->get();

            if ($plans->isEmpty()) {
                return response()->json([
                    'message' => 'No material plans found requiring purchase for the selected period and criteria'
                ], 404);
            }

            $prNumber = $this->generatePRNumber();
            $requiredDate = Carbon::parse($request->period)->addDays($request->lead_time_days);

            // Create purchase requisition header
            $pr = PurchaseRequisition::create([
                'pr_number' => $prNumber,
                'pr_date' => now(),
                'requester_id' => auth()->id() ?? 1, // Use authenticated user or default
                'status' => 'draft',
                'notes' => "Auto-generated from material planning for period {$period} (Material Type: {$materialType})"
            ]);

            $processedCount = 0;

            // Create PR lines
            foreach ($plans as $plan) {
                // Create PR line
                $pr->lines()->create([
                    'item_id' => $plan->item_id,
                    'quantity' => $plan->planned_order_quantity,
                    'uom_id' => $plan->item->uom_id,
                    'required_date' => $requiredDate,
                    'notes' => "Based on material planning for {$period} - {$plan->material_type}"
                ]);

                // Update plan status
                $plan->update(['status' => 'Requisitioned']);
                $processedCount++;
            }

            DB::commit();

            return response()->json([
                'message' => "Purchase requisition generated successfully for {$processedCount} items",
                'data' => $pr->load(['lines.item'])
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to generate purchase requisition by period',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate job orders from material plans (for Finished Goods)
     * PER ITEM - Modified to support single item generation
     */
    public function generateWorkOrders(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period' => 'required|date',
            'planned_start_date' => 'required|date',
            'lead_time_days' => 'required|integer|min:1',
            'item_id' => 'sometimes|exists:items,item_id', // Optional: for single item generation
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Build query for material plans
            $plansQuery = MaterialPlan::where('planning_period', $request->period)
                ->where('material_type', 'FG')  // Only finished goods
                ->where('net_requirement', '>', 0)
                ->where('status', 'Draft')
                ->with(['item', 'bom']);

            // If item_id is provided, filter for that specific item
            if ($request->filled('item_id')) {
                $plansQuery->where('item_id', $request->item_id);
            }

            $plans = $plansQuery->get();

            if ($plans->isEmpty()) {
                $message = $request->filled('item_id')
                    ? 'No finished goods plan found for this item requiring production'
                    : 'No finished goods plans found requiring production';
                return response()->json(['message' => $message], 404);
            }

            $plannedStartDate = Carbon::parse($request->planned_start_date);
            $plannedEndDate = $plannedStartDate->copy()->addDays($request->lead_time_days);
            $workOrders = [];

            foreach ($plans as $plan) {
                // Validate that the item has a BOM
                if (!$plan->bom_id || !$plan->bom) {
                    continue; // Skip items without BOM
                }

                // Get active routing for the item
                $routing = Routing::where('item_id', $plan->item_id)
                    ->where('status', 'Active')
                    ->orderBy('effective_date', 'desc')
                    ->first();

                if (!$routing) {
                    continue; // Skip items without routing
                }

                $woNumber = $this->generateWONumber();

                // Create job order
                $workOrder = WorkOrder::create([
                    'wo_number' => $woNumber,
                    'wo_date' => now(),
                    'item_id' => $plan->item_id,
                    'bom_id' => $plan->bom_id,
                    'routing_id' => $routing->routing_id,
                    'planned_quantity' => $plan->planned_order_quantity,
                    'planned_start_date' => $plannedStartDate,
                    'planned_end_date' => $plannedEndDate,
                    'status' => 'Planned',
                ]);

                // Create work order operations based on routing operations
                $routingOperations = $routing->routingOperations()->orderBy('sequence')->get();
                $operationStartDate = $plannedStartDate->copy();

                foreach ($routingOperations as $routingOperation) {
                    $operationEndDate = $operationStartDate->copy()->addHours($routingOperation->setup_time + $routingOperation->run_time);

                    WorkOrderOperation::create([
                        'wo_id' => $workOrder->wo_id,
                        'routing_operation_id' => $routingOperation->operation_id,
                        'scheduled_start' => $operationStartDate,
                        'scheduled_end' => $operationEndDate,
                        'actual_start' => null,
                        'actual_end' => null,
                        'actual_labor_time' => 0,
                        'actual_machine_time' => 0,
                        'status' => 'Pending',
                    ]);

                    // Next operation starts after this one ends
                    $operationStartDate = $operationEndDate->copy();
                }

                // Update plan status
                $plan->update(['status' => 'Job Order Created']);

                $workOrders[] = $workOrder->load(['item', 'bom', 'routing']);
            }

            DB::commit();

            if (empty($workOrders)) {
                return response()->json(['message' => 'No job orders could be generated. Please check that items have valid BOM and Routing.'], 400);
            }

            return response()->json([
                'message' => count($workOrders) . ' job order(s) generated successfully',
                'data' => $workOrders
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to generate job orders', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate job orders by period (for all items in a period)
     * NEW METHOD FOR PERIOD-BASED GENERATION
     */
    public function generateWorkOrdersByPeriod(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period' => 'required|date',
            'material_type' => 'required|in:FG,ALL',
            'planned_start_date' => 'required|date',
            'lead_time_days' => 'required|integer|min:1',
            'only_draft_status' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $period = Carbon::parse($request->period)->format('Y-m-d');
            $materialType = $request->material_type;
            $onlyDraft = $request->only_draft_status ?? true;

            // Build query for material plans
            $plansQuery = MaterialPlan::where('planning_period', $period)
                ->where('net_requirement', '>', 0)
                ->with(['item', 'bom']);

            // Filter by material type
            if ($materialType === 'FG') {
                $plansQuery->where('material_type', 'FG');
            } elseif ($materialType === 'ALL') {
                // Include all types with positive net requirement that have BOMs
                $plansQuery->whereIn('material_type', ['FG', 'RM'])
                    ->whereNotNull('bom_id');
            }

            // Filter by status if requested
            if ($onlyDraft) {
                $plansQuery->where('status', 'Draft');
            }

            $plans = $plansQuery->get();

            if ($plans->isEmpty()) {
                return response()->json([
                    'message' => 'No material plans found requiring production for the selected period and criteria'
                ], 404);
            }

            $plannedStartDate = Carbon::parse($request->planned_start_date);
            $plannedEndDate = $plannedStartDate->copy()->addDays($request->lead_time_days);
            $workOrders = [];
            $skippedItems = [];

            foreach ($plans as $plan) {
                // Validate that the item has a BOM
                if (!$plan->bom_id || !$plan->bom) {
                    $skippedItems[] = $plan->item->item_code . ' (No BOM)';
                    continue;
                }

                // Get active routing for the item
                $routing = Routing::where('item_id', $plan->item_id)
                    ->where('status', 'Active')
                    ->orderBy('effective_date', 'desc')
                    ->first();

                if (!$routing) {
                    $skippedItems[] = $plan->item->item_code . ' (No Routing)';
                    continue;
                }

                $woNumber = $this->generateWONumber();

                // Create job order
                $workOrder = WorkOrder::create([
                    'wo_number' => $woNumber,
                    'wo_date' => now(),
                    'item_id' => $plan->item_id,
                    'bom_id' => $plan->bom_id,
                    'routing_id' => $routing->routing_id,
                    'planned_quantity' => $plan->planned_order_quantity,
                    'planned_start_date' => $plannedStartDate,
                    'planned_end_date' => $plannedEndDate,
                    'status' => 'Planned',
                ]);

                // Create work order operations based on routing operations
                $routingOperations = $routing->routingOperations()->orderBy('sequence')->get();
                $operationStartDate = $plannedStartDate->copy();

                foreach ($routingOperations as $routingOperation) {
                    $operationEndDate = $operationStartDate->copy()->addHours($routingOperation->setup_time + $routingOperation->run_time);

                    WorkOrderOperation::create([
                        'wo_id' => $workOrder->wo_id,
                        'routing_operation_id' => $routingOperation->operation_id,
                        'scheduled_start' => $operationStartDate,
                        'scheduled_end' => $operationEndDate,
                        'actual_start' => null,
                        'actual_end' => null,
                        'actual_labor_time' => 0,
                        'actual_machine_time' => 0,
                        'status' => 'Pending',
                    ]);

                    // Next operation starts after this one ends
                    $operationStartDate = $operationEndDate->copy();
                }

                // Update plan status
                $plan->update(['status' => 'Job Order Created']);

                $workOrders[] = $workOrder;
            }

            DB::commit();

            $message = count($workOrders) . ' job order(s) generated successfully';
            if (!empty($skippedItems)) {
                $message .= '. Skipped items: ' . implode(', ', $skippedItems);
            }

            return response()->json([
                'message' => $message,
                'data' => $workOrders
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to generate job orders by period',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate PR number
     */
    private function generatePRNumber()
    {
        $prefix = 'PR-AUTO-';
        $date = date('Ymd');
        $lastPR = PurchaseRequisition::where('pr_number', 'like', "{$prefix}{$date}%")
            ->orderBy('pr_number', 'desc')
            ->first();

        if ($lastPR) {
            $lastNumber = intval(substr($lastPR->pr_number, -3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $date . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Generate Job Order number
     */
    private function generateWONumber()
    {
        $prefix = 'JO-AUTO-';
        $date = date('Ymd');
        $lastWO = WorkOrder::where('wo_number', 'like', "{$prefix}{$date}%")
            ->orderBy('wo_number', 'desc')
            ->first();

        if ($lastWO) {
            $lastNumber = intval(substr($lastWO->wo_number, -3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $date . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Delete a material plan
     */
    public function destroy($id)
    {
        try {
            $plan = MaterialPlan::find($id);

            if (!$plan) {
                return response()->json(['message' => 'Material plan not found'], 404);
            }

            // Check if plan has been processed (has work orders or purchase requisitions)
            if ($plan->status !== 'Draft') {
                return response()->json(['message' => 'Cannot delete processed material plan'], 400);
            }

            $plan->delete();

            return response()->json(['message' => 'Material plan deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete material plan', 'error' => $e->getMessage()], 500);
        }
    }
}
