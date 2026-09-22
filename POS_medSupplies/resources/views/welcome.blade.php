<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome - REDEMP Medical Supplies & Pharmacy</title>
    <link rel="icon" type="image/png" href="{{ url('/images/logo-removebg-preview.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ url('/images/logo-removebg-preview.png') }}">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: 100%;
            width: 100%;
            overflow: hidden;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            margin: 0;
            padding: 0;
        }
        
        .welcome-container {
            width: 100vw;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 0;
        }
        
        .welcome-image-wrapper {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #1a237e;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-image-wrapper .welcome-bg-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 1;
        }
        
        /* Centered Design Element */
        .center-design {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 5;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 30px;
        }
        
        .center-content {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px 50px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            max-width: 500px;
        }
        
        .center-content h2 {
            color: white;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .center-content p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .center-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-center {
            padding: 12px 30px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 25px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
        }
        
        .btn-center-primary {
            background: white;
            color: #1a237e;
        }
        
        .btn-center-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(255, 255, 255, 0.4);
            color: #1a237e;
        }
        
        .btn-center-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .btn-center-outline:hover {
            background: white;
            color: #1a237e;
            transform: translateY(-3px);
        }
        
        @media (max-width: 768px) {
            .center-content {
                padding: 30px 30px;
                max-width: 90%;
            }
            
            .center-content h2 {
                font-size: 1.5rem;
            }
            
            .center-content p {
                font-size: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            .center-content {
                padding: 25px 20px;
            }
            
            .center-content h2 {
                font-size: 1.25rem;
            }
            
            .center-actions {
                flex-direction: column;
            }
            
            .btn-center {
                width: 100%;
                justify-content: center;
            }
        }
            </style>
    </head>
<body>
    @php
        $welcomeImageUrl = url('public/images/Welcomepage.png');
    @endphp
    <div class="welcome-container">
        <div class="welcome-image-wrapper">
            <!-- Background image using img tag -->
            <img src="{{ $welcomeImageUrl }}" alt="Welcome Background" class="welcome-bg-image" onerror="this.style.display='none';">
            
            <!-- Centered Design Element -->
            <div class="center-design">
                <div class="center-content">
                    <h2>Welcome to REDEMP</h2>
                    <p>Your comprehensive Medical Supplies & Pharmacy Management System</p>
                    
                    <div class="center-actions">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ route('cashier.dashboard') }}" class="btn btn-center btn-center-primary">
                                    <i class="bi bi-speedometer2"></i>
                                    Go to Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-center btn-center-primary">
                                    <i class="bi bi-box-arrow-in-right"></i>
                                    Login to System
                                </a>
                                
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="btn btn-center btn-center-outline">
                                        <i class="bi bi-person-plus"></i>
                                        Register
                                    </a>
                                @endif
                            @endauth
                        @endif
                </div>
                </div>
            </div>
        </div>
        </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    </body>
</html>
