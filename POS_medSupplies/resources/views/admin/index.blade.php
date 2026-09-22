@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

@section('content')
<div class="admin-container">
    <!-- Header Section -->
    <div class="admin-header">
        <div class="d-flex justify-content-between align-items-center">
            <div class="header-brand">
                <div class="brand-logo">
                <img src="{{ ('/images/logo.jpg') }}" alt="REDEMP Logo" class="logo-image">
                </div>
                <div class="brand-text">
                    <h3 class="brand-name mb-0">ADMIN DASHBOARD</h3>
                    <small class="brand-tagline">SYSTEM MANAGEMENT & OVERSIGHT</small>
                </div>
            </div>
            <div class="header-controls">
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('about.admin') }}">
                    <i class="bi bi-info-circle me-1"></i>About
                </a>
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
            @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm">Logout</button>
        </form>
            </div>
        </div>
    </div>

    <!-- Real-time Summary Bar -->
    <div class="summary-bar">
        <div class="row g-2">
            <div class="col-md-3">
                <div class="summary-card bg-primary">
                    <div class="summary-icon">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-value" id="todaySalesValue">₱{{ number_format($todaySales ?? 0, 2) }}</div>
                        <div class="summary-label">Today's Sales</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card bg-warning">
                    <div class="summary-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-value">{{ $lowStockCount ?? 0 }}</div>
                        <div class="summary-label">Low Stock Alerts</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card bg-info">
                    <div class="summary-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-value">{{ $expiringCount ?? 0 }}</div>
                        <div class="summary-label">Expiring Items</div>
        </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card bg-success">
                    <div class="summary-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="summary-content">
                        <div class="summary-value">{{ $activeUsers ?? 0 }}</div>
                        <div class="summary-label">Active Users</div>
        </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="admin-main">
        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                    <i class="bi bi-grid me-2"></i>Overview
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">
                    <i class="bi bi-people me-2"></i>User Management
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="monitoring-tab" data-bs-toggle="tab" data-bs-target="#monitoring" type="button" role="tab">
                    <i class="bi bi-eye me-2"></i>Monitoring
                </button>
            </li>
            <li class="nav-item dropdown" role="presentation">
                <a class="nav-link dropdown-toggle" href="#" id="approvalsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-list-check me-2"></i>Approvals & Management
                </a>
                <ul class="dropdown-menu" aria-labelledby="approvalsDropdown">
                    <li>
                        <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('void-transactions-tab').click();">
                            <i class="bi bi-x-circle me-2"></i>Void Transactions
                            <span class="badge bg-danger ms-2" id="voidRequestsBadge" style="display: none;">0</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('discrepancies-tab').click();">
                            <i class="bi bi-exclamation-triangle me-2"></i>Discrepancies
                            <span class="badge bg-warning ms-2" id="discrepancyRequestsBadge" style="display: none;">0</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('customers-tab').click();">
                            <i class="bi bi-people me-2"></i>Customers
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('free-samples-tab').click();">
                            <i class="bi bi-gift me-2"></i>Free Sample Requests
                            <span class="badge bg-warning ms-2" id="freeSampleRequestsBadge" style="display: none;">0</span>
                        </a>
                    </li>
                    <li>
                    </li>
                </ul>
            </li>
            <li class="nav-item" role="presentation" style="display: none;">
                <button class="nav-link" id="void-transactions-tab" data-bs-toggle="tab" data-bs-target="#void-transactions" type="button" role="tab">
                    <i class="bi bi-x-circle me-2"></i>Void Transactions
                    <span class="badge bg-danger ms-2" id="voidRequestsBadge" style="display: none;">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation" style="display: none;">
                <button class="nav-link" id="discrepancies-tab" data-bs-toggle="tab" data-bs-target="#discrepancies" type="button" role="tab">
                    <i class="bi bi-exclamation-triangle me-2"></i>Discrepancies
                    <span class="badge bg-warning ms-2" id="discrepancyRequestsBadge" style="display: none;">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation" style="display: none;">
                <button class="nav-link" id="customers-tab" data-bs-toggle="tab" data-bs-target="#customers" type="button" role="tab">
                    <i class="bi bi-people me-2"></i>Customers
                </button>
            </li>
            <li class="nav-item" role="presentation" style="display: none;">
                <button class="nav-link" id="free-samples-tab" data-bs-toggle="tab" data-bs-target="#free-samples" type="button" role="tab">
                    <i class="bi bi-gift me-2"></i>Free Sample Requests
                    <span class="badge bg-warning ms-2" id="freeSampleRequestsBadge" style="display: none;">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation" style="display: none;">
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab">
                    <i class="bi bi-graph-up me-2"></i>Reports & Analytics
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab">
                    <i class="bi bi-gear me-2"></i>System Config
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="costing-tab" data-bs-toggle="tab" data-bs-target="#costing" type="button" role="tab">
                    <i class="bi bi-cash-stack me-2"></i>Costing
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="adminTabContent">
            <!-- Overview Tab -->
            <div class="tab-pane fade show active" id="overview" role="tabpanel">
                <div class="row g-4">
                    <!-- Analytics Charts -->
                    <div class="col-lg-8">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Sales Analytics</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-wrap chart-wrap-lg">
                                    <canvas id="salesChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-trophy me-2"></i>Top Selling Products</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-wrap chart-wrap-md">
                                    <canvas id="topProductsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats & Notifications -->
                    <div class="col-lg-4">
                        <!-- Stock Levels -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-boxes me-2"></i>Stock Levels</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-wrap chart-wrap-sm">
                                    <canvas id="stockLevelsChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- System Notifications -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="bi bi-bell me-2"></i>Notifications</h5>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-danger" id="notificationBadge">{{ $notificationCount ?? 0 }}</span>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="markAllNotificationsAsRead()" id="markAllReadBtn" style="display: none;">
                                            <i class="bi bi-check-all me-1"></i>Mark all as read
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="notification-list" id="notificationList">
                                    <!-- Dynamic notifications -->
                                </div>
                            </div>
                        </div>

                        <!-- Supplier Performance -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-truck me-2"></i>Supplier Performance</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-wrap chart-wrap-sm">
                                    <canvas id="supplierChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Management Tab -->
            <div class="tab-pane fade" id="users" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-people me-2"></i>User Management</h5>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="bi bi-person-plus me-1"></i>Add New User
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                    <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Last Active</th>
                                        <th>Actions</th>
                                </tr>
                            </thead>
                                <tbody id="usersTableBody">
                                    @php
                                        $usersList = $users ?? collect();
                                    @endphp
                                    @foreach($usersList as $user)
                                    @php
                                        $userName = $user->name ?? '';
                                        $userEmail = $user->email ?? '';
                                        $userRole = $user->role ?? 'user';
                                        $userId = $user->id ?? 0;
                                        $userUpdatedAt = $user->updated_at ?? null;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-2">
                                                    {{ strtoupper(substr($userName, 0, 1)) }}
                                                </div>
                                                <div>{{ $userName }}</div>
                                            </div>
                                        </td>
                                        <td>{{ $userEmail }}</td>
                                        <td>
                                            <span class="badge bg-secondary">{{ ucfirst($userRole) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">Active</span>
                                        </td>
                                        <td>{{ $userUpdatedAt ? $userUpdatedAt->diffForHumans() : '' }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" onclick="editUser({{ $userId }})">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" onclick="deactivateUser({{ $userId }})">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                    </td>
                                </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Activity Logs -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Recent Activity Logs</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="activity-log" id="activityLog">
                            <!-- Dynamic activity logs -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monitoring Tab -->
            <div class="tab-pane fade" id="monitoring" role="tabpanel">
                <div class="row g-4">
                    <!-- Cashier Monitoring -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-cash-register me-2"></i>Cashier Monitoring</h5>
                            </div>
                            <div class="card-body">
                                <div class="monitoring-section" id="cashierMonitoring">
                                    <!-- Dynamic cashier data -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Inventory Oversight -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Inventory Oversight</h5>
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Inventory shortcuts" id="inventoryPreviewFilters">
                                        <button type="button" class="btn btn-outline-warning active" data-inventory-view="low" onclick="loadInventoryView('low')">
                                            <i class="bi bi-exclamation-triangle"></i> Low Stock
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" data-inventory-view="out" onclick="loadInventoryView('out')">
                                            <i class="bi bi-x-circle"></i> Out of Stock
                                        </button>
                                        <button type="button" class="btn btn-outline-info" data-inventory-view="expiring" onclick="loadInventoryView('expiring')">
                                            <i class="bi bi-hourglass-split"></i> Expiring Soon
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="monitoring-section" id="inventoryMonitoring">
                                    <!-- Dynamic inventory data -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Inventory Preview (replaces Purchase Orders Pending Approval) -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm inventory-preview-card">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                                <h5 class="mb-0"><i class="bi bi-eye me-2"></i>Inventory Preview</h5>
                                <small class="text-muted" id="inventoryPreviewStatus">Ready</small>
                            </div>
                            <div class="card-body p-0 inventory-preview-body">
                                <div id="inventoryPreviewTableWrap" class="inventory-preview-scroll">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th>Item</th>
                                                <th class="text-end">Qty</th>
                                                <th class="text-end">SRP</th>
                                                <th>Expiry</th>
                                            </tr>
                                        </thead>
                                        <tbody id="inventoryPreviewBody">
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-3">Loading inventory...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="px-2 py-1 border-top bg-light">
                                    <small class="text-muted" id="inventoryPreviewCount">Showing 0 products</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Item Edit Logs -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <h5 class="mb-0"><i class="bi bi-journal-text me-2"></i>Item Edit Logs</h5>
                                    <div class="input-group input-group-sm" style="max-width: 280px;">
                                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                        <input type="search"
                                            id="itemEditLogSearch"
                                            class="form-control"
                                            placeholder="Search email, item, date"
                                            autocomplete="off">
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0" style="height: 420px; overflow-y: auto;">
                                @php
                                    $itemEditLogs = $itemEditLogs ?? collect();
                                @endphp
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm mb-0 align-middle">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th>Email</th>
                                                <th>Date</th>
                                                <th>Item</th>
                                                <th>Changes</th>
                                            </tr>
                                        </thead>
                                        <tbody id="itemEditLogsTableBody">
                                            @forelse($itemEditLogs as $editLog)
                                                <tr>
                                                    <td class="text-break" style="max-width: 160px;">{{ $editLog->user_email }}</td>
                                                    <td class="text-nowrap">{{ $editLog->formattedDate() }}</td>
                                                    <td>{{ $editLog->item_name }}</td>
                                                    <td>
                                                        @php $changeLines = $editLog->formattedChanges(); @endphp
                                                        @if(count($changeLines) === 0)
                                                            <span class="text-muted">—</span>
                                                        @else
                                                            <ul class="mb-0 ps-3 small">
                                                                @foreach($changeLines as $changeLine)
                                                                    <li>{{ $changeLine }}</li>
                                                                @endforeach
                                                            </ul>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center py-5 text-muted">
                                                        <i class="bi bi-clipboard-check" style="font-size: 2.5rem;"></i>
                                                        <p class="mt-3 mb-0">No item edits recorded yet</p>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Void Transactions Tab -->
            <div class="tab-pane fade" id="void-transactions" role="tabpanel">
                <div class="row g-4">
                    <!-- Pending Void Requests -->
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="bi bi-x-circle me-2"></i>Pending Void Transaction Requests</h5>
                                    <button class="btn btn-sm btn-primary" onclick="loadPendingVoidRequests()">
                                        <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Receipt Number</th>
                                                <th>Item Name</th>
                                                <th>Amount</th>
                                                <th>Requested By</th>
                                                <th>Reason</th>
                                                <th>Transaction Date</th>
                                                <th>Request Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="voidRequestsTableBody">
                                            <tr>
                                                <td colspan="8" class="text-center py-4">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div id="voidRequestsEmpty" class="text-center py-4" style="display: none;">
                                    <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                                    <p class="mt-3 text-muted">No pending void requests</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Void Transaction History -->
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Void Transaction History</h5>
                                    <button class="btn btn-sm btn-primary" onclick="loadVoidTransactionHistory()">
                                        <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Receipt Number</th>
                                                <th>Item Name</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Requested By</th>
                                                <th>Approved/Rejected By</th>
                                                <th>Reason</th>
                                                <th>Transaction Date</th>
                                                <th>Request Date</th>
                                                <th>Processed Date</th>
                                            </tr>
                                        </thead>
                                        <tbody id="voidHistoryTableBody">
                                            <tr>
                                                <td colspan="10" class="text-center py-4">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div id="voidHistoryEmpty" class="text-center py-4" style="display: none;">
                                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                    <p class="mt-3 text-muted">No void transaction history</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Discrepancies Tab -->
            <div class="tab-pane fade" id="discrepancies" role="tabpanel">
                <div class="row g-4">
                    <!-- Pending Discrepancy Requests -->
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Pending Discrepancy Requests</h5>
                                    <button class="btn btn-sm btn-primary" onclick="loadPendingDiscrepancyRequests()">
                                        <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Item Name</th>
                                                <th>Quantity</th>
                                                <th>Description</th>
                                                <th>Reason</th>
                                                <th>Requested By</th>
                                                <th>Request Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="discrepancyRequestsTableBody">
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div id="discrepancyRequestsEmpty" class="text-center py-4" style="display: none;">
                                    <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                                    <p class="mt-3 text-muted">No pending discrepancy requests</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Discrepancy History -->
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Discrepancy History</h5>
                                    <button class="btn btn-sm btn-primary" onclick="loadDiscrepancyHistory()">
                                        <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Item Name</th>
                                                <th>Quantity</th>
                                                <th>Description</th>
                                                <th>Reason</th>
                                                <th>Status</th>
                                                <th>Requested By</th>
                                                <th>Approved/Rejected By</th>
                                                <th>Request Date</th>
                                                <th>Processed Date</th>
                                            </tr>
                                        </thead>
                                        <tbody id="discrepancyHistoryTableBody">
                                            <tr>
                                                <td colspan="9" class="text-center py-4">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div id="discrepancyHistoryEmpty" class="text-center py-4" style="display: none;">
                                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                    <p class="mt-3 text-muted">No discrepancy history</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customers Tab -->
            <div class="tab-pane fade" id="customers" role="tabpanel">
                <div class="row g-4">
                    <!-- Add Customer Form -->
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-person-plus me-2"></i>Add New Customer</h5>
                            </div>
                            <div class="card-body">
                                <form id="customerForm">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Registered Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control text-uppercase" id="registered_name" name="registered_name" required style="text-transform: uppercase;">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">TIN (Tax Identification Number)</label>
                                                <input type="text" class="form-control text-uppercase" id="tin" name="tin" placeholder="e.g., 123-456-789-000" style="text-transform: uppercase;">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Business Address</label>
                                        <textarea class="form-control text-uppercase" id="business_address" name="business_address" rows="3" placeholder="Enter business address..." style="text-transform: uppercase;"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                            <label class="form-check-label" for="is_active">
                                                Active
                                            </label>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-secondary" onclick="resetCustomerForm()">Reset</button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save me-1"></i>Save Customer
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Customers List -->
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="bi bi-people me-2"></i>Customer List</h5>
                                    <div class="input-group" style="max-width: 300px;">
                                        <input type="text" class="form-control form-control-sm" id="customerListSearch" placeholder="Search customers...">
                                        <button class="btn btn-outline-secondary btn-sm" type="button" onclick="searchCustomers()">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Registered Name</th>
                                                <th>TIN</th>
                                                <th>Business Address</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="customersTableBody">
                                            <tr>
                                                <td colspan="5" class="text-center py-4">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div id="customersEmpty" class="text-center py-4" style="display: none;">
                                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                    <p class="mt-3 text-muted">No customers found</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Free Sample Requests Tab -->
            <div class="tab-pane fade" id="free-samples" role="tabpanel">
                <div class="row g-4">
                    <!-- Free Sample Requests -->
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="bi bi-gift me-2"></i>Free Sample Requests</h5>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-warning" id="freeSamplePendingBadge">{{ $sampleRequestStats['pending'] }}</span>
                                        <button class="btn btn-sm btn-primary" onclick="loadFreeSampleRequests()">
                                            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Cashier</th>
                                                <th>Customer Name</th>
                                                <th>Item Requested</th>
                                                <th>Qty</th>
                                                <th>Purpose</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="freeSampleRequestsTableBody">
                                            @foreach($allSampleRequests as $request)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="user-avatar me-2">
                                                            {{ strtoupper(substr($request->user->name ?? 'U', 0, 1)) }}
                                                        </div>
                                                        <div>{{ $request->user->name ?? 'Unknown' }}</div>
                                                    </div>
                                                </td>
                                                <td>{{ $request->customer_name ?? 'N/A' }}</td>
                                                <td>{{ $request->item_name }}</td>
                                                <td>{{ $request->quantity }}</td>
                                                <td class="text-truncate" style="max-width: 200px;" title="{{ $request->reason ?? 'N/A' }}">
                                                    {{ $request->reason ? Str::limit($request->reason, 30) : 'N/A' }}
                                                </td>
                                                <td>{{ $request->created_at->format('M d, Y') }}</td>
                                                <td>
                                                    @if($request->status === 'pending')
                                                        <span class="badge bg-warning">Pending</span>
                                                    @elseif($request->status === 'approved')
                                                        <span class="badge bg-success">Approved</span>
                                                    @else
                                                        <span class="badge bg-danger">Rejected</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($request->status === 'pending')
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-success" onclick="approveRequest({{ $request->id }})">
                                                            <i class="bi bi-check-circle"></i>
                                                        </button>
                                                        <button class="btn btn-danger" onclick="rejectRequest({{ $request->id }})">
                                                            <i class="bi bi-x-circle"></i>
                                                        </button>
                                                    </div>
                                                    @else
                                                        <button class="btn btn-outline-info btn-sm" onclick="viewRequestDetails({{ $request->id }})">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div id="freeSampleRequestsEmpty" class="text-center py-4" style="display: none;">
                                    <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                                    <p class="mt-3 text-muted">No free sample requests</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Reports & Analytics Tab -->
            <div class="tab-pane fade" id="reports" role="tabpanel">
                <!-- Shift Reports Section -->
                <div class="row g-4 mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Shift Reports</h5>
                                <button class="btn btn-light btn-sm" onclick="refreshShiftReports()">
                                    <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                                </button>
                            </div>
                            <div class="card-body">
                                <!-- Filter Section -->
                                <div class="row mb-3 g-3">
                                    <div class="col-md-2">
                                        <label class="form-label">Report Period</label>
                                        <select class="form-select" id="reportPeriodFilter" onchange="handlePeriodChange()">
                                            <option value="daily">Daily Reports</option>
                                            <option value="weekly">Weekly Reports</option>
                                            <option value="biweekly">Biweekly Reports</option>
                                            <option value="monthly">Monthly Reports</option>
                                            <option value="annual">Annual Reports</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Filter by Date</label>
                                        <input type="date" class="form-control" id="shiftDateFilter" value="">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="shiftEndDateFilter" style="display: none;">
                                        <input type="text" class="form-control" id="periodDisplay" readonly style="display: none;">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Filter by Cashier</label>
                                        <select class="form-select" id="cashierFilter">
                                            <option value="">All Cashiers</option>
                                            @php
                                                $cashierUsers = $users ?? collect();
                                            @endphp
                                            @foreach($cashierUsers as $user)
                                                @php
                                                    $isAdmin = isset($user->is_admin) && $user->is_admin;
                                                    $cashierId = $user->id ?? '';
                                                    $cashierName = $user->name ?? '';
                                                @endphp
                                                @if(!$isAdmin)
                                                    <option value="{{ $cashierId }}">{{ $cashierName }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" id="shiftStatusFilter">
                                            <option value="" selected>All Status</option>
                                            <option value="active">Active</option>
                                            <option value="closed">Closed</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">&nbsp;</label>
                                        <button class="btn btn-primary w-100" onclick="applyShiftFilters()">
                                            <i class="bi bi-funnel me-1"></i>Apply Filters
                                        </button>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover" id="shiftReportsTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Shift ID</th>
                                                <th>Cashier</th>
                                                <th>Start Time</th>
                                                <th>End Time</th>
                                                <th>Duration</th>
                                                <th>Total Sales</th>
                                                <th>Transactions</th>
                                                <th>Cash Diff.</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="shiftReportsBody">
                                            <tr>
                                                <td colspan="10" class="text-center text-muted py-4">
                                                    <i class="bi bi-clock-history fs-1 d-block mb-2"></i>
                                                    Loading shift reports...
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Today's Sales Report -->
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-file-earmark-bar-graph me-2"></i>Today's Sales Report</h5>
                                <div>
                                    <button type="button" class="btn btn-success btn-sm me-2" onclick="downloadTodayReport()">
                                        <i class="bi bi-download me-1"></i>Download
                                    </button>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="printTodayReport()">
                                        <i class="bi bi-printer me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                            <div class="card-body" id="todaySalesReportContent">
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm" id="todaySalesReportTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Item Name</th>
                                                <th>Quantity</th>
                                                <th>Unit Price</th>
                                                <th>Discount</th>
                                                <th>Total</th>
                                                <th>Receipt #</th>
                                                <th>Time</th>
                                            </tr>
                                        </thead>
                                        <tbody id="todaySalesReportBody">
                                            @if(isset($todaySoldItems) && count($todaySoldItems) > 0)
                                                @foreach($todaySoldItems as $item)
                                                <tr>
                                                    <td>{{ $item['name'] }}</td>
                                                    <td>{{ $item['quantity'] }}</td>
                                                    <td>₱{{ number_format($item['unit_price'], 2) }}</td>
                                                    <td>
                                                        @if(isset($item['discount']) && $item['discount'] > 0)
                                                            <span class="text-success">{{ $item['discount_display'] ?? number_format($item['discount'], 2) }}</span>
                                                            <br><small class="text-muted">(-₱{{ number_format($item['discount'], 2) }})</small>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>₱{{ number_format($item['total'], 2) }}</td>
                                                    <td>{{ $item['receipt_number'] }}</td>
                                                    <td>{{ $item['time'] }}</td>
                                                </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted">No items sold today</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th colspan="3" class="text-end">Total:</th>
                                                <th></th>
                                                <th>₱{{ number_format($todaySoldItemsTotal ?? 0, 2) }}</th>
                                                <th colspan="2"></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- All Sales Report -->
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-receipt-cutoff me-2"></i>All Sales Report</h5>
                                <div>
                                    <button class="btn btn-light btn-sm me-2" onclick="refreshSalesReport()">
                                        <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                                    </button>
                                    <button type="button" class="btn btn-success btn-sm me-2" onclick="downloadSalesReport()">
                                        <i class="bi bi-download me-1"></i>Download
                                    </button>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="printSalesReport()">
                                        <i class="bi bi-printer me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Date Filter Section -->
                                <div class="row mb-3 g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="salesStartDate" value="">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="salesEndDate" value="">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Filter by Cashier</label>
                                        <select class="form-select" id="salesCashierFilter">
                                            <option value="">All Cashiers</option>
                                            @php
                                                $cashierUsers = $users ?? collect();
                                            @endphp
                                            @foreach($cashierUsers as $user)
                                                @php
                                                    $isAdmin = isset($user->is_admin) && $user->is_admin;
                                                    $cashierId = $user->id ?? '';
                                                    $cashierName = $user->name ?? '';
                                                @endphp
                                                @if(!$isAdmin)
                                                    <option value="{{ $cashierId }}">{{ $cashierName }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">&nbsp;</label>
                                        <button class="btn btn-primary w-100" onclick="applySalesFilters()">
                                            <i class="bi bi-funnel me-1"></i>Apply Filters
                                        </button>
                                    </div>
                                </div>

                                <!-- Summary Cards -->
                                <div class="row mb-3 g-2" id="salesReportSummary" style="display: none;">
                                    <div class="col-6 col-md-3">
                                        <div class="card bg-primary text-white">
                                            <div class="card-body p-3">
                                                <h6 class="card-title mb-1 small">Total Sales</h6>
                                                <h5 class="mb-0 fw-bold" id="totalSalesAmount">₱0.00</h5>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="card bg-success text-white">
                                            <div class="card-body p-3">
                                                <h6 class="card-title mb-1 small">Total Transactions</h6>
                                                <h5 class="mb-0 fw-bold" id="totalTransactionsCount">0</h5>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="card bg-info text-white">
                                            <div class="card-body p-3">
                                                <h6 class="card-title mb-1 small">Average Transaction</h6>
                                                <h5 class="mb-0 fw-bold" id="averageTransactionAmount">₱0.00</h5>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="card bg-warning text-white">
                                            <div class="card-body p-3">
                                                <h6 class="card-title mb-1 small">Items Sold</h6>
                                                <h5 class="mb-0 fw-bold" id="totalItemsSold">0</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover" id="salesReportTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Receipt #</th>
                                                <th>Date & Time</th>
                                                <th>Cashier</th>
                                                <th>Items</th>
                                                <th>Quantity</th>
                                                <th>Unit Price</th>
                                                <th>Discount</th>
                                                <th>Total Amount</th>
                                                <th>Payment Method</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="salesReportBody">
            <tr>
                <td colspan="10" class="text-center text-muted py-4">
                    <i class="bi bi-receipt-cutoff fs-1 d-block mb-2"></i>
                    Select date range and click "Apply Filters" to load sales report
                </td>
            </tr>
                                        </tbody>
                                        <tfoot class="table-light" id="salesReportFooter" style="display: none;">
                                            <tr>
                                                <th colspan="5" class="text-end">Grand Total:</th>
                                                <th class="text-end">—</th>
                                                <th class="text-end" id="footerDiscount">₱0.00</th>
                                                <th class="text-end" id="footerTotalAmount">₱0.00</th>
                                                <th colspan="2"></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Summary -->
                    <div class="col-lg-8">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-currency-dollar me-2"></i>Financial Summary</h5>
                                <button class="btn btn-light btn-sm" onclick="refreshFinancialSummary()">
                                    <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                                </button>
                            </div>
                            <div class="card-body">
                                <!-- Financial Summary Filters -->
                                <div class="row mb-3 g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Period</label>
                                        <select class="form-select" id="financialPeriodFilter" onchange="handleFinancialPeriodChange()">
                                            <option value="daily">Daily</option>
                                            <option value="weekly">Weekly</option>
                                            <option value="biweekly">Biweekly</option>
                                            <option value="monthly" selected>Monthly</option>
                                            <option value="annual">Annual</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="financialStartDate" value="{{ date('Y-m-d') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="financialEndDate" style="display: none;">
                                        <input type="text" class="form-control" id="financialPeriodDisplay" readonly style="display: none;">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">&nbsp;</label>
                                        <button class="btn btn-primary w-100" onclick="applyFinancialFilters()" id="applyFinancialBtn">
                                            <i class="bi bi-funnel me-1"></i>Apply Filters
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="row g-3" id="financialSummaryCards">
                                    <div class="col-md-4">
                                        <div class="financial-card">
                                            <div class="financial-label">Total Revenue</div>
                                            <div class="financial-value text-success" id="financialRevenue">₱{{ number_format($totalRevenue ?? 0, 2) }}</div>
                                            <div class="financial-change" id="financialRevenueChange">
                                                <span class="{{ ($revenueChange ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ ($revenueChange ?? 0) >= 0 ? '+' : '' }}{{ number_format($revenueChange ?? 0, 1) }}% from last period
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="financial-card">
                                            <div class="financial-label">Expenses</div>
                                            <div class="financial-value text-danger" id="financialExpenses">₱{{ number_format($totalExpenses ?? 0, 2) }}</div>
                                            <div class="financial-change" id="financialExpensesChange">
                                                <span class="{{ ($expensesChange ?? 0) <= 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ ($expensesChange ?? 0) >= 0 ? '+' : '' }}{{ number_format($expensesChange ?? 0, 1) }}% from last period
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="financial-card">
                                            <div class="financial-label">Net Profit</div>
                                            <div class="financial-value text-primary" id="financialProfit">₱{{ number_format($netProfit ?? 0, 2) }}</div>
                                            <div class="financial-change" id="financialProfitChange">
                                                <span class="{{ ($profitChange ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ ($profitChange ?? 0) >= 0 ? '+' : '' }}{{ number_format($profitChange ?? 0, 1) }}% from last period
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <div class="chart-wrap chart-wrap-md">
                                        <canvas id="revenueChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Inventory Valuation -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Inventory Valuation</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="valuation-card">
                                            <div class="valuation-label">Total Stock Value</div>
                                            <div class="valuation-value">₱{{ number_format($stockValue ?? 0, 2) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="valuation-card">
                                            <div class="valuation-label">Total Items</div>
                                            <div class="valuation-value">{{ $totalItems ?? 0 }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Configuration Tab -->
            <div class="tab-pane fade" id="system" role="tabpanel">
                <div class="row g-4">
                    <!-- Company Information -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-building me-2"></i>Company Information</h5>
                            </div>
                            <div class="card-body">
                                <form id="companyInfoForm">
                                    <div class="mb-3">
                                        <label class="form-label">Company Name</label>
                                        <input type="text" class="form-control" value="REDEMP Medical Supplies">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Address</label>
                                        <textarea class="form-control" rows="3">123 Medical Street, Health City</textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Contact Number</label>
                                        <input type="tel" class="form-control" value="(02) 123-4567">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" value="info@redemp.com">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Logo</label>
                                        <input type="file" class="form-control" accept="image/*">
                                    </div>
                                    <button type="button" class="btn btn-primary" onclick="saveCompanyInfo()">
                                        <i class="bi bi-save me-2"></i>Save Changes
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Tax & Discount Settings -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-calculator me-2"></i>Tax & Discount Settings</h5>
                            </div>
                            <div class="card-body">
                                <form id="taxSettingsForm">
                                    <div class="mb-3">
                                        <label class="form-label">VAT Rate (%)</label>
                                        <input type="number" class="form-control" id="vatRateInput" value="12" step="0.01" min="0" max="100">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Maximum Discount (%)</label>
                                        <input type="number" class="form-control" id="maxDiscountInput" value="20" step="0.01" min="0" max="100">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Rounding Preference</label>
                                        <select class="form-select">
                                            <option>Round to nearest peso</option>
                                            <option>Round to 2 decimal places</option>
                                            <option>No rounding</option>
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-primary" onclick="saveTaxSettings()">
                                        <i class="bi bi-save me-2"></i>Save Changes
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Backup & Restore -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-cloud-arrow-down me-2"></i>Backup & Restore</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Automatic Backup Schedule</label>
                                    <select class="form-select">
                                        <option>Daily at 2:00 AM</option>
                                        <option>Weekly</option>
                                        <option>Monthly</option>
                                        <option>Disabled</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Backup Location</label>
                                    <select class="form-select">
                                        <option>Local Server</option>
                                        <option>Cloud Storage</option>
                                        <option>Both</option>
                                    </select>
                                </div>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-success" onclick="performBackup()">
                                        <i class="bi bi-cloud-download me-2"></i>Backup Now
                                    </button>
                                    <button class="btn btn-warning" onclick="restoreBackup()">
                                        <i class="bi bi-cloud-upload me-2"></i>Restore from Backup
                                    </button>
                                </div>
                                <div class="mt-3">
                                    <small class="text-muted">Last backup: {{ now()->subHours(3)->diffForHumans() }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Costing Tab -->
            <div class="tab-pane fade" id="costing" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-cash-stack me-2"></i>Costing Inventory</h5>
                        <small class="text-muted">Inventory view for costing</small>
                    </div>
                    <div class="card-body p-0">
                        <iframe
                            id="costingInventoryFrame"
                            src="{{ route('admin.inventory.preview') }}"
                            style="width: 100%; height: 70vh; border: 0;"
                            loading="lazy"
                        ></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addUserForm">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="name" id="addUserName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="addUserEmail" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" id="addUserPassword" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role" id="addUserRole" required>
                            <option value="">Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="inventory">Inventory Manager</option>
                            <option value="cashier">Cashier</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveNewUser()">Add User</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" id="editUserId" name="user_id">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="editUserName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="editUserEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <small class="text-muted">(Leave blank to keep current password)</small></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="editUserPassword" name="password" placeholder="Enter new password or leave blank">
                            <button class="btn btn-outline-secondary" type="button" id="toggleEditPassword" onclick="toggleEditPasswordVisibility()">
                                <i class="bi bi-eye" id="editPasswordIcon"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" id="editUserRole" name="role" required>
                            <option value="">Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="inventory">Inventory Manager</option>
                            <option value="cashier">Cashier</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveEditedUser()">Update User</button>
            </div>
        </div>
    </div>
</div>

<!-- System Config Modal -->
<div class="modal fade" id="systemConfigModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">System Configuration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="config-section">
                    <h6>eReceipt Settings</h6>
                    <div class="mb-3">
                        <label class="form-label">Header Text</label>
                        <input type="text" class="form-control" value="Thank you for your purchase!">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Footer Text</label>
                        <textarea class="form-control" rows="2">For questions or concerns, please contact us at info@redemp.com</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Receipt Template</label>
                        <select class="form-select">
                            <option>Professional</option>
                            <option>Simple</option>
                            <option>Detailed</option>
                        </select>
                </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveSystemConfig()">Save Settings</button>
            </div>
        </div>
    </div>
</div>

<!-- Sale Items Modal -->
<div class="modal fade" id="saleItemsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-cart-check me-2"></i>Sale Items - <span id="saleItemsUserName">Loading...</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="saleItemsLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading sale items...</p>
                </div>
                <div id="saleItemsContent" style="display: none;">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-1">Total Sales</h6>
                                    <h4 class="mb-0 text-primary" id="saleItemsTotalSales">₱0.00</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-1">Transactions</h6>
                                    <h4 class="mb-0 text-info" id="saleItemsTransactionCount">0</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-1">Items Sold</h6>
                                    <h4 class="mb-0 text-success" id="saleItemsTotalItems">0</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-primary">
                                <tr>
                                    <th>Item Name</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody id="saleItemsTableBody">
                                <!-- Items will be populated here -->
                            </tbody>
                        </table>
                    </div>
                    <div id="saleItemsEmpty" class="text-center py-4" style="display: none;">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="text-muted mt-2">No items found for this cashier from closed shifts today.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js (local first, CDN fallback) -->
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
    (function() {
        if (typeof Chart !== 'undefined') {
            return;
        }
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
        script.onload = function() {
            if (typeof initializeCharts === 'function') {
                initializeCharts();
            }
        };
        document.head.appendChild(script);
    })();
</script>

<script>
// Store users data in JavaScript for edit/delete functions
const usersData = @json($users ?? []);

// Pass monitoring data from server to JavaScript
const monitoringData = {
    activeCashiers: {{ $activeCashiers ?? 0 }},
    todayTransactions: {{ $todayTransactions ?? 0 }},
    pendingRefunds: {{ $pendingRefunds ?? 0 }},
    averageTransaction: {{ number_format($averageTransaction ?? 0, 2, '.', '') }},
    totalProducts: {{ $totalProducts ?? 0 }},
    lowStockItems: {{ $lowStockCount ?? 0 }},
    expiringSoon: {{ $expiringSoon ?? 0 }},
    pendingPOs: {{ $pendingPOs ?? 0 }},
    outOfStock: {{ $outOfStockCount ?? 0 }}
};

document.addEventListener('DOMContentLoaded', function() {
    // Wait for Chart.js to load before initializing
    function waitForChartJs(callback, maxAttempts = 20) {
        if (typeof Chart !== 'undefined') {
            callback();
        } else if (maxAttempts > 0) {
            setTimeout(() => waitForChartJs(callback, maxAttempts - 1), 100);
        } else {
            console.error('Chart.js failed to load after multiple attempts');
            // Still try to initialize admin (other features should work)
            initializeAdmin();
        }
    }
    
    waitForChartJs(() => {
        initializeAdmin();
    });
});

function initializeAdmin() {
    initializeCharts();
    loadNotifications();
    loadActivityLogs();
    loadMonitoringData();
    loadTaxSettings();
    // Load pending discrepancy count for badge
    loadPendingDiscrepancyRequests();
    // Load pending free sample requests count for badge
    loadFreeSampleRequests();
    // Load pending void requests count for badge
    loadPendingVoidRequests();
    
    // Update date inputs to today's date on page load
    const todayString = getTodayLocalDateString();
    
    // Update shift date filter - FORCE update to today's date immediately
    const shiftDateFilter = document.getElementById('shiftDateFilter');
    if (shiftDateFilter) {
        shiftDateFilter.value = todayString; // Always set to today, don't check current value
        console.log('Page load: Updated shiftDateFilter to:', todayString);
    }
    
    // Also check and update shift date filter periodically to ensure it's always today
    setInterval(function() {
        const shiftDateFilterCheck = document.getElementById('shiftDateFilter');
        const reportPeriodFilterCheck = document.getElementById('reportPeriodFilter');
        if (shiftDateFilterCheck && reportPeriodFilterCheck) {
            const period = reportPeriodFilterCheck.value;
            if (period === 'daily') {
                const todayCheck = getTodayLocalDateString();
                if (shiftDateFilterCheck.value !== todayCheck) {
                    shiftDateFilterCheck.value = todayCheck;
                    console.log('Periodic check: Updated shiftDateFilter to:', todayCheck);
                    // Reload reports if Reports tab is active
                    const reportsTabCheck = document.getElementById('reports-tab');
                    if (reportsTabCheck && reportsTabCheck.classList.contains('active')) {
                        handlePeriodChange();
                        setTimeout(() => loadShiftReports(), 100);
                    }
                }
            }
        }
    }, 60000); // Check every minute
    
    // Update sales date filters
    const salesStartDate = document.getElementById('salesStartDate');
    const salesEndDate = document.getElementById('salesEndDate');
    if (salesStartDate) {
        salesStartDate.value = todayString;
    }
    if (salesEndDate) {
        salesEndDate.value = todayString;
    }
    
    // Initialize financial filters
    if (document.getElementById('financialPeriodFilter')) {
        const period = document.getElementById('financialPeriodFilter').value;
        if (period === 'monthly') {
            const today = new Date(); // Need today for date calculations
            const startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            const endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            document.getElementById('financialStartDate').value = startDate.toISOString().split('T')[0];
            document.getElementById('financialEndDate').value = endDate.toISOString().split('T')[0];
            document.getElementById('financialEndDate').style.display = 'block';
        }
    }
    
    // Refresh data every 30 seconds
    setInterval(refreshDashboard, 30000);
    
    // Check if date has changed and force page reload to get fresh server-side data
    // This ensures Today's Sales Report, Sales Analytics, and all reports use today's date
    let lastCheckedDate = new Date().toDateString();
    setInterval(function() {
        const currentDate = new Date().toDateString();
        if (currentDate !== lastCheckedDate) {
            lastCheckedDate = currentDate;
            console.log('Date changed - reloading page to refresh all dashboard data with today\'s date');
            // Reload page to ensure all server-side data (Today's Sales Report, Sales Analytics, etc.) uses today's date
            location.reload();
        }
    }, 60000); // Check every minute
    
    // Also check on page visibility change (when user returns to tab)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            const currentDate = new Date().toDateString();
            if (currentDate !== lastCheckedDate) {
                lastCheckedDate = currentDate;
                console.log('Date changed while tab was hidden - reloading page');
                location.reload();
            }
        }
    });
    
    // Refresh notifications every 60 seconds
    setInterval(loadNotifications, 60000);
    
    // Refresh activity logs every 60 seconds
    setInterval(loadActivityLogs, 60000);
}

function loadTaxSettings() {
    fetch('/settings/tax')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('vatRateInput').value = data.vat_rate || 12;
                document.getElementById('maxDiscountInput').value = data.max_discount || 20;
            }
        })
        .catch(error => {
            console.error('Error loading tax settings:', error);
        });
}

function initializeCharts() {
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded. Retrying in 500ms...');
        setTimeout(initializeCharts, 500);
        return;
    }
    
    // Overview charts are required; revenue chart is optional (financial tab)
    const salesCanvas = document.getElementById('salesChart');
    const topProductsCanvas = document.getElementById('topProductsChart');
    const stockLevelsCanvas = document.getElementById('stockLevelsChart');
    const supplierCanvas = document.getElementById('supplierChart');
    const revenueCanvas = document.getElementById('revenueChart');
    
    if (!salesCanvas || !topProductsCanvas || !stockLevelsCanvas || !supplierCanvas) {
        console.error('Overview chart canvas elements not found. Retrying in 500ms...');
        setTimeout(initializeCharts, 500);
        return;
    }
    
    try {
        // Sales Chart - using actual sales data
        const salesCtx = salesCanvas.getContext('2d');
        const dailySalesData = @json($dailySales ?? ['labels' => [], 'data' => []]);
        
        window.salesChartInstance = new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: (dailySalesData.labels && dailySalesData.labels.length)
                ? dailySalesData.labels
                : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Daily Sales (₱)',
                data: (dailySalesData.data && dailySalesData.data.length)
                    ? dailySalesData.data
                    : [0, 0, 0, 0, 0, 0, 0],
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Sales: ₱' + context.parsed.y.toLocaleString('en-US', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString('en-US', {
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            });
                        }
                    }
                }
            }
        }
    });

        // Top Products Chart - using actual sales data
        const topProductsCtx = topProductsCanvas.getContext('2d');
        const topProductsData = @json($topProducts ?? ['labels' => [], 'quantities' => [], 'revenues' => []]);
    
    // Generate colors dynamically
    const colors = [
        'rgba(255, 99, 132, 0.7)',
        'rgba(54, 162, 235, 0.7)',
        'rgba(255, 206, 86, 0.7)',
        'rgba(75, 192, 192, 0.7)',
        'rgba(153, 102, 255, 0.7)',
        'rgba(255, 159, 64, 0.7)',
        'rgba(199, 199, 199, 0.7)',
        'rgba(83, 102, 255, 0.7)',
        'rgba(255, 99, 255, 0.7)',
        'rgba(99, 255, 132, 0.7)'
    ];
    
    const productLabels = (topProductsData.labels && topProductsData.labels.length)
        ? topProductsData.labels
        : ['No sales yet'];
    const productQuantities = (topProductsData.quantities && topProductsData.quantities.length)
        ? topProductsData.quantities
        : [0];
    const productColors = productLabels.map((label, index) => colors[index % colors.length]);
    
    new Chart(topProductsCtx, {
        type: 'bar',
        data: {
            labels: productLabels,
            datasets: [{
                label: 'Units Sold',
                data: productQuantities,
                backgroundColor: productColors
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        afterLabel: function(context) {
                            const index = context.dataIndex;
                            const revenue = (topProductsData.revenues || [])[index] || 0;
                            return 'Revenue: ₱' + revenue.toLocaleString('en-US', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

        // Stock Levels Chart - using actual inventory data
        const stockCtx = stockLevelsCanvas.getContext('2d');
    @php
        $defaultStockLevels = ['labels' => [], 'data' => [], 'counts' => [], 'percentages' => []];
        $stockLevelsForJson = $stockLevels ?? $defaultStockLevels;
    @endphp
    const stockLevelsData = @json($stockLevelsForJson);
    
    new Chart(stockCtx, {
        type: 'doughnut',
        data: {
            labels: (stockLevelsData.labels && stockLevelsData.labels.length)
                ? stockLevelsData.labels
                : ['In Stock', 'Low Stock', 'Out of Stock'],
            datasets: [{
                data: (stockLevelsData.data && stockLevelsData.data.length)
                    ? stockLevelsData.data
                    : [0, 0, 0],
                backgroundColor: ['#28a745', '#ffc107', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const percentages = stockLevelsData.percentages || [0, 0, 0];
                            const percentage = percentages[context.dataIndex] !== undefined ? percentages[context.dataIndex] : 0;
                            return label + ': ' + value + ' items (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });

        // Supplier Performance Chart
        const supplierCtx = supplierCanvas.getContext('2d');
    new Chart(supplierCtx, {
        type: 'radar',
        data: {
            labels: ['Quality', 'Timeliness', 'Price', 'Service', 'Reliability'],
            datasets: [{
                label: 'MedSupply Co.',
                data: [90, 85, 80, 95, 88],
                backgroundColor: 'rgba(0, 123, 255, 0.2)',
                borderColor: '#007bff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

        // Revenue Chart - using actual monthly data (optional)
        if (revenueCanvas) {
        const revenueCtx = revenueCanvas.getContext('2d');
    const monthlyFinancials = @json($monthlyFinancials ?? ['labels' => [], 'revenues' => [], 'expenses' => []]);
    
    window.revenueChart = new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: monthlyFinancials.labels || [],
            datasets: [{
                label: 'Revenue',
                data: monthlyFinancials.revenues || [],
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Expenses',
                data: monthlyFinancials.expenses || [],
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ₱' + context.parsed.y.toLocaleString('en-US', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString('en-US', {
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            });
                        }
                    }
                }
            }
        }
    });
        }
    } catch (error) {
        console.error('Error initializing charts:', error);
        console.error('Error details:', {
            ChartJsLoaded: typeof Chart !== 'undefined',
            salesCanvas: !!salesCanvas,
            topProductsCanvas: !!topProductsCanvas,
            stockLevelsCanvas: !!stockLevelsCanvas,
            supplierCanvas: !!supplierCanvas,
            revenueCanvas: !!revenueCanvas,
            errorMessage: error.message,
            errorStack: error.stack
        });
    }
}

async function loadNotifications() {
    const notificationList = document.getElementById('notificationList');
    
    if (!notificationList) {
        console.error('Notification list element not found');
        return;
    }
    
    try {
        const response = await fetch('/api/notifications', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success && data.notifications && data.notifications.length > 0) {
            notificationList.innerHTML = data.notifications.map(notif => `
                <div class="notification-item notification-${notif.type} ${notif.read ? 'notification-read' : ''}" 
                     onclick="markNotificationAsRead('${notif.notification_type}', '${notif.notification_key}')"
                     style="cursor: pointer; transition: opacity 0.3s;">
                    <div class="notification-icon">
                        <i class="bi bi-${notif.icon || (notif.type === 'warning' ? 'exclamation-triangle' : notif.type === 'info' ? 'info-circle' : notif.type === 'danger' ? 'x-circle' : 'check-circle')}"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-message">${notif.message}</div>
                        <div class="notification-time">${notif.time}</div>
                    </div>
                    ${notif.read ? '<div class="notification-read-indicator"><i class="bi bi-check-circle-fill"></i></div>' : ''}
                </div>
            `).join('');
            
            // Update notification badge count
            const badge = document.getElementById('notificationBadge');
            if (badge) {
                badge.textContent = data.count || data.notifications.length;
                badge.style.display = data.count > 0 ? 'inline-block' : 'none';
            }
            
            // Show/hide mark all as read button
            const markAllBtn = document.getElementById('markAllReadBtn');
            if (markAllBtn) {
                markAllBtn.style.display = data.count > 0 ? 'inline-block' : 'none';
            }
        } else {
            notificationList.innerHTML = `
                <div class="notification-item notification-info">
                    <div class="notification-icon">
                        <i class="bi bi-info-circle"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-message">No new notifications</div>
                        <div class="notification-time">All caught up!</div>
                    </div>
                </div>
            `;
            
            // Update notification badge count
            const badge = document.getElementById('notificationBadge');
            if (badge) {
                badge.textContent = '0';
                badge.style.display = 'none';
            }
            
            // Hide mark all as read button
            const markAllBtn = document.getElementById('markAllReadBtn');
            if (markAllBtn) {
                markAllBtn.style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
        const errorMessage = error.message || 'Unknown error';
        notificationList.innerHTML = `
            <div class="notification-item notification-danger">
                <div class="notification-icon">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-message">Error loading notifications</div>
                    <div class="notification-time">${errorMessage}</div>
                </div>
            </div>
        `;
        
        // Update notification badge count to show error
        const badge = document.getElementById('notificationBadge');
        if (badge) {
            badge.textContent = '!';
        }
    }
}

async function markNotificationAsRead(notificationType, notificationKey) {
    try {
        const response = await fetch('/api/notifications/mark-read', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                notification_type: notificationType,
                notification_key: notificationKey
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            // Reload notifications to update the count
            await loadNotifications();
        }
    } catch (error) {
        console.error('Error marking notification as read:', error);
    }
}

async function markAllNotificationsAsRead() {
    try {
        const response = await fetch('/api/notifications/mark-all-read', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            // Reload notifications to update the count
            await loadNotifications();
        }
    } catch (error) {
        console.error('Error marking all notifications as read:', error);
        alert('Error marking all notifications as read. Please try again.');
    }
}

let itemEditLogSearchTimer = null;

function escapeItemEditLogHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function renderItemEditLogs(logs, isSearch) {
    const tbody = document.getElementById('itemEditLogsTableBody');
    if (!tbody) {
        return;
    }

    if (!Array.isArray(logs) || logs.length === 0) {
        const emptyMessage = isSearch ? 'No matching item edit logs' : 'No item edits recorded yet';
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="text-center py-5 text-muted">
                    <i class="bi bi-clipboard-check" style="font-size: 2.5rem;"></i>
                    <p class="mt-3 mb-0">${emptyMessage}</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = logs.map((log) => {
        const changeLines = Array.isArray(log.changes) ? log.changes : [];
        const changesHtml = changeLines.length === 0
            ? '<span class="text-muted">—</span>'
            : `<ul class="mb-0 ps-3 small">${changeLines.map((line) => `<li>${escapeItemEditLogHtml(line)}</li>`).join('')}</ul>`;

        return `
            <tr>
                <td class="text-break" style="max-width: 160px;">${escapeItemEditLogHtml(log.user_email)}</td>
                <td class="text-nowrap">${escapeItemEditLogHtml(log.date)}</td>
                <td>${escapeItemEditLogHtml(log.item_name)}</td>
                <td>${changesHtml}</td>
            </tr>
        `;
    }).join('');
}

async function searchItemEditLogs(searchTerm) {
    const tbody = document.getElementById('itemEditLogsTableBody');
    if (!tbody) {
        return;
    }

    const params = new URLSearchParams();
    if (searchTerm) {
        params.set('search', searchTerm);
    }

    try {
        const response = await fetch('/api/item-edit-logs?' + params.toString(), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        renderItemEditLogs(data.success ? (data.logs || []) : [], Boolean(searchTerm));
    } catch (error) {
        console.error('Error searching item edit logs:', error);
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="text-center py-4 text-danger">Unable to load item edit logs</td>
            </tr>
        `;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('itemEditLogSearch');
    if (!searchInput) {
        return;
    }

    searchInput.addEventListener('input', function () {
        clearTimeout(itemEditLogSearchTimer);
        itemEditLogSearchTimer = setTimeout(() => {
            searchItemEditLogs(searchInput.value.trim());
        }, 300);
    });
});

async function loadActivityLogs() {
    const activityLog = document.getElementById('activityLog');
    
    if (!activityLog) {
        console.error('Activity log element not found');
        return;
    }
    
    try {
        const response = await fetch('/api/activity-logs', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success && data.activities && data.activities.length > 0) {
            activityLog.innerHTML = data.activities.map(log => {
                // Make sale activities clickable
                const isClickable = log.type === 'sale' && log.user_id;
                const clickableClass = isClickable ? 'activity-log-item-clickable' : '';
                const cursorStyle = isClickable ? 'cursor: pointer;' : '';
                return `
                <div class="activity-log-item ${clickableClass}" 
                     ${isClickable ? `onclick="showSaleItems(${log.user_id}, '${log.user.replace(/'/g, "\\'")}')"` : ''}
                     style="${cursorStyle}">
                    <div class="activity-user">${log.user}</div>
                    <div class="activity-action">${log.action}</div>
                    <div class="activity-time">${log.time}</div>
                </div>
            `;
            }).join('');
        } else {
            activityLog.innerHTML = `
                <div class="activity-log-item">
                    <div class="activity-user">System</div>
                    <div class="activity-action">No recent activity</div>
                    <div class="activity-time">--</div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading activity logs:', error);
        activityLog.innerHTML = `
            <div class="activity-log-item">
                <div class="activity-user">System</div>
                <div class="activity-action">Error loading activity logs</div>
                <div class="activity-time">Please refresh</div>
            </div>
        `;
    }
}

async function showSaleItems(userId, userName) {
    const modal = new bootstrap.Modal(document.getElementById('saleItemsModal'));
    const loadingDiv = document.getElementById('saleItemsLoading');
    const contentDiv = document.getElementById('saleItemsContent');
    const emptyDiv = document.getElementById('saleItemsEmpty');
    const userNameSpan = document.getElementById('saleItemsUserName');
    const tableBody = document.getElementById('saleItemsTableBody');
    
    // Show modal and reset state
    modal.show();
    userNameSpan.textContent = userName;
    loadingDiv.style.display = 'block';
    contentDiv.style.display = 'none';
    emptyDiv.style.display = 'none';
    tableBody.innerHTML = '';
    
    try {
        const response = await fetch(`/api/user-sale-items?user_id=${userId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        loadingDiv.style.display = 'none';
        
        if (data.success && data.items && data.items.length > 0) {
            contentDiv.style.display = 'block';
            
            // Update summary cards
            document.getElementById('saleItemsTotalSales').textContent = `₱${parseFloat(data.total_sales || 0).toFixed(2)}`;
            document.getElementById('saleItemsTransactionCount').textContent = data.transaction_count || 0;
            document.getElementById('saleItemsTotalItems').textContent = data.total_items || 0;
            
            // Populate table
            tableBody.innerHTML = data.items.map(item => `
                <tr>
                    <td>${item.name || 'Unknown Product'}</td>
                    <td class="text-end">${parseFloat(item.quantity || 0).toFixed(2)} ${item.unit || 'pcs'}</td>
                    <td class="text-end">₱${parseFloat(item.price || 0).toFixed(2)}</td>
                    <td class="text-end"><strong>₱${parseFloat(item.total || 0).toFixed(2)}</strong></td>
                </tr>
            `).join('');
        } else {
            emptyDiv.style.display = 'block';
        }
    } catch (error) {
        console.error('Error loading sale items:', error);
        loadingDiv.style.display = 'none';
        emptyDiv.style.display = 'block';
        emptyDiv.innerHTML = `
            <i class="bi bi-exclamation-triangle fs-1 text-warning"></i>
            <p class="text-muted mt-2">Error loading sale items. Please try again.</p>
        `;
    }
}

function loadMonitoringData() {
    // Cashier Monitoring - using real data from server
    const cashierMonitoring = document.getElementById('cashierMonitoring');
    if (cashierMonitoring) {
        cashierMonitoring.innerHTML = `
            <div class="monitoring-item">
                <div class="monitoring-label">Active Cashiers</div>
                <div class="monitoring-value">${monitoringData.activeCashiers}</div>
            </div>
            <div class="monitoring-item">
                <div class="monitoring-label">Today's Transactions</div>
                <div class="monitoring-value">${monitoringData.todayTransactions}</div>
            </div>
            <div class="monitoring-item">
                <div class="monitoring-label">Pending Refunds</div>
                <div class="monitoring-value text-warning">${monitoringData.pendingRefunds}</div>
            </div>
            <div class="monitoring-item">
                <div class="monitoring-label">Average Transaction</div>
                <div class="monitoring-value">₱${parseFloat(monitoringData.averageTransaction).toFixed(2)}</div>
            </div>
        `;
    }

    // Inventory Monitoring - using real data from server
    const inventoryMonitoring = document.getElementById('inventoryMonitoring');
    if (inventoryMonitoring) {
        const outOfStockCount = (monitoringData.outOfStock ?? monitoringData.outOfStockItems ?? monitoringData.lowStockItems ?? 0);
        inventoryMonitoring.innerHTML = `
            <div class="monitoring-item">
                <div class="monitoring-label">Total Products</div>
                <div class="monitoring-value">${monitoringData.totalProducts}</div>
            </div>
            <div class="monitoring-item">
                <div class="monitoring-label">Low Stock Items</div>
                <div class="monitoring-value text-warning">${monitoringData.lowStockItems}</div>
            </div>
            <div class="monitoring-item">
                <div class="monitoring-label">Expiring Soon</div>
                <div class="monitoring-value text-danger">${monitoringData.expiringSoon}</div>
            </div>
            <div class="monitoring-item">
                <div class="monitoring-label">Out of Stock</div>
                <div class="monitoring-value text-danger">${outOfStockCount}</div>
            </div>
        `;
    }

    // Prefetch inventory once, then filter instantly on button clicks
    preloadInventoryPreviewData().then(() => loadInventoryView('low'));
}

let inventoryPreviewProducts = null;
let inventoryPreviewLoadPromise = null;
let inventoryPreviewCurrentView = 'low';

function setInventoryPreviewButtons(view) {
    const group = document.getElementById('inventoryPreviewFilters');
    if (!group) return;
    group.querySelectorAll('[data-inventory-view]').forEach((btn) => {
        const isActive = btn.getAttribute('data-inventory-view') === view;
        btn.classList.toggle('active', isActive);
    });
}

function preloadInventoryPreviewData() {
    if (inventoryPreviewProducts) {
        return Promise.resolve(inventoryPreviewProducts);
    }
    if (inventoryPreviewLoadPromise) {
        return inventoryPreviewLoadPromise;
    }

    const statusEl = document.getElementById('inventoryPreviewStatus');
    if (statusEl) statusEl.textContent = 'Loading...';

    inventoryPreviewLoadPromise = fetch('{{ route('admin.inventory.preview') }}?format=json', {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
    })
        .then((response) => {
            if (!response.ok) throw new Error('Failed to load inventory');
            return response.json();
        })
        .then((data) => {
            inventoryPreviewProducts = Array.isArray(data.products) ? data.products : [];
            if (statusEl) statusEl.textContent = 'Ready';
            return inventoryPreviewProducts;
        })
        .catch((error) => {
            console.error(error);
            inventoryPreviewLoadPromise = null;
            if (statusEl) statusEl.textContent = 'Error';
            const body = document.getElementById('inventoryPreviewBody');
            if (body) {
                body.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-3">Could not load inventory</td></tr>`;
            }
            throw error;
        });

    return inventoryPreviewLoadPromise;
}

function filterInventoryPreviewProducts(view, products) {
    const now = new Date();
    const in30Days = new Date(now.getTime() + (30 * 24 * 60 * 60 * 1000));

    return products.filter((product) => {
        const qty = parseFloat(product.quantity_on_hand || 0);
        const exp = product.expiration_date ? new Date(product.expiration_date + 'T00:00:00') : null;

        if (view === 'out') return qty <= 0;
        if (view === 'low') return qty > 0 && qty <= 10;
        if (view === 'expiring') {
            return exp && exp > now && exp <= in30Days;
        }
        return true;
    });
}

function renderInventoryPreview(view, products) {
    const body = document.getElementById('inventoryPreviewBody');
    const countEl = document.getElementById('inventoryPreviewCount');
    const statusEl = document.getElementById('inventoryPreviewStatus');
    if (!body) return;

    const filtered = filterInventoryPreviewProducts(view, products || []);
    const labels = { low: 'Low Stock', out: 'Out of Stock', expiring: 'Expiring Soon' };

    if (statusEl) statusEl.textContent = labels[view] || 'Preview';
    if (countEl) countEl.textContent = `Showing ${filtered.length} product${filtered.length === 1 ? '' : 's'}`;

    if (filtered.length === 0) {
        body.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-3">No products found</td></tr>`;
        return;
    }

    body.innerHTML = filtered.map((product) => {
        const name = product.item_name || 'Unknown';
        const qty = parseFloat(product.quantity_on_hand || 0);
        const price = parseFloat(product.price || 0).toFixed(2);
        const expiry = product.expiration_date || 'N/A';
        return `
            <tr>
                <td><div class="inventory-preview-item" title="${name}">${name}</div></td>
                <td class="text-end">${Number.isInteger(qty) ? qty : qty.toFixed(2)}</td>
                <td class="text-end">${price}</td>
                <td>${expiry}</td>
            </tr>
        `;
    }).join('');
}

// Instant client-side filter (data is preloaded once)
function loadInventoryView(view) {
    inventoryPreviewCurrentView = view || 'low';
    setInventoryPreviewButtons(inventoryPreviewCurrentView);

    // Immediate visual response even before data arrives
    const body = document.getElementById('inventoryPreviewBody');
    if (body && !inventoryPreviewProducts) {
        body.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-3">Loading inventory...</td></tr>`;
    }

    if (inventoryPreviewProducts) {
        renderInventoryPreview(inventoryPreviewCurrentView, inventoryPreviewProducts);
        return;
    }

    preloadInventoryPreviewData()
        .then((products) => renderInventoryPreview(inventoryPreviewCurrentView, products))
        .catch(() => {});
}

async function refreshDashboard() {
    // Refresh today's sales value
    try {
        const response = await fetch('/api/today-sales?' + new Date().getTime()); // Add timestamp to prevent caching
        const data = await response.json();
        if (data.success) {
            const todaySalesElement = document.getElementById('todaySalesValue');
            if (todaySalesElement) {
                todaySalesElement.textContent = data.formatted;
            }
        }
    } catch (error) {
        console.error('Error refreshing today\'s sales:', error);
    }
    
    // Refresh Sales Analytics chart with current data (non-blocking)
    // Don't await - let it run in background and fail silently if needed
    refreshSalesAnalyticsChart().catch(err => {
        // Silently handle - chart will use initial server-side data
        console.debug('Chart refresh failed (using cached data):', err.message);
    });
    
    // Refresh other dashboard data
    console.log('Refreshing dashboard data...');
}

// Function to refresh Sales Analytics chart with current data
async function refreshSalesAnalyticsChart() {
    try {
        // Fetch current daily sales data from server
        const response = await fetch('/api/daily-sales?' + new Date().getTime(), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });
        
        // Check if response is OK
        if (!response.ok) {
            console.warn('Failed to fetch daily sales data:', response.status, response.statusText);
            return; // Silently fail - chart will use initial data
        }
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            console.warn('Daily sales API returned non-JSON response');
            return; // Silently fail
        }
        
        const data = await response.json();
        
        if (data.success && data.dailySales) {
            const salesCanvas = document.getElementById('salesChart');
            if (salesCanvas && window.salesChartInstance) {
                // Update chart data
                window.salesChartInstance.data.labels = data.dailySales.labels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                window.salesChartInstance.data.datasets[0].data = data.dailySales.data || [0, 0, 0, 0, 0, 0, 0];
                window.salesChartInstance.update('none');
                console.log('Sales Analytics chart updated with current data');
            }
        }
    } catch (error) {
        // Silently handle error - chart will continue using initial data
        console.warn('Error refreshing Sales Analytics chart (non-critical):', error.message);
    }
}

function performBackup() {
    alert('Performing system backup...');
    // Implement backup functionality
}

function restoreBackup() {
    if (confirm('Are you sure you want to restore from backup? This will overwrite current data.')) {
        alert('Restoring from backup...');
        // Implement restore functionality
    }
}

function downloadTodayReport() {
    const table = document.getElementById('todaySalesReportTable');
    if (!table) {
        showNotification('Report table not found', 'error');
        return;
    }

    // Create CSV content
    let csv = 'Today\'s Sales Report\n';
    csv += 'Generated: ' + new Date().toLocaleString() + '\n\n';
    
    // Headers
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => {
        headers.push(th.textContent.trim());
    });
    csv += headers.join(',') + '\n';
    
    // Rows
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach((td, index) => {
            let text = td.textContent.trim();
            
            // Handle discount column specially - extract the percentage and amount
            if (index === 3) { // Discount column (4th column, 0-indexed)
                // Get the discount display text (percentage)
                const discountSpan = td.querySelector('.text-success');
                const discountAmount = td.querySelector('small.text-muted');
                if (discountSpan && discountAmount) {
                    // Format as "20% (-₱10.00)" or just the percentage
                    const percent = discountSpan.textContent.trim();
                    const amount = discountAmount.textContent.trim().replace(/[()₱-]/g, '').trim();
                    text = amount ? `${percent} (${amount})` : percent;
                } else if (text === '-') {
                    text = '-';
                }
            } else {
                // Remove currency symbols and format numbers for other columns
                text = text.replace('₱', '').replace(/,/g, '');
            }
            
            // Wrap in quotes if contains comma
            if (text.includes(',')) {
                text = '"' + text + '"';
            }
            row.push(text);
        });
        csv += row.join(',') + '\n';
    });
    
    // Total
    const totalRow = table.querySelector('tfoot tr');
    if (totalRow) {
        const totalCells = [];
        totalRow.querySelectorAll('th, td').forEach(cell => {
            let text = cell.textContent.trim();
            text = text.replace('₱', '').replace(/,/g, '');
            if (text.includes(',')) {
                text = '"' + text + '"';
            }
            totalCells.push(text);
        });
        csv += totalCells.join(',') + '\n';
    }
    
    // Create download link
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'today_sales_report_' + new Date().toISOString().split('T')[0] + '.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showNotification('Report downloaded successfully', 'success');
}

function printTodayReport() {
    const printContent = document.getElementById('todaySalesReportContent').innerHTML;
    const printWindow = window.open('', '_blank');
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Today's Sales Report</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                }
                h2 {
                    text-align: center;
                    margin-bottom: 20px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                }
                th {
                    background-color: #f2f2f2;
                    font-weight: bold;
                }
                tfoot th {
                    background-color: #e9ecef;
                }
                @media print {
                    body { margin: 0; }
                    @page { margin: 1cm; }
                }
            </style>
        </head>
        <body>
            <h2>Today's Sales Report</h2>
            <p><strong>Generated:</strong> ${new Date().toLocaleString()}</p>
            ${printContent}
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.focus();
    
    setTimeout(() => {
        printWindow.print();
        setTimeout(() => printWindow.close(), 1000);
    }, 250);
}

async function saveNewUser() {
    const form = document.getElementById('addUserForm');
    
    // Validate form
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    
    // Build request data
    const data = {
        name: formData.get('name'),
        email: formData.get('email'),
        password: formData.get('password'),
        role: formData.get('role'),
    };
    
    try {
        const response = await fetch('/admin/users', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('User created successfully!');
            bootstrap.Modal.getInstance(document.getElementById('addUserModal')).hide();
            form.reset(); // Reset form for next use
            location.reload(); // Reload to show new user
        } else {
            // Handle validation errors
            if (result.errors) {
                const errorMessages = Object.values(result.errors).flat().join('\n');
                alert('Error: ' + errorMessages);
            } else {
                alert('Error: ' + (result.message || 'Failed to create user'));
            }
        }
    } catch (error) {
        console.error('Error creating user:', error);
        alert('Error creating user. Please try again.');
    }
}

async function editUser(id) {
    try {
        // Find user from the stored data
        const user = usersData.find(u => u.id === id);
        
        if (!user) {
            alert('User not found');
            return;
        }
        
        // Populate the edit modal
        document.getElementById('editUserId').value = user.id;
        document.getElementById('editUserName').value = user.name;
        document.getElementById('editUserEmail').value = user.email;
        document.getElementById('editUserRole').value = user.role;
        document.getElementById('editUserPassword').value = '';
        
        // Reset password field to password type and icon
        const passwordInput = document.getElementById('editUserPassword');
        passwordInput.type = 'password';
        const passwordIcon = document.getElementById('editPasswordIcon');
        passwordIcon.className = 'bi bi-eye';
        
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
        modal.show();
    } catch (error) {
        console.error('Error loading user:', error);
        alert('Error loading user data. Please try again.');
    }
}

function toggleEditPasswordVisibility() {
    const passwordInput = document.getElementById('editUserPassword');
    const passwordIcon = document.getElementById('editPasswordIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordIcon.className = 'bi bi-eye-slash';
    } else {
        passwordInput.type = 'password';
        passwordIcon.className = 'bi bi-eye';
    }
}

async function saveEditedUser() {
    const form = document.getElementById('editUserForm');
    const formData = new FormData(form);
    const userId = formData.get('user_id');
    
    // Build request data
    const data = {
        name: formData.get('name'),
        email: formData.get('email'),
        role: formData.get('role'),
    };
    
    // Only include password if it's not empty
    const password = formData.get('password');
    if (password && password.trim() !== '') {
        data.password = password;
    }
    
    try {
        const response = await fetch(`/admin/users/${userId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('User updated successfully!');
            bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
            location.reload(); // Reload to show updated data
        } else {
            const errorMsg = result.message || (result.errors ? Object.values(result.errors).flat().join(', ') : 'Error updating user');
            alert(errorMsg);
        }
    } catch (error) {
        console.error('Error updating user:', error);
        alert('Error updating user. Please try again.');
    }
}

async function deactivateUser(id) {
    if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        return;
    }
    
    try {
        const response = await fetch(`/admin/users/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('User deleted successfully!');
            location.reload(); // Reload to show updated data
        } else {
            alert(result.message || 'Error deleting user');
        }
    } catch (error) {
        console.error('Error deleting user:', error);
        alert('Error deleting user. Please try again.');
    }
}

function saveCompanyInfo() {
    alert('Company information saved successfully!');
}

function saveTaxSettings() {
    const vatRate = parseFloat(document.getElementById('vatRateInput').value);
    const maxDiscount = parseFloat(document.getElementById('maxDiscountInput').value);
    
    if (isNaN(vatRate) || vatRate < 0 || vatRate > 100) {
        alert('Please enter a valid VAT rate between 0 and 100');
        return;
    }
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    
    fetch('/settings/tax', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken ? csrfToken.content : '',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            vat_rate: vatRate,
            max_discount: maxDiscount
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Tax settings saved successfully!');
            // Optionally reload the page or show a success notification
        } else {
            alert('Error saving tax settings: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error saving tax settings:', error);
        alert('Error saving tax settings. Please try again.');
    });
}

function saveSystemConfig() {
    alert('System configuration saved successfully!');
    bootstrap.Modal.getInstance(document.getElementById('systemConfigModal')).hide();
}

// Free Sample Request Functions
// Free Sample Request Functions
async function loadFreeSampleRequests() {
    try {
        const response = await fetch('/api/free-samples');
        const data = await response.json();
        
        const tbody = document.getElementById('freeSampleRequestsTableBody');
        const emptyDiv = document.getElementById('freeSampleRequestsEmpty');
        const badge = document.getElementById('freeSampleRequestsBadge');
        const pendingBadge = document.getElementById('freeSamplePendingBadge');
        
        if (data.success && data.requests && data.requests.length > 0) {
            const pendingCount = data.requests.filter(r => r.status === 'pending').length;
            
            tbody.innerHTML = data.requests.map(request => {
                const statusBadge = request.status === 'pending' 
                    ? '<span class="badge bg-warning">Pending</span>'
                    : request.status === 'approved'
                    ? '<span class="badge bg-success">Approved</span>'
                    : '<span class="badge bg-danger">Rejected</span>';
                
                const actions = request.status === 'pending'
                    ? `<div class="btn-group btn-group-sm">
                        <button class="btn btn-success" onclick="approveRequest(${request.id})">
                            <i class="bi bi-check-circle"></i>
                        </button>
                        <button class="btn btn-danger" onclick="rejectRequest(${request.id})">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>`
                    : `<button class="btn btn-outline-info btn-sm" onclick="viewRequestDetails(${request.id})">
                        <i class="bi bi-eye"></i>
                    </button>`;
                
                return `
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="user-avatar me-2">
                                    ${(request.user?.name || 'U').charAt(0).toUpperCase()}
                                </div>
                                <div>${request.user?.name || 'Unknown'}</div>
                            </div>
                        </td>
                        <td>${request.customer_name || 'N/A'}</td>
                        <td>${request.item_name}</td>
                        <td>${request.quantity}</td>
                        <td class="text-truncate" style="max-width: 200px;" title="${request.reason || 'N/A'}">
                            ${request.reason ? (request.reason.length > 30 ? request.reason.substring(0, 30) + '...' : request.reason) : 'N/A'}
                        </td>
                        <td>${new Date(request.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                        <td>${statusBadge}</td>
                        <td>${actions}</td>
                    </tr>
                `;
            }).join('');
            
            emptyDiv.style.display = 'none';
            if (badge) {
                badge.textContent = pendingCount;
                badge.style.display = pendingCount > 0 ? 'inline-block' : 'none';
            }
            if (pendingBadge) {
                pendingBadge.textContent = pendingCount;
            }
            // Also update badge in dropdown
            const dropdownBadge = document.querySelector('#approvalsDropdown + ul .dropdown-item:nth-child(4) .badge');
            if (dropdownBadge) {
                dropdownBadge.textContent = pendingCount;
                dropdownBadge.style.display = pendingCount > 0 ? 'inline-block' : 'none';
            }
        } else {
            tbody.innerHTML = '';
            emptyDiv.style.display = 'block';
            if (badge) {
                badge.style.display = 'none';
            }
            if (pendingBadge) {
                pendingBadge.textContent = '0';
            }
            // Hide badge in dropdown
            const dropdownBadge = document.querySelector('#approvalsDropdown + ul .dropdown-item:nth-child(4) .badge');
            if (dropdownBadge) {
                dropdownBadge.style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error loading free sample requests:', error);
        const tbody = document.getElementById('freeSampleRequestsTableBody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">Error loading requests. Please try again.</td></tr>';
        }
    }
}

function approveRequest(requestId) {
    if (confirm('Are you sure you want to approve this free sample request?')) {
        fetch(`/free-samples/${requestId}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => {
            return response.json().then(data => {
                if (!response.ok) {
                    throw new Error(data.message || 'Failed to approve request');
                }
                return data;
            });
        })
        .then(data => {
            if (data.success) {
                let message = 'Free sample request approved successfully!';
                if (data.warnings && data.warnings.length > 0) {
                    message += '\n\nWarnings:\n' + data.warnings.join('\n');
                }
                alert(message);
                showNotification('Free sample request approved successfully!', 'success');
                loadFreeSampleRequests();
            } else {
                showNotification('Failed to approve request: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error approving request:', error);
            showNotification('Error: ' + error.message, 'error');
        });
    }
}

function rejectRequest(requestId) {
    const reason = prompt('Please provide a reason for rejection (optional):');
    if (reason === null) {
        return; // User cancelled
    }
    
    fetch(`/free-samples/${requestId}/reject`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ reason: reason || '' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Free sample request rejected!', 'info');
            loadFreeSampleRequests();
        } else {
            showNotification('Failed to reject request: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error rejecting request:', error);
        showNotification('Error: ' + error.message, 'error');
    });
}

function viewRequestDetails(requestId) {
    // Implement view details modal
    alert('Viewing details for request ID: ' + requestId);
}

// Wholesale/Retail Request Functions
async function loadWholesaleRetailRequests() {
    try {
        const response = await fetch('/api/wholesale-to-retail-requests/pending');
        const data = await response.json();
        
        const tbody = document.getElementById('wholesaleRetailRequestsTableBody');
        const emptyDiv = document.getElementById('wholesaleRetailRequestsEmpty');
        const badge = document.getElementById('wholesaleRetailRequestsBadge');
        const pendingBadge = document.getElementById('wholesaleRetailPendingBadge');
        
        if (data.success && data.requests && data.requests.length > 0) {
            const pendingCount = data.requests.filter(r => r.status === 'pending').length;
            
            tbody.innerHTML = data.requests.map(request => {
                const statusBadge = request.status === 'pending' 
                    ? '<span class="badge bg-warning">Pending</span>'
                    : request.status === 'approved'
                    ? '<span class="badge bg-success">Approved</span>'
                    : '<span class="badge bg-danger">Rejected</span>';
                
                const actions = request.status === 'pending'
                    ? `<div class="btn-group btn-group-sm">
                        <button class="btn btn-success" onclick="approveWholesaleRetailRequest(${request.id})">
                            <i class="bi bi-check-circle"></i>
                        </button>
                        <button class="btn btn-danger" onclick="rejectWholesaleRetailRequest(${request.id})">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>`
                    : `<button class="btn btn-outline-info btn-sm" onclick="viewWholesaleRetailRequestDetails(${request.id})">
                        <i class="bi bi-eye"></i>
                    </button>`;
                
                return `
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="user-avatar me-2">
                                    ${(request.requested_by_user?.name || 'U').charAt(0).toUpperCase()}
                                </div>
                                <div>${request.requested_by_user?.name || 'Unknown'}</div>
                            </div>
                        </td>
                        <td>${request.retail_item?.item || 'N/A'}</td>
                        <td>${request.wholesale_item?.item || 'N/A'}</td>
                        <td>${request.quantity_to_convert || 'All'}</td>
                        <td class="text-truncate" style="max-width: 200px;" title="${request.request_notes || 'N/A'}">
                            ${request.request_notes ? (request.request_notes.length > 30 ? request.request_notes.substring(0, 30) + '...' : request.request_notes) : 'N/A'}
                        </td>
                        <td>${request.requested_at ? new Date(request.requested_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A'}</td>
                        <td>${statusBadge}</td>
                        <td>${actions}</td>
                    </tr>
                `;
            }).join('');
            
            emptyDiv.style.display = 'none';
            if (badge) {
                badge.textContent = pendingCount;
                badge.style.display = pendingCount > 0 ? 'inline-block' : 'none';
            }
            if (pendingBadge) {
                pendingBadge.textContent = pendingCount;
            }
            // Also update badge in dropdown
            const dropdownBadge = document.querySelector('#approvalsDropdown + ul .dropdown-item:nth-child(5) .badge');
            if (dropdownBadge) {
                dropdownBadge.textContent = pendingCount;
                dropdownBadge.style.display = pendingCount > 0 ? 'inline-block' : 'none';
            }
        } else {
            tbody.innerHTML = '';
            emptyDiv.style.display = 'block';
            if (badge) {
                badge.style.display = 'none';
            }
            if (pendingBadge) {
                pendingBadge.textContent = '0';
            }
            // Hide badge in dropdown
            const dropdownBadge = document.querySelector('#approvalsDropdown + ul .dropdown-item:nth-child(5) .badge');
            if (dropdownBadge) {
                dropdownBadge.style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error loading wholesale/retail requests:', error);
        const tbody = document.getElementById('wholesaleRetailRequestsTableBody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">Error loading requests. Please try again.</td></tr>';
        }
    }
}

async function approveWholesaleRetailRequest(requestId) {
    const adminNotes = prompt('Add admin notes (optional):');
    if (adminNotes === null) {
        return; // User cancelled
    }
    
    if (confirm('Are you sure you want to approve this wholesale/retail conversion request?')) {
        try {
            const response = await fetch(`/api/wholesale-to-retail-requests/${requestId}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    admin_notes: adminNotes || null
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification('Wholesale/retail conversion request approved successfully!', 'success');
                loadWholesaleRetailRequests();
            } else {
                showNotification('Failed to approve request: ' + (data.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Error approving request:', error);
            showNotification('Error: ' + error.message, 'error');
        }
    }
}

async function rejectWholesaleRetailRequest(requestId) {
    const adminNotes = prompt('Please provide a reason for rejection (required):');
    if (adminNotes === null || adminNotes.trim() === '') {
        alert('Rejection reason is required.');
        return;
    }
    
    if (confirm('Are you sure you want to reject this wholesale/retail conversion request?')) {
        try {
            const response = await fetch(`/api/wholesale-to-retail-requests/${requestId}/reject`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    admin_notes: adminNotes
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification('Wholesale/retail conversion request rejected.', 'info');
                loadWholesaleRetailRequests();
            } else {
                showNotification('Failed to reject request: ' + (data.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Error rejecting request:', error);
            showNotification('Error: ' + error.message, 'error');
        }
    }
}

function viewWholesaleRetailRequestDetails(requestId) {
    // Implement view details modal
    alert('Viewing details for wholesale/retail request ID: ' + requestId);
}

function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Shift Reports Functions
function handlePeriodChange() {
    const period = document.getElementById('reportPeriodFilter').value;
    const dateInput = document.getElementById('shiftDateFilter');
    const endDateInput = document.getElementById('shiftEndDateFilter');
    const periodDisplay = document.getElementById('periodDisplay');
    
    const today = new Date();
    let startDate, endDate, displayText;
    
    switch(period) {
        case 'daily':
            // Always use today's date for daily reports - FORCE UPDATE
            const todayDateString = getTodayLocalDateString();
            startDate = new Date(todayDateString + 'T00:00:00');
            endDate = new Date(todayDateString + 'T00:00:00');
            displayText = today.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            dateInput.style.display = 'block';
            endDateInput.style.display = 'none';
            periodDisplay.style.display = 'none';
            // ALWAYS set today's date for daily reports (force update, ignore current value)
            // This ensures the date is never stale
            if (dateInput.value !== todayDateString) {
                dateInput.value = todayDateString;
                console.log('handlePeriodChange: Updated date to today for daily reports:', todayDateString);
            }
            // Auto-reload reports after updating date
            setTimeout(() => loadShiftReports(), 100);
            break;
        case 'weekly':
            startDate = new Date(today);
            startDate.setDate(today.getDate() - today.getDay()); // Start of week (Sunday)
            endDate = new Date(startDate);
            endDate.setDate(startDate.getDate() + 6); // End of week (Saturday)
            displayText = `${startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
            dateInput.style.display = 'none';
            endDateInput.style.display = 'none';
            periodDisplay.style.display = 'block';
            periodDisplay.value = displayText;
            // Set hidden date values for filtering
            dateInput.value = startDate.toISOString().split('T')[0];
            endDateInput.value = endDate.toISOString().split('T')[0];
            break;
        case 'biweekly':
            startDate = new Date(today);
            startDate.setDate(today.getDate() - 14);
            endDate = new Date(today);
            displayText = `${startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
            dateInput.style.display = 'none';
            endDateInput.style.display = 'none';
            periodDisplay.style.display = 'block';
            periodDisplay.value = displayText;
            // Set hidden date values for filtering
            dateInput.value = startDate.toISOString().split('T')[0];
            endDateInput.value = endDate.toISOString().split('T')[0];
            break;
        case 'monthly':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            displayText = today.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            dateInput.style.display = 'none';
            endDateInput.style.display = 'none';
            periodDisplay.style.display = 'block';
            periodDisplay.value = displayText;
            // Set hidden date values for filtering
            dateInput.value = startDate.toISOString().split('T')[0];
            endDateInput.value = endDate.toISOString().split('T')[0];
            break;
        case 'annual':
            startDate = new Date(today.getFullYear(), 0, 1);
            endDate = new Date(today.getFullYear(), 11, 31);
            displayText = today.getFullYear().toString();
            dateInput.style.display = 'none';
            endDateInput.style.display = 'none';
            periodDisplay.style.display = 'block';
            periodDisplay.value = displayText;
            // Set hidden date values for filtering
            dateInput.value = startDate.toISOString().split('T')[0];
            endDateInput.value = endDate.toISOString().split('T')[0];
            break;
    }
}

async function loadShiftReports() {
    try {
        // Get filter values
        const periodFilter = document.getElementById('reportPeriodFilter')?.value || 'daily';
        let dateFilter = document.getElementById('shiftDateFilter')?.value || '';
        const endDateFilter = document.getElementById('shiftEndDateFilter')?.value || '';
        const cashierFilter = document.getElementById('cashierFilter')?.value || '';
        const statusFilter = document.getElementById('shiftStatusFilter')?.value || '';
        
        // Calculate date range based on period
        let startDate = '';
        let endDate = '';
        
        // If period is daily, ALWAYS use today's date (ignore any stale dateFilter value)
        if (periodFilter === 'daily') {
            const todayString = getTodayLocalDateString();
            startDate = todayString;
            endDate = todayString;
            // ALWAYS update the date filter input to today's date (force update)
            const shiftDateFilterEl = document.getElementById('shiftDateFilter');
            if (shiftDateFilterEl) {
                // Force update regardless of current value - use setAttribute to ensure it sticks
                shiftDateFilterEl.value = todayString;
                shiftDateFilterEl.setAttribute('value', todayString);
                console.log('loadShiftReports: Force updated shiftDateFilter to today:', todayString, 'Current value:', shiftDateFilterEl.value);
            }
            // Override dateFilter to use today
            dateFilter = todayString;
        } else {
            // For non-daily periods, calculate date range
            const today = new Date();
            let start, end;
            
            switch(periodFilter) {
                case 'weekly':
                    start = new Date(today);
                    start.setDate(today.getDate() - today.getDay()); // Start of week (Sunday)
                    end = new Date(start);
                    end.setDate(start.getDate() + 6); // End of week (Saturday)
                    break;
                case 'biweekly':
                    start = new Date(today);
                    start.setDate(today.getDate() - 14);
                    end = new Date(today);
                    break;
                case 'monthly':
                    start = new Date(today.getFullYear(), today.getMonth(), 1);
                    end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                    break;
                case 'annual':
                    start = new Date(today.getFullYear(), 0, 1);
                    end = new Date(today.getFullYear(), 11, 31);
                    break;
                default:
                    start = new Date(today);
                    end = new Date(today);
            }
            
            startDate = start.toISOString().split('T')[0];
            endDate = end.toISOString().split('T')[0];
        }
        
        // For daily reports, NEVER use manually set dates - always use today
        // This ensures the date is always current
        if (periodFilter === 'daily') {
            const todayString = getTodayLocalDateString();
            startDate = todayString;
            endDate = todayString;
        }
        
        // Build query parameters
        const params = new URLSearchParams();
        params.append('period', periodFilter);
        if (startDate) params.append('start_date', startDate);
        if (endDate) params.append('end_date', endDate);
        if (cashierFilter) params.append('user_id', cashierFilter);
        if (statusFilter) params.append('status', statusFilter);
        
        // Show loading state
        const tbody = document.getElementById('shiftReportsBody');
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="text-center text-muted py-4">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    Loading shift reports...
                </td>
            </tr>
        `;
        
        const url = '/admin/shifts/reports?' + params.toString();
        console.log('Loading shift reports with filters:', {
            period: periodFilter,
            startDate: startDate,
            endDate: endDate,
            cashier: cashierFilter,
            status: statusFilter
        });
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            displayShiftReports(data.shifts);
        } else {
            showNotification(data.message || 'Failed to load shift reports', 'error');
            tbody.innerHTML = `
                <tr>
                    <td colspan="10" class="text-center text-danger py-4">
                        <i class="bi bi-exclamation-triangle fs-1 d-block mb-2"></i>
                        ${data.message || 'Error loading shift reports'}
                    </td>
                </tr>
            `;
        }
    } catch (error) {
        console.error('Error loading shift reports:', error);
        const tbody = document.getElementById('shiftReportsBody');
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="text-center text-danger py-4">
                    <i class="bi bi-exclamation-triangle fs-1 d-block mb-2"></i>
                    Error loading shift reports: ${error.message}
                </td>
            </tr>
        `;
        showNotification('Error loading shift reports', 'error');
    }
}

function displayShiftReports(shifts) {
    const tbody = document.getElementById('shiftReportsBody');
    
    if (!shifts || shifts.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    No shift reports found
                </td>
            </tr>
        `;
        return;
    }
    
    // Remove duplicates by shift ID (frontend safety check)
    const uniqueShifts = [];
    const seenIds = new Set();
    for (const shift of shifts) {
        if (shift.id && !seenIds.has(shift.id)) {
            seenIds.add(shift.id);
            uniqueShifts.push(shift);
        }
    }
    
    tbody.innerHTML = uniqueShifts.map(shift => {
        // Use server-formatted dates for 100% accuracy (no timezone conversion)
        // Always use start_time_full from server - it's already converted to Asia/Manila timezone
        const startTimeDisplay = shift.start_time_full || '-';
        const endTimeDisplay = shift.end_time_full || '-';
        
        // Use server-calculated duration for accuracy (no timezone conversion issues)
        const hours = shift.duration_hours || 0;
        const minutes = shift.duration_minutes || 0;
        
        const statusBadge = shift.status === 'active' 
            ? '<span class="badge bg-success">Active</span>'
            : '<span class="badge bg-secondary">Closed</span>';
        
        const cashDiff = shift.cash_difference || 0;
        const cashDiffClass = cashDiff === 0 ? 'text-success' : (cashDiff > 0 ? 'text-info' : 'text-warning');
        
        // Get cashier name - avoid duplicate display
        // Use cashier_name if available, otherwise use user.name
        // Only show duplicate if they're different
        const cashierName = shift.cashier_name || (shift.user && shift.user.name ? shift.user.name : 'N/A');
        const userName = shift.user && shift.user.name ? shift.user.name : null;
        const showDuplicateName = cashierName && userName && cashierName !== userName;
        
        return `
            <tr>
                <td>#${shift.id}</td>
                <td>
                    <div>${cashierName}</div>
                    ${showDuplicateName ? `<small class="text-muted">(${userName})</small>` : ''}
                </td>
                <td>${startTimeDisplay}</td>
                <td>${endTimeDisplay}</td>
                <td>${hours}h ${minutes}m</td>
                <td>₱${parseFloat(shift.total_sales || 0).toFixed(2)}</td>
                <td>${shift.transaction_count || 0}</td>
                <td class="${cashDiffClass}">₱${Math.abs(cashDiff).toFixed(2)}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="viewShiftDetails(${shift.id})">
                        <i class="bi bi-eye"></i> View
                    </button>
                    ${shift.status === 'closed' ? `
                        <button class="btn btn-sm btn-success" onclick="printShiftReportAdmin(${shift.id})">
                            <i class="bi bi-printer"></i>
                        </button>
                    ` : ''}
                </td>
            </tr>
        `;
    }).join('');
}

async function viewShiftDetails(shiftId) {
    try {
        const response = await fetch(`/shifts/${shiftId}/report`);
        const data = await response.json();
        
        if (data.success) {
            showShiftDetailsModal(data.shift, data.sales);
        } else {
            showNotification('Failed to load shift details', 'error');
        }
    } catch (error) {
        console.error('Error loading shift details:', error);
        showNotification('Error loading shift details', 'error');
    }
}

function showShiftDetailsModal(shift, sales) {
    // Use server-formatted times - already converted to Asia/Manila timezone
    const startTimeDisplay = shift.start_time_full || (shift.start_time ? new Date(shift.start_time).toLocaleString() : '-');
    const endTimeDisplay = shift.end_time_full || (shift.end_time ? new Date(shift.end_time).toLocaleString() : 'Active');
    
    // Use server-calculated duration if available, otherwise calculate from timestamps
    let hours = 0;
    let minutes = 0;
    if (shift.duration_hours !== undefined && shift.duration_minutes !== undefined) {
        hours = shift.duration_hours;
        minutes = shift.duration_minutes;
    } else {
        // Fallback calculation (may have timezone issues)
        const startTime = shift.start_time ? new Date(shift.start_time) : null;
        const endTime = shift.end_time ? new Date(shift.end_time) : null;
        const duration = endTime && startTime ? Math.floor((endTime - startTime) / 1000 / 60) : (startTime ? Math.floor((new Date() - startTime) / 1000 / 60) : 0);
        hours = Math.floor(duration / 60);
        minutes = duration % 60;
    }
    
    const paymentMethods = shift.payment_methods || { cash: 0, card: 0, gcash: 0, maya: 0 };
    
    const modalHTML = `
        <div class="modal fade" id="shiftDetailsModal" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="bi bi-file-text me-2"></i>Shift #${shift.id} Details</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">Shift Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm table-borderless">
                                            <tr>
                                                <td><strong>Cashier:</strong></td>
                                                <td>
                                                    ${shift.cashier_name || shift.user.name}
                                                    ${shift.cashier_name && shift.cashier_name !== shift.user.name ? `<br><small class="text-muted">(User: ${shift.user.name})</small>` : ''}
                                                </td>
                                            </tr>
                                            <tr><td><strong>Start:</strong></td><td>${startTimeDisplay}</td></tr>
                                            <tr><td><strong>End:</strong></td><td>${endTimeDisplay}</td></tr>
                                            <tr><td><strong>Duration:</strong></td><td>${hours}h ${minutes}m</td></tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">Cash Summary</h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm table-borderless">
                                            <tr><td><strong>Opening:</strong></td><td class="text-end">₱${parseFloat(shift.opening_cash).toFixed(2)}</td></tr>
                                            <tr><td><strong>Closing:</strong></td><td class="text-end">₱${shift.closing_cash ? parseFloat(shift.closing_cash).toFixed(2) : 'N/A'}</td></tr>
                                            <tr><td><strong>Expected:</strong></td><td class="text-end">₱${parseFloat(shift.expected_cash || 0).toFixed(2)}</td></tr>
                                            <tr><td><strong>Difference:</strong></td><td class="text-end">${shift.cash_difference ? '₱' + Math.abs(shift.cash_difference).toFixed(2) : 'N/A'}</td></tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0">Sales Summary</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-md-3">
                                                <h3 class="text-primary">₱${parseFloat(shift.total_sales || 0).toFixed(2)}</h3>
                                                <p class="text-muted">Total Sales</p>
                                            </div>
                                            <div class="col-md-3">
                                                <h3 class="text-success">${shift.transaction_count || 0}</h3>
                                                <p class="text-muted">Transactions</p>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Payment Methods</h6>
                                                ${Object.entries(paymentMethods).map(([method, amount]) => 
                                                    `<span class="badge bg-secondary me-2">${method.toUpperCase()}: ₱${parseFloat(amount).toFixed(2)}</span>`
                                                ).join('')}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        ${sales && sales.length > 0 ? `
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <h6 class="mb-0">Transactions</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Receipt</th>
                                                <th>Time</th>
                                                <th>Payment</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${sales.map(sale => `
                                                <tr>
                                                    <td>${sale.receipt_number}</td>
                                                    <td>${new Date(sale.created_at).toLocaleTimeString()}</td>
                                                    <td><span class="badge bg-info">${sale.payment_method.toUpperCase()}</span></td>
                                                    <td class="text-end">₱${parseFloat(sale.amount).toFixed(2)}</td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        ` : '<p class="text-center text-muted">No transactions recorded</p>'}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="printShiftReportAdmin(${shift.id})">
                            <i class="bi bi-printer me-1"></i>Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('shiftDetailsModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add new modal
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('shiftDetailsModal'));
    modal.show();
}

function refreshShiftReports() {
    // Update dates to today before refreshing
    const todayString = getTodayLocalDateString();
    
    // Update shift date filter
    const shiftDateFilter = document.getElementById('shiftDateFilter');
    if (shiftDateFilter) {
        shiftDateFilter.value = todayString;
    }
    
    // Update sales date filters
    const salesStartDate = document.getElementById('salesStartDate');
    const salesEndDate = document.getElementById('salesEndDate');
    if (salesStartDate) {
        salesStartDate.value = todayString;
    }
    if (salesEndDate) {
        salesEndDate.value = todayString;
    }
    
    // Update period display
    const periodFilter = document.getElementById('reportPeriodFilter');
    if (periodFilter) {
        handlePeriodChange();
    }
    
    showNotification('Refreshing shift reports...', 'info');
    loadShiftReports();
}

function applyShiftFilters() {
    // Ensure dates are current before applying filters
    const periodFilter = document.getElementById('reportPeriodFilter')?.value || 'daily';
    if (periodFilter === 'daily') {
        const todayString = getTodayLocalDateString();
        const shiftDateFilter = document.getElementById('shiftDateFilter');
        if (shiftDateFilter && !shiftDateFilter.value) {
            shiftDateFilter.value = todayString;
        }
    }
    loadShiftReports();
    showNotification('Filters applied', 'success');
}

// Sales Report Functions
async function loadSalesReport() {
    try {
        // Get dates from inputs (allow user to select different dates)
        const salesStartDateEl = document.getElementById('salesStartDate');
        const salesEndDateEl = document.getElementById('salesEndDate');
        
        // If dates are not set, default to today
        let startDate = salesStartDateEl?.value || getTodayLocalDateString();
        let endDate = salesEndDateEl?.value || getTodayLocalDateString();
        
        // If dates are empty, set to today
        if (!startDate) {
            startDate = getTodayLocalDateString();
            if (salesStartDateEl) {
                salesStartDateEl.value = startDate;
            }
        }
        if (!endDate) {
            endDate = getTodayLocalDateString();
            if (salesEndDateEl) {
                salesEndDateEl.value = endDate;
            }
        }
        
        const cashierId = document.getElementById('salesCashierFilter')?.value || '';
        
        const params = new URLSearchParams({
            start_date: startDate,
            end_date: endDate
        });
        
        if (cashierId) {
            params.append('cashier_id', cashierId);
        }
        
        console.log('Loading sales report with dates:', startDate, 'to', endDate);
        
        const tbody = document.getElementById('salesReportBody');
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="text-center text-muted py-4">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    Loading sales report...
                </td>
            </tr>
        `;
        
        const response = await fetch(`/api/all-sales?${params.toString()}`);
        const data = await response.json();
        
        console.log('Sales report response:', data);
        
        if (data.success) {
            displaySalesReport(data.sales, data.summary);
        } else {
            showNotification(data.message || 'Failed to load sales report', 'error');
            tbody.innerHTML = `
                <tr>
                    <td colspan="10" class="text-center text-danger py-4">
                        <i class="bi bi-exclamation-triangle fs-1 d-block mb-2"></i>
                        ${data.message || 'Error loading sales report'}
                    </td>
                </tr>
            `;
        }
    } catch (error) {
        console.error('Error loading sales report:', error);
        const tbody = document.getElementById('salesReportBody');
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="text-center text-danger py-4">
                    <i class="bi bi-exclamation-triangle fs-1 d-block mb-2"></i>
                    Error loading sales report: ${error.message}
                </td>
            </tr>
        `;
    }
}

function displaySalesReport(sales, summary) {
    const tbody = document.getElementById('salesReportBody');
    const footer = document.getElementById('salesReportFooter');
    const summarySection = document.getElementById('salesReportSummary');
    
    if (!sales || sales.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    No sales found for the selected date range
                </td>
            </tr>
        `;
        footer.style.display = 'none';
        summarySection.style.display = 'none';
        return;
    }
    
    // Display summary
    if (summary) {
        document.getElementById('totalSalesAmount').textContent = `₱${parseFloat(summary.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('totalTransactionsCount').textContent = summary.total_transactions.toLocaleString();
        document.getElementById('averageTransactionAmount').textContent = `₱${parseFloat(summary.average_transaction).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('totalItemsSold').textContent = Math.round(summary.total_items_sold).toLocaleString();
        summarySection.style.display = 'block';
    }
    
    // Display sales - use server-formatted dates for 100% accuracy (no timezone conversion)
    // Split each sale into separate rows for each item
    const rows = [];
    
    sales.forEach(sale => {
        // Use server-formatted date/time to avoid timezone conversion issues
        const formattedDate = sale.date_formatted || (sale.date_time ? new Date(sale.date_time).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : '-');
        const formattedTime = sale.time_formatted || (sale.date_time ? new Date(sale.date_time).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : '-');
        
        // Payment method badge
        const paymentMethod = sale.payment_method || 'cash';
        const paymentBadge = {
            'cash': '<span class="badge bg-success">Cash</span>',
            'card': '<span class="badge bg-primary">Card</span>',
            'gcash': '<span class="badge bg-info">GCash</span>',
            'maya': '<span class="badge bg-warning text-dark">Maya</span>'
        }[paymentMethod] || `<span class="badge bg-secondary">${paymentMethod}</span>`;
        
        // Status badge
        const statusBadge = sale.status === 'completed' 
            ? '<span class="badge bg-success">Completed</span>'
            : `<span class="badge bg-secondary">${sale.status}</span>`;
        
        // Get voided items array from sale
        const voidedItems = sale.voided_items || [];
        const voidedItemIndices = Array.isArray(voidedItems) ? voidedItems.map(idx => parseInt(idx)) : [];
        
        // Calculate totals for proportional distribution
        const saleSubtotal = parseFloat(sale.subtotal || 0);
        const saleDiscount = parseFloat(sale.discount || 0);
        const saleTax = parseFloat(sale.tax || 0);
        const saleTotal = parseFloat(sale.total_amount || 0);
        
        // Get all items for this sale
        const items = sale.items || [];
        
        if (items.length === 0) {
            // If no items, show one row with empty item
            // For empty items, show void status if entire transaction is voided
            const voidIndicator = sale.void_status === 'approved' 
                ? '<span class="badge bg-danger ms-1">Voided</span>'
                : (sale.void_status === 'pending' ? '<span class="badge bg-warning ms-1">Void Pending</span>' : '');
            
            rows.push(`
                <tr>
                    <td><strong>${sale.receipt_number}</strong></td>
                    <td>
                        <div>${formattedDate}</div>
                        <small class="text-muted">${formattedTime}</small>
                    </td>
                    <td>${sale.cashier_name}</td>
                    <td><small>No items</small></td>
                    <td class="text-end">0</td>
                    <td class="text-end">₱0.00</td>
                    <td class="text-end">₱0.00</td>
                    <td class="text-end"><strong>₱0.00</strong></td>
                    <td>${paymentBadge}</td>
                    <td>${statusBadge}${voidIndicator}</td>
                </tr>
            `);
        } else {
            // Create a separate row for each item
            items.forEach((item, itemIndex) => {
                const itemQty = parseFloat(item.quantity || 0);
                const itemPrice = parseFloat(item.price || 0);
                const itemSubtotal = itemQty * itemPrice;
                
                // Calculate proportional discount and tax for this item
                let itemDiscount = 0;
                let itemTax = 0;
                let itemTotal = itemSubtotal;
                
                if (saleSubtotal > 0) {
                    // Proportional discount
                    itemDiscount = (itemSubtotal / saleSubtotal) * saleDiscount;
                    // Proportional tax (if tax is applied)
                    itemTax = (itemSubtotal / saleSubtotal) * saleTax;
                    // Item total = subtotal - discount + tax
                    itemTotal = itemSubtotal - itemDiscount + itemTax;
                }
                
                // Determine void status for THIS SPECIFIC ITEM
                // Only show void status if this specific item is voided
                let itemVoidIndicator = '';
                const itemIndexValue = item.item_index !== undefined ? parseInt(item.item_index) : itemIndex;
                
                if (sale.void_status === 'approved') {
                    // Check if this specific item is in the voided_items array
                    if (voidedItemIndices.includes(itemIndexValue)) {
                        // This item is voided
                        itemVoidIndicator = '<span class="badge bg-danger ms-1">Voided</span>';
                    }
                    // If item is not voided, don't show any void status
                } else if (sale.void_status === 'pending') {
                    // For pending, show status for all items (transaction-level status)
                    itemVoidIndicator = '<span class="badge bg-warning ms-1">Void Pending</span>';
                } else if (sale.void_status === 'rejected') {
                    // For rejected, show status for all items (transaction-level status)
                    itemVoidIndicator = '<span class="badge bg-secondary ms-1">Void Rejected</span>';
                }
                
                // Show all information on each row for clarity
                rows.push(`
                    <tr>
                        <td><strong>${sale.receipt_number}</strong></td>
                        <td>
                            <div>${formattedDate}</div>
                            <small class="text-muted">${formattedTime}</small>
                        </td>
                        <td>${sale.cashier_name}</td>
                        <td><small>${item.name || 'Unknown Product'}</small></td>
                        <td class="text-end">${Math.round(itemQty).toLocaleString()}</td>
                        <td class="text-end">₱${itemPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        <td class="text-end">₱${itemDiscount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        <td class="text-end"><strong>₱${itemSubtotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></td>
                        <td>${paymentBadge}</td>
                        <td>${statusBadge}${itemVoidIndicator}</td>
                    </tr>
                `);
            });
        }
    });
    
    tbody.innerHTML = rows.join('');
    
    // Display footer totals
    if (summary) {
        const formatCurrency = (value) => {
            const num = parseFloat(value) || 0;
            return `₱${num.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        };
        
        document.getElementById('footerTotalAmount').textContent = formatCurrency(summary.total_amount);
        document.getElementById('footerDiscount').textContent = formatCurrency(summary.total_discount);
        footer.style.display = 'table-row-group';
    }
}

function applySalesFilters() {
    loadSalesReport();
}

function refreshSalesReport() {
    // Update dates to today before refreshing
    const todayString = getTodayLocalDateString();
    
    // Update sales date filters to today
    const salesStartDate = document.getElementById('salesStartDate');
    const salesEndDate = document.getElementById('salesEndDate');
    if (salesStartDate) {
        salesStartDate.value = todayString;
    }
    if (salesEndDate) {
        salesEndDate.value = todayString;
    }
    
    // Load sales report with updated dates
    loadSalesReport();
}

function downloadSalesReport() {
    const startDate = document.getElementById('salesStartDate')?.value || getTodayLocalDateString();
    const endDate = document.getElementById('salesEndDate')?.value || getTodayLocalDateString();
    const cashierId = document.getElementById('salesCashierFilter')?.value || '';
    
    const params = new URLSearchParams({
        start_date: startDate,
        end_date: endDate,
        format: 'csv'
    });
    
    if (cashierId) {
        params.append('cashier_id', cashierId);
    }
    
    window.open(`/api/all-sales?${params.toString()}`, '_blank');
}

function printSalesReport() {
    const printWindow = window.open('', '_blank');
    const startDate = document.getElementById('salesStartDate')?.value || getTodayLocalDateString();
    const endDate = document.getElementById('salesEndDate')?.value || getTodayLocalDateString();
    const cashierFilter = document.getElementById('salesCashierFilter');
    const cashierName = cashierFilter.options[cashierFilter.selectedIndex]?.text || 'All Cashiers';
    
    const table = document.getElementById('salesReportTable');
    const summary = document.getElementById('salesReportSummary');
    
    printWindow.document.write(`
        <html>
            <head>
                <title>Sales Report - ${startDate} to ${endDate}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    h1 { color: #333; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; font-weight: bold; }
                    .text-end { text-align: right; }
                    .summary { margin-bottom: 20px; }
                    .summary-card { display: inline-block; margin-right: 20px; padding: 10px; border: 1px solid #ddd; }
                </style>
            </head>
            <body>
                <h1>Sales Report</h1>
                <p><strong>Date Range:</strong> ${startDate} to ${endDate}</p>
                <p><strong>Cashier:</strong> ${cashierName}</p>
                ${summary ? summary.innerHTML : ''}
                ${table.outerHTML}
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

function printShiftReportAdmin(shiftId) {
    window.open(`/shifts/${shiftId}/print`, '_blank');
}

// Void Transaction Functions
// Helper function to format date only (no time)
function formatDateOnly(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '-';
    
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    
    return `${month}/${day}/${year}`;
}

async function loadPendingVoidRequests() {
    try {
        const response = await fetch('/api/void-requests/pending');
        const data = await response.json();
        
        const tbody = document.getElementById('voidRequestsTableBody');
        const emptyDiv = document.getElementById('voidRequestsEmpty');
        const badge = document.getElementById('voidRequestsBadge');
        
        const pendingCount = data.success && data.pending_requests ? data.pending_requests.length : 0;
        
        if (pendingCount > 0) {
            tbody.innerHTML = data.pending_requests.map(request => `
                <tr>
                    <td><strong>${request.receipt_number || 'N/A'}</strong></td>
                    <td><small>${request.items || 'No items'}</small></td>
                    <td>₱${parseFloat(request.amount).toFixed(2)}</td>
                    <td>${request.requested_by}</td>
                    <td>${request.reason || '<em>No reason provided</em>'}</td>
                    <td>${formatDateOnly(request.sale_created_at)}</td>
                    <td>${formatDateOnly(request.created_at)}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-success" onclick="approveVoidRequest(${request.id})" title="Approve">
                                <i class="bi bi-check-circle"></i>
                            </button>
                            <button class="btn btn-danger" onclick="rejectVoidRequest(${request.id})" title="Reject">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
            tbody.style.display = '';
            emptyDiv.style.display = 'none';
        } else {
            tbody.innerHTML = '';
            tbody.style.display = 'none';
            emptyDiv.style.display = 'block';
        }
        
        // Update all void request badges (dropdown and tab)
        // Note: There are two badges with the same ID (one in dropdown, one in tab)
        // We'll update both using querySelectorAll
        const allVoidBadges = document.querySelectorAll('#voidRequestsBadge');
        allVoidBadges.forEach(badgeElement => {
            if (pendingCount > 0) {
                badgeElement.textContent = pendingCount;
                badgeElement.style.display = 'inline-block';
            } else {
                badgeElement.style.display = 'none';
            }
        });
        
        // Also update badge in dropdown using a more specific selector
        // Find the dropdown item that contains "Void Transactions" text
        const dropdownItems = document.querySelectorAll('.dropdown-item');
        dropdownItems.forEach(item => {
            if (item.textContent.includes('Void Transactions')) {
                const badge = item.querySelector('.badge');
                if (badge) {
                    if (pendingCount > 0) {
                        badge.textContent = pendingCount;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error loading void requests:', error);
        document.getElementById('voidRequestsTableBody').innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-danger py-4">
                    Error loading void requests. Please try again.
                </td>
            </tr>
        `;
    }
}

async function loadVoidTransactionHistory() {
    try {
        const response = await fetch('/api/void-requests/history');
        
        // Check if response is OK
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Non-JSON response:', text);
            throw new Error('Server returned non-JSON response. Check server logs for details.');
        }
        
        const data = await response.json();
        
        const tbody = document.getElementById('voidHistoryTableBody');
        const emptyDiv = document.getElementById('voidHistoryEmpty');
        
        if (data.success && data.void_requests && data.void_requests.length > 0) {
            tbody.innerHTML = data.void_requests.map(request => {
                let statusBadge = '';
                if (request.status === 'approved') {
                    statusBadge = '<span class="badge bg-success">Approved</span>';
                } else if (request.status === 'rejected') {
                    statusBadge = '<span class="badge bg-danger">Rejected</span>';
                } else if (request.status === 'pending') {
                    statusBadge = '<span class="badge bg-warning">Pending</span>';
                } else {
                    statusBadge = '<span class="badge bg-secondary">' + request.status + '</span>';
                }
                
                const processedDate = request.approved_at || request.rejected_at || null;
                const processedBy = request.approved_by || (request.rejection_reason ? 'N/A' : null);
                const reasonDisplay = request.rejection_reason ? 
                    `<span class="text-danger" title="Rejection reason: ${request.rejection_reason}">${request.reason || '<em>No reason</em>'}</span>` :
                    (request.reason || '<em>No reason provided</em>');
                
                return `
                    <tr>
                        <td><strong>${request.receipt_number || 'N/A'}</strong></td>
                        <td><small>${request.items || 'No items'}</small></td>
                        <td>₱${parseFloat(request.amount).toFixed(2)}</td>
                        <td>${statusBadge}</td>
                        <td>${request.requested_by}</td>
                        <td>${processedBy || '-'}</td>
                        <td>${reasonDisplay}</td>
                        <td>${formatDateOnly(request.sale_created_at)}</td>
                        <td>${formatDateOnly(request.created_at)}</td>
                        <td>${formatDateOnly(processedDate)}</td>
                    </tr>
                `;
            }).join('');
            tbody.style.display = '';
            emptyDiv.style.display = 'none';
        } else {
            tbody.innerHTML = '';
            tbody.style.display = 'none';
            emptyDiv.style.display = 'block';
        }
    } catch (error) {
        console.error('Error loading void transaction history:', error);
        const tbody = document.getElementById('voidHistoryTableBody');
        const emptyDiv = document.getElementById('voidHistoryEmpty');
        
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="10" class="text-center text-danger py-4">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Error loading void transaction history: ${error.message}
                        <br><small>Please check the console for more details or contact support.</small>
                    </td>
                </tr>
            `;
        }
        
        if (emptyDiv) {
            emptyDiv.style.display = 'none';
        }
    }
}

async function approveVoidRequest(requestId) {
    if (!confirm('Approve this void request? Selected items will be voided, totals updated, and stock restored for those lines. The rest of the sale stays valid unless every line is voided.')) {
        return;
    }
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        const response = await fetch(`/api/void-requests/${requestId}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken.content,
            },
        });
        
        const data = await response.json();
        
            if (response.ok && data.success) {
                alert('Void request approved successfully');
                loadPendingVoidRequests();
                loadVoidTransactionHistory();
            } else {
                alert(data.message || 'Failed to approve void request');
            }
    } catch (error) {
        console.error('Error approving void request:', error);
        alert('Error approving void request. Please try again.');
    }
}

async function rejectVoidRequest(requestId) {
    const reason = prompt('Please provide a reason for rejection (optional):');
    if (reason === null) {
        // User cancelled
        return;
    }
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        const response = await fetch(`/api/void-requests/${requestId}/reject`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken.content,
            },
            body: JSON.stringify({
                rejection_reason: reason || ''
            }),
        });
        
        const data = await response.json();
        
            if (response.ok && data.success) {
                alert('Void request rejected');
                loadPendingVoidRequests();
                loadVoidTransactionHistory();
            } else {
                alert(data.message || 'Failed to reject void request');
            }
    } catch (error) {
        console.error('Error rejecting void request:', error);
        alert('Error rejecting void request. Please try again.');
    }
}

// Load void requests and history when Void Transactions tab is shown
document.getElementById('void-transactions-tab').addEventListener('shown.bs.tab', function () {
    loadPendingVoidRequests();
    loadVoidTransactionHistory();
});


// Auto-refresh void requests and history every 30 seconds
setInterval(() => {
    const voidTab = document.getElementById('void-transactions-tab');
    if (voidTab && voidTab.classList.contains('active')) {
        loadPendingVoidRequests();
        loadVoidTransactionHistory();
    }
}, 30000);

// Discrepancy Management Functions
async function loadPendingDiscrepancyRequests() {
    try {
        const response = await fetch('/api/discrepancies/pending');
        const data = await response.json();
        
        const tbody = document.getElementById('discrepancyRequestsTableBody');
        const emptyDiv = document.getElementById('discrepancyRequestsEmpty');
        const badge = document.getElementById('discrepancyRequestsBadge');
        
        if (data.success && data.requests && data.requests.length > 0) {
            tbody.innerHTML = data.requests.map(request => `
                <tr>
                    <td><strong>${request.item_name || 'N/A'}</strong></td>
                    <td>${request.quantity}</td>
                    <td><small>${request.description || 'No description'}</small></td>
                    <td>${request.reason || '<em>No reason provided</em>'}</td>
                    <td>${request.requested_by}</td>
                    <td>${formatDateOnly(request.requested_at)}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-success" onclick="approveDiscrepancy(${request.id})" title="Approve">
                                <i class="bi bi-check-circle"></i>
                            </button>
                            <button class="btn btn-danger" onclick="rejectDiscrepancy(${request.id})" title="Reject">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
            
            emptyDiv.style.display = 'none';
            if (badge) {
                badge.textContent = data.requests.length;
                badge.style.display = 'inline-block';
            }
            // Also update badge in dropdown
            const dropdownDiscrepancyBadge = document.querySelector('#approvalsDropdown + ul .dropdown-item:nth-child(2) .badge');
            if (dropdownDiscrepancyBadge) {
                dropdownDiscrepancyBadge.textContent = data.requests.length;
                dropdownDiscrepancyBadge.style.display = 'inline-block';
            }
        } else {
            tbody.innerHTML = '';
            emptyDiv.style.display = 'block';
            if (badge) {
                badge.style.display = 'none';
            }
            // Hide badge in dropdown
            const dropdownDiscrepancyBadge = document.querySelector('#approvalsDropdown + ul .dropdown-item:nth-child(2) .badge');
            if (dropdownDiscrepancyBadge) {
                dropdownDiscrepancyBadge.style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error loading pending discrepancy requests:', error);
        const tbody = document.getElementById('discrepancyRequestsTableBody');
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">Error loading requests. Please try again.</td></tr>';
    }
}

async function loadDiscrepancyHistory() {
    try {
        const response = await fetch('/api/discrepancies/history');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Non-JSON response:', text);
            throw new Error('Server returned non-JSON response.');
        }
        
        const data = await response.json();
        
        const tbody = document.getElementById('discrepancyHistoryTableBody');
        const emptyDiv = document.getElementById('discrepancyHistoryEmpty');
        
        if (data.success && data.history && data.history.length > 0) {
            tbody.innerHTML = data.history.map(request => {
                let statusBadge = '';
                if (request.status === 'approved') {
                    statusBadge = '<span class="badge bg-success">Approved</span>';
                } else if (request.status === 'rejected') {
                    statusBadge = '<span class="badge bg-danger">Rejected</span>';
                } else if (request.status === 'pending') {
                    statusBadge = '<span class="badge bg-warning">Pending</span>';
                }
                
                return `
                    <tr>
                        <td><strong>${request.item_name || 'N/A'}</strong></td>
                        <td>${request.quantity}</td>
                        <td><small>${request.description || 'No description'}</small></td>
                        <td>${request.reason || '<em>No reason</em>'}</td>
                        <td>${statusBadge}</td>
                        <td>${request.requested_by}</td>
                        <td>${request.approved_by || '<em>N/A</em>'}</td>
                        <td>${formatDateOnly(request.requested_at)}</td>
                        <td>${request.approved_at ? formatDateOnly(request.approved_at) : (request.rejected_at ? formatDateOnly(request.rejected_at) : '<em>N/A</em>')}</td>
                    </tr>
                `;
            }).join('');
            
            emptyDiv.style.display = 'none';
        } else {
            tbody.innerHTML = '';
            emptyDiv.style.display = 'block';
        }
    } catch (error) {
        console.error('Error loading discrepancy history:', error);
        const tbody = document.getElementById('discrepancyHistoryTableBody');
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-danger py-4">Error loading history. Please try again.</td></tr>';
    }
}

async function approveDiscrepancy(discrepancyId) {
    if (!confirm('Are you sure you want to approve this discrepancy request? This will deduct the quantity from inventory.')) {
        return;
    }
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        const response = await fetch(`/discrepancies/${discrepancyId}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken.content,
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message || 'Discrepancy request approved successfully');
            loadPendingDiscrepancyRequests();
            loadDiscrepancyHistory();
        } else {
            alert(data.message || 'Failed to approve discrepancy request');
        }
    } catch (error) {
        console.error('Error approving discrepancy:', error);
        alert('Error approving discrepancy request. Please try again.');
    }
}

async function rejectDiscrepancy(discrepancyId) {
    const reason = prompt('Please provide a reason for rejection (optional):');
    if (reason === null) {
        // User cancelled
        return;
    }
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        const response = await fetch(`/discrepancies/${discrepancyId}/reject`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken.content,
            },
            body: JSON.stringify({
                rejection_reason: reason || ''
            }),
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message || 'Discrepancy request rejected successfully');
            loadPendingDiscrepancyRequests();
            loadDiscrepancyHistory();
        } else {
            alert(data.message || 'Failed to reject discrepancy request');
        }
    } catch (error) {
        console.error('Error rejecting discrepancy:', error);
        alert('Error rejecting discrepancy request. Please try again.');
    }
}

// Load discrepancy requests and history when Discrepancies tab is shown
const discrepanciesTab = document.getElementById('discrepancies-tab');
if (discrepanciesTab) {
    discrepanciesTab.addEventListener('shown.bs.tab', function () {
        loadPendingDiscrepancyRequests();
        loadDiscrepancyHistory();
    });
}

// Auto-refresh discrepancy requests and history every 30 seconds
setInterval(() => {
    const discrepanciesTab = document.getElementById('discrepancies-tab');
    if (discrepanciesTab && discrepanciesTab.classList.contains('active')) {
        loadPendingDiscrepancyRequests();
        loadDiscrepancyHistory();
    }
}, 30000);

// Load free sample requests when Free Sample Requests tab is shown
const freeSamplesTab = document.getElementById('free-samples-tab');
if (freeSamplesTab) {
    freeSamplesTab.addEventListener('shown.bs.tab', function () {
        loadFreeSampleRequests();
    });
}

// Load wholesale/retail requests when tab is shown

// Auto-refresh free sample requests every 30 seconds
setInterval(() => {
    const freeSamplesTab = document.getElementById('free-samples-tab');
    if (freeSamplesTab && freeSamplesTab.classList.contains('active')) {
        loadFreeSampleRequests();
    }
}, 30000);

// Customer Management Functions
let editingCustomerId = null;

async function loadCustomers() {
    try {
        const searchQuery = document.getElementById('customerListSearch')?.value || '';
        const url = searchQuery 
            ? `/api/customers/search?q=${encodeURIComponent(searchQuery)}`
            : '/api/customers';
        
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
            }
        });
        
        let customers = [];
        if (response.ok) {
            const data = await response.json();
            if (data.success && data.customers) {
                customers = data.customers;
            }
        }
        
        const tbody = document.getElementById('customersTableBody');
        const emptyDiv = document.getElementById('customersEmpty');
        
        if (customers.length > 0) {
            tbody.innerHTML = customers.map(customer => `
                <tr>
                    <td><strong>${customer.registered_name || 'N/A'}</strong></td>
                    <td>${customer.tin || '-'}</td>
                    <td class="text-truncate" style="max-width: 300px;" title="${customer.business_address || ''}">${customer.business_address || '-'}</td>
                    <td>
                        ${customer.is_active !== false && customer.is_active !== 0
                            ? '<span class="badge bg-success">Active</span>' 
                            : '<span class="badge bg-secondary">Inactive</span>'}
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-primary" onclick="editCustomer(${customer.id})" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-danger" onclick="deleteCustomer(${customer.id})" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
            
            emptyDiv.style.display = 'none';
        } else {
            tbody.innerHTML = '';
            emptyDiv.style.display = 'block';
        }
    } catch (error) {
        console.error('Error loading customers:', error);
        const tbody = document.getElementById('customersTableBody');
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">Error loading customers. Please try again.</td></tr>';
    }
}

async function editCustomer(customerId) {
    try {
        const response = await fetch(`/api/customers/${customerId}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success && data.customer) {
            const customer = data.customer;
            editingCustomerId = customerId;
            
            // Populate form fields
            document.getElementById('registered_name').value = customer.registered_name || '';
            document.getElementById('tin').value = customer.tin || '';
            document.getElementById('business_address').value = customer.business_address || '';
            
            // Handle is_active checkbox
            const isActiveCheckbox = document.getElementById('is_active');
            if (isActiveCheckbox) {
                isActiveCheckbox.checked = customer.is_active !== false && customer.is_active !== 0;
            }
            
            // Update form title and button
            const formTitle = document.querySelector('#customerForm').closest('.card').querySelector('.card-header h5');
            if (formTitle) {
                formTitle.innerHTML = '<i class="bi bi-pencil me-2"></i>Edit Customer';
            }
            
            const submitBtn = document.querySelector('#customerForm button[type="submit"]');
            submitBtn.innerHTML = '<i class="bi bi-save me-1"></i>Update Customer';
            submitBtn.classList.remove('btn-primary');
            submitBtn.classList.add('btn-success');
            
            // Show notification
            if (typeof showNotification === 'function') {
                showNotification('Customer loaded for editing', 'info');
            }
            
            // Scroll to form
            document.getElementById('customerForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            throw new Error(data.message || 'Failed to load customer');
        }
    } catch (error) {
        console.error('Error loading customer:', error);
        alert('Error loading customer information: ' + error.message);
    }
}

async function deleteCustomer(customerId) {
    if (!confirm('Are you sure you want to delete this customer?')) {
        return;
    }
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        const response = await fetch(`/admin/customers/${customerId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken.content,
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Customer deleted successfully');
            loadCustomers();
        } else {
            alert(data.message || 'Failed to delete customer');
        }
    } catch (error) {
        console.error('Error deleting customer:', error);
        alert('Error deleting customer. Please try again.');
    }
}

function resetCustomerForm() {
    document.getElementById('customerForm').reset();
    editingCustomerId = null;
    
    // Reset form title
    const formTitle = document.querySelector('#customerForm').closest('.card').querySelector('.card-header h5');
    if (formTitle) {
        formTitle.innerHTML = '<i class="bi bi-person-plus me-2"></i>Add New Customer';
    }
    
    // Reset button
    const submitBtn = document.querySelector('#customerForm button[type="submit"]');
    submitBtn.innerHTML = '<i class="bi bi-save me-1"></i>Save Customer';
    submitBtn.classList.remove('btn-success');
    submitBtn.classList.add('btn-primary');
    
    // Reset is_active checkbox to checked
    const isActiveCheckbox = document.getElementById('is_active');
    if (isActiveCheckbox) {
        isActiveCheckbox.checked = true;
    }
}

function searchCustomers() {
    loadCustomers();
}

// Handle customer form submission
const customerForm = document.getElementById('customerForm');
if (customerForm) {
    customerForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
            const formData = new FormData(this);
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            formData.append('_token', csrfToken.content);
        }
        
        // Handle is_active checkbox - if unchecked, send 0, otherwise send 1
        const isActiveCheckbox = document.getElementById('is_active');
        if (isActiveCheckbox) {
            formData.set('is_active', isActiveCheckbox.checked ? '1' : '0');
        }
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        try {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + (editingCustomerId ? 'Updating...' : 'Saving...');
            
            const url = editingCustomerId 
                ? `/admin/customers/${editingCustomerId}`
                : '/admin/customers';
            
            // Laravel requires _method field for PUT requests
            if (editingCustomerId) {
                formData.append('_method', 'PUT');
            }
            
            const response = await fetch(url, {
                method: 'POST', // Always use POST, Laravel will use _method field
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.content,
                }
            });
            
            const data = await response.json();
            
            if (response.ok && data.success) {
                const message = editingCustomerId 
                    ? (data.message || 'Customer updated successfully')
                    : (data.message || 'Customer created successfully');
                
                // Use notification if available, otherwise alert
                if (typeof showNotification === 'function') {
                    showNotification('✓ ' + message, 'success');
                } else {
                    alert(message);
                }
                
                resetCustomerForm();
                loadCustomers();
            } else {
                let errorMessage = data.message || 'Failed to save customer';
                
                // Handle validation errors
                if (data.errors) {
                    const errorMessages = Object.values(data.errors).flat();
                    errorMessage = errorMessages.join(', ');
                }
                
                if (typeof showNotification === 'function') {
                    showNotification('Error: ' + errorMessage, 'error');
                } else {
                    alert(errorMessage);
                }
            }
        } catch (error) {
            console.error('Error saving customer:', error);
            const errorMessage = 'Error saving customer: ' + error.message;
            
            if (typeof showNotification === 'function') {
                showNotification(errorMessage, 'error');
            } else {
                alert(errorMessage);
            }
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
}

// Initialize auto-uppercase for customer form fields
function initializeCustomerFormAutoUppercase() {
    const customerForm = document.getElementById('customerForm');
    if (!customerForm) return;
    
    // Get all text inputs and textareas in the form
    const textInputs = customerForm.querySelectorAll('input[type="text"], textarea');
    
    textInputs.forEach(input => {
        // Convert to uppercase on input
        input.addEventListener('input', function() {
            const cursorPosition = this.selectionStart;
            this.value = this.value.toUpperCase();
            // Restore cursor position
            this.setSelectionRange(cursorPosition, cursorPosition);
        });
        
        // Also convert on paste
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const cursorPosition = this.selectionStart;
            const textBefore = this.value.substring(0, cursorPosition);
            const textAfter = this.value.substring(this.selectionEnd);
            this.value = (textBefore + pastedText.toUpperCase() + textAfter);
            // Set cursor position after pasted text
            const newPosition = cursorPosition + pastedText.length;
            this.setSelectionRange(newPosition, newPosition);
        });
    });
}

// Load customers when Customers tab is shown
const customersTab = document.getElementById('customers-tab');
if (customersTab) {
    customersTab.addEventListener('shown.bs.tab', function () {
        loadCustomers();
        // Initialize auto-uppercase when tab is shown
        initializeCustomerFormAutoUppercase();
    });
}

// Initialize auto-uppercase on page load if customers tab is already active
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(initializeCustomerFormAutoUppercase, 100);
    });
} else {
    setTimeout(initializeCustomerFormAutoUppercase, 100);
}

// Search on Enter key
const customerListSearch = document.getElementById('customerListSearch');
if (customerListSearch) {
    customerListSearch.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchCustomers();
        }
    });
}

// Helper function to get today's date in local timezone (YYYY-MM-DD format)
function getTodayLocalDateString() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Function to automatically update ALL dashboard dates to current date
function autoUpdateAllDashboardDates() {
    const todayString = getTodayLocalDateString();
    
    // 1. Update Sales Report dates - ALWAYS update to today
    const salesStartDate = document.getElementById('salesStartDate');
    const salesEndDate = document.getElementById('salesEndDate');
    if (salesStartDate) {
        // Force update to today - use both value and setAttribute to ensure it sticks
        salesStartDate.value = todayString;
        salesStartDate.setAttribute('value', todayString);
        console.log('Updated salesStartDate to today:', todayString, 'Verified:', salesStartDate.value);
    }
    if (salesEndDate) {
        // Force update to today - use both value and setAttribute to ensure it sticks
        salesEndDate.value = todayString;
        salesEndDate.setAttribute('value', todayString);
        console.log('Updated salesEndDate to today:', todayString, 'Verified:', salesEndDate.value);
    }
    
    // 2. Update Shift Report dates - ALWAYS update to today for daily reports
    const shiftDateFilter = document.getElementById('shiftDateFilter');
    const reportPeriodFilter = document.getElementById('reportPeriodFilter');
    if (shiftDateFilter) {
        const period = reportPeriodFilter ? reportPeriodFilter.value : 'daily';
        // Always update to today's date for daily reports, regardless of current value
        if (period === 'daily') {
            // Force update to today - use both value and setAttribute to ensure it sticks
            shiftDateFilter.value = todayString;
            shiftDateFilter.setAttribute('value', todayString);
            console.log('Updated shiftDateFilter to today:', todayString, 'Verified:', shiftDateFilter.value);
        }
    }
    
    // 3. Update Financial Summary dates based on current period
    const financialPeriodFilter = document.getElementById('financialPeriodFilter');
    const financialStartDate = document.getElementById('financialStartDate');
    const financialEndDate = document.getElementById('financialEndDate');
    if (financialPeriodFilter && financialStartDate) {
        const period = financialPeriodFilter.value;
        let startDate, endDate;
        const today = new Date(); // Need today for date calculations
        
        switch(period) {
            case 'daily':
                startDate = todayString;
                if (financialStartDate.value !== startDate) {
                    financialStartDate.value = startDate;
                }
                break;
            case 'weekly':
                startDate = new Date(today);
                startDate.setDate(today.getDate() - today.getDay());
                endDate = new Date(startDate);
                endDate.setDate(startDate.getDate() + 6);
                if (financialStartDate.value !== startDate.toISOString().split('T')[0]) {
                    financialStartDate.value = startDate.toISOString().split('T')[0];
                    if (financialEndDate) {
                        financialEndDate.value = endDate.toISOString().split('T')[0];
                    }
                }
                break;
            case 'monthly':
                startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                const monthStart = startDate.toISOString().split('T')[0];
                const monthEnd = endDate.toISOString().split('T')[0];
                if (financialStartDate.value !== monthStart) {
                    financialStartDate.value = monthStart;
                    if (financialEndDate) {
                        financialEndDate.value = monthEnd;
                    }
                }
                break;
            case 'annual':
                startDate = new Date(today.getFullYear(), 0, 1);
                endDate = new Date(today.getFullYear(), 11, 31);
                const yearStart = startDate.toISOString().split('T')[0];
                const yearEnd = endDate.toISOString().split('T')[0];
                if (financialStartDate.value !== yearStart) {
                    financialStartDate.value = yearStart;
                    if (financialEndDate) {
                        financialEndDate.value = yearEnd;
                    }
                }
                break;
        }
    }
    
    // Auto-reload reports if dates were updated and relevant tabs are active
    const reportsTab = document.getElementById('reports-tab');
    const overviewTab = document.getElementById('overview-tab');
    const monitoringTab = document.getElementById('monitoring-tab');
    
    // Reload sales report if on overview or reports tab
    if (salesStartDate && salesEndDate) {
        if ((reportsTab && reportsTab.classList.contains('active')) || 
            (overviewTab && overviewTab.classList.contains('active'))) {
            loadSalesReport();
        }
    }
    
    // Refresh Sales Analytics chart and Today's Sales if on overview tab
    if (overviewTab && overviewTab.classList.contains('active')) {
        refreshDashboard();
    }
    
    // Reload shift reports if on reports tab
    if (shiftDateFilter && reportsTab && reportsTab.classList.contains('active')) {
        handlePeriodChange(); // This will update dates and trigger reload
        loadShiftReports();
    }
    
    // Reload financial summary if on monitoring tab
    if (financialStartDate && monitoringTab && monitoringTab.classList.contains('active')) {
        handleFinancialPeriodChange(); // This will update dates
        applyFinancialFilters(); // Reload financial data
    }
}

// Alias for backward compatibility
function autoUpdateSalesReportDates() {
    autoUpdateAllDashboardDates();
}

// Auto-update ALL dashboard dates on page load
function initializeDashboardDates() {
    // Force update ALL date inputs to today immediately
    const todayString = getTodayLocalDateString();
    
    // Update shift date filter
    const shiftDateFilter = document.getElementById('shiftDateFilter');
    const reportPeriodFilter = document.getElementById('reportPeriodFilter');
    if (shiftDateFilter) {
        const period = reportPeriodFilter ? reportPeriodFilter.value : 'daily';
        if (period === 'daily') {
            shiftDateFilter.value = todayString;
            shiftDateFilter.setAttribute('value', todayString);
            console.log('Initialized shiftDateFilter to today:', todayString);
        }
    }
    
    // Update sales report dates
    const salesStartDate = document.getElementById('salesStartDate');
    const salesEndDate = document.getElementById('salesEndDate');
    if (salesStartDate) {
        salesStartDate.value = todayString;
        salesStartDate.setAttribute('value', todayString);
        console.log('Initialized salesStartDate to today:', todayString);
    }
    if (salesEndDate) {
        salesEndDate.value = todayString;
        salesEndDate.setAttribute('value', todayString);
        console.log('Initialized salesEndDate to today:', todayString);
    }
    
    // Then run full auto-update
    autoUpdateAllDashboardDates();
}

// IMMEDIATE date update - runs as soon as script loads (before DOM ready)
(function immediateDateUpdate() {
    const todayString = getTodayLocalDateString();
    
    // Try to update dates immediately if elements exist
    const updateDates = () => {
        const shiftDateFilter = document.getElementById('shiftDateFilter');
        const salesStartDate = document.getElementById('salesStartDate');
        const salesEndDate = document.getElementById('salesEndDate');
        const reportPeriodFilter = document.getElementById('reportPeriodFilter');
        
        if (shiftDateFilter) {
            const period = reportPeriodFilter ? reportPeriodFilter.value : 'daily';
            if (period === 'daily') {
                shiftDateFilter.value = todayString;
                shiftDateFilter.setAttribute('value', todayString);
            }
        }
        if (salesStartDate) {
            salesStartDate.value = todayString;
            salesStartDate.setAttribute('value', todayString);
        }
        if (salesEndDate) {
            salesEndDate.value = todayString;
            salesEndDate.setAttribute('value', todayString);
        }
    };
    
    // Try immediately
    updateDates();
    
    // Try again after short delays to catch elements as they load
    setTimeout(updateDates, 100);
    setTimeout(updateDates, 500);
    setTimeout(updateDates, 1000);
})();

// Run on DOM ready
(function() {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initializeDashboardDates();
        });
    } else {
        // DOM is already ready, run immediately
        initializeDashboardDates();
    }
})();

// Auto-update ALL dates when day changes (check every minute)
let lastCheckedDate = new Date().toDateString();
setInterval(function() {
    const currentDate = new Date().toDateString();
    if (currentDate !== lastCheckedDate) {
        lastCheckedDate = currentDate;
        autoUpdateAllDashboardDates();
        console.log('Date changed - automatically updated all dashboard dates');
        
        // Also refresh dashboard data (Today's Sales, Sales Analytics chart)
        refreshDashboard();
    }
    
        // Also check and update shift date filter if it's stale (for daily reports)
        const shiftDateFilter = document.getElementById('shiftDateFilter');
        const reportPeriodFilter = document.getElementById('reportPeriodFilter');
        if (shiftDateFilter && reportPeriodFilter) {
            const period = reportPeriodFilter.value;
            if (period === 'daily') {
                const todayString = getTodayLocalDateString();
            if (shiftDateFilter.value !== todayString) {
                shiftDateFilter.value = todayString;
                console.log('Auto-update: Reset shiftDateFilter to today:', todayString);
                // Reload reports with updated date
                loadShiftReports();
            }
        }
    }
    
    // Check and update sales report dates if they're stale
    const salesStartDate = document.getElementById('salesStartDate');
    const salesEndDate = document.getElementById('salesEndDate');
    if (salesStartDate && salesEndDate) {
        const todayString = getTodayLocalDateString();
        // ALWAYS update to today, don't just check - ensure dates are never stale
        if (salesStartDate.value !== todayString || salesEndDate.value !== todayString) {
            salesStartDate.value = todayString;
            salesStartDate.setAttribute('value', todayString);
            salesEndDate.value = todayString;
            salesEndDate.setAttribute('value', todayString);
            console.log('Auto-update: Reset sales report dates to today:', todayString);
            // Reload sales report if on relevant tab
            const overviewTab = document.getElementById('overview-tab');
            const reportsTab = document.getElementById('reports-tab');
            if ((overviewTab && overviewTab.classList.contains('active')) || 
                (reportsTab && reportsTab.classList.contains('active'))) {
                loadSalesReport();
            }
        }
    }
    
    // Refresh dashboard data periodically (every 5 minutes)
    const overviewTab = document.getElementById('overview-tab');
    if (overviewTab && overviewTab.classList.contains('active')) {
        // Refresh every 5 minutes to keep data current
        if (Math.floor(Date.now() / 1000) % 300 === 0) {
            refreshDashboard();
        }
    }
}, 60000); // Check every minute

// Add event listeners to date inputs to auto-load report when dates change
document.addEventListener('DOMContentLoaded', function() {
    const salesStartDate = document.getElementById('salesStartDate');
    const salesEndDate = document.getElementById('salesEndDate');
    
    if (salesStartDate) {
        salesStartDate.addEventListener('change', function() {
            // Small delay to ensure both dates are updated
            setTimeout(function() {
                loadSalesReport();
            }, 100);
        });
    }
    
    if (salesEndDate) {
        salesEndDate.addEventListener('change', function() {
            // Small delay to ensure both dates are updated
            setTimeout(function() {
                loadSalesReport();
            }, 100);
        });
    }
    
    // Auto-update shift date filter to today for daily reports
    const shiftDateFilter = document.getElementById('shiftDateFilter');
    const reportPeriodFilter = document.getElementById('reportPeriodFilter');
    
    if (shiftDateFilter && reportPeriodFilter) {
        // Check and update date when filter changes
        shiftDateFilter.addEventListener('change', function() {
            const period = reportPeriodFilter.value;
            if (period === 'daily') {
                const todayString = getTodayLocalDateString();
                // If date is not today, force update to today
                if (shiftDateFilter.value !== todayString) {
                    shiftDateFilter.value = todayString;
                    console.log('Auto-updated shiftDateFilter to today:', todayString);
                }
                // Reload reports with updated date
                setTimeout(() => loadShiftReports(), 100);
            } else {
                // For non-daily periods, just reload reports
                setTimeout(() => loadShiftReports(), 100);
            }
        });
        
        // Also check periodically if date needs updating for daily reports
        setInterval(function() {
            const period = reportPeriodFilter.value;
            if (period === 'daily') {
                const todayString = getTodayLocalDateString();
                if (shiftDateFilter.value !== todayString) {
                    shiftDateFilter.value = todayString;
                    console.log('Periodic update: Set shiftDateFilter to today:', todayString);
                    loadShiftReports();
                }
            }
        }, 60000); // Check every minute
    }
});

// Load shift reports when Reports tab is shown
const reportsTab = document.getElementById('reports-tab');
if (reportsTab) {
    reportsTab.addEventListener('shown.bs.tab', function () {
        // IMMEDIATELY force update shift date filter to today (before anything else)
        const todayString = getTodayLocalDateString();
        
        const shiftDateFilter = document.getElementById('shiftDateFilter');
        if (shiftDateFilter) {
            // Force update to today's date - ALWAYS update, don't check
            shiftDateFilter.value = todayString;
            console.log('Updated shiftDateFilter to:', todayString);
        }
        
        // Also ensure report period is set to daily if not already
        const reportPeriodFilter = document.getElementById('reportPeriodFilter');
        if (reportPeriodFilter && reportPeriodFilter.value !== 'daily') {
            reportPeriodFilter.value = 'daily';
        }
        
        // Update ALL dates to current when reports tab is opened
        autoUpdateAllDashboardDates();
        
        // Initialize period display (this will update all date-related displays)
        handlePeriodChange();
        
        // Small delay to ensure date is set, then load reports
        setTimeout(function() {
            loadShiftReports();
            loadSalesReport();
        }, 100);
    });
    
    // Also update date immediately if Reports tab is already active on page load
    if (reportsTab.classList.contains('active')) {
        const todayString = getTodayLocalDateString();
        const shiftDateFilter = document.getElementById('shiftDateFilter');
        if (shiftDateFilter) {
            shiftDateFilter.value = todayString;
            handlePeriodChange();
            setTimeout(function() {
                loadShiftReports();
            }, 100);
        }
    }
}

// Update dates when Overview tab is shown
const overviewTab = document.getElementById('overview-tab');
if (overviewTab) {
    overviewTab.addEventListener('shown.bs.tab', function () {
        // Force update all dates to today first
        const todayString = getTodayLocalDateString();
        
        const salesStartDate = document.getElementById('salesStartDate');
        const salesEndDate = document.getElementById('salesEndDate');
        if (salesStartDate) {
            salesStartDate.value = todayString;
            salesStartDate.setAttribute('value', todayString);
        }
        if (salesEndDate) {
            salesEndDate.value = todayString;
            salesEndDate.setAttribute('value', todayString);
        }
        
        // Refresh dashboard data when overview tab is shown
        refreshDashboard();
        // Also update dates to today
        autoUpdateAllDashboardDates();
    });
}

// Update dates when Monitoring tab is shown
const monitoringTab = document.getElementById('monitoring-tab');
if (monitoringTab) {
    monitoringTab.addEventListener('shown.bs.tab', function () {
        autoUpdateAllDashboardDates();
        // Also trigger financial period change to ensure dates are current
        if (document.getElementById('financialPeriodFilter')) {
            handleFinancialPeriodChange();
        }
    });
}


// Financial Summary Filtering Functions
function handleFinancialPeriodChange() {
    const period = document.getElementById('financialPeriodFilter').value;
    const startDateInput = document.getElementById('financialStartDate');
    const endDateInput = document.getElementById('financialEndDate');
    const periodDisplay = document.getElementById('financialPeriodDisplay');
    const applyBtn = document.getElementById('applyFinancialBtn');
    
    const today = new Date(); // Need today for date calculations
    let startDate, endDate;
    
    switch(period) {
        case 'daily':
            startDate = getTodayLocalDateString();
            endDate = null;
            startDateInput.value = startDate;
            endDateInput.style.display = 'none';
            periodDisplay.style.display = 'none';
            // Auto-apply for daily
            setTimeout(() => applyFinancialFilters(), 100);
            break;
        case 'weekly':
            startDate = new Date(today);
            startDate.setDate(today.getDate() - today.getDay());
            endDate = new Date(startDate);
            endDate.setDate(startDate.getDate() + 6);
            startDateInput.value = startDate.toISOString().split('T')[0];
            endDateInput.value = endDate.toISOString().split('T')[0];
            endDateInput.style.display = 'block';
            periodDisplay.style.display = 'none';
            break;
        case 'biweekly':
            startDate = new Date(today);
            startDate.setDate(today.getDate() - 14);
            endDate = new Date(today);
            startDateInput.value = startDate.toISOString().split('T')[0];
            endDateInput.value = endDate.toISOString().split('T')[0];
            endDateInput.style.display = 'block';
            periodDisplay.style.display = 'none';
            break;
        case 'monthly':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            startDateInput.value = startDate.toISOString().split('T')[0];
            endDateInput.value = endDate.toISOString().split('T')[0];
            endDateInput.style.display = 'block';
            periodDisplay.style.display = 'none';
            break;
        case 'annual':
            startDate = new Date(today.getFullYear(), 0, 1);
            endDate = new Date(today.getFullYear(), 11, 31);
            startDateInput.value = startDate.toISOString().split('T')[0];
            endDateInput.value = endDate.toISOString().split('T')[0];
            endDateInput.style.display = 'block';
            periodDisplay.style.display = 'none';
            break;
    }
}

async function applyFinancialFilters() {
    const period = document.getElementById('financialPeriodFilter')?.value || 'monthly';
    const startDate = document.getElementById('financialStartDate')?.value || '';
    const endDate = document.getElementById('financialEndDate')?.value || '';
    
    // Don't proceed if required elements are missing
    if (!document.getElementById('financialRevenue')) {
        console.error('Financial summary elements not found');
        return;
    }
    
    try {
        const params = new URLSearchParams({
            period: period,
            start_date: startDate || '',
            end_date: endDate || ''
        });
        
        const response = await fetch(`/api/financial-summary?${params.toString()}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Response error:', response.status, errorText);
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            // Update financial summary cards
            document.getElementById('financialRevenue').textContent = 
                '₱' + parseFloat(data.revenue).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('financialExpenses').textContent = 
                '₱' + parseFloat(data.expenses).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('financialProfit').textContent = 
                '₱' + parseFloat(data.profit).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            
            // Update percentage changes
            const revenueChangeEl = document.getElementById('financialRevenueChange');
            const expensesChangeEl = document.getElementById('financialExpensesChange');
            const profitChangeEl = document.getElementById('financialProfitChange');
            
            revenueChangeEl.innerHTML = `<span class="${data.revenueChange >= 0 ? 'text-success' : 'text-danger'}">
                ${data.revenueChange >= 0 ? '+' : ''}${parseFloat(data.revenueChange).toFixed(1)}% from last period
            </span>`;
            
            expensesChangeEl.innerHTML = `<span class="${data.expensesChange <= 0 ? 'text-success' : 'text-danger'}">
                ${data.expensesChange >= 0 ? '+' : ''}${parseFloat(data.expensesChange).toFixed(1)}% from last period
            </span>`;
            
            profitChangeEl.innerHTML = `<span class="${data.profitChange >= 0 ? 'text-success' : 'text-danger'}">
                ${data.profitChange >= 0 ? '+' : ''}${parseFloat(data.profitChange).toFixed(1)}% from last period
            </span>`;
            
            // Update chart
            if (window.revenueChart && data.chartData) {
                window.revenueChart.data.labels = data.chartData.labels || [];
                window.revenueChart.data.datasets[0].data = data.chartData.revenues || [];
                window.revenueChart.data.datasets[1].data = data.chartData.expenses || [];
                window.revenueChart.update();
            }
        } else {
            console.error('Error loading financial summary:', data.message || 'Unknown error');
            alert('Error loading financial summary: ' + (data.message || 'Please try again.'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error loading financial summary: ' + error.message + '. Please check the console for details.');
    }
}

function refreshFinancialSummary() {
    applyFinancialFilters();
}

</script>

@endsection
