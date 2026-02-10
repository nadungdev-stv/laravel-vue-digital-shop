@extends('layouts.app')

@section('title', config('app.name', 'Trang chủ'))

@section('content')
    <!-- Main Section with Sidebar Categories + Banners + Promo Banners -->
    <section class="py-4">
        <div class="container">
            <div class="row g-3 banner-row">
                <!-- Left Sidebar - Categories (only show on Desktop ≥992px) -->
                <div class="col-lg-2 d-none d-lg-block">
                    <div class="categories-sidebar">
                        <div class="list-group list-group-flush">
                            @php
                                $sidebarCategories = collect($categories)->take(6);
                            @endphp
                            @foreach ($sidebarCategories as $category)
                                @php
                                    $icon = $category->icon;
                                    $isFontAwesome = $icon &&
                                        (strpos($icon, 'fa-') === 0 ||
                                            strpos($icon, 'fas ') === 0 ||
                                            strpos($icon, 'far ') === 0 ||
                                            strpos($icon, 'fab ') === 0);
                                @endphp
                                <a href="{{ route('products.category', $category->slug) }}"
                                    class="list-group-item list-group-item-action d-flex align-items-center category-menu-item">
                                    @if ($isFontAwesome)
                                        <i class="{{ $icon }} category-icon-fallback me-3"></i>
                                    @elseif ($icon)
                                        <img src="{{ $icon }}" class="category-icon me-3" alt="{{ $category->name }}" loading="lazy"
                                            onerror="this.outerHTML='<i class=\'fas fa-folder category-icon-fallback me-3\'></i>'">
                                    @else
                                        <i class="fas fa-folder category-icon-fallback me-3"></i>
                                    @endif
                                    <span>{{ $category->name }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Center Column - Banner Carousel -->
                <div class="col-lg-7 col-md-9 col-12 carousel-col">
                    @if (!empty($banners) && count($banners) > 0)
                        <div id="bannerCarousel" class="carousel slide carousel-main" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                @foreach ($banners as $index => $banner)
                                    <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                        @if (!empty($banner->link))
                                            <a href="{{ $banner->link }}">
                                                <img src="{{ $banner->image }}" class="d-block w-100" alt="{{ $banner->title }}"
                                                    loading="{{ $index === 0 ? 'eager' : 'lazy' }}" {!! $index === 0 ? 'fetchpriority="high"' : '' !!}>
                                            </a>
                                        @else
                                            <img src="{{ $banner->image }}" class="d-block w-100" alt="{{ $banner->title }}"
                                                loading="{{ $index === 0 ? 'eager' : 'lazy' }}" {!! $index === 0 ? 'fetchpriority="high"' : '' !!}>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            @if (count($banners) > 1)
                                <!-- Navigation Buttons -->
                                <button class="carousel-control-prev" type="button" data-bs-target="#bannerCarousel"
                                    data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#bannerCarousel"
                                    data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Next</span>
                                </button>

                                <!-- Indicators -->
                                <div class="carousel-indicators" style="padding-bottom: 5px;">
                                    @foreach ($banners as $index => $banner)
                                        <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="{{ $index }}" @if ($index === 0) class="active" aria-current="true" @endif
                                            aria-label="Slide {{ $index + 1 }}"></button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @else
                        <!-- Fallback hero if no banners -->
                        <div class="p-5 mb-4 bg-light rounded-3 h-100 d-flex align-items-center">
                            <div class="container-fluid py-5">
                                <h1 class="display-6 fw-bold">Veyrix Shop</h1>
                                <p class="col-md-10 fs-5 mb-4">
                                    YouTube Premium, Netflix, Spotify, VPN, Canva… Giá tốt – kích hoạt nhanh – bảo hành trọn
                                    đời.
                                </p>
                                <a href="{{ route('products.index') }}" class="btn btn-primary btn-lg">Xem tất cả sản phẩm</a>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Right Column - Promo Banners -->
                <div class="col-12 col-md-3 col-lg-3 promo-banners-col">
                    <div class="promo-banners row g-2 g-md-0">
                        @foreach ($promoBanners as $index => $promo)
                            <div class="col-6 col-md-12 promo-banner-wrapper">
                                <a href="{{ $promo['link'] }}" class="promo-banner-item d-block">
                                    <img src="{{ $promo['image'] }}" class="w-100" alt="Promo {{ $index + 1 }}" loading="lazy">
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sản phẩm nổi bật -->
    @if (!empty($featuredProducts) && count($featuredProducts) > 0)
        <section class="py-5 bg-light">
            <div class="container">
                <div class="text-center mb-4">
                    <h2 class="featured-title d-inline-block">
                        <i class="fas fa-star"></i> Sản phẩm nổi bật
                    </h2>
                </div>
                <div class="row g-4">
                    @foreach ($featuredProducts as $product)
                        @php
                            $mainVariant = $product->variants->sortByDesc('is_main')->first();

                            if (!empty($mainVariant?->variant_title)) {
                                $displayTitle = $mainVariant->variant_title;
                            } elseif (!empty($mainVariant?->name)) {
                                $displayTitle = $product->name . ' ' . $mainVariant->name;
                            } else {
                                $displayTitle = $product->name;
                            }

                            if (!empty($mainVariant?->variant_image)) {
                                $displayImage = $mainVariant->variant_image;
                            } elseif ($product->gallery->isNotEmpty()) {
                                $displayImage = $product->gallery->first()->image_path;
                            } elseif (!empty($product->image)) {
                                $displayImage = $product->image;
                            } else {
                                $displayImage = '/public/images/placeholder-product.svg';
                            }

                            $price = $mainVariant?->sale_price ?? $mainVariant?->price ?? $product->sale_price ?? $product->price;
                        @endphp
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="card product-card h-100">
                                <a href="{{ route('products.show', $product->slug) }}" class="text-decoration-none text-dark">
                                    <img src="{{ $displayImage }}" alt="{{ $displayTitle }}" loading="lazy">
                                    <div class="card-body">
                                        <h5 class="card-title text-truncate mb-2">{{ $displayTitle }}</h5>
                                        <p class="card-text mb-1">
                                            <span class="fw-bold text-primary">
                                                {{ number_format($price, 0, ',', '.') }}đ
                                            </span>
                                        </p>
                                        @if (!empty($product->category?->name))
                                            <p class="small text-muted mb-0">
                                                <i class="fas fa-tag me-1"></i>{{ $product->category->name }}
                                            </p>
                                        @endif
                                    </div>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- Sản phẩm bán chạy -->
    @if (!empty($bestSellingProducts) && count($bestSellingProducts) > 0)
        <section class="py-5">
            <div class="container">
                <div class="text-center mb-4">
                    <h2 class="featured-title d-inline-block">
                        <i class="fas fa-fire"></i> Sản phẩm bán chạy
                    </h2>
                </div>
                <div class="row g-4">
                    @foreach ($bestSellingProducts as $product)
                        @php
                            $mainVariant = $product->variants->sortByDesc('is_main')->first();

                            if (!empty($mainVariant?->variant_title)) {
                                $displayTitle = $mainVariant->variant_title;
                            } elseif (!empty($mainVariant?->name)) {
                                $displayTitle = $product->name . ' ' . $mainVariant->name;
                            } else {
                                $displayTitle = $product->name;
                            }

                            if (!empty($mainVariant?->variant_image)) {
                                $displayImage = $mainVariant->variant_image;
                            } elseif ($product->gallery->isNotEmpty()) {
                                $displayImage = $product->gallery->first()->image_path;
                            } elseif (!empty($product->image)) {
                                $displayImage = $product->image;
                            } else {
                                $displayImage = '/public/images/placeholder-product.svg';
                            }

                            $price = $mainVariant?->sale_price ?? $mainVariant?->price ?? $product->sale_price ?? $product->price;
                        @endphp
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="card product-card h-100">
                                <a href="{{ route('products.show', $product->slug) }}" class="text-decoration-none text-dark">
                                    <img src="{{ $displayImage }}" alt="{{ $displayTitle }}" loading="lazy">
                                    <div class="card-body">
                                        <h5 class="card-title text-truncate mb-2">{{ $displayTitle }}</h5>
                                        <p class="card-text mb-1">
                                            <span class="fw-bold text-primary">
                                                {{ number_format($price, 0, ',', '.') }}đ
                                            </span>
                                        </p>
                                        @if (!empty($product->category?->name))
                                            <p class="small text-muted mb-0">
                                                <i class="fas fa-tag me-1"></i>{{ $product->category->name }}
                                            </p>
                                        @endif
                                    </div>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- Sản phẩm mới -->
    @if (!empty($newProducts) && count($newProducts) > 0)
        <section class="py-5 bg-light">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="featured-title mb-0">
                        <i class="fas fa-clock"></i> Sản phẩm mới
                    </h2>
                    <a href="{{ route('products.index') }}" class="btn btn-outline-primary btn-sm">
                        Xem tất cả
                    </a>
                </div>
                <div class="row g-4">
                    @foreach ($newProducts as $product)
                        @php
                            $mainVariant = $product->variants->sortByDesc('is_main')->first();

                            if (!empty($mainVariant?->variant_title)) {
                                $displayTitle = $mainVariant->variant_title;
                            } elseif (!empty($mainVariant?->name)) {
                                $displayTitle = $product->name . ' ' . $mainVariant->name;
                            } else {
                                $displayTitle = $product->name;
                            }

                            if (!empty($mainVariant?->variant_image)) {
                                $displayImage = $mainVariant->variant_image;
                            } elseif ($product->gallery->isNotEmpty()) {
                                $displayImage = $product->gallery->first()->image_path;
                            } elseif (!empty($product->image)) {
                                $displayImage = $product->image;
                            } else {
                                $displayImage = '/public/images/placeholder-product.svg';
                            }

                            $price = $mainVariant?->sale_price ?? $mainVariant?->price ?? $product->sale_price ?? $product->price;
                        @endphp
                        <div class="col-6 col-md-3 col-lg-3">
                            <div class="card product-card h-100">
                                <a href="{{ route('products.show', $product->slug) }}" class="text-decoration-none text-dark">
                                    <img src="{{ $displayImage }}" alt="{{ $displayTitle }}" loading="lazy">
                                    <div class="card-body">
                                        <h5 class="card-title text-truncate mb-2">{{ $displayTitle }}</h5>
                                        <p class="card-text mb-1">
                                            <span class="fw-bold text-primary">
                                                {{ number_format($price, 0, ',', '.') }}đ
                                            </span>
                                        </p>
                                        @if (!empty($product->category?->name))
                                            <p class="small text-muted mb-0">
                                                <i class="fas fa-tag me-1"></i>{{ $product->category->name }}
                                            </p>
                                        @endif
                                    </div>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection