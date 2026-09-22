@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Costing</h4>
            <small class="text-muted">Separate inventory list for costing</small>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to Admin
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <form method="GET" action="{{ route('admin.costing') }}" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Search item, brand, lot, barcode..."
                            value="{{ request('search') }}"
                        >
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">Search</button>
                    @if(request('search'))
                        <a href="{{ route('admin.costing') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                    @endif
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Item Description</th>
                            <th>Brand</th>
                            <th class="text-end">Qty</th>
                            <th>Unit</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Costing</th>
                            <th>Type</th>
                            <th>Lot</th>
                            <th>Expiry</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td>{{ $product->item_name ?? 'N/A' }}</td>
                                <td>{{ $product->brand ?? 'N/A' }}</td>
                                <td class="text-end">{{ number_format($product->quantity_on_hand ?? 0, 2) }}</td>
                                <td>{{ $product->unit ?? 'N/A' }}</td>
                                <td class="text-end">P{{ number_format($product->price ?? 0, 2) }}</td>
                                <td class="text-end">
                                    @if($product->original_price !== null)
                                        P{{ number_format($product->original_price, 2) }}
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @php $priceType = strtolower((string) ($product->price_type ?? '')); @endphp
                                    @if($priceType === 'wholesale')
                                        <span class="badge bg-success">Wholesale</span>
                                    @elseif($priceType === 'retail')
                                        <span class="badge bg-primary">Retail</span>
                                    @else
                                        <span class="badge bg-secondary">N/A</span>
                                    @endif
                                </td>
                                <td>{{ $product->lot_number ?? 'N/A' }}</td>
                                <td>
                                    @if($product->expiration_date)
                                        {{ \Carbon\Carbon::parse($product->expiration_date)->format('M d, Y') }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">No products found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <small class="text-muted">Showing {{ $products->count() }} of {{ $products->total() }} item(s)</small>
            {{ $products->onEachSide(0)->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection
