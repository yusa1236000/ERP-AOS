<?php

namespace App\Http\Controllers\Api\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\Manufacturing\ProductionOrder;
use App\Models\Manufacturing\ProductionConsumption;
use App\Models\Manufacturing\JobTicket; // ADDED FOR JOB TICKET
use App\Models\Item;
use App\Models\ItemStock;
use App\Models\ItemPrice; // ADDED FOR CUSTOMER DETECTION
use App\Models\Manufacturing\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\StockTransaction;

class ProductionOrderController extends Controller
{
    // Define warehouse IDs as class constants for maintainability
    const FINISHED_GOODS_WAREHOUSE_ID = 2;
    const VIRTUAL_PRODUCTION_WAREHOUSE_ID = 7;
    const RAW_MATERIALS_WAREHOUSE_ID = 1;
    const WIP_WAREHOUSE_ID = 3; // Work in Progress warehouse

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = ProductionOrder::with(['workOrder.item']);

        if ($request->has('wo_id')) {
            $query->where('wo_id', $request->wo_id);
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('production_number', 'like', '%' . $search . '%')
                    ->orWhereHas('workOrder', function ($q2) use ($search) {
                        $q2->where('wo_number', 'like', '%' . $search . '%')
                            ->orWhereHas('item', function ($q3) use ($search) {
                                $q3->where('item_code', 'like', '%' . $search . '%')
                                    ->orWhere('name', 'like', '%' . $search . '%');
                            });
                    });
            });
        }

        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date') && !empty($request->start_date)) {
            $query->whereDate('production_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && !empty($request->end_date)) {
            $query->whereDate('production_date', '<=', $request->end_date);
        }

        $productionOrders = $query->get();
        return response()->json(['data' => $productionOrders]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Modified validation - production_number is now optional
        $validator = Validator::make($request->all(), [
            'wo_id' => 'required|integer|exists:work_orders,wo_id',
            'production_number' => 'sometimes|string|max:50|unique:production_orders,production_number',
            'production_date' => 'required|date',
            'planned_quantity' => 'required|numeric|min:0.01',
            'actual_quantity' => 'sometimes|numeric|min:0',
            'status' => 'required|string|max:50',
            'consumptions' => 'sometimes|array',
            'consumptions.*.item_id' => 'required|integer|exists:items,item_id',
            'consumptions.*.planned_quantity' => 'required|numeric|min:0',
            'consumptions.*.actual_quantity' => 'sometimes|nullable|numeric|min:0',
            'consumptions.*.warehouse_id' => 'required|integer|exists:warehouses,warehouse_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Generate production number if not provided
        $productionNumber = $request->production_number;
        if (empty($productionNumber)) {
            $productionNumber = $this->generateProductionNumber();
        }

        // Validation for work order capacity
        $workOrder = WorkOrder::find($request->wo_id);
        if (!$workOrder) {
            return response()->json(['errors' => ['wo_id' => ['Work order not found']]], 422);
        }

        $existingPlannedQtySum = ProductionOrder::where('wo_id', $request->wo_id)->sum('planned_quantity');
        $totalPlannedQty = $existingPlannedQtySum + $request->planned_quantity;
        if ($totalPlannedQty > $workOrder->planned_quantity) {
            return response()->json(['errors' => ['planned_quantity' => ['Planned quantity exceeds the remaining quantity of the work order. Remaining quantity: ' . ($workOrder->planned_quantity - $existingPlannedQtySum)]]], 422);
        }

        DB::beginTransaction();
        try {
            $productionOrder = ProductionOrder::create([
                'wo_id' => $request->wo_id,
                'production_number' => $productionNumber, // Use generated or provided number
                'production_date' => $request->production_date,
                'planned_quantity' => $request->planned_quantity,
                'actual_quantity' => $request->actual_quantity ?? 0,
                'status' => $request->status,
            ]);

            // Create consumption entries
            if ($request->has('consumptions') && !empty($request->consumptions)) {
                foreach ($request->consumptions as $consumption) {
                    ProductionConsumption::create([
                        'production_id' => $productionOrder->production_id,
                        'item_id' => $consumption['item_id'],
                        'planned_quantity' => $consumption['planned_quantity'],
                        'actual_quantity' => $consumption['actual_quantity'] ?? 0,
                        'variance' => isset($consumption['actual_quantity'])
                            ? $consumption['planned_quantity'] - $consumption['actual_quantity']
                            : 0,
                        'warehouse_id' => $consumption['warehouse_id'],
                    ]);
                }
            } else {
                // Auto-generate consumption entries from BOM
                $this->generateConsumptionsFromBOM($productionOrder, $request->default_warehouse_id ?? self::RAW_MATERIALS_WAREHOUSE_ID);
            }

            DB::commit();

            return response()->json([
                'data' => $productionOrder->load(['workOrder', 'productionConsumptions.item']),
                'message' => 'Production order created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create production order', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate production number with format J-yy-00000
     *
     * @return string
     */
    private function generateProductionNumber()
    {
        $currentYear = date('y'); // Get 2-digit year
        $prefix = 'JP-' . $currentYear . '-';

        // Get the latest production order for current year
        $latestOrder = ProductionOrder::where('production_number', 'like', $prefix . '%')
            ->orderBy('production_number', 'desc')
            ->first();

        $nextSequence = 1;

        if ($latestOrder) {
            // Extract sequence number from the latest production number
            $latestNumber = $latestOrder->production_number;
            $sequencePart = substr($latestNumber, strlen($prefix));
            $nextSequence = intval($sequencePart) + 1;
        }

        // Format sequence with leading zeros (5 digits)
        $sequenceFormatted = str_pad($nextSequence, 5, '0', STR_PAD_LEFT);

        return $prefix . $sequenceFormatted;
    }

    /**
     * Get the next production order number (for preview)
     *
     * @return \Illuminate\Http\Response
     */
    public function getNextProductionNumber()
    {
        return response()->json([
            'next_production_number' => $this->generateProductionNumber()
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $productionOrder = ProductionOrder::with([
            'workOrder.item',
            'productionConsumptions.item',
            'productionConsumptions.warehouse',
            'jobTickets' // ADDED: Include job tickets
        ])->find($id);

        if (!$productionOrder) {
            return response()->json(['message' => 'Production order not found'], 404);
        }

        return response()->json(['data' => $productionOrder]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $productionOrder = ProductionOrder::find($id);

        if (!$productionOrder) {
            return response()->json(['message' => 'Production order not found'], 404);
        }

        // Prevent updates if materials are already issued
        if ($productionOrder->status === 'Materials Issued' || $productionOrder->status === 'In Progress' || $productionOrder->status === 'Completed') {
            return response()->json([
                'message' => 'Cannot update production order after materials are issued',
                'errors' => ['status' => ['Production order cannot be modified after materials are issued']]
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'wo_id' => 'sometimes|required|integer|exists:work_orders,wo_id',
            'production_number' => 'sometimes|required|string|max:50|unique:production_orders,production_number,' . $id . ',production_id',
            'production_date' => 'sometimes|required|date',
            'planned_quantity' => 'sometimes|required|numeric|min:0.01',
            'actual_quantity' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $productionOrder->update($request->all());

            // Regenerate consumptions if planned quantity changed
            if ($request->has('planned_quantity')) {
                $this->regenerateConsumptionsFromBOM($productionOrder, self::RAW_MATERIALS_WAREHOUSE_ID);
            }

            DB::commit();

            return response()->json([
                'data' => $productionOrder->load(['workOrder', 'productionConsumptions.item']),
                'message' => 'Production order updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update production order', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $productionOrder = ProductionOrder::find($id);

        if (!$productionOrder) {
            return response()->json(['message' => 'Production order not found'], 404);
        }

        // Prevent deletion if materials are already issued
        if ($productionOrder->status !== 'Draft' && $productionOrder->status !== 'Cancelled') {
            return response()->json([
                'message' => 'Cannot delete production order',
                'errors' => ['status' => ['Only draft or cancelled production orders can be deleted']]
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Delete production consumptions first
            $productionOrder->productionConsumptions()->delete();

            // ADDED: Delete related job tickets if any
            $productionOrder->jobTickets()->delete();

            // Then delete the production order
            $productionOrder->delete();

            DB::commit();
            return response()->json(['message' => 'Production order deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete production order', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Issue materials for production (Step 1: Material consumption)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function issueMaterials(Request $request, $id)
    {
        $productionOrder = ProductionOrder::with(['workOrder.item', 'productionConsumptions.item'])
            ->find($id);

        if (!$productionOrder) {
            return response()->json(['message' => 'Production order not found'], 404);
        }

        // Validate current status
        if ($productionOrder->status !== 'Draft') {
            return response()->json([
                'message' => 'Invalid operation',
                'errors' => ['status' => ['Materials can only be issued for draft production orders']]
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'consumptions' => 'required|array|min:1',
            'consumptions.*.consumption_id' => 'required|integer',
            'consumptions.*.actual_quantity' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Validate stock availability
        $stockErrors = [];
        $consumptionsMap = [];

        foreach ($request->consumptions as $consumption) {
            $consumptionsMap[$consumption['consumption_id']] = $consumption['actual_quantity'];
        }

        // Check stock availability for each consumption
        foreach ($productionOrder->productionConsumptions as $consumption) {
            if (!isset($consumptionsMap[$consumption->consumption_id])) {
                continue;
            }

            $actualConsumption = $consumptionsMap[$consumption->consumption_id];

            // Get current stock
            $itemStock = ItemStock::where('item_id', $consumption->item_id)
                ->where('warehouse_id', $consumption->warehouse_id)
                ->first();

            $availableStock = $itemStock ? $itemStock->quantity : 0;

            if ($actualConsumption > $availableStock) {
                $item = Item::find($consumption->item_id);
                $stockErrors[] = sprintf(
                    'Insufficient stock for %s. Required: %s, Available: %s',
                    $item->name,
                    $actualConsumption,
                    $availableStock
                );
            }
        }

        if (!empty($stockErrors)) {
            return response()->json([
                'message' => 'Stock validation failed',
                'errors' => ['stock' => $stockErrors]
            ], 422);
        }

        DB::beginTransaction();

        try {
            // 1. Process material consumptions (Issue materials from Raw Materials to WIP)
            foreach ($productionOrder->productionConsumptions as $consumption) {
                if (!isset($consumptionsMap[$consumption->consumption_id])) {
                    continue;
                }

                $actualConsumption = $consumptionsMap[$consumption->consumption_id];

                // Update consumption record
                $consumption->actual_quantity = $actualConsumption;
                $consumption->variance = $consumption->planned_quantity - $actualConsumption;
                $consumption->save();

                // Create stock transaction for material issue (Raw Materials -> WIP)
                $materialTransaction = StockTransaction::create([
                    'item_id' => $consumption->item_id,
                    'warehouse_id' => $consumption->warehouse_id, // Source: Raw Materials
                    'dest_warehouse_id' => self::WIP_WAREHOUSE_ID, // Destination: WIP
                    'transaction_type' => StockTransaction::TYPE_MANUFACTURING,
                    'move_type' => StockTransaction::MOVE_TYPE_INTERNAL, // Internal transfer
                    'quantity' => $actualConsumption,
                    'transaction_date' => now(),
                    'reference_document' => 'material_issue',
                    'reference_number' => $productionOrder->production_number,
                    'origin' => "WO-{$productionOrder->wo_id}",
                    'batch_id' => null,
                    'state' => StockTransaction::STATE_DRAFT,
                    'notes' => "Material issued for production"
                ]);

                // Auto-confirm to update stock
                $materialTransaction->markAsDone();
            }

            // 2. NEW: Create product allocation transaction (Virtual Production -> WIP)
            $finishedItem = $productionOrder->workOrder->item;
            $plannedQuantity = $productionOrder->planned_quantity;

            $productAllocationTransaction = StockTransaction::create([
                'item_id' => $finishedItem->item_id,
                'warehouse_id' => self::VIRTUAL_PRODUCTION_WAREHOUSE_ID, // Source: Virtual Production
                'dest_warehouse_id' => self::WIP_WAREHOUSE_ID, // Destination: WIP
                'transaction_type' => StockTransaction::TYPE_MANUFACTURING,
                'move_type' => StockTransaction::MOVE_TYPE_INTERNAL, // Internal transfer
                'quantity' => $plannedQuantity,
                'transaction_date' => now(),
                'reference_document' => 'product_allocation',
                'reference_number' => $productionOrder->production_number,
                'origin' => "WO-{$productionOrder->wo_id}",
                'batch_id' => null,
                'state' => StockTransaction::STATE_DRAFT,
                'notes' => "Product allocated to WIP for production"
            ]);

            // Auto-confirm to update stock (this will increase WIP stock for finished product)
            $productAllocationTransaction->markAsDone();

            // 3. Update production order status
            $productionOrder->status = 'Materials Issued';
            $productionOrder->save();

            DB::commit();

            return response()->json([
                'message' => 'Materials issued and product allocated to WIP successfully.',
                'data' => $productionOrder->fresh(['workOrder', 'productionConsumptions.item']),
                'transactions' => [
                    'material_transactions_count' => count($consumptionsMap),
                    'product_allocation_transaction_id' => $productAllocationTransaction->transaction_id,
                    'finished_product' => [
                        'item_id' => $finishedItem->item_id,
                        'item_code' => $finishedItem->item_code,
                        'item_name' => $finishedItem->name,
                        'allocated_quantity' => $plannedQuantity
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to issue materials and allocate product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start production (Step 2: Begin production process)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function startProduction(Request $request, $id)
    {
        $productionOrder = ProductionOrder::find($id);

        if (!$productionOrder) {
            return response()->json(['message' => 'Production order not found'], 404);
        }

        // Validate current status
        if ($productionOrder->status !== 'Materials Issued') {
            return response()->json([
                'message' => 'Invalid operation',
                'errors' => ['status' => ['Production can only be started after materials are issued']]
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Update production order status
            $productionOrder->status = 'In Progress';
            $productionOrder->save();

            // Update work order status if needed
            $workOrder = $productionOrder->workOrder;
            if ($workOrder->status === 'Draft') {
                $workOrder->status = 'In Progress';
                $workOrder->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Production started successfully',
                'data' => $productionOrder->fresh(['workOrder', 'productionConsumptions.item'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to start production',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete production (Step 3: Receive finished goods) - UPDATED WITH JOB TICKET AUTO-TRANSFER
     * INCLUDING FGRN_NO AND DATE FIELDS
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function complete(Request $request, $id)
    {
        $productionOrder = ProductionOrder::with(['workOrder.item', 'productionConsumptions.item'])
            ->find($id);

        if (!$productionOrder) {
            return response()->json(['message' => 'Production order not found'], 404);
        }

        // Validate current status
        if ($productionOrder->status !== 'In Progress') {
            return response()->json([
                'message' => 'Invalid operation',
                'errors' => ['status' => ['Production can only be completed from In Progress status']]
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'actual_quantity' => 'required|numeric|min:0.01',
            'completion_date' => 'sometimes|date',
            'customer_name' => 'sometimes|string|max:100', // Optional customer override
            'quality_notes' => 'sometimes|string|max:500',
            'fgrn_no' => 'sometimes|string|max:50',         // ADDED: Optional FGRN number
            'job_ticket_date' => 'sometimes|date',          // ADDED: Optional date for job ticket
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $actualQuantity = floatval($request->actual_quantity);
        $plannedQuantity = $productionOrder->planned_quantity;
        $completionDate = $request->completion_date ?? now()->toDateString();

        DB::beginTransaction();

        try {
            // Update production order
            $productionOrder->actual_quantity = $actualQuantity;
            $productionOrder->status = 'Completed';
            $productionOrder->save();

            // 1. Consume raw materials from WIP warehouse (unchanged)
            foreach ($productionOrder->productionConsumptions as $consumption) {
                if ($consumption->actual_quantity > 0) {
                    // Create consumption transaction (remove from WIP)
                    $consumptionTransaction = StockTransaction::create([
                        'item_id' => $consumption->item_id,
                        'warehouse_id' => self::WIP_WAREHOUSE_ID, // Source: WIP warehouse
                        'dest_warehouse_id' => self::VIRTUAL_PRODUCTION_WAREHOUSE_ID, // Virtual consumption
                        'transaction_type' => StockTransaction::TYPE_MANUFACTURING,
                        'move_type' => StockTransaction::MOVE_TYPE_OUT, // OUT from WIP
                        'quantity' => $consumption->actual_quantity,
                        'transaction_date' => now(),
                        'reference_document' => 'material_consumption',
                        'reference_number' => $productionOrder->production_number,
                        'origin' => "WO-{$productionOrder->wo_id}",
                        'batch_id' => null,
                        'state' => StockTransaction::STATE_DRAFT,
                        'notes' => "Material consumed in production completion"
                    ]);

                    // Auto-confirm to update stock
                    $consumptionTransaction->markAsDone();
                }
            }

            // 2. UPDATED: Handle product completion based on actual vs planned quantity
            $finishedItem = $productionOrder->workOrder->item;

            // If actual quantity differs from planned, we need to adjust the WIP allocation first
            if ($actualQuantity != $plannedQuantity) {
                $quantityDifference = $plannedQuantity - $actualQuantity;

                if ($quantityDifference > 0) {
                    // Less produced than planned - remove excess from WIP back to Virtual Production
                    $excessRemovalTransaction = StockTransaction::create([
                        'item_id' => $finishedItem->item_id,
                        'warehouse_id' => self::WIP_WAREHOUSE_ID, // Source: WIP
                        'dest_warehouse_id' => self::VIRTUAL_PRODUCTION_WAREHOUSE_ID, // Return to Virtual
                        'transaction_type' => StockTransaction::TYPE_MANUFACTURING,
                        'move_type' => StockTransaction::MOVE_TYPE_OUT, // OUT from WIP
                        'quantity' => $quantityDifference,
                        'transaction_date' => now(),
                        'reference_document' => 'production_adjustment',
                        'reference_number' => $productionOrder->production_number,
                        'origin' => "WO-{$productionOrder->wo_id}",
                        'batch_id' => null,
                        'state' => StockTransaction::STATE_DRAFT,
                        'notes' => "Remove excess allocated quantity from WIP (under-production)"
                    ]);

                    $excessRemovalTransaction->markAsDone();
                } elseif ($quantityDifference < 0) {
                    // More produced than planned - add extra to WIP from Virtual Production
                    $extraQuantity = abs($quantityDifference);
                    $extraAllocationTransaction = StockTransaction::create([
                        'item_id' => $finishedItem->item_id,
                        'warehouse_id' => self::VIRTUAL_PRODUCTION_WAREHOUSE_ID, // Source: Virtual
                        'dest_warehouse_id' => self::WIP_WAREHOUSE_ID, // Destination: WIP
                        'transaction_type' => StockTransaction::TYPE_MANUFACTURING,
                        'move_type' => StockTransaction::MOVE_TYPE_INTERNAL, // Internal transfer
                        'quantity' => $extraQuantity,
                        'transaction_date' => now(),
                        'reference_document' => 'production_adjustment',
                        'reference_number' => $productionOrder->production_number,
                        'origin' => "WO-{$productionOrder->wo_id}",
                        'batch_id' => null,
                        'state' => StockTransaction::STATE_DRAFT,
                        'notes' => "Add extra quantity to WIP (over-production)"
                    ]);

                    $extraAllocationTransaction->markAsDone();
                }
            }

            // 3. Move finished product from WIP to Finished Goods
            $finishedProductTransaction = StockTransaction::create([
                'item_id' => $finishedItem->item_id,
                'warehouse_id' => self::WIP_WAREHOUSE_ID, // Source: WIP
                'dest_warehouse_id' => self::FINISHED_GOODS_WAREHOUSE_ID, // Destination: Finished Goods
                'transaction_type' => StockTransaction::TYPE_MANUFACTURING,
                'move_type' => StockTransaction::MOVE_TYPE_INTERNAL, // Internal transfer
                'quantity' => $actualQuantity,
                'transaction_date' => now(),
                'reference_document' => 'production_completion',
                'reference_number' => $productionOrder->production_number,
                'origin' => "WO-{$productionOrder->wo_id}",
                'batch_id' => null,
                'state' => StockTransaction::STATE_DRAFT,
                'notes' => "Finished product moved from WIP to Finished Goods"
            ]);

            // Auto-confirm to update stock
            $finishedProductTransaction->markAsDone();

            // 4. Update work order progress
            $workOrder = $productionOrder->workOrder;
            $totalProduced = $workOrder->productionOrders()
                ->where('status', 'Completed')
                ->sum('actual_quantity');

            if ($totalProduced >= $workOrder->planned_quantity) {
                $workOrder->status = 'Completed';
            } else {
                $workOrder->status = 'In Progress';
            }
            $workOrder->save();

            // 5. UPDATED: AUTO-TRANSFER TO JOB_TICKET TABLE WITH NEW FIELDS
            $this->createJobTicket(
                $productionOrder,
                $actualQuantity,
                $completionDate,
                $request->customer_name,
                $request->fgrn_no,           // ADDED: Pass FGRN number
                $request->job_ticket_date    // ADDED: Pass job ticket date
            );

            DB::commit();

            $responseData = [
                'message' => 'Production completed successfully and job ticket created. Materials consumed and finished goods moved to Finished Goods warehouse.',
                'data' => $productionOrder->fresh(['workOrder', 'productionConsumptions.item', 'jobTickets']),
                'completion_summary' => [
                    'planned_quantity' => $plannedQuantity,
                    'actual_quantity' => $actualQuantity,
                    'efficiency_percentage' => round(($actualQuantity / $plannedQuantity) * 100, 2),
                    'quantity_variance' => $actualQuantity - $plannedQuantity,
                    'completion_date' => $completionDate,
                    'finished_product' => [
                        'item_id' => $finishedItem->item_id,
                        'item_code' => $finishedItem->item_code,
                        'item_name' => $finishedItem->name,
                        'moved_to_finished_goods' => $actualQuantity
                    ]
                ]
            ];

            // Add adjustment info if there was variance
            if ($actualQuantity != $plannedQuantity) {
                $responseData['completion_summary']['wip_adjustment'] = [
                    'variance' => $actualQuantity - $plannedQuantity,
                    'adjustment_type' => $actualQuantity > $plannedQuantity ? 'over_production' : 'under_production',
                    'adjusted_quantity' => abs($actualQuantity - $plannedQuantity)
                ];
            }

            return response()->json($responseData);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to complete production',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * UPDATED: Create job ticket entry when production is completed
     * Auto-transfer to job_ticket table with FGRN_NO and DATE fields
     *
     * @param ProductionOrder $productionOrder
     * @param float $actualQuantity
     * @param string $completionDate
     * @param string|null $customerName
     * @param string|null $fgrnNo
     * @param string|null $jobTicketDate
     * @return void
     */
    private function createJobTicket(
        ProductionOrder $productionOrder,
        float $actualQuantity,
        string $completionDate,
        ?string $customerName = null,
        ?string $fgrnNo = null,           // ADDED: FGRN number parameter
        ?string $jobTicketDate = null     // ADDED: Job ticket date parameter
    ) {
        $workOrder = $productionOrder->workOrder;
        $item = $workOrder->item;

        // Try to get customer name from various sources
        $customer = $customerName;
        if (!$customer) {
            $customer = $this->findCustomerForWorkOrder($workOrder) ?? 'Unknown Customer';
        }

        // Generate FGRN number if not provided
        $fgrnNumber = $fgrnNo;
        if (!$fgrnNumber) {
            $fgrnNumber = $this->generateFGRNNumber($productionOrder);
        }

        // Use job ticket date if provided, otherwise use completion date
        $ticketDate = $jobTicketDate ?? $completionDate;

        // Get UOM from item relationship
        $uom = 'PCS'; // Default UOM
        if ($item && $item->uom) {
            $uom = $item->uom->name;
        }

        JobTicket::create([
            'item' => $item->item_code . ' - ' . $item->name,
            'uom' => $uom,
            'qty_completed' => $actualQuantity,
            'ref_jo_no' => $workOrder->wo_number,
            'issue_date_jo' => $workOrder->wo_date,
            'qty_jo' => $workOrder->planned_quantity,
            'customer' => $customer,
            'production_id' => $productionOrder->production_id,
            'fgrn_no' => $fgrnNumber,     // ADDED: FGRN number
            'date' => $ticketDate,        // ADDED: Date field
        ]);
    }

    /**
     * ADDED: Generate FGRN (Finished Goods Receipt Number) with format JT-yy-xxxxx
     *
     * @param ProductionOrder $productionOrder
     * @return string
     */
    private function generateFGRNNumber(ProductionOrder $productionOrder): string
    {
        $year = now()->format('y'); // 2 digit year (25 for 2025)
        $prefix = 'JT-' . $year . '-';

        // Get the latest FGRN number for current year
        $latestJobTicket = JobTicket::where('fgrn_no', 'like', $prefix . '%')
            ->orderBy('fgrn_no', 'desc')
            ->first();

        if ($latestJobTicket) {
            // Extract the sequence number and increment
            $lastSequence = (int) substr($latestJobTicket->fgrn_no, -5); // Last 5 digits
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        // Format: JT-25-00001
        return $prefix . str_pad($newSequence, 5, '0', STR_PAD_LEFT);
    }

    /**
     * ADDED: Helper method to find customer for work order using ItemPrice table
     * Customer detection via ItemPrice
     *
     * @param WorkOrder $workOrder
     * @return string|null
     */
    private function findCustomerForWorkOrder(WorkOrder $workOrder): ?string
    {
        // Priority 1: Get customer from ItemPrice (sale prices with customer_id)
        $itemPrice = ItemPrice::where('item_id', $workOrder->item_id)
            ->where('price_type', 'sale')
            ->whereNotNull('customer_id')
            ->active() // Using the active scope from ItemPrice model
            ->with('customer')
            ->orderBy('start_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($itemPrice && $itemPrice->customer) {
            return $itemPrice->customer->name;
        }

        // Priority 2: If no customer-specific pricing, try to find through Sales Order
        try {
            $salesOrderLine = \App\Models\Sales\SOLine::where('item_id', $workOrder->item_id)
                ->whereHas('salesOrder', function ($q) {
                    $q->whereIn('status', ['Confirmed', 'Partially Delivered', 'Delivered'])
                        ->where('status', '!=', 'Cancelled');
                })
                ->with('salesOrder.customer')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($salesOrderLine && $salesOrderLine->salesOrder && $salesOrderLine->salesOrder->customer) {
                return $salesOrderLine->salesOrder->customer->name;
            }
        } catch (\Exception $e) {
            // If SOLine model doesn't exist or relationship fails, continue to next priority
        }

        // Priority 3: Try to find any customer who has bought this item before (from any item price)
        $anyItemPrice = ItemPrice::where('item_id', $workOrder->item_id)
            ->where('price_type', 'sale')
            ->whereNotNull('customer_id')
            ->with('customer')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($anyItemPrice && $anyItemPrice->customer) {
            return $anyItemPrice->customer->name;
        }

        // Default fallback
        return 'Unknown Customer';
    }

    /**
     * Update the status of the production order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request, $id)
    {
        $productionOrder = ProductionOrder::find($id);

        if (!$productionOrder) {
            return response()->json(['message' => 'Production order not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:Draft,Materials Issued,In Progress,Completed,Cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $currentStatus = $productionOrder->status;
        $newStatus = $request->status;

        // Define allowed status transitions
        $allowedTransitions = [
            'Draft' => ['Materials Issued', 'Cancelled'],
            'Materials Issued' => ['In Progress', 'Cancelled'],
            'In Progress' => ['Completed', 'Cancelled'],
            'Completed' => [], // Completed orders cannot be changed
            'Cancelled' => ['Draft'], // Can reactivate cancelled orders
        ];

        if (!in_array($newStatus, $allowedTransitions[$currentStatus])) {
            return response()->json([
                'message' => 'Invalid status transition',
                'errors' => [
                    'status' => [
                        "Cannot change status from '{$currentStatus}' to '{$newStatus}'",
                        "Use specific endpoints: issueMaterials(), startProduction(), complete()",
                        "Or allowed manual transitions: " . implode(', ', $allowedTransitions[$currentStatus])
                    ]
                ]
            ], 422);
        }

        // For most transitions, recommend using specific endpoints
        if ($newStatus !== 'Cancelled' && $newStatus !== 'Draft') {
            return response()->json([
                'message' => 'Use specific endpoints',
                'errors' => [
                    'status' => [
                        'For better control, use specific endpoints:',
                        '- POST /production-orders/{id}/issue-materials',
                        '- POST /production-orders/{id}/start-production',
                        '- POST /production-orders/{id}/complete'
                    ]
                ]
            ], 422);
        }

        DB::beginTransaction();
        try {
            $productionOrder->status = $newStatus;

            if ($newStatus === 'Cancelled') {
                $productionOrder->actual_quantity = 0;
            }

            if ($newStatus === 'Draft' && $currentStatus === 'Cancelled') {
                $productionOrder->actual_quantity = 0;
            }

            $productionOrder->save();

            DB::commit();

            return response()->json([
                'message' => $this->getStatusChangeMessage($currentStatus, $newStatus),
                'data' => $productionOrder->fresh(['workOrder', 'productionConsumptions.item'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update production order status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate consumptions from BOM
     */
    private function generateConsumptionsFromBOM($productionOrder, $warehouseId)
    {
        $workOrder = WorkOrder::with('bom.bomLines')->find($productionOrder->wo_id);
        if ($workOrder && $workOrder->bom) {
            foreach ($workOrder->bom->bomLines as $bomLine) {
                $baseQty = $bomLine->quantity * ($productionOrder->planned_quantity / $workOrder->bom->standard_quantity);
                $plannedQty = $baseQty;

                if ($bomLine->is_yield_based && $bomLine->yield_ratio > 0) {
                    $plannedQty = $baseQty / $bomLine->yield_ratio;
                    if ($bomLine->shrinkage_factor > 0) {
                        $plannedQty = $plannedQty / (1 - $bomLine->shrinkage_factor);
                    }
                }

                $plannedQty = ceil($plannedQty);

                ProductionConsumption::create([
                    'production_id' => $productionOrder->production_id,
                    'item_id' => $bomLine->item_id,
                    'planned_quantity' => $plannedQty,
                    'actual_quantity' => 0,
                    'variance' => $plannedQty,
                    'warehouse_id' => $warehouseId,
                ]);
            }
        }
    }

    /**
     * Regenerate consumptions from BOM
     */
    private function regenerateConsumptionsFromBOM($productionOrder, $warehouseId)
    {
        // Delete existing consumptions
        $productionOrder->productionConsumptions()->delete();

        // Generate new ones
        $this->generateConsumptionsFromBOM($productionOrder, $warehouseId);
    }

    /**
     * Get material status for production order
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function getMaterialStatus($id)
    {
        $productionOrder = ProductionOrder::with([
            'productionConsumptions.item',
            'productionConsumptions.warehouse'
        ])->find($id);

        if (!$productionOrder) {
            return response()->json(['message' => 'Production order not found'], 404);
        }

        $materialStatus = [];
        $allMaterialsAvailable = true;
        $totalPlannedValue = 0;
        $totalActualValue = 0;

        foreach ($productionOrder->productionConsumptions as $consumption) {
            // Get current stock
            $itemStock = ItemStock::where('item_id', $consumption->item_id)
                ->where('warehouse_id', $consumption->warehouse_id)
                ->first();

            $availableStock = $itemStock ? $itemStock->quantity : 0;
            $isAvailable = $availableStock >= $consumption->planned_quantity;

            if (!$isAvailable) {
                $allMaterialsAvailable = false;
            }

            // Calculate estimated values
            $estimatedUnitPrice = $consumption->item->cost_price ?? 0;
            $plannedValue = $consumption->planned_quantity * $estimatedUnitPrice;
            $actualValue = $consumption->actual_quantity * $estimatedUnitPrice;

            $totalPlannedValue += $plannedValue;
            $totalActualValue += $actualValue;

            $materialStatus[] = [
                'consumption_id' => $consumption->consumption_id,
                'item_id' => $consumption->item_id,
                'item_code' => $consumption->item->item_code,
                'item_name' => $consumption->item->name,
                'planned_quantity' => $consumption->planned_quantity,
                'actual_quantity' => $consumption->actual_quantity,
                'variance' => $consumption->variance,
                'available_stock' => $availableStock,
                'is_available' => $isAvailable,
                'shortage' => max(0, $consumption->planned_quantity - $availableStock),
                'unit_price' => $estimatedUnitPrice,
                'planned_value' => $plannedValue,
                'actual_value' => $actualValue,
                'warehouse_name' => $consumption->warehouse->name ?? 'Unknown',
                'status' => $consumption->actual_quantity > 0 ? 'Issued' : 'Pending'
            ];
        }

        return response()->json([
            'data' => [
                'production_order_id' => $productionOrder->production_id,
                'production_number' => $productionOrder->production_number,
                'status' => $productionOrder->status,
                'all_materials_available' => $allMaterialsAvailable,
                'total_planned_value' => $totalPlannedValue,
                'total_actual_value' => $totalActualValue,
                'material_details' => $materialStatus
            ]
        ]);
    }

    /**
     * Get production summary
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function getProductionSummary($id)
    {
        $productionOrder = ProductionOrder::with([
            'workOrder.item',
            'productionConsumptions.item'
        ])->find($id);

        if (!$productionOrder) {
            return response()->json(['message' => 'Production order not found'], 404);
        }

        // Calculate efficiency metrics
        $plannedQuantity = $productionOrder->planned_quantity;
        $actualQuantity = $productionOrder->actual_quantity;
        $efficiency = $plannedQuantity > 0 ? ($actualQuantity / $plannedQuantity) * 100 : 0;

        // Calculate material utilization
        $totalPlannedMaterials = $productionOrder->productionConsumptions->sum('planned_quantity');
        $totalActualMaterials = $productionOrder->productionConsumptions->sum('actual_quantity');
        $materialUtilization = $totalPlannedMaterials > 0 ? ($totalActualMaterials / $totalPlannedMaterials) * 100 : 0;

        // Calculate variances
        $quantityVariance = $actualQuantity - $plannedQuantity;
        $quantityVariancePercent = $plannedQuantity > 0 ? ($quantityVariance / $plannedQuantity) * 100 : 0;

        // Production timeline
        $timeline = [
            'created' => $productionOrder->created_at,
            'materials_issued' => null,
            'production_started' => null,
            'completed' => null
        ];

        // Get stock transactions to determine timeline
        $transactions = StockTransaction::where('reference_number', $productionOrder->production_number)
            ->orderBy('transaction_date')
            ->get();

        foreach ($transactions as $transaction) {
            if ($transaction->reference_document === 'material_issue') {
                $timeline['materials_issued'] = $transaction->transaction_date;
            } elseif ($transaction->reference_document === 'production_completion') {
                $timeline['completed'] = $transaction->transaction_date;
            }
        }

        // Estimate production start time (after materials issued)
        if ($timeline['materials_issued'] && $productionOrder->status !== 'Materials Issued') {
            $timeline['production_started'] = $timeline['materials_issued'];
        }

        return response()->json([
            'data' => [
                'production_order' => [
                    'id' => $productionOrder->production_id,
                    'number' => $productionOrder->production_number,
                    'status' => $productionOrder->status,
                    'work_order_number' => $productionOrder->workOrder->wo_number,
                    'product_name' => $productionOrder->workOrder->item->name,
                    'product_code' => $productionOrder->workOrder->item->item_code,
                ],
                'quantities' => [
                    'planned' => $plannedQuantity,
                    'actual' => $actualQuantity,
                    'variance' => $quantityVariance,
                    'variance_percent' => round($quantityVariancePercent, 2),
                ],
                'metrics' => [
                    'production_efficiency' => round($efficiency, 2),
                    'material_utilization' => round($materialUtilization, 2),
                    'total_materials_planned' => $totalPlannedMaterials,
                    'total_materials_used' => $totalActualMaterials,
                ],
                'timeline' => $timeline,
                'material_summary' => $productionOrder->productionConsumptions->map(function ($consumption) {
                    return [
                        'item_name' => $consumption->item->name,
                        'planned' => $consumption->planned_quantity,
                        'actual' => $consumption->actual_quantity,
                        'variance' => $consumption->variance,
                        'variance_percent' => $consumption->planned_quantity > 0
                            ? round(($consumption->variance / $consumption->planned_quantity) * 100, 2)
                            : 0
                    ];
                })
            ]
        ]);
    }

    /**
     * Bulk issue materials for multiple production orders
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function bulkIssueMaterials(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'production_orders' => 'required|array|min:1',
            'production_orders.*.production_id' => 'required|integer',
            'production_orders.*.consumptions' => 'required|array',
            'production_orders.*.consumptions.*.consumption_id' => 'required|integer',
            'production_orders.*.consumptions.*.actual_quantity' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $results = [];
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($request->production_orders as $orderData) {
                $productionOrder = ProductionOrder::find($orderData['production_id']);

                if (!$productionOrder) {
                    $errors[] = "Production order {$orderData['production_id']} not found";
                    continue;
                }

                if ($productionOrder->status !== 'Draft') {
                    $errors[] = "Production order {$productionOrder->production_number} is not in Draft status";
                    continue;
                }

                // Process this production order
                $fakeRequest = new Request(['consumptions' => $orderData['consumptions']]);
                $response = $this->issueMaterials($fakeRequest, $orderData['production_id']);

                if ($response->getStatusCode() === 200) {
                    $results[] = [
                        'production_id' => $orderData['production_id'],
                        'production_number' => $productionOrder->production_number,
                        'status' => 'success'
                    ];
                } else {
                    $errors[] = "Failed to issue materials for production order {$productionOrder->production_number}";
                }
            }

            if (!empty($errors)) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Bulk operation failed',
                    'errors' => $errors
                ], 422);
            }

            DB::commit();

            return response()->json([
                'message' => 'Materials issued successfully for all production orders',
                'data' => $results
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Bulk operation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get appropriate message for status change
     */
    private function getStatusChangeMessage($fromStatus, $toStatus)
    {
        $messages = [
            'Draft|Cancelled' => 'Production order cancelled successfully.',
            'Materials Issued|Cancelled' => 'Production cancelled. Materials remain in WIP warehouse.',
            'In Progress|Cancelled' => 'Production cancelled. Check WIP inventory for issued materials.',
            'Cancelled|Draft' => 'Production order reactivated successfully.',
        ];

        $key = $fromStatus . '|' . $toStatus;
        return $messages[$key] ?? 'Production order status updated successfully.';
    }
}
