<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class LayoutController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->input('sort', 'sort_order');
        $order = $request->input('order', 'ASC');

        $categories = Category::withCount([
            'products' => function ($q) {
                $q->where('status', 'active');
            }
        ])->orderBy($sort, $order)->get();

        $banners = Banner::orderBy('sort_order')->get();

        $promoBanners = json_decode(Setting::getValue('promo_banners', '[]'), true) ?: [];

        return Inertia::render('Admin/Layout/Index', [
            'categories' => $categories,
            'mainBanners' => $banners,
            'promoBanners' => $promoBanners,
            'filters' => $request->only(['sort', 'order']),
        ]);
    }

    public function updateCategoryOrder(Request $request)
    {
        $request->validate(['items' => 'required|array']);

        foreach ($request->items as $item) {
            Category::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return redirect()->back()->with('success', 'Đã cập nhật thứ tự danh mục.');
    }

    public function updateBannerOrder(Request $request)
    {
        $request->validate(['items' => 'required|array']);

        foreach ($request->items as $item) {
            Banner::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return redirect()->back()->with('success', 'Đã cập nhật thứ tự banner.');
    }

    public function createBanner(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'link' => 'nullable|string|max:255',
            'image' => 'required|image|max:5120',
            'status' => 'required|in:active,inactive',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('banners', 'public');
            $validated['image'] = '/storage/' . $path;
        }

        $validated['sort_order'] = (Banner::max('sort_order') ?? 0) + 1;

        Banner::create($validated);

        return redirect()->back()->with('success', 'Banner đã được tạo.');
    }

    public function updateBanner(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'link' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:5120',
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($request->hasFile('image')) {
            if ($banner->image && str_starts_with($banner->image, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $banner->image));
            }
            $path = $request->file('image')->store('banners', 'public');
            $validated['image'] = '/storage/' . $path;
        } else {
            // Don't update image field if no new file uploaded
            unset($validated['image']);
        }

        $banner->update($validated);

        return redirect()->back()->with('success', 'Banner đã được cập nhật.');
    }

    public function deleteBanner($id)
    {
        $banner = Banner::findOrFail($id);

        if ($banner->image && str_starts_with($banner->image, '/storage/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $banner->image));
        }

        $banner->delete();

        return redirect()->back()->with('success', 'Banner đã được xóa.');
    }
}
