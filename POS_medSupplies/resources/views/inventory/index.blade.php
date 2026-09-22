@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/inventory.css') }}">
@endpush

@section('content')
<div class="inventory-container">
    
    <div class="inventory-header">
        <div class="d-flex justify-content-between align-items-center">
            <div class="header-brand">
                <div class="brand-logo">
                    <img src="{{ asset('/images/logo.jpg') }}" alt="REDEMP Logo" class="logo-image">
                </div>
                <div class="brand-text">
                    <h3 class="brand-name mb-0">INVENTORY MANAGEMENT</h3>
                    <small class="brand-tagline">REDEMP MEDICAL SUPPLIES & PHARMACY</small>
                </div>
            </div>
            <div class="header-controls">
                <button class="btn btn-outline-warning btn-sm me-2" type="button" data-bs-toggle="modal" data-bs-target="#generateBarcodeModal">
                    <i class="bi bi-upc-scan me-1"></i>Generate Barcode
                </button>
                <a class="btn btn-outline-secondary btn-sm me-2" href="{{ route('about.inventory') }}">
                    <i class="bi bi-info-circle me-1"></i>About
                </a>
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
            @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm">Logout</button>
        </form>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mx-3 mt-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mx-3 mt-3" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Alert Bar -->
    <div class="alert-bar">
        <div class="d-flex justify-content-between align-items-center">
            <div class="alert-summary">
                <span class="badge bg-danger me-2" id="outOfStockCount">{{ $allItems->where('quantity_on_hand', '<=', 0)->count() }} Out of Stock</span>
                <span class="badge bg-warning me-2" id="lowStockCount">{{ $lowStockProducts }} Low Stock</span>
                <span class="badge bg-info me-2" id="expiringCount">{{ $expiringSoonProducts }} Expiring Soon</span>
                <span class="badge bg-secondary" id="expiredCount">{{ $expiredProducts }} Expired</span>
            </div>
            <div class="alert-actions">
                <button class="btn btn-sm btn-outline-primary" onclick="refreshAlerts()">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="inventory-main">
        <div class="row g-4">
            <div class="col-12">
                <!-- Products Table -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-box-seam text-primary"></i>
                                <h5 class="mb-0">All Products</h5>
                            </div>
                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                <div class="input-group" style="max-width: 350px;">
                                    <span class="input-group-text bg-white">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" class="form-control" id="productSearch" placeholder="Search products..." value="{{ request('search') }}">
                                    <button class="btn btn-outline-primary" type="button" onclick="performSearch()" title="Search">
                                        <i class="bi bi-search"></i>
                                    </button>
                                    @if(request('search'))
                                        <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()" title="Clear search">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    @endif
                                </div>
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="showQuantityComparison()" id="compareQuantitiesBtn">
                                    <i class="bi bi-graph-up-arrow"></i> Compare Quantities
                                </button>
                                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#filterModal">
                                    <i class="bi bi-funnel"></i> Filter
                                </button>
                                <button class="btn btn-outline-success btn-sm" onclick="exportProducts()">
                                    <i class="bi bi-download"></i> Export
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <!-- Fixed Header Table -->
                        <div class="table-header-fixed">
                            <table class="table table-hover mb-0" id="productsTableHeader">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 52px;">IMAGE</th>
                                        <th>ITEM DESCRIPTION</th>
                                        <th>BRAND</th>
                                        <th>QTY</th>
                                        <th>UNIT</th>
                                        <th>LOT</th>
                                        <th>PRICE</th>
                                        <th>MFG DATE</th>
                                        <th>EXPIRY</th>
                                        <th>ACTION</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                        <!-- Scrollable Body -->
                        <div class="table-body-scrollable" style="max-height: calc(100vh - 350px); overflow-y: auto;">
                            <table class="table table-hover mb-0" id="productsTable">
                                <tbody>
                                    @forelse($allItems as $item)
                                        @php
                                            $expirationDate = $item->expiration_date;
                                            $manufacturingDate = $item->mfg_date ?? null;
                                            $isInactive = ($item->active_status === 'Inactive' || $item->active_status === '0' || $item->active_status === 0);
                                        @endphp
                                        <tr class="{{ $isInactive ? 'table-secondary' : '' }}">
                                            <td class="text-center">
                                                @php
                                                    $imagePath = \App\ItemImageAssetUrl::resolve($item->item_image ?? null);
                                                @endphp
                                                @if($imagePath)
                                                    <img src="{{ $imagePath }}" alt="{{ $item->item_name }}" class="product-list-thumb" width="40" height="40" style="width:40px;height:40px;max-width:40px;max-height:40px;object-fit:cover;" loading="lazy" onclick="window.open(this.src, '_blank')" onerror="handleProductImageError(this)">
                                                @else
                                                    <div class="product-list-thumb-placeholder">
                                                        <i class="bi bi-image text-muted"></i>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ $item->item_name ?? 'N/A' }}</div>
                                                @if($item->description)
                                                    <small class="text-muted d-block" title="{{ $item->description }}">
                                                        {{ strlen($item->description) > 60 ? substr($item->description, 0, 60) . '...' : $item->description }}
                                                    </small>
                                                @endif
                                                @php
                                                    $priceType = strtolower($item->price_type ?? '');
                                                    $badgeText = 'N/A';
                                                    $badgeClass = 'bg-secondary';
                                                    if ($priceType === 'retail') {
                                                        $badgeText = 'Retail';
                                                        $badgeClass = 'bg-primary';
                                                    } elseif ($priceType === 'wholesale') {
                                                        $badgeText = 'Wholesale';
                                                        $badgeClass = 'bg-success';
                                                    }
                                                @endphp
                                                <small class="badge {{ $badgeClass }} mt-1">{{ $badgeText }}</small>
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ $item->brand ?? 'N/A' }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span>{{ number_format($item->quantity_on_hand ?? 0, 0) }}</span>
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ $item->unit ?? 'pcs' }}</span>
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ $item->lot_number ?? 'N/A' }}</span>
                                            </td>
                                            <td class="text-end">
                                                <span class="fw-semibold">₱{{ number_format($item->price ?? 0, 2) }}</span>
                                            </td>
                                            <td>
                                                @if($manufacturingDate)
                                                    @php
                                                        $mfgDateObj = is_string($manufacturingDate) ? \Carbon\Carbon::parse($manufacturingDate) : $manufacturingDate;
                                                    @endphp
                                                    <small class="text-muted">
                                                        {{ $mfgDateObj->format('M d, Y') }}
                                                    </small>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($expirationDate)
                                                    @php
                                                        $expDate = is_string($expirationDate) ? \Carbon\Carbon::parse($expirationDate) : $expirationDate;
                                                    @endphp
                                                    <small class="{{ $expDate < now() ? 'text-danger' : ($expDate <= now()->addDays(30) ? 'text-warning' : 'text-muted') }}">
                                                        {{ $expDate->format('M d, Y') }}
                                                    </small>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($item->source === 'products')
                                                    <div class="d-flex gap-1">
                                                        <a href="{{ route('inventory.edit', $item->id) }}" class="btn btn-sm btn-outline-primary" title="Edit Product">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <form action="{{ route('inventory.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this product? If it has sales history it will be archived (set Inactive) instead of permanently deleted.');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Product">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                @elseif($item->source === 'inventory_products')
                                                    <div class="d-flex gap-1">
                                                        <a href="{{ route('inventory-product.edit', $item->id) }}" class="btn btn-sm btn-outline-primary" title="Edit Product">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <form action="{{ route('inventory-product.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this product? This action cannot be undone.');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Product">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                @elseif($item->source === 'item_lists')
                                                    <div class="d-flex gap-1">
                                                        <a href="{{ route('item-list.edit', $item->id) }}" class="btn btn-sm btn-outline-primary" title="Edit Item">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <form action="{{ route('item-list.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this item? This action cannot be undone and will also delete the corresponding inventory product if it exists.');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Item">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                @elseif($item->source === 'barcodes')
                                                    <div class="d-flex gap-1">
                                                        <a href="{{ route('inventory-barcode.edit', $item->id) }}" class="btn btn-sm btn-outline-primary" title="Edit Product">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <form action="{{ route('inventory-barcode.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this product? This action cannot be undone.');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Product">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                                    No products found
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($allItems->count() > 0)
                            <div class="card-footer bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">Showing {{ $allItems->count() }} item(s)</small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addSupplierForm">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Company Name *</label>
                            <input type="text" class="form-control" id="supplierCompany" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Person *</label>
                            <input type="text" class="form-control" id="supplierContact" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone *</label>
                            <input type="tel" class="form-control" id="supplierPhone" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="supplierEmail">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" id="supplierAddress" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Product Categories Supplied</label>
                            <div class="form-check-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Medical Supplies" id="supplierMedicalSupplies">
                                    <label class="form-check-label" for="supplierMedicalSupplies">Medical Supplies</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Medicines" id="supplierMedicines">
                                    <label class="form-check-label" for="supplierMedicines">Medicines</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Medical Equipment" id="supplierMedicalEquipment">
                                    <label class="form-check-label" for="supplierMedicalEquipment">Medical Equipment</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveSupplier()">Save Supplier</button>
            </div>
        </div>
    </div>
