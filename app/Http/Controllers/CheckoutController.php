<?php

namespace App\Http\Controllers;

use App\Models\Action; // Assuming this model exists for logs, or we skip
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User; // For wallet
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Illuminate\Database\QueryException;

class CheckoutController extends Controller
{
    private function getCartItems()
    {
        // Check if this is an explicit buy_now intent
        // If user clicked "Mua ngay" button, prioritize buy_now over cart
        if (Session::has('buy_now_intent') && Session::has('buy_now_product')) {
            $buyNowData = Session::get('buy_now_product');
            $product = Product::with('gallery')->find($buyNowData['product_id']);

            if (!$product)
                return collect([]);

            $variant = $buyNowData['variant_id'] ? ProductVariant::find($buyNowData['variant_id']) : null;
            $quantity = $buyNowData['quantity'];

            $price = $variant ? ($variant->sale_price ?? $variant->price) : ($product->sale_price > 0 ? $product->sale_price : $product->price);
            $name = $variant ? ($variant->variant_title ?: $product->name . ' - ' . $variant->name) : $product->name;
            $image = $variant && $variant->variant_image ? $variant->variant_image : ($product->gallery->first()?->image_path ?: $product->image);
            $slug = $variant ? ($variant->slug ?: $product->slug) : $product->slug;

            // Construct item structure similar to Cart map
            return collect([
                [
                    'product_id' => $product->id,
                    'variant_id' => $variant?->id,
                    'name' => $name,
                    'slug' => $slug,
                    'image' => $image,
                    'price' => $price,
                    'quantity' => $quantity,
                    'subtotal' => $price * $quantity,
                    'stock_quantity' => $product->stock_quantity,
                    'delivery_type' => $variant->delivery_type ?? $product->delivery_type,
                ]
            ]);
        }

        // No buy_now intent, check actual cart
        $userId = Auth::id();
        $sessionId = Session::getId();

        $cartQuery = Cart::query();
        if ($userId) {
            $cartQuery->where('user_id', $userId);
        } else {
            $cartQuery->where('session_id', $sessionId)->whereNull('user_id');
        }

        $actualCartCount = $cartQuery->count();

        // If cart has items, use cart and clear any stale buy_now session
        if ($actualCartCount > 0) {
            // Clear stale buy_now session since user is using cart
            if (Session::has('buy_now_product')) {
                Session::forget('buy_now_product');
                Session::forget('buy_now_customer_email');
                Session::forget('buy_now_customer_account');
            }

            // Load from actual cart
            return $cartQuery->with(['product.gallery', 'variant'])->get()->map(function ($item) {
                $product = $item->product;
                $variant = $item->variant;

                $price = 0;
                $name = '';
                $image = $product->image;
                $stock = 0;
                $deliveryType = $product->delivery_type;
                $slug = $product->slug;

                if ($variant) {
                    $price = $variant->sale_price ?? $variant->price;
                    $name = !empty($variant->variant_title) ? $variant->variant_title : ($product->name . ' - ' . $variant->name);
                    $image = $variant->variant_image ?: ($product->gallery->first()?->image_path ?: $product->image);
                    $stock = $product->stock_quantity;
                    $deliveryType = $variant->delivery_type ?? $product->delivery_type;
                    $slug = $variant->slug ?: $product->slug;
                } else {
                    $price = $product->sale_price > 0 ? $product->sale_price : $product->price;
                    $name = $product->name;
                    $image = $product->gallery->first()?->image_path ?: $product->image;
                    $stock = $product->stock_quantity;
                }

                return [
                    'id' => $item->id, // Cart ID, n/a for buy now
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'name' => $name,
                    'slug' => $slug,
                    'image' => $image,
                    'price' => $price,
                    'quantity' => $item->quantity,
                    'subtotal' => $price * $item->quantity,
                    'stock_quantity' => $stock,
                    'delivery_type' => $deliveryType,
                ];
            });
        }

        // No cart items and no buy_now intent
        return collect([]);
    }

    public function index()
    {
        $cartItems = $this->getCartItems();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng trống hoặc phiên mua hàng hết hạn.');
        }

