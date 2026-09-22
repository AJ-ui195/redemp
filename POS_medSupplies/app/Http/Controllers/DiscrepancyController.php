<?php

namespace App\Http\Controllers;

use App\Models\DamageRequest;
use App\Models\DamageRequestItem;
use App\Models\Product;
use App\Models\RequestStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DiscrepancyController extends Controller
{
    /**
     * Store a new damage / discrepancy request (cashier).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:2000'],
            'reason' => ['nullable', 'string', 'max:500'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'item_id' => ['nullable', 'integer'],
            'inventory_product_id' => ['nullable', 'integer'],
        ]);

        $productId = $this->resolveProductId($validated);
        if (! $productId) {
            $message = 'Please select a valid product from the search results.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => $message,
                ], 422);
            }

            return back()->withErrors(['item_name' => $message])->withInput();
        }

        $pendingStatusId = RequestStatus::query()->where('name', 'pending')->value('id');
        if (! $pendingStatusId) {
            $message = 'Request status "pending" is not configured.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => $message,
                ], 500);
            }

            return back()->withErrors(['item_name' => $message]);
        }

        $storedReason = $this->buildStoredReason($validated['reason'] ?? null, $validated['description']);
        $quantity = max(1, (int) ceil((float) $validated['quantity']));

        $damageRequest = DB::transaction(function () use ($pendingStatusId, $storedReason, $productId, $quantity) {
            $damageRequest = DamageRequest::create([
                'requested_by_user_id' => Auth::id(),
                'request_status_id' => $pendingStatusId,
                'reason' => $storedReason,
            ]);

            DamageRequestItem::create([
                'damage_request_id' => $damageRequest->id,
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);

            return $damageRequest->load(['items.product', 'requestStatus', 'requestedBy']);
        });

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Damage request submitted for approval.',
                'discrepancy' => $this->mapRequestRows($damageRequest)->first(),
            ]);
        }

        return back()->with('status', 'Damage request submitted for approval.');
    }

    /**
     * Pending damage requests for admin Approvals → Discrepancies.
     */
    public function getPendingRequests()
    {
        try {
            $pendingRequests = DamageRequest::query()
                ->pending()
                ->with(['requestedBy:id,name', 'items.product:id,name', 'requestStatus'])
                ->orderByDesc('created_at')
                ->get()
                ->flatMap(fn (DamageRequest $request) => $this->mapRequestRows($request))
                ->values();

            return response()->json([
                'success' => true,
                'requests' => $pendingRequests,
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading pending damage requests: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error loading pending requests: '.$e->getMessage(),
                'requests' => [],
            ], 500);
        }
    }

    /**
     * Damage request history for admin Approvals → Discrepancies.
     */
    public function getHistory()
    {
        try {
            $history = DamageRequest::query()
                ->with([
                    'requestedBy:id,name',
                    'reviewedBy:id,name',
                    'items.product:id,name',
                    'requestStatus',
                ])
                ->orderByDesc('created_at')
                ->get()
                ->flatMap(fn (DamageRequest $request) => $this->mapRequestRows($request))
                ->values();

            return response()->json([
                'success' => true,
                'history' => $history,
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading damage request history: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error loading history: '.$e->getMessage(),
                'history' => [],
            ], 500);
        }
    }

    /**
     * Approve a damage request and deduct product stock.
     */
    public function approve(Request $request, DamageRequest $damageRequest)
    {
        $damageRequest->load(['items.product', 'requestStatus']);

        if ($damageRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be approved',
            ], 400);
        }

        $approvedStatusId = RequestStatus::query()->where('name', 'approved')->value('id');
        if (! $approvedStatusId) {
            return response()->json([
                'success' => false,
                'message' => 'Request status "approved" is not configured.',
            ], 500);
        }

        $damageTypeId = Schema::hasTable('stock_movement_types')
            ? DB::table('stock_movement_types')->where('name', 'damage')->value('id')
            : null;

        DB::beginTransaction();
        try {
            $warnings = [];

            foreach ($damageRequest->items as $item) {
                $product = Product::query()->lockForUpdate()->find($item->product_id);
                $qty = (int) $item->quantity;

                if (! $product) {
                    $warnings[] = "Product #{$item->product_id} was not found; stock was not deducted.";
                    continue;
                }

                $available = (int) ($product->quantity ?? 0);
                if ($available < $qty) {
                    $warnings[] = "Insufficient stock for {$product->name}. Available: {$available}, Requested: {$qty}.";
                }

                $newQty = max(0, $available - $qty);
                $product->quantity = $newQty;
                $product->save();

                if ($damageTypeId && Schema::hasTable('stock_movements')) {
                    DB::table('stock_movements')->insert([
                        'product_id' => $product->id,
                        'stock_movement_type_id' => $damageTypeId,
                        'quantity' => -$qty,
                        'user_id' => Auth::id(),
                        'sale_id' => null,
                        'void_request_id' => null,
                        'damage_request_id' => $damageRequest->id,
                        'free_sample_request_id' => null,
                        'occurred_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $damageRequest->update([
                'request_status_id' => $approvedStatusId,
                'reviewed_by_user_id' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            DB::commit();

            $message = 'Damage request approved successfully';
            if (! empty($warnings)) {
                $message .= '. Note: '.implode(' ', $warnings);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'discrepancy' => $this->mapRequestRows($damageRequest->fresh(['items.product', 'requestStatus', 'requestedBy', 'reviewedBy']))->first(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error approving damage request: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a damage request.
     */
    public function reject(Request $request, DamageRequest $damageRequest)
    {
        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $damageRequest->load('requestStatus');

        if ($damageRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be rejected',
            ], 400);
        }

        $rejectedStatusId = RequestStatus::query()->where('name', 'rejected')->value('id');
        if (! $rejectedStatusId) {
            return response()->json([
                'success' => false,
                'message' => 'Request status "rejected" is not configured.',
            ], 500);
        }

        $damageRequest->update([
            'request_status_id' => $rejectedStatusId,
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at' => now(),
            'review_notes' => $request->input('rejection_reason'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Damage request rejected successfully',
            'discrepancy' => $this->mapRequestRows($damageRequest->fresh(['items.product', 'requestStatus', 'requestedBy', 'reviewedBy']))->first(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveProductId(array $validated): ?int
    {
        foreach (['product_id', 'inventory_product_id', 'item_id'] as $key) {
            if (! empty($validated[$key]) && Product::query()->whereKey($validated[$key])->exists()) {
                return (int) $validated[$key];
            }
        }

        $name = trim((string) ($validated['item_name'] ?? ''));
        if ($name === '') {
            return null;
        }

        $product = Product::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        return $product?->id;
    }

    private function buildStoredReason(?string $reason, string $description): string
    {
        $reason = trim((string) $reason);
        $description = trim($description);

        $combined = $reason !== ''
            ? $reason.' — '.$description
            : $description;

        return Str::limit($combined, 255, '');
    }

    /**
     * Flatten damage request + items into admin/cashier row shape.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function mapRequestRows(DamageRequest $damageRequest)
    {
        $status = $damageRequest->status;
        $requestedBy = $damageRequest->requestedBy?->name ?? 'Unknown';
        $reviewedBy = $damageRequest->reviewedBy?->name;
        $requestedAt = optional($damageRequest->created_at)->format('Y-m-d H:i:s');
        $requestDate = optional($damageRequest->created_at)->format('Y-m-d');
        $reviewedAt = optional($damageRequest->reviewed_at)->format('Y-m-d H:i:s');
        $reviewedDate = optional($damageRequest->reviewed_at)->format('Y-m-d');

        [$shortReason, $description] = $this->splitStoredReason($damageRequest->reason);

        $items = $damageRequest->relationLoaded('items')
            ? $damageRequest->items
            : $damageRequest->items()->with('product:id,name')->get();

        if ($items->isEmpty()) {
            return collect([[
                'id' => $damageRequest->id,
                'item_name' => 'N/A',
                'quantity' => 0,
                'description' => $description,
                'reason' => $shortReason,
                'status' => $status,
                'requested_by' => $requestedBy,
                'approved_by' => $reviewedBy,
                'rejection_reason' => $damageRequest->review_notes,
                'requested_at' => $requestedAt,
                'request_date' => $requestDate,
                'approved_at' => $status === 'approved' ? $reviewedAt : null,
                'approved_date' => $status === 'approved' ? $reviewedDate : null,
                'rejected_at' => $status === 'rejected' ? $reviewedAt : null,
                'rejected_date' => $status === 'rejected' ? $reviewedDate : null,
            ]]);
        }

        return $items->map(function (DamageRequestItem $item) use (
            $damageRequest,
            $status,
            $requestedBy,
            $reviewedBy,
            $requestedAt,
            $requestDate,
            $reviewedAt,
            $reviewedDate,
            $shortReason,
            $description
        ) {
            return [
                'id' => $damageRequest->id,
                'item_name' => $item->product?->name ?? 'N/A',
                'quantity' => (float) $item->quantity,
                'description' => $description,
                'reason' => $shortReason,
                'status' => $status,
                'requested_by' => $requestedBy,
                'approved_by' => $reviewedBy,
                'rejection_reason' => $damageRequest->review_notes,
                'requested_at' => $requestedAt,
                'request_date' => $requestDate,
                'approved_at' => $status === 'approved' ? $reviewedAt : null,
                'approved_date' => $status === 'approved' ? $reviewedDate : null,
                'rejected_at' => $status === 'rejected' ? $reviewedAt : null,
                'rejected_date' => $status === 'rejected' ? $reviewedDate : null,
            ];
        });
    }

    /**
     * @return array{0: ?string, 1: string}
     */
    private function splitStoredReason(?string $stored): array
    {
        $stored = trim((string) $stored);
        if ($stored === '') {
            return [null, 'N/A'];
        }

        if (str_contains($stored, ' — ')) {
            [$reason, $description] = explode(' — ', $stored, 2);

            return [trim($reason) !== '' ? trim($reason) : null, trim($description) !== '' ? trim($description) : $stored];
        }

        return [null, $stored];
    }
}