</div>

<!-- Create Purchase Order Modal -->
<div class="modal fade" id="createPOModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Purchase Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createPOForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Supplier *</label>
                            <select class="form-select" id="poSupplier" required>
                                <option value="">Select Supplier</option>
                                <!-- Dynamic supplier options -->
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expected Delivery Date *</label>
                            <input type="date" class="form-control" id="poDeliveryDate" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" id="poNotes" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Products to Order</label>
                            <div class="po-products" id="poProducts">
                                <!-- Dynamic product selection -->
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="generatePO()">Generate PO</button>
            </div>
        </div>
    </div>
</div>

<!-- Quantity Comparison Modal -->
<div class="modal fade" id="quantityComparisonModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-graph-up-arrow me-2"></i>Quantity Comparison: Previous vs Current
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Date From</label>
                        <input type="date" class="form-control" id="comparisonDateFrom">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date To</label>
                        <input type="date" class="form-control" id="comparisonDateTo">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="d-grid gap-2 w-100">
                            <button class="btn btn-primary" onclick="loadQuantityComparison()">
                                <i class="bi bi-search me-1"></i> Load Comparison
                            </button>
                            <button class="btn btn-success" onclick="downloadQuantityComparison()">
                                <i class="bi bi-download me-1"></i> Download Excel
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover" id="comparisonTable">
                        <thead class="table-light">
                            <tr>
                                <th>Item Name</th>
                                <th>Previous Quantity</th>
                                <th>Current Quantity</th>
                                <th>Stock Deduction</th>
                                <th>Type</th>
                                <th>Last Updated</th>
                            </tr>
                        </thead>
                        <tbody id="comparisonTableBody">
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bi bi-arrow-down-circle fs-3 d-block mb-2"></i>
                                        Click "Load Comparison" to view quantity changes
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Filter Products</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="filterForm">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Category</label>
                            <select class="form-select" id="filterCategory">
                                <option value="">All Categories</option>
                                <option value="Medical Supplies">Medical Supplies</option>
                                <option value="Medicines">Medicines</option>
                                <option value="Medical Equipment">Medical Equipment</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Stock Status</label>
                            <select class="form-select" id="filterStockStatus">
                                <option value="">All</option>
                                <option value="out">Out of Stock</option>
                                <option value="low">Low Stock</option>
                                <option value="normal">Normal Stock</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Expiration Status</label>
                            <select class="form-select" id="filterExpiration">
                                <option value="">All</option>
                                <option value="expired">Expired</option>
                                <option value="expiring">Expiring Soon</option>
                                <option value="normal">Normal</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Supplier</label>
                            <select class="form-select" id="filterSupplier">
                                <option value="">All Suppliers</option>
                                <!-- Dynamic supplier options -->
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="applyFilter()">Apply Filter</button>
            </div>
        </div>
    </div>
