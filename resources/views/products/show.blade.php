@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <div class="container mt-5 mb-5">
        <div class="row">
            <!-- Product Image -->
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm">
                    <img src="{{ $product->image ? asset($product->image) : asset('images/placeholder-product.svg') }}"
                        class="img-fluid rounded" alt="{{ $product->name }}">
                </div>

                @if($product->gallery->count() > 0)
                    <div class="row mt-3 g-2">
                        @foreach($product->gallery as $image)
                            <div class="col-3">
                                <img src="{{ asset($image->image_path) }}" class="img-thumbnail" style="cursor: pointer;">
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Product Details -->
            <div class="col-md-6">
                <h1 class="fw-bold mb-3">{{ $product->name }}</h1>

                <div class="mb-3">
                    @if($product->sale_price)
                        <h3 class="text-danger fw-bold d-inline me-2">{{ number_format($product->sale_price, 0, ',', '.') }}đ
                        </h3>
                        <span
                            class="text-muted text-decoration-line-through">{{ number_format($product->price, 0, ',', '.') }}đ</span>
                    @else
                        <h3 class="text-primary fw-bold">{{ number_format($product->price, 0, ',', '.') }}đ</h3>
                    @endif
                </div>

                <div class="mb-4">
                    {!! $product->description !!}
                </div>

                <form action="{{ route('cart.add') }}" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                    @if($product->variants->count() > 0)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Chọn loại:</label>
                            <select class="form-select" name="variant_id">
                                @foreach($product->variants as $variant)
                                    <option value="{{ $variant->id }}">
                                        {{ $variant->variant_title }} -
                                        {{ number_format($variant->sale_price ?? $variant->price, 0, ',', '.') }}đ
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-bold">Số lượng:</label>
                        <input type="number" name="quantity" class="form-control" value="1" min="1" style="width: 100px;">
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold">
                            <i class="fas fa-cart-plus me-2"></i> Thêm vào giỏ hàng
                        </button>
                    </div>
                </form>

                <div class="mt-4 pt-4 border-top">
                    <h5>Mô tả chi tiết</h5>
                    <div class="product-content">
                        {!! $product->content !!}
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        @if($relatedProducts->count() > 0)
            <div class="mt-5">
                <h3 class="fw-bold mb-4">Sản phẩm liên quan</h3>
                <div class="row row-cols-1 row-cols-md-4 g-4">
                    @foreach($relatedProducts as $related)
                        <div class="col">
                            <div class="card h-100 shadow-sm border-0">
                                <a href="{{ route('products.show', $related->slug) }}">
                                    <img src="{{ $related->image ? asset($related->image) : asset('images/placeholder-product.svg') }}"
                                        class="card-img-top" alt="{{ $related->name }}" style="height: 150px; object-fit: cover;">
                                </a>
                                <div class="card-body">
                                    <h6 class="card-title text-truncate">
                                        <a href="{{ route('products.show', $related->slug) }}"
                                            class="text-decoration-none text-dark">
                                            {{ $related->name }}
                                        </a>
                                    </h6>
                                    <p class="card-text fw-bold text-primary">
                                        {{ number_format($related->sale_price ?? $related->price, 0, ',', '.') }}đ</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection