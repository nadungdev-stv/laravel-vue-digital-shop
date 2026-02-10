<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\Product; // Ensure Product model is imported
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $wishlistItems = Wishlist::where('user_id', $user->id)
            ->with([
                'product' => function ($query) {
                    $query->select('id', 'name', 'slug', 'image', 'price', 'sale_price', 'status', 'featured');
                },
                'product.variants' => function ($query) {
                    $query->where('is_main', true)->where('status', 'active');
                }
            ])
            ->latest()
            ->get()
            ->map(function ($item) {
                $product = $item->product;
                if (!$product)
                    return null;

                $mainVariant = $product->variants->first();

                if ($mainVariant) {
                    $product->variant_id = $mainVariant->id;
                    $product->variant_name = $mainVariant->name;
                    $product->variant_title = $mainVariant->variant_title;
                    $product->variant_slug = $mainVariant->slug;
                    $product->variant_price = $mainVariant->price;
                    $product->variant_sale_price = $mainVariant->sale_price;
                    $product->variant_image = $mainVariant->variant_image;
                }

                return $product;
            })
            ->filter();

        return Inertia::render('Wishlist/Index', [
            'products' => $wishlistItems->values(),
        ]);
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $user = Auth::user();
        $productId = $request->product_id;

        $exists = Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->exists();

        if ($exists) {
            Wishlist::where('user_id', $user->id)
                ->where('product_id', $productId)
                ->delete();
            $message = 'Đã xóa khỏi danh sách yêu thích';
            $type = 'success';
        } else {
            Wishlist::create([
                'user_id' => $user->id,
                'product_id' => $productId,
            ]);
            $message = 'Đã thêm vào danh sách yêu thích';
            $type = 'success';
        }

        // Return JSON if AJAX (usually for toggle button)
        // Or redirect back
        if ($request->wantsJson()) {
            return response()->json([
                'in_wishlist' => !$exists,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }
}
