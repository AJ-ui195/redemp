<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CashierDashboardController;
use App\Http\Controllers\FreeSampleController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\DiscrepancyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\Api\ScannedProductController;

Route::get('/', function () {
    return view('welcome');
});

// Login routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Dashboard (authenticated)
Route::middleware('auth')->group(function () {
    Route::get('/cashier', [CashierDashboardController::class, 'index'])->name('cashier.dashboard');
    Route::get('/cashier/products', [CashierDashboardController::class, 'getProducts'])->name('cashier.products');
    Route::view('/about/admin', 'about-admin')->name('about.admin');
    Route::view('/about/cashier', 'about-cashier')->name('about.cashier');
    Route::view('/about/inventory', 'about-inventory')->name('about.inventory');
    
    // Inventory Routes
    Route::middleware('inventory')->group(function () {
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
        
        // Specific routes must come before parameterized routes
        Route::get('/inventory/quantity-comparison/export', [InventoryController::class, 'exportQuantityComparison'])->name('inventory.quantity-comparison.export');
        Route::get('/inventory/quantity-comparison', [InventoryController::class, 'getQuantityComparison'])->name('inventory.quantity-comparison');
        Route::get('/inventory/alerts/low-stock', [InventoryController::class, 'lowStock'])->name('inventory.low-stock');
        Route::get('/inventory/alerts/expiring', [InventoryController::class, 'expiring'])->name('inventory.expiring');
        Route::get('/inventory/alerts/expired', [InventoryController::class, 'expired'])->name('inventory.expired');
        Route::get('/inventory/activity/logs', [InventoryController::class, 'logs'])->name('inventory.logs');
        Route::get('/inventory/export', [InventoryController::class, 'export'])->name('inventory.export');
        Route::get('/inventory/sync-barcode-images', [InventoryController::class, 'previewBarcodeImageSync'])->name('inventory.sync-barcode-images');
        Route::post('/inventory/sync-barcode-images', [InventoryController::class, 'applyBarcodeImageSync'])->name('inventory.sync-barcode-images.apply');
        
        // Parameterized routes come last
        Route::get('/inventory/{product}', [InventoryController::class, 'show'])->name('inventory.show');
        Route::get('/inventory/{product}/edit', [InventoryController::class, 'edit'])->name('inventory.edit');
        Route::put('/inventory/{product}', [InventoryController::class, 'update'])->name('inventory.update');
        Route::delete('/inventory/{product}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
        Route::post('/inventory/{product}/archive', [InventoryController::class, 'archive'])->name('inventory.archive');
        Route::post('/inventory/{product}/batch', [InventoryController::class, 'addBatch'])->name('inventory.addBatch');
        
        // InventoryProduct routes
        Route::get('/inventory-product/{inventoryProduct}/edit', [InventoryController::class, 'editInventoryProduct'])->name('inventory-product.edit');
        Route::put('/inventory-product/{inventoryProduct}', [InventoryController::class, 'updateInventoryProduct'])->name('inventory-product.update');
        Route::delete('/inventory-product/{inventoryProduct}', [InventoryController::class, 'destroyInventoryProduct'])->name('inventory-product.destroy');

        Route::get('/inventory-barcode/{barcode}/edit', [InventoryController::class, 'editBarcode'])->name('inventory-barcode.edit');
        Route::delete('/inventory-barcode/{barcode}', [InventoryController::class, 'destroyBarcode'])->name('inventory-barcode.destroy');
        
        // ItemList routes
        Route::get('/item-list/{itemList}/edit', [InventoryController::class, 'editItemList'])->name('item-list.edit');
        Route::put('/item-list/{itemList}', [InventoryController::class, 'updateItemList'])->name('item-list.update');
        Route::delete('/item-list/{itemList}', [InventoryController::class, 'destroyItemList'])->name('item-list.destroy');

        // Supplier Routes
        Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::get('/suppliers/all', [SupplierController::class, 'getAll'])->name('suppliers.all');
        Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

        // Purchase Order Routes
        Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
        Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
        Route::post('/purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
        Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
        Route::post('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    });

    // Settings Routes (accessible to all authenticated users)
    Route::get('/settings/vat-rate', [SettingController::class, 'getVatRate'])->name('settings.vat-rate');
    
    // Customer search (accessible to all authenticated users - must be BEFORE parameterized routes)
    Route::get('/api/customers/search', [CustomerController::class, 'search'])->name('api.customers.search');
    
    // Admin
    Route::middleware('admin')->group(function () {
        Route::get('/admin', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
        // Free sample API and approvals (new free_sample_requests schema)
        Route::get('/api/free-samples', [FreeSampleController::class, 'index'])->name('api.free-samples.index');
        Route::post('/free-samples/{freeSample}/approve', [FreeSampleController::class, 'approve'])->name('free-samples.approve');
        Route::post('/free-samples/{freeSample}/reject', [FreeSampleController::class, 'reject'])->name('free-samples.reject');
        
        // Discrepancy / damage approvals (admin only)
        Route::get('/api/discrepancies/pending', [DiscrepancyController::class, 'getPendingRequests'])->name('api.discrepancies.pending');
        Route::get('/api/discrepancies/history', [DiscrepancyController::class, 'getHistory'])->name('api.discrepancies.history');
        Route::post('/discrepancies/{damageRequest}/approve', [DiscrepancyController::class, 'approve'])->name('discrepancies.approve');
        Route::post('/discrepancies/{damageRequest}/reject', [DiscrepancyController::class, 'reject'])->name('discrepancies.reject');
        
        // Customer management (admin only)
        Route::get('/admin/customers', [CustomerController::class, 'index'])->name('admin.customers.index');
        Route::get('/api/customers', [CustomerController::class, 'index'])->name('api.customers.index');
        Route::get('/api/customers/{customer}', [CustomerController::class, 'show'])->name('api.customers.show');
        Route::post('/admin/customers', [CustomerController::class, 'store'])->name('admin.customers.store');
        Route::put('/admin/customers/{customer}', [CustomerController::class, 'update'])->name('admin.customers.update');
        Route::delete('/admin/customers/{customer}', [CustomerController::class, 'destroy'])->name('admin.customers.destroy');
        
        // Admin shift reports
        Route::get('/admin/shifts/reports', [ShiftController::class, 'adminReports'])->name('admin.shifts.reports');
        
        // Settings management (admin only)
        Route::post('/settings/tax', [SettingController::class, 'updateTaxSettings'])->name('settings.update-tax');
        Route::get('/settings/tax', [SettingController::class, 'getTaxSettings'])->name('settings.get-tax');
        
        // User management routes
        Route::post('/admin/users', [AdminDashboardController::class, 'storeUser'])->name('admin.users.store');
        Route::put('/admin/users/{user}', [AdminDashboardController::class, 'updateUser'])->name('admin.users.update');
        Route::delete('/admin/users/{user}', [AdminDashboardController::class, 'deleteUser'])->name('admin.users.delete');
        
        // API routes for admin
        Route::get('/api/items/search', [SalesController::class, 'searchItems'])->name('api.items.search');
        Route::get('/api/financial-summary', [AdminDashboardController::class, 'getFinancialSummary'])->name('api.financial-summary');
        Route::get('/api/notifications', [AdminDashboardController::class, 'getNotifications'])->name('api.notifications');
        Route::post('/api/notifications/mark-read', [AdminDashboardController::class, 'markNotificationAsRead'])->name('api.notifications.mark-read');
        Route::post('/api/notifications/mark-all-read', [AdminDashboardController::class, 'markAllNotificationsAsRead'])->name('api.notifications.mark-all-read');
        Route::get('/api/activity-logs', [AdminDashboardController::class, 'getActivityLogs'])->name('api.activity-logs');
        Route::get('/api/item-edit-logs', [AdminDashboardController::class, 'getItemEditLogs'])->name('api.item-edit-logs');
        Route::get('/api/user-sale-items', [AdminDashboardController::class, 'getUserSaleItems'])->name('api.user-sale-items');
        Route::get('/api/all-sales', [AdminDashboardController::class, 'getAllSales'])->name('api.all-sales');
        Route::get('/api/today-sales', [AdminDashboardController::class, 'getTodaySales'])->name('api.today-sales');
        Route::get('/api/daily-sales', [AdminDashboardController::class, 'getDailySalesData'])->name('api.daily-sales');
        
        // Void transaction approval routes (admin only)
        Route::get('/api/sales/void-statuses', [SalesController::class, 'getVoidStatuses'])->name('api.sales.void-statuses');
        Route::get('/api/void-requests/pending', [SalesController::class, 'getPendingVoidRequests'])->name('api.void-requests.pending');
        Route::get('/api/void-requests/history', [SalesController::class, 'getVoidTransactionHistory'])->name('api.void-requests.history');
        Route::post('/api/void-requests/{voidRequest}/approve', [SalesController::class, 'approveVoid'])->name('api.void-requests.approve');
        Route::post('/api/void-requests/{voidRequest}/reject', [SalesController::class, 'rejectVoid'])->name('api.void-requests.reject');
        
        // Wholesale to Retail conversion requests (admin only)
        Route::get('/api/wholesale-to-retail-requests/pending', [PurchaseRequestController::class, 'getPendingRequests'])->name('api.wholesale-to-retail-requests.pending');
        Route::get('/api/wholesale-to-retail-requests/history', [PurchaseRequestController::class, 'getHistory'])->name('api.wholesale-to-retail-requests.history');
        Route::post('/api/wholesale-to-retail-requests/{wholesaleToRetailRequest}/approve', [PurchaseRequestController::class, 'approve'])->name('api.wholesale-to-retail-requests.approve');
        Route::post('/api/wholesale-to-retail-requests/{wholesaleToRetailRequest}/reject', [PurchaseRequestController::class, 'reject'])->name('api.wholesale-to-retail-requests.reject');
        
        // Inventory preview for admin (bypasses inventory middleware)
        Route::get('/admin/inventory-preview', [InventoryController::class, 'preview'])->name('admin.inventory.preview');
        Route::get('/admin/costing', [InventoryController::class, 'costing'])->name('admin.costing');
        Route::post('/admin/costing/{inventoryProduct}/original-price', [InventoryController::class, 'updateOriginalPrice'])->name('admin.costing.original-price.update');
    });

    // Free Samples (cashier submits)
    Route::post('/free-samples', [FreeSampleController::class, 'store'])->name('free-samples.store');
    
    // Discrepancies (cashier submits)
    Route::post('/discrepancies', [DiscrepancyController::class, 'store'])->name('discrepancies.store');
    
    // Wholesale to Retail conversion requests (cashier can create and view their own history)
    Route::post('/api/wholesale-to-retail-requests', [PurchaseRequestController::class, 'store'])->name('api.wholesale-to-retail-requests.store');
    Route::get('/api/wholesale-to-retail-requests/my-history', [PurchaseRequestController::class, 'getMyHistory'])->name('api.wholesale-to-retail-requests.my-history');
    Route::get('/api/wholesale-to-retail-requests/find-corresponding-item', [PurchaseRequestController::class, 'findCorrespondingItem'])->name('api.wholesale-to-retail-requests.find-corresponding-item');

    // Shift Management Routes (cashier)
    Route::get('/shifts/active', [ShiftController::class, 'getActive'])->name('shifts.active');
    Route::post('/shifts/start', [ShiftController::class, 'start'])->name('shifts.start');
    Route::post('/shifts/end', [ShiftController::class, 'end'])->name('shifts.end');
    Route::post('/shifts/clear-active', [ShiftController::class, 'clearActive'])->name('shifts.clear-active');
    Route::get('/shifts/{id}/report', [ShiftController::class, 'report'])->name('shifts.report');
    Route::get('/shifts/history', [ShiftController::class, 'history'])->name('shifts.history');

    // Sales Routes (cashier)
    // Specific routes must come before parameterized routes
    Route::get('/sales/today-stats', [SalesController::class, 'todayStats'])->name('sales.today-stats');
    Route::get('/sales/today-report', [SalesController::class, 'todayReport'])->name('sales.today-report');
    Route::post('/sales', [SalesController::class, 'store'])->name('sales.store');
    Route::get('/sales/{sale}', [SalesController::class, 'show'])->name('sales.show');
    Route::post('/sales/{sale}/refund', [SalesController::class, 'refund'])->name('sales.refund');
    Route::post('/sales/{sale}/void-request', [SalesController::class, 'requestVoid'])->name('sales.void-request');
    Route::get('/api/sales/void-statuses', [SalesController::class, 'getVoidStatuses'])->name('api.sales.void-statuses');
    Route::get('/products/search', [SalesController::class, 'searchProducts'])->name('products.search');
    
    // Barcode Routes
    Route::post('/barcodes/save', [SalesController::class, 'saveBarcode'])->name('barcodes.save');
    Route::post('/barcodes/pending', [SalesController::class, 'savePendingBarcode'])->name('barcodes.pending');
    
});
    
// Connection Test Routes (for debugging)
Route::options('/api/connection-test', function () {
    return response()->json([], 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
});
Route::get('/api/connection-test', [\App\Http\Controllers\Api\ConnectionTestController::class, 'test'])->name('api.connection-test');
Route::get('/api/connection-test/full', [\App\Http\Controllers\Api\ConnectionTestController::class, 'fullTest'])->name('api.connection-test-full');
Route::get('/api/connection-test/barcode', [\App\Http\Controllers\Api\ConnectionTestController::class, 'testBarcodeLookup'])->name('api.connection-test-barcode');
Route::get('/api/health', [\App\Http\Controllers\Api\ConnectionTestController::class, 'healthCheck'])->name('api.health');
Route::options('/api/health', function () {
    return response()->json([], 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
});

// Public API Routes (for mobile app - no auth required)
Route::options('/barcodes/lookup', function () {
    return response()->json([], 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization');
});
Route::get('/barcodes/lookup', [SalesController::class, 'lookupBarcode'])->name('barcodes.lookup');

// Simple barcode lookup endpoint (alternative for mobile apps)
Route::options('/barcodes/lookup-simple', function () {
    return response()->json([], 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
});
Route::get('/barcodes/lookup-simple', [SalesController::class, 'lookupBarcodeSimple'])->name('barcodes.lookup-simple');

// Inventory Products API Routes (for mobile app - no auth required)
Route::options('/api/inventory-products', function () {
    return response()->json([], 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization');
});
Route::get('/api/inventory-products', [\App\Http\Controllers\InventoryProductController::class, 'index'])->name('api.inventory-products.index');
Route::post('/api/inventory-products', [\App\Http\Controllers\InventoryProductController::class, 'store'])->name('api.inventory-products.store');
Route::get('/api/inventory-products/by-barcode', [\App\Http\Controllers\InventoryProductController::class, 'getByBarcode'])->name('api.inventory-products.by-barcode');
Route::get('/api/inventory-products/{id}', [\App\Http\Controllers\InventoryProductController::class, 'show'])->name('api.inventory-products.show');
Route::put('/api/inventory-products/{id}', [\App\Http\Controllers\InventoryProductController::class, 'update'])->name('api.inventory-products.update');
Route::delete('/api/inventory-products/{id}', [\App\Http\Controllers\InventoryProductController::class, 'destroy'])->name('api.inventory-products.destroy');

// Test endpoint to check database connection and barcodes
Route::get('/barcodes/test-db', function () {
    try {
        // Test database connection
        $dbConnected = false;
        try {
            \DB::connection()->getPdo();
            $dbConnected = true;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'database_connected' => false,
                'error' => 'Database connection failed: ' . $e->getMessage()
            ], 500)->header('Access-Control-Allow-Origin', '*');
        }
        
        // Get barcode count and samples
        $totalBarcodes = \App\Models\Barcode::count();
        $sampleBarcodes = \App\Models\Barcode::limit(10)
            ->get(['id', 'barcode_value', 'item_name', 'price', 'price_type', 'unit', 'expiration_date']);
        
        // Get all barcode values for testing
        $allBarcodeValues = \App\Models\Barcode::pluck('barcode_value')->toArray();
        
        return response()->json([
            'success' => true,
            'database_connected' => $dbConnected,
            'total_barcodes' => $totalBarcodes,
            'sample_barcodes' => $sampleBarcodes,
            'all_barcode_values' => $allBarcodeValues,
            'message' => $totalBarcodes > 0 
                ? "Database connected. Found {$totalBarcodes} barcodes." 
                : "Database connected but no barcodes found. Add barcodes in Admin Dashboard → Generate Barcode.",
            'test_url' => 'http://192.168.1.4:8000/barcodes/lookup?value=' . ($allBarcodeValues[0] ?? 'YOUR_BARCODE_VALUE')
        ])->header('Access-Control-Allow-Origin', '*');
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'database_connected' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500)->header('Access-Control-Allow-Origin', '*');
    }
})->name('barcodes.test-db');

// Scanned Products API Routes (for mobile app - no auth required)
Route::options('/api/scanned-products', function () {
    return response()->json([], 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
});
Route::get('/api/scanned-products/test', [ScannedProductController::class, 'test'])->name('api.scanned-products.test');
Route::post('/api/scanned-products', [ScannedProductController::class, 'store'])->name('api.scanned-products.store');
Route::get('/api/scanned-products', [ScannedProductController::class, 'index'])->name('api.scanned-products.index');

// Cashier-specific endpoints
Route::options('/api/cashier/scan', function () {
    return response()->json([], 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization');
});
Route::post('/api/cashier/scan/lookup', [ScannedProductController::class, 'lookupProduct'])->name('api.cashier.scan.lookup');
Route::post('/api/cashier/scan', [ScannedProductController::class, 'storeForCashier'])->name('api.cashier.scan');
Route::get('/api/cashier/scan/latest', [ScannedProductController::class, 'getLatestForCashier'])->name('api.cashier.scan.latest');
Route::post('/api/cashier/scan/quantity', [ScannedProductController::class, 'updateQuantity'])->name('api.cashier.scan.quantity');
Route::get('/api/cashier/scan/items', [ScannedProductController::class, 'getScannedItems'])->name('api.cashier.scan.items');
Route::post('/api/cashier/scan/remove', [ScannedProductController::class, 'removeItem'])->name('api.cashier.scan.remove');
Route::post('/api/cashier/scan/clear', [ScannedProductController::class, 'clearItems'])->name('api.cashier.scan.clear');
// ID capture endpoints with CORS support
Route::options('/api/cashier/id-capture', function () {
    return response()->json([], 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization');
});
Route::post('/api/cashier/id-capture', [ScannedProductController::class, 'captureId'])->name('api.cashier.id-capture');
Route::options('/api/cashier/id-latest', function () {
    return response()->json([], 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization');
});
Route::get('/api/cashier/id-latest', [ScannedProductController::class, 'getLatestId'])->name('api.cashier.id-latest');
Route::options('/api/cashier/id-clear', function () {
    return response()->json([], 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization');
});
Route::post('/api/cashier/id-clear', [ScannedProductController::class, 'clearId'])->name('api.cashier.id-clear');
