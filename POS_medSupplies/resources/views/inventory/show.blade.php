@extends('layouts.app')

@section('content')

<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Inventory
            </a>
            <h2 class="mb-0">Product Details</h2>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('inventory.edit', $product) }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit Product
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Product Info -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <div class="d-flex align-items-center gap-3">
                        @if($product->category === 'Medical Equipment')
                            <i class="bi bi-heart-pulse text-primary fs-2"></i>
                        @elseif($product->category === 'Medicines')
                            <i class="bi bi-capsule text-success fs-2"></i>
                        @else
                            <i class="bi bi-bandaid text-info fs-2"></i>
                        @endif
                        <div>
                            <h4 class="mb-1">{{ $product->name }}</h4>
                            <span class="badge bg-secondary fs-6">{{ $product->category }}</span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted">Description</label>
                            <p class="mb-0">{{ $product->description ?: 'No description provided' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Brand</label>
                            <p class="mb-0">{{ $product->brand ?: 'Generic' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">SKU</label>
                            <p class="mb-0"><code>{{ $product->sku ?: 'Not assigned' }}</code></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Unit</label>
                            <p class="mb-0">{{ $product->unit }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Supplier</label>
                            <p class="mb-0">{{ $product->supplier ?: 'Not specified' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Price</label>
                            <p class="mb-0 fs-5 fw-bold text-primary">₱{{ number_format($product->price, 2) }}</p>
                        </div>
                        @if($product->expiry_date)
                            <div class="col-md-6">
                                <label class="form-label text-muted">Expiry Date</label>
                                <p class="mb-0">
                                    {{ $product->expiry_date->format('M d, Y') }}
                                    @if($product->isExpired())
                                        <span class="badge bg-danger ms-2">Expired</span>
                                    @elseif($product->isExpiringSoon())
                                        <span class="badge bg-warning ms-2">Expiring Soon</span>
                                    @else
                                        <span class="badge bg-success ms-2">Valid</span>
                                    @endif
                                </p>
                            </div>
                        @endif
                        <div class="col-md-6">
                            <label class="form-label text-muted">Status</label>
                            <div class="d-flex gap-2">
                                @if(!$product->is_active)
                                    <span class="badge bg-secondary">Inactive</span>
                                @else
                                    <span class="badge bg-success">Active</span>
                                @endif
                                @if($product->requires_prescription)
                                    <span class="badge bg-warning">Prescription Required</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Information -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Stock Information</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="display-4 fw-bold 
                            @if($product->stock_quantity == 0) text-danger
                            @elseif($product->isLowStock()) text-warning
                            @else text-success @endif">
                            {{ $product->stock_quantity }}
                        </div>
                        <p class="text-muted mb-0">{{ $product->unit }} in stock</p>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Minimum Stock Level</span>
                            <span class="fw-semibold">{{ $product->min_stock_level }}</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            @php
                                $percentage = $product->min_stock_level > 0 ? 
                                    min(100, ($product->stock_quantity / $product->min_stock_level) * 100) : 100;
                            @endphp
                            <div class="progress-bar 
                                @if($percentage <= 100) bg-warning
                                @else bg-success @endif" 
                                style="width: {{ min(100, $percentage) }}%">
                            </div>
                        </div>
                    </div>

                    @if($product->isLowStock())
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Low Stock Alert!</strong><br>
                            Current stock is below minimum level.
                        </div>
                    @elseif($product->stock_quantity == 0)
                        <div class="alert alert-danger">
                            <i class="bi bi-x-circle me-2"></i>
                            <strong>Out of Stock!</strong><br>
                            This product needs to be restocked immediately.
                        </div>
                    @else
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            <strong>Stock Level OK</strong><br>
                            Current stock is above minimum level.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('inventory.edit', $product) }}" class="btn btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit Product
                        </a>
                        <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#adjustStockModal">
                            <i class="bi bi-arrow-up-down"></i> Adjust Stock
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Adjust Stock Modal -->
<div class="modal fade" id="adjustStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adjust Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('inventory.update', $product) }}">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Current Stock</label>
                        <input type="text" class="form-control" value="{{ $product->stock_quantity }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Stock Quantity</label>
                        <input type="number" name="stock_quantity" class="form-control" 
                               value="{{ $product->stock_quantity }}" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
