@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h3 class="mb-3">Copy barcode photos</h3>
    <p class="text-muted">
        This copies photo paths from Generate Barcode onto matching inventory products.
        It does not create products and does not change quantity or price.
        Retail and wholesale stay separate.
    </p>

    <div class="card mb-3">
        <div class="card-body">
            <p class="mb-1">Inventory photos that would be filled: <strong>{{ $result['inventory_filled'] }}</strong></p>
            <p class="mb-1">Item list photos that would be filled: <strong>{{ $result['item_list_filled'] }}</strong></p>
            <p class="mb-1">Already have a photo (skipped): <strong>{{ $result['skipped_has_image'] }}</strong></p>
            <p class="mb-1">Barcode photo with no matching product: <strong>{{ $result['skipped_no_match'] }}</strong></p>
            <p class="mb-0">Photo files missing on this server: <strong>{{ $result['missing_files'] }}</strong></p>
        </div>
    </div>

    @if($result['missing_files'] > 0)
        <div class="alert alert-warning">
            Some barcode photos are not in <code>public/images/barcodes/</code> on this server.
            Copying the path will not fix a 404 until those files are uploaded (GitHub does not always include uploaded photos).
        </div>
    @endif

    @if(count($result['changes']) > 0)
        <p class="fw-semibold">Preview ({{ count($result['changes']) }} change{{ count($result['changes']) === 1 ? '' : 's' }}):</p>
        <pre class="bg-light p-3" style="max-height: 320px; overflow: auto;">{{ implode("\n", $result['changes']) }}</pre>
    @else
        <p>Nothing to copy. Matching products already have photos, or barcodes have no photos.</p>
    @endif

    <div class="d-flex gap-2 mt-3">
        <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">Back to inventory</a>
        @if($result['inventory_filled'] > 0 || $result['item_list_filled'] > 0)
            <form method="POST" action="{{ route('inventory.sync-barcode-images.apply') }}" onsubmit="return confirm('Copy these photo paths now? No products will be created. Qty and price will not change.');">
                @csrf
                <button type="submit" class="btn btn-primary">Apply copy</button>
            </form>
        @endif
    </div>
</div>
@endsection
