@extends('layouts.app')

@section('content')

<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Inventory
            </a>
            <h2 class="mb-0">Edit Item</h2>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Item Information</h5>
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

                    <form method="POST" action="{{ route('item-list.update', $itemList) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <!-- Basic Information -->
                            <div class="col-12">
                                <h6 class="text-muted mb-3">Basic Information</h6>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Item Name <span class="text-danger">*</span></label>
                                <input type="text" name="item" class="form-control @error('item') is-invalid @enderror" 
                                       value="{{ old('item', $itemList->item) }}" required>
                                @error('item')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Brand</label>
                                <input type="text" name="brand" class="form-control @error('brand') is-invalid @enderror"
                                       value="{{ old('brand', $itemList->brand) }}" placeholder="e.g., Acme">
                                @error('brand')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Barcode Value</label>
                                <input type="text" name="mpn" class="form-control @error('mpn') is-invalid @enderror" 
                                       value="{{ old('mpn', $itemList->mpn) }}">
                                @error('mpn')
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
                                    if (!empty($itemList->item_image)) {
                                        $imageFile = trim($itemList->item_image);
                                        
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
                                                 id="currentImagePreview">
                                        </div>
                                    </div>
                                @elseif(!empty($itemList->item_image))
                                    <div class="mt-3">
                                        <h6 class="form-label mb-2">Current Image</h6>
                                        <div class="alert alert-warning">
                                            <i class="bi bi-exclamation-triangle"></i> 
                                            Image file not found. The stored path is: <code>{{ $itemList->item_image }}</code>
                                            <br><small>Please upload a new image to replace it.</small>
                                        </div>
                                    </div>
                                @else
                                    <div class="mt-3">
                                        <div class="text-muted">
                                            <i class="bi bi-image"></i> No image currently uploaded
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
                                       value="{{ old('price', $itemList->price) }}">
                                @error('price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Price Type</label>
                                <select name="price_type" class="form-select @error('price_type') is-invalid @enderror">
                                    <option value="">Select Price Type</option>
                                    <option value="retail" {{ old('price_type', $itemList->price_type) == 'retail' ? 'selected' : '' }}>Retail</option>
                                    <option value="wholesale" {{ old('price_type', $itemList->price_type) == 'wholesale' ? 'selected' : '' }}>Wholesale</option>
                                </select>
                                @error('price_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Quantity on Hand</label>
                                <input type="number" name="quantity_on_hand" step="1" min="0" 
                                       class="form-control @error('quantity_on_hand') is-invalid @enderror" 
                                       value="{{ old('quantity_on_hand', number_format((float) ($itemList->quantity_on_hand ?? 0), 0, '.', '')) }}">
                                @error('quantity_on_hand')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Unit</label>
                                <input type="text" name="unit_of_measure" class="form-control @error('unit_of_measure') is-invalid @enderror"
                                       value="{{ old('unit_of_measure', $itemList->unit_of_measure) }}" placeholder="e.g., pcs, box, roll">
                                @error('unit_of_measure')
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
                                       value="{{ old('mfg_date', $itemList->mfg_date?->format('Y-m-d')) }}">
                                @error('mfg_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" name="expiry_date" class="form-control @error('expiry_date') is-invalid @enderror" 
                                       value="{{ old('expiry_date', $itemList->expiry_date?->format('Y-m-d')) }}">
                                @error('expiry_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Lot Number</label>
                                <input type="text" name="lot_number" class="form-control @error('lot_number') is-invalid @enderror"
                                       value="{{ old('lot_number', $itemList->lot_number) }}">
                                @error('lot_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Active Status</label>
                                <select name="active_status" class="form-select @error('active_status') is-invalid @enderror">
                                    <option value="Active" {{ old('active_status', $itemList->active_status) == 'Active' ? 'selected' : '' }}>Active</option>
                                    <option value="Inactive" {{ old('active_status', $itemList->active_status) == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('active_status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Update Item
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

