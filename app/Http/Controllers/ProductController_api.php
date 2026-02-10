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
$query->whereRaw('COALESCE(v.sale_price, v.price) <= ?', [$maxPrice]); } // Sorting switch ($sortBy) { case 'price_asc'
    : $query->orderByRaw('COALESCE(v.sale_price, v.price) ASC');
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