</div>

<script>
function handleProductImageError(img) {
    if (!img.dataset.altTried) {
        img.dataset.altTried = '1';
        const src = img.getAttribute('src') || '';
        if (src.includes('/public/images/')) {
            img.src = src.replace('/public/images/', '/images/');
            return;
        }
        if (src.includes('/images/')) {
            img.src = src.replace('/images/', '/public/images/');
            return;
        }
    }
    img.onerror = null;
    img.src = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40'%3E%3Crect fill='%23f0f0f0' width='40' height='40'/%3E%3Ctext x='50%25' y='50%25' text-anchor='middle' dy='.3em' fill='%23999' font-family='Arial' font-size='16'%3E📷%3C/text%3E%3C/svg%3E";
}

document.addEventListener('DOMContentLoaded', function() {
    initializeInventory();
});

function initializeInventory() {
    // Set up event listeners
    setupEventListeners();
    
    // Sync column widths between header and body tables
    // Use setTimeout to ensure DOM is fully rendered
    setTimeout(() => {
        syncTableColumnWidths();
    }, 100);
    
    // Re-sync on window resize
    window.addEventListener('resize', () => {
        setTimeout(syncTableColumnWidths, 50);
    });
}

function syncTableColumnWidths() {
    const headerTable = document.getElementById('productsTableHeader');
    const bodyTable = document.getElementById('productsTable');
    
    if (!headerTable || !bodyTable) return;
    
    const headerCells = headerTable.querySelectorAll('thead th');
    const firstBodyRow = bodyTable.querySelector('tbody tr:first-child');
    
    if (headerCells.length === 0 || !firstBodyRow) return;
    
    const bodyCells = firstBodyRow.querySelectorAll('td');
    
    if (bodyCells.length === 0 || bodyCells.length !== headerCells.length) return;
    
    // Calculate total width of body table
    const bodyTableWidth = bodyTable.offsetWidth;
    const headerTableWidth = headerTable.offsetWidth;
    
    // Sync header columns to body columns
    headerCells.forEach((headerCell, index) => {
        if (bodyCells[index]) {
            const bodyWidth = bodyCells[index].offsetWidth;
            headerCell.style.width = bodyWidth + 'px';
            headerCell.style.minWidth = bodyWidth + 'px';
            headerCell.style.maxWidth = bodyWidth + 'px';
        }
    });
    
    // Ensure both tables have same total width
    if (bodyTableWidth > 0 && headerTableWidth !== bodyTableWidth) {
        headerTable.style.width = bodyTableWidth + 'px';
    }
}


function setupEventListeners() {
    // Search input event listener
    const searchInput = document.getElementById('productSearch');
    if (searchInput) {
        // Search only on Enter key press
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch();
            }
        });
    }
}

function performSearch() {
    const searchTerm = document.getElementById('productSearch').value.trim();
    const params = new URLSearchParams(window.location.search);
    
    // Get existing filter parameters
    const existingParams = {
        active_status: params.get('active_status'),
        stock_status: params.get('stock_status'),
        expiration: params.get('expiration'),
    };
    
    // Build new URL with search term
    const newParams = new URLSearchParams();
    
    // Preserve existing filters
    if (existingParams.active_status) {
        newParams.append('active_status', existingParams.active_status);
    }
    if (existingParams.stock_status) {
        newParams.append('stock_status', existingParams.stock_status);
    }
    if (existingParams.expiration) {
        newParams.append('expiration', existingParams.expiration);
    }
    
    // Add search term if provided
    if (searchTerm) {
        newParams.append('search', searchTerm);
    }
    
    // Redirect to same page with search parameter
    window.location.href = '/inventory' + (newParams.toString() ? '?' + newParams.toString() : '');
}

function clearSearch() {
    document.getElementById('productSearch').value = '';
    performSearch();
}

function refreshAlerts() {
    // Refresh alert counts
    location.reload();
}

function viewProduct(id) {
    // Open product details modal
    window.location.href = `/inventory/products/${id}`;
}

function editProduct(id) {
    // Open edit product modal
    window.location.href = `/inventory/products/${id}/edit`;
}

function addBatch(id) {
    // Open add batch modal
    alert('Add batch functionality - Product ID: ' + id);
}