        $subtotal = $cartItems->sum('subtotal');
        $discount = 0;
        $couponCode = null;

        // Apply coupon logic
        if (Session::has('applied_coupon_code')) {
            $code = Session::get('applied_coupon_code');
            $coupon = Coupon::where('code', $code)->first();

            if ($coupon && $coupon->isValid() && $subtotal >= $coupon->min_amount) {
                if ($coupon->type === 'percent') {
                    $discount = ($subtotal * $coupon->value) / 100;
                    if ($coupon->max_discount && $discount > $coupon->max_discount) {
                        $discount = $coupon->max_discount;
                    }
                } else {
                    $discount = $coupon->value;
                }
                $couponCode = $code;
            } else {
                Session::forget('applied_coupon_code');
            }
        }

        return Inertia::render('Checkout/Index', [
            'checkoutItems' => $cartItems,  // Changed from cartItems to checkoutItems
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => max(0, $subtotal - $discount),
            'couponCode' => $couponCode,
            'user' => Auth::user(),
            'buyNowCustomerEmail' => Session::get('buy_now_customer_email'),
            'buyNowCustomerAccount' => Session::get('buy_now_customer_account'),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'customer_name' => 'required|string|max:100',
            'customer_email' => 'required|email|max:100',
            'customer_phone' => 'required|string|max:20',
            'payment_method' => 'required|in:bank_transfer,wallet,momo,zalopay',
            'invitation_emails' => 'nullable|array',
            'customer_accounts' => 'nullable|array',
        ];

        // Custom validation for Wallet
        if ($request->payment_method === 'wallet') {
            if (!$user) {
                return back()->withErrors(['payment_method' => 'Bạn cần đăng nhập để thanh toán qua ví.']);
            }
        }

        $validated = $request->validate($rules);
        $cartItems = $this->getCartItems();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng trống.');
        }

        // Validate stock
        foreach ($cartItems as $item) {
            if ($item['stock_quantity'] < $item['quantity']) {
                return back()->withErrors(['error' => "Sản phẩm {$item['name']} không đủ số lượng."]);
            }
        }

        // Calculate Totals again
        $subtotal = $cartItems->sum('subtotal');
        $discount = 0;
        $couponCode = null;

        if (Session::has('applied_coupon_code')) {
            $code = Session::get('applied_coupon_code');
            $coupon = Coupon::where('code', $code)->first();

            if ($coupon && $coupon->isValid() && $subtotal >= $coupon->min_amount) {
                if ($coupon->type === 'percent') {
                    $discount = ($subtotal * $coupon->value) / 100;
                    if ($coupon->max_discount && $discount > $coupon->max_discount) {
                        $discount = $coupon->max_discount;
                    }
                } else {
                    $discount = $coupon->value;
                }
                $couponCode = $code;
            }
        }

        $finalAmount = max(0, $subtotal - $discount);

        // Check Wallet Balance
        if ($validated['payment_method'] === 'wallet') {
            if ($user->balance < $finalAmount) {
                return back()->withErrors(['payment_method' => 'Số dư ví không đủ.']);
            }
        }

