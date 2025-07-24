<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesReturn;
use App\Models\Sales\SalesReturnLine;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceLine;
use App\Models\Item;
use App\Models\Accounting\CustomerReceivable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SalesReturnController extends Controller
{
    /**
     * Display a listing of the sales returns.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $returns = SalesReturn::with(['customer', 'salesInvoice'])->get();
        return response()->json(['data' => $returns], 200);
    }

    /**
     * Store a newly created sales return in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'return_number' => 'required|unique:SalesReturn,return_number',
            'return_date' => 'required|date',
            'customer_id' => 'required|exists:Customer,customer_id',
            'invoice_id' => 'required|exists:SalesInvoice,invoice_id',
            'return_reason' => 'required|string',
            'status' => 'required|string|max:50',
            'lines' => 'required|array',
            'lines.*.invoice_line_id' => 'required|exists:SalesInvoiceLine,line_id',
            'lines.*.returned_quantity' => 'required|numeric|min:0',
            'lines.*.condition' => 'required|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Get the invoice
            $invoice = SalesInvoice::find($request->invoice_id);

            // Create sales return
            $return = SalesReturn::create([
                'return_number' => $request->return_number,
                'return_date' => $request->return_date,
                'customer_id' => $request->customer_id,
                'invoice_id' => $request->invoice_id,
                'return_reason' => $request->return_reason,
                'status' => $request->status
            ]);

            $totalReturnAmount = 0;

            // Create return lines
            foreach ($request->lines as $line) {
                $invoiceLine = SalesInvoiceLine::find($line['invoice_line_id']);

                // Validate if the returned quantity is valid
                if ($line['returned_quantity'] > $invoiceLine->quantity) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Returned quantity exceeds invoiced quantity for item ' . $invoiceLine->item_id
                    ], 400);
                }

                $returnAmount = ($invoiceLine->total / $invoiceLine->quantity) * $line['returned_quantity'];

                SalesReturnLine::create([
                    'return_id' => $return->return_id,
                    'invoice_line_id' => $line['invoice_line_id'],
                    'item_id' => $invoiceLine->item_id,
                    'returned_quantity' => $line['returned_quantity'],
                    'condition' => $line['condition']
                ]);

                // Update inventory if the returned items are in good condition
                if ($line['condition'] === 'Good') {
                    $item = Item::find($invoiceLine->item_id);
                    $item->current_stock += $line['returned_quantity'];
                    $item->save();
                }

                $totalReturnAmount += $returnAmount;
            }

            // Update customer receivable
            $receivable = CustomerReceivable::where('invoice_id', $request->invoice_id)->first();
            if ($receivable) {
                // Reduce the balance by the return amount
                $newBalance = max(0, $receivable->balance - $totalReturnAmount);
                $newPaidAmount = $receivable->paid_amount + ($receivable->balance - $newBalance);

                $receivable->update([
                    'paid_amount' => $newPaidAmount,
                    'balance' => $newBalance
                ]);

                // Update status if fully paid
                if ($newBalance === 0) {
                    $receivable->update(['status' => 'Closed']);
                    $invoice->update(['status' => 'Closed']);
                }
            }

            DB::commit();

            return response()->json([
                'data' => $return->load('salesReturnLines'),
                'message' => 'Sales return created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create sales return', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified sales return.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $return = SalesReturn::with([
            'customer',
            'salesInvoice',
            'salesReturnLines.item',
            'salesReturnLines.salesInvoiceLine'
        ])->find($id);

        if (!$return) {
            return response()->json(['message' => 'Sales return not found'], 404);
        }

        return response()->json(['data' => $return], 200);
    }

    /**
     * Update the specified sales return in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $return = SalesReturn::find($id);

        if (!$return) {
            return response()->json(['message' => 'Sales return not found'], 404);
        }

        // Check if return can be updated (not processed)
        if (in_array($return->status, ['Processed', 'Completed'])) {
            return response()->json(['message' => 'Cannot update a ' . $return->status . ' sales return'], 400);
        }

        $validator = Validator::make($request->all(), [
            'return_number' => 'required|unique:SalesReturn,return_number,' . $id . ',return_id',
            'return_date' => 'required|date',
            'return_reason' => 'required|string',
            'status' => 'required|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $return->update($request->all());
        return response()->json(['data' => $return, 'message' => 'Sales return updated successfully'], 200);
    }

    /**
     * Process a sales return.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function process($id)
    {
        $return = SalesReturn::with('salesReturnLines')->find($id);

        if (!$return) {
            return response()->json(['message' => 'Sales return not found'], 404);
        }

        if ($return->status !== 'Pending') {
            return response()->json(['message' => 'Sales return must be in pending status to be processed'], 400);
        }

        try {
            DB::beginTransaction();

            $return->update(['status' => 'Processed']);

            // Additional processing logic can be added here
            // Such as creating a credit note or refund

            DB::commit();

            return response()->json(['message' => 'Sales return processed successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to process sales return', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified sales return from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $return = SalesReturn::find($id);

        if (!$return) {
            return response()->json(['message' => 'Sales return not found'], 404);
        }

        // Check if return can be deleted (not processed or completed)
        if (in_array($return->status, ['Processed', 'Completed'])) {
            return response()->json(['message' => 'Cannot delete a ' . $return->status . ' sales return'], 400);
        }

        try {
            DB::beginTransaction();

            // Delete related return lines
            $return->salesReturnLines()->delete();

            // Delete the return
            $return->delete();

            DB::commit();

            return response()->json(['message' => 'Sales return deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete sales return', 'error' => $e->getMessage()], 500);
        }
    }
}