async function archiveProduct(id) {
    if (!confirm('Are you sure you want to archive this product?')) {
        return;
    }
    
    try {
        const response = await fetch(`/inventory/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success || response.ok) {
            showNotification('Product archived successfully!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message || 'Failed to archive product', 'error');
        }
    } catch (error) {
        console.error('Error archiving product:', error);
        showNotification('Error archiving product', 'error');
    }
}

function showNotification(message, type = 'info') {
    const alertClass = type === 'error' ? 'danger' : type;
    const notification = document.createElement('div');
    notification.className = `alert alert-${alertClass} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    notification.style.zIndex = '9999';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

async function saveSupplier() {
    const formData = {
        company_name: document.getElementById('supplierCompany').value,
        contact_person: document.getElementById('supplierContact').value,
        phone: document.getElementById('supplierPhone').value,
        email: document.getElementById('supplierEmail').value,
        address: document.getElementById('supplierAddress').value,
        product_categories: Array.from(document.querySelectorAll('#addSupplierModal input[type="checkbox"]:checked')).map(cb => cb.value).join(',')
    };
    
    console.log('Saving supplier:', formData);
    
    try {
        const response = await fetch('/suppliers', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success || response.ok) {
            showNotification('Supplier added successfully!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('addSupplierModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message || 'Failed to add supplier', 'error');
        }
    } catch (error) {
        console.error('Error saving supplier:', error);
        showNotification('Error saving supplier', 'error');
    }
}

function generatePO() {
    // Generate purchase order
    const formData = {
        supplier: document.getElementById('poSupplier').value,
        delivery_date: document.getElementById('poDeliveryDate').value,
        notes: document.getElementById('poNotes').value
    };
    
    console.log('Generating PO:', formData);
    alert('Purchase Order generated successfully!');
    
    // Close modal
    bootstrap.Modal.getInstance(document.getElementById('createPOModal')).hide();
}

function applyFilter() {
    // Apply filters to products table
    const category = document.getElementById('filterCategory').value;
    const stockStatus = document.getElementById('filterStockStatus').value;
    const expiration = document.getElementById('filterExpiration').value;
    const supplier = document.getElementById('filterSupplier').value;
    
    console.log('Applying filters:', { category, stockStatus, expiration, supplier });
    
    // Close modal
    bootstrap.Modal.getInstance(document.getElementById('filterModal')).hide();
    
    // Reload page with filters (preserve search if exists)
    const params = new URLSearchParams();
    const searchInput = document.getElementById('productSearch');
    if (searchInput && searchInput.value.trim()) {
        params.append('search', searchInput.value.trim());
    }
    if (category) params.append('category', category);
    if (stockStatus) params.append('stock_status', stockStatus);
    if (expiration) params.append('expiration', expiration);
    if (supplier) params.append('supplier', supplier);
    
    window.location.href = `/inventory?${params.toString()}`;
}

function exportProducts() {
    window.location.href = '/inventory/export';
    showNotification('Exporting products...', 'info');
}

// JavaScript number_format function (similar to PHP's number_format)
function number_format(number, decimals = 2, decPoint = '.', thousandsSep = ',') {
    if (number === null || number === undefined || isNaN(number)) {
        return '0.00';
    }
    
    number = parseFloat(number);
    const sign = number < 0 ? '-' : '';
    number = Math.abs(number);
    
    const rounded = number.toFixed(decimals);
    const parts = rounded.split('.');
    let integerPart = parts[0];
    const decimalPart = parts[1] || '';
    
    // Add thousands separator
    if (thousandsSep) {
        integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSep);
    }
    
    return sign + integerPart + (decimals > 0 ? decPoint + decimalPart : '');
}

async function loadQuantityComparison() {
    const dateFrom = document.getElementById('comparisonDateFrom')?.value || '';
    const dateTo = document.getElementById('comparisonDateTo')?.value || '';
    const tbody = document.getElementById('comparisonTableBody');
    
    if (!tbody) {
        console.error('Comparison table body not found');
        return;
    }
    
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>';
    
    try {
        const params = new URLSearchParams();
        if (dateFrom) params.append('date_from', dateFrom);
        if (dateTo) params.append('date_to', dateTo);
        
        // Get CSRF token safely
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.content : '';
        
        const url = `/inventory/quantity-comparison${params.toString() ? '?' + params.toString() : ''}`;
        console.log('Fetching comparison data from:', url);
        
        const headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        };
        
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }
        
        const response = await fetch(url, {
            method: 'GET',
            headers: headers,
            credentials: 'same-origin'
        });
        
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            let errorMessage = `HTTP ${response.status}: ${response.statusText}`;
            try {
                const errorData = await response.json();
                errorMessage = errorData.message || errorMessage;
            } catch (e) {
                const errorText = await response.text();
                if (errorText) errorMessage = errorText.substring(0, 200);
            }
            throw new Error(errorMessage);
        }
        
        const result = await response.json();
        
        console.log('Comparison result:', result);
        
        if (result.success && result.data && result.data.length > 0) {
            // Filter to show only products with changes, or all if no changes exist
            const productsToShow = result.products_with_changes > 0 
                ? result.data.filter(item => item.has_change)
                : result.data;
            
            if (productsToShow.length > 0) {
                tbody.innerHTML = productsToShow.map(item => {
                    const change = item.change || 0;
                    const hasChange = Math.abs(change) > 0.01;
                    const changeClass = !hasChange ? 'text-muted' : (change > 0 ? 'text-success' : 'text-danger');
                    const changeIcon = !hasChange ? '<i class="bi bi-dash"></i>' : (change > 0 ? '<i class="bi bi-arrow-up"></i>' : '<i class="bi bi-arrow-down"></i>');
                    
                    return `
                        <tr>
                            <td><strong>${item.item_name}</strong></td>
                            <td>${item.previous_quantity !== null ? number_format(item.previous_quantity, 0) : number_format(item.current_quantity, 0)}</td>
                            <td><strong>${number_format(item.current_quantity, 0)}</strong></td>
                            <td class="${changeClass}">
                                ${hasChange ? `${changeIcon} ${change > 0 ? '+' : ''}${number_format(change, 0)}` : '<span class="text-muted">No change</span>'}
                            </td>
                            <td>
                                ${item.movement_type || 'Paid'}
                            </td>
                            <td>
                                ${item.last_updated ? new Date(item.last_updated).toLocaleString() : 'No updates recorded'}
                            </td>
                        </tr>
                    `;
                }).join('');
                
                // Add summary row
                if (result.total_products && result.products_with_changes !== undefined) {
                    tbody.innerHTML += `
                        <tr class="table-info">
                            <td colspan="6" class="text-center fw-bold">
                                Showing ${productsToShow.length} of ${result.total_products} products
                                ${result.products_with_changes > 0 ? `(${result.products_with_changes} with changes)` : '(No changes detected)'}
                            </td>
                        </tr>
                    `;
                }
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                No products with quantity changes found in the selected period
                            </div>
                        </td>
                    </tr>
                `;
            }
        } else {
            // Show all products even if no changes or if result.data exists but is empty
            if (result.data && Array.isArray(result.data) && result.data.length > 0) {
                tbody.innerHTML = result.data.map(item => {
                    const change = item.change || 0;
                    const hasChange = Math.abs(change) > 0.01;
                    const changeClass = !hasChange ? 'text-muted' : (change > 0 ? 'text-success' : 'text-danger');
                    const changeIcon = !hasChange ? '<i class="bi bi-dash"></i>' : (change > 0 ? '<i class="bi bi-arrow-up"></i>' : '<i class="bi bi-arrow-down"></i>');
                    
                    return `
                        <tr>
                            <td><strong>${item.item_name || 'N/A'}</strong></td>
                            <td>${item.previous_quantity !== null ? number_format(item.previous_quantity, 0) : number_format(item.current_quantity, 0)}</td>
                            <td><strong>${number_format(item.current_quantity, 0)}</strong></td>
                            <td class="${changeClass}">
                                ${hasChange ? `${changeIcon} ${change > 0 ? '+' : ''}${number_format(change, 0)}` : '<span class="text-muted">No change</span>'}
                            </td>
                            <td>
                                ${item.movement_type || 'Paid'}
                            </td>
                            <td>
                                ${item.last_updated ? new Date(item.last_updated).toLocaleString() : 'No updates recorded'}
                            </td>
                        </tr>
                    `;
                }).join('');
                
                if (result.total_products !== undefined) {
                    tbody.innerHTML += `
                        <tr class="table-info">
                            <td colspan="6" class="text-center fw-bold">
                                Showing ${result.data.length} of ${result.total_products} products
                            </td>
                        </tr>
                    `;
                }
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                ${result.message || 'No comparison data found. Products may not have quantity changes yet.'}
                            </div>
                        </td>
                    </tr>
                `;
            }
        }
    } catch (error) {
        console.error('Error loading comparison:', error);
        console.error('Error stack:', error.stack);
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-4 text-danger">
                    <div>
                        <i class="bi bi-exclamation-triangle fs-3 d-block mb-2"></i>
                        <strong>Error loading comparison data</strong>
                        <br>
                        <small class="text-muted">${error.message || 'Unknown error occurred'}</small>
                        <br>
                        <button class="btn btn-sm btn-outline-primary mt-2" onclick="loadQuantityComparison()">
                            <i class="bi bi-arrow-clockwise"></i> Retry
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }
}

