<?php

namespace App\Http\Controllers;

use App\ItemImageAssetUrl;
use App\Models\DamageRequest;
use App\Models\FreeSampleRequest;
use App\Support\ItemInventoryLinker;
use App\Support\ProductCatalog;
use App\Support\RepairPlaceholderPrices;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CashierDashboardController extends Controller
{
    /**
     * Show the cashier dashboard.
     */
    public function index(): View
    {
        $products = $this->loadCashierProducts();
        $this->logCashierProductSummary($products);

        $freeSampleRequests = collect();
        if (Schema::hasTable('free_sample_requests')) {
            $freeSampleRequests = FreeSampleRequest::query()
                ->where('requested_by_user_id', Auth::id())
                ->with(['items.product', 'requestStatus'])
                ->orderByDesc('created_at')
                ->get()
                ->flatMap(function (FreeSampleRequest $request) {
                    [$customerName, $purpose] = $this->splitFreeSampleReason($request->reason);
                    $items = $request->items;

                    if ($items->isEmpty()) {
                        return collect([(object) [
                            'customer_name' => $customerName,
                            'item_name' => 'N/A',
                            'quantity' => 0,
                            'reason' => $purpose,
                            'status' => $request->status,
                            'created_at' => $request->created_at,
                        ]]);
                    }

                    return $items->map(function ($item) use ($request, $customerName, $purpose) {
                        return (object) [
                            'customer_name' => $customerName,
                            'item_name' => $item->product?->name ?? 'N/A',
                            'quantity' => $item->quantity,
                            'reason' => $purpose,
                            'status' => $request->status,
                            'created_at' => $request->created_at,
                        ];
                    });
                });
        }

        $discrepancyRequests = collect();
        if (Schema::hasTable('damage_requests')) {
            $discrepancyRequests = DamageRequest::query()
                ->where('requested_by_user_id', Auth::id())
                ->with(['items.product', 'requestStatus'])
                ->orderByDesc('created_at')
                ->get()
                ->flatMap(function (DamageRequest $request) {
                    $items = $request->items;
                    if ($items->isEmpty()) {
                        return collect([(object) [
                            'item_name' => 'N/A',
                            'quantity' => 0,
                            'description' => $request->reason,
                            'reason' => $request->reason,
                            'status' => $request->status,
                            'rejection_reason' => $request->review_notes,
                            'created_at' => $request->created_at,
                        ]]);
                    }

                    return $items->map(function ($item) use ($request) {
                        return (object) [
                            'item_name' => $item->product?->name ?? 'N/A',
                            'quantity' => $item->quantity,
                            'description' => $request->reason,
                            'reason' => $request->reason,
                            'status' => $request->status,
                            'rejection_reason' => $request->review_notes,
                            'created_at' => $request->created_at,
                        ];
                    });
                });
        }

        return view('dashboard.cashier', compact('products', 'freeSampleRequests', 'discrepancyRequests'));
    }

    /**
     * Get products as JSON for AJAX refresh.
     */
    public function getProducts(): JsonResponse
    {
        $products = $this->loadCashierProducts();

        return response()->json([
            'success' => true,
            'products' => $products,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadCashierProducts(): array
    {
        RepairPlaceholderPrices::run();

        $products = [];

        foreach (ProductCatalog::all() as $row) {
            $product = $this->mapCatalogRowToCashierProduct($row);
            if ($product === null) {
                continue;
            }

            $products[] = $product;
        }

        return $products;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function mapCatalogRowToCashierProduct(object $row): ?array
    {
        $itemName = trim((string) ($row->item_name ?? ''));
        if ($itemName === '') {
            return null;
        }

        $productId = (int) ($row->id ?? 0);
        $displayQuantity = (float) ($row->quantity_on_hand ?? 0);
        $imagePath = $row->item_image ?? null;

        return [
            'id' => $productId,
            'cashier_key' => $row->cashier_key ?? ('p-' . $productId),
            'source' => $row->source ?? 'products',
            'item_id' => $productId,
            'inventory_product_id' => $productId,
            'barcode_id' => null,
            'name' => $itemName,
            'item' => $itemName,
            'price' => $row->price ?? 0,
            'stock_quantity' => $displayQuantity,
            'quantity_on_hand' => $displayQuantity,
            'category' => $row->price_type ?? null,
            'sku' => $row->sku ?: ($row->barcode ?: 'N/A'),
            'mpn' => $row->barcode ?: ($row->sku ?: 'N/A'),
            'unit' => $row->unit ?? 'pcs',
            'unit_of_measure' => $row->unit ?? 'pcs',
            'min_stock_level' => 10,
            'reorder_pt_min' => 10,
            'expiry_date' => $row->expiration_date ?? null,
            'price_type' => $row->price_type ?? null,
            'image' => ItemImageAssetUrl::resolve($this->isBlankPath($imagePath) ? null : $imagePath),
            'brand' => $row->brand ?? null,
            'description' => $row->description ?? null,
            'search_text' => ItemInventoryLinker::searchableText(implode(' ', [
                $itemName,
                $row->barcode ?? '',
                $row->sku ?? '',
                $row->brand ?? '',
                $row->description ?? '',
                $row->price_type ?? '',
            ])),
        ];
    }

    private function isBlankPath(mixed $path): bool
    {
        return trim((string) $path) === '';
    }

    /**
     * @param  list<array<string, mixed>>  $products
     */
    private function logCashierProductSummary(array $products): void
    {
        Log::info('Cashier Dashboard Products', [
            'total_products_count' => count($products),
            'source' => 'products',
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitFreeSampleReason(?string $stored): array
    {
        $stored = trim((string) $stored);
        if ($stored === '') {
            return ['N/A', 'N/A'];
        }

        if (preg_match('/^Customer:\s*(.+?)\s*—\s*(.*)$/s', $stored, $matches)) {
            return [
                trim($matches[1]) !== '' ? trim($matches[1]) : 'N/A',
                trim($matches[2]) !== '' ? trim($matches[2]) : 'N/A',
            ];
        }

        if (str_starts_with($stored, 'Customer:')) {
            return [trim(substr($stored, strlen('Customer:'))) ?: 'N/A', 'N/A'];
        }

        return ['N/A', $stored];
    }
}
