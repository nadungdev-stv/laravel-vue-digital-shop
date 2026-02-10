<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Clear homepage cache when products are modified
     */
    private function clearProductCache(): void
    {
        Cache::forget('homepage_data');
    }
    public function index(Request $request)
    {
        $search = $request->search ?? '';
        $categoryId = $request->category ?? null;
        $sortBy = $request->sort ?? 'created_at';
        $sortOrder = $request->order ?? 'DESC';

        // Whitelist allowed sort columns
        $allowedSortColumns = ['id', 'name', 'price', 'delivery_type', 'stock_quantity', 'sold_count', 'status', 'featured', 'created_at', 'category_name'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'created_at';
        }

        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $query = Product::query()
            ->leftJoin('categories as c', 'products.category_id', '=', 'c.id')
            ->select('products.*', 'c.name as category_name')
            ->addSelect([
                'variant_count' => function ($q) {
                    $q->selectRaw('count(*)')
                        ->from('product_variants')
                        ->whereColumn('product_id', 'products.id');
                }
            ])
            ->addSelect([
                'first_gallery_image' => function ($q) {
                    $q->select('image_path')
                        ->from('product_gallery')
                        ->whereColumn('product_id', 'products.id')
                        ->orderBy('sort_order', 'asc')
                        ->limit(1);
                }
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', '%' . $search . '%')
                    ->orWhere('products.description', 'like', '%' . $search . '%');
            });
        }

        if ($categoryId) {
            $query->where('products.category_id', $categoryId);
        }

        // Handle sorting
        if ($sortBy === 'category_name') {
            $query->orderBy('c.name', $sortOrder);
        } else {
            $query->orderBy('products.' . $sortBy, $sortOrder);
        }

        $products = $query->paginate(20)->withQueryString();

        $totalProducts = Product::count();

        $categories = Category::orderBy('sort_order')->get();

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category', 'sort', 'order']),
            'totalProducts' => $totalProducts,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Products/Create', [
            'categories' => Category::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'slug' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable',
            'content' => 'nullable',
            'tags' => 'nullable|string',
            'features' => 'nullable|string',
            'featured' => 'boolean',
            'image' => 'nullable|image|max:2048',
            'delivery_type' => 'required|in:account,email_only,customer_account',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|max:5120',
        ]);

        // Handle slug: use provided or auto-generate
        if (!isset($validated['slug']) || trim($validated['slug']) === '') {
            $validated['slug'] = Str::slug($validated['name']);
        } else {
            $validated['slug'] = Str::slug($validated['slug']);
        }

        // Handle category_ids - set first as primary category_id
        if (!empty($request->input('category_ids'))) {
            $validated['category_id'] = $request->input('category_ids')[0];
        }
        unset($validated['category_ids']);
        unset($validated['gallery_images']);

        // Use ImageService for optimized image storage
        $imageService = app(ImageService::class);

        if ($request->hasFile('image')) {
            $validated['image'] = $imageService->storeOptimized(
                $request->file('image'),
                'products',
                ['max_width' => 800, 'max_height' => 800, 'quality' => 85]
            );
        }

        $product = Product::create($validated);

        // Sync categories if provided
        if ($request->has('category_ids') && !empty($request->input('category_ids'))) {
            $product->categories()->sync($request->input('category_ids'));
        }

        // Handle gallery images with optimization
        if ($request->hasFile('gallery_images')) {
            $files = $request->file('gallery_images');
            if (!is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $index => $file) {
                $path = $imageService->storeOptimized(
                    $file,
                    'gallery',
                    ['max_width' => 1200, 'max_height' => 1200, 'quality' => 85]
                );
                if ($path) {
                    $product->gallery()->create([
                        'image_path' => $path,
                        'sort_order' => $index
                    ]);
                }
            }
        } else {
            // Try indexed format (gallery_images.0, gallery_images.1, etc.)
            $uploadedCount = 0;
            for ($i = 0; $i < 50; $i++) {
                if ($request->hasFile("gallery_images.$i")) {
                    $file = $request->file("gallery_images.$i");
                    $path = $imageService->storeOptimized(
                        $file,
                        'gallery',
                        ['max_width' => 1200, 'max_height' => 1200, 'quality' => 85]
                    );
                    if ($path) {
                        $product->gallery()->create([
                            'image_path' => $path,
                            'sort_order' => $uploadedCount
                        ]);
                        $uploadedCount++;
                    }
                }
            }
        }

        $this->clearProductCache();
        return redirect()->route('admin.products.edit', $product)->with('success', 'Sản phẩm đã được tạo thành công.');
    }

    public function edit(Product $product)
    {
        $product->load([
            'variants' => function ($q) {
                $q->orderBy('sort_order', 'asc');
            },
            'gallery',
            'categories'
        ]); // Load relationships

        return Inertia::render('Admin/Products/Edit', [
            'product' => $product,
            'categories' => Category::all(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            // 'category_id' => 'required|exists:categories,id', // Replaced by category_ids
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:categories,id',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable',
            'content' => 'nullable',
            'tags' => 'nullable|string',
            'features' => 'nullable|string',
            'featured' => 'boolean',
            'image' => 'nullable|image|max:2048',
            'delivery_type' => 'required|in:account,email_only,customer_account',
            'slug' => 'nullable|string', // Allow custom slug

            // Gallery validation
            'deleted_gallery_ids' => 'nullable|array',
            'deleted_gallery_ids.*' => 'integer',
            'new_gallery_images' => 'nullable|array',
            'new_gallery_images.*' => 'image|max:5120', // 5MB max per image
            'gallery_order' => 'nullable|array',
            'gallery_order.*' => 'integer',
        ]);

        // Backward compatibility: Set main category_id to the first selected category
        $validated['category_id'] = $validated['category_ids'][0] ?? null;
        unset($validated['category_ids']); // Remove from direct update array (synced separately)

        // Handle slug: Only auto-generate if not provided or empty string
        if (!isset($validated['slug']) || trim($validated['slug']) === '') {
            $validated['slug'] = Str::slug($validated['name']);
        } else {
            // Ensure slug is clean
            $validated['slug'] = Str::slug($validated['slug']);
        }

        $imageService = app(ImageService::class);

        DB::transaction(function () use ($request, $product, $validated, $imageService) {

            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($product->image && str_starts_with($product->image, '/storage/')) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $product->image));
                }

                $validated['image'] = $imageService->storeOptimized(
                    $request->file('image'),
                    'products',
                    ['max_width' => 800, 'max_height' => 800, 'quality' => 85]
                );
            }

            $product->update($validated);

            // Sync Categories
            if ($request->has('category_ids')) {
                $product->categories()->sync($request->input('category_ids'));
            }

            // Handle Existing Variants
            if ($request->has('existing_variants')) {
                $existingVariants = $request->input('existing_variants');
                // Decode JSON if sent as string from FormData
                if (is_string($existingVariants)) {
                    $existingVariants = json_decode($existingVariants, true);
                }

                foreach ($existingVariants as $id => $data) {
                    $variant = $product->variants()->find($id);
                    if ($variant) {
                        // Handle image deletion if requested
                        if (isset($data['delete_image']) && $data['delete_image']) {
                            if ($variant->variant_image) {
                                Storage::disk('public')->delete(str_replace('/storage/', '', $variant->variant_image));
                            }
                            $variant->update(['variant_image' => '']);
                        }

                        $variant->update([
                            'name' => $data['name'],
                            'slug' => $data['slug'] ?? Str::slug($data['name']),
                            'price' => $data['price'],
                            'sale_price' => $data['sale_price'],
                            'stock_quantity' => $data['stock_quantity'] ?? $data['stock'] ?? 0,
                            'delivery_type' => $data['delivery_type'] ?: $product->delivery_type,
                            'status' => $data['status'] ?? 'active',
                            'sort_order' => $data['sort_order'] ?? 0,
                        ]);
                    }
                }

                // Debug: Log all file keys to see what's received
                \Log::info("All files received", [
                    'files' => array_keys($request->allFiles()),
                ]);

                // Handle variant images separately after all variants updated
                foreach ($existingVariants as $id => $data) {
                    \Log::info("Checking variant image for ID: $id", [
                        'hasFile' => $request->hasFile("variant_images.$id"),
                    ]);

                    if ($request->hasFile("variant_images.$id")) {
                        $variant = $product->variants()->find($id);
                        if ($variant) {
                            $file = $request->file("variant_images.$id");

                            if ($file->isValid()) {
                                // Delete old image if exists
                                if ($variant->variant_image) {
                                    Storage::disk('public')->delete(str_replace('/storage/', '', $variant->variant_image));
                                }
                                // Store optimized image
                                $optimizedPath = $imageService->storeOptimized(
                                    $file,
                                    'variants',
                                    ['max_width' => 600, 'max_height' => 600, 'quality' => 85]
                                );
                                $variant->update(['variant_image' => $optimizedPath]);
                            }
                        }
                    }
                }
            }

            // Handle New Variants
            $newVariantsData = $request->input('new_variants');
            if (!empty($newVariantsData)) {
                // Decode JSON if sent as string from FormData
                if (is_string($newVariantsData)) {
                    $newVariantsData = json_decode($newVariantsData, true);
                }
                // Files in new_variants array are tricky with Inertia in some versions if not using strict FormData mapping.
                // However, with forceFormData: true, simple fields come through.
                // For files, we might need to access $request->file('new_variants').

                foreach ($newVariantsData as $index => $data) {
                    // Check logic to skip if empty?
                    if (empty($data['name']))
                        continue;

                    $imagePath = null;
                    if ($request->hasFile("new_variants.{$index}.image")) {
                        $file = $request->file("new_variants.{$index}.image");
                        $imagePath = $imageService->storeOptimized(
                            $file,
                            'variants',
                            ['max_width' => 600, 'max_height' => 600, 'quality' => 85]
                        );
                    }

                    $product->variants()->create([
                        'name' => $data['name'],
                        'slug' => $data['slug'] ?? ($product->slug . '-' . Str::slug($data['name'])),
                        'price' => $data['price'],
                        'sale_price' => $data['sale_price'],
                        'stock_quantity' => $data['stock_quantity'] ?? $data['stock'] ?? 0,
                        'delivery_type' => $data['delivery_type'] ?: $product->delivery_type, // Fallback to product delivery type
                        'status' => 'active',
                        'image' => $imagePath,
                        // 'title' => $data['title'] // Model currently doesn't have title for variant? Check migration later.
                    ]);
                }
            }

            // Handle Main Variant Selection
            if ($request->has('main_variant_selection')) {
                $mainId = $request->input('main_variant_selection');
                // Reset all
                $product->variants()->update(['is_main' => false]);
                // Set new main
                if ($mainId) {
                    $product->variants()->where('id', $mainId)->update(['is_main' => true]);
                }
            }

            // Handle Deleted Gallery Images
            if ($request->has('deleted_gallery_ids')) {
                $deletedIds = $request->input('deleted_gallery_ids');
                foreach ($deletedIds as $id) {
                    $galleryImage = $product->gallery()->find($id);
                    if ($galleryImage) {
                        // Delete file from storage
                        if ($galleryImage->image_path && str_starts_with($galleryImage->image_path, '/storage/')) {
                            Storage::disk('public')->delete(str_replace('/storage/', '', $galleryImage->image_path));
                        }
                        // Delete record
                        $galleryImage->delete();
                    }
                }
            }

            // Handle New Gallery Images with optimization
            $uploadedCount = 0;
            $maxOrder = $product->gallery()->max('sort_order') ?? 0;

            // Try array format first
            if ($request->hasFile('new_gallery_images')) {
                $files = $request->file('new_gallery_images');
                if (!is_array($files)) {
                    $files = [$files];
                }

                foreach ($files as $index => $file) {
                    $path = $imageService->storeOptimized(
                        $file,
                        'gallery',
                        ['max_width' => 1200, 'max_height' => 1200, 'quality' => 85]
                    );

                    if ($path) {
                        $product->gallery()->create([
                            'image_path' => $path,
                            'sort_order' => $maxOrder + $index + 1
                        ]);
                        $uploadedCount++;
                    }
                }
            } else {
                // Try indexed format (new_gallery_images.0, new_gallery_images.1, etc.)
                for ($i = 0; $i < 50; $i++) {
                    if ($request->hasFile("new_gallery_images.$i")) {
                        $file = $request->file("new_gallery_images.$i");

                        $path = $imageService->storeOptimized(
                            $file,
                            'gallery',
                            ['max_width' => 1200, 'max_height' => 1200, 'quality' => 85]
                        );

                        if ($path) {
                            $product->gallery()->create([
                                'image_path' => $path,
                                'sort_order' => $maxOrder + $uploadedCount + 1
                            ]);
                            $uploadedCount++;
                        }
                    }
                }
            }

            // Update Gallery Sort Order
            if ($request->has('gallery_order')) {
                $galleryOrder = $request->input('gallery_order');
                foreach ($galleryOrder as $index => $id) {
                    $product->gallery()->where('id', $id)->update(['sort_order' => $index]);
                }
            }
        });

        $this->clearProductCache();
        return redirect()->route('admin.products.index')->with('success', 'Sản phẩm đã được cập nhật.');
    }

    public function updateVariant(Request $request, ProductVariant $variant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'delivery_type' => 'required|in:account,email_only,customer_account',
            'status' => 'required|in:active,inactive,draft',
            'variant_image' => 'nullable|image|max:5120',
            'delete_image' => 'nullable|boolean',
        ]);

        // Handle image deletion
        if ($request->boolean('delete_image')) {
            if ($variant->variant_image) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $variant->variant_image));
            }
            $variant->variant_image = '';
        }

        // Handle image upload
        if ($request->hasFile('variant_image')) {
            // Delete old image
            if ($variant->variant_image) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $variant->variant_image));
            }
            // Store new image
            $path = $request->file('variant_image')->store('variants', 'public');
            $validated['variant_image'] = '/storage/' . $path;
        }

        $variant->update($validated);

        return back()->with('success', 'Variant đã được cập nhật.');
    }

    public function destroy(Product $product)
    {
        if ($product->image && str_starts_with($product->image, '/storage/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $product->image));
        }

        $product->delete();

        $this->clearProductCache();
        return redirect()->back()->with('success', 'Sản phẩm đã được xóa.');
    }

    public function toggleFeatured(Product $product)
    {
        $product->featured = !$product->featured;
        $product->save();

        $this->clearProductCache();
        return redirect()->back()->with('success', 'Đã cập nhật trạng thái nổi bật');
    }
}
