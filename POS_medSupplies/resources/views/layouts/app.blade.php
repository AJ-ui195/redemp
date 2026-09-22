<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'POS')</title>
    <link rel="icon" type="image/png" href="{{ url('public/images/logo-removebg-preview.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ url('public/images/logo-removebg-preview.png') }}">
    <!-- Bootstrap CSS -->
    <link id="bootstrap-css" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons (local first, CDN fallback) -->
    <link id="bootstrap-icons" rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" media="print" onload="this.media='all'">
    @stack('styles')
    
    <!-- CDN Fallback Script -->
    <script>
    (function() {
        // Function to load fallback CSS
        function loadBootstrapFallback() {
            const existing = document.getElementById('bootstrap-css-fallback');
            if (!existing) {
                const link = document.createElement('link');
                link.id = 'bootstrap-css-fallback';
                link.rel = 'stylesheet';
                link.href = 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css';
                link.crossOrigin = 'anonymous';
                document.head.appendChild(link);
            }
        }
        
        function loadIconsFallback() {
            const existing = document.getElementById('bootstrap-icons-fallback');
            if (!existing) {
                const link = document.createElement('link');
                link.id = 'bootstrap-icons-fallback';
                link.rel = 'stylesheet';
                link.href = 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css';
                link.crossOrigin = 'anonymous';
                document.head.appendChild(link);
            }
        }
        
        // Check if Bootstrap CSS loaded by testing a Bootstrap-specific style
        function checkBootstrapLoaded() {
            const testEl = document.createElement('div');
            testEl.className = 'd-none';
            testEl.style.position = 'absolute';
            testEl.style.visibility = 'hidden';
            document.body.appendChild(testEl);
            
            const styles = window.getComputedStyle(testEl);
            const display = styles.display;
            
            document.body.removeChild(testEl);
            
            // Bootstrap's .d-none class sets display: none
            // If it's not working, Bootstrap didn't load
            if (display !== 'none') {
                console.warn('Bootstrap CSS may not have loaded, loading fallback...');
                loadBootstrapFallback();
            }
        }
        
        // Verify icon font is usable; if not, load CDN fallback
        function checkIconsLoaded() {
            const iconsLink = document.getElementById('bootstrap-icons');
            if (!iconsLink) {
                loadIconsFallback();
                return;
            }

            setTimeout(() => {
                try {
                    const probe = document.createElement('i');
                    probe.className = 'bi bi-info-circle';
                    probe.style.cssText = 'position:absolute;left:-9999px;font-size:16px;';
                    document.body.appendChild(probe);
                    const family = window.getComputedStyle(probe).fontFamily || '';
                    document.body.removeChild(probe);
                    if (!/bootstrap-icons/i.test(family) && !iconsLink.sheet) {
                        console.warn('Bootstrap Icons not available, loading CDN fallback...');
                        loadIconsFallback();
                    }
                } catch (e) {
                    loadIconsFallback();
                }
            }, 800);
        }
        
        // Run checks after page loads
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(checkBootstrapLoaded, 1500);
                setTimeout(checkIconsLoaded, 500);
            });
        } else {
            setTimeout(checkBootstrapLoaded, 1500);
            setTimeout(checkIconsLoaded, 500);
        }
    })();
    </script>
</head>
<body>
@yield('content')

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<!-- Bootstrap JS Fallback -->
<script>
    if (typeof bootstrap === 'undefined') {
        setTimeout(function() {
            if (typeof bootstrap === 'undefined') {
                console.warn('Bootstrap JS failed to load, trying fallback...');
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js';
                script.crossOrigin = 'anonymous';
                document.body.appendChild(script);
            }
        }, 2000);
    }
</script>
@stack('scripts')
</body>
</html>
