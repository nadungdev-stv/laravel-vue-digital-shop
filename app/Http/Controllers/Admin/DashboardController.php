<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // Fetch basic stats
        $totalOrders = Order::count();
        $totalRevenue = Order::where('payment_status', 'paid')->sum('final_amount');
        $totalUsers = User::count();
        $totalProducts = Product::count();

        // Recent orders
        $recentOrders = Order::with('user')
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue,
                'total_users' => $totalUsers,
                'total_products' => $totalProducts,
            ],
            'recentOrders' => $recentOrders,
        ]);
    }
}