async function downloadQuantityComparison() {
    const dateFrom = document.getElementById('comparisonDateFrom')?.value || '';
    const dateTo = document.getElementById('comparisonDateTo')?.value || '';
    const params = new URLSearchParams();
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);

    const url = `/inventory/quantity-comparison/export${params.toString() ? '?' + params.toString() : ''}`;
    console.log('Downloading comparison CSV from:', url);
    window.location.href = url;
}

function showQuantityComparison() {
    console.log('showQuantityComparison called');
    try {
        const modalElement = document.getElementById('quantityComparisonModal');
        if (!modalElement) {
            console.error('Modal element not found');
            alert('Comparison modal not found. Please refresh the page.');
            return;
        }
        
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        
        // Set default dates - leave empty to show all products with current state
        // User can optionally filter by date range
        const dateFromEl = document.getElementById('comparisonDateFrom');
        const dateToEl = document.getElementById('comparisonDateTo');
        
        if (dateFromEl) dateFromEl.value = '';
        if (dateToEl) dateToEl.value = '';
        
        // Load comparison immediately after modal is shown
        modalElement.addEventListener('shown.bs.modal', function onModalShown() {
            loadQuantityComparison();
            modalElement.removeEventListener('shown.bs.modal', onModalShown);
        }, { once: true });
    } catch (error) {
        console.error('Error in showQuantityComparison:', error);
        alert('Error opening comparison modal: ' + error.message);
    }
}

// Also assign to window for global access
window.showQuantityComparison = showQuantityComparison;

</script>

