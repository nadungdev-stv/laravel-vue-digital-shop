<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class CartController extends Controller
{
    private function getCartQuery()
    {
        if (Auth::check()) {
            return Cart::where('user_id', Auth::id());
        } else {
            return Cart::where('session_id', Session::getId())->whereNull('user_id');
        }
    }

    public function index()
    {
        // Clear buy now session to ensure cart checkout uses actual cart
        if (Session::has('buy_now_product')) {
            Session::forget('buy_now_product');
            Session::forget('buy_now_customer_email');
            Session::forget('buy_now_customer_account');
        }

        $cartItems = $this->getCartQuery()
            ->with(['product.gallery', 'variant'])
            ->get()
            ->map(function ($item) {
                $product = $item->product;
                $variant = $item->variant;

                $price = 0;
                $name = '';
                $image = $product->image;
                $stock = 0;
                $slug = $product->slug;

                if ($variant) {
                    $price = $variant->sale_price ?? $variant->price;
                    $name = !empty($variant->variant_title) ? $variant->variant_title : ($product->name . ' - ' . $variant->name);
                    $image = $variant->variant_image ?: ($product->gallery->first()?->image_path ?: $product->image);
                    $stock = $product->stock_quantity; // Variants might share stock or have their own depending on design, using product stock for now based on cart.php logic
                    $slug = $variant->slug ?: $product->slug;
                } else {
                    $price = $product->sale_price > 0 ? $product->sale_price : $product->price;
                    $name = $product->name;
                    $image = $product->gallery->first()?->image_path ?: $product->image;
                    $stock = $product->stock_quantity;
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
                    'subtotal' => $price * $item->quantity,
                    'stock_quantity' => $stock,
                ];
            });

        $subtotal = $cartItems->sum('subtotal');
        $discount = 0;
        $couponCode = null;
        $couponMessage = null;

        if (Session::has('applied_coupon_code')) {
            $code = Session::get('applied_coupon_code');
            $coupon = Coupon::where('code', $code)->first();

            if ($coupon && $coupon->isValid()) {
                if ($subtotal >= $coupon->min_amount) {
                    if ($coupon->type === 'percent') {
                        $discount = ($subtotal * $coupon->value) / 100;
                        if ($coupon->max_discount && $discount > $coupon->max_discount) {
                            $discount = $coupon->max_discount;
                        }
                    } else {
                        $discount = $coupon->value;
                    }
                    $couponCode = $code;
                    $couponMessage = "Đã áp dụng mã giảm giá: $code";
                } else {
                    // Coupon valid but min_amount not met
                    Session::forget('applied_coupon_code');
                    // Optional: could pass a message saying min amount not met
                }
            } else {
                Session::forget('applied_coupon_code');
            }
        }

        return Inertia::render('Cart/Index', [
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => max(0, $subtotal - $discount),
            'couponCode' => $couponCode,
            'couponMessage' => $couponMessage,
        ]);
    }

    public function add(Request $request)
    {
        // Clear buy now session as user is building a cart
        if (Session::has('buy_now_product')) {
            Session::forget('buy_now_product');
        }

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'nullable|integer|min:1|max:5',
        ]);

        $userId = Auth::id();
        $sessionId = Session::getId();
        $quantity = $request->quantity ?? 1;

        $conditions = [
            'product_id' => $request->product_id,
            'variant_id' => $request->variant_id,
        ];

        if ($userId) {
            $conditions['user_id'] = $userId;
        } else {
            $conditions['session_id'] = $sessionId;
            $conditions['user_id'] = null; // Important for uniqueness
        }

        $cartItem = Cart::where($conditions)->first();

        if ($cartItem) {
            $cartItem->increment('quantity', $quantity);
        } else {
            Cart::create(array_merge($conditions, [
                'quantity' => $quantity,
                'session_id' => $sessionId, // Always store session_id for potential merging later
            ]));
        }

        return redirect()->back()->with('success', 'Đã thêm sản phẩm vào giỏ hàng.');
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:cart,id',
            'quantity' => 'required|integer|min:1|max:5',
        ]);

        $cartItem = $this->getCartQuery()->where('id', $request->id)->firstOrFail();
        $cartItem->update(['quantity' => $request->quantity]);

        return redirect()->back();
    }

    public function remove(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:cart,id',
        ]);

        $this->getCartQuery()->where('id', $request->id)->delete();

        return redirect()->back()->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    }

    public function applyCoupon(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string',
        ]);

        $coupon = Coupon::where('code', $request->coupon_code)->first();

        if (!$coupon) {
            return redirect()->back()->withErrors(['coupon' => 'Mã giảm giá không tồn tại.']);
        }

        if (!$coupon->isValid()) {
            return redirect()->back()->withErrors(['coupon' => 'Mã giảm giá đã hết hạn hoặc không khả dụng.']);
        }

        // Check min amount requirement based on current cart
        $cartItems = $this->getCartQuery()->with(['product', 'variant'])->get();
        $subtotal = 0;
        foreach ($cartItems as $item) {
            $price = $item->variant ? ($item->variant->sale_price ?? $item->variant->price) : ($item->product->sale_price > 0 ? $item->product->sale_price : $item->product->price);
            $subtotal += $price * $item->quantity;
        }

        if ($subtotal < $coupon->min_amount) {
            return redirect()->back()->withErrors(['coupon' => 'Đơn hàng chưa đạt giá trị tối thiểu để sử dụng mã này.']);
        }

        Session::put('applied_coupon_code', $coupon->code);

        return redirect()->back()->with('success', 'Áp dụng mã giảm giá thành công.');
    }

    public function removeCoupon()
    {
        Session::forget('applied_coupon_code');
        return redirect()->back()->with('success', 'Đã xóa mã giảm giá.');
    }

    public function buyNow(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'nullable|integer|min:1|max:5',
        ]);

        $quantity = $request->quantity ?? 1;

        // Store buy now data in session
        Session::put('buy_now_product', [
            'product_id' => $request->product_id,
            'variant_id' => $request->variant_id,
            'quantity' => $quantity,
        ]);

        // Mark that this is a buy_now intent (not cart checkout)
        Session::put('buy_now_intent', true);

        // Store additional customer info if provided
        if ($request->has('customer_email')) {
            Session::put('buy_now_customer_email', $request->customer_email);
        }

        if ($request->has('customer_username') && $request->has('customer_password')) {
            Session::put('buy_now_customer_account', [
                'username' => $request->customer_username,
                'password' => $request->customer_password,
            ]);
        }

        return redirect()->route('checkout.index');
    }

    /**
     * Prepare for cart checkout by clearing buy_now intent
     * Used when user clicks "Checkout" from cart popup
     */
    public function prepareCheckout()
    {
        // Clear buy_now intent to switch from buy_now to cart checkout
        Session::forget('buy_now_intent');
        Session::forget('buy_now_product');
        Session::forget('buy_now_customer_email');
        Session::forget('buy_now_customer_account');

        return redirect()->route('checkout.index');
    }
}
