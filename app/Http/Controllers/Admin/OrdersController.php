<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class OrdersController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $orderStatus = $request->get('order_status', '');
        $paymentStatus = $request->get('payment_status', '');
        $dateFrom = $request->get('date_from', '');
        $dateTo = $request->get('date_to', '');
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'DESC');

        // Validate sort column
        $allowedSortColumns = ['order_code', 'customer_name', 'final_amount', 'payment_status', 'payment_method', 'order_status', 'created_at'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'created_at';
        }
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        // Build query
        $query = Order::with(['user', 'items'])
            ->select('orders.*')
            ->leftJoin('users', 'orders.user_id', '=', 'users.id')
            ->selectRaw('COALESCE(users.full_name, users.username, orders.customer_name, "Khách") as display_name')
            ->selectRaw('COALESCE(users.email, orders.customer_email) as display_email');

        // Apply filters
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('orders.order_code', 'LIKE', "%$search%")
                    ->orWhere('users.full_name', 'LIKE', "%$search%")
                    ->orWhere('users.username', 'LIKE', "%$search%")
                    ->orWhere('users.email', 'LIKE', "%$search%")
                    ->orWhere('orders.customer_name', 'LIKE', "%$search%")
                    ->orWhere('orders.customer_email', 'LIKE', "%$search%");
            });
        }

        if ($orderStatus) {
            $query->where('orders.order_status', $orderStatus);
        }

        if ($paymentStatus) {
            $query->where('orders.payment_status', $paymentStatus);
        }

        if ($dateFrom) {
            $query->whereDate('orders.created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('orders.created_at', '<=', $dateTo);
        }

        // Apply sorting
        if ($sortBy === 'customer_name') {
            $query->orderBy('display_name', $sortOrder);
        } else if ($sortBy === 'order_code') {
            $query->orderBy('orders.order_code', $sortOrder);
        } else {
            $query->orderBy("orders.$sortBy", $sortOrder);
        }

        $orders = $query->paginate(20)->through(function ($order) {
            return [
                'id' => $order->id,
                'order_code' => $order->order_code,
                'user_id' => $order->user_id,
                'display_name' => $order->display_name,
                'display_email' => $order->display_email,
                'final_amount' => $order->final_amount,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'order_status' => $order->order_status,
                'created_at' => $order->created_at,
                'items' => $order->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_name' => $item->product_name,
                        'quantity' => $item->quantity,
                    ];
                }),
            ];
        });

        // Stats
        $stats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('order_status', 'pending')->count(),
            'processing_orders' => Order::where('order_status', 'processing')->count(),
            'completed_orders' => Order::where('order_status', 'completed')->count(),
            'cancelled_orders' => Order::where('order_status', 'cancelled')->count(),
            'pending_payment' => Order::where('payment_status', 'pending')->count(),
            'today_revenue' => Order::where('payment_status', 'paid')
                ->whereDate('created_at', today())
                ->sum('final_amount'),
        ];

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'order_status' => $orderStatus,
                'payment_status' => $paymentStatus,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'sort' => $sortBy,
                'order' => $sortOrder,
            ],
            'pageTitle' => 'Quản lý đơn hàng',
            'pageDescription' => 'Tổng quan tình hình đơn hàng',
        ]);
    }

    public function show($id)
    {
        $order = Order::with('user')->findOrFail($id);

        $orderItems = OrderItem::where('order_id', $id)
            ->with('product')
            ->get()
            ->map(function ($item) {
                $accountDelivered = null;
                if ($item->account_delivered) {
                    $accountDelivered = json_decode($item->account_delivered, true);
                }

                $customerAccountInfo = null;
                if ($item->customer_account_info) {
                    $customerAccountInfo = json_decode($item->customer_account_info, true);
                }

                return [
                    'id' => $item->id,
                    'product_name' => $item->product_name ?? $item->product?->name ?? 'Sản phẩm đã xóa',
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'total_price' => $item->total_price,
                    'image' => $item->product?->image ?? '',
                    'delivery_type' => $item->product?->delivery_type ?? 'account',
                    'account_delivered' => $accountDelivered,
                    'customer_account_info' => $customerAccountInfo,
                ];
            });

        $orderData = [
            'id' => $order->id,
            'order_code' => $order->order_code,
            'user_id' => $order->user_id,
            'display_name' => $order->user?->full_name ?? $order->user?->username ?? $order->customer_name ?? 'Khách',
            'display_email' => $order->user?->email ?? $order->customer_email,
            'display_phone' => $order->user?->phone ?? $order->customer_phone,
            'order_status' => $order->order_status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'total_amount' => $order->total_amount,
            'discount_amount' => $order->discount_amount,
            'final_amount' => $order->final_amount,
            'coupon_code' => $order->coupon_code,
            'customer_note' => $order->customer_note,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
        ];

        return Inertia::render('Admin/Orders/Show', [
            'order' => $orderData,
            'orderItems' => $orderItems,
            'pageTitle' => 'Chi tiết đơn hàng #' . $order->order_code,
            'pageDescription' => '',
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $request->validate([
            'order_status' => 'required|in:pending,processing,completed,cancelled',
            'payment_status' => 'required|in:pending,confirming,paid,failed,refunded',
        ]);

        $order->update([
            'order_status' => $request->order_status,
            'payment_status' => $request->payment_status,
        ]);

        // Auto-deliver accounts when payment is confirmed
        if ($request->payment_status === 'paid' && $order->payment_status !== 'paid') {
            $this->autoDeliverAccounts($order);
        }

        return redirect()->back()->with('success', 'Đã cập nhật trạng thái đơn hàng');
    }

    public function quickAction(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $action = $request->input('action');

        switch ($action) {
            case 'confirm_payment':
                $order->update([
                    'payment_status' => 'paid',
                    'order_status' => 'processing',
                ]);
                $this->autoDeliverAccounts($order);
                return redirect()->back()->with('success', 'Đã xác nhận thanh toán và giao tài khoản');

            case 'complete':
                $order->update(['order_status' => 'completed']);
                return redirect()->back()->with('success', 'Đã hoàn thành đơn hàng');

            case 'cancel':
                $order->update(['order_status' => 'cancelled']);
                return redirect()->back()->with('success', 'Đã hủy đơn hàng');

            default:
                return redirect()->back()->with('error', 'Hành động không hợp lệ');
        }
    }

    public function deliverAccount(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $orderItemId = $request->input('order_item_id');
        $deliveryType = $request->input('delivery_type');

        $orderItem = OrderItem::where('id', $orderItemId)
            ->where('order_id', $id)
            ->firstOrFail();

        if ($deliveryType === 'email_only') {
            $deliveryData = [
                'type' => 'email_only',
                'email' => $request->input('account_email'),
                'note' => $request->input('account_note', ''),
            ];
        } elseif ($deliveryType === 'customer_account') {
            $deliveryData = [
                'type' => 'customer_account',
                'note' => $request->input('account_note', ''),
            ];
        } else {
            $deliveryData = [
                'type' => 'account',
                'username' => $request->input('account_username'),
                'password' => $request->input('account_password'),
                'note' => $request->input('account_note', ''),
            ];
        }

        $orderItem->update([
            'account_delivered' => json_encode($deliveryData),
        ]);

        return redirect()->back()->with('success', 'Đã giao tài khoản thành công');
    }

    private function autoDeliverAccounts(Order $order)
    {
        $orderItems = OrderItem::where('order_id', $order->id)
            ->whereNull('account_delivered')
            ->get();

        foreach ($orderItems as $item) {
            $product = $item->product;
            if (!$product)
                continue;

            $deliveryType = $product->delivery_type ?? 'account';

            if ($deliveryType === 'account') {
                // Get account from stock
                $account = DB::table('accounts_stock')
                    ->where('product_id', $item->product_id)
                    ->where('status', 'available')
                    ->first();

                if ($account) {
                    $item->update([
                        'account_delivered' => json_encode([
                            'type' => 'account',
                            'username' => $account->account_username,
                            'password' => $account->account_password,
                            'note' => $account->account_note ?? '',
                        ]),
                    ]);

                    DB::table('accounts_stock')
                        ->where('id', $account->id)
                        ->update([
                            'status' => 'sold',
                            'sold_at' => now(),
                        ]);
                }
            }
        }
    }
}
