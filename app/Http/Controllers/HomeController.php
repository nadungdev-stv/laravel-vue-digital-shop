<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class HomeController extends Controller
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Clean image paths helper
     */
    private function cleanPath($path)
    {
        if (!$path) return $path;
        $path = str_replace('/public/', '/', $path);
        if (str_starts_with($path, 'public/')) {
            $path = '/' . substr($path, 7);
        }
        return $path;
    }

    /**
     * Clean paths for product array
     */
    private function cleanProductPaths(array $products): array
    {
        foreach ($products as $p) {
            $p->image = $this->cleanPath($p->image);
            $p->variant_image = $this->cleanPath($p->variant_image);
            $p->first_gallery_image = $this->cleanPath($p->first_gallery_image);
        }
        return $products;
    }

    public function index()
    {
        // Cache all homepage data for better performance
        $homeData = Cache::remember('homepage_data', self::CACHE_TTL, function () {
            // 1. Categories
            $categories = DB::select("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order");
            foreach ($categories as $cat) {
                $cat->icon = $this->cleanPath($cat->icon);
            }

            // 2. Categories with Images - optimized with JOIN instead of subqueries
            $categoriesWithImages = DB::select("
                SELECT c.*,
                    (SELECT p.image FROM products p WHERE p.category_id = c.id AND p.status = 'active' ORDER BY p.featured DESC, p.created_at DESC LIMIT 1) as representative_image,
                    (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status = 'active') as product_count
                FROM categories c
                WHERE c.status = 'active'
                ORDER BY c.sort_order
            ");
            foreach ($categoriesWithImages as $cat) {
                $cat->representative_image = $this->cleanPath($cat->representative_image);
            }

            // 3. Banners
            $banners = DB::select("SELECT * FROM banners WHERE status = 'active' ORDER BY sort_order ASC LIMIT 10");
            foreach ($banners as $banner) {
                $banner->image = $this->cleanPath($banner->image);
            }

            // 4. Featured Products - optimized query
            $featuredProducts = DB::select("
                SELECT p.id, p.name, p.slug, p.image, p.price, p.sale_price, p.category_id, p.sold_count,
                    c.name as category_name,
                    v.id as variant_id, v.name as variant_name, v.variant_title, v.variant_image,
                    v.price as variant_price, v.sale_price as variant_sale_price,
                    v.slug as variant_slug, v.stock_quantity as variant_stock,
                    g.image_path as first_gallery_image
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_variants v ON p.id = v.product_id AND v.is_main = 1
                LEFT JOIN (
                    SELECT product_id, MIN(id) as min_id
                    FROM product_gallery
                    GROUP BY product_id
                ) pg_min ON p.id = pg_min.product_id
                LEFT JOIN product_gallery g ON pg_min.min_id = g.id
                WHERE p.status = 'active'
                ORDER BY p.featured DESC, p.created_at DESC
                LIMIT 24
            ");
            $featuredProducts = $this->cleanProductPaths($featuredProducts);

            // 5. Best Selling - optimized, fetch exactly 12 with RAND()
            $bestSellingProducts = DB::select("
                SELECT p.id, p.name, p.slug, p.image, p.price, p.sale_price, p.category_id, p.sold_count,
                    c.name as category_name,
                    v.id as variant_id, v.name as variant_name, v.variant_title, v.variant_image,
                    v.price as variant_price, v.sale_price as variant_sale_price,
                    v.slug as variant_slug, v.stock_quantity as variant_stock,
                    g.image_path as first_gallery_image
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_variants v ON p.id = v.product_id AND v.is_main = 1
                LEFT JOIN (
                    SELECT product_id, MIN(id) as min_id
                    FROM product_gallery
                    GROUP BY product_id
                ) pg_min ON p.id = pg_min.product_id
                LEFT JOIN product_gallery g ON pg_min.min_id = g.id
                WHERE p.status = 'active'
                ORDER BY COALESCE(p.sold_count, 0) DESC, RAND()
                LIMIT 12
            ");
            $bestSellingProducts = $this->cleanProductPaths($bestSellingProducts);

            // 6. Products for Tab Navigation - optimized
            $allTabProducts = DB::select("
                SELECT p.id, p.name, p.slug, p.image, p.price, p.sale_price, p.category_id,
                    c.name as category_name,
                    v.id as variant_id, v.name as variant_name, v.variant_title, v.variant_image,
                    v.price as variant_price, v.sale_price as variant_sale_price,
                    v.slug as variant_slug, v.stock_quantity as variant_stock,
                    g.image_path as first_gallery_image
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_variants v ON p.id = v.product_id AND v.is_main = 1
                LEFT JOIN (
                    SELECT product_id, MIN(id) as min_id
                    FROM product_gallery
                    GROUP BY product_id
                ) pg_min ON p.id = pg_min.product_id
                LEFT JOIN product_gallery g ON pg_min.min_id = g.id
                WHERE p.status = 'active'
                ORDER BY p.featured DESC, p.created_at DESC
                LIMIT 50
            ");
            $allTabProducts = $this->cleanProductPaths($allTabProducts);

            return compact('categories', 'categoriesWithImages', 'banners', 'featuredProducts', 'bestSellingProducts', 'allTabProducts');
        });

        // Promo Banners (uses cached settings)
        $promoBanners = json_decode(\App\Models\Setting::getValue('promo_banners', '[]'), true) ?: [];
        foreach ($promoBanners as &$promo) {
            if (isset($promo['image'])) {
                $promo['image'] = $this->cleanPath($promo['image']);
            }
        }
        unset($promo);

        return Inertia::render('Home', [
            ...$homeData,
            'promoBanners' => $promoBanners,
        ]);
    }
    public function about()
    {
        $siteName = config('app.name', 'Veyrix');
        $contactEmail = 'admin@veyrix.pro';
        $contactPhone = '0848877758';

        return Inertia::render('About', [
            'siteName' => $siteName,
            'contactEmail' => $contactEmail,
            'contactPhone' => $contactPhone
        ]);
    }

    public function terms()
    {
        $siteName = config('app.name', 'Veyrix');
        $contactEmail = 'admin@veyrix.pro';
        $contactPhone = '0848877758';

        return Inertia::render('Terms', [
            'siteName' => $siteName,
            'contactEmail' => $contactEmail,
            'contactPhone' => $contactPhone
        ]);
    }
}
