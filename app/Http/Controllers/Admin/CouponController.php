<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $query = Coupon::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->type && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Sorting
        $sortBy = $request->sort ?? 'created_at';
        $sortOrder = $request->order ?? 'DESC';
        $query->orderBy($sortBy, $sortOrder);

        $coupons = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => Coupon::count(),
            'active' => Coupon::where('status', 'active')->count(),
            'inactive' => Coupon::where('status', 'inactive')->count(),
            'expired' => Coupon::where('status', 'expired')->count(),
            'total_used' => (int) Coupon::sum('used_count'),
        ];

        // Top used coupons
        $topUsed = Coupon::where('used_count', '>', 0)
            ->orderByDesc('used_count')
            ->limit(5)
            ->get(['code', 'type', 'value', 'used_count', 'status']);

        return Inertia::render('Admin/Coupons/Index', [
            'coupons' => $coupons,
            'filters' => $request->only(['search', 'type', 'status', 'sort', 'order']),
            'stats' => $stats,
            'topUsed' => $topUsed,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code',
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['code'] = strtoupper($validated['code']);
        $validated['used_count'] = 0;

        if ($validated['type'] === 'percent' && $validated['value'] > 100) {
            $validated['value'] = 100;
        }

        Coupon::create($validated);

        return redirect()->back()->with('success', 'Mã giảm giá đã được tạo.');
    }

    public function update(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code,' . $coupon->id,
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['code'] = strtoupper($validated['code']);

        if ($validated['type'] === 'percent' && $validated['value'] > 100) {
            $validated['value'] = 100;
        }

        $coupon->update($validated);

        return redirect()->back()->with('success', 'Mã giảm giá đã được cập nhật.');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return redirect()->back()->with('success', 'Mã giảm giá đã được xóa.');
    }
}
