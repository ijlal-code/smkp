<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISO Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bs-primary: #000000;      /* Hitam Utama */
            --bs-secondary: #1a1a1a;    /* Hitam Header */
            --bs-danger: #d60000;       /* Merah Aksen */
            --bs-warning: #ffc107;      /* Kuning/Emas Folder */
            --bs-body-bg: #f4f6f9;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bs-body-bg);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navbar Styling */
        .navbar {
            background: linear-gradient(to right, #000000, #222222);
            border-bottom: 3px solid var(--bs-danger);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        /* Content Wrapper */
        .main-content {
            flex: 1;
            padding-top: 2rem;
            padding-bottom: 3rem;
        }

        /* Footer Styling */
        .footer {
            background-color: #000;
            color: #aaa;
            padding: 1.5rem 0;
            border-top: 1px solid #333;
            margin-top: auto;
        }

        /* Custom Buttons */
        .btn-black { background: #000; color: #fff; border: 1px solid #333; }
        .btn-black:hover { background: #333; color: #fff; border-color: #555; }
        
        .btn-red { background: var(--bs-danger); color: #fff; border: none; }
        .btn-red:hover { background: #b30000; color: #fff; }

        /* Icon Colors */
        .icon-folder { color: var(--bs-warning); text-shadow: 0 2px 3px rgba(0,0,0,0.1); }
        .icon-pdf { color: #dc3545; }
        .icon-word { color: #0d6efd; }
        .icon-excel { color: #198754; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark shadow">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('smkp.index') }}">
                <i class="bi bi-shield-check text-danger fs-3"></i>
                <div class="d-flex flex-column lh-1">
                    <span class="text-white">ISO Management</span>
                   
                </div>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                    @auth
                        <li class="nav-item">
                            <span class="nav-link text-white">
                                <i class="bi bi-person-circle me-1"></i> {{ Auth::user()->name }} 
                                <span class="badge bg-danger ms-1" style="font-size: 0.6rem;">{{ Auth::user()->role }}</span>
                            </span>
                        </li>
                        <li class="nav-item ms-lg-2">
                            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-light">Logout</button>
                            </form>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Login</a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container">
            @yield('content')
        </div>
    </div>

    <footer class="footer text-center">
        <div class="container">
            <small>&copy; {{ date('Y') }} ISO Management. All Rights Reserved.</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>