<!-- Generate Barcode Modal -->
<div class="modal fade" id="generateBarcodeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-upc-scan me-2"></i>Generate Barcode</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2">
                    <small><i class="bi bi-info-circle me-1"></i>Generate creates a printable barcode only. Scan it in the <strong>BarcodeScanner</strong> app and tap <strong>Add</strong> to save the product to inventory.</small>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Item Description</label>
                            <input type="text" class="form-control" id="barcodeItemName" placeholder="Enter item description">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Brand</label>
                            <input type="text" class="form-control" id="barcodeBrand" placeholder="Enter brand name">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Original price</label>
                            <input type="number" class="form-control" id="barcodeOriginalPrice" placeholder="Enter original price (optional)" step="0.01" min="0">
                            <small class="text-muted">Optional: purchase or landed cost for this item</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Input Price</label>
                            <input type="number" class="form-control" id="barcodePrice" placeholder="Enter price" step="0.01" min="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price Type</label>
                            <select class="form-select" id="barcodePriceType">
                                <option value="retail" selected>Retail</option>
                                <option value="wholesale">Wholesale</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Unit</label>
                            <select class="form-select" id="barcodeUnit">
                                <option value="box" selected>Box</option>
                                <option value="case">Case</option>
                                <option value="roll">Roll</option>
                                <option value="gal">Gal</option>
                                <option value="set">Set</option>
                                <option value="bottle">Bottle</option>
                                <option value="unit">Unit</option>
                                <option value="pair">Pair</option>
                                <option value="pck">Pck</option>
                                <option value="piece">Piece</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">LOT</label>
                            <input type="text" class="form-control" id="barcodeLot" placeholder="Enter lot number (optional)">
                            <small class="text-muted">Optional: Enter lot number for this item</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expiration Date</label>
                            <input type="date" class="form-control" id="barcodeExpirationDate">
                            <small class="text-muted">Optional: Enter expiration date for the item</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">MFG Date</label>
                            <input type="date" class="form-control" id="barcodeMfgDate">
                            <small class="text-muted">Optional: Enter manufacturing date for the item</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Active Status</label>
                            <select class="form-select" id="barcodeActiveStatus">
                                <option value="Active" selected>Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Item Image</label>
                            <input type="file" class="form-control" id="barcodeItemImage" accept="image/*">
                            <small class="text-muted">Optional: Upload an image for this item</small>
                            <div id="barcodeImagePreview" class="mt-2" style="display: none;">
                                <img id="barcodeImagePreviewImg" src="" alt="Preview" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                                <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeBarcodeImage()">
                                    <i class="bi bi-x-circle me-1"></i>Remove Image
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity on Hand</label>
                            <input type="number" class="form-control" id="barcodeQuantityOnHand" placeholder="Enter quantity" step="0.01" min="0" value="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Barcode Value</label>
                            <input type="text" class="form-control" id="barcodeValue" placeholder="Auto-generated or enter manually">
                            <small class="text-muted">Leave empty to auto-generate from item name</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Barcode Type</label>
                            <select class="form-select" id="barcodeType">
                                <option value="CODE128" selected>CODE128</option>
                                <option value="CODE39">CODE39</option>
                                <option value="EAN13">EAN13</option>
                                <option value="EAN8">EAN8</option>
                                <option value="UPC">UPC</option>
                                <option value="ITF14">ITF14</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Barcode Format</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="barcodeFormat" id="formatSVG" value="svg" checked>
                                <label class="form-check-label" for="formatSVG">SVG (Vector)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="barcodeFormat" id="formatPNG" value="png">
                                <label class="form-check-label" for="formatPNG">PNG (Image)</label>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary w-100" onclick="generateBarcode()">
                            <i class="bi bi-upc-scan me-2"></i>Generate Barcode
                        </button>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h6 class="mb-0">Barcode Preview</h6>
                            </div>
                            <div class="card-body text-center">
                                <div id="barcodePreview" class="mb-3" style="min-height: 150px; display: flex; align-items: center; justify-content: center;">
                                    <p class="text-muted">Enter item description and price to generate barcode</p>
                                </div>
                                <div id="barcodeActions" style="display: none;">
                                    <button type="button" class="btn btn-success btn-sm me-2" onclick="downloadBarcode()">
                                        <i class="bi bi-download me-1"></i>Download
                                    </button>
                                    <button type="button" class="btn btn-info btn-sm" onclick="printBarcode()">
                                        <i class="bi bi-printer me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/JsBarcode.all.min.js') }}"></script>
<script>
// Barcode Generation Functions
let currentBarcodeData = null;
let currentBarcodeSvg = null;
let barcodeItemImageBase64 = null;

// Handle image upload
document.getElementById('barcodeItemImage')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Validate file type
        if (!file.type.startsWith('image/')) {
            showNotification('Please select a valid image file', 'warning');
            e.target.value = '';
            return;
        }
        
        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            showNotification('Image size must be less than 5MB', 'warning');
            e.target.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(event) {
            barcodeItemImageBase64 = event.target.result;
            const previewDiv = document.getElementById('barcodeImagePreview');
            const previewImg = document.getElementById('barcodeImagePreviewImg');
            
            if (previewDiv && previewImg) {
                previewImg.src = barcodeItemImageBase64;
                previewDiv.style.display = 'block';
            }
        };
        reader.readAsDataURL(file);
    }
});

// Function to remove uploaded image
function removeBarcodeImage() {
    barcodeItemImageBase64 = null;
    const imageInput = document.getElementById('barcodeItemImage');
    const previewDiv = document.getElementById('barcodeImagePreview');
    
    if (imageInput) {
        imageInput.value = '';
    }
    if (previewDiv) {
        previewDiv.style.display = 'none';
    }
}

// Function to convert text to barcode digits
function convertNameToNumbers(name) {
    let result = '';
    for (let i = 0; i < name.length; i++) {
        const char = name[i];
        if (char >= 'A' && char <= 'Z') {
            result += (char.charCodeAt(0) - 64).toString().padStart(2, '0');
        } else if (char >= 'a' && char <= 'z') {
            result += (char.charCodeAt(0) - 96).toString().padStart(2, '0');
        } else if (char >= '0' && char <= '9') {
            result += char;
        }
    }
    return result.replace(/\D/g, '');
}

/**
 * Auto barcode must differ for retail vs wholesale even with the same name/brand.
 * Price-type digits are always appended last so truncation cannot drop them.
 */
function buildAutoBarcodeValue(itemName, brand, priceType) {
    const typePart = (priceType || '').toLowerCase() === 'wholesale' ? '02' : '01';
    const namePart = convertNameToNumbers(itemName || '');
    const brandPart = convertNameToNumbers(brand || '');
    const body = (namePart + brandPart).replace(/\D/g, '');
    // Keep last 2 chars for price type (01 retail / 02 wholesale)
    const maxBodyLen = 18;
    const truncatedBody = (body || String(Date.now()).slice(-10)).substring(0, maxBodyLen);

    return (truncatedBody + typePart).substring(0, 20);
}

function refreshAutoBarcodeIfNeeded() {
    const barcodeValueInput = document.getElementById('barcodeValue');
    if (!barcodeValueInput || barcodeValueInput.dataset.manual === '1') {
        return;
    }

    const itemName = document.getElementById('barcodeItemName')?.value?.trim() || '';
    const brand = document.getElementById('barcodeBrand')?.value?.trim() || '';
    const priceType = document.getElementById('barcodePriceType')?.value || 'retail';
    if (!itemName) {
        return;
    }

    barcodeValueInput.value = buildAutoBarcodeValue(itemName, brand, priceType);
}

