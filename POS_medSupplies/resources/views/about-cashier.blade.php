@extends('layouts.app')

@push('styles')
<style>
/* Scope only to cashier about page */
.pos-container .brand-logo .logo-image {
    width: 44px;
    height: 44px;
    object-fit: contain;
}
</style>
@endpush

@section('content')
<div class="pos-container">
    <div class="pos-header">
        <div class="d-flex justify-content-between align-items-center">
            <div class="brand-section">
                <div class="brand-logo">
                    <img src="{{ asset('images/logo.jpg') }}" alt="REDEMP Logo" class="logo-image">
                </div>
                <div class="brand-text">
                    <h3 class="brand-name mb-0">CASHIER</h3>
                    <small class="brand-tagline">Point of Sale &amp; Shift Management</small>
                </div>
            </div>
            <div class="header-controls">
                <a class="btn btn-outline-primary btn-sm" href="{{ route('dashboard') }}">
                    <i class="bi bi-cash-stack me-1"></i>Back to Cashier
                </a>
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm">Logout</button>
            </form>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <div class="card shadow-sm border-0 mb-4 position-relative overflow-hidden" style="background: radial-gradient(circle at 18% 15%, rgba(0,255,170,0.18), transparent 35%), radial-gradient(circle at 85% 15%, rgba(0,123,255,0.16), transparent 40%), linear-gradient(120deg, #0f172a, #10334a); color: #fff;">
            <div class="position-absolute top-0 end-0 p-4" style="pointer-events:none;">
                <div style="width:120px; height:120px; border-radius:18px; background: linear-gradient(145deg, rgba(76,175,80,0.25), rgba(0,255,170,0.12)); box-shadow: -10px 20px 40px rgba(0,0,0,0.35); transform: rotate(8deg) translateY(-10px); backdrop-filter: blur(4px); border:1px solid rgba(255,255,255,0.08);"></div>
            </div>
            <div class="position-absolute bottom-0 start-0 p-4" style="pointer-events:none;">
                <div style="width:170px; height:90px; border-radius:14px; background: linear-gradient(145deg, rgba(0,255,170,0.18), rgba(76,175,80,0.08)); box-shadow: 0 25px 60px rgba(0,0,0,0.35); transform: rotate(-6deg) translateY(18px); border:1px solid rgba(255,255,255,0.08);"></div>
            </div>
            <div class="card-body d-flex flex-column flex-lg-row align-items-start align-items-lg-center gap-3 position-relative" style="z-index:2;">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-success">Cashier Suite</span>
                        <span class="badge bg-outline-light border border-light text-light">Fast Checkout</span>
                        <span class="badge bg-outline-light border border-light text-light">Shift Control</span>
                    </div>
                    <h2 class="mb-1">About the Cashier Module</h2>
                    <p class="mb-0 text-light opacity-75">Designed for fast checkout, accurate shifts, and smooth customer flow.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <div class="rounded-3 px-3 py-2 text-start" style="background: rgba(255,255,255,0.08); box-shadow: 0 12px 30px rgba(0,0,0,0.25); transform: translateZ(8px); border:1px solid rgba(255,255,255,0.08);">
                        <div class="small text-light opacity-75">Control</div>
                        <div class="fw-semibold">Shifts &amp; Receipts</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Cashier Highlights</h5>
                        <ul class="mb-0 text-muted">
                            <li>Barcode/QR scanning with product lookup</li>
                            <li>Shift tracking with start/end controls</li>
                            <li>Cart, payments, refunds, and receipt flow</li>
                            <li>Quick access products for faster checkout</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-success bg-opacity-10 p-2 me-2">
                                <i class="bi bi-headset text-success"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Support</h6>
                                <small class="text-muted">We’re here to help.</small>
                            </div>
                        </div>
                        <p class="text-muted small mb-3">
                            Contact your supervisor for credentials or shift issues. Technical support: Techies.
                        </p>
                        <div class="border rounded p-2 mb-3">
                            <div class="d-flex align-items-center mb-1">
                                <i class="bi bi-envelope text-primary me-2"></i>
                                <span class="small text-muted">Email : Techies@gmail.com</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-telephone text-primary me-2"></i>
                                <span class="small text-muted"># : +63 (9451) 857 5194</span>
                            </div>
                        </div>
                        <div class="mt-auto">
                            <div class="small text-muted">Office</div>
                            <div class="fw-semibold">Luna Ext, Digos, 8002 Davao del Sur</div>
                            <div class="small text-muted">Build</div>
                            <div class="fw-semibold">REDEMP Medical Supplies &amp; Pharmacy</div>
                            <div class="text-muted small">Powered By: Techies</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

