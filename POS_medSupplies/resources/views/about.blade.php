@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

@section('content')
<div class="admin-container">
    <!-- Header Section (same as Admin) -->
    <div class="admin-header">
        <div class="d-flex justify-content-between align-items-center">
            <div class="header-brand">
                <div class="brand-logo">
                    <img src="{{ asset('images/logo.jpg') }}" alt="REDEMP Logo" class="logo-image">
                </div>
                <div class="brand-text">
                    <h3 class="brand-name mb-0">ADMIN DASHBOARD</h3>
                    <small class="brand-tagline">SYSTEM MANAGEMENT &amp; OVERSIGHT</small>
                </div>
            </div>
            <div class="header-controls">
                <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.dashboard') }}">
                    <i class="bi bi-speedometer2 me-1"></i>Back to Admin
                </a>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('about') }}">
                    <i class="bi bi-info-circle me-1"></i>About
                </a>
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm">Logout</button>
            </form>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">About This System</h2>
                <p class="text-muted mb-0">REDEMP Medical Supplies &amp; Pharmacy POS Suite</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('dashboard') }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-cash-stack me-1"></i>Cashier
                </a>
                <a href="{{ route('inventory.index') }}" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-box-seam me-1"></i>Inventory
                </a>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-dark btn-sm">
                    <i class="bi bi-speedometer2 me-1"></i>Admin
                </a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Purpose</h5>
                        <p class="text-muted mb-3">
                            This platform streamlines pharmacy operations across cashiering, inventory, and administration — ensuring accurate sales, stock visibility, and role-based oversight.
                        </p>
                        <h6 class="fw-bold">Key Capabilities</h6>
                        <ul class="mb-0 text-muted">
                            <li>Fast POS with barcode/QR support and shift tracking</li>
                            <li>Inventory monitoring with expirations, low-stock alerts, and exports</li>
                            <li>Admin analytics, user management, and system configuration</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <h6 class="fw-bold">Support</h6>
                        <p class="text-muted small mb-3">
                            For assistance or feedback, contact your system administrator or the Techies support team.
                        </p>
                        <div class="mt-auto">
                            <div class="small text-muted">Build</div>
                            <div class="fw-semibold">Techies POS Suite</div>
                            <div class="text-muted small">Powered By: Techies</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

