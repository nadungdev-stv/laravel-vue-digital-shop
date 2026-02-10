<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>@yield('title', config('app.name', 'Veyrix Shop'))</title>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('images/logo.ico') }}">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v=9">

    <style>
        /* Smart Sticky Header */
        body {
            padding-top: 61px;
        }

        .navbar.fixed-top {
            transition: transform 0.3s ease-in-out;
        }
    </style>
    @stack('styles')
</head>

<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top"
        style="background: linear-gradient(135deg, #667eea 0%, #7387df 100%); box-shadow: 0 2px 10px rgba(0,0,0,0.1); height: 61px;">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand fw-bold d-flex align-items-center" href="{{ url('/') }}"
                style="font-size: 1.4rem; letter-spacing: 0.5px; gap: 8px;">
                <span style="line-height: 1;">{{ config('app.name', 'Veyrix') }}</span>
            </a>

            <button class="navbar-toggler border-0 p-2" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Search Bar -->
                <div class="flex-grow-1 mx-3 position-relative" style="max-width: 500px;">
                    <div class="input-group search-bar">
                        <span class="input-group-text border-0 bg-white ps-3">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-0" placeholder="Bạn đang tìm kiếm gì...">
                    </div>
                </div>

                <ul class="navbar-nav align-items-center ms-auto">
                    <!-- Cart -->
                    <li class="nav-item me-2">
                        <a class="nav-link position-relative px-2" href="{{ url('/cart') }}">
                            <div class="icon-circle">
                                <i class="fas fa-shopping-cart"></i>
                                <!-- TODO: Cart Count -->
                            </div>
                        </a>
                    </li>

                    @guest
                        <li class="nav-item">
                            <a class="nav-link px-2" href="{{ route('login') }}">
                                <div class="auth-button auth-single-btn">
                                    <i class="far fa-user-circle"></i>
                                    <span>Đăng nhập</span>
                                </div>
                            </a>
                        </li>
                    @else
                        <!-- User Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link position-relative px-2" href="#" role="button" data-bs-toggle="dropdown">
                                <div class="icon-circle">
                                    <i class="far fa-user-circle"></i>
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end user-dropdown shadow-lg">
                                <li class="user-dropdown-header">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1 ms-3">
                                            <div class="fw-bold">{{ Auth::user()->username }}</div>
                                            <div class="small text-muted">{{ Auth::user()->email }}</div>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="dropdown-item d-flex align-items-center text-danger">
                                            <i class="fas fa-power-off me-3"></i> Đăng xuất
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="mt-5" style="background: rgb(245, 245, 247); border-top: 1px solid #e0e0e0;">
        <div class="container py-5">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5 class="fw-bold mb-3">{{ config('app.name', 'Veyrix Shop') }}</h5>
                    <p class="text-muted">Chuyên cung cấp các tài khoản premium chất lượng cao.</p>
                </div>
                <div class="col-lg-2">
                    <h6 class="fw-bold mb-3">Liên kết</h6>
                    <ul class="list-unstyled">
                        <li><a href="{{ url('/') }}" class="text-muted text-decoration-none">Trang chủ</a></li>
                        <li><a href="{{ url('/products') }}" class="text-muted text-decoration-none">Sản phẩm</a></li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h6 class="fw-bold mb-3">Hỗ trợ</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-muted text-decoration-none">Hướng dẫn</a></li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h6 class="fw-bold mb-3">Liên hệ</h6>
                    <p class="text-muted">Email: admin@veyrix.pro</p>
                </div>
            </div>
            <div class="row mt-4 pt-4 border-top">
                <div class="col-12 text-center text-muted">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/main.js') }}"></script>
    @stack('scripts')
</body>

</html>