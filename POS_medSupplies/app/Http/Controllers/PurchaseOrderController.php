<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\ItemList;
use App\Models\ProductBatch;
use App\Models\InventoryLog;
use App\Support\ItemInventoryLinker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $purchaseOrders = PurchaseOrder::with(['supplier', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('purchase-orders.index', compact('purchaseOrders'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'expected_delivery_date' => 'required|date|after:today',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.cost_per_unit' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Calculate total amount
            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $totalAmount += $item['quantity'] * $item['cost_per_unit'];
            }

            // Create purchase order
            $po = PurchaseOrder::create([
                'po_number' => PurchaseOrder::generatePONumber(),
                'supplier_id' => $validated['supplier_id'],
                'created_by' => Auth::id(),
                'order_date' => now(),
                'expected_delivery_date' => $validated['expected_delivery_date'],
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
                'items' => $validated['items'],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase Order created successfully',
                'po' => $po
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create PO: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'creator']);

        return view('purchase-orders.show', compact('purchaseOrder'));
    }

    public function approve(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending orders can be approved'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $purchaseOrder->update(['status' => 'approved']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase Order approved successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve PO: ' . $e->getMessage()
            ], 500);
        }
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Only approved orders can be received'
            ], 400);
        }

        $validated = $request->validate([
            'received_items' => 'required|array',
            'received_items.*.product_id' => 'required|exists:products,id',
            'received_items.*.quantity' => 'required|integer|min:0',
            'received_items.*.batch_number' => 'required|string',
            'received_items.*.expiry_date' => 'nullable|date|after:today',
            'received_items.*.manufactured_date' => 'nullable|date|before_or_equal:today',
        ]);

        DB::beginTransaction();
        try {
            // Process each received item
            foreach ($validated['received_items'] as $item) {
                if ($item['quantity'] > 0) {
                    // Create batch
                    $batch = ProductBatch::create([
                        'product_id' => $item['product_id'],
                        'batch_number' => $item['batch_number'],
                        'quantity' => $item['quantity'],
                        'expiry_date' => $item['expiry_date'] ?? null,
                        'manufactured_date' => $item['manufactured_date'] ?? null,
                        'cost_per_unit' => $this->getCostFromPO($purchaseOrder, $item['product_id']),
                        'supplier_id' => $purchaseOrder->supplier_id,
                        'received_date' => now(),
                        'status' => 'active',
                    ]);

                    // Update product stock
                    $product = ItemList::find($item['product_id']);
                    $inventoryProduct = $product
                        ? ItemInventoryLinker::findInventoryProductForItemList($product)
                        : null;

                    if ($inventoryProduct) {
                        $oldStock = (float) ($inventoryProduct->quantity_on_hand ?? 0);
                        $inventoryProduct->update(['quantity_on_hand' => $oldStock + $item['quantity']]);
                        $newStock = (float) ($inventoryProduct->quantity_on_hand ?? 0);
                    } else {
                        $oldStock = (float) ($product->quantity_on_hand ?? 0);
                        $product->skipObserverSync = true;
                        $product->update(['quantity_on_hand' => $oldStock + $item['quantity']]);
                        $newStock = (float) ($product->quantity_on_hand ?? 0);
                    }

                    InventoryLog::logAction(
                        $product->id,
                        Auth::id(),
                        'stock_in',
                        $oldStock,
                        $newStock,
                        "Received from PO #{$purchaseOrder->po_number}",
                        [
                            'po_id' => $purchaseOrder->id,
                            'batch_id' => $batch->id,
                        ]
                    );
                }
            }

            // Update PO status
            $purchaseOrder->update([
                'status' => 'received',
                'actual_delivery_date' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase Order received and stock updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to receive PO: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status === 'received') {
            return response()->json([
                'success' => false,
                'message' => 'Received orders cannot be cancelled'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $purchaseOrder->update(['status' => 'cancelled']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase Order cancelled successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel PO: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getCostFromPO(PurchaseOrder $po, int $productId): float
    {
        foreach ($po->items as $item) {
            if ($item['product_id'] == $productId) {
                return $item['cost_per_unit'];
            }
        }
        return 0;
    }
}
