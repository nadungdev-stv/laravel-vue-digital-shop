<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\Cart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Get cart query for current user/session
     */
    private function getCartQuery(Request $request)
    {
        return $request->user()
            ? Cart::where('user_id', $request->user()->id)
            : Cart::where('session_id', $request->session()->getId())->whereNull('user_id');
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'cartCount' => fn() => (int) $this->getCartQuery($request)->sum('quantity'),
            'cartItems' => function () use ($request) {
                // Optimized: select only needed fields, limit gallery to 1
                return $this->getCartQuery($request)
                    ->with([
                        'product:id,name,slug,image,price,sale_price,stock_quantity',
                        'product.gallery' => fn($q) => $q->orderBy('sort_order')->limit(1),
                        'variant:id,product_id,name,slug,variant_title,variant_image,price,sale_price'
                    ])
                    ->get()
                    ->map(function ($item) {
                        $product = $item->product;
                        $variant = $item->variant;

                        if ($variant) {
                            $price = $variant->sale_price ?? $variant->price;
                            $name = !empty($variant->variant_title) ? $variant->variant_title : ($product->name . ' - ' . $variant->name);
                            $image = $variant->variant_image ?: ($product->gallery->first()?->image_path ?: $product->image);
                            $slug = $variant->slug ?: $product->slug;
                        } else {
                            $price = $product->sale_price > 0 ? $product->sale_price : $product->price;
                            $name = $product->name;
                            $image = $product->gallery->first()?->image_path ?: $product->image;
                            $slug = $product->slug;
                        }

                        return [
                            'id' => $item->id,
                            'product_id' => $item->product_id,
                            'variant_id' => $item->variant_id,
                            'name' => $name,
                            'slug' => $slug,
                            'image' => $image,
                            'price' => $price,
                            'quantity' => $item->quantity,
                            'stock_quantity' => $product->stock_quantity ?? 0,
                        ];
                    });
            },
            'cartTotal' => function () use ($request) {
                return $this->getCartQuery($request)
                    ->with(['product:id,price,sale_price', 'variant:id,price,sale_price'])
                    ->get()
                    ->sum(function ($item) {
                        $price = $item->variant
                            ? ($item->variant->sale_price ?? $item->variant->price)
                            : ($item->product->sale_price > 0 ? $item->product->sale_price : $item->product->price);
                        return $price * $item->quantity;
                    });
            },
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
            ],
            'walletBalance' => fn() => $request->user()?->balance ?? 0,
            'notifications' => function () use ($request) {
                if (!$request->user()) return [];
                try {
                    return \App\Models\Notification::where('user_id', $request->user()->id)
                        ->select('id', 'title', 'message', 'type', 'is_read', 'created_at')
                        ->orderBy('created_at', 'desc')
                        ->limit(5)
                        ->get();
                } catch (\Exception $e) {
                    return [];
                }
            },
            'unreadNotificationCount' => function () use ($request) {
                if (!$request->user()) return 0;
                try {
                    return \App\Models\Notification::where('user_id', $request->user()->id)
                        ->where('is_read', false)
                        ->count();
                } catch (\Exception $e) {
                    return 0;
                }
            },
            // Use cached settings
            'settings' => fn() => \App\Models\Setting::getAllCached(),
            // Admin Global Stats - cached for 5 minutes
            'pendingOrders' => function () use ($request) {
                if (!$request->user() || $request->user()->role !== 'admin') return 0;
                return Cache::remember('admin_pending_orders', 300, fn() =>
                    DB::table('orders')->where('payment_status', 'pending')->count()
                );
            },
            'lowStockProducts' => function () use ($request) {
                if (!$request->user() || $request->user()->role !== 'admin') return 0;
                return Cache::remember('admin_low_stock', 300, fn() =>
                    DB::table('products')->where('stock_quantity', '>', 0)->where('stock_quantity', '<', 5)->count()
                );
            },
            'pendingReviews' => function () use ($request) {
                if (!$request->user() || $request->user()->role !== 'admin') return 0;
                return Cache::remember('admin_pending_reviews', 300, fn() =>
                    DB::table('reviews')->where('status', 'pending')->count()
                );
            },
            'unreadChats' => function () use ($request) {
                if (!$request->user() || $request->user()->role !== 'admin') return 0;
                return Cache::remember('admin_unread_chats', 300, fn() =>
                    DB::table('chat_messages')->where('sender_type', 'customer')->where('is_read', 0)->distinct('session_id')->count()
                );
            },
        ];
    }
}
