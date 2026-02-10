<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\Wishlist;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Get query parameters
        $searchQuery = $request->input('q', '');
        $categorySlug = $request->input('category', '');
        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');
        $sortBy = $request->input('sort', 'default');
        $perPage = 12;

        // Get all categories with product count
        $categories = Category::withCount([
            'products' => function ($query) {
                $query->where('status', 'active');
            }
        ])
            ->where('status', 'active')
            ->having('products_count', '>', 0)
            ->orderBy('sort_order')
            ->get();

        // Get price range for slider
        $priceRange = ProductVariant::selectRaw('MIN(COALESCE(sale_price, price)) as min_price, MAX(COALESCE(sale_price, price)) as max_price')
            ->whereHas('product', function ($query) {
                $query->where('status', 'active');
            })
            ->first();

        $priceRangeMin = (int) ($priceRange?->min_price ?? 0);
        $priceRangeMax = (int) ($priceRange?->max_price ?? 1000000);

        // Base query
        $query = DB::table('products as p')
            ->join('product_variants as v', 'p.id', '=', 'v.product_id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->where('p.status', 'active')
            ->where('v.status', 'active')
            ->select(
                'p.id',
                'p.name',
                'p.slug',
                'p.image',
                'p.description',
                'p.sort_order',
                'p.created_at',
                'p.category_id',
                'c.name as category_name',
                'v.id as variant_id',
                'v.name as variant_name', // e.g. "1 tháng"
                'v.slug as variant_slug',
                'v.variant_title', // e.g. "YouTube Premium 1 tháng"
                'v.variant_image',
                'v.price as variant_price',
                'v.sale_price as variant_sale_price',
                'v.stock_quantity as variant_stock',
                'v.is_main',
                DB::raw('(SELECT image_path FROM product_gallery WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) as first_gallery_image')
            );

        // If searching, we want to find ANY variant that matches.
        // If NOT searching, typically we show only main variants (canonical products).
        if (empty($searchQuery)) {
            $query->where('v.is_main', true);
        }

        // Filter by category
        if (!empty($categorySlug) && $categorySlug !== 'all') {
            $query->where('c.slug', $categorySlug);
        }

        // Filter by search query
        if (!empty($searchQuery)) {
            $term = '%' . $searchQuery . '%';
            $query->where(function ($q) use ($term, $searchQuery) {
                // Match Method 1: Product Name
                $q->where('p.name', 'LIKE', $term)
                    // Match Method 2: Variant Title (Full combined name)
                    ->orWhere('v.variant_title', 'LIKE', $term)
                    // Match Method 3: Variant Name (Attribute)
                    ->orWhere('v.name', 'LIKE', $term)
                    // Match Method 4: Description
                    ->orWhere('p.description', 'LIKE', $term);
            });
        }

        // Filter by price range
        if ($minPrice !== null) {
            $query->whereRaw('COALESCE(v.sale_price, v.price) >= ?', [$minPrice]);
        }
        if ($maxPrice !== null) {
            $query->whereRaw('COALESCE(v.sale_price, v.price) <= ?', [$maxPrice]);
        }

        // Sorting
        switch ($sortBy) {
            case 'price_asc':
                $query->orderByRaw('COALESCE(v.sale_price, v.price) ASC');
                break;
            case 'price_desc':
                $query->orderByRaw('COALESCE(v.sale_price, v.price) DESC');
                break;
            case 'name_asc':
                // Sort by product name for consistent A-Z order
                $query->orderBy('p.name', 'ASC');
                break;
            case 'name_desc':
                $query->orderBy('p.name', 'DESC');
                break;
            case 'newest':
                $query->orderBy('p.created_at', 'DESC');
                break;
            default:
                if (!empty($searchQuery)) {
                    // Relevance sorting for search
                    // 1. Exact Name/Title Match (Highest Priority)
                    // 2. Starts With Query
                    // 3. Contains Query
                    // 4. Default Sort Order
                    $escapedQuery = str_replace("'", "''", $searchQuery);

                    $query->orderByRaw("
                        CASE 
                            WHEN p.name = '$escapedQuery' OR v.variant_title = '$escapedQuery' THEN 1
                            WHEN p.name LIKE '$escapedQuery%' OR v.variant_title LIKE '$escapedQuery%' THEN 2
                            WHEN p.name LIKE '%$escapedQuery%' OR v.variant_title LIKE '%$escapedQuery%' THEN 3
                            ELSE 4
                        END ASC
                    ");
                }

                $query->orderBy('p.sort_order', 'ASC')
                    ->orderBy('v.is_main', 'DESC') // Main variants first if tie
                    ->orderBy('p.created_at', 'DESC');
        }

        $products = $query->paginate($perPage);

        // Helper to clean paths (same as in show method)
        $cleanPath = function ($path) {
            if (!$path)
                return null;
            $path = str_replace('/public/', '/', $path);
            if (str_starts_with($path, 'public/')) {
                $path = '/' . substr($path, 7);
            }
            if (!str_starts_with($path, '/') && !str_starts_with($path, 'http')) {
                $path = '/' . $path;
            }
            return $path;
        };

        // Transform the raw DB results into the structure expected by the frontend
        $products->through(function ($item) use ($cleanPath) {
            // Fetch gallery image if needed (though simplified via raw DB query to avoid N+1 is hard without Eloquent, 
            // but for search results, the variant image or product image is usually primary).
            // We'll trust variant_image or p.image. If we really need gallery, we'd need a separate query or join.
            // For performance, let's Stick to main images for now.

            return [
                'id' => $item->id,
                'name' => $item->name,
                'slug' => $item->slug,
                'image' => $cleanPath($item->image),
                // 'price' => $item->price, // Not selected from products to avoid confusion
                // 'sale_price' => $item->sale_price,
                // 'stock_quantity' => $item->stock_quantity,
                'category_name' => $item->category_name,

                // Important: Frontend logic uses these variant fields for display
                'variant_id' => $item->variant_id,
                'variant_name' => $item->variant_name,
                'variant_slug' => $item->variant_slug,
                'variant_title' => $item->variant_title,
                'variant_image' => $cleanPath($item->variant_image),
                'variant_price' => $item->variant_price,
                'variant_sale_price' => $item->variant_sale_price,
                'variant_stock' => $item->variant_stock,

                'first_gallery_image' => $cleanPath($item->first_gallery_image),
            ];
        });

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => [
                'search' => $searchQuery,
                'category' => $categorySlug,
                'minPrice' => $minPrice,
                'maxPrice' => $maxPrice,
                'sort' => $sortBy,
            ],
            'priceRange' => [
                'min' => $priceRangeMin,
                'max' => $priceRangeMax,
            ],
        ]);
    }

    public function show($slug)
    {
        // Try to find product by slug first
        $product = Product::with([
            'category',
            'variants',
            'gallery',
            'reviews' => function ($q) {
                $q->where('status', 'approved')->with('user:id,full_name,username')->latest();
            }
        ])
            ->where('slug', $slug)
            ->where('status', 'active')
            ->first();

        $variant = null;

        // If not found in products, try to find in variants
        if (!$product) {
            $variant = ProductVariant::with([
                'product.category',
                'product.variants',
                'product.gallery',
                'product.reviews' => function ($q) {
                    $q->where('status', 'approved')->with('user:id,full_name,username')->latest();
                }
            ])
                ->where('slug', $slug)
                ->where('status', 'active')
                ->first();

            if ($variant) {
                $product = $variant->product;
            }
        }

        if (!$product) {
            return Inertia::render('Error', ['status' => 404])->toResponse(request())->setStatusCode(404);
        }

        // If accessing by product slug (not variant slug), redirect to primary variant
        if (!$variant && $product) {
            $primaryVariant = $product->variants()
                ->where('status', 'active')
                ->where('is_main', true)
                ->first();

            if ($primaryVariant) {
                return redirect('/' . $primaryVariant->slug, 301);
            }

            // If no primary variant, try to get any active variant
            $anyVariant = $product->variants()
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->first();

            if ($anyVariant) {
                return redirect('/' . $anyVariant->slug, 301);
            }
        }

        // Increment views
        $product->increment('views');

        // Get all variants
        $variants = $product->variants()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($v) {
                return [
                    'id' => $v->id,
                    'name' => $v->name,
                    'slug' => $v->slug,
                    'price' => $v->price,
                    'sale_price' => $v->sale_price,
                    'stock_quantity' => $v->stock_quantity,
                    'duration' => $v->duration,
                    'variant_title' => $v->variant_title,
                    'variant_image' => $v->variant_image,
                    'delivery_type' => $v->delivery_type,
                    'is_main' => $v->is_main,
                ];
            });

        // Get gallery images
        $galleryImages = $product->gallery()
            ->orderBy('sort_order')
            ->get()
            ->map(function ($img) {
                return $img->image_path;
            });

        // Determine display data based on variant or product
        $displayData = [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'image' => $product->image,
            'description' => $product->description,
            'price' => $product->price,
            'sale_price' => $product->sale_price,
            'stock_quantity' => $product->stock_quantity,
            'status' => $product->status,
            'duration' => $product->duration,
            'delivery_type' => $product->delivery_type,
            'category_name' => $product->category?->name ?? '',
            'category_slug' => $product->category->slug ?? '',
            'variant_id' => null,
            'current_slug' => $slug,
            'is_wishlisted' => false,
        ];

        if (Auth::check()) {
            $displayData['is_wishlisted'] = Wishlist::where('user_id', Auth::id())
                ->where('product_id', $product->id)
                ->exists();
        }

        // Override with variant data if viewing a variant
        if ($variant) {
            $displayData['variant_id'] = $variant->id;
            $displayData['price'] = $variant->price;
            $displayData['sale_price'] = $variant->sale_price;
            $displayData['stock_quantity'] = $variant->stock_quantity;

            if ($variant->duration) {
                $displayData['duration'] = $variant->duration;
            }

            if ($variant->variant_title) {
                $displayData['name'] = $variant->variant_title;
            } else {
                $displayData['name'] = $product->name . ' - ' . $variant->name;
            }

            if ($variant->variant_image) {
                $displayData['image'] = $variant->variant_image;
            }

            if ($variant->delivery_type) {
                $displayData['delivery_type'] = $variant->delivery_type;
            }
        }

        // Prepare display images for carousel
        $displayImages = [];
        if ($variant && $variant->variant_image) {
            $displayImages[] = $variant->variant_image;
        }
        $displayImages = array_merge($displayImages, $galleryImages->toArray());
        if (empty($displayImages) && $product->image) {
            $displayImages[] = $product->image;
        }
        if (empty($displayImages)) {
            $displayImages[] = '/images/placeholder-product.svg';
        }

        // Get related products
        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('status', 'active')
            ->take(8)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'image' => $p->image,
                    'price' => $p->price,
                    'sale_price' => $p->sale_price,
                ];
            });

        // Categories for tab navigation
        $categories = DB::select("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order");

        // Products for "Khám phá sản phẩm" tab section
        $cleanPath = function ($path) {
            if (!$path)
                return $path;
            $path = str_replace('/public/', '/', $path);
            if (str_starts_with($path, 'public/')) {
                $path = '/' . substr($path, 7);
            }
            return $path;
        };

        $allTabProducts = DB::select("
            SELECT p.*, c.name as category_name,
                v.id as variant_id, v.name as variant_name, v.variant_title, v.variant_image,
                v.price as variant_price, v.sale_price as variant_sale_price,
                v.slug as variant_slug, v.stock_quantity as variant_stock,
                (SELECT image_path FROM product_gallery WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) as first_gallery_image
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_variants v ON v.id = (
                SELECT id FROM product_variants
                WHERE product_id = p.id
                ORDER BY is_main DESC, id ASC
                LIMIT 1
            )
            WHERE p.status = 'active'
            ORDER BY p.featured DESC, p.created_at DESC
            LIMIT 50
        ");

        foreach ($allTabProducts as $p) {
            $p->image = $cleanPath($p->image);
            $p->variant_image = $cleanPath($p->variant_image);
            $p->first_gallery_image = $cleanPath($p->first_gallery_image);
        }

        return Inertia::render('Products/Show', [
            'product' => $displayData,
            'variants' => $variants,
            'reviews' => $product->reviews,
            'displayImages' => $displayImages,
            'relatedProducts' => $relatedProducts,
            'categories' => $categories,
            'allTabProducts' => $allTabProducts,
        ]);
    }

    public function category($slug)
    {
        $category = Category::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        $products = Product::where('category_id', $category->id)
            ->where('status', 'active')
            ->latest()
            ->paginate(12);

        return view('products.index', compact('category', 'products'));
    }

    /**
     * API endpoint for infinite scroll / load more (returns JSON only)
     */
    public function apiIndex(Request $request)
    {
        // Get query parameters
        $searchQuery = $request->input('q', '');
        $categorySlug = $request->input('category', '');
        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');
        $sortBy = $request->input('sort', 'default');
        $perPage = 12;

        // Build products query - join with main variant
        $query = Product::with(['category', 'gallery'])
            ->join('product_variants as v', function ($join) {
                $join->on('products.id', '=', 'v.product_id')
                    ->where('v.is_main', true)
                    ->where('v.status', 'active');
            })
            ->select(
                'products.*',
                'v.id as variant_id',
                'v.name as variant_name',
                'v.slug as variant_slug',
                'v.variant_title',
                'v.variant_image',
                'v.price as variant_price',
                'v.sale_price as variant_sale_price',
                'v.stock_quantity as variant_stock'
            )
            ->where('products.status', 'active');

        // Filter by category
        if (!empty($categorySlug) && $categorySlug !== 'all') {
            $category = Category::where('slug', $categorySlug)->where('status', 'active')->first();
            if ($category) {
                $query->where('products.category_id', $category->id);
            }
        }

        // Filter by search query
        if (!empty($searchQuery)) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('products.name', 'LIKE', "%{$searchQuery}%")
                    ->orWhere('products.description', 'LIKE', "%{$searchQuery}%");
            });
        }

        // Filter by price range
        if ($minPrice !== null) {
            $query->whereRaw('COALESCE(v.sale_price, v.price) >= ?', [$minPrice]);
        }
        if ($maxPrice !== null) {
            $query->whereRaw('COALESCE(v.sale_price, v.price) <= ?', [$maxPrice]);
        }

        // Sorting
        switch ($sortBy) {
            case 'price_asc':
                $query->orderByRaw('COALESCE(v.sale_price, v.price) ASC');
                break;
            case 'price_desc':
                $query->orderByRaw('COALESCE(v.sale_price, v.price) DESC');
                break;
            case 'name_asc':
                $query->orderBy('products.name', 'ASC');
                break;
            case 'name_desc':
                $query->orderBy('products.name', 'DESC');
                break;
            case 'newest':
                $query->orderBy('products.created_at', 'DESC');
                break;
            default:
                $query->orderBy('products.sort_order', 'ASC')
                    ->orderBy('products.created_at', 'DESC');
        }

        $products = $query->paginate($perPage)->through(function ($product) {
            // Get first gallery image
            $firstGalleryImage = $product->gallery->first()?->image_path ?? null;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'image' => $product->image,
                'price' => $product->price,
                'sale_price' => $product->sale_price,
                'stock_quantity' => $product->stock_quantity,
                'category_name' => $product->category?->name ?? '',
                'variant_id' => $product->variant_id,
                'variant_name' => $product->variant_name,
                'variant_slug' => $product->variant_slug,
                'variant_title' => $product->variant_title,
                'variant_image' => $product->variant_image,
                'variant_price' => $product->variant_price,
                'variant_sale_price' => $product->variant_sale_price,
                'variant_stock' => $product->variant_stock,
                'first_gallery_image' => $firstGalleryImage,
            ];
        });

        return response()->json($products);
    }

    /**
     * Search API for header autocomplete
     */
    public function search(Request $request)
    {
        // Popular products
        if ($request->has('popular')) {
            $products = DB::select("
                SELECT id, name, slug, price, sale_price, image, stock_quantity, sold_count
                FROM products
                WHERE status = 'active'
                ORDER BY sold_count DESC, created_at DESC
                LIMIT 10
            ");

            return response()->json([
                'success' => true,
                'products' => $products,
            ]);
        }

        $query = trim($request->input('q', ''));

        if (empty($query)) {
            return response()->json([
                'success' => false,
                'products' => [],
            ]);
        }

        // Split query into keywords
        $keywords = preg_split('/\s+/', trim($query));
        $keywords = array_filter($keywords);

        // Build conditions for each keyword
        $nameConditions = [];
        $descConditions = [];
        $params = [];

        foreach ($keywords as $keyword) {
            $nameConditions[] = "LOWER(name) LIKE LOWER(?)";
            $descConditions[] = "LOWER(description) LIKE LOWER(?)";
            $params[] = '%' . $keyword . '%';
            $params[] = '%' . $keyword . '%';
        }

        $nameSQL = implode(' OR ', $nameConditions);
        $descSQL = implode(' OR ', $descConditions);

        // Build keyword match count for relevancy scoring
        $keywordMatchCases = [];
        $keywordMatchParams = [];
        foreach ($keywords as $keyword) {
            $keywordMatchCases[] = "CASE WHEN LOWER(name) LIKE LOWER(?) THEN 1 ELSE 0 END";
            $keywordMatchParams[] = '%' . $keyword . '%';
        }
        $keywordMatchSQL = implode(' + ', $keywordMatchCases);

        $sql = "SELECT id, name, slug, price, sale_price, image, stock_quantity, sold_count,
                ({$keywordMatchSQL}) as keyword_match_count,
                CASE
                    WHEN LOWER(name) = LOWER(?) THEN 1
                    WHEN LOWER(name) LIKE LOWER(?) THEN 2
                    WHEN LOWER(name) LIKE LOWER(?) THEN 3
                    ELSE 4
                END as position_score
                FROM products
                WHERE status = 'active'
                AND (
                    ({$nameSQL}) OR
                    ({$descSQL})
                )
                ORDER BY
                    keyword_match_count DESC,
                    position_score ASC,
                    sold_count DESC,
                    stock_quantity DESC,
                    created_at DESC
                LIMIT 10";

        $allParams = array_merge(
            $keywordMatchParams,
            [$query, $query . '%', '%' . $query . '%'],
            $params
        );

        $products = DB::select($sql, $allParams);

        // Fallback: try without Vietnamese accents if no results
        if (empty($products)) {
            $queryNoAccent = $this->removeVietnameseAccents($query);
            if ($queryNoAccent !== $query) {
                $products = DB::select("
                    SELECT id, name, slug, price, sale_price, image, stock_quantity, sold_count
                    FROM products
                    WHERE status = 'active'
                    AND (
                        LOWER(name) LIKE LOWER(?) OR
                        LOWER(description) LIKE LOWER(?)
                    )
                    ORDER BY sold_count DESC, created_at DESC
                    LIMIT 10
                ", ['%' . $queryNoAccent . '%', '%' . $queryNoAccent . '%']);
            }
        }

        return response()->json([
            'success' => true,
            'products' => $products,
            'query' => $query,
            'count' => count($products),
        ]);
    }

    private function removeVietnameseAccents(string $str): string
    {
        $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", 'a', $str);
        $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", 'e', $str);
        $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", 'i', $str);
        $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", 'o', $str);
        $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", 'u', $str);
        $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", 'y', $str);
        $str = preg_replace("/(đ)/", 'd', $str);
        $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", 'A', $str);
        $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", 'E', $str);
        $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", 'I', $str);
        $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", 'O', $str);
        $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", 'U', $str);
        $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", 'Y', $str);
        $str = preg_replace("/(Đ)/", 'D', $str);

        return $str;
    }
}
