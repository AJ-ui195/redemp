<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Preview</title>
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            margin: 0;
        }
        body {
            padding: 8px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            font-size: 0.85rem;
        }
        .preview-toolbar {
            flex: 0 0 auto;
            margin-bottom: 6px;
        }
        .preview-scroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
        }
        .preview-footer {
            flex: 0 0 auto;
            margin-top: 4px;
        }
        .table {
            margin-bottom: 0;
        }
        .table thead th {
            position: sticky;
            top: 0;
            background: #f8f9fa;
            z-index: 2;
            white-space: nowrap;
        }
        .table td {
            vertical-align: middle;
            white-space: nowrap;
        }
        .item-name {
            max-width: 160px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        @if(request()->boolean('compact'))
        .col-optional {
            display: none;
        }
        .item-name {
            max-width: 140px;
        }
        @endif
    </style>
</head>
<body>
    @if(session('success'))
        <div class="alert alert-success py-1 px-2 mb-2 preview-toolbar">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('admin.inventory.preview') }}" class="preview-toolbar">
        @if(request()->boolean('compact'))
            <input type="hidden" name="compact" value="1">
        @endif
        @if(request('stock_status'))
            <input type="hidden" name="stock_status" value="{{ request('stock_status') }}">
        @endif
        @if(request('expiration'))
            <input type="hidden" name="expiration" value="{{ request('expiration') }}">
        @endif
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-white">Search</span>
            <input
                type="text"
                name="search"
                class="form-control"
                placeholder="Search item, brand, barcode..."
                value="{{ request('search') }}"
            >
            <button class="btn btn-primary" type="submit">Go</button>
            @if(request('search'))
                <a href="{{ route('admin.inventory.preview', request()->except('search')) }}" class="btn btn-outline-secondary">Clear</a>
            @endif
        </div>
    </form>

    <div class="preview-scroll">
        <table class="table table-sm table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Item</th>
                    <th class="text-end">Qty</th>
                    <th class="col-optional">Unit</th>
                    <th class="text-end">SRP</th>
                    <th>Expiry</th>
                    <th class="text-end col-optional">Cost</th>
                    <th class="text-center col-optional">Edit</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>
                            <div class="item-name" title="{{ $product->item_name }}">{{ $product->item_name }}</div>
                        </td>
                        <td class="text-end">{{ number_format($product->quantity_on_hand ?? 0, 0) }}</td>
                        <td class="col-optional">{{ $product->unit ?? 'N/A' }}</td>
                        <td class="text-end">{{ number_format($product->price ?? 0, 2) }}</td>
                        <td>
                            @if($product->expiration_date)
                                {{ \Carbon\Carbon::parse($product->expiration_date)->format('Y-m-d') }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td class="text-end col-optional">
                            @if($product->original_price !== null)
                                {{ number_format($product->original_price, 2) }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td class="text-center col-optional">
                            <button
                                type="button"
                                class="btn btn-outline-primary btn-sm py-0 px-1"
                                title="Edit Original Price"
                                onclick="openOriginalPriceModal(this)"
                                data-product-id="{{ $product->id }}"
                                data-item-name="{{ $product->item_name }}"
                                data-original-price="{{ $product->original_price ?? '' }}"
                            >
                                <i class="bi bi-pencil-square"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-3">No products found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="preview-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Showing {{ $products->count() }} products</small>
    </div>

    <div class="modal fade" id="originalPriceModal" tabindex="-1" aria-labelledby="originalPriceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="originalPriceModalForm" method="POST">
                    @csrf
                    <div class="modal-header py-2">
                        <h5 class="modal-title" id="originalPriceModalLabel">Update Original Price</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <small class="text-muted">Item</small>
                            <div id="originalPriceItemName" class="fw-semibold">-</div>
                        </div>
                        <div>
                            <label for="originalPriceModalInput" class="form-label">Original Price</label>
                            <input
                                type="number"
                                name="original_price"
                                step="0.01"
                                min="0"
                                class="form-control"
                                id="originalPriceModalInput"
                                placeholder="0.00"
                                required
                            >
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openOriginalPriceModal(button) {
            const productId = button.getAttribute('data-product-id');
            const itemName = button.getAttribute('data-item-name') || 'Unknown Item';
            const currentPrice = button.getAttribute('data-original-price');

            const form = document.getElementById('originalPriceModalForm');
            const itemNameEl = document.getElementById('originalPriceItemName');
            const inputEl = document.getElementById('originalPriceModalInput');

            if (!form || !itemNameEl || !inputEl || !productId) {
                return;
            }

            form.action = `/admin/costing/${productId}/original-price`;
            itemNameEl.textContent = itemName;
            inputEl.value = currentPrice !== '' && currentPrice !== null ? Number(currentPrice).toFixed(2) : '0.00';

            const modal = new bootstrap.Modal(document.getElementById('originalPriceModal'));
            modal.show();
        }
    </script>
</body>
</html>