// Auto-generate barcode from name + brand + price type (variants get distinct codes)
document.getElementById('barcodeItemName')?.addEventListener('input', function () {
    refreshAutoBarcodeIfNeeded();
});
document.getElementById('barcodeBrand')?.addEventListener('input', function () {
    refreshAutoBarcodeIfNeeded();
});
document.getElementById('barcodePriceType')?.addEventListener('change', function () {
    const barcodeValueInput = document.getElementById('barcodeValue');
    // Price type must always produce a different barcode — clear manual lock
    if (barcodeValueInput) {
        barcodeValueInput.dataset.manual = '0';
    }
    refreshAutoBarcodeIfNeeded();
});
document.getElementById('barcodeValue')?.addEventListener('input', function (e) {
    e.target.dataset.manual = e.target.value.trim() ? '1' : '0';
});

function parseMoney(value) {
    if (value === null || value === undefined || value === '') {
        return NaN;
    }
    if (typeof value === 'number') {
        return value;
    }
    const cleaned = String(value).replace(/[₱P,\s]/gi, '');
    return parseFloat(cleaned);
}

function renderBarcodePreview(previewDiv, barcodeValue, barcodeType, format) {
    previewDiv.innerHTML = '';

    if (format === 'svg') {
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.id = 'barcodeSvg';
        previewDiv.appendChild(svg);
        JsBarcode('#barcodeSvg', barcodeValue, {
            format: barcodeType,
            width: 2,
            height: 80,
            displayValue: true,
            fontSize: 14,
            margin: 10
        });
        currentBarcodeSvg = svg;
        return;
    }

    const canvas = document.createElement('canvas');
    canvas.id = 'barcodeCanvas';
    previewDiv.appendChild(canvas);
    JsBarcode('#barcodeCanvas', barcodeValue, {
        format: barcodeType,
        width: 2,
        height: 80,
        displayValue: true,
        fontSize: 14,
        margin: 10
    });
    currentBarcodeSvg = canvas;
}

async function generateBarcode() {
    if (typeof JsBarcode === 'undefined') {
        showNotification('Barcode library failed to load. Refresh the page or check that /js/JsBarcode.all.min.js is reachable.', 'error');
        return;
    }

    const itemName = document.getElementById('barcodeItemName').value.trim();
    const brand = document.getElementById('barcodeBrand').value.trim();
    const originalPriceRaw = document.getElementById('barcodeOriginalPrice')?.value?.trim() ?? '';
    const price = document.getElementById('barcodePrice').value.trim();
    const priceType = document.getElementById('barcodePriceType').value;
    const unit = document.getElementById('barcodeUnit').value;
    const lot = document.getElementById('barcodeLot').value.trim();
    const expirationDate = document.getElementById('barcodeExpirationDate').value;
    const mfgDate = document.getElementById('barcodeMfgDate').value;
    const activeStatus = document.getElementById('barcodeActiveStatus').value;
    const quantityOnHand = document.getElementById('barcodeQuantityOnHand').value.trim() || '0';
    let barcodeValue = document.getElementById('barcodeValue').value.trim();
    const barcodeType = document.getElementById('barcodeType').value;
    const format = document.querySelector('input[name="barcodeFormat"]:checked').value;
    const previewDiv = document.getElementById('barcodePreview');
    const actionsDiv = document.getElementById('barcodeActions');
    
    if (!itemName) {
        showNotification('Please enter item name', 'warning');
        return;
    }
    
    if (!price || parseMoney(price) <= 0) {
        showNotification('Please enter a valid price', 'warning');
        return;
    }
    
    // Always rebuild auto barcode from name + brand + price type unless user typed it manually
    if (document.getElementById('barcodeValue')?.dataset.manual !== '1') {
        barcodeValue = buildAutoBarcodeValue(itemName, brand, priceType);
        document.getElementById('barcodeValue').value = barcodeValue;
    } else if (!barcodeValue) {
        barcodeValue = buildAutoBarcodeValue(itemName, brand, priceType);
        document.getElementById('barcodeValue').value = barcodeValue;
        document.getElementById('barcodeValue').dataset.manual = '0';
    }
    
    // Validate barcode value based on type
    if (barcodeType === 'EAN13' && (barcodeValue.length !== 12 && barcodeValue.length !== 13)) {
        showNotification('EAN13 requires 12 or 13 digits', 'warning');
        return;
    }
    if (barcodeType === 'EAN8' && (barcodeValue.length !== 7 && barcodeValue.length !== 8)) {
        showNotification('EAN8 requires 7 or 8 digits', 'warning');
        return;
    }
    if (barcodeType === 'UPC' && (barcodeValue.length !== 11 && barcodeValue.length !== 12)) {
        showNotification('UPC requires 11 or 12 digits', 'warning');
        return;
    }
    
    try {
        const saveData = {
            barcode_value: barcodeValue,
            item_name: itemName,
            brand: brand || null,
            price: parseMoney(price),
            original_price: originalPriceRaw === '' ? null : parseMoney(originalPriceRaw),
            price_type: priceType,
            unit: unit,
            lot_number: lot || null,
            expiration_date: expirationDate || null,
            mfg_date: mfgDate || null,
            barcode_type: barcodeType,
            active_status: activeStatus,
            description: null,
            quantity_on_hand: parseFloat(quantityOnHand) || 0
        };

        if (barcodeItemImageBase64) {
            saveData.item_image = barcodeItemImageBase64;
        }

        // Queue first — server is the source of truth for the final barcode value.
        const saveResponse = await fetch('/barcodes/pending', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify(saveData)
        });

        const saveResponseData = await saveResponse.json();
        if (!saveResponseData.success) {
            console.error('Failed to queue barcode:', saveResponseData.message);
            showNotification('Failed to queue barcode: ' + (saveResponseData.message || 'Unknown error'), 'error');
            return;
        }

        const finalBarcode = saveResponseData.barcode?.barcode_value || barcodeValue;
        if (finalBarcode !== barcodeValue) {
            showNotification('Barcode adjusted to stay unique: ' + finalBarcode, 'info');
        }
        barcodeValue = finalBarcode;
        document.getElementById('barcodeValue').value = barcodeValue;

        renderBarcodePreview(previewDiv, barcodeValue, barcodeType, format);

        currentBarcodeData = {
            itemName: itemName,
            price: parseMoney(price).toFixed(2),
            priceType: priceType,
            unit: unit.toUpperCase(),
            expirationDate: expirationDate,
            mfgDate: mfgDate,
            barcodeValue: barcodeValue
        };

        console.log('✓ Barcode queued for scanner confirmation', barcodeValue);
        showNotification('Barcode ready — scan it in BarcodeScanner and tap Add to save the product.', 'success');
        actionsDiv.style.display = 'block';
        
        // Only show generic success if save was successful (specific message already shown)
        // The save notification will handle the success message
    } catch (error) {
        console.error('Barcode generation error:', error);
        showNotification('Error generating barcode: ' + error.message, 'error');
    }
}

