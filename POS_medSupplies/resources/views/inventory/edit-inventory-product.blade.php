@extends('layouts.app')

@section('content')

<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Inventory
            </a>
            <h2 class="mb-0">Edit Product</h2>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Product Information</h5>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('inventory-product.update', $inventoryProduct) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <!-- Basic Information -->
                            <div class="col-12">
                                <h6 class="text-muted mb-3">Basic Information</h6>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Item Name <span class="text-danger">*</span></label>
                                <input type="text" name="item_name" class="form-control @error('item_name') is-invalid @enderror" 
                                       value="{{ old('item_name', $inventoryProduct->item_name) }}" required>
                                @error('item_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Brand</label>
                                <input type="text" name="brand" class="form-control @error('brand') is-invalid @enderror"
                                       value="{{ old('brand', $inventoryProduct->brand) }}" placeholder="e.g., Acme">
                                @error('brand')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Barcode Value</label>
                                <input type="text" name="barcode_value" class="form-control @error('barcode_value') is-invalid @enderror" 
                                       value="{{ old('barcode_value', $inventoryProduct->barcode_value) }}">
                                @error('barcode_value')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Item Image</label>
                                <input type="file" name="item_image" class="form-control @error('item_image') is-invalid @enderror" 
                                       accept="image/*" id="itemImageInput">
                                @error('item_image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Upload a new image to replace the current one</small>
                                
                                @php
                                    $imagePath = null;
                                    // Only set imagePath if file actually exists
                                    if (!empty($inventoryProduct->item_image)) {
                                        $imageFile = trim($inventoryProduct->item_image);
                                        
                                        // Check if it's already a full URL
                                        if (strpos($imageFile, 'http') === 0) {
                                            $imagePath = $imageFile;
                                        } elseif (strpos($imageFile, 'images/') === 0) {
                                            // Path already starts with images/, check if file exists
                                            $fullPath = public_path($imageFile);
                                            if (@file_exists($fullPath) && @is_readable($fullPath) && @filesize($fullPath) > 0) {
                                                $imagePath = asset($imageFile);
                                            }
                                        } elseif (strpos($imageFile, 'storage/') === 0) {
                                            // Path already starts with storage/, check if file exists
                                            $storagePath = str_replace('storage/', '', $imageFile);
                                            $fullPath = storage_path('app/public/' . $storagePath);
                                            if (@file_exists($fullPath) && @is_readable($fullPath) && @filesize($fullPath) > 0) {
                                                $imagePath = asset($imageFile);
                                            }
                                        } else {
                                            // Try images/inventory/ first (most common location)
                                            $fullPath = public_path('images/inventory/' . $imageFile);
                                            if (@file_exists($fullPath) && @is_readable($fullPath) && @filesize($fullPath) > 0) {
                                                $imagePath = asset('images/inventory/' . $imageFile);
                                            } else {
                                                $fullPath = public_path('images/' . $imageFile);
                                                if (@file_exists($fullPath) && @is_readable($fullPath) && @filesize($fullPath) > 0) {
                                                    $imagePath = asset('images/' . $imageFile);
                                                } else {
                                                    $fullPath = storage_path('app/public/' . $imageFile);
                                                    if (@file_exists($fullPath) && @is_readable($fullPath) && @filesize($fullPath) > 0) {
                                                        $imagePath = asset('storage/' . $imageFile);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                @endphp
                                
                                @if($imagePath)
                                    <div class="mt-3">
                                        <h6 class="form-label mb-2">Current Image</h6>
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="{{ $imagePath }}" alt="Current Image" class="img-thumbnail" 
                                                 style="max-width: 200px; max-height: 200px; object-fit: cover;" 
                                                 id="currentImagePreview"
                                                 onerror="this.onerror=null; this.style.display='none'; const msg = document.getElementById('noImageMsg'); if(msg) msg.style.display='block'; return false;">
                                            <div id="noImageMsg" style="display: none;" class="text-muted">
                                                <i class="bi bi-image"></i> No image available
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                
                                <div class="mt-2" id="newImagePreview" style="display: none;">
                                    <label class="form-label">New Image Preview</label>
                                    <div>
                                        <img id="newImagePreviewImg" src="" alt="Preview" class="img-thumbnail" 
                                             style="max-width: 200px; max-height: 200px; object-fit: cover;">
                                        <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeNewImage()">
                                            <i class="bi bi-x-circle me-1"></i>Remove New Image
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Pricing & Stock -->
                            <div class="col-12">
                                <h6 class="text-muted mb-3 mt-4">Pricing & Stock</h6>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Price (₱)</label>
                                <input type="number" name="price" step="0.01" min="0" 
                                       class="form-control @error('price') is-invalid @enderror" 
                                       value="{{ old('price', $inventoryProduct->price) }}">
                                @error('price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Price Type</label>
                                <select name="price_type" class="form-select @error('price_type') is-invalid @enderror">
                                    <option value="">Select Price Type</option>
                                    <option value="retail" {{ old('price_type', $inventoryProduct->price_type) == 'retail' ? 'selected' : '' }}>Retail</option>
                                    <option value="wholesale" {{ old('price_type', $inventoryProduct->price_type) == 'wholesale' ? 'selected' : '' }}>Wholesale</option>
                                </select>
                                @error('price_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Quantity on Hand</label>
                                <input type="number" name="quantity_on_hand" step="1" min="0" 
                                       class="form-control @error('quantity_on_hand') is-invalid @enderror" 
                                       value="{{ old('quantity_on_hand', number_format((float) ($inventoryProduct->quantity_on_hand ?? 0), 0, '.', '')) }}">
                                @error('quantity_on_hand')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Unit</label>
                                <select name="unit" class="form-select @error('unit') is-invalid @enderror">
                                    <option value="">Select Unit</option>
                                    <option value="box" {{ old('unit', $inventoryProduct->unit) == 'box' ? 'selected' : '' }}>Box</option>
                                    <option value="case" {{ old('unit', $inventoryProduct->unit) == 'case' ? 'selected' : '' }}>Case</option>
                                    <option value="roll" {{ old('unit', $inventoryProduct->unit) == 'roll' ? 'selected' : '' }}>Roll</option>
                                    <option value="gal" {{ old('unit', $inventoryProduct->unit) == 'gal' ? 'selected' : '' }}>Gal</option>
                                    <option value="set" {{ old('unit', $inventoryProduct->unit) == 'set' ? 'selected' : '' }}>Set</option>
                                    <option value="bottle" {{ old('unit', $inventoryProduct->unit) == 'bottle' ? 'selected' : '' }}>Bottle</option>
                                    <option value="unit" {{ old('unit', $inventoryProduct->unit) == 'unit' ? 'selected' : '' }}>Unit</option>
                                    <option value="pair" {{ old('unit', $inventoryProduct->unit) == 'pair' ? 'selected' : '' }}>Pair</option>
                                    <option value="pck" {{ old('unit', $inventoryProduct->unit) == 'pck' ? 'selected' : '' }}>Pck</option>
                                    <option value="piece" {{ old('unit', $inventoryProduct->unit) == 'piece' ? 'selected' : '' }}>Piece</option>
                                </select>
                                @error('unit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Additional Information -->
                            <div class="col-12">
                                <h6 class="text-muted mb-3 mt-4">Additional Information</h6>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">MFG Date</label>
                                <input type="date" name="mfg_date" class="form-control @error('mfg_date') is-invalid @enderror" 
                                       value="{{ old('mfg_date', $inventoryProduct->mfg_date?->format('Y-m-d')) }}">
                                @error('mfg_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Expiration Date</label>
                                <input type="date" name="expiration_date" class="form-control @error('expiration_date') is-invalid @enderror" 
                                       value="{{ old('expiration_date', $inventoryProduct->expiration_date?->format('Y-m-d')) }}">
                                @error('expiration_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Lot Number</label>
                                <input type="text" name="lot_number" class="form-control @error('lot_number') is-invalid @enderror"
                                       value="{{ old('lot_number', $inventoryProduct->lot_number) }}">
                                @error('lot_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Active Status</label>
                                <select name="active_status" class="form-select @error('active_status') is-invalid @enderror">
                                    <option value="Active" {{ old('active_status', $inventoryProduct->active_status) == 'Active' ? 'selected' : '' }}>Active</option>
                                    <option value="Inactive" {{ old('active_status', $inventoryProduct->active_status) == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('active_status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Update Product
                            </button>
                            <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.getElementById('itemImageInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('newImagePreviewImg').src = e.target.result;
                document.getElementById('newImagePreview').style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });

    function removeNewImage() {
        document.getElementById('itemImageInput').value = '';
        document.getElementById('newImagePreview').style.display = 'none';
        document.getElementById('newImagePreviewImg').src = '';
    }
</script>
@endpush

