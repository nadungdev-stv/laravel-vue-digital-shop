@extends('layouts.app')

@section('title', 'Giỏ hàng')

@section('content')
    <div class="container mt-5 mb-5">
        <h1 class="fw-bold mb-4">Giỏ hàng của bạn</h1>
        <div class="alert alert-info">
            Chức năng giỏ hàng đang được cập nhật.
        </div>
        <div class="text-center py-5">
            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
            <p>Giỏ hàng trống</p>
            <a href="{{ route('products.index') }}" class="btn btn-primary">Tiếp tục mua sắm</a>
        </div>
    </div>
@endsection