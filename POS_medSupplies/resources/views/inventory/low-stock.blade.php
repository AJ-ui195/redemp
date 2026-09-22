@extends('layouts.app')

@section('content')

<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Inventory
            </a>
            <h2 class="mb-0">Low Stock Alert</h2>
        </div>
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle text-warning fs-3"></i>
            <span class="badge bg-warning fs-6">{{ $products->count() }} items</span>
        </div>
    </div>

    @if($products->count() > 0)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Attention Required!</strong> The following products are running low on stock and may need to be restocked soon.
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Low Stock Products</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Current Stock</th>
                                <th>Min. Required</th>
                                <th>Shortfall</th>
                                <th>Priority</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                @if($product->category === 'Medical Equipment')
                                                    <i class="bi bi-heart-pulse text-primary fs-5"></i>
                                                @elseif($product->category === 'Medicines')
                                                    <i class="bi bi-capsule text-success fs-5"></i>
                                                @else
                                                    <i class="bi bi-bandaid text-info fs-5"></i>
                                                @endif
                                            </div>
                                            <div>
                                                <div class="fw-semibold">{{ $product->name }}</div>
                                                <small class="text-muted">{{ $product->sku }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $product->category }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-warning">{{ $product->stock_quantity }}</span>
                                        <small class="text-muted d-block">{{ $product->unit }}</small>
                                    </td>
                                    <td>
                                        <span class="fw-semibold">{{ $product->min_stock_level }}</span>
                                        <small class="text-muted d-block">{{ $product->unit }}</small>
                                    </td>
                                    <td>
                                        @php
                                            $shortfall = $product->min_stock_level - $product->stock_quantity;
                                        @endphp
                                        <span class="fw-semibold text-danger">{{ $shortfall }}</span>
                                        <small class="text-muted d-block">{{ $product->unit }}</small>
                                    </td>
                                    <td>
                                        @php
                                            $percentage = $product->min_stock_level > 0 ? 
                                                ($product->stock_quantity / $product->min_stock_level) * 100 : 100;
                                        @endphp
                                        @if($product->stock_quantity == 0)
                                            <span class="badge bg-danger">Critical</span>
                                        @elseif($percentage <= 25)
                                            <span class="badge bg-warning">High</span>
                                        @elseif($percentage <= 50)
                                            <span class="badge bg-info">Medium</span>
                                        @else
                                            <span class="badge bg-success">Low</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('inventory.show', $product) }}" class="btn btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('inventory.edit', $product) }}" class="btn btn-outline-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-check-circle text-success display-1 mb-3"></i>
                <h4 class="text-success">Great News!</h4>
                <p class="text-muted mb-0">All products are well-stocked. No low stock alerts at this time.</p>
            </div>
        </div>
    @endif
</div>

@endsection
