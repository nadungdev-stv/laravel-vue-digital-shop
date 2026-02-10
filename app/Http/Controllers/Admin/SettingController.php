<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('setting_value', 'setting_key')->toArray();

        $systemStats = [
            'total_users' => User::count(),
            'total_products' => Product::count(),
            'total_orders' => DB::table('orders')->count(),
            'total_revenue' => DB::table('orders')->where('payment_status', 'paid')->sum('total_amount'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
            'systemStats' => $systemStats,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->except('_token');

        foreach ($data as $key => $value) {
            Setting::setValue($key, $value);
        }

        return redirect()->back()->with('success', 'Cài đặt đã được lưu.');
    }
}
