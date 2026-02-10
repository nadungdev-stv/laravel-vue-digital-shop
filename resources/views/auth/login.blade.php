@extends('layouts.app')

@section('title', 'Đăng nhập')

@section('content')
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold">Đăng nhập</h2>
                            <p class="text-muted">Chào mừng bạn trở lại</p>
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('login.post') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Tên đăng nhập / Email</label>
                                <input type="text" name="username" class="form-control rounded-3"
                                    value="{{ old('username') }}" required autofocus>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Mật khẩu</label>
                                <input type="password" name="password" class="form-control rounded-3" required>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 fw-bold py-2 rounded-3">
                                Đăng nhập
                            </button>

                            <div class="text-center mt-3">
                                <a href="#" class="text-muted text-decoration-none small">Quên mật khẩu?</a>
                            </div>

                            <div class="text-center mt-4 pt-3 border-top">
                                <p class="mb-0 text-muted">Chưa có tài khoản? <a href="{{ route('register') }}"
                                        class="text-primary text-decoration-none fw-bold">Đăng ký ngay</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection