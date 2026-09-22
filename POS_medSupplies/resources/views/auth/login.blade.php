@extends('layouts.app')

@section('content')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
<style>
    body {
        background: #1a237e url("{{ url('/images/Welcomepage.png') }}") no-repeat center center fixed !important;
        background-size: cover !important;
    }
</style>
@endpush

<div class="login-logo">
    <img src="{{ url('/images/logo-removebg-preview.png') }}" alt="Logo" class="logo-img">
</div>

<div class="login-wrapper" style="display:flex; gap: 24px; align-items: stretch; justify-content: center;">
    <div class="card shadow" style="flex: 0 0 650px;">
        <div class="card-body">
            <div class="text-center mb-3">
                <div style="font-weight:700; font-size:18px;">Welcome back</div>
                <div style="color:#6c757d; font-size:14px;">Sign in to continue to your dashboard</div>
            </div>
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email -->
                <div class="form-group mb-3">
                    <label for="email">Email</label>
                    <div class="input-group">
                        <span class="input-group-text" id="email-icon" aria-hidden="true" style="background:#f8f9fa;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" style="color:#6c757d;">
                                <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v.217l6.76 3.848a.5.5 0 0 0 .48 0L15 4.217V4a1 1 0 0 0-1-1z"/>
                                <path d="M15 5.383 9.104 8.697a2.5 2.5 0 0 1-2.208 0L1 5.383V12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1z"/>
                            </svg>
                        </span>
                        <input id="email" type="email"
                               class="form-control @error('email') is-invalid @enderror"
                               name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="you@example.com" aria-describedby="email-icon">
                    </div>
                    @error('email')
                        <span class="error-text">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-group mb-3">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <span class="input-group-text" id="password-icon" aria-hidden="true" style="background:#f8f9fa;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" style="color:#6c757d;">
                                <path d="M3 8a5 5 0 1 1 10 0v1h1.5a.5.5 0 0 1 .5.5v5A1.5 1.5 0 0 1 13.5 16h-11A1.5 1.5 0 0 1 1 14.5v-5a.5.5 0 0 1 .5-.5H3V8zm1 1h8V8a4 4 0 1 0-8 0z"/>
                            </svg>
                        </span>
                        <input id="password" type="password"
                               class="form-control @error('password') is-invalid @enderror"
                               name="password" required autocomplete="current-password" placeholder="Enter your password" aria-describedby="password-icon">
                        <button type="button" class="btn btn-outline-secondary" id="togglePassword" aria-label="Show password">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8z"/>
                                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5z"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <span class="error-text">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Remember + Forgot -->
                <div class="form-group mb-3" style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
                    <div class="form-check" style="margin:0;">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">{{ __('Remember Me') }}</label>
                    </div>
                    @if (Route::has('password.request'))
                        <a class="btn btn-link" href="{{ route('password.request') }}" style="padding:0;">
                            {{ __('Forgot Password?') }}
                        </a>
                    @endif
                </div>

                <!-- Submit -->
                <div class="form-group mb-3">
                    <button type="submit" class="btn btn-primary w-100">
                        {{ __('Login') }}
                    </button>
                </div>

                <!-- Additional link (optional) -->
                @if (Route::has('register'))
                    <div class="text-center" style="color:#6c757d; font-size:14px;">
                        Don't have an account? <a href="{{ route('register') }}">Register</a>
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var toggleBtn = document.getElementById('togglePassword');
    var passwordInput = document.getElementById('password');
    if (toggleBtn && passwordInput) {
        toggleBtn.addEventListener('click', function () {
            var isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            toggleBtn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        });
    }
});
</script>
@endpush
@endsection
