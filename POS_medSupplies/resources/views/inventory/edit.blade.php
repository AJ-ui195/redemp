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
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('inventory.update', $product) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Product Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $product->name) }}" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Brand</label>
                                <input type="text" name="brand" class="form-control @error('brand') is-invalid @enderror"
                                       value="{{ old('brand', $product->brand) }}" placeholder="e.g., Coca-Cola">
                                @error('brand') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                                    <option value="">Select Category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">SKU <span class="text-danger">*</span></label>
                                <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror"
                                       value="{{ old('sku', $product->sku) }}" required>
                                @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Barcode</label>
                                <input type="text" name="barcode" class="form-control @error('barcode') is-invalid @enderror"
                                       value="{{ old('barcode', $product->barcode) }}">
                                @error('barcode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Selling Price (₱) <span class="text-danger">*</span></label>
                                <input type="number" name="selling_price" step="0.01" min="0"
                                       class="form-control @error('selling_price') is-invalid @enderror"
                                       value="{{ old('selling_price', $product->selling_price) }}" required>
                                @error('selling_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Cost Price (₱) <span class="text-danger">*</span></label>
                                <input type="number" name="cost_price" step="0.01" min="0"
                                       class="form-control @error('cost_price') is-invalid @enderror"
                                       value="{{ old('cost_price', $product->cost_price) }}" required>
                                @error('cost_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Price Type <span class="text-danger">*</span></label>
                                <select name="price_type" class="form-select @error('price_type') is-invalid @enderror" required>
                                    <option value="retail" @selected(old('price_type', $product->price_type) === 'retail')>Retail</option>
                                    <option value="wholesale" @selected(old('price_type', $product->price_type) === 'wholesale')>Wholesale</option>
                                </select>
                                @error('price_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" min="0"
                                       class="form-control @error('quantity') is-invalid @enderror"
                                       value="{{ old('quantity', $product->quantity) }}" required>
                                @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Unit <span class="text-danger">*</span></label>
                                <select name="unit_id" class="form-select @error('unit_id') is-invalid @enderror" required>
                                    <option value="">Select Unit</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->id }}" @selected(old('unit_id', $product->unit_id) == $unit->id)>
                                            {{ $unit->name }} ({{ $unit->abbreviation }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Lot Number</label>
                                <input type="text" name="lot_number" class="form-control @error('lot_number') is-invalid @enderror"
                                       value="{{ old('lot_number', $product->lot_number) }}">
                                @error('lot_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">MFG Date</label>
                                <input type="date" name="mfg_date" class="form-control @error('mfg_date') is-invalid @enderror"
                                       value="{{ old('mfg_date', optional($product->mfg_date)->format('Y-m-d')) }}">
                                @error('mfg_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Expiration Date</label>
                                <input type="date" name="expiration_date" class="form-control @error('expiration_date') is-invalid @enderror"
                                       value="{{ old('expiration_date', optional($product->expiration_date)->format('Y-m-d')) }}">
                                @error('expiration_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="is_active" class="form-select @error('is_active') is-invalid @enderror">
                                    <option value="1" @selected(old('is_active', $product->is_active) == 1)>Active</option>
                                    <option value="0" @selected(old('is_active', $product->is_active) == 0)>Inactive</option>
                                </select>
                                @error('is_active') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Product Image</label>
                                <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" accept="image/*">
                                @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                @php $imageUrl = \App\ItemImageAssetUrl::resolve($product->image); @endphp
                                @if($imageUrl)
                                    <div class="mt-2">
                                        <img src="{{ $imageUrl }}"
                                             alt="Current image"
                                             class="img-thumbnail"
                                             style="max-width: 160px; max-height: 160px; object-fit: cover;"
                                             onerror="if(this.dataset.tried){this.style.display='none';return;} this.dataset.tried='1'; this.src=this.src.includes('/public/images/')?this.src.replace('/public/images/','/images/'):this.src.replace('/images/','/public/images/');">
                                    </div>
                                @endif
                            </div>

                            <div class="col-12 d-flex justify-content-end gap-2 mt-4">
                                <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-1"></i> Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