        DB::beginTransaction();
        try {
            // Generate Order Code (Letter + 5 digits, e.g., D89342)
            do {
                $letter = chr(rand(65, 90)); // A-Z
                $numbers = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
                $orderCode = $letter . $numbers;
            } while (Order::where('order_code', $orderCode)->exists());

            $order = Order::create([
                'user_id' => $user->id ?? null,
                'order_code' => $orderCode,
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'customer_note' => $request->customer_note,
                'total_amount' => $subtotal,
                'discount_amount' => $discount,
                'final_amount' => $finalAmount,
                'payment_method' => $validated['payment_method'],
                'payment_status' => $validated['payment_method'] === 'wallet' ? 'paid' : 'pending',
                'order_status' => $validated['payment_method'] === 'wallet' ? 'processing' : 'pending', // Wallet auto-paid -> processing
                'coupon_code' => $couponCode,
            ]);

            foreach ($cartItems as $item) {
                // Determine Account Delivered Info
                $accountDelivered = null;
                $customerAccountInfo = null;

                // Handle email_only invitation emails
                if ($item['delivery_type'] === 'email_only' && isset($validated['invitation_emails'][$item['product_id']])) {
                    $customerAccountInfo = [
                        'type' => 'email_only',
                        'emails' => $validated['invitation_emails'][$item['product_id']],
                    ];
                }

                // Handle customer provided account info
                if ($item['delivery_type'] === 'customer_account' && isset($validated['customer_accounts'][$item['product_id']])) {
                    $customerAccountInfo = [
                        'type' => 'customer_account',
                        'account' => $validated['customer_accounts'][$item['product_id']],
                    ];
                }

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'product_name' => $item['name'],
                    'image' => $item['image'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'customer_account_info' => $customerAccountInfo, // Eloquent will cast array to json
                ]);

                // Auto Delivery Logic (Only for 'account' delivery type)
                if ($item['delivery_type'] === 'account') {
                    // Try to fetch stock accounts
                    // NOTE: This assumes 'accounts_stock' table and model would exist properly. 
                    // Using DB Query for simplicity as model might not be created for everything from old project.
                    $accountsNeeded = $item['quantity'];
                    $accounts = DB::table('accounts_stock')
                        ->where('product_id', $item['product_id'])
                        ->where('status', 'available')
                        ->limit($accountsNeeded)
                        ->lockForUpdate() // Prevent race conditions
                        ->get();

                    if ($accounts->count() > 0) {
                        $deliveredContent = "";
                        foreach ($accounts as $account) {
                            DB::table('accounts_stock')
                                ->where('id', $account->id)
                                ->update([
                                    'status' => 'sold',
                                    'sold_to_order_id' => $order->id,
                                    'sold_at' => now(),
                                ]);

                            $deliveredContent .= "Username: {$account->username} | Password: {$account->password}";
                            if (!empty($account->additional_info))
                                $deliveredContent .= " | Info: {$account->additional_info}";
                            $deliveredContent .= "\n";
                        }

                        // Store delivered content in order_item if paying immediately (Wallet) or if business logic allows
                        // Usually only deliver if paid.
                        if ($order->payment_status === 'paid') {
                            $orderItem->update(['account_delivered' => $deliveredContent]);
                        }
                    }
                }

                // Decrement Stock
                Product::where('id', $item['product_id'])->decrement('stock_quantity', $item['quantity']);
            }

            // Handle Wallet Payment
            if ($validated['payment_method'] === 'wallet') {
                $user->decrement('balance', $finalAmount);
                WalletTransaction::create([
                    'user_id' => $user->id,
                    'type' => 'purchase',
                    'amount' => $finalAmount,
                    'balance_before' => $user->balance + $finalAmount,
                    'balance_after' => $user->balance,
                    'description' => "Thanh toán đơn hàng #$orderCode",
                    'reference_id' => $order->id, // If ref_id is int, else modify schema 
                ]);
            }

            // Update Coupon Usage
            if ($couponCode) {
                Coupon::where('code', $couponCode)->increment('used_count');
            }

            // Clear Cart/Session
            if (Session::has('buy_now_product') || Session::has('buy_now_intent')) {
                Session::forget('buy_now_product');
                Session::forget('buy_now_intent');
                Session::forget('buy_now_customer_email');
                Session::forget('buy_now_customer_account');
            } else {
                if ($user) {
                    Cart::where('user_id', $user->id)->delete();
                } else {
                    Cart::where('session_id', Session::getId())->delete();
                }
            }
            Session::forget('applied_coupon_code');

            // Track guest orders in session so they can view later
            if (!$user) {
                $guestOrders = Session::get('guest_orders', []);
                if (!in_array($orderCode, $guestOrders)) {
                    $guestOrders[] = $orderCode;
                    Session::put('guest_orders', $guestOrders);
                }
            }

            DB::commit();

            return redirect()->route('user.orders.show', $orderCode)->with('success', 'Đặt hàng thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Đã có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }
}
