@extends('layouts.app')

@section('content')

<div class="pos-container">
    <!-- Header Section -->
    <div class="pos-header">
        <div class="d-flex justify-content-between align-items-center">
            <div class="brand-section">
                <div class="brand-logo">
                    <img src="{{ asset('/images/logo.jpg') }}" alt="REDEMP Logo" class="logo-image">
                </div>
                <div class="brand-text">
                    <h3 class="brand-name mb-0">REDEMP</h3>
                    <small class="brand-tagline">MEDICAL SUPPLIES & PHARMACY - Cashier Dashboard</small>
                </div>
            </div>
            <div class="header-controls">
                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#freeSampleModal">
                    <i class="bi bi-gift me-1"></i>Sample Request
                </button>
                <button class="btn btn-outline-info btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#sampleHistoryModal">View Requests</button>
                <button class="btn btn-outline-warning btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#discrepancyModal">Report Discrepancy</button>
                <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#discrepancyHistoryModal">View Discrepancies</button>
                <button class="btn btn-outline-info btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#customerInfoSection">
                    <i class="bi bi-person-circle me-1"></i>Customer Info
                </button>
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm">Logout</button>
            </form>
            </div>
        </div>
    </div>

    <!-- Shift Status Bar -->
    <div class="shift-status-bar">
        <div class="d-flex justify-content-between align-items-center">
            <div class="shift-info">
                <span class="badge bg-secondary" id="shiftStatus">No Active Shift</span>
                <span class="ms-2" id="cashierName"></span>
                <span class="ms-2" id="shiftTime">Start a shift to begin</span>
                </div>
            <!-- No Shift Buttons -->
            <div class="shift-actions" id="noShiftActions">
                <button class="btn btn-success btn-sm" onclick="if(window.showStartShiftModal){window.showStartShiftModal();}else{console.error('showStartShiftModal not available');}">
                    <i class="bi bi-play-circle me-1"></i>Start Shift
                </button>
            </div>
            <!-- Active Shift Buttons -->
            <div class="shift-actions" id="activeShiftActions" style="display: none;">
                <button class="btn btn-outline-primary btn-sm" id="endShiftBtn" onclick="if(typeof window.showEndShiftModal === 'function') { window.showEndShiftModal(); } else { console.error('showEndShiftModal not available'); }">
                    <i class="bi bi-stop-circle me-1"></i>End Shift
                </button>
                <button class="btn btn-outline-warning btn-sm" onclick="clearActiveShift()" title="Clear stuck shift">
                    <i class="bi bi-x-circle me-1"></i>Clear Shift
                </button>
        </div>
                </div>
            </div>

    <!-- Customer Information Section -->
    <div class="pos-sample-section">
        <div class="collapse" id="customerInfoSection">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-person-circle me-2"></i>Customer Information</h5>
                </div>
                <div class="card-body">
                    <!-- Customer Search -->
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-search me-1"></i>Search Customer</label>
                        <div class="position-relative">
                            <input type="text" class="form-control" id="customerSearchInput" placeholder="Search by registered name, TIN, or business address..." autocomplete="off">
                            <div id="customerSearchResults" class="dropdown-menu w-100" style="max-height: 300px; overflow-y: auto; display: none; z-index: 1060;"></div>
                        </div>
                        <small class="text-muted">Type to search for customers</small>
                    </div>
                    
                    <!-- Customer Information Display (Read-only) -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-person me-1"></i>Registered Name</label>
                                <input type="text" class="form-control" id="customerInfoRegisteredName" placeholder="No customer selected" readonly style="background-color: #f8f9fa;">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-card-text me-1"></i>TIN (Tax Identification Number)</label>
                                <input type="text" class="form-control" id="customerInfoTin" placeholder="-" readonly style="background-color: #f8f9fa;">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-geo-alt me-1"></i>Business Address</label>
                        <textarea class="form-control" id="customerInfoBusinessAddress" rows="3" placeholder="-" readonly style="background-color: #f8f9fa;"></textarea>
                    </div>
                    <div class="text-end">
                        <button class="btn btn-sm btn-secondary" onclick="clearCustomerInfo()">
                            <i class="bi bi-x-circle me-1"></i>Clear
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main POS Layout -->
    <div class="pos-main-layout">
        <!-- Left Side - Product Selection & Cart -->
        <div class="pos-left-section">
            <!-- Product Search & Selection -->
            <div class="pos-product-section">
                <div class="pos-section-header">
                    <h5><i class="bi bi-search me-2"></i>Product Selection</h5>
        </div>
                <div class="pos-search-container position-relative">
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                        <input type="text" id="barcodeInput" class="form-control" placeholder="Scan barcode or enter product name/SKU" autocomplete="off">
                        <button class="btn btn-outline-secondary" type="button" id="barcodeScanBtn">
                            <i class="bi bi-camera"></i>
                        </button>
                        <div id="searchResults" class="dropdown-menu w-100"></div>
                </div>
                    
                    <!-- Product Details Display -->
                    <div id="productDetails" class="product-details-card" style="display: none;">
                        <div class="row">
                            <div class="col-4 text-center mb-2">
                                <img id="productImage" src="" alt="Product Image" class="rounded" style="width: 48px; height: 48px; max-width: 48px; max-height: 48px; display: none; object-fit: cover; border: 1px solid #e0e0e0;">
                            </div>
                            <div class="col-8">
                                <h6 id="productName" class="mb-1"></h6>
                                <small class="text-muted" id="productSku"></small>
                                <div class="mt-2">
                                    <span class="fw-bold text-primary fs-5" id="productPrice"></span>
                                    <span id="productPriceType" class="ms-2 badge bg-info" style="display: none;"></span>
                                    <span id="productUnit" class="ms-1 badge bg-secondary" style="display: none;"></span>
                                    <span class="ms-2 badge" id="stockBadge"></span>
                                </div>
                                <div id="expirationWarning" class="mt-1" style="display: none;">
                                    <small class="text-warning">
                                        <i class="bi bi-exclamation-triangle"></i> Expires soon!
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <div id="wholesaleConversionAlert" class="alert alert-info mb-2" style="display: none;">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <i class="bi bi-info-circle me-1"></i>
                                            <strong>Wholesale version available!</strong>
                                            <small class="d-block text-muted mt-1">Request to convert wholesale stock to retail?</small>
                                        </div>
                                        <button class="btn btn-sm btn-primary" id="requestConversionBtn">
                                            <i class="bi bi-arrow-repeat me-1"></i> Request Conversion
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 text-end">
                                <button class="btn btn-success btn-sm" id="addToCartBtn">
                                    <i class="bi bi-cart-plus"></i> Add
                                </button>
                            </div>
                        </div>
                    </div>
    </div>

                <!-- Quick Access Products -->
                <div class="pos-quick-access">
                    <small class="text-muted">Quick Access:</small>
                    <div id="quickAccess" class="quick-access-buttons"></div>
                </div>
            </div>

            <!-- Shopping Cart -->
            <div class="pos-cart-section">
                <div class="pos-section-header">
                    <h5><i class="bi bi-cart3 me-2"></i>Shopping Cart</h5>
                </div>
                <div class="pos-cart-table">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Total</th>
                                <th width="60"></th>
                            </tr>
                        </thead>
                        <tbody id="cartTableBody">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">
                                    <i class="bi bi-cart-x me-2"></i>No items in cart
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Side - Transaction Summary & Controls -->
        <div class="pos-right-section">
            <!-- Transaction Summary -->
            <div class="pos-summary-section">
                <div class="pos-section-header">
                    <h5><i class="bi bi-receipt me-2"></i>Transaction Summary</h5>
                </div>
                <div class="pos-summary-content">
                    <div class="summary-line">
                        <span>Number of Items:</span>
                        <strong id="itemCount">0</strong>
                    </div>
                    <div class="summary-line">
                        <span>Subtotal:</span>
                        <strong id="subtotal">₱ 0.00</strong>
                    </div>
                    <div class="summary-line" id="discountLine" style="display: none;">
                        <span>Discount (3%):</span>
                        <strong id="discountAmount" class="text-success">₱ 0.00</strong>
                    </div>
                    <div class="summary-line total-line">
                        <span>Grand Total:</span>
                        <strong id="total">₱ 0.00</strong>
            </div>
        </div>
    </div>

            <!-- Payment Controls -->
            <div class="pos-payment-section">
                <div class="pos-section-header">
                    <h5><i class="bi bi-credit-card me-2"></i>Payment</h5>
                </div>
                <div class="pos-payment-controls">
                    <!-- Payment Method Selection -->
                    <div class="mb-3">
                        <p class="form-label mb-1">Payment Method</p>
                        <div class="payment-method-buttons">
                            <button class="btn btn-outline-success payment-method-btn active" data-method="cash">
                                <i class="bi bi-cash-coin"></i><br><small>Cash</small>
                            </button>
                            <button class="btn btn-outline-primary payment-method-btn" data-method="e_wallet">
                                <i class="bi bi-wallet2"></i><br><small>E-Wallet</small>
                            </button>
                            <button class="btn btn-outline-info payment-method-btn" data-method="check">
                                <i class="bi bi-receipt"></i><br><small>Check</small>
                            </button>
                        </div>
                    </div>

                    <!-- Senior Citizen & PWD Discount -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <p class="form-label mb-0">Discount</p>
                            <button class="btn btn-outline-danger btn-sm" id="removeDiscountBtn" style="display: none;" title="Remove discount">
                                <i class="bi bi-x-circle me-1"></i>Remove Discount
                            </button>
                        </div>
                        <div class="discount-buttons">
                            <button class="btn btn-outline-warning discount-btn" data-discount="senior" id="seniorCitizenBtn">
                                <i class="bi bi-person-badge"></i><br><small>Senior Citizen</small><br><small class="text-muted">(20% OFF)</small>
                            </button>
                            <button class="btn btn-outline-warning discount-btn" data-discount="pwd" id="pwdBtn">
                                <i class="bi bi-heart-pulse"></i><br><small>PWD</small><br><small class="text-muted">(20% OFF)</small>
                            </button>
                        </div>
                    </div>

                    <!-- ID Display Section -->
                    <div class="mb-3" id="idDisplaySection" style="display: none;">
                        <p class="form-label mb-1">ID Verification</p>
                        
                        <!-- Toggle between Image and Manual Input -->
                        <div class="btn-group w-100 mb-2" role="group">
                            <input type="radio" class="btn-check" name="idVerificationMethod" id="idMethodImage" value="image" checked>
                            <label class="btn btn-outline-primary btn-sm" for="idMethodImage">
                                <i class="bi bi-camera"></i> Upload Image
                            </label>
                            <input type="radio" class="btn-check" name="idVerificationMethod" id="idMethodManual" value="manual">
                            <label class="btn btn-outline-primary btn-sm" for="idMethodManual">
                                <i class="bi bi-pencil-square"></i> Manual Entry
                            </label>
                        </div>

                        <!-- Image Upload Section -->
                        <div id="idImageSection">
                            <div class="card">
                                <div class="card-body p-2">
                                    <div id="idPreview" class="text-center">
                                        <img id="idImage" src="" alt="ID Preview" class="img-fluid rounded" style="max-height: 200px; display: none;">
                                        <div id="idPlaceholder" class="text-muted">
                                            <i class="bi bi-card-image" style="font-size: 3rem;"></i>
                                            <p class="mt-2 mb-0">ID will be captured from scanner app</p>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted" id="discountTypeLabel"></small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Manual Input Section -->
                        <div id="idManualSection" style="display: none;">
                            <div class="card">
                                <div class="card-body">
                                    <div class="mb-2">
                                        <label class="form-label small" for="discountCustomerName">Customer Name</label>
                                        <input type="text" class="form-control form-control-sm" id="discountCustomerName" placeholder="Enter customer name">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small" for="discountIdNumber">ID Number</label>
                                        <input type="text" class="form-control form-control-sm" id="discountIdNumber" placeholder="Enter ID number">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small" for="discountIdType">ID Type</label>
                                        <input type="text" class="form-control form-control-sm" id="discountIdType" readonly style="background-color: #e9ecef;">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small" for="discountIssuingLgu">Issuing LGU</label>
                                        <input type="text" class="form-control form-control-sm" id="discountIssuingLgu" placeholder="Enter issuing LGU">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- E-Wallet Payment Details -->
                    <div class="mb-3" id="ewalletDetails" style="display: none;">
                        <label class="form-label" for="ewalletProvider">E-Wallet Provider</label>
                        <select class="form-select" id="ewalletProvider">
                            <option value="GCash">GCash</option>
                            <option value="Maya">Maya</option>
                            <option value="PayMaya">PayMaya</option>
                            <option value="Palawan Pay">Palawan Pay</option>
                        </select>
                        <label class="form-label mt-2" for="senderName">Name of the Sender</label>
                        <input type="text" class="form-control" id="senderName" placeholder="Enter Sender Name">
                        <label class="form-label mt-2" for="transactionId">Transaction ID</label>
                        <input type="text" class="form-control" id="transactionId" placeholder="Enter Transaction ID">
                    </div>

                    <!-- Check Payment Details -->
                    <div class="mb-3" id="checkDetails" style="display: none;">
                        <label class="form-label" for="checkNumber">Check Number</label>
                        <input type="text" class="form-control" id="checkNumber" placeholder="Enter Check Number">
                        <label class="form-label mt-2" for="checkBank">Bank</label>
                        <input type="text" class="form-control" id="checkBank" placeholder="Enter Bank Name (e.g., BDO)">
                    </div>

                    <!-- Amount Input -->
                    <div class="mb-3" id="amountTenderedContainer">
                        <label class="form-label" for="amountTendered">Amount Tendered</label>
                            <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" id="amountTendered" placeholder="0.00" step="0.01" min="0">
                            <button class="btn btn-outline-secondary" type="button" id="exactAmountBtn">Exact</button>
                            </div>
                        </div>

                    <!-- Change Display -->
                    <div class="mb-3" id="changeDisplay" style="display: none;">
                        <div class="alert alert-info mb-0">
                            <strong>Change Due: ₱<span id="changeAmount">0.00</span></strong>
                        </div>
                    </div>

                    <!-- Receipt Options -->
                    <div class="mb-3">
                        <div class="form-label">Receipt Options</div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="printReceipt" checked>
                            <label class="form-check-label" for="printReceipt">
                                <i class="bi bi-printer"></i> Print Receipt
                            </label>
                        </div>
                    </div>

                    <!-- Payment Buttons -->
                    <div class="pos-payment-buttons">
                        <button class="btn btn-success btn-lg w-100 mb-2" id="processPayment">
                            <i class="bi bi-cash-stack me-2"></i>Process Payment
                        </button>
                        <button class="btn btn-outline-secondary w-100" id="saveDraft">
                            <i class="bi bi-save me-2"></i>Save as Draft
                        </button>
                    </div>
                </div>
            </div>

            <!-- Analytics Dashboard -->
            <div class="pos-analytics-section">
                <div class="pos-section-header d-flex justify-content-between align-items-center">
                    <h5><i class="bi bi-graph-up me-2"></i>Today's Analytics</h5>
                </div>
                <div class="analytics-content">
                    <div class="row g-2 mb-3">
                        <div class="col-12 col-sm-6">
                            <div class="analytics-card">
                                <div class="analytics-value" id="todaySales">₱0.00</div>
                                <div class="analytics-label">Sales</div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="analytics-card">
                                <div class="analytics-value" id="todayTransactions">0</div>
                                <div class="analytics-label">Transactions</div>
                            </div>
                        </div>
                    </div>
                    <div class="analytics-chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-primary w-100" id="generateDailySalesBtn">
                            <i class="bi bi-file-earmark-text me-2"></i><span class="d-none d-sm-inline">Generate </span>Daily Sales Report
                        </button>
                    </div>
                </div>
        </div>
    </div>
</div>

<!-- Daily Sales Report Modal -->
<div class="modal fade" id="dailySalesReportModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-file-earmark-bar-graph me-2"></i>Daily Sales Report - <span id="reportDate"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Loading State -->
                <div id="reportLoading" class="text-center py-5" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3">Generating report...</p>
                </div>

                <!-- Report Content -->
                <div id="reportContent" style="display: none;">
                    <!-- Summary Cards -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h6 class="card-title"><i class="bi bi-cash-stack me-2"></i>Total Sales</h6>
                                    <h3 class="mb-0" id="reportTotalSales">₱0.00</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h6 class="card-title"><i class="bi bi-receipt me-2"></i>Transactions</h6>
                                    <h3 class="mb-0" id="reportTotalTransactions">0</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h6 class="card-title"><i class="bi bi-box-seam me-2"></i>Items Sold</h6>
                                    <h3 class="mb-0" id="reportTotalItems">0</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-dark">
                                <div class="card-body">
                                    <h6 class="card-title"><i class="bi bi-graph-up me-2"></i>Avg Transaction</h6>
                                    <h3 class="mb-0" id="reportAvgTransaction">₱0.00</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Sold Table -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bi bi-list-check me-2"></i>Items Sold Today</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-hover table-sm">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>#</th>
                                            <th>Item Name</th>
                                            <th class="text-center">Quantity Sold</th>
                                            <th class="text-end">Unit Price</th>
                                            <th class="text-end">Total Amount</th>
                                            <th>Discount</th>
                                            <th>Transaction</th>
                                            <th>Payment Info</th>
                                            <th class="text-center">Void Status</th>
                                            <th class="text-center">Void Transaction</th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsSoldTableBody">
                                        <tr>
                                            <td colspan="10" class="text-center text-muted">No data available</td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="table-light sticky-bottom">
                                        <tr class="fw-bold">
                                            <td colspan="2">TOTAL</td>
                                            <td class="text-center" id="footerTotalQty">0</td>
                                            <td></td>
                                            <td class="text-end" id="footerTotalAmount">₱0.00</td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="printDailyReport">
                    <i class="bi bi-printer me-2"></i>Print Report
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Sales Report Modal -->

    <!-- Free Sample Request Modal -->
    <div class="modal fade" id="freeSampleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-gift me-2"></i>Request Free Sample</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('free-samples.store') }}" id="freeSampleForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="customer_name" class="form-label">Customer Name</label>
                                    <input type="text" class="form-control" id="customer_name" name="customer_name" placeholder="Enter customer name..." required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sampleItemSearch" class="form-label">Search Item</label>
                                    <div class="position-relative">
                                        <input type="text" class="form-control" id="sampleItemSearch" placeholder="Search for item..." autocomplete="off" required>
                                        <input type="hidden" id="item_name" name="item_name" required>
                                        <input type="hidden" id="free_sample_product_id" name="product_id">
                                        <div id="sampleSearchResults" class="dropdown-menu w-100" style="max-height: 300px; overflow-y: auto; display: none; z-index: 1060;"></div>
                                    </div>
                                    <small class="text-muted">Type to search for items</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="quantity" class="form-label">Quantity</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="purpose" class="form-label">Purpose/Reason</label>
                            <textarea class="form-control" id="purpose" name="reason" rows="3" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" form="freeSampleForm">
                        <i class="bi bi-send me-1"></i>Submit Request
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sample Request History Modal -->
    <div class="modal fade" id="sampleHistoryModal" tabindex="-1" aria-labelledby="sampleHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sampleHistoryModalLabel">
                        <i class="bi bi-clock-history me-2"></i>My Sample Request History
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($freeSampleRequests->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Customer Name</th>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Purpose</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($freeSampleRequests as $request)
                                        <tr>
                                            <td class="text-truncate" style="max-width: 160px;" title="{{ $request->customer_name ?? 'N/A' }}">
                                                {{ $request->customer_name ?? 'N/A' }}
                                            </td>
                                            <td class="text-truncate" style="max-width: 160px;" title="{{ $request->item_name ?? 'N/A' }}">
                                                {{ $request->item_name ?? 'N/A' }}
                                            </td>
                                            <td>{{ $request->quantity }}</td>
                                            <td class="text-truncate" style="max-width: 200px;" title="{{ $request->reason ?? 'N/A' }}">
                                                {{ $request->reason ? Str::limit($request->reason, 40) : 'N/A' }}
                                            </td>
                                            <td>
                                                @if($request->status === 'pending')
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                @elseif($request->status === 'approved')
                                                    <span class="badge bg-success">Approved</span>
                                                @else
                                                    <span class="badge bg-danger">Rejected</span>
                                                @endif
                                            </td>
                                            <td>{{ optional($request->created_at)->format('M d, Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-inbox display-4 text-muted"></i>
                            <p class="text-muted mt-2 mb-0">No sample requests found</p>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Discrepancy Request Modal -->
    <!-- Wholesale to Retail Conversion Request Modal -->
    <div class="modal fade" id="conversionRequestModal" tabindex="-1" aria-labelledby="conversionRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="conversionRequestModalLabel">
                        <i class="bi bi-arrow-repeat me-2"></i>Request Wholesale to Retail Conversion
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Retail Product:</strong> <span id="conversionRetailName"></span><br>
                        <strong>Wholesale Product:</strong> <span id="conversionWholesaleName"></span><br>
                        <strong>Available Wholesale Stock:</strong> <span id="conversionWholesaleStock"></span>
                    </div>
                    <div class="mb-3">
                        <label for="conversionQuantity" class="form-label">Quantity to Convert (leave empty to convert all)</label>
                        <input type="number" class="form-control" id="conversionQuantity" min="1" step="1" placeholder="Enter quantity or leave empty for all">
                        <small class="text-muted">Maximum available: <span id="conversionMaxQuantity"></span></small>
                    </div>
                    <div class="mb-3">
                        <label for="conversionNotes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="conversionNotes" rows="3" placeholder="Add any notes about this conversion request..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitConversionRequestBtn">
                        <i class="bi bi-send me-1"></i>Submit Request
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="discrepancyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Report Discrepancy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('discrepancies.store') }}" id="discrepancyForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="discrepancyItemSearch" class="form-label">Search Item</label>
                                    <div class="position-relative">
                                        <input type="text" class="form-control" id="discrepancyItemSearch" placeholder="Search for item or type manually..." autocomplete="off">
                                        <input type="hidden" id="discrepancy_item_name" name="item_name" required>
                                        <input type="hidden" id="discrepancy_product_id" name="product_id">
                                        <input type="hidden" id="discrepancy_item_id" name="item_id">
                                        <input type="hidden" id="discrepancy_inventory_product_id" name="inventory_product_id">
                                        <div id="discrepancySearchResults" class="dropdown-menu w-100" style="max-height: 300px; overflow-y: auto; display: none; z-index: 1060;"></div>
                                    </div>
                                    <small class="text-muted">Type to search for items</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="discrepancy_quantity" class="form-label">Quantity</label>
                                    <input type="number" class="form-control" id="discrepancy_quantity" name="quantity" min="0.01" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="discrepancy_reason" class="form-label">Reason (Optional)</label>
                                    <input type="text" class="form-control" id="discrepancy_reason" name="reason" placeholder="e.g., Damaged, Expired, etc.">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="discrepancy_description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="discrepancy_description" name="description" rows="4" placeholder="Describe the discrepancy (damage, issue, etc.)..." required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" form="discrepancyForm">
                        <i class="bi bi-send me-1"></i>Submit Request
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Discrepancy History Modal -->
    <div class="modal fade" id="discrepancyHistoryModal" tabindex="-1" aria-labelledby="discrepancyHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="discrepancyHistoryModalLabel">
                        <i class="bi bi-clock-history me-2"></i>My Discrepancy Request History
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($discrepancyRequests->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Quantity</th>
                                        <th>Description</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Rejection Notes</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($discrepancyRequests as $request)
                                        <tr>
                                            <td class="text-truncate" style="max-width: 160px;" title="{{ $request->item_name ?? 'N/A' }}">
                                                {{ $request->item_name ?? 'N/A' }}
                                            </td>
                                            <td>
                                                @if($request->quantity == (int) $request->quantity)
                                                    {{ (int) $request->quantity }}
                                                @else
                                                    {{ rtrim(rtrim(number_format($request->quantity, 2, '.', ''), '0'), '.') }}
                                                @endif
                                            </td>
                                            <td class="text-truncate" style="max-width: 180px;" title="{{ $request->description ?? 'N/A' }}">
                                                {{ $request->description ? Str::limit($request->description, 40) : 'N/A' }}
                                            </td>
                                            <td class="text-truncate" style="max-width: 120px;" title="{{ $request->reason ?? 'N/A' }}">
                                                {{ $request->reason ? Str::limit($request->reason, 20) : 'N/A' }}
                                            </td>
                                            <td>
                                                @if($request->status === 'pending')
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                @elseif($request->status === 'approved')
                                                    <span class="badge bg-success">Approved</span>
                                                @else
                                                    <span class="badge bg-danger">Rejected</span>
                                                @endif
                                            </td>
                                            <td class="text-truncate" style="max-width: 160px;" title="{{ $request->rejection_reason ?? 'N/A' }}">
                                                @if($request->status === 'rejected' && $request->rejection_reason)
                                                    <span class="text-danger"><i class="bi bi-info-circle me-1"></i>{{ Str::limit($request->rejection_reason, 30) }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>{{ optional($request->created_at)->format('M d, Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-inbox display-4 text-muted"></i>
                            <p class="text-muted mt-2 mb-0">No discrepancy requests found</p>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

<!-- Shift Management Modal -->
<div class="modal fade" id="shiftModal" tabindex="-1" aria-labelledby="shiftModalTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shiftModalTitle">Start Shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="openingCash">Opening Cash Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">₱</span>
                        <input type="number" class="form-control" id="openingCash" placeholder="0.00" step="0.01" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="cashierNameInput">Cashier Name</label>
                    <input type="text" class="form-control" id="cashierNameInput" value="{{ Auth::user()->name }}" placeholder="Enter cashier name" required>
                    <small class="form-text text-muted">You can edit this name if needed</small>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="shiftDate">Shift Date</label>
                    <input type="date" class="form-control" id="shiftDate" value="{{ now()->format('Y-m-d') }}">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmShiftBtn" onclick="console.log('Button clicked!'); startShift();">Start Shift</button>
            </div>
            </div>
        </div>
    </div>

<!-- End Shift Modal -->
<div class="modal fade" id="endShiftModal" tabindex="-1" aria-hidden="true" aria-labelledby="endShiftModalLabel">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="endShiftModalLabel">End Shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            <div class="modal-body">
                <div class="row">
                        <div class="col-md-6">
                        <h6>Cash Count</h6>
                        <div class="mb-3">
                            <label class="form-label" for="closingCash">Closing Cash Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="closingCash" placeholder="0.00" step="0.01" oninput="updateCashDifference()">
                        </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="expectedCash">Expected Cash</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="text" class="form-control" id="expectedCash" readonly>
                        </div>
                        </div>
                        <div class="alert alert-info" id="cashDifference">
                            <strong>Difference: ₱0.00</strong>
                </div>
            </div>
                    <div class="col-md-6">
                        <h6>Shift Summary</h6>
                        <div class="shift-summary">
                            <div class="summary-item">
                                <span>Total Sales:</span>
                                <strong id="shiftTotalSales">₱0.00</strong>
        </div>
                            <div class="summary-item">
                                <span>Transactions:</span>
                                <strong id="shiftTransactions">0</strong>
                            </div>
                            <div class="summary-item">
                                <span>Payment Methods:</span>
                                <div id="paymentMethodsBreakdown"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmEndShiftBtn">End Shift</button>
            </div>
        </div>
    </div>
</div>

<!-- Shift Report Modal -->
<div class="modal fade" id="shiftReportModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-graph-up me-2"></i>Shift Report</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="shiftReportContent">
                    <!-- Report content will be generated here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printShiftReport()">
                    <i class="bi bi-printer me-1"></i>Print Report
                </button>
                <button type="button" class="btn btn-success" onclick="exportShiftReportCSV()">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Refund Modal -->
<div class="modal fade" id="refundModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Process Refund</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="receiptNumber">Receipt Number</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="receiptNumber" placeholder="Enter receipt number">
                        <button class="btn btn-outline-secondary" id="searchReceiptBtn">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </div>
                <div id="receiptDetails" style="display: none;">
                    <h6>Receipt Details</h6>
                    <div class="table-responsive">
                        <table class="table table-sm" id="refundItemsTable">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <label class="form-label" for="refundReason">Refund Reason</label>
                        <select class="form-select" id="refundReason">
                            <option value="defective">Defective Item</option>
                            <option value="wrong_item">Wrong Item</option>
                            <option value="customer_request">Customer Request</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="processRefundBtn" disabled>Process Refund</button>
            </div>
        </div>
    </div>
</div>

<!-- Void Items Selection Modal -->
<div class="modal fade" id="voidItemsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Items to Void</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Receipt Number: <strong id="voidModalReceiptNumber">-</strong></label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Reason for Void Request (optional):</label>
                    <textarea class="form-control" id="voidReason" rows="3" placeholder="Enter reason for voiding items..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Select items to void:</label>
                    <p class="text-muted small mb-2">Check only the line(s) to void. Unchecked lines stay on the sale.</p>
                    <div id="voidItemsList" class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                        <!-- Items will be populated here -->
                    </div>
                </div>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Total to void:</strong> <span id="voidTotalAmount">₱0.00</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="submitVoidRequestBtn">
                    <i class="bi bi-x-circle me-1"></i>Submit Void Request
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Preview Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Receipt Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="receiptContent" class="receipt-print-wrap mx-auto" style="max-width: 72mm; width: 100%; box-sizing: border-box;">
                    <!-- Receipt content will be generated here -->
                </div>
                <div class="text-center mt-3">
                    <div class="mb-3">
                        <label class="form-label" for="printerType">Printer Type:</label>
                        <select class="form-select form-select-sm d-inline-block" id="printerType" style="width: auto;">
                            <option value="browser">Browser Print (80mm preview)</option>
                            <option value="thermal">USB serial — 80mm ESC/POS (Web Serial)</option>
                            <option value="bluetooth" selected>Bluetooth thermal — RPP02N / 80mm (Web Bluetooth)</option>
                        </select>
                        <small class="d-block text-muted mt-1">
                            <i class="bi bi-info-circle"></i> <strong>Bluetooth:</strong> Pair in Windows, use <strong>Chrome or Edge</strong>. The first print may ask you to pick the printer in the browser; after that, prints go straight to the same device without that prompt. USB cable: use “USB serial” instead.
                        </small>
                    </div>
                    <button class="btn btn-primary" id="printReceiptBtn">
                        <i class="bi bi-printer"></i> Print Receipt
                    </button>
                    <button class="btn btn-info" id="generateQRBtn">
                        <i class="bi bi-qr-code"></i> Generate QR Code
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pos.css') }}">
<style>
/* Sample Request History Modal */
#sampleHistoryModal .table th {
    background-color: #e9ecef;
    border-color: #dee2e6;
}

#sampleHistoryModal .badge {
    font-size: 0.75rem;
}

#sampleHistoryModal .table-responsive {
    font-size: 0.875rem;
}

/* Free Sample Search Dropdown */
#sampleSearchResults {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1060;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    margin-top: 0.25rem;
}

/* Customer Search Dropdown */
#customerInfoSection .card,
#customerInfoSection .card-body {
    overflow: visible;
}

#customerSearchResults {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1080;
    display: none;
    max-height: 280px;
    overflow-y: auto;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    margin-top: 0.25rem;
}

#customerSearchResults.show {
    display: block !important;
}

#customerSearchResults .customer-search-item {
    padding: 0.5rem 1rem;
    border-bottom: 1px solid #f0f0f0;
}

#customerSearchResults .customer-search-item:last-child {
    border-bottom: none;
}

#customerSearchResults .customer-search-item:hover,
#customerSearchResults .customer-search-item.bg-primary {
    background-color: #0d6efd !important;
    color: #fff !important;
}

#sampleSearchResults .sample-search-item {
    padding: 0.5rem 1rem;
    border-bottom: 1px solid #f0f0f0;
}

#sampleSearchResults .sample-search-item:last-child {
    border-bottom: none;
}

#sampleSearchResults .sample-search-item:hover {
    background-color: #f8f9fa;
}

#sampleSearchResults .sample-search-item.bg-primary {
    background-color: #0d6efd !important;
    color: white !important;
}

/* Discrepancy Search Dropdown (inside modal) */
#discrepancyModal .modal-content,
#discrepancyModal .modal-body {
    overflow: visible;
}

#discrepancySearchResults {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 2000;
    display: none;
    max-height: 280px;
    overflow-y: auto;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    margin-top: 0.25rem;
}

#discrepancySearchResults.show {
    display: block !important;
}

#discrepancySearchResults .discrepancy-search-item {
    padding: 0.5rem 1rem;
    border-bottom: 1px solid #f0f0f0;
}

#discrepancySearchResults .discrepancy-search-item:last-child {
    border-bottom: none;
}

#discrepancySearchResults .discrepancy-search-item:hover,
#discrepancySearchResults .discrepancy-search-item.bg-warning {
    background-color: #ffc107 !important;
    color: #212529 !important;
}

/* Product Search Results Dropdown - Expanded */
.pos-search-container {
    position: relative !important;
    z-index: 1;
}

.pos-search-container .input-group {
    position: relative;
    z-index: 1;
}

#searchResults {
    position: fixed !important;
    top: auto !important;
    left: auto !important;
    z-index: 9999 !important;
    background: white !important;
    border: 1px solid #dee2e6 !important;
    border-radius: 0.375rem !important;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    margin-top: 0.25rem !important;
    width: auto !important;
    min-width: 100% !important;
    max-width: 600px !important;
    /* Height will be set dynamically via JavaScript for responsiveness */
    min-height: 300px !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    display: none !important;
    /* Ensure all items are visible and scrollable */
    contain: none !important;
    will-change: scroll-position !important;
    /* Make scrollbar always visible and styled */
    scrollbar-width: thin;
    scrollbar-color: #cbd5e0 #f7fafc;
    /* Add significant padding at bottom so last items are fully visible when scrolled */
    padding-bottom: 100px !important;
    padding-top: 0.5rem !important;
}

/* Webkit scrollbar styling for Chrome/Safari */
#searchResults::-webkit-scrollbar {
    width: 8px;
}

#searchResults::-webkit-scrollbar-track {
    background: #f7fafc;
    border-radius: 4px;
}

#searchResults::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 4px;
}

#searchResults::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}

#searchResults.show {
    display: block !important;
}

/* Add padding to dropdown content area for better scrolling */
#searchResults.dropdown-menu {
    padding-top: 0.5rem !important;
    padding-bottom: 100px !important;
}

/* Ensure last item has enough space below it */
#searchResults .product-search-item:last-child {
    margin-bottom: 60px;
    padding-bottom: 1rem;
}

#searchResults .product-search-item {
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid #f0f0f0;
    min-height: 50px;
    display: flex !important;
    align-items: center;
    visibility: visible !important;
    opacity: 1 !important;
    /* Ensure items are not hidden */
    position: relative !important;
    font-size: 0.875rem;
}

#searchResults .product-search-item .fw-semibold {
    font-size: 0.9rem;
    font-weight: 600;
}

#searchResults .product-search-item small {
    font-size: 0.75rem;
}

#searchResults .product-search-item .badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}

/* Responsive styles for search results dropdown */
@media (max-width: 768px) {
    #searchResults {
        max-width: 100% !important;
        width: 100% !important;
        min-height: 250px !important;
    }
    
    #searchResults .product-search-item {
        padding: 0.4rem 0.5rem;
        min-height: 45px;
        font-size: 0.8rem;
    }
    
    #searchResults .product-search-item .fw-semibold {
        font-size: 0.85rem;
    }
    
    #searchResults .product-search-item small {
        font-size: 0.7rem;
    }
}

@media (min-width: 769px) and (max-width: 1024px) {
    #searchResults {
        max-width: 500px !important;
        min-height: 350px !important;
    }
}

@media (min-width: 1025px) {
    #searchResults {
        max-width: 600px !important;
    }
}

#searchResults .product-search-item:last-child {
    border-bottom: none;
}

#searchResults .product-search-item:hover {
    background-color: #f8f9fa;
}

#searchResults .product-search-item.bg-primary {
    background-color: #0d6efd !important;
    color: white !important;
}

/* Daily Sales Report Modal Styles */
#dailySalesReportModal .sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
    background-color: #f8f9fa !important;
}

#dailySalesReportModal .sticky-bottom {
    position: sticky;
    bottom: 0;
    z-index: 10;
    background-color: #f8f9fa !important;
    box-shadow: 0 -2px 4px rgba(0,0,0,0.1);
}

#dailySalesReportModal .card {
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
}

#dailySalesReportModal .table-responsive {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}

#dailySalesReportModal .modal-xl {
    max-width: 1200px;
}

/* Summary cards responsive */
@media (max-width: 768px) {
    #dailySalesReportModal .col-md-3 {
        margin-bottom: 10px;
    }
}
</style>
@endpush

@push('scripts')
<!-- Chart.js -->
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
    (function() {
        if (typeof Chart !== 'undefined') return;
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
        document.head.appendChild(s);
    })();
</script>
<!-- Chart.js Fallback -->
<script>
    setTimeout(function() {
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js failed to load, trying fallback...');
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js';
            script.onload = function() {
                console.log('Chart.js loaded from fallback CDN');
            };
            document.head.appendChild(script);
        }
    }, 2000);
</script>
<script src="{{ asset('js/qrcode.min.js') }}"></script>
<script>
// Global helpers for escaping
const escapeTemplate = (str) => {
    if (str == null) return '';
    return String(str)
        .replace(/\\/g, '\\\\')
        .replace(/`/g, '\\`')
        .replace(/\${/g, '\\${');
};

const escapeHtml = (str) => {
    if (str == null) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
};

function escapeReceiptPre(str) {
    if (str == null) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

// Define showStartShiftModalWithRetry globally BEFORE DOMContentLoaded
window.showStartShiftModalWithRetry = function(retryCount = 0) {
    const maxRetries = 10;
    
    console.log('showStartShiftModalWithRetry called, attempt:', retryCount + 1);
    const modalElement = document.getElementById('shiftModal');
    
    if (!modalElement) {
        console.error('Modal element not found!');
        if (retryCount < maxRetries) {
            setTimeout(() => window.showStartShiftModalWithRetry(retryCount + 1), 200);
        }
        return;
    }
    
    // Check if Bootstrap is available
    if (typeof bootstrap === 'undefined') {
        console.log('Bootstrap not ready yet, retrying...');
        if (retryCount < maxRetries) {
            setTimeout(() => window.showStartShiftModalWithRetry(retryCount + 1), 200);
        } else {
            console.error('Bootstrap failed to load after max retries!');
            // Fallback: show modal using direct DOM manipulation
            // Don't manually set aria-hidden - let Bootstrap handle it
            modalElement.style.display = 'block';
            modalElement.classList.add('show');
            modalElement.removeAttribute('aria-hidden');
            modalElement.setAttribute('aria-modal', 'true');
            document.body.classList.add('modal-open');
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'shiftModalBackdrop';
            document.body.appendChild(backdrop);
        }
        return;
    }
    
    // Reset form
    const openingCashInput = document.getElementById('openingCash');
    const cashierNameInput = document.getElementById('cashierNameInput');
    const shiftDateInput = document.getElementById('shiftDate');
    
    if (openingCashInput) {
        openingCashInput.value = '';
    }
    if (cashierNameInput) {
        cashierNameInput.value = '{{ Auth::user()->name ?? "" }}';
    }
    if (shiftDateInput) {
        // Update date to current date
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        shiftDateInput.value = `${year}-${month}-${day}`;
    }
    
    try {
        // Clean up any existing backdrops before showing modal
        const existingBackdrops = document.querySelectorAll('.modal-backdrop');
        existingBackdrops.forEach(backdrop => {
            backdrop.remove();
            console.log('✅ Removed existing backdrop before showing start shift modal');
        });
        
        // Clean up body classes and styles
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        
        // Set up proper focus management for accessibility (only once)
        if (!modalElement.hasAttribute('data-focus-managed')) {
            modalElement.setAttribute('data-focus-managed', 'true');
            
            modalElement.addEventListener('show.bs.modal', function() {
                // Remove aria-hidden when showing - Bootstrap will handle it
                modalElement.removeAttribute('aria-hidden');
                modalElement.setAttribute('aria-modal', 'true');
            });

            modalElement.addEventListener('shown.bs.modal', function() {
                // Focus the first input after modal is fully shown
                const firstInput = modalElement.querySelector('#openingCash');
                if (firstInput) {
                    setTimeout(() => firstInput.focus(), 100);
                }
            });

            modalElement.addEventListener('hide.bs.modal', function(e) {
                // Remove focus from any element inside modal before hiding
                // This prevents the accessibility warning about aria-hidden on focused elements
                const activeElement = document.activeElement;
                if (modalElement.contains(activeElement) && activeElement !== document.body && activeElement !== document.documentElement) {
                    // Blur the active element to remove focus
                    if (typeof activeElement.blur === 'function') {
                        activeElement.blur();
                    }
                    // Move focus to a safe element outside the modal
                    // Use requestAnimationFrame to ensure blur happens before aria-hidden is set
                    requestAnimationFrame(() => {
                        const safeElement = document.querySelector('body') || document.documentElement;
                        if (safeElement && typeof safeElement.focus === 'function') {
                            safeElement.focus();
                        }
                    });
                }
            });

            modalElement.addEventListener('hidden.bs.modal', function() {
                // Bootstrap handles aria-hidden automatically, but ensure it's set after modal is fully hidden
                if (!modalElement.classList.contains('show')) {
                    modalElement.setAttribute('aria-hidden', 'true');
                    modalElement.removeAttribute('aria-modal');
                }
            });
        }
        
        // Get existing modal instance or create new one
        let modal = bootstrap.Modal.getInstance(modalElement);
        if (!modal) {
            modal = new bootstrap.Modal(modalElement, {
                backdrop: 'static',
                keyboard: false
            });
        }
        
        // Show the modal using Bootstrap
        modal.show();
        
        // Silent fallback check - only force display if modal doesn't show after Bootstrap animation
        // This handles edge cases where Bootstrap might not initialize properly
        let fallbackTimeout = setTimeout(() => {
            if (!modalElement.classList.contains('show') && !document.querySelector('.modal-backdrop')) {
                // Only force if modal truly isn't showing and no backdrop exists
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                backdrop.id = 'shiftModalBackdrop';
                document.body.appendChild(backdrop);
                
                modalElement.style.display = 'block';
                modalElement.classList.add('show');
                // Don't manually set aria-hidden - Bootstrap handles it
                modalElement.removeAttribute('aria-hidden');
                modalElement.setAttribute('aria-modal', 'true');
                document.body.classList.add('modal-open');
                document.body.style.overflow = 'hidden';
            }
        }, 300);
        
        // Clear fallback if modal shows successfully via Bootstrap
        const clearFallback = () => {
            if (fallbackTimeout) {
                clearTimeout(fallbackTimeout);
                fallbackTimeout = null;
            }
            modalElement.removeEventListener('shown.bs.modal', clearFallback);
        };
        
        modalElement.addEventListener('shown.bs.modal', clearFallback, { once: true });
    } catch (error) {
        console.error('Error showing modal:', error);
        if (retryCount < maxRetries) {
            setTimeout(() => window.showStartShiftModalWithRetry(retryCount + 1), 200);
        } else {
            // Last resort: direct DOM manipulation
            modalElement.style.display = 'block';
            modalElement.classList.add('show');
            // Don't manually set aria-hidden - Bootstrap handles it
            modalElement.removeAttribute('aria-hidden');
            modalElement.setAttribute('aria-modal', 'true');
            document.body.classList.add('modal-open');
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'shiftModalBackdrop';
            document.body.appendChild(backdrop);
        }
    }
};

/** Parse a transaction total with safe fallback to summed line totals. */
window.getBaseTransactionTotalForShift = function(transaction) {
    if (!transaction) return 0;

    if (transaction.total !== undefined || transaction.grand_total !== undefined || transaction.amount !== undefined) {
        return parseFloat(transaction.total || transaction.grand_total || transaction.amount || 0);
    }

    if (!transaction.items || !Array.isArray(transaction.items)) return 0;

    return transaction.items.reduce((sum, item) => {
        const quantity = parseFloat(item.quantity || 0);
        const price = parseFloat(item.price || 0);
        return sum + (quantity * price);
    }, 0);
};

/** Sum approved voided line amounts (qty * price) for a transaction. */
window.getVoidedLineAmountForShift = function(transaction, voidedItemsMap) {
    if (!transaction || !transaction.id || !transaction.items || !Array.isArray(transaction.items)) return 0;

    const voidedItems = voidedItemsMap[transaction.id] || [];
    const voidedItemIndices = Array.isArray(voidedItems) ? voidedItems.map(idx => parseInt(idx)) : [];
    if (voidedItemIndices.length === 0) return 0;

    return transaction.items.reduce((sum, item, itemIndex) => {
        if (!voidedItemIndices.includes(itemIndex)) return sum;
        const quantity = parseFloat(item.quantity || 0);
        const price = parseFloat(item.price || 0);
        return sum + (quantity * price);
    }, 0);
};

/** True when a shift record is an actual completed sale with sold qty — not a refund, empty cart, or leftover row. */
window.isCountableShiftTransaction = function(transaction, voidStatusMap, voidedItemsMap) {
    if (!transaction || transaction.type === 'refund') return false;

    const items = Array.isArray(transaction.items) ? transaction.items : [];
    const voidStatus = transaction.id ? (voidStatusMap || {})[transaction.id] : null;
    const voidedItems = transaction.id ? ((voidedItemsMap || {})[transaction.id] || []) : [];
    const voidedItemIndices = Array.isArray(voidedItems) ? voidedItems.map(idx => parseInt(idx, 10)) : [];

    const soldItems = items.filter((item, itemIndex) => {
        const qty = parseFloat(item.quantity || 0);
        if (qty <= 0) return false;
        if (voidStatus === 'approved' && voidedItemIndices.includes(itemIndex)) return false;
        return true;
    });

    if (soldItems.length === 0) return false;

    return window.getEffectiveTransactionTotalForShift(transaction, voidStatusMap || {}, voidedItemsMap || {}) > 0;
};

/** Effective amount used for sales analytics. For approved voids, count only non-voided lines. */
window.getEffectiveTransactionTotalForShift = function(transaction, voidStatusMap, voidedItemsMap) {
    const baseTotal = window.getBaseTransactionTotalForShift(transaction);
    if (!transaction || !transaction.id) return baseTotal;

    const voidStatus = voidStatusMap[transaction.id];
    if (voidStatus !== 'approved') return baseTotal;

    const voidedItems = voidedItemsMap[transaction.id] || [];
    const hasExplicitVoidedItems = Array.isArray(voidedItems) && voidedItems.length > 0;
    if (!hasExplicitVoidedItems) return 0;

    if (transaction.items && Array.isArray(transaction.items)) {
        const voidedItemIndices = voidedItems.map(idx => parseInt(idx));
        const nonVoidedLinesTotal = transaction.items.reduce((sum, item, itemIndex) => {
            if (voidedItemIndices.includes(itemIndex)) return sum;
            const quantity = parseFloat(item.quantity || 0);
            const price = parseFloat(item.price || 0);
            return sum + (quantity * price);
        }, 0);
        return Math.max(0, nonVoidedLinesTotal);
    }

    const voidedLineAmount = window.getVoidedLineAmountForShift(transaction, voidedItemsMap);
    return Math.max(0, baseTotal - voidedLineAmount);
};

// Define showEndShiftModal globally BEFORE DOMContentLoaded (early definition for button onclick)
// This is a lightweight version that will be overridden by the full version later
window.showEndShiftModal = function() {
    console.log('showEndShiftModal called (early definition - will be overridden)');
    
    // Check if Bootstrap is available
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap is not loaded yet!');
        setTimeout(() => {
            if (typeof bootstrap !== 'undefined') {
                window.showEndShiftModal();
            } else {
                if (typeof showNotification === 'function') {
                    showNotification('Bootstrap failed to load. Please refresh the page.', 'error');
                } else {
                    alert('Bootstrap failed to load. Please refresh the page.');
                }
            }
        }, 1000);
        return;
    }
    
    // Check if shiftData is available (might not be initialized yet)
    if (typeof shiftData === 'undefined' || !shiftData || !shiftData.isActive) {
        if (typeof showNotification === 'function') {
            showNotification('No active shift to end', 'error');
        } else {
            alert('No active shift to end');
        }
        return;
    }

    const modalElement = document.getElementById('endShiftModal');
    if (!modalElement) {
        console.error('End shift modal not found!');
        if (typeof showNotification === 'function') {
            showNotification('End shift modal not found. Please refresh the page.', 'error');
        } else {
            alert('End shift modal not found. Please refresh the page.');
        }
        return;
    }

    // Check if modal instance already exists
    let modal = bootstrap.Modal.getInstance(modalElement);
    if (!modal) {
        modal = new bootstrap.Modal(modalElement, {
            backdrop: 'static',
            keyboard: false
        });
    }
    
    // Update shift summary — sales = transaction total minus approved voided line amounts
    const shiftTransactions = shiftData.transactions || [];
    
    // Use global void status maps if available
    let voidStatusMap = { ...globalVoidStatusMap };
    let voidedItemsMap = { ...globalVoidedItemsMap };
    
    // Calculate total sales using effective totals after approved void deductions
    // Only count cash sales - exclude E-Wallet (e_wallet, gcash, maya) and card
    let totalSales = 0;
    const eWalletMethods = ['e_wallet', 'gcash', 'maya'];
    
    const nonVoidedTransactions = shiftTransactions.filter(transaction =>
        window.getEffectiveTransactionTotalForShift(transaction, voidStatusMap, voidedItemsMap) > 0
    );
    
    // Filter to only cash transactions for end shift
    const cashTransactions = nonVoidedTransactions.filter(transaction => {
        const paymentMethod = transaction.paymentMethod || 'cash';
        // Only include cash transactions - exclude E-Wallet and other payment methods
        return paymentMethod === 'cash' && !eWalletMethods.includes(paymentMethod);
    });
    
    cashTransactions.forEach(transaction => {
        totalSales += window.getEffectiveTransactionTotalForShift(transaction, voidStatusMap, voidedItemsMap);
    });
    
    // Transaction count: Include all payment types (cash, e-wallet, check, card)
    const transactionCount = nonVoidedTransactions.length;
    
    // Calculate total discounts - only from cash transactions
    let totalDiscounts = 0;
    
    // Calculate payment methods excluding voided transactions
    // Display all payment methods (Cash, E-Wallet, Check) but only Cash counts toward Total Sales
    const paymentMethodsAfterDiscount = { cash: 0, card: 0, gcash: 0, maya: 0, e_wallet: 0, check: 0 };
    
    // Process all transactions for payment methods breakdown (display purposes)
    nonVoidedTransactions.forEach(transaction => {
        const paymentMethod = transaction.paymentMethod || 'cash';
        const effectiveTotal = window.getEffectiveTransactionTotalForShift(transaction, voidStatusMap, voidedItemsMap);
        paymentMethodsAfterDiscount[paymentMethod] = (paymentMethodsAfterDiscount[paymentMethod] || 0) + effectiveTotal;
    });
    
    // Calculate total discounts - only from cash transactions (for Total Sales)
    cashTransactions.forEach(transaction => {
        const baseTotal = window.getBaseTransactionTotalForShift(transaction);
        const effectiveTotal = window.getEffectiveTransactionTotalForShift(transaction, voidStatusMap, voidedItemsMap);
        if (effectiveTotal <= 0 || baseTotal <= 0) return;
        
        // Calculate discount for total discounts display (only from cash)
        let transactionDiscount = 0;
        if (transaction.discountAmount !== undefined) {
            transactionDiscount = parseFloat(transaction.discountAmount) || 0;
        } else if (transaction.originalSubtotal !== undefined && transaction.subtotal !== undefined) {
            transactionDiscount = parseFloat(transaction.originalSubtotal) - parseFloat(transaction.subtotal) || 0;
        } else if (transaction.discount !== undefined) {
            transactionDiscount = parseFloat(transaction.discount) || 0;
        }
        
        const effectiveShare = Math.min(1, effectiveTotal / baseTotal);
        totalDiscounts += transactionDiscount * effectiveShare;
    });
    
    const shiftTotalSalesEl = document.getElementById('shiftTotalSales');
    const shiftTransactionsEl = document.getElementById('shiftTransactions');
    if (shiftTotalSalesEl) {
        if (totalDiscounts > 0) {
            shiftTotalSalesEl.innerHTML = `₱${formatCurrency(totalSales)}<br><small class="text-success">Discount: -₱${formatCurrency(totalDiscounts)}</small>`;
        } else {
            shiftTotalSalesEl.textContent = `₱${formatCurrency(totalSales)}`;
        }
    }
    if (shiftTransactionsEl) shiftTransactionsEl.textContent = transactionCount;
    
    // Update payment methods breakdown
    const breakdown = document.getElementById('paymentMethodsBreakdown');
    if (breakdown) {
        let breakdownHTML = '';
        Object.keys(paymentMethodsAfterDiscount).forEach(method => {
            const amount = paymentMethodsAfterDiscount[method] || 0;
            if (amount > 0) {
                // Format payment method names for better display
                let methodName = method.charAt(0).toUpperCase() + method.slice(1).replace('_', ' ');
                // Special formatting for common payment methods
                if (method === 'e_wallet') {
                    methodName = 'E-Wallet';
                } else if (method === 'gcash') {
                    methodName = 'GCash';
                } else if (method === 'maya') {
                    methodName = 'Maya';
                } else if (method === 'check') {
                    methodName = 'Check';
                }
                breakdownHTML += `<div><strong>${methodName}:</strong> ₱${formatCurrency(amount)}</div>`;
            }
        });
        breakdown.innerHTML = breakdownHTML || '<div><strong>Cash:</strong> ₱0.00</div>';
    }
    
    // Calculate expected cash (use cash amount after discount)
    const openingCash = shiftData.openingCash || 0;
    const cashSales = paymentMethodsAfterDiscount.cash || 0;
    const expectedCash = openingCash + cashSales;
    
    const expectedCashEl = document.getElementById('expectedCash');
    if (expectedCashEl) expectedCashEl.value = formatCurrency(expectedCash);
    
    // Clear closing cash input and set up real-time difference calculation
    const closingCashEl = document.getElementById('closingCash');
    if (closingCashEl) {
        closingCashEl.value = '';
        closingCashEl.setAttribute('data-expected-cash', expectedCash);
        // Set up real-time difference calculation
        if (typeof window.updateCashDifference === 'function') {
            closingCashEl.removeEventListener('input', window.updateCashDifference);
            closingCashEl.removeEventListener('change', window.updateCashDifference);
            closingCashEl.addEventListener('input', window.updateCashDifference);
            closingCashEl.addEventListener('change', window.updateCashDifference);
        }
    }
    
    // Reset difference display
    const cashDifferenceEl = document.getElementById('cashDifference');
    if (cashDifferenceEl) {
        cashDifferenceEl.className = 'alert alert-info';
        cashDifferenceEl.innerHTML = '<strong>Enter closing cash to see difference</strong>';
    }
    
    try {
        modal.show();
        console.log('✅ End shift modal shown (early definition)');
    } catch (error) {
        console.error('Error showing end shift modal:', error);
        if (typeof showNotification === 'function') {
            showNotification('Error opening end shift modal. Please try again.', 'error');
        } else {
            alert('Error opening end shift modal. Please try again.');
        }
    }
};

// Helper function to format numbers with commas for thousands
function formatCurrency(amount) {
    return parseFloat(amount || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
// Make it globally available
window.formatCurrency = formatCurrency;

// Define updateCashDifference function early (before DOMContentLoaded)
window.updateCashDifference = function() {
    const closingCashInput = document.getElementById('closingCash');
    const expectedCashInput = document.getElementById('expectedCash');
    const differenceElement = document.getElementById('cashDifference');
    
    if (!closingCashInput || !expectedCashInput || !differenceElement) {
        return;
    }
    
        const closingCash = parseFloat(closingCashInput.value) || 0;
        // Use the data attribute which contains the raw numeric value (more reliable than parsing formatted string)
        const expectedCashRaw = closingCashInput.getAttribute('data-expected-cash');
        let expectedCash = 0;
        if (expectedCashRaw) {
            expectedCash = parseFloat(expectedCashRaw) || 0;
        } else {
            // Fallback: Remove commas from expectedCash input value before parsing
            const expectedCashValue = expectedCashInput.value.replace(/,/g, '');
            expectedCash = parseFloat(expectedCashValue) || 0;
        }
        const difference = closingCash - expectedCash;
        
        if (closingCash === 0 || isNaN(closingCash)) {
            differenceElement.className = 'alert alert-info';
            differenceElement.innerHTML = '<strong>Enter closing cash to see difference</strong>';
        } else if (difference === 0) {
            differenceElement.className = 'alert alert-success';
            differenceElement.innerHTML = '<strong>Perfect! No difference</strong>';
        } else if (difference > 0) {
            differenceElement.className = 'alert alert-info';
            differenceElement.innerHTML = `<strong>Over by: ₱${formatCurrency(difference)}</strong>`;
        } else {
            differenceElement.className = 'alert alert-warning';
            differenceElement.innerHTML = `<strong>Short by: ₱${formatCurrency(Math.abs(difference))}</strong>`;
        }
    };

// Define showStartShiftModal globally BEFORE DOMContentLoaded
// This ensures it's available when the HTML button's onclick handler runs
window.showStartShiftModal = function() {
    console.log('showStartShiftModal called from global scope');
    // Use setTimeout to ensure DOM is ready
    setTimeout(function() {
        if (window.showStartShiftModalWithRetry) {
            window.showStartShiftModalWithRetry(0);
        } else {
            // Fallback: try direct approach
            const modalElement = document.getElementById('shiftModal');
            if (modalElement && typeof bootstrap !== 'undefined') {
                try {
                    const modal = new bootstrap.Modal(modalElement, {
                        backdrop: 'static',
                        keyboard: false
                    });
                    modal.show();
                } catch(e) {
                    console.error('Error showing modal:', e);
                }
            } else {
                console.warn('Modal element or Bootstrap not available yet');
            }
        }
    }, 100);
};

document.addEventListener('DOMContentLoaded', async function() {
    // Ensure formatCurrency is available in this scope
    if (typeof window.formatCurrency === 'undefined') {
        window.formatCurrency = function(amount) {
            return parseFloat(amount || 0).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        };
    }
    console.log('DOM Content Loaded - Initializing POS...');
    const cartTableBody = document.getElementById('cartTableBody');
    const quickAccess = document.getElementById('quickAccess');
    
    let cart = [];
    let recentProducts = [];
    let shiftData = {
        isActive: false,
        startTime: null,
        openingCash: 0,
        totalSales: 0,
        transactions: [],
        paymentMethods: { cash: 0 }
    };
    
    let selectedPaymentMethod = 'cash';
    let selectedDiscountType = null; // 'senior' or 'pwd'
    let discountIdImage = null; // Base64 or URL of captured ID
    let capturedSeniorPwdDiscountId = null; // senior_pwd_discounts.id from scanner capture
    let idVerificationMethod = 'image'; // 'image' or 'manual'
    let salesChart = null;
    
    // Helper function to safely check if chart is valid
    function isChartValid() {
        try {
            return salesChart !== null && 
                   salesChart.canvas !== null && 
                   salesChart.canvas !== undefined &&
                   salesChart.canvas.ownerDocument !== null &&
                   salesChart.canvas.ownerDocument !== undefined &&
                   document.contains(salesChart.canvas);
        } catch (e) {
            return false;
        }
    }
    let isInitializingChart = false; // Prevent concurrent initialization
    let idManuallyRemoved = false; // Flag to track if user manually removed ID
    let cachedStartTimeDisplay = null; // Cache the formatted start time to prevent changes
    
    // Lock mechanism: Once set correctly, never change it until shift ends
    // Store in localStorage for persistence across page refreshes
    let lockedStartTime = null;
    
    // Helper functions for locked start time persistence
    function loadLockedStartTime() {
        try {
            const stored = localStorage.getItem('lockedStartTime');
            if (stored) {
                const parsed = JSON.parse(stored);
                // Only restore if it's in correct format
                if (parsed && typeof parsed === 'string' && 
                    !parsed.includes('T') && !parsed.includes('Z') && 
                    /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(parsed)) {
                    lockedStartTime = parsed;
                    console.log('🔒 Restored locked startTime from localStorage:', lockedStartTime);
                    return true;
                }
            }
        } catch (e) {
            console.error('Error loading locked startTime:', e);
        }
        return false;
    }
    
    function saveLockedStartTime(time) {
        if (time && typeof time === 'string' && 
            !time.includes('T') && !time.includes('Z') && 
            /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(time)) {
            lockedStartTime = time;
            localStorage.setItem('lockedStartTime', JSON.stringify(time));
            console.log('🔒 Saved locked startTime to localStorage:', lockedStartTime);
            return true;
        }
        return false;
    }
    
    function clearLockedStartTime() {
        lockedStartTime = null;
        localStorage.removeItem('lockedStartTime');
        console.log('🔓 Cleared locked startTime');
    }
    
    // Load locked time on page load
    loadLockedStartTime();
    
    // Product data — image URLs use /public/images/... (Hostinger/Laragon project web root)
    const productsArray = @json($products);

    function cashierItemImageUrl(url) {
        if (url === null || url === undefined || url === '') return url;
        if (typeof url !== 'string') return url;
        const u = url.trim();
        if (u.startsWith('data:') || u.startsWith('blob:')) return u;
        return u.replace(/\/public\/public\/(images|storage)\//g, '/public/$1/');
    }

    function cashierAlternateImageUrl(url) {
        if (!url || typeof url !== 'string') return '';
        if (url.includes('/public/images/')) {
            return url.replace('/public/images/', '/images/');
        }
        if (url.includes('/images/')) {
            return url.replace('/images/', '/public/images/');
        }
        return '';
    }

    function cashierImagePlaceholder(size) {
        const s = size || 40;
        return `<div class="product-image-fallback" style="width:${s}px;height:${s}px;"><i class="bi bi-image"></i></div>`;
    }

    function cashierProductImageHtml(url, name, size) {
        const src = cashierItemImageUrl(url);
        const s = size || 48;
        if (!src) {
            return cashierImagePlaceholder(s);
        }
        const safeName = String(name || 'Product').replace(/"/g, '&quot;');
        return `<img src="${src}" alt="${safeName}" class="rounded" width="${s}" height="${s}" style="width:${s}px;height:${s}px;max-width:${s}px;max-height:${s}px;object-fit:cover;background:#f8f9fa;" loading="lazy" onerror="handleCashierProductImageError(this, ${s})">`;
    }

    function handleCashierProductImageError(img, size) {
        if (!img.dataset.altTried) {
            img.dataset.altTried = '1';
            const alt = cashierAlternateImageUrl(img.getAttribute('src') || '');
            if (alt) {
                img.src = alt;
                return;
            }
        }
        img.outerHTML = cashierImagePlaceholder(size || 48);
    }

    window.handleCashierProductImageError = handleCashierProductImageError;

    // Convert array to object with a stable unique key (item_lists and inventory_products can share numeric IDs)
    const products = {};
    const duplicateIds = [];

    function cashierSearchableText(text) {
        return String(text || '')
            .toLowerCase()
            .replace(/[''`´’‘]/g, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

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

    function cashierProductMatchesQuery(product, query) {
        const needle = cashierSearchableText(query);
        if (!needle) return true;
        if (!product || !product.name) return false;

        const haystacks = [
            product.search_text,
            product.name,
            product.sku && product.sku !== 'N/A' ? product.sku : '',
            product.category,
            product.brand,
            product.description,
            product.price_type
        ];

        return haystacks.some((haystack) => cashierSearchableText(haystack).includes(needle));
    }

    function cashierStoreKey(product) {
        if (product.cashier_key) return String(product.cashier_key);
        if (product.item_id) return 'il-' + product.item_id;
        if (product.inventory_product_id) return 'ip-' + product.inventory_product_id;
        if (product.barcode_id) return 'bc-' + product.barcode_id;
        if (product.uniqueKey) return String(product.uniqueKey);
        if (product.id !== undefined && product.id !== null && product.id !== '') return String(product.id);
        return '';
    }

    function findStoredCashierProduct(product) {
        if (!product) return null;
        const key = cashierStoreKey(product);
        if (key && products[key]) return products[key];
        if (product.id !== undefined && product.id !== null && products[product.id]) return products[product.id];
        if (product.item_id && products['il-' + product.item_id]) return products['il-' + product.item_id];
        if (product.inventory_product_id && products['ip-' + product.inventory_product_id]) return products['ip-' + product.inventory_product_id];
        if (product.barcode_id && products['bc-' + product.barcode_id]) return products['bc-' + product.barcode_id];
        if (product.name) {
            const priceType = (product.price_type || product.category || '').toLowerCase();
            return Object.values(products).find(p => {
                if (!p || p.name !== product.name) return false;
                if (!priceType || priceType === 'barcode item') return true;
                return String(p.price_type || p.category || '').toLowerCase() === priceType;
            }) || null;
        }
        return null;
    }

    function indexCashierProduct(product) {
        if (!product) return;
        const productName = (product.name || product.item || '').trim();
        if (!productName) {
            console.warn('Skipping product with empty name:', product);
            return;
        }

        const storeKey = cashierStoreKey(product);
        if (!storeKey) {
            console.warn('Skipping product without ID:', product);
            return;
        }

        products[storeKey] = {
            id: product.id,
            cashier_key: storeKey,
            uniqueKey: storeKey,
            name: productName,
            price: parseMoney(product.price || 0),
            stock_quantity: parseInt(product.stock_quantity || product.quantity_on_hand || 0),
            quantity_on_hand: parseInt(product.stock_quantity || product.quantity_on_hand || 0),
            category: product.category || product.type || '',
            price_type: product.price_type || product.category || product.type || '',
            item_id: product.item_id || null,
            inventory_product_id: product.inventory_product_id || null,
            barcode_id: product.barcode_id || null,
            source: product.source || null,
            sku: product.sku || product.mpn || 'N/A',
            expiry_date: product.expiry_date || '',
            min_stock_level: parseInt(product.min_stock_level || product.reorder_pt_min || 10),
            unit: product.unit || product.unit_of_measure || 'pcs',
            image: product.image || null,
            brand: product.brand || null,
            description: product.description || null,
            search_text: product.search_text || cashierSearchableText([
                productName,
                product.sku || product.mpn || '',
                product.brand || '',
                product.description || '',
                product.price_type || product.category || ''
            ].join(' '))
        };
    }

    productsArray.forEach((product) => {
        indexCashierProduct(product);
    });
    
    // Log summary of duplicates (only once, less verbose)
    if (duplicateIds.length > 0) {
        const uniqueDuplicateIds = [...new Set(duplicateIds)];
        console.warn(`⚠️ Found ${uniqueDuplicateIds.length} duplicate product ID(s). Products have been assigned unique keys to prevent data loss.`);
        // Only show detailed list in development
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            console.warn('Duplicate IDs:', uniqueDuplicateIds);
        }
    }
    
    // Log adhesive products for debugging
    const adhesiveProducts = Object.values(products).filter(p => 
        p.name && p.name.toLowerCase().includes('adhesive')
    );
    console.log('Adhesive products found:', adhesiveProducts.length, adhesiveProducts.map(p => ({ id: p.id, name: p.name })));
    
    // Log face mask products for debugging
    const faceMaskProducts = Object.values(products).filter(p => 
        p && p.name && p.name.toLowerCase().includes('face mask')
    );
    console.log('Face mask products found in products object:', faceMaskProducts.length);
    console.log('Face mask products details:', faceMaskProducts.map(p => ({ 
        id: p.id, 
        name: p.name,
        key: Object.keys(products).find(k => products[k] === p),
        hasUniqueKey: !!p.uniqueKey
    })));
    
    // Check if all face mask items from server are in the products object
    const faceMaskFromServer = productsArray.filter(p => 
        p && (p.name || p.item) && (p.name || p.item).toLowerCase().includes('face mask')
    );
    console.log('Face mask items from server (productsArray):', faceMaskFromServer.length);
    if (faceMaskFromServer.length !== faceMaskProducts.length) {
        console.warn('⚠️ MISMATCH: Server sent', faceMaskFromServer.length, 'face mask items but only', faceMaskProducts.length, 'are in products object');
        const serverNames = faceMaskFromServer.map(p => (p.name || p.item).toLowerCase()).sort();
        const objectNames = faceMaskProducts.map(p => p.name.toLowerCase()).sort();
        const missing = serverNames.filter(name => !objectNames.includes(name));
        if (missing.length > 0) {
            console.warn('Missing items:', missing);
        }
    }

    // Debug: Log products to console
    console.log('Total products loaded:', Object.keys(products).length);
    console.log('First 3 products:', Object.values(products).slice(0, 3));
    console.log('Raw products array:', productsArray.slice(0, 2));
    
    // Log test1 product specifically for debugging
    const test1Product = Object.values(products).find(p => p.name && p.name.toLowerCase() === 'test1');
    if (test1Product) {
        console.log('test1 product found:', {
            id: test1Product.id,
            name: test1Product.name,
            stock_quantity: test1Product.stock_quantity,
            quantity_on_hand: test1Product.quantity_on_hand
        });
    } else {
        console.log('test1 product NOT found in initial load');
    }
    
    if (Object.keys(products).length === 0) {
        console.error('WARNING: No products loaded! Check database connection and ItemList table.');
    }
    
    // Note: reloadProducts() will be called after it's defined (see line ~4181)

    // Initialize POS System
    async function initializePOS() {
        // FIRST: Check localStorage for active shift (faster, works offline)
        const storedShift = localStorage.getItem('activeShift');
        if (storedShift) {
            try {
                const parsedShift = JSON.parse(storedShift);
                if (parsedShift.isActive && parsedShift.startTime) {
                    console.log('Found active shift in localStorage, using it temporarily');
                    console.log('LocalStorage startTime format:', parsedShift.startTime);
                    // Only clear cache if startTime format is invalid or if we don't have a cached display
                    // Don't clear if we already have a valid cached time for this startTime
                    if (!cachedStartTimeDisplay || cachedStartTimeDisplay.originalTime !== parsedShift.startTime) {
                        cachedStartTimeDisplay = null;
                    }
                    
                    // Use locked time if available, otherwise validate and lock localStorage time
                    if (lockedStartTime) {
                        // Use existing locked time - don't change it
                        console.log('🔒 Using existing locked startTime:', lockedStartTime);
                        parsedShift.startTime = lockedStartTime;
                    } else if (parsedShift.startTime && !parsedShift.startTime.includes('T') && !parsedShift.startTime.includes('Z') && /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(parsedShift.startTime)) {
                        // Lock and save the time from localStorage
                        saveLockedStartTime(parsedShift.startTime);
                    }
                    
                    // Ensure we're using shift-specific data structure
                    shiftData = {
                        id: parsedShift.id,
                        isActive: parsedShift.isActive,
                        startTime: parsedShift.startTime, // May be old format, will be updated by server
                        openingCash: parseFloat(parsedShift.openingCash || 0),
                        totalSales: parseFloat(parsedShift.totalSales || 0), // Shift-specific total only
                        transactionCount: parsedShift.transactionCount || (parsedShift.transactions ? parsedShift.transactions.length : 0),
                        transactions: parsedShift.transactions || [], // Shift-specific transactions only
                        paymentMethods: parsedShift.paymentMethods || { cash: 0 },
                        cashierName: parsedShift.cashierName
                    };
                    updateShiftStatus();
                    updateAnalytics(); // Update analytics with shift-specific data
                    // Continue to verify with server, but don't overwrite if server fails
                }
            } catch (e) {
                console.error('Error parsing stored shift:', e);
            }
        }
        
        // THEN: Verify with server (but don't overwrite if server check fails)
        try {
            const response = await fetch('/shifts/active');
            const data = await response.json();
            
            console.log('Server shift check response:', data);
            
            if (data.success && data.shift) {
                console.log('Server confirms active shift exists:', data.shift.id);
                
                // Prefer server-provided transactions so Today's Analytics & Daily Sales Report stay in sync.
                // An empty array from the server means this shift has no sales — do not restore stale localStorage items.
                let transactionsToUse = [];
                let totalSalesToUse = 0;
                if (data.shift.transactions && Array.isArray(data.shift.transactions)) {
                    transactionsToUse = data.shift.transactions;
                    totalSalesToUse = parseFloat(data.shift.total_sales || 0);
                    console.log('✅ Using transactions from server (sync with DB):', transactionsToUse.length, 'transactions');
                } else {
                    // Fallback only when the server omitted the transactions field
                    const storedShift = localStorage.getItem('activeShift');
                    if (storedShift) {
                        try {
                            const parsedShift = JSON.parse(storedShift);
                            if (parsedShift.id === data.shift.id && parsedShift.transactions && Array.isArray(parsedShift.transactions)) {
                                transactionsToUse = parsedShift.transactions;
                                totalSalesToUse = parseFloat(parsedShift.totalSales || 0);
                                console.log('✅ Using transactions from localStorage:', transactionsToUse.length, 'transactions');
                            }
                        } catch (e) {
                            console.warn('Could not parse stored shift for transaction preservation:', e);
                        }
                    }
                }
                
                // Prefer server Asia/Manila start_time; refresh lock if server sends a newer valid value.
                let serverStartTime = data.shift.start_time;
                let startTimeChanged = false;
                const isManilaFormat = (value) => typeof value === 'string'
                    && /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(value)
                    && !value.includes('T')
                    && !value.includes('Z');

                if (isManilaFormat(serverStartTime)) {
                    if (lockedStartTime !== serverStartTime) {
                        console.log('🔒 Updating locked startTime from server (Asia/Manila):', serverStartTime);
                        startTimeChanged = true;
                        saveLockedStartTime(serverStartTime);
                    } else {
                        console.log('🔒 Using locked startTime (Asia/Manila):', lockedStartTime);
                    }
                } else if (lockedStartTime) {
                    console.log('🔒 Server time invalid; keeping locked startTime:', lockedStartTime);
                    serverStartTime = lockedStartTime;
                } else if (serverStartTime && (serverStartTime.includes('T') || serverStartTime.includes('Z'))) {
                    console.error('❌ ERROR: Server returned invalid time format (ISO/UTC):', serverStartTime);
                    const storedShift = localStorage.getItem('activeShift');
                    if (storedShift) {
                        try {
                            const parsed = JSON.parse(storedShift);
                            if (isManilaFormat(parsed.startTime)) {
                                serverStartTime = parsed.startTime;
                                saveLockedStartTime(serverStartTime);
                            }
                        } catch (e) {
                            console.error('Error parsing stored shift:', e);
                        }
                    }
                }
                
                if (startTimeChanged || !cachedStartTimeDisplay) {
                    cachedStartTimeDisplay = null;
                    console.log('🔄 Cleared cache due to startTime change');
                } else {
                    console.log('✅ Keeping cached time - startTime unchanged');
                }
                
                    shiftData = {
                        id: data.shift.id,
                        isActive: true,
                    startTime: serverStartTime,
                    openingCash: parseFloat(data.shift.opening_cash || 0),
                    totalSales: totalSalesToUse > 0 ? totalSalesToUse : parseFloat(data.shift.total_sales || 0),
                    transactionCount: transactionsToUse.length,
                    transactions: transactionsToUse,
                        paymentMethods: data.shift.payment_methods || { cash: 0, card: 0, gcash: 0, maya: 0 },
                        cashierName: data.shift.cashier_name || data.user.name
                    };
                
                // CRITICAL: Always use locked time in shiftData before saving
                if (lockedStartTime) {
                    shiftData.startTime = lockedStartTime;
                }
                
                console.log('✅ Shift data loaded from server (shift-specific):', shiftData);
                console.log('✅ Using locked startTime (Asia/Manila):', shiftData.startTime);
                console.log('✅ Transactions preserved:', shiftData.transactions.length);
                
                // CRITICAL: Validate time format before saving
                if (shiftData.startTime && (shiftData.startTime.includes('T') || shiftData.startTime.includes('Z'))) {
                    console.error('❌ ERROR: Invalid time format (should be Asia/Manila format)!', shiftData.startTime);
                } else {
                    console.log('✅ Time format validated - correct Asia/Manila format');
                }
                
                localStorage.setItem('activeShift', JSON.stringify(shiftData));
                console.log('💾 Saved to localStorage with validated startTime:', shiftData.startTime);
                updateShiftStatus();
                
                // Update analytics immediately with shift-specific data (preserves transactions on refresh)
                updateAnalytics();
            } else {
                // Server says no active shift - clear localStorage and update UI
                console.warn('⚠️ Server reports no active shift, but localStorage may have one. Clearing localStorage...');
                localStorage.removeItem('activeShift');
                
                // Clear shiftData
                cachedStartTimeDisplay = null; // Clear cache when shift ends
                clearLockedStartTime(); // Clear lock when shift ends
                shiftData = {
                    isActive: false,
                    startTime: null,
                    openingCash: 0,
                    totalSales: 0,
                    transactions: [],
                    paymentMethods: { cash: 0 }
                };
                
                // Update UI to reflect no active shift
                updateShiftStatus();
                updateAnalytics(); // Reset analytics to zero
                
                // Show modal to start a new shift
                window.showStartShiftModalWithRetry(0);
            }
        } catch (error) {
            console.error('Error fetching active shift:', error);
            // Check localStorage as backup before assuming no shift
            const storedShift = localStorage.getItem('activeShift');
            if (storedShift) {
                try {
                    const parsedShift = JSON.parse(storedShift);
                    if (parsedShift.isActive && parsedShift.startTime) {
                        console.log('Using stored shift data from localStorage');
                        shiftData = parsedShift;
                        updateShiftStatus();
                        return; // Don't show modal if we have stored shift data
                    }
                } catch (e) {
                    console.error('Error parsing stored shift:', e);
                }
            }
            
            shiftData = {
                isActive: false,
                startTime: null,
                openingCash: 0,
                totalSales: 0,
                transactions: [],
                paymentMethods: { cash: 0 }
            };
            updateShiftStatus();
            showNotification('Error loading shift data', 'error');
            // Don't show modal on error - let user check manually or retry
            console.log('Error loading shift - not showing modal automatically');
        }
        
        // Initialize components
        initializeSearch();
        initializePaymentMethods();
        initializeDiscountButtons();
        initializeIdPolling();
        initializeShiftManagement();
        initializeRefunds();
        
        // Initialize analytics - wait for Chart.js to be fully loaded
        function waitForChartJS(callback, maxAttempts = 50) {
            let attempts = 0;
            const checkChart = () => {
                attempts++;
                // Check if Chart.js is loaded (v4 uses Chart, older versions use Chart.Chart)
                if (typeof Chart !== 'undefined' && (typeof Chart.Chart !== 'undefined' || typeof Chart.register !== 'undefined')) {
                    console.log('Chart.js is loaded, initializing chart...');
                    callback();
                } else if (attempts < maxAttempts) {
                    setTimeout(checkChart, 100);
                } else {
                    console.error('Chart.js failed to load after maximum attempts');
                    // Try to initialize anyway - might work
                    callback();
                }
            };
            checkChart();
        }
        
        waitForChartJS(() => {
            initializeAnalytics();
            // Load analytics data after chart is initialized
            setTimeout(() => {
                updateAnalytics(); // This will fetch all today's transactions
            }, 200);
        });
        
        // Also fetch today's transactions immediately on page load
        updateAnalytics();
        
        // Also try to initialize on window load as a fallback
        window.addEventListener('load', function() {
            if (!salesChart) {
                console.log('Window loaded, checking if chart needs initialization...');
                const canvas = document.getElementById('salesChart');
                if (canvas && typeof Chart !== 'undefined') {
                    setTimeout(() => {
                        if (!salesChart) {
                            console.log('Initializing chart on window load...');
                            initializeAnalytics();
                            updateAnalytics();
                        }
                    }, 100);
                }
            }
        });
        
        // Refresh analytics every 30 seconds to keep data current
        setInterval(updateAnalytics, 30000);
        
        // Poll for scanned items from mobile app
        initializeCashierScanPolling();
        
        // Final check: If no active shift after initialization, show modal
        setTimeout(() => {
            // Double-check: verify shift status from both memory and localStorage
            let hasActiveShift = shiftData.isActive;
            
            if (!hasActiveShift) {
                const storedShift = localStorage.getItem('activeShift');
                if (storedShift) {
                    try {
                        const parsedShift = JSON.parse(storedShift);
                        if (parsedShift.isActive && parsedShift.startTime) {
                            console.log('Final check - Found active shift in localStorage');
                            console.warn('⚠️ Using localStorage shift data - time format may be old:', parsedShift.startTime);
                            // Don't overwrite shiftData directly - fetch from server to get correct time format
                            // Only use localStorage as temporary fallback
                            if (!shiftData || !shiftData.isActive) {
                                // Clear cache to force re-formatting
                                cachedStartTimeDisplay = null;
                                shiftData = {
                                    id: parsedShift.id,
                                    isActive: parsedShift.isActive,
                                    startTime: parsedShift.startTime, // May be old format - will be corrected by server
                                    openingCash: parseFloat(parsedShift.openingCash || 0),
                                    totalSales: parseFloat(parsedShift.totalSales || 0),
                                    transactionCount: parsedShift.transactionCount || 0,
                                    transactions: parsedShift.transactions || [],
                                    paymentMethods: parsedShift.paymentMethods || { cash: 0 },
                                    cashierName: parsedShift.cashierName
                                };
                                updateShiftStatus();
                                hasActiveShift = true;
                                // Try to fetch correct time from server
                                fetch('/shifts/active').then(response => response.json()).then(data => {
                                    if (data.success && data.shift && data.shift.start_time) {
                                        console.log('🔄 Correcting startTime from server:', data.shift.start_time);
                                        shiftData.startTime = data.shift.start_time;
                                        cachedStartTimeDisplay = null; // Clear cache
                                        localStorage.setItem('activeShift', JSON.stringify(shiftData));
                                        updateShiftStatus();
                                    }
                                }).catch(err => console.warn('Could not fetch correct time from server:', err));
                            }
                        }
                    } catch (e) {
                        console.error('Error parsing stored shift:', e);
                    }
                }
            }
            
            if (!hasActiveShift && !shiftData.isActive) {
                const modalElement = document.getElementById('shiftModal');
                if (modalElement) {
                    const isModalVisible = modalElement.classList.contains('show') || 
                                         modalElement.style.display === 'block' ||
                                         window.getComputedStyle(modalElement).display === 'block';
                    if (!isModalVisible) {
                        console.log('Final check - No active shift detected, showing start shift modal...');
                        window.showStartShiftModalWithRetry(0);
                    }
                } else {
                    console.error('Modal element not found in final check!');
                }
            } else {
                console.log('Final check skipped - active shift exists');
            }
        }, 500);
    }
    
    // Call initializePOS to load shift data and wait for it to complete
    initializePOS().then(() => {
        // After shift check completes, verify one more time before showing modal
    setTimeout(function() {
            // Double-check: verify shift status from both memory and localStorage
            const storedShift = localStorage.getItem('activeShift');
            let hasActiveShift = shiftData.isActive;
            
            if (!hasActiveShift && storedShift) {
                try {
                    const parsedShift = JSON.parse(storedShift);
                    if (parsedShift.isActive && parsedShift.startTime) {
                        console.log('Found active shift in localStorage, updating shiftData');
                        shiftData = parsedShift;
                        updateShiftStatus();
                        hasActiveShift = true;
                    }
                } catch (e) {
                    console.error('Error parsing stored shift:', e);
                }
            }
            
            console.log('Emergency check - shiftData.isActive:', shiftData.isActive, 'hasActiveShift:', hasActiveShift);
            
            // Only show modal if we're CERTAIN there's no active shift
            if (!hasActiveShift && !shiftData.isActive) {
            const modalEl = document.getElementById('shiftModal');
            if (modalEl) {
                const isShown = modalEl.classList.contains('show') || 
                              window.getComputedStyle(modalEl).display === 'block' ||
                              modalEl.style.display === 'block';
                console.log('Emergency check - modal shown?', isShown);
                if (!isShown) {
                        console.log('EMERGENCY: No active shift confirmed, showing modal...');
                    if (typeof bootstrap !== 'undefined') {
                        try {
                            let modal = bootstrap.Modal.getInstance(modalEl);
                            if (!modal) {
                                modal = new bootstrap.Modal(modalEl, {
                                    backdrop: 'static',
                                    keyboard: false
                                });
                            }
                            modal.show();
                            // Force it visible
                            setTimeout(function() {
                                if (!modalEl.classList.contains('show')) {
                                    modalEl.style.display = 'block';
                                    modalEl.classList.add('show');
                                    modalEl.setAttribute('aria-hidden', 'false');
                                    document.body.classList.add('modal-open');
                                    if (!document.getElementById('shiftModalBackdrop')) {
                                        const backdrop = document.createElement('div');
                                        backdrop.className = 'modal-backdrop fade show';
                                        backdrop.id = 'shiftModalBackdrop';
                                        document.body.appendChild(backdrop);
                                    }
                                }
                            }, 100);
                        } catch(e) {
                            console.error('Error in emergency modal show:', e);
                        }
                    }
                    } else {
                        console.log('Emergency check - modal already shown');
                }
            }
            } else {
                console.log('Emergency check skipped - active shift exists');
        }
    }, 3000);
    }).catch(error => {
        console.error('Error in initializePOS:', error);
        // On error, check localStorage before showing modal
        const storedShift = localStorage.getItem('activeShift');
        if (storedShift) {
            try {
                const parsedShift = JSON.parse(storedShift);
                if (parsedShift.isActive && parsedShift.startTime) {
                    console.log('Using stored shift data after error');
                    shiftData = parsedShift;
                    updateShiftStatus();
                    return; // Don't show modal
                }
            } catch (e) {
                console.error('Error parsing stored shift:', e);
            }
        }
    });
    
    // Poll for scanned items from mobile scanner app
    let lastScannedItems = {};
    let scanPollInterval = null;
    let scanPollBootstrapped = false;

    function scannedItemIsRecent(scannedItem) {
        const stamp = scannedItem.updated_at || scannedItem.scanned_at;
        if (!stamp) {
            // No timestamp — treat as recent so new scans still appear
            return true;
        }
        // Support "YYYY-MM-DD HH:MM:SS" from Laravel and ISO strings
        let scannedAt = new Date(String(stamp).replace(' ', 'T'));
        if (isNaN(scannedAt.getTime())) {
            return true;
        }
        // If timestamp looks like UTC without timezone and is far in the future/past relative to now,
        // still allow a generous window so timezone skew does not block cart updates.
        return Math.abs(Date.now() - scannedAt.getTime()) <= 300000; // 5 minutes
    }

    function findProductForScannedItem(scannedItem) {
        const barcode = scannedItem.barcode_value;
        const productId = scannedItem.product_id || scannedItem.inventory_product_id;
        const allProducts = Object.values(products || {});

        if (productId) {
            const byId = allProducts.find(p => Number(p.id) === Number(productId) || Number(p.inventory_product_id) === Number(productId));
            if (byId) return byId;
        }

        if (barcode) {
            const byCode = allProducts.find(p =>
                p.sku === barcode
                || p.barcode === barcode
                || p.mpn === barcode
                || (p.cashier_key && String(p.cashier_key).endsWith('-' + barcode))
            );
            if (byCode) return byCode;
        }

        if (scannedItem.item_name) {
            return allProducts.find(p =>
                (p.name || '').toLowerCase() === String(scannedItem.item_name).toLowerCase()
                && (
                    !scannedItem.price_type
                    || (p.price_type || p.category || '').toLowerCase() === String(scannedItem.price_type).toLowerCase()
                )
            ) || null;
        }

        return null;
    }

    function clearCashierScanSession() {
        const barcodesToIgnore = { ...lastScannedItems };
        cart.forEach(item => {
            if (item.barcode) {
                barcodesToIgnore[item.barcode] = item.quantity || 1;
            }
        });
        lastScannedItems = barcodesToIgnore;
        scanPollBootstrapped = true;
        fetch('/api/cashier/scan/clear', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        }).then(() => {
            lastScannedItems = {};
            scanPollBootstrapped = true;
        }).catch(err => console.debug('Error clearing scanned items:', err));
    }

    async function pollCashierScannedItems() {
        try {
            const response = await fetch('/api/cashier/scan/items', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                console.error('Polling error:', response.status, response.statusText);
                return;
            }

            const data = await response.json();

            if (!data.success || !Array.isArray(data.items)) {
                return;
            }

            // Remember leftover cache on first poll so stale scans are not treated as new cart lines
            if (!scanPollBootstrapped) {
                data.items.forEach(scannedItem => {
                    if (!scannedItemIsRecent(scannedItem) && scannedItem.barcode_value) {
                        lastScannedItems[scannedItem.barcode_value] = scannedItem.quantity || 1;
                    }
                });
                scanPollBootstrapped = true;
            }

            let cartChanged = false;

            for (const scannedItem of data.items) {
                const barcode = scannedItem.barcode_value;
                if (!barcode) {
                    continue;
                }

                const quantity = scannedItem.quantity || 1;
                const lastQuantity = lastScannedItems[barcode] || 0;

                if (quantity === lastQuantity) {
                    continue;
                }

                lastScannedItems[barcode] = quantity;

                if (!scannedItemIsRecent(scannedItem) && lastQuantity === 0 && !cart.some(item => item.barcode === barcode)) {
                    continue;
                }

                const product = findProductForScannedItem(scannedItem);
                const itemPrice = parseFloat(scannedItem.price) || 0;
                const itemName = scannedItem.item_name || 'Unknown Item';
                const itemUnit = scannedItem.unit || 'pcs';

                const cartItem = cart.find(item => {
                    if (item.barcode && item.barcode === barcode) {
                        return true;
                    }
                    if (product && Number(item.id) === Number(product.id)) {
                        return true;
                    }
                    if (scannedItem.product_id && Number(item.id) === Number(scannedItem.product_id)) {
                        return true;
                    }
                    return item.name === itemName && item.barcode === barcode;
                });

                if (cartItem) {
                    if (cartItem.quantity !== quantity) {
                        cartItem.quantity = quantity;
                        cartItem.subtotal = itemPrice * quantity;
                        if (itemPrice > 0) {
                            cartItem.price = itemPrice;
                        }
                        cartChanged = true;
                        showNotification(`Updated: ${itemName} (Qty: ${quantity})`, 'info');
                    }
                } else {
                    let itemId;
                    if (product && product.id) {
                        itemId = product.id;
                    } else if (scannedItem.product_id) {
                        itemId = scannedItem.product_id;
                    } else if (scannedItem.inventory_product_id) {
                        itemId = scannedItem.inventory_product_id;
                    } else {
                        itemId = -Math.abs(barcode.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0));
                    }

                    cart.push({
                        id: itemId,
                        barcode: barcode,
                        name: itemName,
                        price: itemPrice,
                        quantity: quantity,
                        subtotal: itemPrice * quantity,
                        unit: itemUnit,
                        stock_quantity: (scannedItem.quantity_on_hand !== undefined && scannedItem.quantity_on_hand !== null)
                            ? scannedItem.quantity_on_hand
                            : 0,
                        category: product ? (product.category || product.price_type) : (scannedItem.price_type || 'Scanned Item'),
                        price_type: (product && (product.price_type || product.category)) || scannedItem.price_type || null,
                        item_id: product && product.item_id ? product.item_id : null,
                        product_id: scannedItem.product_id || (product && product.id) || null,
                        inventory_product_id: scannedItem.inventory_product_id || (product && product.inventory_product_id) || scannedItem.product_id || null,
                        barcode_id: (product && product.barcode_id) || scannedItem.barcode_id || null,
                        cashier_key: (product && (product.cashier_key || product.uniqueKey)) || (scannedItem.product_id ? ('p-' + scannedItem.product_id) : null),
                        uniqueKey: (product && (product.uniqueKey || product.cashier_key)) || (scannedItem.product_id ? ('p-' + scannedItem.product_id) : null),
                        sku: (product && product.sku) || barcode || scannedItem.barcode_value || null,
                        source: (product && product.source) || 'products',
                        image: (product && product.image) || scannedItem.item_image || scannedItem.image || null
                    });
                    cartChanged = true;
                    showNotification(`Added: ${itemName} (Qty: ${quantity})`, 'success');
                }
            }

            // Only prune scanner-origin cart lines when the API returned a successful item list.
            const scannedBarcodes = data.items.map(item => item.barcode_value).filter(Boolean);
            const beforeLen = cart.length;
            cart = cart.filter(cartItem => {
                if (cartItem.barcode) {
                    return scannedBarcodes.includes(cartItem.barcode);
                }
                return true;
            });
            if (cart.length !== beforeLen) {
                cartChanged = true;
            }

            if (cartChanged) {
                updateCartDisplay();
            }
        } catch (error) {
            console.debug('Scan polling error:', error);
        }
    }

    function initializeCashierScanPolling() {
        console.log('Starting cashier scan polling...');
        // Fetch immediately, then keep polling quickly so phone scans appear in the cart right away
        pollCashierScannedItems();
        scanPollInterval = setInterval(pollCashierScannedItems, 500);
    }

    function initializeSearch() {
        const barcodeInput = document.getElementById('barcodeInput');
        const searchResults = document.getElementById('searchResults');
        const productDetails = document.getElementById('productDetails');
        const addToCartBtn = document.getElementById('addToCartBtn');
        
        let selectedProduct = null;

        let barcodeLookupController = null; // For canceling barcode API calls

        barcodeInput.addEventListener('input', async function() {
            const query = this.value.trim();
            
            // Cancel any pending barcode lookup
            if (barcodeLookupController && barcodeLookupController.signal) {
                try {
                    barcodeLookupController.abort();
                } catch (e) {
                    // Ignore errors if controller is already aborted
                }
                barcodeLookupController = null;
            }
            
            if (query.length < 1) {
                searchResults.classList.remove('show');
                productDetails.style.display = 'none';
                return;
            }
            
            // FIRST: Search products locally immediately (no API delay!)
            const queryLower = query.toLowerCase();
            
            // Get ALL products including those with unique keys (duplicates)
            // Use Object.values() to get all products regardless of their key (ID or uniqueKey)
            const allProducts = Object.values(products);
            
            // Debug: Log total products available for search
            if (queryLower.includes('face mask')) {
                console.log('🔍 Face mask search - Total products in object:', Object.keys(products).length);
                console.log('🔍 Face mask search - Products from Object.values():', allProducts.length);
                const allFaceMaskInAllProducts = allProducts.filter(p => p && p.name && p.name.toLowerCase().includes('face mask'));
                console.log('🔍 Face mask search - Face mask items found in allProducts:', allFaceMaskInAllProducts.length);
            }
            
            const filteredProducts = allProducts.filter(product => cashierProductMatchesQuery(product, query));
            
            // Debug logging for face mask search
            if (queryLower.includes('face mask')) {
                const allFaceMaskInProducts = allProducts.filter(p => p && p.name && p.name.toLowerCase().includes('face mask'));
                const allFaceMaskKeys = Object.keys(products).filter(k => {
                    const p = products[k];
                    return p && p.name && p.name.toLowerCase().includes('face mask');
                });
                
                console.log('🔍 Face mask search debug:', {
                    totalProducts: Object.keys(products).length,
                    allProductsCount: allProducts.length,
                    allFaceMaskInProducts: allFaceMaskInProducts.length,
                    allFaceMaskKeys: allFaceMaskKeys.length,
                    filteredCount: filteredProducts.length,
                    query: query,
                    allFaceMaskProducts: allFaceMaskInProducts.map(p => ({
                        id: p.id,
                        name: p.name,
                        uniqueKey: p.uniqueKey || 'none',
                        key: Object.keys(products).find(k => products[k] === p)
                    })),
                    allFaceMaskKeys: allFaceMaskKeys,
                    missingItems: allFaceMaskKeys.length !== filteredProducts.length ? 
                        allFaceMaskKeys.filter(k => !filteredProducts.some(p => Object.keys(products).find(key => products[key] === p) === k)) : []
                });
            }
            
            // Display results immediately - no waiting!
            if (filteredProducts.length > 0) {
                displaySearchResults(filteredProducts, query);
            } else {
                searchResults.innerHTML = '<div class="dropdown-item text-muted">No products found</div>';
                searchResults.classList.add('show');
            }
            
            // THEN: Check barcode API in background (only if query looks like a barcode and no exact match found)
            // Only check barcode if query is longer and looks like a barcode (all digits or alphanumeric)
            if (query.length >= 3 && /^[A-Za-z0-9]+$/.test(query)) {
                // Check if we already have an exact match by SKU
                const exactMatch = filteredProducts.find(p => p.sku && p.sku.toLowerCase() === queryLower);
                
                if (!exactMatch) {
                    // No exact match, try barcode lookup in background
                    barcodeLookupController = new AbortController();
                    const currentController = barcodeLookupController; // Store reference
                    const currentSignal = currentController.signal; // Store signal reference
                    
                    try {
                        const barcodeResponse = await fetch(`/barcodes/lookup?value=${encodeURIComponent(query)}`, {
                            signal: currentSignal
                        });
                        
                        // Check if controller was aborted (using stored reference)
                        if (currentController && currentSignal.aborted) {
                            return; // Request was cancelled
                        }
                        
                const barcodeData = await barcodeResponse.json();
                
                if (barcodeData.success && barcodeData.found && barcodeData.barcode) {
                    const barcode = barcodeData.barcode;
                    const stored = findStoredCashierProduct({
                        name: barcode.item_name,
                        sku: query,
                        barcode_id: barcode.id,
                        price_type: barcode.price_type
                    });
                    if (stored) {
                        selectProduct(stored, {
                            price_type: barcode.price_type,
                            unit: barcode.unit,
                            expiration_date: barcode.expiration_date,
                            item_image: barcode.item_image
                        });
                        return;
                    }
                    const barcodeProduct = {
                        id: 0,
                        name: barcode.item_name,
                        price: parseFloat(barcode.price),
                        stock_quantity: (barcode.quantity_on_hand !== undefined && barcode.quantity_on_hand !== null)
                            ? parseFloat(barcode.quantity_on_hand)
                            : 0,
                        category: barcode.price_type || 'Barcode Item',
                        price_type: barcode.price_type || null,
                        sku: query,
                        barcode_id: barcode.id || null,
                        cashier_key: barcode.id ? ('bc-' + barcode.id) : null,
                        uniqueKey: barcode.id ? ('bc-' + barcode.id) : null,
                        source: 'barcodes',
                        min_stock_level: 0,
                        unit: barcode.unit
                    };
                    
                    selectProduct(barcodeProduct, {
                        price_type: barcode.price_type,
                        unit: barcode.unit,
                        expiration_date: barcode.expiration_date,
                        item_image: barcode.item_image
                    });
                    return;
                }
            } catch (error) {
                        if (error.name !== 'AbortError') {
                            // Only log if it's not an abort error and controller still exists
                            if (currentController && !currentSignal.aborted) {
                console.error('Error looking up barcode:', error);
            }
                        }
                    } finally {
                        // Only clear if this is still the current controller
                        if (barcodeLookupController === currentController) {
                            barcodeLookupController = null;
                        }
                    }
                }
            }
        });

        // Barcode scanning simulation
        document.getElementById('barcodeScanBtn').addEventListener('click', function() {
            this.classList.add('scanning');
            showNotification('Barcode scanner activated. Point camera at barcode.', 'info');
            
            // Simulate barcode scan after 2 seconds
            setTimeout(() => {
                this.classList.remove('scanning');
                // In real implementation, this would be handled by a barcode scanner library
                const randomProduct = Object.values(products)[Math.floor(Math.random() * Object.values(products).length)];
                barcodeInput.value = randomProduct.sku;
                selectProduct(randomProduct);
            }, 2000);
        });

        addToCartBtn.addEventListener('click', function() {
            if (selectedProduct) {
                addToCart(selectedProduct);
                barcodeInput.value = '';
                productDetails.style.display = 'none';
                selectedProduct = null;
            }
        });

        function selectProduct(product, barcodeInfo = null) {
            // ALWAYS get the latest product data from the products object to ensure accurate stock
            // This ensures we're using the most up-to-date quantity from item_lists
            let latestProduct = product;
            const stored = findStoredCashierProduct(product);
            if (stored) {
                latestProduct = stored;
                console.log('🔄 Using latest product from products object:', {
                    id: latestProduct.id,
                    name: latestProduct.name,
                    stock_quantity: latestProduct.stock_quantity,
                    old_stock: product.stock_quantity
                });
            }
            
            selectedProduct = latestProduct;
            document.getElementById('productName').textContent = latestProduct.name;
            document.getElementById('productSku').textContent = `SKU: ${latestProduct.sku || 'N/A'}`;
            const productPrice = parseFloat(latestProduct.price) || 0;
            document.getElementById('productPrice').textContent = `₱${productPrice.toFixed(2)}`;
            
            // Display product image if available (prefer barcode info, then product image)
            const productImage = document.getElementById('productImage');
            const itemImageUrl = cashierItemImageUrl(
                (barcodeInfo && barcodeInfo.item_image) ? barcodeInfo.item_image : latestProduct.image
            );

            if (itemImageUrl) {
                productImage.src = itemImageUrl;
                productImage.style.display = 'block';
                productImage.onerror = function() {
                    if (!this.dataset.altTried) {
                        this.dataset.altTried = '1';
                        const alt = cashierAlternateImageUrl(this.src);
                        if (alt) {
                            this.src = alt;
                            return;
                        }
                    }
                    this.style.display = 'none';
                };
            } else {
                productImage.style.display = 'none';
            }
            
            // Display price type and unit if barcode info is available
            const priceTypeBadge = document.getElementById('productPriceType');
            const unitBadge = document.getElementById('productUnit');
            
            if (barcodeInfo && barcodeInfo.price_type) {
                priceTypeBadge.textContent = barcodeInfo.price_type.charAt(0).toUpperCase() + barcodeInfo.price_type.slice(1);
                priceTypeBadge.style.display = 'inline-block';
            } else {
                priceTypeBadge.style.display = 'none';
            }
            
            if (barcodeInfo && barcodeInfo.unit) {
                unitBadge.textContent = barcodeInfo.unit.toUpperCase();
                unitBadge.style.display = 'inline-block';
            } else if (latestProduct.unit) {
                unitBadge.textContent = latestProduct.unit.toUpperCase();
                unitBadge.style.display = 'inline-block';
            } else {
                unitBadge.style.display = 'none';
            }
            
            const stockBadge = document.getElementById('stockBadge');
            const conversionAlert = document.getElementById('wholesaleConversionAlert');
            
            // Use latestProduct.stock_quantity to ensure we show the correct quantity from item_lists
            if (latestProduct.stock_quantity > latestProduct.min_stock_level) {
                stockBadge.className = 'badge bg-success';
                stockBadge.textContent = `${latestProduct.stock_quantity} in stock`;
                conversionAlert.style.display = 'none';
            } else if (latestProduct.stock_quantity === 0) {
                stockBadge.className = 'badge bg-danger';
                stockBadge.textContent = 'Out of stock';
                
                // Check if this is a retail product and if there's a wholesale version available
                if (latestProduct.category === 'retail' || latestProduct.price_type === 'retail') {
                    checkForWholesaleVersion(latestProduct);
                } else {
                    conversionAlert.style.display = 'none';
                }
            } else {
                stockBadge.className = 'badge bg-warning';
                stockBadge.textContent = `${latestProduct.stock_quantity} low stock`;
                conversionAlert.style.display = 'none';
            }
            
            console.log('📊 Displaying product stock:', {
                name: latestProduct.name,
                stock_quantity: latestProduct.stock_quantity,
                displayed: stockBadge.textContent
            });
            
            // Check expiration - prioritize barcode expiration date if available
            const expirationWarning = document.getElementById('expirationWarning');
            const expiryDate = (barcodeInfo && barcodeInfo.expiration_date) ? barcodeInfo.expiration_date : product.expiry_date;
            
            if (expiryDate && isExpiringSoon(expiryDate)) {
                expirationWarning.style.display = 'block';
            } else {
                expirationWarning.style.display = 'none';
            }
            
            productDetails.style.display = 'block';
            searchResults.classList.remove('show');
        }
        
        // Check for wholesale version when retail is out of stock
        function checkForWholesaleVersion(retailProduct) {
            const conversionAlert = document.getElementById('wholesaleConversionAlert');
            const requestConversionBtn = document.getElementById('requestConversionBtn');
            
            // Find wholesale version with same name
            const wholesaleProduct = Object.values(products).find(p => 
                p.name === retailProduct.name && 
                (p.category === 'wholesale' || p.price_type === 'wholesale') &&
                p.stock_quantity > 0
            );
            
            if (wholesaleProduct) {
                conversionAlert.style.display = 'block';
                requestConversionBtn.onclick = function() {
                    showConversionRequestModal(retailProduct, wholesaleProduct);
                };
            } else {
                conversionAlert.style.display = 'none';
            }
        }
        
        // Show conversion request modal
        function showConversionRequestModal(retailProduct, wholesaleProduct) {
            document.getElementById('conversionRetailName').textContent = retailProduct.name;
            document.getElementById('conversionWholesaleName').textContent = wholesaleProduct.name;
            document.getElementById('conversionWholesaleStock').textContent = `${wholesaleProduct.stock_quantity} ${wholesaleProduct.unit || 'pcs'}`;
            document.getElementById('conversionMaxQuantity').textContent = `${wholesaleProduct.stock_quantity} ${wholesaleProduct.unit || 'pcs'}`;
            document.getElementById('conversionQuantity').max = wholesaleProduct.stock_quantity;
            document.getElementById('conversionQuantity').value = '';
            document.getElementById('conversionNotes').value = '';
            
            // Store product IDs for submission
            window.currentConversionRequest = {
                retailItemId: retailProduct.id,
                wholesaleItemId: wholesaleProduct.id
            };
            
            const modal = new bootstrap.Modal(document.getElementById('conversionRequestModal'));
            modal.show();
        }
        
        // Handle conversion request submission
        if (document.getElementById('submitConversionRequestBtn')) {
            document.getElementById('submitConversionRequestBtn').addEventListener('click', async function() {
                if (!window.currentConversionRequest) {
                    showNotification('Error: No conversion request data', 'error');
                    return;
                }
                
                const quantity = document.getElementById('conversionQuantity').value;
                const notes = document.getElementById('conversionNotes').value;
                const submitBtn = this;
                const originalText = submitBtn.innerHTML;
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Submitting...';
                
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]');
                    const response = await fetch('/api/wholesale-to-retail-requests', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken.content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            retail_item_id: window.currentConversionRequest.retailItemId,
                            wholesale_item_id: window.currentConversionRequest.wholesaleItemId,
                            quantity_to_convert: quantity ? parseFloat(quantity) : null,
                            request_notes: notes || null
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showNotification('Conversion request submitted successfully. Waiting for admin approval.', 'success');
                        const modal = bootstrap.Modal.getInstance(document.getElementById('conversionRequestModal'));
                        modal.hide();
                        
                        // Reload products to refresh stock
                        setTimeout(() => {
                            reloadProducts();
                        }, 1000);
                    } else {
                        showNotification(data.message || 'Failed to submit conversion request', 'error');
                    }
                } catch (error) {
                    console.error('Error submitting conversion request:', error);
                    showNotification('Error submitting conversion request: ' + error.message, 'error');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            });
        }
        
        // Make selectProduct accessible globally for refreshing after product reload
        window.selectProduct = selectProduct;
        window.selectedProductRef = () => selectedProduct;
        
        // Function to update search results dropdown position
        function updateSearchResultsPosition() {
            const searchResults = document.getElementById('searchResults');
            const barcodeInput = document.getElementById('barcodeInput');
            if (searchResults && barcodeInput && searchResults.classList.contains('show')) {
                const inputGroup = barcodeInput.closest('.input-group');
                if (inputGroup) {
                    const rect = inputGroup.getBoundingClientRect();
                    searchResults.style.top = (rect.bottom + window.scrollY + 4) + 'px';
                    searchResults.style.left = (rect.left + window.scrollX) + 'px';
                    searchResults.style.width = Math.max(rect.width, 400) + 'px';
                }
            }
        }
        
        // Update position on scroll and resize
        window.addEventListener('scroll', updateSearchResultsPosition, true);
        window.addEventListener('resize', function() {
            updateSearchResultsPosition();
            // Recalculate height on resize for responsiveness
            if (searchResults && searchResults.classList.contains('show')) {
                const displayedItems = searchResults.querySelectorAll('.product-search-item').length;
                if (displayedItems > 0) {
                    // Recalculate optimal height
                    const viewportHeight = window.innerHeight;
                    const viewportWidth = window.innerWidth;
                    const headerHeight = 120;
                    const availableHeight = viewportHeight - headerHeight;
                    const itemHeight = 52;
                    const bottomPadding = 120; // Match the increased padding
                    const topPadding = 20;
                    const lastItemExtraSpace = 60; // Extra space for last item
                    const estimatedHeight = (displayedItems * itemHeight) + bottomPadding + topPadding + lastItemExtraSpace;
                    
                    let maxHeight;
                    if (viewportWidth < 768) {
                        // Mobile: use 65% of viewport (increased to show more items)
                        maxHeight = Math.min(estimatedHeight, viewportHeight * 0.65);
                    } else if (viewportWidth < 1024) {
                        // Tablet: use 75% of viewport (increased to show more items)
                        maxHeight = Math.min(estimatedHeight, viewportHeight * 0.75);
                    } else {
                        // Desktop: use available height or estimated height, whichever is smaller
                        maxHeight = Math.min(estimatedHeight, availableHeight);
                    }
                    
                    // Ensure minimum height for usability (show at least 5-6 items)
                    const minHeight = Math.min(400, Math.max(300, displayedItems * itemHeight + 40));
                    let finalHeight = Math.max(minHeight, maxHeight);
                    
                    // For 50 items, ensure we have enough height to show them all with scrolling
                    if (displayedItems >= 50) {
                        const maxPossibleHeight = Math.min(estimatedHeight, viewportHeight - 80);
                        if (maxPossibleHeight > finalHeight) {
                            finalHeight = maxPossibleHeight;
                        }
                    }
                    
                    searchResults.style.maxHeight = finalHeight + 'px';
                }
            }
        });

        function isExpiringSoon(expiryDate, days = 30) {
            const today = new Date();
            const expiry = new Date(expiryDate);
            const diffTime = expiry - today;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            return diffDays <= days && diffDays > 0;
        }

        // Hide search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!barcodeInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.remove('show');
            }
        });
        
        // Keyboard navigation
        barcodeInput.addEventListener('keydown', function(e) {
        const items = document.querySelectorAll('.product-search-item');
        let selected = document.querySelector('.product-search-item.bg-primary');
        let selectedIndex = Array.from(items).indexOf(selected);
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (selectedIndex < items.length - 1) {
                if (selected) selected.classList.remove('bg-primary', 'text-white');
                items[selectedIndex + 1].classList.add('bg-primary', 'text-white');
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (selectedIndex > 0) {
                if (selected) selected.classList.remove('bg-primary', 'text-white');
                items[selectedIndex - 1].classList.add('bg-primary', 'text-white');
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const selected = document.querySelector('.product-search-item.bg-primary');
            if (selected) {
                selected.click();
            }
        } else if (e.key === 'Escape') {
            searchResults.classList.remove('show');
            this.blur();
        }
    });

        async function displaySearchResults(filteredProducts, searchQuery = '') {
        console.log('displaySearchResults called with', filteredProducts.length, 'products');
        searchResults.innerHTML = '';
        
        const queryLower = (searchQuery || '').toLowerCase();
        const isFaceMaskSearch = queryLower.includes('face mask');
        const productsToShow = filteredProducts;
        const maxResults = productsToShow.length;
        console.log('Displaying', productsToShow.length, 'of', filteredProducts.length, 'products');
        
        let displayedCount = 0;
        let skippedCount = 0;
        const skippedProducts = [];
        
        for (const product of productsToShow) {
            // Validate product data before creating display element
            if (!product || !product.name) {
                skippedCount++;
                skippedProducts.push({ reason: 'missing name', product: product });
                console.warn('⚠️ Skipping product - missing name:', product);
                continue;
            }
            
            // Check for required numeric fields - use defaults if missing
            const productPrice = product.price !== undefined && product.price !== null && !isNaN(product.price) 
                ? parseFloat(product.price) 
                : 0;
            const productStock = product.stock_quantity !== undefined && product.stock_quantity !== null && !isNaN(product.stock_quantity)
                ? parseFloat(product.stock_quantity)
                : 0;
            const productMinStock = product.min_stock_level !== undefined && product.min_stock_level !== null && !isNaN(product.min_stock_level)
                ? parseFloat(product.min_stock_level)
                : 0;
            
            // Only skip if critical data is missing (name is already checked above)
            // Price and stock can be 0, but should still be displayed
            const item = document.createElement('div');
            item.className = 'dropdown-item d-flex align-items-center product-search-item';
            item.style.cursor = 'pointer';
            
            // Use pre-loaded image from product data
            const productImage = cashierItemImageUrl(product.image);

            const imageHtml = cashierProductImageHtml(productImage, product.name, 32);
            
            // Format brand display (show after item name if brand exists)
            const brandDisplay = product.brand ? ` <span class="text-muted fw-normal">- ${product.brand}</span>` : '';
            
            try {
                item.innerHTML = `
                    <div class="me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        ${imageHtml}
                    </div>
                    <div class="flex-grow-1" style="min-width: 0;">
                        <div class="fw-semibold" style="font-size: 0.9rem; line-height: 1.3;">${product.name}${brandDisplay}</div>
                        <small class="text-muted" style="font-size: 0.75rem; line-height: 1.2;">${product.sku || 'No SKU'} • ₱${productPrice.toFixed(2)} • Stock: ${productStock}</small>
                    </div>
                    <div class="ms-2" style="flex-shrink: 0;">
                        <span class="badge ${productStock > productMinStock ? 'bg-success' : 'bg-warning'}" style="font-size: 0.7rem; padding: 0.25rem 0.5rem;">
                            ${productStock} ${product.unit || 'pcs'}
                        </span>
                    </div>
                `;
                
                searchResults.appendChild(item);
                displayedCount++;
                
                if (isFaceMaskSearch) {
                    console.log(`✅ Displayed face mask item ${displayedCount}:`, product.name, product.id || product.uniqueKey);
                }
            } catch (error) {
                skippedCount++;
                skippedProducts.push({ reason: 'display error', product: product, error: error.message });
                console.error('❌ Error displaying product:', product.name, error);
            }
            
            item.addEventListener('click', async function(e) {
                // ALWAYS get the latest product from products object to ensure accurate stock
                // This ensures we're using the most up-to-date quantity from item_lists
                let latestProduct = product;
                const stored = findStoredCashierProduct(product);
                if (stored) {
                    latestProduct = stored;
                }
                
                // Ensure product has all required properties for addToCart
                if ((!latestProduct.id && !latestProduct.uniqueKey && !latestProduct.inventory_product_id && !latestProduct.item_id) || !latestProduct.name || latestProduct.price === undefined) {
                    console.error('Product missing required properties:', latestProduct);
                    showNotification('Error: Product data incomplete', 'error');
                    return;
                }
                
                // Ensure stock_quantity is a number
                latestProduct.stock_quantity = parseInt(latestProduct.stock_quantity || 0);
                latestProduct.price = parseFloat(latestProduct.price || 0);
                
                // Directly add to cart when clicking product item
                try {
                    addToCart(latestProduct);
                    
                    // Also update product details display
                // Try to get barcode info for image
                let barcodeInfo = null;
                if (latestProduct.sku && latestProduct.sku !== 'N/A') {
                    try {
                        const barcodeResponse = await fetch(`/barcodes/lookup?value=${encodeURIComponent(latestProduct.sku)}`);
                        const barcodeData = await barcodeResponse.json();
                        if (barcodeData.success && barcodeData.found && barcodeData.barcode) {
                            barcodeInfo = {
                                price_type: barcodeData.barcode.price_type,
                                unit: barcodeData.barcode.unit,
                                expiration_date: barcodeData.barcode.expiration_date,
                                item_image: barcodeData.barcode.item_image
                            };
                        }
                    } catch (error) {
                        // Silently fail
                    }
                }
                selectProduct(latestProduct, barcodeInfo);
                
                // Highlight selected item
                document.querySelectorAll('.product-search-item').forEach(el => el.classList.remove('bg-primary', 'text-white'));
                this.classList.add('bg-primary', 'text-white');
                    
                    // Clear search input
                    barcodeInput.value = '';
                    searchResults.classList.remove('show');
                } catch (error) {
                    console.error('Error adding product to cart:', error);
                    showNotification('Error adding product to cart', 'error');
                }
            });
        }
        
        // Log summary of displayed vs skipped products
        if (isFaceMaskSearch || skippedCount > 0) {
            console.log(`📊 Display Summary: ${displayedCount} displayed, ${skippedCount} skipped out of ${productsToShow.length} products`);
            if (skippedCount > 0) {
                console.log('⚠️ Skipped products:', skippedProducts);
            }
        }
        
        // Verify all items are actually in the DOM
        const actualItemsInDOM = searchResults.querySelectorAll('.product-search-item').length;
        if (isFaceMaskSearch && actualItemsInDOM !== displayedCount) {
            console.warn(`⚠️ Mismatch: ${displayedCount} items appended but ${actualItemsInDOM} items found in DOM`);
            // Log all items in DOM to see which ones are missing
            const itemsInDOM = Array.from(searchResults.querySelectorAll('.product-search-item'));
            itemsInDOM.forEach((item, index) => {
                const nameEl = item.querySelector('.fw-semibold');
                const name = nameEl ? nameEl.textContent.trim() : 'Unknown';
                console.log(`DOM item ${index + 1}:`, name);
            });
        }
        
        // Show message if there are more results (after all products)
        if (filteredProducts.length > maxResults) {
            const moreResultsMsg = document.createElement('div');
            moreResultsMsg.className = 'dropdown-item text-muted text-center border-top';
            moreResultsMsg.style.paddingTop = '8px';
            moreResultsMsg.innerHTML = `<small>Showing ${maxResults} of ${filteredProducts.length} results. Refine your search to see more specific results.</small>`;
            searchResults.appendChild(moreResultsMsg);
        }
        
        // Position the dropdown relative to the input field (for fixed positioning)
        const inputGroup = barcodeInput.closest('.input-group');
        if (inputGroup) {
            const rect = inputGroup.getBoundingClientRect();
            searchResults.style.top = (rect.bottom + window.scrollY + 4) + 'px';
            searchResults.style.left = (rect.left + window.scrollX) + 'px';
            searchResults.style.width = Math.max(rect.width, 400) + 'px';
        }
        
        // Responsive height calculation for all searches - dynamically adjust based on item count and viewport
        const calculateOptimalHeight = () => {
            // Get viewport dimensions
            const viewportHeight = window.innerHeight;
            const viewportWidth = window.innerWidth;
            
            // Calculate available space (leave room for header, input, and margins)
            const headerHeight = 120; // Approximate header + input field height
            const availableHeight = viewportHeight - headerHeight;
            
            // Item height calculation (50px min-height + padding)
            const itemHeight = 52; // Approximate height per item
            const bottomPadding = 120; // Extra padding at bottom to ensure last item is fully visible (increased for better visibility)
            const topPadding = 20; // Top padding/borders
            const lastItemExtraSpace = 60; // Extra space for last item margin
            
            // Calculate estimated height needed for all items
            // Add extra space to ensure last item is fully visible when scrolled
            const estimatedHeight = (displayedCount * itemHeight) + bottomPadding + topPadding + lastItemExtraSpace;
            
            // For mobile/small screens, use a smaller percentage of viewport
            // But ensure we can show at least 10-15 items, and up to 50 items on larger screens
            let maxHeight;
            if (viewportWidth < 768) {
                // Mobile: use 65% of viewport (increased to show more items)
                maxHeight = Math.min(estimatedHeight, viewportHeight * 0.65);
            } else if (viewportWidth < 1024) {
                // Tablet: use 75% of viewport (increased to show more items)
                maxHeight = Math.min(estimatedHeight, viewportHeight * 0.75);
            } else {
                // Desktop: use available height or estimated height, whichever is smaller
                // For 50 items, estimated height would be: (50 * 52) + 120 + 20 + 60 = 2800px
                // But we'll cap it at viewport height minus header
                maxHeight = Math.min(estimatedHeight, availableHeight);
            }
            
            // Ensure minimum height for usability (show at least 5-6 items)
            const minHeight = Math.min(400, Math.max(300, displayedCount * itemHeight + 40));
            const finalHeight = Math.max(minHeight, maxHeight);
            
            // For 50 items, ensure we have enough height to show them all with scrolling
            // 50 items * 52px = 2600px, plus padding = ~2800px total
            // But we'll limit to viewport height and allow scrolling
            if (displayedCount >= 50) {
                // For 50 items, use maximum available space
                const maxPossibleHeight = Math.min(estimatedHeight, viewportHeight - 80);
                if (maxPossibleHeight > finalHeight) {
                    return {
                        finalHeight: maxPossibleHeight,
                        estimatedHeight,
                        maxHeight: maxPossibleHeight,
                        availableHeight,
                        viewportHeight,
                        viewportWidth
                    };
                }
            }
            
            return {
                finalHeight,
                estimatedHeight,
                maxHeight,
                availableHeight,
                viewportHeight,
                viewportWidth
            };
        };
        
        const heightInfo = calculateOptimalHeight();
        searchResults.style.maxHeight = heightInfo.finalHeight + 'px';
        
        console.log(`📏 Responsive dropdown height: ${heightInfo.finalHeight}px for ${displayedCount} items`, {
            viewport: `${heightInfo.viewportWidth}x${heightInfo.viewportHeight}`,
            estimated: `${heightInfo.estimatedHeight}px`,
            available: `${heightInfo.availableHeight}px`,
            final: `${heightInfo.finalHeight}px`
        });
        
        // Force a reflow to ensure the height is applied
        searchResults.offsetHeight;
        
        // Ensure scrollbar is visible and scrolling works
        searchResults.style.overflowY = 'auto';
        searchResults.style.overflowX = 'hidden';
        
        // After showing, verify scrollability and ensure last items are accessible
        setTimeout(() => {
            const scrollHeight = searchResults.scrollHeight;
            const clientHeight = searchResults.clientHeight;
            const isScrollable = scrollHeight > clientHeight;
            
            if (isScrollable) {
                console.log(`📜 Dropdown is scrollable: scrollHeight=${scrollHeight}px, clientHeight=${clientHeight}px`);
                // Ensure we can scroll to the bottom
                const maxScroll = scrollHeight - clientHeight;
                if (maxScroll > 0) {
                    // Verify last item is reachable and fully visible when scrolled
                    const lastItem = searchResults.querySelector('.product-search-item:last-child');
                    if (lastItem) {
                        const lastItemRect = lastItem.getBoundingClientRect();
                        const containerRect = searchResults.getBoundingClientRect();
                        const lastItemBottom = lastItem.offsetTop + lastItem.offsetHeight;
                        const containerBottom = clientHeight;
                        const scrollableDistance = scrollHeight - clientHeight;
                        
                        console.log(`📋 Last item position: ${lastItemBottom}px, Container height: ${clientHeight}px, Scrollable: ${scrollableDistance}px`);
                        
                        // Check if we need to adjust height to ensure last item is fully visible
                        if (lastItemBottom > containerBottom - 50) {
                            // Last item might be cut off, increase max-height slightly
                            const currentMaxHeight = parseInt(searchResults.style.maxHeight) || heightInfo.finalHeight;
                            const newMaxHeight = currentMaxHeight + 50; // Add 50px buffer
                            const viewportMax = window.innerHeight - 100;
                            if (newMaxHeight <= viewportMax) {
                                searchResults.style.maxHeight = newMaxHeight + 'px';
                                console.log(`🔧 Adjusted dropdown height to ${newMaxHeight}px to ensure last item is visible`);
                            }
                        }
                        
                        // Test scroll to bottom to verify last item is accessible
                        const originalScrollTop = searchResults.scrollTop;
                        searchResults.scrollTop = scrollHeight; // Scroll to bottom
                        setTimeout(() => {
                            const lastItemVisible = lastItem.getBoundingClientRect();
                            const containerVisible = searchResults.getBoundingClientRect();
                            const isFullyVisible = lastItemVisible.bottom <= containerVisible.bottom;
                            
                            if (isFullyVisible) {
                                console.log(`✅ Last item is fully visible when scrolled to bottom`);
                            } else {
                                console.warn(`⚠️ Last item may still be partially cut off`);
                            }
                            
                            // Restore scroll position
                            searchResults.scrollTop = originalScrollTop;
                        }, 50);
                    }
                    console.log(`✅ Scroll range: 0 to ${maxScroll}px - all items accessible`);
                }
            } else {
                console.log(`ℹ️ All items fit in viewport (no scrolling needed)`);
            }
            
            // Force scrollbar to be more visible by temporarily using 'scroll'
            if (isScrollable) {
                searchResults.style.overflowY = 'scroll';
                setTimeout(() => {
                    searchResults.style.overflowY = 'auto';
                }, 100);
            }
        }, 150);
        
        searchResults.classList.add('show');
    }
    } // End of initializeSearch

    function addToCart(product) {
        console.log('Adding to cart:', product); // Debug log
        console.log('Cart before adding:', cart); // Debug log
        
        // Validate product object has required properties
        if (!product || (!product.id && !product.uniqueKey)) {
            console.error('Invalid product object:', product);
            showNotification('Error: Invalid product data', 'error');
            return;
        }
        
        // Use uniqueKey if available (for duplicates), otherwise use id
        const productId = product.uniqueKey || product.id;
        const productName = product.name || 'Unknown Product';
        const productPrice = parseFloat(product.price || 0);
        const productStock = parseInt(product.stock_quantity || product.stock || 0);
        const productUnit = product.unit || 'pcs';
        
        if (!productId) {
            showNotification('Error: Product ID is missing', 'error');
            return;
        }
        
        // Find existing item by ID or uniqueKey
        const existingItem = cart.find(item => item.id === productId || item.uniqueKey === productId);
        
        if (existingItem) {
            // Check if we can add more (compare with stock)
            const currentQuantity = existingItem.quantity || 0;
            if (currentQuantity < productStock) {
                existingItem.quantity++;
                const newTotal = existingItem.price * existingItem.quantity;
                existingItem.total = newTotal;
                existingItem.subtotal = newTotal; // Add subtotal for consistency
            } else {
                showNotification('Cannot add more items. Insufficient stock!', 'warning');
                return;
            }
        } else {
            // Add new item to cart
            if (productStock > 0) {
                const cartItem = {
                    id: product.id || productId, // Keep original ID for reference
                    name: productName,
                    price: productPrice,
                    quantity: 1,
                    total: productPrice,
                    subtotal: productPrice, // Add subtotal for consistency
                    stock: productStock,
                    unit: productUnit,
                    category: product.category || product.price_type || '',
                    price_type: product.price_type || product.category || '',
                    item_id: product.item_id || null,
                    inventory_product_id: product.inventory_product_id || null,
                    barcode_id: product.barcode_id || null,
                    cashier_key: product.cashier_key || product.uniqueKey || null,
                    uniqueKey: product.uniqueKey || product.cashier_key || null,
                    sku: product.sku || product.mpn || null,
                    source: product.source || null,
                    image: product.image || null
                };
                
                // If product has uniqueKey (duplicate), store it for lookup
                if (product.uniqueKey) {
                    cartItem.uniqueKey = product.uniqueKey;
                }
                
                cart.push(cartItem);
            } else {
                showNotification('Product is out of stock!', 'warning');
                return;
            }
        }
        
        console.log('Cart after adding:', cart); // Debug log
        updateCartDisplay();
        addToRecentProducts(product);
        showNotification(`${productName} added to cart!`, 'success');
    }

    // Make cart functions globally accessible
    window.removeFromCart = function(productIdOrBarcode) {
        // Handle both ID (number) and barcode (string) identifiers
        const item = cart.find(item => {
            if (typeof productIdOrBarcode === 'string') {
                return item.barcode === productIdOrBarcode;
            }
            return item.id === productIdOrBarcode;
        });
        
        if (item && item.barcode) {
            // If item has barcode, remove from backend session
            fetch('/api/cashier/scan/remove', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    barcode_value: item.barcode
                })
            }).catch(err => console.error('Error removing from backend:', err));
        }
        
        cart = cart.filter(item => {
            if (typeof productIdOrBarcode === 'string') {
                return item.barcode !== productIdOrBarcode;
            }
            return item.id !== productIdOrBarcode;
        });
        updateCartDisplay();
        showNotification('Item removed from cart', 'info');
    };

    window.updateQuantity = async function(productIdOrBarcode, newQuantity) {
        console.log('updateQuantity called:', productIdOrBarcode, newQuantity);
        // Handle both ID (number) and barcode (string) identifiers
        const item = cart.find(item => {
            if (typeof productIdOrBarcode === 'string') {
                return item.barcode === productIdOrBarcode;
            }
            return item.id === productIdOrBarcode;
        });
        
        if (item) {
            const product = products[item.id];
            const maxStock = product ? product.stock_quantity : (item.stock_quantity || item.stock || 999);
            
            if (newQuantity <= 0) {
                removeFromCart(productIdOrBarcode);
            } else if (newQuantity <= maxStock) {
                item.quantity = newQuantity;
                item.subtotal = item.price * item.quantity;
                console.log('Updated item:', item);
                updateCartDisplay();
                
                // If item has barcode, sync with backend
                if (item.barcode) {
                    try {
                        await fetch('/api/cashier/scan/quantity', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                            },
                            body: JSON.stringify({
                                barcode_value: item.barcode,
                                quantity: newQuantity
                            })
                        });
                    } catch (error) {
                        console.error('Error updating quantity on backend:', error);
                    }
                }
                
                showNotification(`Quantity updated to ${newQuantity}`, 'success');
            } else {
                showNotification(`Cannot add more items. Max stock: ${maxStock}`, 'warning');
            }
        } else {
            console.error('Item not found in cart:', productIdOrBarcode);
        }
    };

    function updateCartDisplay() {
        // Helper function to escape special characters for HTML attributes
        const escapeForHtml = (str) => {
            return str.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '&quot;');
        };
        
        if (cart.length === 0) {
            cartTableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">
                        <i class="bi bi-cart-x me-2"></i>No items in cart
                    </td>
                </tr>
            `;
        } else {
            cartTableBody.innerHTML = cart.map(item => {
                const product = products[item.id];
                const maxStock = product ? product.stock_quantity : (item.stock_quantity || item.stock || 999);
                const itemPrice = parseFloat(item.price) || 0;
                const itemTotal = item.subtotal || item.total || (itemPrice * (item.quantity || 1));
                // Use item ID or barcode for identification - properly escape for HTML attributes
                const itemIdentifier = item.barcode ? `'${escapeForHtml(item.barcode)}'` : item.id;
                const itemImage = item.image || (product && product.image) || null;
                return `
                <tr class="cart-item" data-item-id="${item.id}" data-barcode="${item.barcode || ''}">
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            ${cashierProductImageHtml(itemImage, item.name, 32)}
                            <div>
                                <div class="fw-semibold">${item.name || 'Unknown Item'}</div>
                                <small class="text-muted">₱${itemPrice.toFixed(2)} each</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-secondary" 
                                    onclick="updateQuantity(${itemIdentifier}, ${item.quantity - 1})"
                                    ${item.quantity <= 1 ? 'disabled' : ''}>
                                <i class="bi bi-dash"></i>
                            </button>
                            <input type="number" value="${item.quantity}" min="1" max="${maxStock}" 
                                   onchange="updateQuantity(${itemIdentifier}, parseInt(this.value) || 1)" 
                                   class="form-control form-control-sm text-center" 
                                   style="width: 60px; border-left: 0; border-right: 0;">
                            <button type="button" class="btn btn-outline-secondary" 
                                    onclick="updateQuantity(${itemIdentifier}, ${item.quantity + 1})"
                                    ${item.quantity >= maxStock ? 'disabled' : ''}>
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                        <small class="text-muted d-block mt-1">Max: ${maxStock}</small>
                    </td>
                    <td>₱${itemPrice.toFixed(2)}</td>
                    <td class="fw-bold text-primary">₱${parseFloat(itemTotal || 0).toFixed(2)}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${itemIdentifier})" title="Remove item">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                `;
            }).join('');
        }
        
        updateTotals();
        updateQuickAccess();
    }

    // Helper function to check if ID verification is complete
    function isIdVerificationComplete() {
        if (!selectedDiscountType) return false;
        
        if (idVerificationMethod === 'image') {
            return discountIdImage !== null && discountIdImage !== '';
        } else if (idVerificationMethod === 'manual') {
            const customerName = document.getElementById('discountCustomerName')?.value.trim() || '';
            const idNumber = document.getElementById('discountIdNumber')?.value.trim() || '';
            const issuingLgu = document.getElementById('discountIssuingLgu')?.value.trim() || '';
            return customerName !== '' && idNumber !== '' && issuingLgu !== '';
        }
        return false;
    }

    function updateTotals() {
        // Calculate original subtotal (before discount)
        const originalSubtotal = cart.reduce((sum, item) => {
            const itemPrice = parseFloat(item.price) || 0;
            const itemQty = parseInt(item.quantity) || 0;
            return sum + (itemPrice * itemQty);
        }, 0);
        
        // Check for Senior Citizen or PWD discount (20%)
        const seniorPwdDiscountPercent = 20;
        let seniorPwdDiscountApplied = false;
        let discountApplied = false; // Declare at function level for scope
        let discountAmount = 0; // Declare discount amount variable
        
        if (selectedDiscountType && (selectedDiscountType === 'senior' || selectedDiscountType === 'pwd')) {
            // Only apply discount if ID verification is complete
            if (isIdVerificationComplete()) {
                // Apply 20% discount to all items
                cart.forEach(item => {
                    const itemPrice = parseFloat(item.price) || 0;
                    const itemQty = parseInt(item.quantity) || 0;
                    const originalItemTotal = itemPrice * itemQty;
                    
                    // Apply 20% discount (overrides auto discount)
                    item.discount = seniorPwdDiscountPercent;
                    item.total = originalItemTotal * (1 - seniorPwdDiscountPercent / 100);
                    item.subtotal = item.total;
                    seniorPwdDiscountApplied = true;
                });
            } else {
                // Remove discount if verification is not complete
                cart.forEach(item => {
                    const itemPrice = parseFloat(item.price) || 0;
                    const itemQty = parseInt(item.quantity) || 0;
                    const originalItemTotal = itemPrice * itemQty;
                    
                    // Remove 20% discount if it was applied
                    if (item.discount === seniorPwdDiscountPercent) {
                        item.discount = 0;
                        item.total = originalItemTotal;
                        item.subtotal = originalItemTotal;
                    }
                });
            }
        } else {
            // Check if automatic discount should be applied (2500 pesos and above)
            const discountThreshold = 2500;
            const autoDiscountPercent = 3;
            
            // Remove any previously applied 3% auto discounts from items
            // The 3% discount will be applied to the Subtotal, not per item
            cart.forEach(item => {
                const itemPrice = parseFloat(item.price) || 0;
                const itemQty = parseInt(item.quantity) || 0;
                const originalItemTotal = itemPrice * itemQty;
                
                // Remove auto discount (3%) if it was applied, but keep manual discounts
                if (item.discount === autoDiscountPercent) {
                    item.discount = 0;
                    item.total = originalItemTotal;
                    item.subtotal = originalItemTotal;
                }
            });
            
            // Mark that 3% discount should be applied based on Subtotal
            if (originalSubtotal >= discountThreshold) {
                discountApplied = true;
            }
        }
        
        // Calculate subtotal after item-level discounts (for senior/PWD only)
        const subtotal = cart.reduce((sum, item) => {
            const itemPrice = parseFloat(item.price) || 0;
            const itemQty = parseInt(item.quantity) || 0;
            return sum + (item.subtotal || item.total || (itemPrice * itemQty));
        }, 0);
        
        // Calculate discount amount
        if (discountApplied && !seniorPwdDiscountApplied) {
            // 3% discount is based on the Subtotal (originalSubtotal)
            discountAmount = originalSubtotal * (3 / 100);
        } else if (seniorPwdDiscountApplied) {
            // Senior/PWD discount is already applied per item, calculate total discount
            discountAmount = originalSubtotal - subtotal;
        } else {
            discountAmount = 0;
        }
        
        // Calculate total
        // For 3% discount: total = Subtotal - 3% discount
        // For senior/PWD: total = subtotal (already discounted per item)
        const total = seniorPwdDiscountApplied ? subtotal : (originalSubtotal - discountAmount);
        
        // Format number with comma separators
        const formatNumber = (num) => {
            return parseFloat(num || 0).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        };
        
        // Update display
        document.getElementById('subtotal').textContent = `₱ ${formatNumber(originalSubtotal)}`;
        document.getElementById('total').textContent = `₱ ${formatNumber(total)}`;
        document.getElementById('itemCount').textContent = cart.reduce((sum, item) => sum + (parseInt(item.quantity) || 0), 0);
        
        // Show/hide discount line
        const discountLine = document.getElementById('discountLine');
        const discountAmountEl = document.getElementById('discountAmount');
        const discountLabel = discountLine.querySelector('span');
        
        if (discountAmount > 0) {
            // For senior/PWD discount, only show if verification is complete
            if (seniorPwdDiscountApplied) {
                if (isIdVerificationComplete()) {
                    discountLine.style.display = 'flex';
                    discountAmountEl.textContent = `-₱ ${formatNumber(discountAmount)}`;
                    discountLabel.textContent = 'Discount (20% Senior/PWD):';
                } else {
                    // Hide discount line if verification not complete
                    discountLine.style.display = 'none';
                }
            } else if (discountApplied) {
                discountLine.style.display = 'flex';
                discountAmountEl.textContent = `-₱ ${formatNumber(discountAmount)}`;
                discountLabel.textContent = 'Discount (3%):';
            } else {
                discountLine.style.display = 'flex';
                discountAmountEl.textContent = `-₱ ${formatNumber(discountAmount)}`;
                discountLabel.textContent = 'Discount:';
            }
        } else {
            discountLine.style.display = 'none';
        }
        
        // Note: Don't call updateCartDisplay() here to avoid infinite recursion
        // updateCartDisplay() already calls updateTotals() at the end
        // If cart display needs updating, it should be called separately
        
        // Update change calculation when totals change (auto-compute change)
        if (window.calculateChange) {
            window.calculateChange();
        }
        
        // Also trigger change calculation after a short delay to ensure DOM is updated
        setTimeout(() => {
            if (window.calculateChange) {
                window.calculateChange();
            }
        }, 100);
    }

    function addToRecentProducts(product) {
        // Remove if already exists
        recentProducts = recentProducts.filter(p => p.id !== product.id);
        // Add to beginning
        recentProducts.unshift(product);
        // Keep only last 5
        recentProducts = recentProducts.slice(0, 5);
        updateQuickAccess();
    }

    function initializeQuickAccess() {
        // Get popular products (most in stock or commonly used)
        const popularProducts = Object.values(products)
            .sort((a, b) => b.stock_quantity - a.stock_quantity)
            .slice(0, 5);
        
        popularProducts.forEach(product => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-outline-primary btn-sm';
            btn.innerHTML = `
                <i class="bi bi-${product.category === 'Medical Equipment' ? 'heart-pulse' : 
                  product.category === 'Medicines' ? 'capsule' : 'bandaid'} me-1"></i>
                ${product.name.substring(0, 15)}${product.name.length > 15 ? '...' : ''}
            `;
            btn.title = `${product.name} - ₱${product.price.toFixed(2)} - Stock: ${product.stock_quantity}`;
            btn.addEventListener('click', function() {
                addToCart(product);
            });
            quickAccess.appendChild(btn);
        });
    }

    function updateQuickAccess() {
        quickAccess.innerHTML = '';
        
        recentProducts.forEach(product => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-outline-primary btn-sm';
            btn.innerHTML = `
                <i class="bi bi-${product.category === 'Medical Equipment' ? 'heart-pulse' : 
                  product.category === 'Medicines' ? 'capsule' : 'bandaid'} me-1"></i>
                ${product.name.substring(0, 15)}${product.name.length > 15 ? '...' : ''}
            `;
            btn.title = `${product.name} - ₱${product.price.toFixed(2)} - Stock: ${product.stock_quantity}`;
            btn.addEventListener('click', function() {
                addToCart(product);
            });
            quickAccess.appendChild(btn);
        });
    }

    function showNotification(message, type = 'info') {
        // Remove existing notifications
        document.querySelectorAll('.notification').forEach(n => n.remove());
        
        const notification = document.createElement('div');
        notification.className = `notification alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }

    // Initialize quick access
    initializeQuickAccess();
    
    // Test function to verify cart is working
    window.testCart = function() {
        if (products && Object.keys(products).length > 0) {
            const firstProduct = products[Object.keys(products)[0]];
            console.log('Testing with product:', firstProduct);
            addToCart(firstProduct);
            console.log('Test: Added first product to cart');
        } else {
            console.error('No products found');
        }
    };
    
    // Debug function to check cart state
    window.debugCart = function() {
        console.log('Cart contents:', cart);
        console.log('Cart table body element:', cartTableBody);
        console.log('Products data:', products);
        console.log('Total products available:', Object.keys(products).length);
    };
    
    // Show initialization message
    setTimeout(() => {
        const productCount = Object.keys(products).length;
        if (productCount > 0) {
            showNotification('POS System ready! Search for products to add to cart.', 'info');
        } else {
            console.warn('No products available');
        }
    }, 1000);

    // POS Functions
    window.clearCart = function() {
        if (confirm('Are you sure you want to clear the cart?')) {
            cart = [];
            if (typeof clearCashierScanSession === 'function') {
                clearCashierScanSession();
            }
            updateCartDisplay();
            showNotification('Cart cleared', 'info');
        }
    };

    // Customer Search Functionality — preload once, filter instantly as you type
    function initializeCustomerSearch() {
        const customerSearchInput = document.getElementById('customerSearchInput');
        const customerSearchResults = document.getElementById('customerSearchResults');
        
        if (!customerSearchInput || !customerSearchResults) {
            return;
        }

        let cachedCustomers = null;
        let loadPromise = null;

        function ensureCustomersLoaded() {
            if (cachedCustomers) {
                return Promise.resolve(cachedCustomers);
            }
            if (loadPromise) {
                return loadPromise;
            }

            loadPromise = fetch('/api/customers/search?q=', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then((response) => {
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    return response.json();
                })
                .then((data) => {
                    cachedCustomers = Array.isArray(data.customers) ? data.customers : [];
                    return cachedCustomers;
                })
                .catch((error) => {
                    console.error('Error preloading customers:', error);
                    loadPromise = null;
                    throw error;
                });

            return loadPromise;
        }

        function filterCustomers(customers, query) {
            const q = query.trim().toLowerCase();
            if (!q) return [];

            return customers.filter((customer) => {
                const name = String(customer.registered_name || '').toLowerCase();
                const tin = String(customer.tin || '').toLowerCase();
                const address = String(customer.business_address || '').toLowerCase();
                return name.includes(q) || tin.includes(q) || address.includes(q);
            }).slice(0, 20);
        }

        // Prefetch when Customer Info is opened
        const customerInfoSection = document.getElementById('customerInfoSection');
        if (customerInfoSection) {
            customerInfoSection.addEventListener('shown.bs.collapse', () => {
                ensureCustomersLoaded().catch(() => {});
            });
        }
        ensureCustomersLoaded().catch(() => {});
        
        customerSearchInput.addEventListener('input', async function() {
            const query = this.value.trim();
            
            if (query.length < 1) {
                customerSearchResults.classList.remove('show');
                customerSearchResults.style.display = 'none';
                return;
            }

            try {
                const customers = await ensureCustomersLoaded();
                const matches = filterCustomers(customers, query);

                if (matches.length > 0) {
                    displayCustomerSearchResults(matches);
                } else {
                    customerSearchResults.innerHTML = '<div class="dropdown-item text-muted">No customers found</div>';
                    customerSearchResults.classList.add('show');
                    customerSearchResults.style.display = 'block';
                }
            } catch (error) {
                console.error('Error searching customers:', error);
                customerSearchResults.innerHTML = `<div class="dropdown-item text-danger">Error searching customers: ${error.message}</div>`;
                customerSearchResults.classList.add('show');
                customerSearchResults.style.display = 'block';
            }
        });
        
        function displayCustomerSearchResults(customers) {
            customerSearchResults.innerHTML = '';
            
            if (!customers || !Array.isArray(customers)) {
                console.error('Invalid customers data:', customers);
                customerSearchResults.innerHTML = '<div class="dropdown-item text-danger">Invalid response format</div>';
                customerSearchResults.classList.add('show');
                customerSearchResults.style.display = 'block';
                return;
            }
            
            customers.forEach(customer => {
                if (!customer || !customer.registered_name) {
                    console.warn('Invalid customer data:', customer);
                    return;
                }
                
                const itemElement = document.createElement('div');
                itemElement.className = 'dropdown-item d-flex align-items-center customer-search-item';
                itemElement.style.cursor = 'pointer';
                
                const escapeHtml = (text) => {
                    if (!text) return '';
                    const div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                };
                
                const registeredName = escapeHtml(customer.registered_name || '');
                const tin = escapeHtml(customer.tin || '');
                const businessAddress = customer.business_address || '';
                const addressDisplay = businessAddress.length > 30 ? businessAddress.substring(0, 30) + '...' : businessAddress;
                
                itemElement.innerHTML = `
                    <div class="me-3">
                        <i class="bi bi-person-circle text-primary"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">${registeredName}</div>
                        <small class="text-muted">
                            ${tin ? 'TIN: ' + escapeHtml(tin) : ''} 
                            ${businessAddress ? '• ' + escapeHtml(addressDisplay) : ''}
                        </small>
                    </div>
                `;
                
                itemElement.addEventListener('click', function() {
                    selectCustomer(customer);
                    customerSearchInput.value = customer.registered_name || '';
                    customerSearchResults.classList.remove('show');
                    customerSearchResults.style.display = 'none';
                });
                
                itemElement.addEventListener('mouseenter', function() {
                    this.classList.add('bg-primary', 'text-white');
                });
                
                itemElement.addEventListener('mouseleave', function() {
                    this.classList.remove('bg-primary', 'text-white');
                });
                
                customerSearchResults.appendChild(itemElement);
            });
            
            if (customers.length > 0) {
                customerSearchResults.classList.add('show');
                customerSearchResults.style.display = 'block';
            }
        }
        
        // Hide search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!customerSearchInput.contains(e.target) && !customerSearchResults.contains(e.target)) {
                customerSearchResults.classList.remove('show');
                customerSearchResults.style.display = 'none';
            }
        });

        // Enter selects the first match immediately
        customerSearchInput.addEventListener('keydown', function(e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            const first = customerSearchResults.querySelector('.customer-search-item');
            if (first) first.click();
        });
    }
    
    // Select Customer and populate fields
    function selectCustomer(customer) {
        // Update customer info section
        const customerInfoRegisteredName = document.getElementById('customerInfoRegisteredName');
        const customerInfoTin = document.getElementById('customerInfoTin');
        const customerInfoBusinessAddress = document.getElementById('customerInfoBusinessAddress');
        
        if (customerInfoRegisteredName) customerInfoRegisteredName.value = customer.registered_name || '';
        if (customerInfoTin) customerInfoTin.value = customer.tin || '';
        if (customerInfoBusinessAddress) customerInfoBusinessAddress.value = customer.business_address || '';
        
        // Also update discount fields if they exist (for backward compatibility)
        const discountCustomerName = document.getElementById('discountCustomerName');
        const discountIdNumber = document.getElementById('discountIdNumber');
        const discountIdType = document.getElementById('discountIdType');
        const discountIssuingLgu = document.getElementById('discountIssuingLgu');
        
        if (discountCustomerName) discountCustomerName.value = customer.registered_name || '';
        if (discountIdNumber) discountIdNumber.value = customer.tin || '';
        if (discountIdType) discountIdType.value = '';
        if (discountIssuingLgu) discountIssuingLgu.value = '';
        
        showNotification('Customer information loaded', 'success');
    }

    // Update Customer Info Section (for backward compatibility with discount fields)
    function updateCustomerInfoSection() {
        const customerName = document.getElementById('discountCustomerName')?.value || '';
        const idNumber = document.getElementById('discountIdNumber')?.value || '';
        const idType = document.getElementById('discountIdType')?.value || '';
        const issuingLgu = document.getElementById('discountIssuingLgu')?.value || '';
        
        const customerInfoName = document.getElementById('customerInfoName');
        const customerInfoIdNumber = document.getElementById('customerInfoIdNumber');
        const customerInfoIdType = document.getElementById('customerInfoIdType');
        const customerInfoIssuingLgu = document.getElementById('customerInfoIssuingLgu');
        
        if (customerInfoName) customerInfoName.value = customerName;
        if (customerInfoIdNumber) customerInfoIdNumber.value = idNumber;
        if (customerInfoIdType) customerInfoIdType.value = idType;
        if (customerInfoIssuingLgu) customerInfoIssuingLgu.value = issuingLgu;
    }

    // Clear Customer Info
    window.clearCustomerInfo = function() {
        if (confirm('Are you sure you want to clear customer information?')) {
            // Clear customer info section
            const customerInfoRegisteredName = document.getElementById('customerInfoRegisteredName');
            const customerInfoTin = document.getElementById('customerInfoTin');
            const customerInfoBusinessAddress = document.getElementById('customerInfoBusinessAddress');
            const customerSearchInput = document.getElementById('customerSearchInput');
            
            if (customerInfoRegisteredName) customerInfoRegisteredName.value = '';
            if (customerInfoTin) customerInfoTin.value = '';
            if (customerInfoBusinessAddress) customerInfoBusinessAddress.value = '';
            if (customerSearchInput) customerSearchInput.value = '';
            
            // Clear discount fields (for backward compatibility)
            const discountCustomerName = document.getElementById('discountCustomerName');
            const discountIdNumber = document.getElementById('discountIdNumber');
            const discountIdType = document.getElementById('discountIdType');
            const discountIssuingLgu = document.getElementById('discountIssuingLgu');
            
            if (discountCustomerName) discountCustomerName.value = '';
            if (discountIdNumber) discountIdNumber.value = '';
            if (discountIdType) discountIdType.value = '';
            if (discountIssuingLgu) discountIssuingLgu.value = '';
            
            showNotification('Customer information cleared', 'info');
        }
    };

    window.applyDiscount = function() {
        const discountPercent = prompt('Enter discount percentage (0-100):');
        if (discountPercent !== null && discountPercent !== '') {
            const percent = parseFloat(discountPercent);
            if (percent >= 0 && percent <= 100) {
                // Apply discount to all items
                cart.forEach(item => {
                    item.discount = percent;
                    item.total = item.price * item.quantity * (1 - percent / 100);
                });
                updateCartDisplay();
                showNotification(`Applied ${percent}% discount to all items`, 'success');
            } else {
                showNotification('Invalid discount percentage', 'error');
            }
        }
    };

    window.printReceipt = function() {
        if (cart.length === 0) {
            showNotification('Cart is empty', 'warning');
            return;
        }

        const originalSubtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const total = cart.reduce((sum, item) => sum + item.total, 0);
        const discountAmount = originalSubtotal - total;
        const hasDiscount = discountAmount > 0;
        const grandTotal = total;

        const receipt = `
REDEMP MEDICAL POS SYSTEM
==========================
Date: ${new Date().toLocaleDateString()}
Time: ${new Date().toLocaleTimeString()}

Items:
${cart.map(item => {
    const itemPrice = parseFloat(item.price) || 0;
    const itemQty = parseInt(item.quantity) || 0;
    const itemTotal = item.subtotal || item.total || (itemPrice * itemQty);
    const itemLine = `${item.name || 'Unknown Item'} x${itemQty} @ ₱${itemPrice.toFixed(2)} = ₱${(itemTotal || 0).toFixed(2)}`;
    if (item.discount && item.discount > 0) {
        const itemDiscountAmount = (itemPrice * itemQty) - (itemTotal || 0);
        return itemLine + `\n  Discount: ${item.discount}% (-₱${(itemDiscountAmount || 0).toFixed(2)})`;
    }
    return itemLine;
}).join('\n')}

Subtotal: ₱${(originalSubtotal || 0).toFixed(2)}
${hasDiscount ? `Discount: -₱${(discountAmount || 0).toFixed(2)}\n` : ''}Subtotal After Discount: ₱${(total || 0).toFixed(2)}
Total: ₱${(grandTotal || 0).toFixed(2)}

Thank you for your purchase!
        `;

        // Open print dialog
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Receipt</title>
                    <style>
                        body { font-family: monospace; padding: 20px; }
                        @media print { body { margin: 0; } }
                    </style>
                </head>
                <body>
                    <pre>${receipt}</pre>
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    };

    window.holdTransaction = function() {
        if (cart.length === 0) {
            showNotification('Cart is empty', 'warning');
            return;
        }

        const transactionData = {
            items: cart,
            timestamp: new Date().toISOString(),
            total: cart.reduce((sum, item) => sum + item.total, 0)
        };

        // Save to localStorage
        const heldTransactions = JSON.parse(localStorage.getItem('heldTransactions') || '[]');
        heldTransactions.push(transactionData);
        localStorage.setItem('heldTransactions', JSON.stringify(heldTransactions));

        showNotification('Transaction held successfully', 'success');
    };

    // Make processPayment globally accessible - will be defined later as async function
    // processPayment will be defined later - placeholder to prevent errors
    window.processPayment = function() {
        console.warn('processPayment not yet initialized');
    };

    // Payment Methods
    function initializePaymentMethods() {
        const paymentMethodBtns = document.querySelectorAll('.payment-method-btn');
        const amountTendered = document.getElementById('amountTendered');
        const amountTenderedContainer = document.getElementById('amountTenderedContainer');
        const exactAmountBtn = document.getElementById('exactAmountBtn');
        const changeDisplay = document.getElementById('changeDisplay');
        const changeAmount = document.getElementById('changeAmount');
        const ewalletDetails = document.getElementById('ewalletDetails');
        const checkDetails = document.getElementById('checkDetails');

        // Payment method button click handlers
        paymentMethodBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all buttons
                paymentMethodBtns.forEach(b => {
                    b.classList.remove('active');
                    b.disabled = false;
                });
                // Add active class to clicked button
                this.classList.add('active');
                selectedPaymentMethod = this.dataset.method;
                
                // Show/hide relevant fields based on payment method
                if (selectedPaymentMethod === 'cash') {
                    amountTenderedContainer.style.display = 'block';
                    exactAmountBtn.style.display = 'block';
                    ewalletDetails.style.display = 'none';
                    checkDetails.style.display = 'none';
                    changeDisplay.style.display = 'none';
                } else if (selectedPaymentMethod === 'e_wallet') {
                    amountTenderedContainer.style.display = 'none';
                    exactAmountBtn.style.display = 'none';
                    ewalletDetails.style.display = 'block';
                    checkDetails.style.display = 'none';
                    changeDisplay.style.display = 'none';
                } else if (selectedPaymentMethod === 'check') {
                    amountTenderedContainer.style.display = 'none';
                    exactAmountBtn.style.display = 'none';
                    ewalletDetails.style.display = 'none';
                    checkDetails.style.display = 'block';
                    changeDisplay.style.display = 'none';
                }
                
                // Recalculate change if cash
                if (selectedPaymentMethod === 'cash' && window.calculateChange) {
                    window.calculateChange();
                }
            });
        });

        // Amount tendered is visible by default for cash
        if (amountTenderedContainer) {
            amountTenderedContainer.style.display = 'block';
        }
        if (exactAmountBtn) {
            exactAmountBtn.style.display = 'block';
        }

        // Make calculateChange globally accessible
        window.calculateChange = function() {
            if (!amountTendered || !changeDisplay || !changeAmount) return;
            
            // Get the Total from Transaction Summary (this includes discounts)
            const totalElement = document.getElementById('total');
            let grandTotal = 0;
            
            if (totalElement) {
                // Extract numeric value from the displayed total (e.g., "₱ 2,850.00" -> 2850.00)
                const totalText = totalElement.textContent.replace(/[₱,\s]/g, '');
                grandTotal = parseFloat(totalText) || 0;
            } else {
                // Fallback: calculate from cart if element not found
                const subtotal = cart.reduce((sum, item) => {
                    const itemPrice = parseFloat(item.price) || 0;
                    const itemQty = parseInt(item.quantity) || 0;
                    const itemTotal = item.subtotal || item.total || (itemPrice * itemQty);
                    return sum + (itemTotal || 0);
                }, 0);
                grandTotal = subtotal || 0;
            }
            
            const tendered = parseFloat(amountTendered.value) || 0;
            
            if (tendered >= grandTotal && tendered > 0) {
                const change = tendered - grandTotal;
                changeAmount.textContent = (change || 0).toFixed(2);
                changeDisplay.style.display = 'block';
            } else {
                changeDisplay.style.display = 'none';
            }
        };

        if (exactAmountBtn && amountTendered) {
            exactAmountBtn.addEventListener('click', function() {
                // Get the Total from Transaction Summary (this includes discounts)
                const totalElement = document.getElementById('total');
                let grandTotal = 0;
                
                if (totalElement) {
                    // Extract numeric value from the displayed total (e.g., "₱ 2,850.00" -> 2850.00)
                    const totalText = totalElement.textContent.replace(/[₱,\s]/g, '');
                    grandTotal = parseFloat(totalText) || 0;
                } else {
                    // Fallback: calculate from cart if element not found
                    const subtotal = cart.reduce((sum, item) => {
                        const itemPrice = parseFloat(item.price) || 0;
                        const itemQty = parseInt(item.quantity) || 0;
                        const itemTotal = item.subtotal || item.total || (itemPrice * itemQty);
                        return sum + (itemTotal || 0);
                    }, 0);
                    grandTotal = subtotal || 0;
                }
                
                amountTendered.value = (grandTotal || 0).toFixed(2);
                window.calculateChange();
                amountTendered.focus();
            });
        }

        if (amountTendered) {
            // Auto-compute change on input
            amountTendered.addEventListener('input', function() {
                window.calculateChange();
            });
            amountTendered.addEventListener('keyup', function() {
                window.calculateChange();
            });
            amountTendered.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (cart.length > 0) {
                        processPayment();
                    }
                }
            });
        }
        
        // Make calculateChange accessible globally for updateTotals
        window.calculateChange = calculateChange;
    }

    // Function to remove discount
    async function removeDiscount() {
        selectedDiscountType = null;
        discountIdImage = null;
        capturedSeniorPwdDiscountId = null;
        idManuallyRemoved = true; // Mark that user manually removed the ID
        
        // Remove active class from all discount buttons
        document.querySelectorAll('.discount-btn').forEach(b => b.classList.remove('active'));
        
        // Hide ID display section
        const idDisplaySection = document.getElementById('idDisplaySection');
        if (idDisplaySection) {
            idDisplaySection.style.display = 'none';
        }
        
        // Clear ID image display - completely reset the image element
        const idImage = document.getElementById('idImage');
        const idPlaceholder = document.getElementById('idPlaceholder');
        if (idImage) {
            // Remove all event handlers
            idImage.onerror = null;
            idImage.onload = null;
            // Clear the src to force browser to release the image
            idImage.src = '';
            idImage.removeAttribute('src');
            // Reset display
            idImage.style.display = 'none';
            // Clear any cached image data
            discountIdImage = null;
        }
        if (idPlaceholder) {
            idPlaceholder.style.display = 'block';
            idPlaceholder.innerHTML = '<div class="text-center text-muted"><i class="bi bi-image" style="font-size: 3rem;"></i><p class="mt-2 mb-0">No ID captured</p></div>';
        }
        
        // Clear manual input fields
        const customerName = document.getElementById('discountCustomerName');
        const idNumber = document.getElementById('discountIdNumber');
        const idType = document.getElementById('discountIdType');
        const issuingLgu = document.getElementById('discountIssuingLgu');
        
        if (customerName) customerName.value = '';
        if (idNumber) idNumber.value = '';
        if (idType) idType.value = '';
        if (issuingLgu) issuingLgu.value = '';
        
        // Hide remove button
        const removeDiscountBtn = document.getElementById('removeDiscountBtn');
        if (removeDiscountBtn) {
            removeDiscountBtn.style.display = 'none';
        }
        
        // Clear discount from all cart items (remove 20% senior/PWD discount)
        cart.forEach(item => {
            const itemPrice = parseFloat(item.price) || 0;
            const itemQty = parseInt(item.quantity) || 0;
            const originalItemTotal = itemPrice * itemQty;
            
            // If item has 20% discount (senior/PWD), remove it
            if (item.discount === 20) {
                item.discount = 0;
                item.total = originalItemTotal;
                item.subtotal = originalItemTotal;
            }
        });
        
        // Clear ID from backend cache
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                await fetch('/api/cashier/id-clear', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.content,
                    },
                });
            }
        } catch (error) {
            console.error('Error clearing ID from cache:', error);
        }
        
        // Recalculate totals without discount
        updateTotals();
        
        showNotification('Discount removed', 'info');
    }

    // Initialize Discount Buttons
    function initializeDiscountButtons() {
        const discountBtns = document.querySelectorAll('.discount-btn');
        const idDisplaySection = document.getElementById('idDisplaySection');
        const idImage = document.getElementById('idImage');
        const idPlaceholder = document.getElementById('idPlaceholder');
        const discountTypeLabel = document.getElementById('discountTypeLabel');
        const idImageSection = document.getElementById('idImageSection');
        const idManualSection = document.getElementById('idManualSection');
        const idMethodImage = document.getElementById('idMethodImage');
        const idMethodManual = document.getElementById('idMethodManual');
        const removeDiscountBtn = document.getElementById('removeDiscountBtn');

        // Handle verification method toggle
        if (idMethodImage && idMethodManual) {
            idMethodImage.addEventListener('change', function() {
                if (this.checked) {
                    idVerificationMethod = 'image';
                    idImageSection.style.display = 'block';
                    idManualSection.style.display = 'none';
                }
            });
            idMethodManual.addEventListener('change', function() {
                if (this.checked) {
                    idVerificationMethod = 'manual';
                    idImageSection.style.display = 'none';
                    idManualSection.style.display = 'block';
                }
            });
        }

        // Handle remove discount button
        if (removeDiscountBtn) {
            removeDiscountBtn.addEventListener('click', function() {
                removeDiscount();
            });
        }

        discountBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const discountType = this.dataset.discount;
                
                // Toggle selection
                if (selectedDiscountType === discountType) {
                    // Deselect - use removeDiscount function
                    removeDiscount();
                } else {
                    // Select new discount
                    selectedDiscountType = discountType;
                    discountBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    idDisplaySection.style.display = 'block';
                    
                    // Show remove button
                    if (removeDiscountBtn) {
                        removeDiscountBtn.style.display = 'block';
                    }
                    
                    // Set default verification method to image
                    if (idMethodImage) {
                        idMethodImage.checked = true;
                        idVerificationMethod = 'image';
                        idImageSection.style.display = 'block';
                        idManualSection.style.display = 'none';
                    }
                    
                    // Auto-set ID type based on discount type
                    const idTypeInput = document.getElementById('discountIdType');
                    if (idTypeInput) {
                        if (discountType === 'senior') {
                            idTypeInput.value = 'OSCA';
                        } else if (discountType === 'pwd') {
                            idTypeInput.value = 'PWD';
                        }
                    }
                    
                    // If ID already captured, show it
                    if (discountIdImage) {
                        idImage.src = discountIdImage;
                        idImage.style.display = 'block';
                        idPlaceholder.style.display = 'none';
                    } else {
                        idImage.style.display = 'none';
                        idPlaceholder.style.display = 'block';
                    }
                    
                    // Apply discount - update cart display (which will update totals)
                    updateCartDisplay();
                }
            });
        });
        
        // Add event listeners to manual entry fields to update totals when filled
        const customerNameInput = document.getElementById('discountCustomerName');
        const idNumberInput = document.getElementById('discountIdNumber');
        const issuingLguInput = document.getElementById('discountIssuingLgu');
        
        if (customerNameInput) {
            customerNameInput.addEventListener('input', function() {
                if (selectedDiscountType) {
                    // Update cart display (which will update totals) when discount fields change
                    updateCartDisplay();
                }
            });
        }
        
        if (idNumberInput) {
            idNumberInput.addEventListener('input', function() {
                if (selectedDiscountType) {
                    // Update cart display (which will update totals) when discount fields change
                    updateCartDisplay();
                }
            });
        }
        
        if (issuingLguInput) {
            issuingLguInput.addEventListener('input', function() {
                if (selectedDiscountType) {
                    // Update cart display (which will update totals) when discount fields change
                    updateCartDisplay();
                }
            });
        }
    }

    // Function to receive ID from scanner app
    window.receiveIdFromScanner = function(imageData, discountType, manualData = null, discountId = null) {
        console.log('receiveIdFromScanner called:', { 
            discountType, 
            imageDataLength: imageData?.length,
            imageDataPreview: imageData?.substring(0, 50),
            manualData: manualData,
            discountId: discountId
        });
        
        if (!imageData) {
            console.error('No image data provided to receiveIdFromScanner');
            showNotification('No image data received from scanner', 'error');
            return;
        }
        
        // Reset the manually removed flag when new ID is received
        idManuallyRemoved = false;
        capturedSeniorPwdDiscountId = discountId || null;
        
        // Validate image data format
        if (!imageData.startsWith('data:image/')) {
            console.error('Invalid image data format:', imageData.substring(0, 50));
            showNotification('Invalid image data format received', 'error');
            return;
        }
        
        // Validate base64 data is not empty
        const base64Match = imageData.match(/data:image\/[^;]+;base64,(.+)/);
        if (!base64Match || !base64Match[1] || base64Match[1].length < 100) {
            console.error('Invalid or empty base64 data');
            showNotification('Invalid image data: base64 data is empty or too short', 'error');
            return;
        }
        
        console.log('Image data validated, base64 length:', base64Match[1].length);
        discountIdImage = imageData;
        const idImage = document.getElementById('idImage');
        const idPlaceholder = document.getElementById('idPlaceholder');
        const idDisplaySection = document.getElementById('idDisplaySection');
        const discountTypeLabel = document.getElementById('discountTypeLabel');
        
        console.log('DOM elements check:', {
            idImage: !!idImage,
            idPlaceholder: !!idPlaceholder,
            idDisplaySection: !!idDisplaySection,
            discountTypeLabel: !!discountTypeLabel
        });
        
        if (!idImage || !idPlaceholder) {
            console.error('ID image elements not found in DOM');
            showNotification('ID display elements not found. Please refresh the page.', 'error');
            return;
        }
        
        // Completely reset the image element before loading new image
        idImage.onerror = null;
        idImage.onload = null;
        idImage.src = '';
        idImage.removeAttribute('src');
        idImage.style.display = 'none';
        
        // Show loading state
        if (idPlaceholder) {
            idPlaceholder.style.display = 'block';
            idPlaceholder.innerHTML = '<div class="spinner-border spinner-border-sm text-primary" role="status"></div><p class="mt-2 mb-0">Loading ID image...</p>';
        }
        
        // Validate image data before attempting to load
        if (!discountIdImage || typeof discountIdImage !== 'string') {
            console.error('Invalid image data:', typeof discountIdImage);
        idImage.style.display = 'none';
            if (idPlaceholder) {
                idPlaceholder.style.display = 'block';
                idPlaceholder.innerHTML = '<i class="bi bi-exclamation-triangle text-danger" style="font-size: 3rem;"></i><p class="mt-2 mb-0 text-danger">Invalid image data</p>';
            }
            return;
        }
        
        // Check if it's a valid data URI
        if (!discountIdImage.startsWith('data:image/')) {
            console.error('Invalid image format - not a data URI:', discountIdImage.substring(0, 50));
            idImage.style.display = 'none';
            if (idPlaceholder) {
                idPlaceholder.style.display = 'block';
                idPlaceholder.innerHTML = '<i class="bi bi-exclamation-triangle text-danger" style="font-size: 3rem;"></i><p class="mt-2 mb-0 text-danger">Invalid image format</p>';
            }
            return;
        }
        
        // Create a new image object to test loading first
        const testImg = new Image();
        let loadTimeout;
        
        testImg.onerror = function() {
            console.warn('Failed to load ID image - test image onerror triggered');
            clearTimeout(loadTimeout);
            idImage.style.display = 'none';
            if (idPlaceholder) {
                idPlaceholder.style.display = 'block';
                idPlaceholder.innerHTML = '<i class="bi bi-exclamation-triangle text-danger" style="font-size: 3rem;"></i><p class="mt-2 mb-0 text-danger">Failed to load ID image</p>';
            }
        };
        
        testImg.onload = function() {
            console.log('Test image loaded successfully, dimensions:', this.naturalWidth, 'x', this.naturalHeight);
            clearTimeout(loadTimeout);
            
            // Validate image dimensions
            if (this.naturalWidth === 0 || this.naturalHeight === 0) {
                console.error('Invalid image dimensions:', this.naturalWidth, 'x', this.naturalHeight);
                idImage.style.display = 'none';
                if (idPlaceholder) {
                    idPlaceholder.style.display = 'block';
                    idPlaceholder.innerHTML = '<i class="bi bi-exclamation-triangle text-danger" style="font-size: 3rem;"></i><p class="mt-2 mb-0 text-danger">Invalid image dimensions</p>';
                }
                return;
            }
            
            // Now set the actual image element
            // Remove any existing error handlers to avoid conflicts
            idImage.onerror = null;
            idImage.onload = null;
            
            // Set up load handler first
            idImage.onload = function() {
                console.log('ID image displayed successfully');
                this.style.display = 'block';
                if (idPlaceholder) {
                    idPlaceholder.style.display = 'none';
                }
            };
            
            // Set up error handler
            idImage.onerror = function(e) {
                console.warn('Failed to load ID image in img element');
                this.style.display = 'none';
                if (idPlaceholder) {
                    idPlaceholder.style.display = 'block';
                    idPlaceholder.innerHTML = '<i class="bi bi-exclamation-triangle text-danger" style="font-size: 3rem;"></i><p class="mt-2 mb-0 text-danger">Failed to display image. Please try capturing again.</p>';
                }
            };
            
            // Use the original image data directly - since test image loaded successfully, this should work
            // Set src directly - the test image already validated it works
            idImage.src = discountIdImage;
        };
        
        // Set timeout for test image
        loadTimeout = setTimeout(() => {
            console.warn('Image load timeout - image may be too large or corrupted');
            clearTimeout(loadTimeout);
            idImage.style.display = 'none';
            if (idPlaceholder) {
                idPlaceholder.style.display = 'block';
                idPlaceholder.innerHTML = '<i class="bi bi-exclamation-triangle text-danger" style="font-size: 3rem;"></i><p class="mt-2 mb-0 text-danger">Image load timeout. The image may be too large.</p>';
            }
        }, 15000);
        
        // Start loading test image
        console.log('Loading test image, data URI length:', discountIdImage.length);
        // Ensure we're using a clean image object
        testImg.onerror = function() {
            console.warn('Failed to load ID image - test image onerror triggered');
            clearTimeout(loadTimeout);
            idImage.style.display = 'none';
            if (idPlaceholder) {
                idPlaceholder.style.display = 'block';
                idPlaceholder.innerHTML = '<i class="bi bi-exclamation-triangle text-danger" style="font-size: 3rem;"></i><p class="mt-2 mb-0 text-danger">Failed to load ID image. Please try capturing again.</p>';
            }
        };
        testImg.src = discountIdImage;
        
        // Switch to image method if manual is selected
        const idMethodImage = document.getElementById('idMethodImage');
        const idImageSection = document.getElementById('idImageSection');
        const idManualSection = document.getElementById('idManualSection');
        
        if (idMethodImage) {
            idMethodImage.checked = true;
            idVerificationMethod = 'image';
            if (idImageSection) {
                idImageSection.style.display = 'block';
                console.log('ID image section displayed');
            }
            if (idManualSection) idManualSection.style.display = 'none';
        }
        
        // Ensure ID display section is visible
        if (idDisplaySection) {
            idDisplaySection.style.display = 'block';
            console.log('ID display section made visible');
        }
        
        // Auto-select discount type if not already selected
        if (discountType) {
            // Map discount type from backend format
            let mappedDiscountType = discountType;
            if (discountType === 'senior_citizen') {
                mappedDiscountType = 'senior';
            }
            
            if (!selectedDiscountType) {
                selectedDiscountType = mappedDiscountType;
                const btn = document.querySelector(`[data-discount="${mappedDiscountType}"]`);
                if (btn) {
                    btn.classList.add('active');
                    // Show ID display section
                    if (idDisplaySection) {
                        idDisplaySection.style.display = 'block';
                    }
                }
            } else if (selectedDiscountType !== mappedDiscountType) {
                // If different discount type, update it
                selectedDiscountType = mappedDiscountType;
                // Remove active from all buttons
                document.querySelectorAll('.discount-btn').forEach(b => b.classList.remove('active'));
                // Add active to correct button
                const btn = document.querySelector(`[data-discount="${mappedDiscountType}"]`);
                if (btn) {
                    btn.classList.add('active');
                }
            }
            
            // Update discount type label
            if (discountTypeLabel) {
                if (mappedDiscountType === 'senior') {
                    discountTypeLabel.textContent = 'Senior Citizen ID';
                } else if (mappedDiscountType === 'pwd') {
                    discountTypeLabel.textContent = 'PWD ID';
                }
            }
        }
        
        // Populate manual entry fields if manual data is provided
        if (manualData && (manualData.customerName || manualData.idNumber || manualData.issuingLgu)) {
            const customerNameInput = document.getElementById('discountCustomerName');
            const idNumberInput = document.getElementById('discountIdNumber');
            const issuingLguInput = document.getElementById('discountIssuingLgu');
            const idTypeInput = document.getElementById('discountIdType');
            
            if (customerNameInput && manualData.customerName) {
                customerNameInput.value = manualData.customerName;
            }
            if (idNumberInput && manualData.idNumber) {
                idNumberInput.value = manualData.idNumber;
            }
            if (issuingLguInput && manualData.issuingLgu) {
                issuingLguInput.value = manualData.issuingLgu;
            }
            if (idTypeInput && discountType) {
                const mappedType = discountType === 'senior_citizen' ? 'senior' : discountType;
                idTypeInput.value = mappedType === 'senior' ? 'OSCA' : 'PWD';
            }
            
            // Also populate customer info section
            updateCustomerInfoSection();
            
            console.log('Manual data populated:', manualData);
        }
        
        // Update cart display to apply discount (this will also update totals)
        updateCartDisplay();
        showNotification('ID captured successfully from scanner app', 'success');
        
        console.log('ID displayed successfully');
    };

    // Poll for ID capture from scanner app
    function initializeIdPolling() {
        console.log('Initializing ID polling...');
        let lastIdTimestamp = null;
        let lastDiscountId = null;
        let pollCount = 0;
        
        const pollInterval = setInterval(async () => {
            pollCount++;
            try {
                const response = await fetch('/api/cashier/id-latest', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Cache-Control': 'no-cache'
                    }
                });
                
                if (!response.ok) {
                    // Only log errors that aren't 400 (bad request) or 404 (not found) - these are expected
                    if (response.status !== 400 && response.status !== 404) {
                    if (pollCount % 30 === 0) { // Log every 60 seconds (30 * 2s)
                        console.warn('ID polling: Response not OK', response.status);
                        }
                    }
                    return; // Silently fail, will retry
                }
                
                const data = await response.json();
                
                if (data.success && data.data && data.data.id_image) {
                    const capturedAt = data.data.captured_at;
                    const discountId = data.data.discount_id;
                    
                    // Only process if it's a new capture (check both timestamp and ID)
                    if (capturedAt !== lastIdTimestamp || discountId !== lastDiscountId) {
                        console.log('New ID capture detected:', { 
                            capturedAt, 
                            discountId, 
                            discountType: data.data.discount_type,
                            imageLength: data.data.id_image?.length 
                        });
                        
                        // Reset the manually removed flag when a new ID is captured
                        // This allows new IDs to display even after removing a previous one
                        idManuallyRemoved = false;
                        
                        lastIdTimestamp = capturedAt;
                        lastDiscountId = discountId;
                        
                        // Format image data as base64 data URI
                        let imageData = data.data.id_image;
                        if (!imageData || typeof imageData !== 'string') {
                            console.error('Invalid image data received:', typeof imageData);
                            return;
                        }
                        
                        // Remove any existing data URI prefix and add our own
                        if (imageData.startsWith('data:')) {
                            // Extract just the base64 part
                            const parts = imageData.split(',');
                            if (parts.length > 1) {
                                imageData = parts[1];
                            }
                        }
                        
                        // Add data URI prefix
                        imageData = 'data:image/jpeg;base64,' + imageData;
                        
                        // Map discount type from backend format to frontend format
                        let discountType = data.data.discount_type;
                        if (discountType === 'senior_citizen') {
                            discountType = 'senior';
                        } else if (discountType === 'pwd') {
                            discountType = 'pwd';
                        }
                        
                        console.log('Calling receiveIdFromScanner with:', { 
                            discountType, 
                            imageDataLength: imageData.length,
                            capturedAt, 
                            discountId 
                        });
                        
                        // Get manual data if available
                        const manualData = {
                            customerName: data.data.customer_name || null,
                            idNumber: data.data.id_number || null,
                            issuingLgu: data.data.issuing_lgu || null,
                        };
                        
                        // Ensure the function exists before calling
                        if (typeof window.receiveIdFromScanner === 'function') {
                            window.receiveIdFromScanner(imageData, discountType, manualData, discountId);
                        } else {
                            console.error('receiveIdFromScanner function not found!');
                        }
                    }
                } else if (data.success === false && lastIdTimestamp !== null) {
                    // ID was cleared, reset
                    console.log('ID cleared from cache');
                    lastIdTimestamp = null;
                    lastDiscountId = null;
                }
            } catch (error) {
                // Log errors but continue polling
                if (pollCount % 60 === 0) { // Log every 30 seconds (60 * 500ms)
                    console.error('Error polling for ID:', error);
                }
            }
        }, 500); // Poll every 500ms for faster display
        
        console.log('ID polling started, interval ID:', pollInterval);
    }

    // Shift Management
    function initializeShiftManagement() {
        const endShiftBtn = document.getElementById('endShiftBtn');
        const confirmShiftBtn = document.getElementById('confirmShiftBtn');
        const confirmEndShiftBtn = document.getElementById('confirmEndShiftBtn');
        const shiftModal = document.getElementById('shiftModal');

        console.log('Initializing shift management...');
        console.log('confirmShiftBtn:', confirmShiftBtn);

        if (endShiftBtn) {
            endShiftBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('End shift button clicked');
                try {
                    if (typeof window.showEndShiftModal === 'function') {
                        window.showEndShiftModal();
                    } else {
                        console.error('showEndShiftModal function not found!');
                        showNotification('End shift function not available. Please refresh the page.', 'error');
                    }
                } catch (error) {
                    console.error('Error showing end shift modal:', error);
                    showNotification('Error opening end shift modal. Please try again.', 'error');
                }
            });
            console.log('End shift button event listener attached');
        } else {
            console.error('endShiftBtn not found!');
        }
        if (confirmShiftBtn) {
            confirmShiftBtn.addEventListener('click', startShift);
            console.log('Start shift event listener attached');
        } else {
            console.error('confirmShiftBtn not found!');
        }
        // Set up confirm end shift button handler
        // Use event delegation on the modal to ensure it works even if button is recreated
        const endShiftModal = document.getElementById('endShiftModal');
        if (endShiftModal) {
            endShiftModal.addEventListener('click', function(e) {
                // Check if the clicked element is the confirm button or a child of it
                const confirmBtn = e.target.closest('#confirmEndShiftBtn');
                if (confirmBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('✅ Confirm end shift button clicked (via delegation)');
                    
                    // Validate closing cash before proceeding
                    const closingCashInput = document.getElementById('closingCash');
                    if (!closingCashInput) {
                        console.error('❌ closingCash input not found');
                        showNotification('Error: Closing cash input not found', 'error');
                        return;
                    }
                    
                    const closingCash = parseFloat(closingCashInput.value);
                    if (isNaN(closingCash) || closingCash < 0) {
                        console.error('❌ Invalid closing cash:', closingCash);
                        showNotification('Please enter a valid closing cash amount', 'error');
                        closingCashInput.focus();
                        return;
                    }
                    
                    if (typeof window.endShift === 'function') {
                        console.log('✅ Calling window.endShift()');
                        window.endShift();
                    } else {
                        console.error('❌ endShift function not found!');
                        showNotification('Error: End shift function not available. Please refresh the page.', 'error');
                    }
                }
            });
            console.log('✅ End shift modal event delegation set up');
        }
        
        // Also set up direct event listener as backup
        if (confirmEndShiftBtn) {
            confirmEndShiftBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('✅ Confirm end shift button clicked (direct listener)');
                
                // Validate closing cash before proceeding
                const closingCashInput = document.getElementById('closingCash');
                if (!closingCashInput) {
                    console.error('❌ closingCash input not found');
                    showNotification('Error: Closing cash input not found', 'error');
                    return;
                }
                
                const closingCash = parseFloat(closingCashInput.value);
                if (isNaN(closingCash) || closingCash < 0) {
                    console.error('❌ Invalid closing cash:', closingCash);
                    showNotification('Please enter a valid closing cash amount', 'error');
                    closingCashInput.focus();
                    return;
                }
                
                if (typeof window.endShift === 'function') {
                    console.log('✅ Calling window.endShift()');
                    window.endShift();
                } else {
                    console.error('❌ endShift function not found!');
                    showNotification('Error: End shift function not available. Please refresh the page.', 'error');
                }
            });
            console.log('✅ Confirm end shift button direct event listener attached');
        } else {
            console.warn('⚠️ confirmEndShiftBtn not found during initialization (will use delegation)');
        }

        // Clean up backdrop when modal is closed and reset Start Shift button
        if (shiftModal) {
            shiftModal.addEventListener('show.bs.modal', function() {
                // Reset Start Shift button in case it was left disabled from a previous attempt
                const btn = document.getElementById('confirmShiftBtn');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = 'Start Shift';
                }
            });
            shiftModal.addEventListener('hidden.bs.modal', function() {
                // Remove manually created backdrop if it exists
                const customBackdrop = document.getElementById('shiftModalBackdrop');
                if (customBackdrop) {
                    customBackdrop.remove();
                }
                // Remove any remaining Bootstrap backdrops
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => backdrop.remove());
                // Clean up body classes
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            });
        }
    }

    // Functions are now defined globally before DOMContentLoaded

    // Clear active shift (for debugging/cleanup)
    window.clearActiveShift = async function(skipConfirm = false) {
        if (!skipConfirm && !confirm('This will close any active shift. Are you sure?')) {
            return false;
        }

        try {
            const response = await fetch('/shifts/clear-active', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                localStorage.removeItem('activeShift');
                cachedStartTimeDisplay = null; // Clear cache when shift ends
                clearLockedStartTime(); // Clear lock when shift ends
                shiftData = {
                    isActive: false,
                    startTime: null,
                    openingCash: 0,
                    totalSales: 0,
                    transactions: [],
                    paymentMethods: { cash: 0 }
                };
                updateShiftStatus();
                showNotification('Active shift cleared. You can now start a new shift.', 'success');
                if (!skipConfirm) {
                    setTimeout(() => showStartShiftModal(), 500);
                }
                return true;
            } else {
                showNotification(data.message || 'No active shift to clear', 'info');
                return false;
            }
        } catch (error) {
            console.error('Error clearing shift:', error);
            showNotification('Error clearing shift', 'error');
            return false;
        }
    };

    // Make showEndShiftModal globally accessible
    window.showEndShiftModal = function() {
        console.log('showEndShiftModal called');
        
        // Check if Bootstrap is available
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap is not loaded yet!');
            showNotification('Please wait for the page to fully load', 'error');
            // Retry after a short delay
            setTimeout(() => {
                if (typeof bootstrap !== 'undefined') {
                    window.showEndShiftModal();
                } else {
                    showNotification('Bootstrap failed to load. Please refresh the page.', 'error');
                }
            }, 1000);
            return;
        }
        
        if (!shiftData || !shiftData.isActive) {
            showNotification('No active shift to end', 'error');
            return;
        }

        const modalElement = document.getElementById('endShiftModal');
        if (!modalElement) {
            console.error('End shift modal not found!');
            showNotification('End shift modal not found. Please refresh the page.', 'error');
            return;
        }

        // Set up proper focus management for accessibility (only once)
        if (!modalElement.hasAttribute('data-focus-managed')) {
            modalElement.setAttribute('data-focus-managed', 'true');
            
            modalElement.addEventListener('show.bs.modal', function() {
                // Ensure aria-hidden is false when showing
                modalElement.removeAttribute('aria-hidden');
                modalElement.setAttribute('aria-modal', 'true');
            });

            modalElement.addEventListener('shown.bs.modal', function() {
                // Focus the first input after modal is fully shown
                const firstInput = modalElement.querySelector('#closingCash');
                if (firstInput) {
                    setTimeout(() => firstInput.focus(), 100);
                }
            });

            modalElement.addEventListener('hide.bs.modal', function(e) {
                // Remove focus from any element inside modal before hiding
                // This prevents the accessibility warning about aria-hidden on focused elements
                const activeElement = document.activeElement;
                if (modalElement.contains(activeElement) && activeElement !== document.body && activeElement !== document.documentElement) {
                    // Blur the active element to remove focus
                    if (typeof activeElement.blur === 'function') {
                        activeElement.blur();
                    }
                    // Move focus to a safe element outside the modal
                    // Use requestAnimationFrame to ensure blur happens before aria-hidden is set
                    requestAnimationFrame(() => {
                        const safeElement = document.querySelector('body') || document.documentElement;
                        if (safeElement && typeof safeElement.focus === 'function') {
                            safeElement.focus();
                        }
                    });
                }
            });

            modalElement.addEventListener('hidden.bs.modal', function() {
                // Bootstrap handles aria-hidden automatically, but ensure it's set after modal is fully hidden
                if (!modalElement.classList.contains('show')) {
                    modalElement.setAttribute('aria-hidden', 'true');
                    modalElement.removeAttribute('aria-modal');
                }
            });
        }

        // Check if modal instance already exists
        let modal = bootstrap.Modal.getInstance(modalElement);
        if (!modal) {
            modal = new bootstrap.Modal(modalElement, {
            backdrop: 'static',
            keyboard: false
        });
        }
        
        // Update shift summary — sales = transaction total minus approved voided line amounts
        const shiftTransactions = shiftData.transactions || [];
        
        // Use global void status maps if available
        let voidStatusMap = { ...globalVoidStatusMap };
        let voidedItemsMap = { ...globalVoidedItemsMap };
        
        // Calculate total sales using effective totals after approved void deductions
        // Only count cash sales - exclude E-Wallet (e_wallet, gcash, maya) and card
        let totalSales = 0;
        const eWalletMethods = ['e_wallet', 'gcash', 'maya'];
        
        const nonVoidedTransactions = shiftTransactions.filter(transaction =>
            window.getEffectiveTransactionTotalForShift(transaction, voidStatusMap, voidedItemsMap) > 0
        );
        
        // Filter to only cash transactions for end shift
        const cashTransactions = nonVoidedTransactions.filter(transaction => {
            const paymentMethod = transaction.paymentMethod || 'cash';
            // Only include cash transactions - exclude E-Wallet and other payment methods
            return paymentMethod === 'cash' && !eWalletMethods.includes(paymentMethod);
        });
        
        cashTransactions.forEach(transaction => {
            totalSales += window.getEffectiveTransactionTotalForShift(transaction, voidStatusMap, voidedItemsMap);
        });
        
        // Transaction count: Include all payment types (cash, e-wallet, check, card)
        const transactionCount = nonVoidedTransactions.length;
        
        // Calculate total discounts - only from cash transactions
        let totalDiscounts = 0;
        
        // Calculate payment methods excluding voided transactions
        // Display all payment methods (Cash, E-Wallet, Check) but only Cash counts toward Total Sales
        // Use transaction total (after discount) for payment methods
        const paymentMethodsAfterDiscount = { cash: 0, card: 0, gcash: 0, maya: 0, e_wallet: 0, check: 0 };
        
        // Process all transactions for payment methods breakdown (display purposes)
        nonVoidedTransactions.forEach(transaction => {
            const paymentMethod = transaction.paymentMethod || 'cash';
            const effectiveTotal = window.getEffectiveTransactionTotalForShift(transaction, voidStatusMap, voidedItemsMap);
            paymentMethodsAfterDiscount[paymentMethod] = (paymentMethodsAfterDiscount[paymentMethod] || 0) + effectiveTotal;
        });
        
        // Calculate total discounts - only from cash transactions (for Total Sales)
        cashTransactions.forEach(transaction => {
            const baseTotal = window.getBaseTransactionTotalForShift(transaction);
            const effectiveTotal = window.getEffectiveTransactionTotalForShift(transaction, voidStatusMap, voidedItemsMap);
            if (effectiveTotal <= 0 || baseTotal <= 0) return;
            
            // Calculate discount for total discounts display (only from cash)
            let transactionDiscount = 0;
            if (transaction.discountAmount !== undefined) {
                transactionDiscount = parseFloat(transaction.discountAmount) || 0;
            } else if (transaction.originalSubtotal !== undefined && transaction.subtotal !== undefined) {
                transactionDiscount = parseFloat(transaction.originalSubtotal) - parseFloat(transaction.subtotal) || 0;
            } else if (transaction.discount !== undefined) {
                transactionDiscount = parseFloat(transaction.discount) || 0;
            }
            
            const effectiveShare = Math.min(1, effectiveTotal / baseTotal);
            totalDiscounts += transactionDiscount * effectiveShare;
        });
        
        const shiftTotalSalesEl = document.getElementById('shiftTotalSales');
        if (shiftTotalSalesEl) {
            if (totalDiscounts > 0) {
                shiftTotalSalesEl.innerHTML = `₱${formatCurrency(totalSales)}<br><small class="text-success">Discount: -₱${formatCurrency(totalDiscounts)}</small>`;
            } else {
                shiftTotalSalesEl.textContent = `₱${formatCurrency(totalSales)}`;
            }
        }
        document.getElementById('shiftTransactions').textContent = transactionCount;
        
        // Update payment methods breakdown
        const breakdown = document.getElementById('paymentMethodsBreakdown');
        if (breakdown) {
            let breakdownHTML = '';
            Object.keys(paymentMethodsAfterDiscount).forEach(method => {
                const amount = paymentMethodsAfterDiscount[method] || 0;
                if (amount > 0) {
                    const methodName = method.charAt(0).toUpperCase() + method.slice(1).replace('_', ' ');
                    breakdownHTML += `<div><strong>${methodName}:</strong> ₱${formatCurrency(amount)}</div>`;
                }
            });
            breakdown.innerHTML = breakdownHTML || '<div><strong>Cash:</strong> ₱0.00</div>';
        }
        
        // Calculate expected cash (use cash amount after discount)
        const openingCash = shiftData.openingCash || 0;
        const cashSales = paymentMethodsAfterDiscount.cash || 0;
        const expectedCash = openingCash + cashSales;
        
        document.getElementById('expectedCash').value = formatCurrency(expectedCash);
        
        // Clear closing cash input and set up real-time difference calculation
        const closingCashInput = document.getElementById('closingCash');
        if (closingCashInput) {
            closingCashInput.value = '';
            
            // Store expected cash as data attribute for accurate difference calculation (raw numeric value)
            closingCashInput.setAttribute('data-expected-cash', expectedCash);
            
            // Remove old listeners and add new ones for real-time calculation
            closingCashInput.removeEventListener('input', window.updateCashDifference);
            closingCashInput.removeEventListener('change', window.updateCashDifference);
            closingCashInput.addEventListener('input', window.updateCashDifference);
            closingCashInput.addEventListener('change', window.updateCashDifference);
        }
        
        // Reset difference display
        const cashDifferenceEl = document.getElementById('cashDifference');
        if (cashDifferenceEl) {
            cashDifferenceEl.className = 'alert alert-info';
            cashDifferenceEl.innerHTML = '<strong>Enter closing cash to see difference</strong>';
        }
        
        console.log('End shift modal data:', {
            totalSales,
            transactionCount,
            expectedCash,
            openingCash,
            cashSales
        });
        
        // Set up confirm button handler when modal is shown (ensure it works)
        const confirmBtn = modalElement.querySelector('#confirmEndShiftBtn');
        if (confirmBtn) {
            // Remove any existing listeners first
            const newBtn = confirmBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);
            
            // Get the new button reference
            const freshConfirmBtn = modalElement.querySelector('#confirmEndShiftBtn');
            if (freshConfirmBtn) {
                freshConfirmBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('✅ Confirm end shift button clicked (from modal show)');
                    
                    // Validate closing cash before proceeding
                    const closingCashInput = document.getElementById('closingCash');
                    if (!closingCashInput) {
                        console.error('❌ closingCash input not found');
                        showNotification('Error: Closing cash input not found', 'error');
                        return;
                    }
                    
                    const closingCash = parseFloat(closingCashInput.value);
                    if (isNaN(closingCash) || closingCash < 0) {
                        console.error('❌ Invalid closing cash:', closingCash);
                        showNotification('Please enter a valid closing cash amount', 'error');
                        closingCashInput.focus();
                        return;
                    }
                    
                    if (typeof window.endShift === 'function') {
                        console.log('✅ Calling window.endShift()');
                        window.endShift();
                    } else {
                        console.error('❌ endShift function not found!');
                        showNotification('Error: End shift function not available. Please refresh the page.', 'error');
                    }
                });
                console.log('✅ Confirm button handler attached when modal shown');
            }
        }
        
        try {
        modal.show();
            console.log('✅ End shift modal shown successfully');
        } catch (error) {
            console.error('Error showing end shift modal:', error);
            showNotification('Error opening end shift modal. Please try again.', 'error');
        }
    };
    
    // Function to update cash difference in real-time (must be defined before modal setup)
    window.updateCashDifference = function() {
        const closingCashInput = document.getElementById('closingCash');
        const expectedCashInput = document.getElementById('expectedCash');
        const differenceElement = document.getElementById('cashDifference');
        
        if (!closingCashInput || !expectedCashInput || !differenceElement) {
            return;
        }
        
        const closingCash = parseFloat(closingCashInput.value) || 0;
        // Use the data attribute which contains the raw numeric value (more reliable than parsing formatted string)
        const expectedCashRaw = closingCashInput.getAttribute('data-expected-cash');
        let expectedCash = 0;
        if (expectedCashRaw) {
            expectedCash = parseFloat(expectedCashRaw) || 0;
        } else {
            // Fallback: Remove commas from expectedCash input value before parsing
            const expectedCashValue = expectedCashInput.value.replace(/,/g, '');
            expectedCash = parseFloat(expectedCashValue) || 0;
        }
        const difference = closingCash - expectedCash;
        
        if (closingCash === 0 || isNaN(closingCash)) {
            differenceElement.className = 'alert alert-info';
            differenceElement.innerHTML = '<strong>Enter closing cash to see difference</strong>';
        } else if (difference === 0) {
            differenceElement.className = 'alert alert-success';
            differenceElement.innerHTML = '<strong>Perfect! No difference</strong>';
        } else if (difference > 0) {
            differenceElement.className = 'alert alert-info';
            differenceElement.innerHTML = `<strong>Over by: ₱${formatCurrency(difference)}</strong>`;
        } else {
            differenceElement.className = 'alert alert-warning';
            differenceElement.innerHTML = `<strong>Short by: ₱${formatCurrency(Math.abs(difference))}</strong>`;
        }
    };

    // Make startShift globally accessible
    window.startShift = async function() {
        const confirmBtn = document.getElementById('confirmShiftBtn');
        
        // Prevent double-click: disable button immediately
        if (confirmBtn) {
            if (confirmBtn.disabled) {
                console.log('Start shift already in progress, ignoring double-click');
                return;
            }
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Starting...';
        }
        
        console.log('startShift function called');
        
        const openingCash = parseFloat(document.getElementById('openingCash').value);
        const cashierNameInput = document.getElementById('cashierNameInput');
        const cashierName = cashierNameInput.value.trim();
        
        console.log('Opening cash:', openingCash);
        console.log('Cashier name:', cashierName);
        
        // Validate opening cash
        if (isNaN(openingCash) || openingCash < 0) {
            showNotification('Please enter a valid opening cash amount', 'error');
            if (confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = 'Start Shift';
            }
            return;
        }

        // Validate cashier name
        if (!cashierName || cashierName.length < 2) {
            showNotification('Please enter a valid cashier name (at least 2 characters)', 'error');
            cashierNameInput.focus();
            if (confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = 'Start Shift';
            }
            return;
        }

        try {
            console.log('Sending shift start request...');
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error('CSRF token not found!');
                showNotification('Security token missing. Please refresh the page.', 'error');
                const btn = document.getElementById('confirmShiftBtn');
                if (btn) { btn.disabled = false; btn.innerHTML = 'Start Shift'; }
                return;
            }
            
            const response = await fetch('/shifts/start', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    opening_cash: parseFloat(openingCash) || 0,
                    cashier_name: cashierName || null
                })
            });

            console.log('Response status:', response.status);
            
            let data;
            try {
                data = await response.json();
            console.log('Response data:', data);
            } catch (jsonError) {
                console.error('Error parsing JSON response:', jsonError);
                showNotification('Error starting shift. Please try again.', 'error');
                const btn = document.getElementById('confirmShiftBtn');
                if (btn) { btn.disabled = false; btn.innerHTML = 'Start Shift'; }
                return;
            }
            
            // Handle non-OK responses
            if (!response.ok) {
                const errorMessage = data.message || data.error || 'Failed to start shift';
                if (data.errors) {
                    const validationErrors = Object.values(data.errors).flat().join(', ');
                    showNotification(validationErrors || errorMessage, 'error');
                } else {
                    showNotification(errorMessage, 'error');
                }
                const btn = document.getElementById('confirmShiftBtn');
                if (btn) { btn.disabled = false; btn.innerHTML = 'Start Shift'; }
                return;
            }

            if (data.success) {
                // Clear cart before starting new shift
                console.log('🔄 Clearing cart for new shift...');
                cart = [];
                if (typeof clearCashierScanSession === 'function') {
                    clearCashierScanSession();
                }
                if (typeof updateCartDisplay === 'function') {
                    updateCartDisplay();
                }
                
                // Reset transaction summary
                if (typeof updateTotals === 'function') {
                    updateTotals();
                }
                
                // Clear customer information
                const customerInfoSection = document.getElementById('customerInfo');
                if (customerInfoSection) {
                    customerInfoSection.style.display = 'none';
                }
                
                // Clear discount if applied
                if (typeof clearDiscount === 'function') {
                    clearDiscount();
                }
                
                // Reset payment method to Cash
                const cashPaymentBtn = document.querySelector('[data-method="cash"]') || 
                    Array.from(document.querySelectorAll('button')).find(btn => 
                        btn.textContent && btn.textContent.includes('Cash') && btn.classList.contains('payment-method-btn')
                    );
                if (cashPaymentBtn) {
                    cashPaymentBtn.click();
                }
                
                // Clear amount tendered
                const amountTenderedInput = document.getElementById('amountTendered');
                if (amountTenderedInput) {
                    amountTenderedInput.value = '';
                }
                
                // Clear cache when starting a new shift to ensure correct time display
                cachedStartTimeDisplay = null;
                        
                // Validate and lock the startTime from server when starting NEW shift
                // This is the ONLY time we should set/update lockedStartTime
                let newStartTime = data.shift.start_time;
                if (newStartTime && !newStartTime.includes('T') && !newStartTime.includes('Z') && /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(newStartTime)) {
                    // Save to localStorage for persistence
                    saveLockedStartTime(newStartTime);
                    console.log('🔒 Locked new startTime for new shift:', lockedStartTime);
                } else {
                    console.error('❌ Invalid startTime format from server:', newStartTime);
                    // Try to use existing locked time if available
                    if (lockedStartTime) {
                        newStartTime = lockedStartTime;
                        console.log('🔒 Using existing locked time due to invalid server format');
                    }
                }
                
                                    shiftData = {
                    id: data.shift.id,
                                        isActive: true,
                    startTime: newStartTime, // Server returns Asia/Manila time format: "2026-01-14 15:22:00"
                                        openingCash: openingCash,
                                        totalSales: 0,
                                        transactions: [],
                                        paymentMethods: { cash: 0 },
                                        cashierName: cashierName
                                    };
                                    
                console.log('✅ Shift data set:', shiftData);
                                    localStorage.setItem('activeShift', JSON.stringify(shiftData));
                console.log('✅ Shift data saved to localStorage');
                
                // Reset analytics display to 0 for new shift
                console.log('🔄 Resetting analytics display for new shift...');
                const todaySalesEl = document.getElementById('todaySales');
                const todayTransactionsEl = document.getElementById('todayTransactions');
                
                if (todaySalesEl) {
                    todaySalesEl.textContent = '₱0.00';
                }
                if (todayTransactionsEl) {
                    todayTransactionsEl.textContent = '0';
                }
                
                // Reset chart to show all zeros for new shift
                if (isChartValid()) {
                    try {
                        if (salesChart.data && salesChart.data.datasets && salesChart.data.datasets[0]) {
                            salesChart.data.datasets[0].data = [0, 0, 0, 0, 0, 0];
                            salesChart.update('none');
                            console.log('✅ Analytics chart reset to zeros for new shift');
                                }
                    } catch (chartError) {
                        console.warn('⚠️ Error resetting chart:', chartError);
                        salesChart = null;
                    }
                }
                
                // Clear todayTransactions for new shift
                todayTransactions = [];
                
                // Update status immediately
                updateShiftStatus();
                
                // Also update after a short delay to ensure UI is updated
                setTimeout(() => {
                    updateShiftStatus();
                    console.log('✅ Shift status updated after delay');
                }, 200);
                
                const modal = bootstrap.Modal.getInstance(document.getElementById('shiftModal'));
                if (modal) {
                    modal.hide();
                }
                
                showNotification('Shift started successfully', 'success');
                console.log('✅ Shift started successfully - all data reset');
            } else {
                // If there's already an active shift, inform the user instead of auto-ending
                if (data.message && data.message.includes('already have an active shift')) {
                    showNotification('You already have an active shift. Please end it first before starting a new one.', 'warning');
                    console.log('⚠️ Cannot start shift - active shift exists');
                    const btn = document.getElementById('confirmShiftBtn');
                    if (btn) { btn.disabled = false; btn.innerHTML = 'Start Shift'; }
                    return;
                } else {
                    console.error('Shift start failed:', data.message);
                    showNotification(data.message || 'Failed to start shift', 'error');
                    const btn = document.getElementById('confirmShiftBtn');
                    if (btn) { btn.disabled = false; btn.innerHTML = 'Start Shift'; }
                }
            }
        } catch (error) {
            console.error('Error starting shift:', error);
            showNotification('Error starting shift. Please try again.', 'error');
            const btn = document.getElementById('confirmShiftBtn');
            if (btn) { btn.disabled = false; btn.innerHTML = 'Start Shift'; }
        }
    }; // End of window.startShift

    // Make endShift globally accessible
    window.endShift = async function() {
        console.log('=== END SHIFT FUNCTION CALLED ===');
        console.log('Shift data:', shiftData);
        
        // Validate shift data
        if (!shiftData) {
            console.error('❌ shiftData is null or undefined');
            showNotification('No active shift to end', 'error');
            return;
        }

        if (!shiftData.isActive) {
            console.error('❌ Shift is not active. isActive:', shiftData.isActive);
            showNotification('No active shift to end', 'error');
            return;
        }

        // Get form elements
        const closingCashInput = document.getElementById('closingCash');
        const expectedCashInput = document.getElementById('expectedCash');
        const shiftNotesInput = document.getElementById('shiftNotes');
        
        if (!closingCashInput) {
            console.error('❌ closingCash input not found');
            showNotification('Error: Closing cash input not found', 'error');
            return;
        }
        
        if (!expectedCashInput) {
            console.error('❌ expectedCash input not found');
            showNotification('Error: Expected cash input not found', 'error');
            return;
        }
        
        // Remove commas from expectedCash value before parsing (since it's now a text input with formatted value)
        const expectedCashValue = expectedCashInput.value.replace(/,/g, '');
        const expectedCash = parseFloat(expectedCashValue) || 0;
        const closingCash = parseFloat(closingCashInput.value) || 0;
        const notes = shiftNotesInput ? shiftNotesInput.value : '';
        
        console.log('📊 Cash values:', {
            closingCash: closingCash,
            expectedCash: expectedCash,
            shiftId: shiftData.id,
            shiftDataType: typeof shiftData.id,
            fullShiftData: shiftData
        });
        
        // Validate shift ID
        if (!shiftData.id) {
            console.error('❌ shiftData.id is missing or invalid:', shiftData.id);
            showNotification('Shift ID is missing. Please refresh the page and try again.', 'error');
            return;
        }
        
        if (isNaN(closingCash) || closingCash < 0) {
            console.error('❌ Invalid closing cash:', closingCash);
            showNotification('Please enter a valid closing cash amount', 'error');
            closingCashInput.focus();
            return;
        }

        const difference = closingCash - expectedCash;
        const differenceElement = document.getElementById('cashDifference');
        
        console.log('💰 Cash difference:', difference);
        
        // Update difference display
        if (differenceElement) {
        if (difference === 0) {
            differenceElement.className = 'alert alert-success';
            differenceElement.innerHTML = '<strong>Perfect! No difference</strong>';
        } else if (difference > 0) {
            differenceElement.className = 'alert alert-info';
            differenceElement.innerHTML = `<strong>Over by: ₱${formatCurrency(difference)}</strong>`;
        } else {
            differenceElement.className = 'alert alert-warning';
            differenceElement.innerHTML = `<strong>Short by: ₱${formatCurrency(Math.abs(difference))}</strong>`;
            }
        }

        // Disable button to prevent double submission
        const endShiftBtn = document.getElementById('confirmEndShiftBtn');
        if (!endShiftBtn) {
            console.error('❌ confirmEndShiftBtn not found');
            showNotification('Error: End shift button not found', 'error');
            return;
        }
        
        const originalText = endShiftBtn.innerHTML;
        endShiftBtn.disabled = true;
        endShiftBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Ending Shift...';
        
        console.log('🔄 Button disabled, sending request...');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error('❌ CSRF token not found');
                throw new Error('CSRF token not found');
            }

            const requestBody = {
                shift_id: shiftData.id ? parseInt(shiftData.id) : null, // Send shift ID if available, ensure it's an integer
                closing_cash: closingCash,
                notes: notes
            };
            
            console.log('📤 Sending end shift request:', {
                url: '/shifts/end',
                method: 'POST',
                body: requestBody
            });

            const response = await fetch('/shifts/end', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(requestBody)
            });

            console.log('📥 Response received:', {
                status: response.status,
                statusText: response.statusText,
                ok: response.ok
            });
            
            // Check if response is ok
            if (!response.ok) {
                const errorText = await response.text();
                console.error('❌ Response not OK. Error text:', errorText);
                let errorData;
                try {
                    errorData = JSON.parse(errorText);
                    console.error('❌ Parsed error data:', errorData);
                } catch (e) {
                    errorData = { message: errorText || `HTTP error! status: ${response.status}` };
                    console.error('❌ Could not parse error as JSON:', e);
                }
                throw new Error(errorData.message || `Failed to end shift. Status: ${response.status}`);
            }
            
            // Parse response - handle both JSON and text responses
            let data;
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                try {
                    data = await response.json();
                    console.log('✅ Response data (JSON):', data);
                } catch (parseError) {
                    console.error('❌ Error parsing JSON response:', parseError);
                    const textResponse = await response.text();
                    console.error('❌ Response text:', textResponse);
                    throw new Error('Invalid JSON response from server');
                }
            } else {
                // Not JSON, try to parse as text
                const textResponse = await response.text();
                console.warn('⚠️ Response is not JSON:', textResponse);
                try {
                    data = JSON.parse(textResponse);
                } catch (e) {
                    throw new Error(`Server error: ${textResponse || 'Unknown error'}`);
                }
            }

            if (data && data.success) {
                console.log('✅ Shift ended successfully on server');
                
                // Generate shift report (optional, may not exist)
                if (typeof generateShiftReport === 'function') {
                    try {
                generateShiftReport(closingCash, difference);
                        console.log('✅ Shift report generated');
                    } catch (reportError) {
                        console.warn('⚠️ Error generating shift report:', reportError);
                        // Don't fail the whole process if report generation fails
                    }
                } else {
                    console.log('ℹ️ generateShiftReport function not available (optional)');
                }
                
                // Clear shift data completely
                console.log('🔄 Clearing shift data...');
                cachedStartTimeDisplay = null; // Clear cache when shift ends
                clearLockedStartTime(); // Clear lock when shift ends
                shiftData = {
                    isActive: false,
                    startTime: null,
                    openingCash: 0,
                    totalSales: 0,
                    transactions: [],
                    paymentMethods: { cash: 0 },
                    endTime: new Date().toISOString(),
                    closingCash: closingCash,
                    cashDifference: difference
                };
                console.log('✅ Shift data cleared:', shiftData);
                
                // Clear cart completely
                console.log('🔄 Clearing cart...');
                cart = [];
                if (typeof clearCashierScanSession === 'function') {
                    clearCashierScanSession();
                }
                if (typeof updateCartDisplay === 'function') {
                    updateCartDisplay();
                    console.log('✅ Cart cleared and display updated');
                }
                
                // Reset transaction summary UI
                console.log('🔄 Resetting transaction summary...');
                
                // Reset totals using updateTotals function (this will update all UI elements)
                if (typeof updateTotals === 'function') {
                    updateTotals();
                    console.log('✅ Totals reset via updateTotals function');
                }
                
                // Also manually update UI elements to ensure they're reset
                const itemCountEl = document.getElementById('itemCount');
                const subtotalEl = document.getElementById('subtotal');
                const totalEl = document.getElementById('total');
                const discountLineEl = document.getElementById('discountLine');
                const discountAmountEl = document.getElementById('discountAmount');
                
                if (itemCountEl) itemCountEl.textContent = '0';
                if (subtotalEl) subtotalEl.textContent = '₱ 0.00';
                if (totalEl) totalEl.textContent = '₱ 0.00';
                if (discountLineEl) discountLineEl.style.display = 'none';
                if (discountAmountEl) discountAmountEl.textContent = '₱ 0.00';
                
                // Clear customer information
                console.log('🔄 Clearing customer information...');
                const customerNameEl = document.getElementById('customerName');
                const customerTinEl = document.getElementById('customerTIN');
                const customerAddressEl = document.getElementById('customerAddress');
                const customerInfoSection = document.getElementById('customerInfo');
                
                if (customerNameEl) customerNameEl.textContent = '';
                if (customerTinEl) customerNameEl.textContent = '';
                if (customerAddressEl) customerAddressEl.textContent = '';
                if (customerInfoSection) {
                    customerInfoSection.style.display = 'none';
                }
                
                // Clear discount if applied
                if (typeof clearDiscount === 'function') {
                    clearDiscount();
                    console.log('✅ Discount cleared');
                }
                
                // Reset payment method to Cash
                const cashPaymentBtn = document.querySelector('[data-method="cash"]') || 
                    Array.from(document.querySelectorAll('button')).find(btn => 
                        btn.textContent && btn.textContent.includes('Cash') && btn.classList.contains('payment-method-btn')
                    );
                if (cashPaymentBtn) {
                    cashPaymentBtn.click();
                }
                
                // Clear amount tendered input
                const amountTenderedInput = document.getElementById('amountTendered');
                if (amountTenderedInput) {
                    amountTenderedInput.value = '';
                }
                
                // Reset analytics display to 0
                console.log('🔄 Resetting analytics display...');
                const todaySalesEl = document.getElementById('todaySales');
                const todayTransactionsEl = document.getElementById('todayTransactions');
                
                if (todaySalesEl) {
                    todaySalesEl.textContent = '₱0.00';
                }
                if (todayTransactionsEl) {
                    todayTransactionsEl.textContent = '0';
                }
                
                // Reset chart to show all zeros
                if (isChartValid()) {
                    try {
                        if (salesChart.data && salesChart.data.datasets && salesChart.data.datasets[0]) {
                            salesChart.data.datasets[0].data = [0, 0, 0, 0, 0, 0];
                            salesChart.update('none');
                            console.log('✅ Analytics chart reset to zeros');
                        }
                    } catch (chartError) {
                        console.warn('⚠️ Error resetting chart:', chartError);
                        salesChart = null;
                    }
                }
                
                // Clear todayTransactions for this shift
                todayTransactions = [];
                
                // Remove from localStorage
                localStorage.removeItem('activeShift');
                console.log('✅ Removed activeShift from localStorage');
                
                // Update UI immediately
                console.log('🔄 Updating shift status UI...');
                if (typeof updateShiftStatus === 'function') {
                updateShiftStatus();
                    console.log('✅ Shift status UI updated');
                } else {
                    console.error('❌ updateShiftStatus function not found');
                }
                
                // Close the end shift modal and clean up all backdrops
                console.log('🔄 Closing end shift modal...');
                const modalElement = document.getElementById('endShiftModal');
                if (modalElement) {
                    const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                        // Remove focus from any element inside modal before hiding
                        const activeElement = document.activeElement;
                        if (activeElement && modalElement.contains(activeElement) && activeElement !== document.body && activeElement !== document.documentElement) {
                            if (typeof activeElement.blur === 'function') {
                                activeElement.blur();
                            }
                        }
                        
                        // Hide modal
                    modal.hide();
                        
                        // Wait for Bootstrap to finish hiding, then clean up any remaining backdrops
                        setTimeout(() => {
                            // Remove all modal backdrops
                            const backdrops = document.querySelectorAll('.modal-backdrop');
                            backdrops.forEach(backdrop => {
                                backdrop.remove();
                                console.log('✅ Removed backdrop');
                            });
                            
                            // Remove modal-open class from body
                            document.body.classList.remove('modal-open');
                            
                            // Remove any inline styles Bootstrap might have added
                            document.body.style.overflow = '';
                            document.body.style.paddingRight = '';
                            
                            console.log('✅ Modal closed and backdrops cleaned up');
                        }, 300); // Wait for Bootstrap animation to complete
                    } else {
                        console.warn('⚠️ Modal instance not found, cleaning up manually');
                        modalElement.classList.remove('show');
                        modalElement.setAttribute('aria-hidden', 'true');
                        
                        // Remove all backdrops
                        const backdrops = document.querySelectorAll('.modal-backdrop');
                        backdrops.forEach(backdrop => backdrop.remove());
                        
                        // Clean up body
                        document.body.classList.remove('modal-open');
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                        
                        console.log('✅ Modal cleaned up manually');
                    }
                } else {
                    console.error('❌ endShiftModal element not found');
                    // Still try to clean up backdrops
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => backdrop.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }
                
                showNotification('✓ Shift ended successfully!', 'success');
                console.log('✅ Shift ended successfully - all steps completed');
                
                // Re-enable button
                endShiftBtn.disabled = false;
                endShiftBtn.innerHTML = originalText;
                
                // Show start shift modal for next shift after a delay
                // Wait for modal to fully close and backdrops to be cleaned up first
                setTimeout(() => {
                    // Ensure all backdrops are removed before opening new modal
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => {
                        backdrop.remove();
                        console.log('✅ Removed remaining backdrop before opening start shift modal');
                    });
                    
                    // Ensure body is clean
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                    
                    console.log('🔄 Attempting to show start shift modal...');
                    console.log('Available functions:', {
                        showStartShiftModal: typeof window.showStartShiftModal,
                        showStartShiftModalWithRetry: typeof window.showStartShiftModalWithRetry
                    });
                    
                    // Try multiple methods to show the start shift modal
                    if (typeof window.showStartShiftModal === 'function') {
                        console.log('✅ Calling window.showStartShiftModal()');
                        try {
                            window.showStartShiftModal();
                        } catch (modalError) {
                            console.error('❌ Error calling showStartShiftModal:', modalError);
                            // Fallback to retry method
                            if (typeof window.showStartShiftModalWithRetry === 'function') {
                                console.log('🔄 Falling back to showStartShiftModalWithRetry');
                                window.showStartShiftModalWithRetry(0);
                            }
                        }
                    } else if (typeof window.showStartShiftModalWithRetry === 'function') {
                        console.log('✅ Calling window.showStartShiftModalWithRetry(0)');
                        try {
                            window.showStartShiftModalWithRetry(0);
                        } catch (modalError) {
                            console.error('❌ Error calling showStartShiftModalWithRetry:', modalError);
                            showNotification('Shift ended. Click "Start Shift" to begin a new shift.', 'info');
                        }
            } else {
                        console.error('❌ showStartShiftModal function not available');
                        // Try direct modal access as last resort
                        const shiftModal = document.getElementById('shiftModal');
                        if (shiftModal && typeof bootstrap !== 'undefined') {
                            try {
                                // Clean up any existing modal instances
                                const existingModal = bootstrap.Modal.getInstance(shiftModal);
                                if (existingModal) {
                                    existingModal.dispose();
                                }
                                
                                // Clean up any existing backdrops first
                                const existingBackdrops = document.querySelectorAll('.modal-backdrop');
                                existingBackdrops.forEach(backdrop => backdrop.remove());
                                document.body.classList.remove('modal-open');
                                document.body.style.overflow = '';
                                document.body.style.paddingRight = '';
                                
                                const modal = new bootstrap.Modal(shiftModal, {
                                    backdrop: 'static',
                                    keyboard: false
                                });
                                modal.show();
                                console.log('✅ Opened start shift modal directly');
                            } catch (directError) {
                                console.error('❌ Error opening modal directly:', directError);
                                showNotification('Shift ended. Click "Start Shift" to begin a new shift.', 'info');
                            }
                        } else {
                            showNotification('Shift ended. Click "Start Shift" to begin a new shift.', 'info');
                        }
                    }
                }, 500); // Reduced delay since we're cleaning up properly
            } else {
                console.error('❌ Server returned success: false', data);
                throw new Error(data.message || 'Failed to end shift');
            }
        } catch (error) {
            console.error('❌ Error ending shift:', error);
            console.error('Error name:', error.name);
            console.error('Error message:', error.message);
            if (error.stack) {
                console.error('Error stack:', error.stack);
            }
            
            // Show user-friendly error message
            let errorMessage = 'Error ending shift';
            if (error.message) {
                errorMessage = error.message;
            } else if (error instanceof TypeError) {
                errorMessage = 'Network error. Please check your connection.';
            } else if (error instanceof SyntaxError) {
                errorMessage = 'Invalid response from server.';
            } else if (error.name === 'AbortError') {
                errorMessage = 'Request was cancelled.';
            }
            
            showNotification(`Error: ${errorMessage}`, 'error');
            
            // Re-enable button
            if (endShiftBtn) {
            endShiftBtn.disabled = false;
            endShiftBtn.innerHTML = originalText;
        }
        }
        
        console.log('=== END SHIFT FUNCTION COMPLETED ===');
    };

    function updateShiftStatus() {
        const statusElement = document.getElementById('shiftStatus');
        const timeElement = document.getElementById('shiftTime');
        const cashierNameElement = document.getElementById('cashierName');
        const noShiftActions = document.getElementById('noShiftActions');
        const activeShiftActions = document.getElementById('activeShiftActions');
        
        // Check localStorage as backup if shiftData is not set
        if (!shiftData || !shiftData.isActive) {
            const storedShift = localStorage.getItem('activeShift');
            if (storedShift) {
                try {
                    const parsedShift = JSON.parse(storedShift);
                    if (parsedShift.isActive) {
                        console.log('🔄 Loading shift data from localStorage for status update');
                        // Use locked time if available, otherwise use stored time
                        if (lockedStartTime && parsedShift.startTime !== lockedStartTime) {
                            console.log('🔒 Using locked startTime instead of localStorage time');
                            parsedShift.startTime = lockedStartTime;
                        }
                        shiftData = parsedShift;
                    }
                } catch (e) {
                    console.error('Error parsing stored shift in updateShiftStatus:', e);
                }
            }
        }
        
        // Ensure shiftData uses locked time if available and different
        if (shiftData && shiftData.isActive && shiftData.startTime && lockedStartTime && shiftData.startTime !== lockedStartTime) {
            console.log('🔒 Correcting shiftData.startTime to locked time');
            shiftData.startTime = lockedStartTime;
            // Only clear cache if the time actually changed
            if (cachedStartTimeDisplay && cachedStartTimeDisplay.originalTime !== lockedStartTime) {
                cachedStartTimeDisplay = null;
            }
        }
        
        if (shiftData && shiftData.isActive) {
            // Active shift
            console.log('✅ Updating shift status: Active', shiftData);
            if (statusElement) {
            statusElement.className = 'badge bg-success';
            statusElement.textContent = 'Shift Active';
            }
            if (timeElement && shiftData.startTime) {
                try {
                    // Use locked time if available to ensure consistency
                    const currentStartTime = lockedStartTime || shiftData.startTime;
                    
                    // Check if we already have a cached formatted time for this startTime value
                    // Only re-format if the startTime value has changed
                    
                    // If cached time exists and matches current startTime, reuse it
                    if (cachedStartTimeDisplay && cachedStartTimeDisplay.originalTime === currentStartTime) {
                        timeElement.textContent = cachedStartTimeDisplay.formatted;
                        return;
                    }
                    
                    // Format the time (backend returns Asia/Manila time: "2026-01-14 15:22:00")
                    // IMPORTANT: Parse and format directly without Date object conversion to prevent timezone issues
                    const timeStr = currentStartTime;
                    console.log('Formatting start time:', timeStr);
                    
                    // Extract time part from datetime string (server already sends Asia/Manila time)
                    let timePart = '';
                    
                    if (timeStr.includes('T')) {
                        // ISO format: "2026-01-14T15:22:00" or "2026-01-14T15:22:00Z"
                        // Remove 'Z' if present (indicates UTC, but we treat as Asia/Manila)
                        const cleanTimeStr = timeStr.replace('Z', '').split('.')[0];
                        const parts = cleanTimeStr.split('T');
                        if (parts.length >= 2) {
                            timePart = parts[1];
                        }
                    } else if (timeStr.includes(' ')) {
                        // MySQL format: "2026-01-14 15:22:00" (already in Asia/Manila from server)
                        const parts = timeStr.split(' ');
                        if (parts.length >= 2) {
                            timePart = parts[1];
                        }
                    } else {
                        // Just time: "15:22:00"
                        timePart = timeStr;
                    }
                    
                    // Parse and format time components directly (no Date object conversion)
                    // This ensures the time never changes regardless of browser timezone
                    if (timePart) {
                        const parts = timePart.split(':');
                        if (parts.length >= 2) {
                            const hour24 = parseInt(parts[0]);
                            const minute = parts[1].padStart(2, '0');
                            const second = parts[2] ? parts[2].padStart(2, '0') : '00';
                            
                            // Convert to 12-hour format (Asia/Manila time)
                            const hour12 = hour24 === 0 ? 12 : (hour24 > 12 ? hour24 - 12 : hour24);
                            const ampm = hour24 >= 12 ? 'PM' : 'AM';
                            
                            const formattedTime = `${hour12}:${minute}:${second} ${ampm}`;
                            const displayText = `Started: ${formattedTime}`;
                            
                            // Cache the formatted time to prevent re-formatting
                            cachedStartTimeDisplay = {
                                originalTime: currentStartTime,
                                formatted: displayText
                            };
                            
                            timeElement.textContent = displayText;
                        } else {
                            // Fallback: use the time string as-is
                            const displayText = `Started: ${timeStr}`;
                            cachedStartTimeDisplay = {
                                originalTime: currentStartTime,
                                formatted: displayText
                            };
                            timeElement.textContent = displayText;
                        }
                    } else {
                        // Final fallback: use the time string as-is
                        const displayText = `Started: ${timeStr}`;
                        cachedStartTimeDisplay = {
                            originalTime: currentStartTime,
                            formatted: displayText
                        };
                        timeElement.textContent = displayText;
                    }
                } catch (error) {
                    console.error('Error formatting start time:', error);
                    console.error('Start time value:', shiftData.startTime);
                    const displayText = `Started: ${shiftData.startTime}`;
                    cachedStartTimeDisplay = {
                        originalTime: shiftData.startTime,
                        formatted: displayText
                    };
                    timeElement.textContent = displayText;
                }
            }
            
            if (cashierNameElement) {
            if (shiftData.cashierName) {
                cashierNameElement.innerHTML = `<strong>Cashier:</strong> ${shiftData.cashierName}`;
            } else {
                cashierNameElement.innerHTML = '';
                }
            }
            
            // Show active shift buttons, hide no shift buttons
            if (noShiftActions) noShiftActions.style.display = 'none';
            if (activeShiftActions) activeShiftActions.style.display = 'block';
        } else {
            // No active shift
            console.log('⚠️ Updating shift status: No Active Shift', shiftData);
            if (statusElement) {
            statusElement.className = 'badge bg-secondary';
            statusElement.textContent = 'No Active Shift';
            }
            if (timeElement) {
            timeElement.textContent = 'Start a shift to begin';
            }
            if (cashierNameElement) {
            cashierNameElement.innerHTML = '';
            }
            
            // Show no shift buttons, hide active shift buttons
            if (noShiftActions) noShiftActions.style.display = 'block';
            if (activeShiftActions) activeShiftActions.style.display = 'none';
        }
    }

    // Analytics
    function initializeAnalytics() {
        // Prevent concurrent initialization
        if (isInitializingChart) {
            console.log('Chart initialization already in progress, skipping...');
            return;
        }
        
        // Check if canvas element exists
        const canvas = document.getElementById('salesChart');
        if (!canvas) {
            console.error('Sales chart canvas not found');
            // Retry after a short delay
            setTimeout(initializeAnalytics, 100);
            return;
        }
        
        // Check if chart container is visible
        const container = canvas.closest('.analytics-chart-container');
        if (container && container.offsetParent === null) {
            console.warn('Chart container is not visible, waiting...');
            setTimeout(initializeAnalytics, 200);
            return;
        }
        
        // Check if Chart.js is loaded - wait for it if not
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js is not loaded yet, waiting...');
            // Retry after a short delay
            setTimeout(initializeAnalytics, 100);
            return;
        }
        
        // Destroy existing chart if it exists
        if (salesChart) {
            try {
                salesChart.destroy();
            } catch (e) {
                console.warn('Error destroying existing chart:', e);
            }
            salesChart = null;
        }
        
        try {
        // Initialize Chart.js
            // Double-check canvas exists and is valid
            if (!canvas || !canvas.getContext) {
                console.error('Canvas element is invalid');
                return;
            }
            
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                throw new Error('Could not get 2d context from canvas');
            }
            
            // Verify canvas is still in the DOM and has ownerDocument
            if (!canvas.ownerDocument) {
                console.error('Canvas element is not properly attached to DOM (no ownerDocument)');
                return;
            }
            
            if (!canvas.parentElement) {
                console.error('Canvas element is not properly attached to DOM (no parentElement)');
                return;
            }
            
            // Verify Chart.js is fully loaded (Chart.js v4 uses Chart directly, not Chart.Chart)
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not fully loaded');
                setTimeout(initializeAnalytics, 200);
                return;
            }
            
            // Set flag to prevent concurrent initialization
            isInitializingChart = true;
            
            // Final check right before creating chart - ensure canvas is still valid
            const finalCanvasCheck = document.getElementById('salesChart');
            if (!finalCanvasCheck || finalCanvasCheck !== canvas || !finalCanvasCheck.ownerDocument) {
                console.error('Canvas became invalid between checks');
                isInitializingChart = false;
                setTimeout(initializeAnalytics, 500);
                return;
            }
            
            // Get initial hourly sales data
            let initialHourlySales = getHourlySales();
            console.log('Initial hourly sales:', initialHourlySales);
            
            // Calculate total sales for validation using effective totals after approved void deductions
            let initialTotalSales = 0;
            if (shiftData && shiftData.isActive && shiftData.transactions) {
                shiftData.transactions.forEach(t => {
                    initialTotalSales += window.getEffectiveTransactionTotalForShift(t, globalVoidStatusMap, globalVoidedItemsMap);
                });
            }
            
            // Validate: if we have sales but all buckets are zero, put sales in the most recent bucket
            const sumOfBuckets = initialHourlySales.reduce((sum, val) => sum + val, 0);
            if (initialTotalSales > 0 && sumOfBuckets === 0) {
                console.warn('Initial: Sales exist but no buckets have data, distributing to most recent bucket');
                const currentHour = new Date().getHours();
                const hours = [6, 9, 12, 15, 18, 21];
                let targetBucketIndex = hours.length - 1; // Default to last bucket
                
                for (let i = 0; i < hours.length; i++) {
                    const bucketStart = hours[i] - 3;
                    const bucketEnd = hours[i];
                    if (bucketStart < 0) {
                        if (currentHour >= 21 || currentHour < bucketEnd) {
                            targetBucketIndex = i;
                            break;
                        }
                    } else if (currentHour >= bucketStart && currentHour < bucketEnd) {
                        targetBucketIndex = i;
                        break;
                    }
                }
                
                initialHourlySales[targetBucketIndex] = initialTotalSales;
                console.log(`Initial: Distributed ₱${initialTotalSales.toFixed(2)} to bucket ${hours[targetBucketIndex]}:00`);
            }
            
            console.log('Validated initial hourly sales:', initialHourlySales);
            
            // Create chart with error handling
            try {
        salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['6AM', '9AM', '12PM', '3PM', '6PM', '9PM'],
                datasets: [{
                    label: 'Sales (₱)',
                        data: initialHourlySales,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        borderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#007bff',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        tension: 0.4,
                        fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                resizeDelay: 100,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: {
                                    size: window.innerWidth < 576 ? 10 : 12
                                }
                            }
                        },
                        tooltip: {
                            enabled: true,
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    return 'Sales: ₱' + context.parsed.y.toFixed(2);
                                }
                            }
                        }
                    },
                scales: {
                        x: {
                            display: true,
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: window.innerWidth < 576 ? 9 : 11
                                }
                            }
                        },
                    y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toFixed(0);
                                },
                                font: {
                                    size: window.innerWidth < 576 ? 9 : 11
                                }
                            }
                        }
                    },
                    animation: {
                        duration: 0 // Disable animation for faster updates
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                }
            }
        });
            } catch (chartInitError) {
                console.error('Error creating Chart.js instance:', chartInitError);
                console.error('Error details:', {
                    canvas: canvas ? 'exists' : 'null',
                    ctx: ctx ? 'exists' : 'null',
                    ownerDocument: canvas && canvas.ownerDocument ? 'exists' : 'null',
                    parentElement: canvas && canvas.parentElement ? 'exists' : 'null',
                    errorMessage: chartInitError.message,
                    errorStack: chartInitError.stack
                });
                salesChart = null;
                isInitializingChart = false; // Reset flag on error
                // Retry after delay
                setTimeout(() => {
                    initializeAnalytics();
                }, 1000);
                return;
            }
            
            console.log('Sales chart initialized successfully');
            isInitializingChart = false; // Reset flag on success
            
            // Force a resize to ensure chart renders properly
            setTimeout(() => {
                if (isChartValid()) {
                    try {
                        salesChart.resize();
                    } catch (resizeError) {
                        console.warn('Error resizing chart:', resizeError);
                    }
                }
            }, 100);
        } catch (error) {
            console.error('Error initializing sales chart:', error);
            console.error('Error details:', error.stack);
            isInitializingChart = false; // Reset flag on error
            salesChart = null;
            // Retry initialization after a delay
            setTimeout(() => {
                console.log('Retrying chart initialization...');
                initializeAnalytics();
            }, 500);
        }
    }

    async function updateAnalytics() {
        // Only show shift-specific analytics (not all of today's sales)
        // If no active shift, show zeros
        if (!shiftData || !shiftData.isActive) {
            const todaySalesEl = document.getElementById('todaySales');
            const todayTransactionsEl = document.getElementById('todayTransactions');
            
            if (todaySalesEl) {
                todaySalesEl.textContent = '₱0.00';
            }
            if (todayTransactionsEl) {
                todayTransactionsEl.textContent = '0';
            }
            
            // Reset chart to zeros
            if (isChartValid()) {
                try {
                    if (salesChart.data && salesChart.data.datasets && salesChart.data.datasets[0]) {
                        salesChart.data.datasets[0].data = [0, 0, 0, 0, 0, 0];
                        salesChart.update('none');
                    }
                } catch (error) {
                    console.error('Error resetting chart:', error);
                    salesChart = null;
                }
            }
            return; // Don't update if no active shift
        }
        
        // Use shift-specific data only (from shiftData.transactions)
        // Calculate totals from current shift's transactions - RECALCULATE to ensure accuracy
        const shiftTransactions = shiftData.transactions || [];
        
        // Collect sale IDs to fetch void statuses
        const saleIds = new Set();
        shiftTransactions.forEach(transaction => {
            if (transaction.id) {
                saleIds.add(transaction.id);
            }
        });
        
        // Fetch void request statuses and voided items for all sales
        const voidStatusMap = {};
        const voidedItemsMap = {};
        if (saleIds.size > 0) {
            try {
                const saleIdsArray = Array.from(saleIds).filter(id => id && !isNaN(id) && id > 0);
                if (saleIdsArray.length > 0) {
                    const response = await fetch(`/api/sales/void-statuses?${saleIdsArray.map(id => `sale_ids[]=${id}`).join('&')}`);
                    if (response.ok) {
            const data = await response.json();
                        if (data.success && data.void_statuses) {
                            Object.assign(voidStatusMap, data.void_statuses);
                            // Store globally for use in getHourlySales
                            globalVoidStatusMap = { ...voidStatusMap };
                        }
                        if (data.success && data.voided_items) {
                            Object.assign(voidedItemsMap, data.voided_items);
                            // Store globally for use in getHourlySales
                            globalVoidedItemsMap = { ...voidedItemsMap };
                }
                    } else if (response.status === 400) {
                        // Silently handle validation errors - likely invalid sale IDs
                        console.debug('Some sale IDs may be invalid, skipping void status fetch');
                    }
            }
        } catch (error) {
                console.error('Error fetching void statuses for analytics:', error);
                // Continue without void statuses if fetch fails
            }
        }
        
        // Recalculate total sales from transactions using effective totals after approved void deductions
        let totalSales = 0;
        shiftTransactions.forEach(transaction => {
            if (!window.isCountableShiftTransaction(transaction, voidStatusMap, voidedItemsMap)) {
                return;
            }
            totalSales += window.getEffectiveTransactionTotalForShift(transaction, voidStatusMap, voidedItemsMap);
        });
                
        // Count only completed sales that still have sold (non-voided) items
        const transactionsWithNonVoidedItems = new Set();
        
        shiftTransactions.forEach(transaction => {
            if (!window.isCountableShiftTransaction(transaction, voidStatusMap, voidedItemsMap)) {
                return;
            }
            transactionsWithNonVoidedItems.add(transaction.id || ('sale-' + (transaction.receiptNumber || transactionsWithNonVoidedItems.size)));
        });
        
        const transactionCount = transactionsWithNonVoidedItems.size;
        
        console.log('Updating analytics from shift data - Sales:', totalSales, 'Transactions (excluding approved voids):', transactionCount, 'All transactions:', shiftTransactions.length);
        
        // Update shiftData.totalSales to match the calculated value (excluding voided items)
        if (shiftData) {
            shiftData.totalSales = totalSales;
            shiftData.transactionCount = transactionCount;
            // Save to localStorage to keep it in sync
            localStorage.setItem('activeShift', JSON.stringify(shiftData));
        }
        
        // Update DOM elements with shift-specific data
        const todaySalesEl = document.getElementById('todaySales');
        const todayTransactionsEl = document.getElementById('todayTransactions');
        
        if (todaySalesEl) {
            todaySalesEl.textContent = `₱${totalSales.toFixed(2)}`;
        }
        if (todayTransactionsEl) {
            todayTransactionsEl.textContent = transactionCount;
            console.log('Updated transaction count display (shift-specific):', transactionCount);
        }
        
        // Update chart with hourly data
        if (salesChart) {
            try {
                // Check if chart is still valid and canvas exists
                if (!isChartValid()) {
                    console.warn('Chart canvas is no longer valid, re-initializing...');
                    salesChart = null;
                    setTimeout(() => {
                        initializeAnalytics();
                    }, 500);
                    return;
                }
                
                let hourlySales = getHourlySales();
                console.log('Raw hourly sales from getHourlySales():', hourlySales);
                
                // Validate: if we have sales but all buckets are zero, put sales in the most recent bucket
                const sumOfBuckets = hourlySales.reduce((sum, val) => sum + val, 0);
                if (totalSales > 0 && sumOfBuckets === 0) {
                    console.warn('Sales exist but no buckets have data, distributing to most recent bucket');
                    const currentHour = new Date().getHours();
                    const hours = [6, 9, 12, 15, 18, 21];
                    let targetBucketIndex = hours.length - 1; // Default to last bucket
                    
                    // Find the bucket that contains the current hour
                    for (let i = 0; i < hours.length; i++) {
                        const bucketStart = hours[i] - 3;
                        const bucketEnd = hours[i];
                        if (bucketStart < 0) {
                            if (currentHour >= 21 || currentHour < bucketEnd) {
                                targetBucketIndex = i;
                                break;
                            }
                        } else if (currentHour >= bucketStart && currentHour < bucketEnd) {
                            targetBucketIndex = i;
                            break;
                        }
                    }
                    
                    hourlySales[targetBucketIndex] = totalSales;
                    console.log(`Distributed ₱${totalSales.toFixed(2)} to bucket ${hours[targetBucketIndex]}:00 (index ${targetBucketIndex})`);
                }
                
                console.log('Validated hourly sales for chart:', hourlySales);
                
                // Update chart data
                if (salesChart.data && salesChart.data.datasets && salesChart.data.datasets[0]) {
            salesChart.data.datasets[0].data = hourlySales;
                    
                    // Update chart
                    salesChart.update('none'); // 'none' mode for instant update without animation
                    
                    console.log('Chart updated successfully');
                } else {
                    console.warn('Chart data structure is invalid, re-initializing...');
                    salesChart = null;
                    setTimeout(() => {
                        initializeAnalytics();
                    }, 500);
                }
            } catch (error) {
                console.error('Error updating sales chart:', error);
                console.error('Error stack:', error.stack);
                // Reset chart and re-initialize if update fails
                salesChart = null;
                setTimeout(() => {
                    initializeAnalytics();
                }, 500);
            }
        } else {
            // Chart not initialized, try to initialize it
            console.warn('Sales chart not initialized, attempting to initialize...');
            setTimeout(() => {
                initializeAnalytics();
            }, 200);
        }
    }

    // Store all today's transactions separately from shiftData
    let todayTransactions = [];
    let lastFetchDate = null; // Track the last date we fetched data for
    
    // Store void statuses globally for use in getHourlySales and updateAnalytics
    let globalVoidStatusMap = {};
    let globalVoidedItemsMap = {};

    function getHourlySales() {
        // Get hourly sales data from CURRENT SHIFT's transactions only
        const hours = [6, 9, 12, 15, 18, 21];
        
        // Only use shift-specific transactions if shift is active
        let allTransactions = [];
        if (shiftData && shiftData.isActive && shiftData.transactions && Array.isArray(shiftData.transactions)) {
            allTransactions = shiftData.transactions.filter(t =>
                window.isCountableShiftTransaction(t, globalVoidStatusMap, globalVoidedItemsMap)
            );
        }
        
        console.log('Calculating hourly sales from shift transactions:', allTransactions.length, 'transactions');
        console.log('Transactions data:', allTransactions);
        
        // If no transactions, return zeros
        if (!allTransactions || allTransactions.length === 0) {
            console.log('No transactions found, returning zeros for all hours');
            return hours.map(() => 0);
        }
        
        // Check if any transactions have valid timestamps
        let hasValidTimestamps = false;
        allTransactions.forEach(t => {
            let transactionTime = null;
            if (t.timestamp) {
                transactionTime = new Date(t.timestamp);
            } else if (t.created_at) {
                transactionTime = new Date(t.created_at);
            } else if (t.date) {
                transactionTime = new Date(t.date);
            }
            if (transactionTime && !isNaN(transactionTime.getTime())) {
                hasValidTimestamps = true;
            }
        });
        
        // If no transactions have valid timestamps, distribute all sales evenly
        if (!hasValidTimestamps && allTransactions.length > 0) {
            console.log('No transactions have valid timestamps, distributing sales evenly across all buckets');
            let totalSales = 0;
            allTransactions.forEach(t => {
                totalSales += window.getEffectiveTransactionTotalForShift(t, globalVoidStatusMap, globalVoidedItemsMap);
            });
            const salesPerBucket = totalSales / hours.length;
            console.log(`Distributing ₱${totalSales.toFixed(2)} evenly: ₱${salesPerBucket.toFixed(2)} per bucket`);
            return hours.map(() => salesPerBucket);
        }
        
        return hours.map((hour, hourIndex) => {
            // Calculate sales for each time period from current shift only
            let totalSales = 0;
            
            const hourTransactions = allTransactions.filter(t => {
                // Try multiple timestamp fields
                let transactionTime = null;
                
                if (t.timestamp) {
                    transactionTime = new Date(t.timestamp);
                } else if (t.created_at) {
                    transactionTime = new Date(t.created_at);
                } else if (t.date) {
                    transactionTime = new Date(t.date);
                }
                
                if (!transactionTime || isNaN(transactionTime.getTime())) {
                    // If no timestamp, put in the bucket that matches current hour
                    const currentHour = new Date().getHours();
                    const bucketStart = hour - 3;
                    const bucketEnd = hour;
                    let isInCurrentBucket = false;
                    
                    if (bucketStart < 0) {
                        isInCurrentBucket = currentHour >= 21 || currentHour < bucketEnd;
                    } else {
                        isInCurrentBucket = currentHour >= bucketStart && currentHour < bucketEnd;
                    }
                    
                    if (isInCurrentBucket) {
                        console.log(`Transaction without timestamp included in ${hour}:00 bucket (current hour: ${currentHour})`);
                    }
                    return isInCurrentBucket;
                }
                
                // Get local hour (not UTC)
                const transactionHour = transactionTime.getHours();
                const bucketStart = hour - 3;
                const bucketEnd = hour;
                
                // Handle edge case for 6AM bucket (3AM-6AM, which crosses midnight)
                let isInRange = false;
                if (bucketStart < 0) {
                    // 6AM bucket: 3AM-6AM becomes previous day 9PM (21) to current day 6AM
                    isInRange = transactionHour >= 21 || transactionHour < bucketEnd;
                } else {
                    isInRange = transactionHour >= bucketStart && transactionHour < bucketEnd;
                }
                
                if (isInRange) {
                    console.log(`Transaction at ${transactionHour}:00 included in ${hour}:00 bucket (range: ${bucketStart >= 0 ? bucketStart : '21(prev day)'}-${bucketEnd})`);
                }
                return isInRange;
            });
            
            console.log(`Hour ${hour}: Found ${hourTransactions.length} transactions`);
                
                totalSales = hourTransactions.reduce((sum, t) => {
                    return sum + window.getEffectiveTransactionTotalForShift(t, globalVoidStatusMap, globalVoidedItemsMap);
                }, 0);
            
            console.log(`Hour ${hour}: ${hourTransactions.length} shift transactions, Total: ₱${totalSales.toFixed(2)}`);
            return totalSales;
        });
    }
    
    // Helper function to ensure chart data is valid and non-zero if there are sales
    function validateChartData(hourlySales, totalSales) {
        // If we have sales but all buckets are zero, distribute sales to the most recent bucket
        const sumOfBuckets = hourlySales.reduce((sum, val) => sum + val, 0);
        if (totalSales > 0 && sumOfBuckets === 0) {
            console.warn('Sales exist but no buckets have data, distributing to most recent bucket');
            const currentHour = new Date().getHours();
            // Find the bucket that contains the current hour
            const hours = [6, 9, 12, 15, 18, 21];
            let targetBucketIndex = 0;
            
            for (let i = 0; i < hours.length; i++) {
                const bucketStart = hours[i] - 3;
                const bucketEnd = hours[i];
                if (bucketStart < 0) {
                    if (currentHour >= 21 || currentHour < bucketEnd) {
                        targetBucketIndex = i;
                        break;
                    }
                } else if (currentHour >= bucketStart && currentHour < bucketEnd) {
                    targetBucketIndex = i;
                    break;
                }
            }
            
            // If no bucket matches, use the last bucket
            if (targetBucketIndex === 0 && currentHour >= 21) {
                targetBucketIndex = hours.length - 1;
            }
            
            hourlySales[targetBucketIndex] = totalSales;
            console.log(`Distributed ₱${totalSales.toFixed(2)} to bucket ${hours[targetBucketIndex]}:00 (index ${targetBucketIndex})`);
        }
        
        return hourlySales;
    }

    // Refunds
    function initializeRefunds() {
        const refundBtn = document.getElementById('processRefundBtn');
        const searchReceiptBtn = document.getElementById('searchReceiptBtn');
        
        refundBtn.addEventListener('click', processRefund);
        searchReceiptBtn.addEventListener('click', searchReceipt);
    }

    function processRefund() {
        const receiptNumber = document.getElementById('receiptNumber').value;
        const reason = document.getElementById('refundReason').value;
        
        if (!receiptNumber) {
            showNotification('Please enter a receipt number', 'error');
            return;
        }

        // Find transaction
        const transaction = shiftData.transactions.find(t => t.receiptNumber === receiptNumber);
        if (!transaction) {
            showNotification('Receipt not found', 'error');
            return;
        }

        // Process refund
        const refundAmount = transaction.total;
        shiftData.totalSales -= refundAmount;
        shiftData.paymentMethods[transaction.paymentMethod] -= refundAmount;
        
        // Add refund to transactions
        shiftData.transactions.push({
            type: 'refund',
            receiptNumber: `REF-${Date.now()}`,
            originalReceipt: receiptNumber,
            total: -refundAmount,
            timestamp: new Date().toISOString(),
            reason: reason
        });

        localStorage.setItem('activeShift', JSON.stringify(shiftData));
        updateAnalytics();
        
        showNotification(`Refund of ₱${refundAmount.toFixed(2)} processed`, 'success');
        
        const modal = bootstrap.Modal.getInstance(document.getElementById('refundModal'));
        modal.hide();
    }

    function searchReceipt() {
        const receiptNumber = document.getElementById('receiptNumber').value;
        const transaction = shiftData.transactions.find(t => t.receiptNumber === receiptNumber);
        
        if (transaction) {
            displayReceiptDetails(transaction);
            document.getElementById('processRefundBtn').disabled = false;
        } else {
            showNotification('Receipt not found', 'error');
        }
    }

    function displayReceiptDetails(transaction) {
        const tbody = document.querySelector('#refundItemsTable tbody');
        tbody.innerHTML = transaction.items.map(item => `
            <tr>
                <td>${item.name}</td>
                <td>${item.quantity}</td>
                <td>₱${item.price.toFixed(2)}</td>
                <td>
                    <button class="btn btn-sm btn-outline-danger" onclick="refundItem(${item.id})">
                        Refund
                    </button>
                </td>
            </tr>
        `).join('');
        
        document.getElementById('receiptDetails').style.display = 'block';
    }

    // Enhanced Payment Processing
    // Enhanced Payment Processing - make it globally accessible
    window.processPayment = async function() {
        console.log('processPayment called');
        
        // Validation checks
        if (cart.length === 0) {
            showNotification('Cart is empty. Please add items first.', 'warning');
            return;
        }

        if (!shiftData || !shiftData.isActive) {
            showNotification('No active shift. Please start a shift first.', 'error');
            showStartShiftModal();
            return;
        }

        // Calculate original subtotal (before discount)
        const originalSubtotal = cart.reduce((sum, item) => {
            const itemPrice = parseFloat(item.price) || 0;
            const itemQty = parseInt(item.quantity) || 0;
            return sum + (itemPrice * itemQty);
        }, 0);
        
        // Calculate subtotal after item-level discounts
        const subtotal = cart.reduce((sum, item) => {
            const itemPrice = parseFloat(item.price) || 0;
            const itemQty = parseInt(item.quantity) || 0;
            const itemTotal = item.subtotal || item.total || (itemPrice * itemQty);
            return sum + (itemTotal || 0);
        }, 0);
        
        // Get the Total from Transaction Summary (this includes the 3% discount on Subtotal)
        const totalElement = document.getElementById('total');
        let grandTotal = 0;
        
        if (totalElement) {
            // Extract numeric value from the displayed total (e.g., "₱ 2,850.00" -> 2850.00)
            const totalText = totalElement.textContent.replace(/[₱,\s]/g, '');
            grandTotal = parseFloat(totalText) || 0;
        } else {
            // Fallback: use subtotal if element not found
            grandTotal = subtotal || 0;
        }
        
        // Calculate discount amount (for display/record keeping)
        const discountAmount = originalSubtotal - grandTotal;
        
        console.log('Totals:', { originalSubtotal, subtotal, discountAmount, grandTotal });
        console.log('Cart items:', cart);
        
        // Get amount tendered
        const amountTenderedInput = document.getElementById('amountTendered');
        let amountTendered = parseFloat(amountTenderedInput.value) || 0;
        
        // Validate Senior Citizen/PWD discount requires either ID image or manual input
        if (selectedDiscountType) {
            if (idVerificationMethod === 'image' && !discountIdImage) {
                showNotification('Please capture ID from scanner app or switch to manual entry', 'error');
                return;
            } else if (idVerificationMethod === 'manual') {
                const customerName = document.getElementById('discountCustomerName').value.trim();
                const idNumber = document.getElementById('discountIdNumber').value.trim();
                const issuingLgu = document.getElementById('discountIssuingLgu').value.trim();
                
                if (!customerName || !idNumber || !issuingLgu) {
                    showNotification('Please fill in all discount information fields', 'error');
                    return;
                }
            }
        }

        // Validate payment based on method
        if (selectedPaymentMethod === 'cash') {
            if (amountTendered < grandTotal) {
                showNotification(`Insufficient payment. Need ₱${(grandTotal || 0).toFixed(2)}`, 'error');
                amountTenderedInput.focus();
                return;
            }
        } else if (selectedPaymentMethod === 'e_wallet') {
            const senderName = document.getElementById('senderName').value.trim();
            const transactionId = document.getElementById('transactionId').value.trim();
            const provider = document.getElementById('ewalletProvider').value;
            if (!senderName) {
                showNotification('Please enter Name of the Sender', 'error');
                document.getElementById('senderName').focus();
                return;
            }
            if (!transactionId) {
                showNotification('Please enter Transaction ID', 'error');
                document.getElementById('transactionId').focus();
                return;
            }
        } else if (selectedPaymentMethod === 'check') {
            const checkNumber = document.getElementById('checkNumber').value.trim();
            const checkBank = document.getElementById('checkBank').value.trim();
            if (!checkNumber) {
                showNotification('Please enter Check Number', 'error');
                document.getElementById('checkNumber').focus();
                return;
            }
            if (!checkBank) {
                showNotification('Please enter Bank Name', 'error');
                document.getElementById('checkBank').focus();
                return;
            }
        }

        // Disable button to prevent double submission
        const processBtn = document.getElementById('processPayment');
        const originalText = processBtn.innerHTML;
        processBtn.disabled = true;
        processBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

        // Prepare payment details based on method
        let paymentDetails = {};
        if (selectedPaymentMethod === 'e_wallet') {
            paymentDetails = {
                provider: document.getElementById('ewalletProvider').value,
                sender_name: document.getElementById('senderName').value.trim(),
                reference_number: document.getElementById('transactionId').value.trim(),
                status: 'paid'
            };
        } else if (selectedPaymentMethod === 'check') {
            paymentDetails = {
                provider: document.getElementById('checkBank').value.trim(),
                reference_number: document.getElementById('checkNumber').value.trim(),
                status: 'pending'
            };
        }

        // Prepare discount information
        let discountInfo = {};
        if (selectedDiscountType) {
            discountInfo.discount_type = selectedDiscountType;
            if (capturedSeniorPwdDiscountId) {
                discountInfo.discount_id = capturedSeniorPwdDiscountId;
            }
            
            if (idVerificationMethod === 'image' && discountIdImage) {
                discountInfo.discount_id_image = discountIdImage;
            } else if (idVerificationMethod === 'manual') {
                discountInfo.customer_name = document.getElementById('discountCustomerName').value.trim();
                discountInfo.id_number = document.getElementById('discountIdNumber').value.trim();
                // ID Type is automatically set based on discount_type (OSCA for senior, PWD for pwd)
                discountInfo.id_type = selectedDiscountType === 'senior' ? 'OSCA' : 'PWD';
                discountInfo.issuing_lgu = document.getElementById('discountIssuingLgu').value.trim();
            }
        }

        // Prepare sale data - ensure all required fields are present
        const saleData = {
            items: cart.map(item => {
                const itemPrice = parseFloat(item.price) || 0;
                const itemQty = parseInt(item.quantity) || 0;
                // Always calculate total as quantity * price to ensure accuracy
                const itemTotal = parseFloat((itemPrice * itemQty).toFixed(2));
                return {
                    product_id: Number(item.id) > 0 ? Number(item.id) : 0,
                    item_id: item.item_id || null,
                    inventory_product_id: item.inventory_product_id || null,
                    barcode_id: item.barcode_id || null,
                    cashier_key: item.cashier_key || item.uniqueKey || null,
                    sku: item.sku || item.barcode || null,
                    source: item.source || null,
                    name: item.name || 'Unknown Item',
                    quantity: itemQty,
                    price: itemPrice,
                    price_type: item.price_type || item.category || null,
                    total: itemTotal,
                    discount: parseFloat(item.discount || 0)
                };
            }),
            subtotal: parseFloat((subtotal || 0).toFixed(2)),
            tax: 0,
            discount: parseFloat((discountAmount || 0).toFixed(2)),
            amount: parseFloat((grandTotal || 0).toFixed(2)),
            payment_method: selectedPaymentMethod,
            amount_tendered: selectedPaymentMethod === 'cash' ? parseFloat((amountTendered || 0).toFixed(2)) : parseFloat((grandTotal || 0).toFixed(2)),
            payment_details: paymentDetails,
            ...discountInfo
        };

        console.log('Sale data:', saleData);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                throw new Error('CSRF token not found');
            }

            const response = await fetch('/sales', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(saleData)
            });

            console.log('Response status:', response.status);
            
            // Check if response is OK before parsing JSON
            let data;
            try {
                data = await response.json();
            } catch (jsonError) {
                console.error('Error parsing JSON response:', jsonError);
                throw new Error(`Server error: ${response.status} ${response.statusText}`);
            }
            
            console.log('Response data:', data);

            // Check if response was successful
            if (!response.ok) {
                throw new Error(data.message || `Server error: ${response.status} ${response.statusText}`);
            }

            if (data.success) {
                // Calculate discount information - handle both subtotal and total properties
                const originalSubtotal = cart.reduce((sum, item) => {
                    const itemPrice = parseFloat(item.price) || 0;
                    const itemQty = parseInt(item.quantity) || 0;
                    return sum + (itemPrice * itemQty);
                }, 0);
                const discountAmount = Math.max(0, (originalSubtotal || 0) - (grandTotal || 0));
                
                // Get payment details
                let paymentInfo = {};
                if (selectedPaymentMethod === 'e_wallet') {
                    paymentInfo = {
                        provider: document.getElementById('ewalletProvider').value,
                        sender_name: document.getElementById('senderName').value.trim(),
                        reference_number: document.getElementById('transactionId').value.trim(),
                        status: 'PAID'
                    };
                } else if (selectedPaymentMethod === 'check') {
                    paymentInfo = {
                        provider: document.getElementById('checkBank').value.trim(),
                        reference_number: document.getElementById('checkNumber').value.trim(),
                        status: 'PENDING'
                    };
                }

                // Create transaction for local tracking — preserve discounted line totals for receipt
                const transactionItems = cart.map(item => {
                    const itemPrice = parseFloat(item.price) || 0;
                    const itemQty = parseInt(item.quantity) || 0;
                    const lineGross = parseFloat((itemPrice * itemQty).toFixed(2));
                    const itemTotal = parseFloat(
                        (item.subtotal ?? item.total ?? lineGross).toFixed(2)
                    );
                    return {
                        ...item,
                        price: itemPrice,
                        quantity: itemQty,
                        total: itemTotal,
                        subtotal: itemTotal
                    };
                });
                
                const transaction = {
                    id: data.sale.id,
                    receiptNumber: data.receipt_number || data.sale.receipt_number || 'N/A',
                    items: transactionItems,
                    originalSubtotal: originalSubtotal || 0,
                    discountAmount: discountAmount || 0,
                    subtotal: subtotal || 0,
                    tax: 0,
                    total: grandTotal || 0,
                    paymentMethod: selectedPaymentMethod,
                    amountTendered: amountTendered || 0,
                    paymentInfo: paymentInfo,
                    change: Math.max(0, parseFloat(((amountTendered - grandTotal) || 0).toFixed(2))),
                    timestamp: new Date().toISOString(),
                    cashier: shiftData.cashierName || 'Cashier'
                };

                // Update local shift data
                // CRITICAL: NEVER change startTime during sales - always use locked time
                // The locked time is set when shift starts and never changes until shift ends
                
                // Note: totalSales will be recalculated by updateAnalytics() which properly excludes voided items
                // Don't recalculate here as we don't have void status information yet
                // Just add the transaction and let updateAnalytics() handle the calculation
                
                // Update shiftData while preserving startTime
                // Don't update totalSales here - let updateAnalytics() calculate it correctly with void status
                shiftData.transactionCount = (shiftData.transactionCount || 0) + 1;
                shiftData.paymentMethods[selectedPaymentMethod] = (shiftData.paymentMethods[selectedPaymentMethod] || 0) + grandTotal;
                shiftData.transactions = shiftData.transactions || [];
                shiftData.transactions.push(transaction);
                
                // CRITICAL: Always use locked time - never change it during sales
                // If locked time doesn't exist, something is wrong - try to restore from localStorage
                if (lockedStartTime) {
                    shiftData.startTime = lockedStartTime;
                    console.log('🔒 Using locked startTime after sale (never changes):', lockedStartTime);
                } else {
                    // Try to restore locked time from localStorage
                    if (loadLockedStartTime()) {
                        shiftData.startTime = lockedStartTime;
                        console.log('🔒 Restored and using locked startTime after sale:', lockedStartTime);
                    } else {
                        // Last resort: use existing shiftData.startTime but don't change it
                        console.warn('⚠️ No locked startTime available, using existing shiftData.startTime:', shiftData.startTime);
                        // Don't update shiftData.startTime - keep what we have
                    }
                }
                
                // Ensure we're saving the locked time to localStorage
                if (lockedStartTime) {
                    shiftData.startTime = lockedStartTime;
                }
                console.log('💾 Saving shift data to localStorage with locked startTime:', shiftData.startTime);
                localStorage.setItem('activeShift', JSON.stringify(shiftData));
                
                // Transaction is already added to shiftData.transactions above
                // No need to add to todayTransactions since we're using shift-specific data
                console.log('Transaction added to shift data:', transaction);

                // Update local stock
                updateStock(cart);

                // Generate receipt
                generateReceipt(transaction);

                // Clear cart and leftover scanner session so items do not reappear on the next transaction
                cart = [];
                if (typeof clearCashierScanSession === 'function') {
                    clearCashierScanSession();
                }
                updateCartDisplay();
                amountTenderedInput.value = '';
                // Clear change display
                const changeDisplay = document.getElementById('changeDisplay');
                if (changeDisplay) {
                    changeDisplay.style.display = 'none';
                }
                // Recalculate change if calculateChange exists
                if (window.calculateChange) {
                    window.calculateChange();
                }

                // Update analytics immediately to refresh chart
                updateAnalytics();
                
                // Also update chart directly if it exists
                if (isChartValid()) {
                    try {
                        if (salesChart.data && salesChart.data.datasets && salesChart.data.datasets[0]) {
                            const hourlySales = getHourlySales();
                            salesChart.data.datasets[0].data = hourlySales;
                            salesChart.update('none');
                            console.log('Chart updated immediately after new sale');
                        }
                    } catch (chartError) {
                        console.error('Error updating chart after sale:', chartError);
                        salesChart = null;
                    }
                }
                
                // Re-enable button immediately after successful payment
                processBtn.disabled = false;
                processBtn.innerHTML = originalText;
                
                showNotification('✓ Payment processed successfully!', 'success');
                
                // Small delay to ensure database transaction is fully committed
                await new Promise(resolve => setTimeout(resolve, 100));
                
                // Reload products from server to sync quantities (with timeout to prevent hanging)
                try {
                    await Promise.race([
                        reloadProducts(),
                        new Promise((_, reject) => 
                            setTimeout(() => reject(new Error('Product reload timeout')), 10000)
                        )
                    ]);
                } catch (reloadError) {
                    console.warn('Product reload failed or timed out:', reloadError);
                    // Don't show error to user as payment was already successful
                }
            } else {
                throw new Error(data.message || 'Failed to process payment');
            }
        } catch (error) {
            console.error('Error processing payment:', error);
            
            // Show user-friendly error message
            let errorMessage = 'Failed to process payment. Please try again.';
            if (error.message) {
                errorMessage = error.message;
            } else if (error instanceof TypeError && error.message.includes('fetch')) {
                errorMessage = 'Network error. Please check your connection and try again.';
            }
            
            showNotification(`Error: ${errorMessage}`, 'error');
        } finally {
            // Always re-enable button, even if there was an error
            if (processBtn) {
            processBtn.disabled = false;
            processBtn.innerHTML = originalText;
            }
        }
    }

    // Reload products from server to sync quantities
    async function reloadProducts() {
        try {
            console.log('Reloading products from server...');
            const response = await fetch('/cashier/products');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            
            if (data.success && data.products) {
                console.log('Received products from server:', data.products.length);
                
                // Store currently selected product info before clearing
                const productDetails = document.getElementById('productDetails');
                const isProductSelected = productDetails && productDetails.style.display !== 'none';
                let selectedProductName = null;
                let selectedProductId = null;
                
                if (isProductSelected && window.selectedProductRef) {
                    const currentSelected = window.selectedProductRef();
                    if (currentSelected) {
                        selectedProductName = currentSelected.name;
                        selectedProductId = currentSelected.id;
                        console.log('Currently selected product:', selectedProductName, 'ID:', selectedProductId, 'Current stock:', currentSelected.stock_quantity);
                    }
                }
                
                // Clear existing products
                const oldProductCount = Object.keys(products).length;
                Object.keys(products).forEach(key => delete products[key]);
                console.log('Cleared', oldProductCount, 'old products');
                
                // Reload products from server with duplicate handling
                const reloadDuplicateIds = [];
                const reloadIdCounter = {};
                
                data.products.forEach((product) => {
                    indexCashierProduct(product);
                });
                
                // Log summary of duplicates in reload (only in development mode)
                if (reloadDuplicateIds.length > 0) {
                    const uniqueReloadDuplicateIds = [...new Set(reloadDuplicateIds)];
                    // Only show warnings in development mode
                    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                        console.warn(`⚠️ Reload: Found ${uniqueReloadDuplicateIds.length} duplicate product ID(s). Products have been assigned unique keys.`);
                        console.warn('Duplicate IDs:', uniqueReloadDuplicateIds);
                    }
                    // In production, silently handle duplicates without logging
                }
                
                console.log('Products reloaded from server:', Object.keys(products).length);
                
                // Log face mask products after reload
                const faceMaskAfterReload = Object.values(products).filter(p => 
                    p && p.name && p.name.toLowerCase().includes('face mask')
                );
                console.log('Face mask products after reload:', faceMaskAfterReload.length);
                if (faceMaskAfterReload.length !== 20) {
                    console.warn('⚠️ After reload: Expected 20 face mask items but found', faceMaskAfterReload.length);
                    console.log('Face mask items:', faceMaskAfterReload.map(p => ({ id: p.id, name: p.name })));
                }
                
                // Log test1 product specifically from API response
                const test1FromAPI = data.products.find(p => p.name && p.name.toLowerCase() === 'test1');
                if (test1FromAPI) {
                    console.log('🔍 test1 from API response:', {
                        id: test1FromAPI.id,
                        name: test1FromAPI.name,
                        stock_quantity: test1FromAPI.stock_quantity,
                        quantity_on_hand: test1FromAPI.quantity_on_hand,
                        raw_data: test1FromAPI
                    });
                    console.log('🔍 API debug info:', data.debug);
                } else {
                    console.log('⚠️ test1 NOT found in API response');
                    console.log('🔍 Available products:', data.products.map(p => p.name).slice(0, 10));
                    console.log('🔍 API debug info:', data.debug);
                }
                
                // Log test1 product from reloaded products object
                const test1AfterReload = Object.values(products).find(p => p.name && p.name.toLowerCase() === 'test1');
                if (test1AfterReload) {
                    console.log('✅ test1 in products object after reload:', {
                        id: test1AfterReload.id,
                        name: test1AfterReload.name,
                        stock_quantity: test1AfterReload.stock_quantity,
                        quantity_on_hand: test1AfterReload.quantity_on_hand
                    });
                } else {
                    console.log('❌ test1 NOT found in products object after reload');
                }
                
                // Log all products to verify stock quantities (for debugging)
                console.log('All products after reload:', Object.values(products).map(p => ({
                    id: p.id,
                    name: p.name,
                    stock_quantity: p.stock_quantity
                })));
                
                // Refresh selected product display if one was selected
                if (isProductSelected && selectedProductName && window.selectProduct) {
                    // Try to find the product by ID first, then by name
                    let updatedProduct = findStoredCashierProduct({
                        id: selectedProductId,
                        name: selectedProductName
                    }) || Object.values(products).find(p => p.name === selectedProductName);
                    
                    if (updatedProduct) {
                        // Re-select the product to refresh the display with updated stock
                        window.selectProduct(updatedProduct);
                        console.log('Refreshed selected product:', updatedProduct.name, 'Stock:', updatedProduct.stock_quantity);
                    } else {
                        console.warn('Could not refresh selected product - product not found in reloaded data');
                    }
                } else {
                    console.log('No product selected or selectProduct not available');
                }
                
                // Refresh search results if they're visible - force refresh even if input is empty
                const searchResults = document.getElementById('searchResults');
                const barcodeInput = document.getElementById('barcodeInput');
                if (searchResults && searchResults.classList.contains('show')) {
                    // If search results are visible, refresh them
                    if (barcodeInput && barcodeInput.value.trim().length >= 2) {
                        // Trigger search again to refresh results
                        barcodeInput.dispatchEvent(new Event('input'));
                    } else {
                        // Clear search results if input is empty
                        searchResults.classList.remove('show');
                        searchResults.innerHTML = '';
                    }
                }
                
                // Also refresh the product details card stock badge if visible (even if product not selected)
                const productDetailsCard = document.getElementById('productDetails');
                const stockBadge = document.getElementById('stockBadge');
                if (productDetailsCard && productDetailsCard.style.display !== 'none' && stockBadge) {
                    const productNameElement = document.getElementById('productName');
                    if (productNameElement && productNameElement.textContent) {
                        const productName = productNameElement.textContent.trim();
                        const updatedProduct = Object.values(products).find(p => p.name === productName);
                        if (updatedProduct) {
                            // Update stock badge
                            if (updatedProduct.stock_quantity > updatedProduct.min_stock_level) {
                                stockBadge.className = 'badge bg-success';
                                stockBadge.textContent = `${updatedProduct.stock_quantity} in stock`;
                            } else {
                                stockBadge.className = 'badge bg-warning';
                                stockBadge.textContent = `${updatedProduct.stock_quantity} low stock`;
                            }
                            console.log('Updated stock badge for:', productName, 'New stock:', updatedProduct.stock_quantity);
                        }
                    }
                }
            } else {
                console.error('Failed to reload products:', data);
            }
        } catch (error) {
            console.error('Error reloading products:', error);
        }
    }

    function updateStock(items) {
        items.forEach(item => {
            const qty = parseInt(item.quantity) || 0;
            if (!qty) return;

            const stored = findStoredCashierProduct(item);
            if (!stored) return;

            stored.stock_quantity = Math.max(0, (parseFloat(stored.stock_quantity) || 0) - qty);
            stored.quantity_on_hand = stored.stock_quantity;
        });
    }
    
    // Make reloadProducts accessible globally
    window.reloadProducts = reloadProducts;
    
    // Reload products from server on page load to ensure we have the latest data from item_lists table
    // This ensures the cashier always shows the current stock, not cached data
    // IMMEDIATELY reload products from server on page load (no delay)
    // This ensures the cashier always shows the current stock from item_lists table
    console.log('🔄 Immediately reloading products from server to get latest item_lists quantities...');
    reloadProducts().then(() => {
        console.log('✅ Products reloaded on page load - cashier should now show correct stock from item_lists table');
        
        // Log test1 again after reload to verify
        const test1AfterReload = Object.values(products).find(p => p.name && p.name.toLowerCase() === 'test1');
        if (test1AfterReload) {
            console.log('📊 test1 product after page load reload:', {
                id: test1AfterReload.id,
                name: test1AfterReload.name,
                stock_quantity: test1AfterReload.stock_quantity,
                quantity_on_hand: test1AfterReload.quantity_on_hand
            });
            
            // If test1 is currently displayed, refresh it
            const productDetailsCard = document.getElementById('productDetails');
            const productNameElement = document.getElementById('productName');
            if (productDetailsCard && productNameElement && productNameElement.textContent.trim().toLowerCase() === 'test1') {
                console.log('🔄 Refreshing test1 display with updated stock:', test1AfterReload.stock_quantity);
                if (window.selectProduct) {
                    window.selectProduct(test1AfterReload);
                }
            }
        } else {
            console.log('⚠️ test1 product NOT found after page load reload');
        }
    }).catch(err => {
        console.error('❌ Error reloading products on page load:', err);
    });

    /**
     * 80mm thermal (~72mm print) — use 42 columns so lines fit Font A without clipping.
     * Screen/preview may use UTF-8 ₱; raw ESC/POS often expects Latin/CP437 — use asciiCurrency for thermal.
     */
    const THERMAL80_WIDTH = 42;
    function thermal80Separator() {
        return '-'.repeat(THERMAL80_WIDTH);
    }
    function receiptItemLine80mm(item, pricePrefix) {
        const cur = pricePrefix != null ? pricePrefix : '₱';
        const parsed = parseFloat(item.total);
        const lineAmt = !isNaN(parsed) ? parsed : (parseFloat(item.price || 0) * parseFloat(item.quantity || 0));
        const priceStr = cur + lineAmt.toFixed(2);
        const qtyPart = 'x' + (parseFloat(item.quantity) || 0);
        const rawName = String(item.name || 'Item').trim();
        const maxLeft = THERMAL80_WIDTH - priceStr.length - 1;
        let left = rawName + ' ' + qtyPart;
        if (left.length > maxLeft) {
            const nameBudget = Math.max(4, maxLeft - qtyPart.length - 3);
            left = (rawName.length > nameBudget ? rawName.substring(0, nameBudget - 2) + '..' : rawName) + ' ' + qtyPart;
            if (left.length > maxLeft) left = left.substring(0, maxLeft);
        }
        const pad = THERMAL80_WIDTH - left.length - priceStr.length;
        return left + (pad > 0 ? ' '.repeat(pad) : ' ') + priceStr;
    }
    function receiptMoneyRow80(label, rightPart) {
        const L = String(label);
        const R = String(rightPart);
        const sp = THERMAL80_WIDTH - L.length - R.length;
        if (sp < 1) {
            return (L + ' ' + R).substring(0, THERMAL80_WIDTH) + '\n';
        }
        return L + ' '.repeat(sp) + R + '\n';
    }

    /**
     * Plain-text receipt. options.asciiCurrency: true for USB/BT thermal (PHP + digits only — no UTF-8 mojibake).
     */
    function buildReceiptFormattedText(transaction, options) {
        options = options || {};
        const asciiCurrency = !!options.asciiCurrency;
        const cur = asciiCurrency ? 'PHP ' : '₱';
        const curNeg = asciiCurrency ? '-PHP ' : '-₱';

        const W = THERMAL80_WIDTH;
        const sep = thermal80Separator();
        const center = function(text) {
            const t = String(text);
            if (t.length <= W) {
                const pad = W - t.length;
                const leftPad = Math.floor(pad / 2);
                return ' '.repeat(leftPad) + t + ' '.repeat(pad - leftPad);
            }
            const lines = [];
            for (let i = 0; i < t.length; i += W) {
                const chunk = t.slice(i, i + W);
                const pad = W - chunk.length;
                const leftPad = Math.floor(pad / 2);
                lines.push(' '.repeat(leftPad) + chunk + ' '.repeat(pad - leftPad));
            }
            return lines.join('\n');
        };

        const ts = transaction.timestamp ? new Date(transaction.timestamp) : new Date();
        const when = isNaN(ts.getTime()) ? new Date() : ts;
        const dateStr = when.toLocaleString();

        let out = '';
        out += center('Redemp Medical Supply') + '\n';
        out += center('Ponce St., Prk.5, Brgy. 28-C Pob. Dist. Davao City') + '\n';
        out += sep + '\n';
        out += center('Receipt #: ' + (transaction.receiptNumber || 'N/A')) + '\n';
        out += sep + '\n';
        out += center('Date: ' + dateStr) + '\n';
        out += sep + '\n';
        out += 'Cashier: ' + (transaction.cashier || 'Cashier') + '\n';
        out += sep + '\n';

        const items = transaction.items || [];
        let originalSubtotal = 0;
        if (transaction.originalSubtotal !== undefined) {
            originalSubtotal = parseFloat(transaction.originalSubtotal) || 0;
        } else {
            originalSubtotal = items.reduce(function(sum, item) {
                return sum + (parseFloat(item.price) * parseFloat(item.quantity));
            }, 0);
        }
        let discountAmount = 0;
        if (transaction.discountAmount !== undefined && !isNaN(parseFloat(transaction.discountAmount))) {
            discountAmount = Math.max(0, parseFloat(transaction.discountAmount));
        }
        if (discountAmount < 0.0001) {
            const totalDue = parseFloat(transaction.total);
            if (!isNaN(totalDue) && originalSubtotal > totalDue + 0.0001) {
                discountAmount = originalSubtotal - totalDue;
            } else {
                const st = parseFloat(transaction.subtotal);
                discountAmount = Math.max(0, originalSubtotal - (isNaN(st) ? originalSubtotal : st));
            }
        }
        const hasDiscount = discountAmount > 0.0001;
        let subtotalAfterDiscount = Math.max(0, Math.round((originalSubtotal - discountAmount) * 100) / 100);
        const totalNum = parseFloat(transaction.total);
        const taxNum = parseFloat(transaction.tax);
        const noTax = isNaN(taxNum) || taxNum === 0;
        if (noTax && !isNaN(totalNum)) {
            subtotalAfterDiscount = Math.round(totalNum * 100) / 100;
        }

        items.forEach(function(item) {
            out += receiptItemLine80mm(item, cur) + '\n';
            if (item.discount && item.discount > 0) {
                const itemDiscountAmount = (parseFloat(item.price) * parseFloat(item.quantity)) - parseFloat(item.total);
                out += receiptMoneyRow80('  Disc: ' + item.discount + '%', curNeg + itemDiscountAmount.toFixed(2));
            }
        });

        out += sep + '\n';
        out += receiptMoneyRow80('Subtotal :', cur + originalSubtotal.toFixed(2));
        if (hasDiscount) {
            out += receiptMoneyRow80('Discount :', curNeg + discountAmount.toFixed(2));
        }
        out += receiptMoneyRow80('Subtotal After Discount :', cur + subtotalAfterDiscount.toFixed(2));

        out += sep + '\n';
        out += receiptMoneyRow80('Total :', cur + (parseFloat(transaction.total) || 0).toFixed(2));

        let paymentMethodDisplay = '';
        if (transaction.paymentInfo && transaction.paymentInfo.provider) {
            paymentMethodDisplay = transaction.paymentInfo.provider;
        } else if (transaction.paymentMethod === 'check') {
            paymentMethodDisplay = 'Check';
        } else {
            paymentMethodDisplay = (transaction.paymentMethod || 'cash').toUpperCase();
        }

        out += sep + '\n';
        out += receiptMoneyRow80('Payment Method :', paymentMethodDisplay);

        if (transaction.paymentMethod === 'e_wallet' && transaction.paymentInfo) {
            out += receiptMoneyRow80('Sender Name :', String(transaction.paymentInfo.sender_name || 'N/A'));
            out += receiptMoneyRow80('Transaction ID :', String(transaction.paymentInfo.reference_number || 'N/A'));
            out += receiptMoneyRow80('Status :', String(transaction.paymentInfo.status || 'PAID'));
        } else if (transaction.paymentMethod === 'check' && transaction.paymentInfo) {
            out += receiptMoneyRow80('Check No :', String(transaction.paymentInfo.reference_number || 'N/A'));
            out += receiptMoneyRow80('Bank :', String(transaction.paymentInfo.provider || 'N/A'));
            out += receiptMoneyRow80('Status :', String(transaction.paymentInfo.status || 'PENDING'));
        } else if (transaction.paymentMethod === 'cash') {
            out += receiptMoneyRow80('Amount Tendered :', cur + parseFloat(transaction.amountTendered || 0).toFixed(2));
            out += sep + '\n';
            out += receiptMoneyRow80('Change :', cur + parseFloat(transaction.change || 0).toFixed(2));
        }

        out += sep + '\n';
        out += center('Thank you for your purchase!') + '\n';
        return out;
    }

    function buildReceiptPreviewInnerHtml(transaction) {
        const raw = buildReceiptFormattedText(transaction, { asciiCurrency: false });
        return '<div class="receipt-preview receipt-print"><pre class="receipt-plain">' + escapeReceiptPre(raw) + '</pre></div>';
    }

    function generateReceipt(transaction) {
        document.getElementById('receiptContent').innerHTML = buildReceiptPreviewInnerHtml(transaction);
        
        const modal = new bootstrap.Modal(document.getElementById('receiptModal'));
        modal.show();
        
        // Setup print receipt button
        const printBtn = document.getElementById('printReceiptBtn');
        if (printBtn) {
            printBtn.onclick = function() {
                printReceiptFromModal(transaction);
            };
        }
    }

    // Print Receipt Functions
    function printReceiptFromModal(transaction) {
        const printerType = document.getElementById('printerType')?.value || 'browser';
        
        switch(printerType) {
            case 'thermal':
                printThermalReceipt(transaction);
                break;
            case 'bluetooth':
                printBluetoothReceipt(transaction);
                break;
            case 'browser':
            default:
                printBrowserReceipt(transaction);
                break;
        }
    }

    function printBrowserReceipt(transaction) {
        const printWindow = window.open('', '_blank', 'width=420,height=720');
        const posCssLink = document.querySelector('link[href*="pos.css"]');
        const posCssHref = posCssLink ? posCssLink.href : '';

        const inner = buildReceiptPreviewInnerHtml(transaction);
        const titleEsc = escapeHtml(transaction.receiptNumber || 'Receipt');

        let html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Receipt - ' + titleEsc + '</title>';
        if (posCssHref) {
            html += '<link rel="stylesheet" href="' + posCssHref + '">';
        }
        html += '<style>';
        html += '@media print { @page { margin: 1mm; size: 80mm auto; } body { background: #fff !important; padding: 0 !important; margin: 0 !important; } }';
        html += 'body { margin: 0; padding: 8px; background: #f8f9fa; -webkit-print-color-adjust: exact; print-color-adjust: exact; }';
        html += '</style></head><body>';
        html += '<div class="receipt-print-wrap">' + inner + '</div>';
        html += '</body></html>';

        printWindow.document.write(html);
        printWindow.document.close();

        setTimeout(() => {
            printWindow.print();
            setTimeout(() => printWindow.close(), 1000);
        }, 250);
    }

    function formatReceiptText(transaction) {
        return buildReceiptFormattedText(transaction, { asciiCurrency: true });
    }

    /** ESC/POS bytes shared by USB serial and Bluetooth thermal (80mm, feed + cut). */
    function buildEscPos80mmReceiptPayload(transaction) {
        const ESC = '\x1B';
        const GS = '\x1D';
        let commands = '';
        commands += ESC + '@';
        commands += ESC + '!' + '\x00';
        commands += ESC + 'a' + '\x00';
        commands += formatReceiptText(transaction);
        commands += '\n';
        commands += ESC + 'd' + '\x02';
        commands += GS + 'V' + '\x00';
        return new TextEncoder().encode(commands);
    }

    function serialPortUsbKey(port) {
        try {
            const info = port.getInfo();
            if (info.usbVendorId != null && info.usbProductId != null) {
                return String(info.usbVendorId) + ':' + String(info.usbProductId);
            }
        } catch (e) { /* ignore */ }
        return null;
    }

    async function printThermalReceipt(transaction) {
        try {
            if (!navigator.serial) {
                showNotification('Web Serial API not supported. Please use Chrome/Edge browser for thermal printing.', 'warning');
                printBrowserReceipt(transaction);
                return;
            }

            const STORAGE_KEY = 'pos_serial_printer_usb';
            let port = null;
            const grantedPorts = await navigator.serial.getPorts();
            const savedKey = localStorage.getItem(STORAGE_KEY);

            if (savedKey) {
                for (let i = 0; i < grantedPorts.length; i++) {
                    if (serialPortUsbKey(grantedPorts[i]) === savedKey) {
                        port = grantedPorts[i];
                        break;
                    }
                }
            }
            if (!port && grantedPorts.length === 1) {
                port = grantedPorts[0];
                const k = serialPortUsbKey(port);
                if (k) {
                    try { localStorage.setItem(STORAGE_KEY, k); } catch (e) { /* ignore */ }
                }
            }

            if (!port) {
                showNotification('Select your USB thermal printer once…', 'info');
                port = await navigator.serial.requestPort();
                const k = serialPortUsbKey(port);
                if (k) {
                    try { localStorage.setItem(STORAGE_KEY, k); } catch (e) { /* ignore */ }
                }
            } else {
                showNotification('Printing…', 'info');
            }

            // Typical: 9600. If you see garbled text, set the printer to 9600 in Windows, or try 115200 via vendor tool and change this baudRate.
            await port.open({ baudRate: 9600, dataBits: 8, stopBits: 1, parity: 'none' });

            const writer = port.writable.getWriter();
            await writer.write(buildEscPos80mmReceiptPayload(transaction));
            writer.releaseLock();
            await port.close();

            showNotification('Receipt sent to RPP02N.', 'success');
        } catch (error) {
            console.error('Thermal print error:', error);
            showNotification('Failed to print. Using browser print instead.', 'warning');
            printBrowserReceipt(transaction);
        }
    }

    async function printBluetoothReceipt(transaction) {
        try {
            if (!navigator.bluetooth) {
                showNotification('Web Bluetooth API not supported. Please use Chrome/Edge browser.', 'warning');
                printBrowserReceipt(transaction);
                return;
            }

            const BT_DEVICE_STORAGE = 'pos_bt_printer_device_id';
            const btFilters = {
                filters: [
                    { services: ['000018f0-0000-1000-8000-00805f9b34fb'] },
                ],
                optionalServices: [
                    '00001800-0000-1000-8000-00805f9b34fb',
                    '49535343-fe7d-4ae5-8fa9-9fafd205e455',
                ],
            };

            let device = null;
            let granted = [];

            if (typeof navigator.bluetooth.getDevices === 'function') {
                try {
                    granted = await navigator.bluetooth.getDevices();
                } catch (e) {
                    console.warn('Bluetooth getDevices', e);
                    granted = [];
                }
            }

            const savedId = localStorage.getItem(BT_DEVICE_STORAGE);
            if (savedId && granted.length) {
                device = granted.find(function (d) { return d.id === savedId; }) || null;
            }
            if (!device && granted.length > 0) {
                device = granted[0];
                try { localStorage.setItem(BT_DEVICE_STORAGE, device.id); } catch (e) { /* ignore */ }
            }

            if (!device) {
                showNotification('Choose your Bluetooth thermal printer once…', 'info');
                device = await navigator.bluetooth.requestDevice(btFilters);
                try { localStorage.setItem(BT_DEVICE_STORAGE, device.id); } catch (e) { /* ignore */ }
            } else {
                showNotification('Printing…', 'info');
            }

            const server = await device.gatt.connect();
            const service = await server.getPrimaryService('000018f0-0000-1000-8000-00805f9b34fb');
            const characteristic = await service.getCharacteristic('00002af1-0000-1000-8000-00805f9b34fb');

            const payload = buildEscPos80mmReceiptPayload(transaction);
            const chunkSize = 20;
            for (let offset = 0; offset < payload.length; offset += chunkSize) {
                const chunk = payload.slice(offset, offset + chunkSize);
                await characteristic.writeValue(chunk);
            }

            showNotification('Receipt sent to Bluetooth printer.', 'success');
        } catch (error) {
            console.error('Bluetooth print error:', error);
            if (error.name === 'NotFoundError') {
                showNotification('Bluetooth printer not found. Please ensure printer is paired and turned on.', 'error');
            } else if (error.name === 'SecurityError') {
                showNotification('Bluetooth permission denied. Please allow Bluetooth access.', 'error');
            } else {
                showNotification('Failed to print via Bluetooth. Using browser print instead.', 'warning');
                printBrowserReceipt(transaction);
            }
        }
    }

    function showShiftReport() {
        console.log('showShiftReport called');
        
        if (!shiftData || !shiftData.isActive) {
            showNotification('No active shift data available', 'warning');
            return;
        }

        const modal = new bootstrap.Modal(document.getElementById('shiftReportModal'));
        generateShiftReportHTML();
        modal.show();
    }

    function generateShiftReportHTML() {
        // The backend returns raw database value (format: "2026-01-14 15:22:00")
        // This is already in Asia/Manila time, so parse and format directly
        let startTimeFormatted = '';
        let startTimeDate = null;
        
        if (shiftData.startTime) {
            try {
                const timeStr = shiftData.startTime;
                
                // Extract date and time parts from datetime string
                let datePart = '';
                let timePart = '';
                
                if (timeStr.includes('T')) {
                    // ISO format: "2026-01-14T15:22:00" or "2026-01-14T15:22:00Z"
                    const parts = timeStr.split('T');
                    datePart = parts[0];
                    timePart = parts[1].replace(/Z$/, '').split('.')[0];
                } else if (timeStr.includes(' ')) {
                    // MySQL format: "2026-01-14 15:22:00"
                    const parts = timeStr.split(' ');
                    datePart = parts[0];
                    timePart = parts[1];
                }
                
                // Parse date components
                if (datePart && timePart) {
                    const [year, month, day] = datePart.split('-');
                    const [hours, minutes, seconds] = timePart.split(':');
                    
                    // Create date object treating the datetime as Asia/Manila time
                    // Use ISO string with +08:00 timezone offset to ensure correct parsing
                    const isoString = `${year}-${month}-${day}T${hours}:${minutes}:${seconds || '00'}+08:00`;
                    startTimeDate = new Date(isoString);
                    
                    // Format date and time using Asia/Manila timezone
                    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                                       'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    const monthName = monthNames[parseInt(month) - 1];
                    const dayNum = parseInt(day);
                    
                    // Convert to 12-hour format (using the parsed values which are already in Asia/Manila)
                    const hour24 = parseInt(hours);
                    const hour12 = hour24 === 0 ? 12 : (hour24 > 12 ? hour24 - 12 : hour24);
                    const ampm = hour24 >= 12 ? 'PM' : 'AM';
                    const min = minutes.padStart(2, '0');
                    const sec = seconds ? seconds.padStart(2, '0') : '00';
                    
                    startTimeFormatted = `${monthName} ${dayNum}, ${year}, ${hour12}:${min}:${sec} ${ampm}`;
                }
            } catch (error) {
                console.error('Error parsing start time:', error);
                startTimeFormatted = shiftData.startTime;
            }
        }
        
        // Get current time in Asia/Manila
        const currentTime = new Date();
        const currentTimeOptions = {
            timeZone: 'Asia/Manila',
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        };
        const currentTimeFormatted = currentTime.toLocaleString('en-US', currentTimeOptions);
        
        // Calculate duration - use startTimeDate if available, otherwise parse from string
        let duration = 0;
        if (startTimeDate && !isNaN(startTimeDate.getTime())) {
            duration = Math.floor((currentTime - startTimeDate) / 1000 / 60); // minutes
        } else if (shiftData.startTime) {
            // Fallback: parse as Asia/Manila time
            try {
                const timeStr = shiftData.startTime;
                let datePart = '';
                let timePart = '';
                
                if (timeStr.includes('T')) {
                    const parts = timeStr.split('T');
                    datePart = parts[0];
                    timePart = parts[1].replace(/Z$/, '').split('.')[0];
                } else if (timeStr.includes(' ')) {
                    const parts = timeStr.split(' ');
                    datePart = parts[0];
                    timePart = parts[1];
                }
                
                if (datePart && timePart) {
                    const [year, month, day] = datePart.split('-');
                    const [hours, minutes, seconds] = timePart.split(':');
                    const isoString = `${year}-${month}-${day}T${hours}:${minutes}:${seconds || '00'}+08:00`;
                    const parsed = new Date(isoString);
                    if (!isNaN(parsed.getTime())) {
                        duration = Math.floor((currentTime - parsed) / 1000 / 60);
                    }
                }
            } catch (error) {
                console.error('Error parsing start time for duration:', error);
            }
        }
        const hours = Math.floor(duration / 60);
        const minutes = duration % 60;

        const totalSales = shiftData.totalSales || 0;
        const transactionCount = shiftData.transactions ? shiftData.transactions.length : 0;
        const paymentMethods = shiftData.paymentMethods || { cash: 0 };
        const openingCash = shiftData.openingCash || 0;
        const expectedCash = openingCash + paymentMethods.cash;

        const reportHTML = `
            <div class="shift-report">
                <!-- Header -->
                <div class="text-center mb-4">
                    <h3>REDEMP MEDICAL SUPPLIES</h3>
                    <h5>Shift Report</h5>
                    <p class="text-muted">Generated: ${currentTime.toLocaleString()}</p>
                </div>

                <!-- Shift Details -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="bi bi-person me-2"></i>Shift Information</h6>
                </div>
                <div class="card-body">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td><strong>Cashier:</strong></td>
                                        <td>${shiftData.cashierName || '{{ Auth::user()->name }}'}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Start Time:</strong></td>
                                        <td>${startTimeFormatted}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Current Time:</strong></td>
                                        <td>${currentTimeFormatted}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Duration:</strong></td>
                                        <td>${hours}h ${minutes}m</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td><span class="badge bg-success">Active</span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                        <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="bi bi-cash-stack me-2"></i>Cash Summary</h6>
                        </div>
                            <div class="card-body">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td><strong>Opening Cash:</strong></td>
                                        <td class="text-end">₱${openingCash.toFixed(2)}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Cash Sales:</strong></td>
                                        <td class="text-end">₱${paymentMethods.cash.toFixed(2)}</td>
                                    </tr>
                                    <tr class="border-top">
                                        <td><strong>Expected Cash:</strong></td>
                                        <td class="text-end"><strong>₱${expectedCash.toFixed(2)}</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Summary -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Sales Summary</h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                        <div class="col-md-3">
                                        <h2 class="text-primary">₱${totalSales.toFixed(2)}</h2>
                                        <p class="text-muted">Total Sales</p>
                        </div>
                                    <div class="col-md-3">
                                        <h2 class="text-success">${transactionCount}</h2>
                                        <p class="text-muted">Transactions</p>
                        </div>
                                    <div class="col-md-3">
                                        <h2 class="text-info">₱${(transactionCount > 0 ? totalSales / transactionCount : 0).toFixed(2)}</h2>
                                        <p class="text-muted">Avg Transaction</p>
                        </div>
                                    <div class="col-md-3">
                                        <h2 class="text-warning">${duration > 0 ? (transactionCount / (duration / 60)).toFixed(1) : 0}</h2>
                                        <p class="text-muted">Trans/Hour</p>
                                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

                <!-- Payment Methods Breakdown -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-warning">
                                <h6 class="mb-0"><i class="bi bi-credit-card me-2"></i>Payment Methods</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    ${Object.entries(paymentMethods).map(([method, amount]) => `
                                        <div class="col-md-3">
                                            <div class="p-3 border rounded text-center">
                                                <h5 class="text-capitalize">${method}</h5>
                                                <h4 class="text-primary">₱${amount.toFixed(2)}</h4>
                                                <small class="text-muted">${totalSales > 0 ? ((amount / totalSales) * 100).toFixed(1) : 0}%</small>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <h6 class="mb-0"><i class="bi bi-receipt me-2"></i>Recent Transactions (Last 10)</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Receipt No.</th>
                                                <th>Time</th>
                                                <th>Items</th>
                                                <th>Payment Method</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${(shiftData.transactions || []).slice(-10).reverse().map((txn, index) => `
                                                <tr>
                                                    <td>${index + 1}</td>
                                                    <td>${txn.receiptNumber}</td>
                                                    <td>${new Date(txn.timestamp).toLocaleTimeString()}</td>
                                                    <td>${txn.items.length} items</td>
                                                    <td><span class="badge bg-secondary">${txn.paymentMethod.toUpperCase()}</span></td>
                                                    <td class="text-end">₱${txn.total.toFixed(2)}</td>
                                                </tr>
                                            `).join('') || '<tr><td colspan="6" class="text-center text-muted">No transactions yet</td></tr>'}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('shiftReportContent').innerHTML = reportHTML;
    }

    function generateShiftReport(closingCash, difference) {
        const report = {
            shiftId: shiftData.id || Date.now(),
            startTime: shiftData.startTime,
            endTime: new Date().toISOString(),
            openingCash: shiftData.openingCash,
            closingCash: closingCash,
            difference: difference,
            totalSales: shiftData.totalSales,
            transactions: shiftData.transactions.length,
            paymentMethods: shiftData.paymentMethods,
            cashier: shiftData.cashierName || '{{ Auth::user()->name }}'
        };

        console.log('Shift Report:', report);
        
        // Store report for later access
        localStorage.setItem('lastShiftReport', JSON.stringify(report));
        
        showNotification('Shift report generated', 'success');
    }

    // Print shift report
    window.printShiftReport = function() {
        const printContent = document.getElementById('shiftReportContent').innerHTML;
        const printWindow = window.open('', '', 'height=600,width=800');
        
        printWindow.document.write('<html><head><title>Shift Report</title>');
        printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">');
        printWindow.document.write('<style>@media print { .no-print { display: none; } body { padding: 20px; } }</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write(printContent);
        printWindow.document.write('<' + 'script>window.print(); window.onafterprint = function(){ window.close(); }<' + '/script>');
        printWindow.document.write('</body></html>');
        printWindow.document.close();
    };

    // Export shift report to CSV
    window.exportShiftReportCSV = function() {
        if (!shiftData || !shiftData.transactions) {
            showNotification('No shift data to export', 'warning');
            return;
        }

        let csv = 'Receipt Number,Time,Items,Payment Method,Amount\n';
        
        shiftData.transactions.forEach(txn => {
            csv += `${txn.receiptNumber},${new Date(txn.timestamp).toLocaleString()},${txn.items.length},${txn.paymentMethod},${txn.total}\n`;
        });

        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `shift-report-${new Date().toISOString().slice(0,10)}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        showNotification('Report exported successfully', 'success');
    };

    // Global functions for buttons
    window.voidTransaction = function() {
        if (cart.length === 0) {
            showNotification('Cart is empty. Nothing to void.', 'warning');
            return;
        }
        
        if (confirm('Are you sure you want to void the current transaction? This will clear all items from the cart.')) {
            clearCart();
            showNotification('Transaction voided successfully', 'success');
        }
    };

    // Request void transaction from Daily Sales Report (requires admin approval)
    window.voidTransactionFromReport = async function(saleId) {
        if (!saleId) {
            showNotification('Invalid transaction ID', 'error');
            return;
        }
        
        // Show modal immediately with loading state for instant feedback
        const voidModalElement = document.getElementById('voidItemsModal');
        const voidModal = new bootstrap.Modal(voidModalElement);
        const itemsList = document.getElementById('voidItemsList');
        const submitBtn = document.getElementById('submitVoidRequestBtn');
        
        // Show loading state immediately
        itemsList.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2 text-muted">Loading transaction details...</p></div>';
        submitBtn.disabled = true;
        document.getElementById('voidModalReceiptNumber').textContent = 'Loading...';
        document.getElementById('voidReason').value = '';
        document.getElementById('voidTotalAmount').textContent = '₱0.00';
        
        // Show modal immediately
        voidModal.show();
        
        try {
            // Fetch sale details
            const saleResponse = await fetch(`/sales/${saleId}`);
            const saleData = await saleResponse.json();
            
            if (!saleResponse.ok || !saleData.success) {
                voidModal.hide();
                showNotification('Failed to load transaction details', 'error');
            return;
        }
        
            const sale = saleData.sale;
            const items = (sale.items || []).filter((item) => parseFloat(item.quantity || 0) > 0);
            
            if (items.length === 0) {
                voidModal.hide();
                showNotification('No items found in this transaction', 'error');
            return;
        }
            
            // Populate modal with actual data
            document.getElementById('voidModalReceiptNumber').textContent = sale.receipt_number || sale.sale_number || 'N/A';
            document.getElementById('voidReason').value = '';
            
            itemsList.innerHTML = '';
            
            let totalVoidAmount = 0;
            const itemsByKey = {};
            
            items.forEach((item, index) => {
                const itemKey = item.sale_item_id != null ? item.sale_item_id : index;
                itemsByKey[itemKey] = item;
                
                const itemName = item.name || item.product_name || item.item || 'Unknown Product';
                const itemQuantity = parseFloat(item.quantity || 0);
                const itemPrice = parseFloat(item.price || 0);
                const itemTotal = itemQuantity * itemPrice;
                
                const itemDiv = document.createElement('div');
                itemDiv.className = 'form-check mb-2 p-2 border-bottom';
                itemDiv.innerHTML = `
                    <input class="form-check-input void-item-checkbox" type="checkbox" value="${itemKey}" id="voidItem${itemKey}">
                    <label class="form-check-label w-100" for="voidItem${itemKey}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${itemName}</strong><br>
                                <small class="text-muted">Qty: ${itemQuantity} × ₱${itemPrice.toFixed(2)}</small>
                            </div>
                            <div class="text-end">
                                <strong>₱${itemTotal.toFixed(2)}</strong>
                            </div>
                        </div>
                    </label>
                `;
                itemsList.appendChild(itemDiv);
                
                // Add event listener to update total
                const checkbox = itemDiv.querySelector('.void-item-checkbox');
                checkbox.addEventListener('change', updateVoidTotal);
            });
            
            // Update total initially
            updateVoidTotal();
            
            // Enable submit button now that data is loaded
            submitBtn.disabled = false;
            
            // Store saleId for submission
            document.getElementById('voidItemsModal').dataset.saleId = saleId;
            
            // Update void total function
            function updateVoidTotal() {
                const checkboxes = document.querySelectorAll('.void-item-checkbox:checked');
                totalVoidAmount = 0;
                
                checkboxes.forEach(checkbox => {
                    const item = itemsByKey[checkbox.value] || itemsByKey[parseInt(checkbox.value)];
                    if (item) {
                        const itemQuantity = parseFloat(item.quantity || 0);
                        const itemPrice = parseFloat(item.price || 0);
                        totalVoidAmount += itemQuantity * itemPrice;
                    }
                });
                
                document.getElementById('voidTotalAmount').textContent = `₱${totalVoidAmount.toFixed(2)}`;
            }
            
            // Submit button handler (submitBtn already declared above)
            const originalHandler = submitBtn.onclick;
            submitBtn.onclick = async function() {
                const checkedBoxes = document.querySelectorAll('.void-item-checkbox:checked');
                if (checkedBoxes.length === 0) {
                    showNotification('Please select at least one item to void', 'warning');
                    return;
                }
                
                const voidedItems = Array.from(checkedBoxes).map(cb => parseInt(cb.value));
                const reason = document.getElementById('voidReason').value.trim();
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';
        
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                showNotification('CSRF token not found', 'error');
                return;
            }
            
            const response = await fetch(`/sales/${saleId}/void-request`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.content,
                },
                body: JSON.stringify({
                            reason: reason,
                            voided_items: voidedItems
                }),
            });
            
            const data = await response.json();
            
            if (response.ok && data.success) {
                showNotification('Void request submitted. Waiting for admin approval.', 'success');
                        voidModal.hide();
                // Refresh the daily sales report
                if (document.getElementById('generateDailySalesBtn')) {
                    document.getElementById('generateDailySalesBtn').click();
                }
            } else {
                showNotification(data.message || 'Failed to submit void request', 'error');
            }
        } catch (error) {
            console.error('Error submitting void request:', error);
            showNotification('Error submitting void request. Please try again.', 'error');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-x-circle me-1"></i>Submit Void Request';
                }
            };
            
        } catch (error) {
            console.error('Error loading transaction details:', error);
            showNotification('Error loading transaction details. Please try again.', 'error');
        }
    };

    window.processRefund = function() {
        const modal = new bootstrap.Modal(document.getElementById('refundModal'));
        modal.show();
    };

    window.clearCart = function() {
        if (confirm('Are you sure you want to clear the cart?')) {
            cart = [];
            if (typeof clearCashierScanSession === 'function') {
                clearCashierScanSession();
            }
            updateCartDisplay();
            showNotification('Cart cleared', 'info');
        }
    };

    window.applyDiscount = function() {
        const discountPercent = prompt('Enter discount percentage (0-100):');
        if (discountPercent !== null && discountPercent !== '') {
            const percent = parseFloat(discountPercent);
            if (percent >= 0 && percent <= 100) {
                cart.forEach(item => {
                    item.discount = percent;
                    item.total = item.price * item.quantity * (1 - percent / 100);
                });
                updateCartDisplay();
                showNotification(`Applied ${percent}% discount to all items`, 'success');
            } else {
                showNotification('Invalid discount percentage', 'error');
            }
        }
    };

    window.holdTransaction = function() {
        if (cart.length === 0) {
            showNotification('Cart is empty', 'warning');
            return;
        }

        const transactionData = {
            items: cart,
            timestamp: new Date().toISOString(),
            total: cart.reduce((sum, item) => sum + item.total, 0)
        };

        const heldTransactions = JSON.parse(localStorage.getItem('heldTransactions') || '[]');
        heldTransactions.push(transactionData);
        localStorage.setItem('heldTransactions', JSON.stringify(heldTransactions));

        showNotification('Transaction held successfully', 'success');
    };

    // Initialize payment button
    const processPaymentBtn = document.getElementById('processPayment');
    if (processPaymentBtn) {
        processPaymentBtn.addEventListener('click', processPayment);
    }
    
    // Initialize Free Sample Item Search - Using same fast local search as product selection
    function initializeFreeSampleSearch() {
        const sampleItemSearch = document.getElementById('sampleItemSearch');
        const sampleSearchResults = document.getElementById('sampleSearchResults');
        const itemNameInput = document.getElementById('item_name');
        const productIdInput = document.getElementById('free_sample_product_id');
        
        if (!sampleItemSearch || !sampleSearchResults || !itemNameInput) {
            return; // Elements not found, skip initialization
        }
        
        sampleItemSearch.addEventListener('input', function() {
            const query = this.value.trim();
            
            if (query.length < 1) {
                sampleSearchResults.classList.remove('show');
                sampleSearchResults.style.display = 'none';
                itemNameInput.value = '';
                if (productIdInput) productIdInput.value = '';
                return;
            }
            
            // Use the same fast local search as product selection - no API calls!
            const queryLower = query.toLowerCase();
            
            // Filter products locally from the pre-loaded products object
            const filteredProducts = Object.values(products).filter(product => cashierProductMatchesQuery(product, query));
            
            // Display results immediately - no debounce, no API calls!
            if (filteredProducts.length > 0) {
                displaySampleSearchResults(filteredProducts);
                        } else {
                            sampleSearchResults.innerHTML = '<div class="dropdown-item text-muted">No items found</div>';
                            sampleSearchResults.classList.add('show');
                            sampleSearchResults.style.display = 'block';
                        }
        });
        
        function displaySampleSearchResults(items) {
            // Clear previous results immediately
            sampleSearchResults.innerHTML = '';
            
            // Limit to top 10 results for performance
            const itemsToShow = items.slice(0, 10);
            
            // Use same fast rendering approach as product selection
            for (const item of itemsToShow) {
                const itemElement = document.createElement('div');
                itemElement.className = 'dropdown-item d-flex align-items-center sample-search-item';
                itemElement.style.cursor = 'pointer';
                
                // Format brand display (show after item name if brand exists)
                const brandDisplay = item.brand ? ` <span class="text-muted fw-normal">- ${item.brand}</span>` : '';
                
                itemElement.innerHTML = `
                    <div class="me-3">
                        <i class="bi bi-box-seam text-primary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">${item.name}${brandDisplay}</div>
                        <small class="text-muted">${item.sku || 'No SKU'} • Stock: ${item.stock_quantity} ${item.unit || 'pcs'}</small>
                    </div>
                    <div class="ms-2">
                        <span class="badge ${item.stock_quantity > (item.min_stock_level || 0) ? 'bg-success' : 'bg-warning'}">
                            ${item.stock_quantity} ${item.unit || 'pcs'}
                        </span>
                    </div>
                `;
                
                itemElement.addEventListener('click', function() {
                    sampleItemSearch.value = item.name;
                    itemNameInput.value = item.name;
                    if (productIdInput) {
                        productIdInput.value = item.id || item.product_id || item.item_id || '';
                    }
                    sampleSearchResults.classList.remove('show');
                    sampleSearchResults.style.display = 'none';
                });
                
                itemElement.addEventListener('mouseenter', function() {
                    this.classList.add('bg-primary', 'text-white');
                });
                
                itemElement.addEventListener('mouseleave', function() {
                    this.classList.remove('bg-primary', 'text-white');
                });
                
                sampleSearchResults.appendChild(itemElement);
            }
            
            sampleSearchResults.classList.add('show');
            sampleSearchResults.style.display = 'block';
        }
        
        // Hide search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!sampleItemSearch.contains(e.target) && !sampleSearchResults.contains(e.target)) {
                sampleSearchResults.classList.remove('show');
                sampleSearchResults.style.display = 'none';
            }
        });
        
        // Keyboard navigation
        sampleItemSearch.addEventListener('keydown', function(e) {
            const items = sampleSearchResults.querySelectorAll('.sample-search-item');
            let selected = sampleSearchResults.querySelector('.sample-search-item.bg-primary');
            let selectedIndex = Array.from(items).indexOf(selected);
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (selectedIndex < items.length - 1) {
                    if (selected) selected.classList.remove('bg-primary', 'text-white');
                    items[selectedIndex + 1].classList.add('bg-primary', 'text-white');
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (selectedIndex > 0) {
                    if (selected) selected.classList.remove('bg-primary', 'text-white');
                    items[selectedIndex - 1].classList.add('bg-primary', 'text-white');
                }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const selected = sampleSearchResults.querySelector('.sample-search-item.bg-primary');
                if (selected) {
                    selected.click();
                }
            } else if (e.key === 'Escape') {
                sampleSearchResults.classList.remove('show');
                sampleSearchResults.style.display = 'none';
                this.blur();
            }
        });
    }
    
    // Initialize free sample search
    initializeFreeSampleSearch();
    
    // Initialize Discrepancy Item Search — local product list (instant, from 1 character)
    function initializeDiscrepancySearch() {
        const discrepancyItemSearch = document.getElementById('discrepancyItemSearch');
        const discrepancySearchResults = document.getElementById('discrepancySearchResults');
        const itemNameInput = document.getElementById('discrepancy_item_name');
        const productIdInput = document.getElementById('discrepancy_product_id');
        const itemIdInput = document.getElementById('discrepancy_item_id');
        const inventoryProductIdInput = document.getElementById('discrepancy_inventory_product_id');
        
        if (!discrepancyItemSearch || !discrepancySearchResults || !itemNameInput) {
            return;
        }
        
        discrepancyItemSearch.addEventListener('input', function() {
            const query = this.value.trim();
            
            if (query.length > 0) {
                itemNameInput.value = query;
            } else {
                itemNameInput.value = '';
            }
            
            if (query.length < 1) {
                discrepancySearchResults.classList.remove('show');
                discrepancySearchResults.style.display = 'none';
                if (productIdInput) productIdInput.value = '';
                if (itemIdInput) itemIdInput.value = '';
                if (inventoryProductIdInput) inventoryProductIdInput.value = '';
                return;
            }

            const filteredProducts = Object.values(products || {}).filter((product) =>
                cashierProductMatchesQuery(product, query)
            );

            if (filteredProducts.length > 0) {
                displayDiscrepancySearchResults(filteredProducts);
            } else {
                discrepancySearchResults.innerHTML = '<div class="dropdown-item text-muted">No items found</div>';
                discrepancySearchResults.classList.add('show');
                discrepancySearchResults.style.display = 'block';
            }
        });
        
        function displayDiscrepancySearchResults(items) {
            discrepancySearchResults.innerHTML = '';
            
            items.slice(0, 10).forEach(item => {
                const itemElement = document.createElement('div');
                itemElement.className = 'dropdown-item d-flex align-items-center discrepancy-search-item';
                itemElement.style.cursor = 'pointer';

                const escapeHtml = (text) => {
                    if (text == null) return '';
                    const div = document.createElement('div');
                    div.textContent = String(text);
                    return div.innerHTML;
                };
                
                itemElement.innerHTML = `
                    <div class="me-3">
                        <i class="bi bi-box-seam text-warning"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">${escapeHtml(item.name)}</div>
                        <small class="text-muted">${escapeHtml(item.sku || 'No SKU')} • Stock: ${escapeHtml(item.stock_quantity)} ${escapeHtml(item.unit || 'pcs')}</small>
                    </div>
                `;
                
                itemElement.addEventListener('click', function() {
                    discrepancyItemSearch.value = item.name;
                    itemNameInput.value = item.name;
                    const resolvedId = item.product_id || item.item_id || item.inventory_product_id || item.id || '';
                    if (productIdInput) productIdInput.value = resolvedId;
                    if (itemIdInput) itemIdInput.value = resolvedId;
                    if (inventoryProductIdInput) inventoryProductIdInput.value = resolvedId;
                    discrepancySearchResults.classList.remove('show');
                    discrepancySearchResults.style.display = 'none';
                });
                
                itemElement.addEventListener('mouseenter', function() {
                    this.classList.add('bg-warning', 'text-dark');
                });
                
                itemElement.addEventListener('mouseleave', function() {
                    this.classList.remove('bg-warning', 'text-dark');
                });
                
                discrepancySearchResults.appendChild(itemElement);
            });
            
            discrepancySearchResults.classList.add('show');
            discrepancySearchResults.style.display = 'block';
        }
        
        discrepancyItemSearch.addEventListener('blur', function() {
            const query = this.value.trim();
            if (query.length > 0 && !itemNameInput.value) {
                itemNameInput.value = query;
            }
        });
        
        document.addEventListener('click', function(e) {
            if (!discrepancyItemSearch.contains(e.target) && !discrepancySearchResults.contains(e.target)) {
                discrepancySearchResults.classList.remove('show');
                discrepancySearchResults.style.display = 'none';
            }
        });
        
        discrepancyItemSearch.addEventListener('keydown', function(e) {
            const items = discrepancySearchResults.querySelectorAll('.discrepancy-search-item');
            let selected = discrepancySearchResults.querySelector('.discrepancy-search-item.bg-warning');
            let selectedIndex = Array.from(items).indexOf(selected);
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (selectedIndex < items.length - 1) {
                    if (selected) selected.classList.remove('bg-warning', 'text-dark');
                    items[selectedIndex + 1].classList.add('bg-warning', 'text-dark');
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (selectedIndex > 0) {
                    if (selected) selected.classList.remove('bg-warning', 'text-dark');
                    items[selectedIndex - 1].classList.add('bg-warning', 'text-dark');
                }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const highlighted = discrepancySearchResults.querySelector('.discrepancy-search-item.bg-warning');
                const first = discrepancySearchResults.querySelector('.discrepancy-search-item');
                (highlighted || first)?.click();
            } else if (e.key === 'Escape') {
                discrepancySearchResults.classList.remove('show');
                discrepancySearchResults.style.display = 'none';
                this.blur();
            }
        });
    }
    
    // Initialize discrepancy search
    initializeDiscrepancySearch();
    
    // Initialize customer search
    initializeCustomerSearch();
    
    // Sync customer info section when discount fields change
    const discountCustomerName = document.getElementById('discountCustomerName');
    const discountIdNumber = document.getElementById('discountIdNumber');
    const discountIdType = document.getElementById('discountIdType');
    const discountIssuingLgu = document.getElementById('discountIssuingLgu');
    
    if (discountCustomerName) {
        discountCustomerName.addEventListener('input', updateCustomerInfoSection);
    }
    if (discountIdNumber) {
        discountIdNumber.addEventListener('input', updateCustomerInfoSection);
    }
    if (discountIdType) {
        discountIdType.addEventListener('change', updateCustomerInfoSection);
    }
    if (discountIssuingLgu) {
        discountIssuingLgu.addEventListener('input', updateCustomerInfoSection);
    }
    
    // Initial sync
    updateCustomerInfoSection();
    
    // Handle discrepancy form submission
    const discrepancyForm = document.getElementById('discrepancyForm');
    const discrepancySubmitBtn = document.querySelector('button[form="discrepancyForm"][type="submit"]');
    
    // Function to handle form submission
    async function handleDiscrepancySubmit(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        if (!discrepancyForm) {
            console.error('Discrepancy form not found!');
            return;
        }
        
        const form = document.getElementById('discrepancyForm');
        if (!form) {
            console.error('Discrepancy form not found!');
            showNotification('Form not found. Please refresh the page.', 'error');
            return;
        }
        
        console.log('Discrepancy form submit triggered');
            
            // Validate form before submission
            const itemNameInput = document.getElementById('discrepancy_item_name');
            const itemSearchInput = document.getElementById('discrepancyItemSearch');
            const quantityInput = document.getElementById('discrepancy_quantity');
            const descriptionInput = document.getElementById('discrepancy_description');
            
            console.log('Form values:', {
                itemName: itemNameInput?.value,
                itemSearch: itemSearchInput?.value,
                quantity: quantityInput?.value,
                description: descriptionInput?.value
            });
            
            // Check if item was selected
            if (!itemNameInput || !itemNameInput.value || itemNameInput.value.trim() === '') {
                showNotification('Please select an item from the search results or type an item name', 'error');
                if (itemSearchInput) itemSearchInput.focus();
                return;
            }
            
            // Validate quantity
            if (!quantityInput || !quantityInput.value || parseFloat(quantityInput.value) <= 0) {
                showNotification('Please enter a valid quantity greater than 0', 'error');
                if (quantityInput) quantityInput.focus();
                return;
            }
            
            // Validate description
            if (!descriptionInput || !descriptionInput.value || descriptionInput.value.trim() === '') {
                showNotification('Please enter a description for the discrepancy', 'error');
                if (descriptionInput) descriptionInput.focus();
                return;
            }
            
            const formData = new FormData(form);
            
            // Log form data for debugging
            console.log('FormData contents:');
            for (let [key, value] of formData.entries()) {
                console.log(key, ':', value);
            }
            
        const submitBtn = discrepancySubmitBtn || form.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.innerHTML : '';
        
        try {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Submitting...';
            }
            
            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                formData.append('_token', csrfToken.content);
                console.log('CSRF token added:', csrfToken.content.substring(0, 10) + '...');
            } else {
                console.error('CSRF token not found!');
            }
            
            console.log('Sending request to:', form.action);
            const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });
                
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                let data = {};
                try {
                    const text = await response.text();
                    console.log('Response text:', text);
                    data = JSON.parse(text);
                } catch (parseError) {
                    console.error('Error parsing response:', parseError);
                    showNotification('Error parsing server response', 'error');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                    return;
                }
                
                console.log('Response data:', data);
                
            if (response.ok) {
                if (data.success || data.status === 'success') {
                    showNotification(data.message || 'Discrepancy request submitted for approval.', 'success');
                    form.reset();
                    // Clear hidden inputs
                    const itemIdInput = document.getElementById('discrepancy_item_id');
                    const invProductIdInput = document.getElementById('discrepancy_inventory_product_id');
                    if (itemIdInput) itemIdInput.value = '';
                    if (invProductIdInput) invProductIdInput.value = '';
                    if (itemSearchInput) itemSearchInput.value = '';
                    const modal = bootstrap.Modal.getInstance(document.getElementById('discrepancyModal'));
                    if (modal) {
                        modal.hide();
                    }
                    // Reload page to refresh history
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotification(data.message || 'Failed to submit discrepancy request', 'error');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                }
            } else {
                // Handle validation errors
                let errorMessage = 'Error submitting discrepancy request';
                if (data.message) {
                    errorMessage = data.message;
                } else if (data.errors) {
                    const errorMessages = Object.values(data.errors).flat();
                    errorMessage = errorMessages.join(', ');
                }
                console.error('Submission error:', errorMessage);
                showNotification(errorMessage, 'error');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            }
        } catch (error) {
            console.error('Error submitting discrepancy:', error);
            console.error('Error stack:', error.stack);
            showNotification('Error submitting discrepancy request: ' + error.message, 'error');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
    }
    
    // Attach submit handler to form
    if (discrepancyForm) {
        console.log('Discrepancy form found, attaching submit handler');
        discrepancyForm.addEventListener('submit', handleDiscrepancySubmit);
    } else {
        console.error('Discrepancy form not found!');
    }
    
    // Also attach click handler to submit button (backup)
    if (discrepancySubmitBtn) {
        console.log('Discrepancy submit button found, attaching click handler');
        discrepancySubmitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            handleDiscrepancySubmit(e);
        });
    }
    
    // Daily Sales Report functionality
    const generateDailySalesBtn = document.getElementById('generateDailySalesBtn');
    const dailySalesReportModal = new bootstrap.Modal(document.getElementById('dailySalesReportModal'));
    const reportLoading = document.getElementById('reportLoading');
    const reportContent = document.getElementById('reportContent');
    const printDailyReportBtn = document.getElementById('printDailyReport');

    // Function to load daily sales report
    // Parameter: showModal - if true, shows the modal; if false, only refreshes content (for auto-refresh)
    async function loadDailySalesReport(showModal = true) {
        // Only show modal if explicitly requested (not during auto-refresh)
        if (showModal && !dailySalesReportModal._isShown) {
            dailySalesReportModal.show();
        }

        // If modal is not shown, don't update content (user closed it)
        if (!dailySalesReportModal._isShown && !showModal) {
            return; // Don't refresh if modal is closed and this is an auto-refresh
        }
        
        // Show content immediately, no loading state
        reportLoading.style.display = 'none';
        reportContent.style.display = 'block';
        
        // Check if there's an active shift
        if (!shiftData || !shiftData.isActive) {
            // No active shift, show empty report
            // Update report date to today
            const today = new Date();
            document.getElementById('reportDate').textContent = today.toLocaleDateString('en-US', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
            
            // Show zeros for all metrics
            document.getElementById('reportTotalSales').textContent = '₱0.00';
            document.getElementById('reportTotalTransactions').textContent = '0';
            document.getElementById('reportTotalItems').textContent = '0';
            document.getElementById('reportAvgTransaction').textContent = '₱0.00';
            
            // Clear table
            const tableBody = document.getElementById('itemsSoldTableBody');
            tableBody.innerHTML = '<tr><td colspan="10" class="text-center text-muted">No active shift. Please start a shift to view sales data.</td></tr>';
            document.getElementById('footerTotalQty').textContent = '0';
            document.getElementById('footerTotalAmount').textContent = '₱0.00';
            return;
        }

        try {
            // Use shift-specific data from shiftData.transactions (already in memory - instant!)
            const shiftTransactions = shiftData.transactions || [];

                    // Format number with comma separators
                    const formatNumber = (num) => {
                        return parseFloat(num || 0).toLocaleString('en-US', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    };
                    
            // Calculate totals from shift transactions
            let totalSales = 0;
            let totalItems = 0;
            const products = [];
            const saleIds = new Set(); // Collect unique sale IDs
            
            shiftTransactions.forEach((transaction, txnIndex) => {
                if (!window.isCountableShiftTransaction(transaction, globalVoidStatusMap, globalVoidedItemsMap)) {
                    return;
                }
                if (transaction.items && Array.isArray(transaction.items)) {
                    // Calculate transaction-level discount information
                    let transactionDiscountAmount = 0;
                    let transactionOriginalSubtotal = 0;
                    
                    // Get discount amount from transaction
                    if (transaction.discountAmount !== undefined) {
                        transactionDiscountAmount = parseFloat(transaction.discountAmount) || 0;
                    } else if (transaction.originalSubtotal !== undefined && transaction.subtotal !== undefined) {
                        transactionOriginalSubtotal = parseFloat(transaction.originalSubtotal) || 0;
                        const transactionSubtotal = parseFloat(transaction.subtotal) || 0;
                        transactionDiscountAmount = transactionOriginalSubtotal - transactionSubtotal;
                    } else if (transaction.discount !== undefined) {
                        transactionDiscountAmount = parseFloat(transaction.discount) || 0;
                    }
                    
                    // Calculate original subtotal if not available
                    if (transactionOriginalSubtotal === 0 && transaction.originalSubtotal !== undefined) {
                        transactionOriginalSubtotal = parseFloat(transaction.originalSubtotal) || 0;
                    }
                    if (transactionOriginalSubtotal === 0) {
                        // Calculate from items if not available
                        transaction.items.forEach(item => {
                            const qty = parseFloat(item.quantity || 0);
                            const itemPrice = parseFloat(item.price || 0);
                            transactionOriginalSubtotal += qty * itemPrice;
                        });
                    }
                    
                    // If we still don't have discount amount, try to calculate from total vs subtotal
                    if (transactionDiscountAmount === 0 && transaction.total !== undefined && transaction.subtotal !== undefined) {
                        const transactionTotal = parseFloat(transaction.total) || 0;
                        const transactionSubtotal = parseFloat(transaction.subtotal) || 0;
                        // If total is less than subtotal, there's a discount
                        if (transactionSubtotal > transactionTotal) {
                            transactionDiscountAmount = transactionSubtotal - transactionTotal;
                        } else if (transactionOriginalSubtotal > 0 && transactionSubtotal < transactionOriginalSubtotal) {
                            // Discount was applied to subtotal
                            transactionDiscountAmount = transactionOriginalSubtotal - transactionSubtotal;
                        }
                    }
                    
                    // If still no discount amount but we have originalSubtotal and total
                    if (transactionDiscountAmount === 0 && transactionOriginalSubtotal > 0 && transaction.total !== undefined) {
                        const transactionTotal = parseFloat(transaction.total) || 0;
                        if (transactionOriginalSubtotal > transactionTotal) {
                            transactionDiscountAmount = transactionOriginalSubtotal - transactionTotal;
                        }
                    }
                    
                    transaction.items.forEach((item, itemIndex) => {
                        const quantity = parseFloat(item.quantity || 0);
                        if (quantity <= 0) {
                            return;
                        }
                        const price = parseFloat(item.price || 0);
                        // Calculate item original total (before discount)
                        const itemOriginalTotal = parseFloat((quantity * price).toFixed(2));
                        
                        // Calculate item discount
                        let itemDiscount = 0;
                        let discountDisplay = '-';
                        
                        // Check if item has its own discount (for senior/PWD discounts applied per item)
                        if (item.discount !== undefined && item.discount > 0) {
                            // Item-level discount (e.g., 20% for senior/PWD)
                            const itemDiscountPercent = parseFloat(item.discount) || 0;
                            itemDiscount = itemOriginalTotal * (itemDiscountPercent / 100);
                            discountDisplay = `${itemDiscountPercent}%`;
                        } else if (transactionDiscountAmount > 0 && transactionOriginalSubtotal > 0) {
                            // Transaction-level discount (e.g., 3% on subtotal) - calculate proportional discount
                            const itemProportion = itemOriginalTotal / transactionOriginalSubtotal;
                            itemDiscount = transactionDiscountAmount * itemProportion;
                            
                            // Display as percentage or amount
                            const discountPercent = (transactionDiscountAmount / transactionOriginalSubtotal) * 100;
                            discountDisplay = `${discountPercent.toFixed(0)}%`;
                        } else {
                            // Try to calculate discount from item's original vs final price
                            // If item has subtotal that's different from total, there might be a discount
                            const itemSubtotal = parseFloat(item.subtotal || item.total || itemOriginalTotal);
                            if (itemSubtotal < itemOriginalTotal) {
                                itemDiscount = itemOriginalTotal - itemSubtotal;
                                const discountPercent = (itemDiscount / itemOriginalTotal) * 100;
                                discountDisplay = `${discountPercent.toFixed(0)}%`;
                            }
                        }
                        
                        // Item total after discount - use item's total if available, otherwise calculate
                        let itemTotal = parseFloat(item.total || item.subtotal || itemOriginalTotal);
                        if (itemDiscount > 0) {
                            itemTotal = parseFloat((itemOriginalTotal - itemDiscount).toFixed(2));
                        }
                        
                        totalSales += itemOriginalTotal; // Use original total for total sales
                        totalItems += quantity;
                        
                        if (transaction.id) {
                            saleIds.add(transaction.id);
                        }
                        
                        products.push({
                            name: item.name || 'Unknown Product',
                            quantity: quantity,
                            price: price,
                            total: itemTotal,
                            discount: discountDisplay,
                            sale_id: transaction.id,
                            item_index: itemIndex, // Store the item index within the sale
                            transaction_number: txnIndex + 1,
                            payment_method: transaction.paymentMethod || 'cash',
                            payment_info: transaction.paymentInfo ? JSON.stringify(transaction.paymentInfo) : null,
                            reference_number: transaction.paymentInfo?.reference_number || null,
                            void_status: null, // Will be updated after fetching void requests
                            sale_status: 'completed'
                        });
                    });
                }
            });
            
            // Use global void status maps if available (from updateAnalytics), otherwise fetch in background
            let voidStatusMap = { ...globalVoidStatusMap };
            let voidedItemsMap = { ...globalVoidedItemsMap };
            
            // If we don't have void statuses for all sales, fetch them in the background (non-blocking)
            if (saleIds.size > 0) {
                const missingSaleIds = Array.from(saleIds)
                    .filter(id => id && !isNaN(id) && id > 0 && !voidStatusMap[id]);
                
                if (missingSaleIds.length > 0) {
                    // Fetch missing void statuses in background (don't await - non-blocking)
                    fetch(`/api/sales/void-statuses?${missingSaleIds.map(id => `sale_ids[]=${id}`).join('&')}`)
                        .then(response => {
                            if (response.ok) {
                                return response.json();
                            } else if (response.status === 400) {
                                // Silently handle validation errors
                                console.debug('Some sale IDs may be invalid, skipping void status fetch');
                                return null;
                            }
                            return null;
                        })
                        .then(data => {
                            if (data && data.success) {
                                if (data.void_statuses) {
                                    Object.assign(globalVoidStatusMap, data.void_statuses);
                                }
                                if (data.voided_items) {
                                    Object.assign(globalVoidedItemsMap, data.voided_items);
                                }
                                // Refresh the report with updated void statuses (only if modal is open)
                                if (dailySalesReportModal._isShown) {
                                    loadDailySalesReport(false); // Don't show modal, just refresh content
                                }

                            }
                        })
                        .catch(error => {
                            console.error('Error fetching void statuses in background:', error);
                            // Silently fail - report already displayed
                        });
                }
            }
            
            // Update void_status for each product - only set void_status if THIS SPECIFIC ITEM is voided
            products.forEach(product => {
                if (product.sale_id && voidStatusMap[product.sale_id]) {
                    const voidStatus = voidStatusMap[product.sale_id];
                    const voidedItems = voidedItemsMap[product.sale_id] || [];
                    const hasApprovedVoidedLines = voidStatus === 'approved' && Array.isArray(voidedItems) && voidedItems.length > 0;
                    if (hasApprovedVoidedLines) {
                        // Business rule: for approved partial voids, remaining lines keep original unit-price totals.
                        product.discount = '-';
                        const voidedItemIndices = voidedItems.map(idx => parseInt(idx));
                        if (!voidedItemIndices.includes(product.item_index)) {
                            const qty = parseFloat(product.quantity || 0);
                            const price = parseFloat(product.price || 0);
                            product.total = parseFloat((qty * price).toFixed(2));
                        }
                    }
                    
                    // Only set void_status if this specific item is voided
                    if (hasApprovedVoidedLines) {
                        const voidedItemIndices = voidedItems.map(idx => parseInt(idx));
                        // Check if this item's index is in the voided_items array
                        if (voidedItemIndices.includes(product.item_index)) {
                            // This item is voided, set the void status
                            product.void_status = voidStatus;
                        } else {
                            // This item is NOT voided, clear void_status
                            product.void_status = null;
                        }
                    } else if (voidStatus === 'pending' || voidStatus === 'rejected') {
                        // For pending/rejected, show status for all items in the transaction
                        product.void_status = voidStatus;
                    } else {
                        // No void status or not approved, clear it
                        product.void_status = null;
                    }
                } else {
                    // No void status for this sale, clear it
                    product.void_status = null;
                }
            });
            
            // Filter out voided items (items that are in voided_items array when status is 'approved')
            const filteredProducts = products.filter((product) => {
                if (!product.sale_id) {
                    return false;
                }
                if (parseFloat(product.quantity || 0) <= 0) {
                    return false;
                }
                
                const voidStatus = voidStatusMap[product.sale_id];
                const voidedItems = voidedItemsMap[product.sale_id] || [];
                
                // If void status is 'approved', check if this item's index is in the voided_items array
                if (voidStatus === 'approved' && Array.isArray(voidedItems) && voidedItems.length > 0) {
                    // Check if this item's index (stored in item_index) is in the voided_items array
                    // Convert voidedItems to numbers for comparison
                    const voidedItemIndices = voidedItems.map(idx => parseInt(idx));
                    if (voidedItemIndices.includes(product.item_index)) {
                        return false; // Exclude this item
                    }
                }
                
                return true; // Keep the item
            });
            
            // Recalculate totals after filtering
            // Use effective transaction totals to keep report sales aligned with void logic.
            totalSales = 0;
            totalItems = 0;
            filteredProducts.forEach(product => {
                totalItems += product.quantity;
            });

            shiftTransactions.forEach(transaction => {
                totalSales += window.getEffectiveTransactionTotalForShift(transaction, voidStatusMap, voidedItemsMap);
            });
            
            // Renumber transactions sequentially (1, 2, 3...) based on filtered products
            // Get unique sale_ids from filtered products in order of appearance
            const uniqueSaleIds = [];
            const seenSaleIds = new Set();
            filteredProducts.forEach(product => {
                if (product.sale_id && !seenSaleIds.has(product.sale_id)) {
                    uniqueSaleIds.push(product.sale_id);
                    seenSaleIds.add(product.sale_id);
                }
            });
            
            // Create a map of sale_id to sequential transaction number
            const saleIdToTransactionNumber = {};
            uniqueSaleIds.forEach((saleId, index) => {
                saleIdToTransactionNumber[saleId] = index + 1;
            });
            
            // Update transaction numbers in filtered products
            const productsToDisplay = filteredProducts.map(product => {
                if (product.sale_id && saleIdToTransactionNumber[product.sale_id]) {
                    product.transaction_number = saleIdToTransactionNumber[product.sale_id];
                }
                return product;
            });
            
            // Update report date to shift start date (Asia/Manila timezone)
            let shiftStartDate = new Date();
            if (shiftData.startTime) {
                try {
                    const timeStr = shiftData.startTime;
                    let datePart = '';
                    let timePart = '';
                    
                    if (timeStr.includes('T')) {
                        const parts = timeStr.split('T');
                        datePart = parts[0];
                        timePart = parts[1].replace(/Z$/, '').split('.')[0];
                    } else if (timeStr.includes(' ')) {
                        const parts = timeStr.split(' ');
                        datePart = parts[0];
                        timePart = parts[1];
                    }
                    
                    if (datePart && timePart) {
                        const [year, month, day] = datePart.split('-');
                        const [hours, minutes, seconds] = timePart.split(':');
                        const isoString = `${year}-${month}-${day}T${hours}:${minutes}:${seconds || '00'}+08:00`;
                        shiftStartDate = new Date(isoString);
                    } else {
                        shiftStartDate = new Date(timeStr);
                    }
                } catch (error) {
                    console.error('Error parsing shift start date:', error);
                    shiftStartDate = new Date();
                }
            }
            document.getElementById('reportDate').textContent = shiftStartDate.toLocaleDateString('en-US', {
                timeZone: 'Asia/Manila',
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }) + ' (Current Shift)';
            
            // Update summary cards with shift-specific data
            // Transaction count should be based on unique sale_ids from displayed items
            // This ensures we count transactions that have at least one non-voided item
            const totalTransactionCount = uniqueSaleIds.length;
            console.log('Daily Sales Report - Total transactions (unique sale_ids from displayed items):', totalTransactionCount, 'All transactions:', shiftTransactions.length);
            document.getElementById('reportTotalSales').textContent = `₱${formatNumber(totalSales)}`;
            document.getElementById('reportTotalTransactions').textContent = totalTransactionCount;
            document.getElementById('reportTotalItems').textContent = totalItems;
            
            const avgTransaction = totalTransactionCount > 0 ? totalSales / totalTransactionCount : 0;
                    document.getElementById('reportAvgTransaction').textContent = `₱${formatNumber(avgTransaction)}`;

                    // Populate items table
                    const tableBody = document.getElementById('itemsSoldTableBody');
                    tableBody.innerHTML = '';

            if (productsToDisplay.length > 0) {
                        let totalQty = 0;
                        let totalAmount = 0;
                        
                productsToDisplay.forEach((product, index) => {
                            totalQty += parseFloat(product.quantity);
                            totalAmount += parseFloat(product.total);
                            
                    const transactionNumber = product.transaction_number || '-';

                            // Format payment info
                    let paymentInfo = '-';
                    if (product.payment_info) {
                        try {
                            const paymentData = typeof product.payment_info === 'string' ? JSON.parse(product.payment_info) : product.payment_info;
                            if (paymentData.provider) {
                                paymentInfo = paymentData.provider;
                                if (paymentData.reference_number) {
                                    paymentInfo += ': ' + paymentData.reference_number;
                                }
                            } else if (product.payment_method) {
                                paymentInfo = product.payment_method === 'e_wallet' && product.reference_number ? 
                                    'E-wallet: ' + product.reference_number : 
                                    product.payment_method === 'check' && product.reference_number ? 
                                    'Check: ' + product.reference_number : 
                                    product.payment_method.charAt(0).toUpperCase() + product.payment_method.slice(1).replace('_', ' ');
                            }
                        } catch (e) {
                            paymentInfo = product.payment_method ? 
                                product.payment_method.charAt(0).toUpperCase() + product.payment_method.slice(1).replace('_', ' ') : '-';
                        }
                    } else if (product.payment_method) {
                        paymentInfo = product.payment_method === 'e_wallet' && product.reference_number ? 
                            'E-wallet: ' + product.reference_number : 
                            product.payment_method === 'check' && product.reference_number ? 
                            'Check: ' + product.reference_number : 
                            product.payment_method.charAt(0).toUpperCase() + product.payment_method.slice(1).replace('_', ' ');
                    }

                            const row = document.createElement('tr');
                            const discount = product.discount || '-';
                    const saleId = product.sale_id;
                            const voidStatus = product.void_status;
                            
                            // Determine void status badge and button state
                            let voidStatusBadge = '<span class="badge bg-secondary">-</span>';
                            let voidButton = '';
                            
                            // Display void status based on void request status
                            if (voidStatus) {
                                if (voidStatus === 'pending') {
                                    voidStatusBadge = '<span class="badge bg-warning">Pending</span>';
                                } else if (voidStatus === 'approved') {
                                    voidStatusBadge = '<span class="badge bg-success">Approved</span>';
                                } else if (voidStatus === 'rejected') {
                                    voidStatusBadge = '<span class="badge bg-danger">Rejected</span>';
                                } else {
                                    voidStatusBadge = '<span class="badge bg-secondary">-</span>';
                                }
                            }
                            
                            if (saleId) {
                                // Only show void button if there's no pending or approved void request
                                if (!voidStatus || voidStatus === 'rejected') {
                                    voidButton = `<button class="btn btn-outline-danger btn-sm" onclick="voidTransactionFromReport(${saleId})" title="Void this transaction">
                                        <i class="bi bi-x-circle"></i>
                                    </button>`;
                                } else {
                                    voidButton = '-';
                                }
                            } else {
                                voidButton = '-';
                            }
                            
                            row.innerHTML = `
                                <td>${index + 1}</td>
                                <td>${product.name}</td>
                                <td class="text-center">${parseFloat(product.quantity).toFixed(0)}</td>
                                <td class="text-end">₱${formatNumber(product.price)}</td>
                                <td class="text-end">₱${formatNumber(product.total)}</td>
                                <td class="text-center">${discount}</td>
                                <td class="text-center">${transactionNumber}</td>
                                <td>${paymentInfo}</td>
                                <td class="text-center">${voidStatusBadge}</td>
                                <td class="text-center">${voidButton}</td>
                            `;
                            tableBody.appendChild(row);
                        });

                        // Update footer totals
                        document.getElementById('footerTotalQty').textContent = totalQty.toFixed(0);
                        document.getElementById('footerTotalAmount').textContent = `₱${formatNumber(totalAmount)}`;
                    } else {
                tableBody.innerHTML = '<tr><td colspan="10" class="text-center text-muted">No sales data for current shift</td></tr>';
                        document.getElementById('footerTotalQty').textContent = '0';
                        document.getElementById('footerTotalAmount').textContent = '₱0.00';
                }
            } catch (error) {
                console.error('Error generating report:', error);
            // Show error in content area (not loading area since we removed loading state)
            reportContent.style.display = 'block';
            const tableBody = document.getElementById('itemsSoldTableBody');
            if (tableBody) {
                tableBody.innerHTML = '<tr><td colspan="10" class="text-center text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Failed to generate report. Please try again.</td></tr>';
            }
            }
        }
    
    if (generateDailySalesBtn) {
        generateDailySalesBtn.addEventListener('click', loadDailySalesReport);
    }
    
    // Auto-refresh report every 30 seconds when modal is open
    let reportRefreshInterval = null;
    const dailySalesReportModalElement = document.getElementById('dailySalesReportModal');
    if (dailySalesReportModalElement) {
        dailySalesReportModalElement.addEventListener('shown.bs.modal', function() {
            // Start auto-refresh (only refresh content, don't show modal)
            reportRefreshInterval = setInterval(() => {
                if (dailySalesReportModal._isShown) {
                    loadDailySalesReport(false); // Only refresh content, don't show modal
                }
            }, 30000);
        });
        
        dailySalesReportModalElement.addEventListener('hidden.bs.modal', function() {
            // Stop auto-refresh when modal is closed
            if (reportRefreshInterval) {
                clearInterval(reportRefreshInterval);
                reportRefreshInterval = null;
            }
        });
    }

    // Print Daily Report
    if (printDailyReportBtn) {
        printDailyReportBtn.addEventListener('click', function() {
            const reportDate = document.getElementById('reportDate').textContent;
            const reportTotalSales = document.getElementById('reportTotalSales').textContent;
            const reportTotalTransactions = document.getElementById('reportTotalTransactions').textContent;
            const reportTotalItems = document.getElementById('reportTotalItems').textContent;
            const reportAvgTransaction = document.getElementById('reportAvgTransaction').textContent;
            
            const tableBody = document.getElementById('itemsSoldTableBody');
            const footerTotalQty = document.getElementById('footerTotalQty').textContent;
            const footerTotalAmount = document.getElementById('footerTotalAmount').textContent;
            
            let tableRows = '';
            tableBody.querySelectorAll('tr').forEach(row => {
                if (!row.textContent.includes('No sales data')) {
                    const cells = row.querySelectorAll('td');
                    if (cells.length > 0) {
                        tableRows += `
                            <tr>
                                <td style="border: 1px solid #ddd; padding: 8px;">${cells[0].textContent}</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">${cells[1].textContent}</td>
                                <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">${cells[2].textContent}</td>
                                <td style="border: 1px solid #ddd; padding: 8px; text-align: right;">${cells[3].textContent}</td>
                                <td style="border: 1px solid #ddd; padding: 8px; text-align: right;">${cells[4].textContent}</td>
                                <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">${cells[5] ? cells[5].textContent : '-'}</td>
                                <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">${cells[6] ? cells[6].textContent : '-'}</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">${cells[7] ? cells[7].textContent : '-'}</td>
                                <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">${cells[8] ? cells[8].textContent : '-'}</td>
                                <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">${cells[9] ? cells[9].textContent : '-'}</td>
                            </tr>
                        `;
                    }
                }
            });
            
            const printWindow = window.open('', '', 'height=800,width=1000');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Daily Sales Report - ${reportDate}</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        .header { text-align: center; margin-bottom: 30px; }
                        .summary { display: flex; justify-content: space-around; margin-bottom: 30px; }
                        .summary-card { text-align: center; padding: 15px; border: 2px solid #ddd; border-radius: 8px; }
                        .summary-card h3 { margin: 10px 0; color: #333; }
                        .summary-card p { margin: 0; color: #666; font-size: 14px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th { background-color: #f0f0f0; padding: 12px; text-align: left; border: 1px solid #ddd; }
                        td { padding: 8px; border: 1px solid #ddd; }
                        .text-center { text-align: center; }
                        .text-right { text-align: right; }
                        tfoot td { font-weight: bold; background-color: #f8f9fa; }
                        @media print {
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>Daily Sales Report</h1>
                        <h3>${reportDate}</h3>
                    </div>
                    
                    <div class="summary">
                        <div class="summary-card">
                            <p>Total Sales</p>
                            <h3>${reportTotalSales}</h3>
                        </div>
                        <div class="summary-card">
                            <p>Transactions</p>
                            <h3>${reportTotalTransactions}</h3>
                        </div>
                        <div class="summary-card">
                            <p>Items Sold</p>
                            <h3>${reportTotalItems}</h3>
                        </div>
                        <div class="summary-card">
                            <p>Avg Transaction</p>
                            <h3>${reportAvgTransaction}</h3>
                        </div>
                    </div>
                    
                    <h2>Items Sold Today</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Item Name</th>
                                <th class="text-center">Quantity Sold</th>
                                <th class="text-right">Unit Price</th>
                                <th class="text-right">Total Amount</th>
                                <th class="text-center">Discount</th>
                                <th class="text-center">Transaction</th>
                                <th>Payment Info</th>
                                <th class="text-center">Void Status</th>
                                <th class="text-center">Void Transaction</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tableRows || '<tr><td colspan="10" style="text-align: center; color: #999;">No sales data</td></tr>'}
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2">TOTAL</td>
                                <td class="text-center">${footerTotalQty}</td>
                                <td></td>
                                <td class="text-right">${footerTotalAmount}</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <div style="margin-top: 40px; text-align: center; color: #666;">
                        <p>Generated on: ${new Date().toLocaleString()}</p>
                        <p>Cashier: ${shiftData.cashierName || 'N/A'}</p>
                    </div>
                    
                    <script>
                        window.onload = function() {
                            window.print();
                            setTimeout(function() { window.close(); }, 500);
                        };
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
        });
    } // End of printDailyReportBtn click handler

    // Test log to confirm script is running
    console.log('POS System initialized successfully!');
    console.log('Cart:', cart);
    console.log('ShiftData:', shiftData);

}); // End of DOMContentLoaded addEventListener
</script>
@endpush