function downloadBarcode() {
    const format = document.querySelector('input[name="barcodeFormat"]:checked').value;
    const barcodeType = document.getElementById('barcodeType').value;
    
    if (!currentBarcodeData || !currentBarcodeSvg) {
        showNotification('Please generate a barcode first', 'warning');
        return;
    }
    
    const barcodeValue = currentBarcodeData.barcodeValue;
    
    try {
        if (format === 'svg') {
            const svg = currentBarcodeSvg;
            const svgData = new XMLSerializer().serializeToString(svg);
            const blob = new Blob([svgData], { type: 'image/svg+xml' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `barcode-${barcodeValue}-${barcodeType}.svg`;
            link.click();
            URL.revokeObjectURL(url);
        } else {
            const canvas = currentBarcodeSvg;
            canvas.toBlob(function(blob) {
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `barcode-${barcodeValue}-${barcodeType}.png`;
                link.click();
                URL.revokeObjectURL(url);
            });
        }
        
        showNotification('Barcode downloaded successfully!', 'success');
    } catch (error) {
        console.error('Download error:', error);
        showNotification('Error downloading barcode', 'error');
    }
}

function printBarcode() {
    if (!currentBarcodeSvg || !currentBarcodeData) {
        showNotification('Please generate a barcode first', 'warning');
        return;
    }
    
    const printWindow = window.open('', '_blank');
    const { itemName, barcodeValue } = currentBarcodeData;
    
    // Clone the barcode SVG/canvas and remove the displayValue text if it exists
    let barcodeHTML = '';
    if (currentBarcodeSvg.tagName === 'svg') {
        // Clone SVG and remove text elements (barcode value display)
        const svgClone = currentBarcodeSvg.cloneNode(true);
        const textElements = svgClone.querySelectorAll('text');
        textElements.forEach(text => text.remove());
        barcodeHTML = svgClone.outerHTML;
    } else if (currentBarcodeSvg.tagName === 'CANVAS') {
        // For canvas, create a new barcode without displayValue
        const tempDiv = document.createElement('div');
        const tempCanvas = document.createElement('canvas');
        tempDiv.appendChild(tempCanvas);
        
        // Generate barcode without displayValue
        JsBarcode(tempCanvas, barcodeValue, {
            format: document.getElementById('barcodeType').value,
            width: 2,
            height: 80,
            displayValue: false,
            fontSize: 14,
            margin: 10
        });
        
        barcodeHTML = tempCanvas.outerHTML;
    } else {
        barcodeHTML = currentBarcodeSvg.outerHTML || '';
    }
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
            <head>
                <title>Barcode</title>
                <style>
                    @media print {
                        @page { margin: 10mm; }
                        body { margin: 0; padding: 0; }
                    }
                    body {
                        font-family: Arial, sans-serif;
                        text-align: center;
                        padding: 20px;
                        margin: 0;
                    }
                    .barcode-container {
                        margin: 20px 0;
                    }
                    .item-name {
                        font-size: 18px;
                        font-weight: bold;
                        margin-bottom: 15px;
                    }
                    .barcode-value {
                        font-size: 12px;
                        color: #666;
                        margin-top: 10px;
                    }
                </style>
            </head>
            <body>
                <div class="barcode-container">
                    <div class="item-name">${itemName}</div>
                    ${barcodeHTML}
                    <div class="barcode-value">${barcodeValue}</div>
                </div>
            </body>
        </html>
    `);
    printWindow.document.close();
    
    setTimeout(() => {
        printWindow.print();
        setTimeout(() => printWindow.close(), 1000);
    }, 250);
}

// Clear selection when modal is closed
document.getElementById('generateBarcodeModal')?.addEventListener('hidden.bs.modal', function() {
    currentBarcodeData = null;
    currentBarcodeSvg = null;
    barcodeItemImageBase64 = null;
    document.getElementById('barcodeItemName').value = '';
    document.getElementById('barcodeOriginalPrice').value = '';
    document.getElementById('barcodePrice').value = '';
    document.getElementById('barcodePriceType').value = 'retail';
    document.getElementById('barcodeUnit').value = 'pcs';
    document.getElementById('barcodeExpirationDate').value = '';
    document.getElementById('barcodeMfgDate').value = '';
    document.getElementById('barcodeValue').value = '';
    document.getElementById('barcodeBrand').value = '';
    document.getElementById('barcodeLot').value = '';
    document.getElementById('barcodeQuantityOnHand').value = '0';
    document.getElementById('barcodePreview').innerHTML = '<p class="text-muted">Enter item description and price to generate barcode</p>';
    document.getElementById('barcodeActions').style.display = 'none';
    removeBarcodeImage();
});
</script>
@endpush