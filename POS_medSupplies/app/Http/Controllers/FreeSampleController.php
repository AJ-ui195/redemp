<?php

namespace App\Http\Controllers;

use App\Models\FreeSampleRequest;
use App\Models\FreeSampleRequestItem;
use App\Models\Product;
use App\Models\RequestStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FreeSampleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $requests = FreeSampleRequest::query()
                ->with(['requestedBy:id,name', 'items.product:id,name', 'requestStatus'])
                ->latest()
                ->get()
                ->flatMap(fn (FreeSampleRequest $sample) => $this->mapRequestRows($sample))
                ->values();

            return response()->json([
                'success' => true,
                'requests' => $requests,
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading free sample requests: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error loading free sample requests: '.$e->getMessage(),
                'requests' => [],
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'item_name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
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

        $storedReason = $this->buildStoredReason($validated['customer_name'], $validated['reason'] ?? '');

        DB::transaction(function () use ($pendingStatusId, $storedReason, $productId, $validated) {
            $sampleRequest = FreeSampleRequest::create([
                'requested_by_user_id' => Auth::id(),
                'request_status_id' => $pendingStatusId,
                'reason' => $storedReason,
            ]);

            FreeSampleRequestItem::create([
                'free_sample_request_id' => $sampleRequest->id,
                'product_id' => $productId,
                'quantity' => (int) $validated['quantity'],
            ]);
        });

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Free sample request submitted for approval.',
            ]);
        }

        return back()->with('status', 'Free sample request submitted for approval.');
    }

    public function approve(Request $request, FreeSampleRequest $freeSample)
    {
        $freeSample->load(['items.product', 'requestStatus']);

        if ($freeSample->status !== 'pending') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending requests can be approved',
                ], 400);
            }

            return back()->with('error', 'Only pending requests can be approved.');
        }

        $approvedStatusId = RequestStatus::query()->where('name', 'approved')->value('id');
        if (! $approvedStatusId) {
            $message = 'Request status "approved" is not configured.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }

            return back()->with('error', $message);
        }

        $sampleTypeId = Schema::hasTable('stock_movement_types')
            ? DB::table('stock_movement_types')->where('name', 'free_sample')->value('id')
            : null;

        DB::beginTransaction();
        try {
            $warnings = [];

            foreach ($freeSample->items as $item) {
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

                $product->quantity = max(0, $available - $qty);
                $product->save();

                if ($sampleTypeId && Schema::hasTable('stock_movements')) {
                    DB::table('stock_movements')->insert([
                        'product_id' => $product->id,
                        'stock_movement_type_id' => $sampleTypeId,
                        'quantity' => -$qty,
                        'user_id' => Auth::id(),
                        'sale_id' => null,
                        'void_request_id' => null,
                        'damage_request_id' => null,
                        'free_sample_request_id' => $freeSample->id,
                        'occurred_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $freeSample->update([
                'request_status_id' => $approvedStatusId,
                'reviewed_by_user_id' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            DB::commit();

            $message = 'Free sample request approved successfully';
            if (! empty($warnings)) {
                $message .= '. Note: '.implode(' ', $warnings);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'warnings' => $warnings,
                    'request' => $this->mapRequestRows($freeSample->fresh(['items.product', 'requestStatus', 'requestedBy']))->first(),
                ]);
            }

            $redirect = back()->with('status', $message);
            if (! empty($warnings)) {
                $redirect->with('warnings', $warnings);
            }

            return $redirect;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving free sample request', [
                'free_sample_request_id' => $freeSample->id ?? null,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to approve request: '.$e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to approve request: '.$e->getMessage());
        }
    }

    public function reject(Request $request, FreeSampleRequest $freeSample)
    {
        $freeSample->load('requestStatus');

        if ($freeSample->status !== 'pending') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending requests can be rejected',
                ], 400);
            }

            return back()->with('error', 'Only pending requests can be rejected.');
        }

        $rejectedStatusId = RequestStatus::query()->where('name', 'rejected')->value('id');
        if (! $rejectedStatusId) {
            $message = 'Request status "rejected" is not configured.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }

            return back()->with('error', $message);
        }

        $freeSample->update([
            'request_status_id' => $rejectedStatusId,
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at' => now(),
            'review_notes' => $request->input('reason'),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Free sample request rejected',
                'request' => $this->mapRequestRows($freeSample->fresh(['items.product', 'requestStatus', 'requestedBy']))->first(),
            ]);
        }

        return back()->with('status', 'Request rejected.');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveProductId(array $validated): ?int
    {
        if (! empty($validated['product_id']) && Product::query()->whereKey($validated['product_id'])->exists()) {
            return (int) $validated['product_id'];
        }

        $name = trim((string) ($validated['item_name'] ?? ''));
        if ($name === '') {
            return null;
        }

        return Product::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->value('id');
    }

    private function buildStoredReason(string $customerName, string $purpose): string
    {
        $customerName = trim($customerName);
        $purpose = trim($purpose);
        $combined = $purpose !== ''
            ? "Customer: {$customerName} — {$purpose}"
            : "Customer: {$customerName}";

        return Str::limit($combined, 255, '');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitStoredReason(?string $stored): array
    {
        $stored = trim((string) $stored);
        if ($stored === '') {
            return ['N/A', 'N/A'];
        }

        if (preg_match('/^Customer:\s*(.+?)\s*—\s*(.*)$/s', $stored, $matches)) {
            return [trim($matches[1]) !== '' ? trim($matches[1]) : 'N/A', trim($matches[2]) !== '' ? trim($matches[2]) : 'N/A'];
        }

        if (str_starts_with($stored, 'Customer:')) {
            return [trim(substr($stored, strlen('Customer:'))) ?: 'N/A', 'N/A'];
        }

        return ['N/A', $stored];
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function mapRequestRows(FreeSampleRequest $sample)
    {
        [$customerName, $purpose] = $this->splitStoredReason($sample->reason);
        $status = $sample->status;
        $userName = $sample->requestedBy?->name ?? $sample->user?->name ?? 'Unknown';
        $createdAt = optional($sample->created_at)->toISOString() ?? optional($sample->created_at)->format('c');

        $items = $sample->relationLoaded('items')
            ? $sample->items
            : $sample->items()->with('product:id,name')->get();

        if ($items->isEmpty()) {
            return collect([[
                'id' => $sample->id,
                'user' => ['name' => $userName],
                'customer_name' => $customerName,
                'item_name' => 'N/A',
                'quantity' => 0,
                'reason' => $purpose,
                'status' => $status,
                'created_at' => $createdAt,
            ]]);
        }

        return $items->map(function (FreeSampleRequestItem $item) use ($sample, $customerName, $purpose, $status, $userName, $createdAt) {
            return [
                'id' => $sample->id,
                'user' => ['name' => $userName],
                'customer_name' => $customerName,
                'item_name' => $item->product?->name ?? 'N/A',
                'quantity' => (int) $item->quantity,
                'reason' => $purpose,
                'status' => $status,
                'created_at' => $createdAt,
            ];
        });
    }
}
