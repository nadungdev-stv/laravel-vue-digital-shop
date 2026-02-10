@extends('layouts.app')

@section('title', isset($category) ? $category->name : 'Tất cả sản phẩm')

@section('content')
    <div class="container mt-4 mb-5">
        <div class="row">
            <div class="col-12 mb-4">
                <h2 class="fw-bold">{{ isset($category) ? $category->name : 'Tất cả sản phẩm' }}</h2>
                @if(isset($category) && $category->description)
                    <p class="text-muted">{{ $category->description }}</p>
                @endif
            </div>
        </div>

        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
            @forelse($products as $product)
                <div class="col">
                    <div class="card h-100 shadow-sm border-0">
                        <img src="{{ $product->image ? asset($product->image) : asset('images/placeholder-product.svg') }}"
                            class="card-img-top" alt="{{ $product->name }}" style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title text-truncate">{{ $product->name }}</h5>
                            <p class="card-text fw-bold text-primary">
                                {{ number_format($product->sale_price ?? $product->price, 0, ',', '.') }}đ</p>
                        </div>
                        <div class="card-footer bg-transparent border-0 pb-3">
                            <a href="{{ route('products.show', $product->slug) }}" class="btn btn-outline-primary w-100">Xem chi
                                tiết</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info">Chưa có sản phẩm nào.</div>
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $products->links() }}
        </div>
    </div>
@endsection