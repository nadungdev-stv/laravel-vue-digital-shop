<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::where('user_id', Auth::id())
            ->with(['items.product'])
            ->latest()
            ->paginate(10)
            ->through(function ($order) {
                return [
                    'id' => $order->id,
                    'order_code' => $order->order_code,
                    'created_at' => $order->created_at->format('d/m/Y'),
                    'created_at_time' => $order->created_at->format('H:i'),
                    'final_amount' => $order->final_amount,
                    'order_status' => $order->order_status,
                    'payment_status' => $order->payment_status,
                    'items_count' => $order->items->count(),
                    'first_item_name' => $order->items->first()?->product_name,
                ];
            });

        $stats = [
            'total' => Order::where('user_id', Auth::id())->count(),
            'completed' => Order::where('user_id', Auth::id())->where('order_status', 'completed')->count(),
            'pending' => Order::where('user_id', Auth::id())->where('order_status', 'pending')->count(),
            'total_spent' => Order::where('user_id', Auth::id())->where('payment_status', 'paid')->sum('final_amount'),
        ];

        return Inertia::render('User/Orders/Index', [
            'orders' => $orders,
            'stats' => $stats,
        ]);
    }

    public function show($code)
    {
        // Get user id if logged in
        $userId = Auth::id();

        // Try to find order for logged in user first
        $order = null;
        if ($userId) {
            $order = Order::where('order_code', $code)
                ->where('user_id', $userId)
                ->with(['items.product'])
                ->first();
        }

        // If not found and guest, check guest orders in session
        if (!$order) {
            $guestOrders = Session::get('guest_orders', []);
            if (in_array($code, $guestOrders)) {
                $order = Order::where('order_code', $code)
                    ->whereNull('user_id')
                    ->with(['items.product'])
                    ->first();
            }
        }

        if (!$order) {
            abort(404, 'Không tìm thấy đơn hàng');
        }

        // Get bank info for payment modal
        $bankInfo = $this->getBankInfo();

        return Inertia::render('User/Orders/Show', [
            'order' => $order,
            'items' => $order->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'image' => $item->image ?? $item->product?->image,
                    'account_delivered' => $item->account_delivered,
                    'delivery_type' => $item->product?->delivery_type,
                    'customer_account_info' => $item->customer_account_info,
                ];
            }),
            'bankInfo' => $bankInfo,
            'user' => Auth::user(),
        ]);
    }

    /**
     * Get bank info from settings
     */
    private function getBankInfo()
    {
        $settings = DB::table('settings')
            ->whereIn('setting_key', [
                'sepay_enabled',
                'sepay_bank_code',
                'sepay_account_number',
                'sepay_account_name',
            ])
            ->pluck('setting_value', 'setting_key');

        $bankCode = $settings['sepay_bank_code'] ?? 'MB';
        $accountNumber = $settings['sepay_account_number'] ?? '';
        $accountName = $settings['sepay_account_name'] ?? '';

        // Bank names mapping
        $bankNames = [
            'MB' => 'MB Bank',
            'VCB' => 'Vietcombank',
            'TCB' => 'Techcombank',
            'ACB' => 'ACB',
            'VPB' => 'VPBank',
            'TPB' => 'TPBank',
            'BIDV' => 'BIDV',
            'VTB' => 'Vietinbank',
            'MSB' => 'MSB',
            'SHB' => 'SHB',
            'STB' => 'Sacombank',
            'EIB' => 'Eximbank',
            'HDB' => 'HDBank',
            'OCB' => 'OCB',
            'LPB' => 'LienVietPostBank',
        ];

        return [
            'bank_code' => $bankCode,
            'bank_name' => $bankNames[$bankCode] ?? $bankCode,
            'account_number' => $accountNumber,
            'account_name' => $accountName,
        ];
    }

    /**
     * Confirm payment (manual confirmation by user)
     */
    public function confirmPayment(Request $request, $code)
    {
        $order = Order::where('order_code', $code)
            ->where('user_id', Auth::id())
            ->where('payment_status', 'pending')
            ->firstOrFail();

        // Update to processing (waiting for admin verification)
        $order->update([
            'order_status' => 'processing',
        ]);

        return back()->with('success', 'Đã xác nhận thanh toán! Chúng tôi sẽ kiểm tra và xử lý đơn hàng của bạn.');
    }

    /**
     * Confirm payment for guest orders (public route)
     */
    public function confirmPaymentGuest(Request $request, $code)
    {
        $order = Order::where('order_code', $code)
            ->where('payment_status', 'pending')
            ->where('order_status', 'pending')
            ->firstOrFail();

        // Update to processing (waiting for admin verification)
        $order->update([
            'order_status' => 'processing',
        ]);

        return back()->with('success', 'Đã xác nhận thanh toán! Chúng tôi sẽ kiểm tra và xử lý đơn hàng của bạn.');
    }
}
