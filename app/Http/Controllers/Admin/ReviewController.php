<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('reviews')
            ->join('products', 'reviews.product_id', '=', 'products.id')
            ->leftJoin('users', 'reviews.user_id', '=', 'users.id')
            ->leftJoin('orders', 'reviews.order_id', '=', 'orders.id')
            ->select(
                'reviews.*',
                'products.name as product_name',
                DB::raw("REPLACE(products.image, '/public/', '/') as product_image"),
                DB::raw('COALESCE(users.full_name, users.username) as user_name'),
                'users.email as user_email',
                'orders.order_code'
            );

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('reviews.comment', 'like', '%' . $request->search . '%')
                    ->orWhere('users.full_name', 'like', '%' . $request->search . '%')
                    ->orWhere('users.username', 'like', '%' . $request->search . '%')
                    ->orWhere('products.name', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->status && $request->status !== 'all') {
            $query->where('reviews.status', $request->status);
        }

        if ($request->rating) {
            $query->where('reviews.rating', $request->rating);
        }

        if ($request->product_id) {
            $query->where('reviews.product_id', $request->product_id);
        }

        // Sorting
        $sort = $request->sort ?? 'newest';
        $sortMap = [
            'newest' => ['reviews.created_at', 'DESC'],
            'oldest' => ['reviews.created_at', 'ASC'],
            'rating_desc' => ['reviews.rating', 'DESC'],
            'rating_asc' => ['reviews.rating', 'ASC'],
        ];
        [$sortColumn, $sortOrder] = $sortMap[$sort] ?? ['reviews.created_at', 'DESC'];

        $reviews = $query->orderBy($sortColumn, $sortOrder)->paginate(10)->withQueryString();

        $stats = [
            'total' => DB::table('reviews')->count(),
            'pending' => DB::table('reviews')->where('status', 'pending')->count(),
            'approved' => DB::table('reviews')->where('status', 'approved')->count(),
            'rejected' => DB::table('reviews')->where('status', 'rejected')->count(),
            'avg_rating' => DB::table('reviews')->where('status', 'approved')->avg('rating') ?: 0,
            'today' => DB::table('reviews')->whereDate('created_at', today())->count(),
        ];

        // Top reviewed products
        $topReviewed = DB::table('reviews')
            ->join('products', 'reviews.product_id', '=', 'products.id')
            ->where('reviews.status', 'approved')
            ->select('products.id', 'products.name', DB::raw('COUNT(*) as review_count'), DB::raw('AVG(reviews.rating) as avg_rating'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('review_count')
            ->limit(5)
            ->get();

        // Products for filter
        $products = DB::table('products')->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $reviews,
            'filters' => $request->only(['search', 'status', 'rating', 'product_id', 'sort']),
            'stats' => $stats,
            'topReviewed' => $topReviewed,
            'products' => $products,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:approved,rejected']);

        DB::table('reviews')->where('id', $id)->update([
            'status' => $request->status,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Trạng thái đánh giá đã được cập nhật.');
    }

    public function reply(Request $request, $id)
    {
        $request->validate(['admin_reply' => 'required|string|max:1000']);

        DB::table('reviews')->where('id', $id)->update([
            'admin_reply' => $request->admin_reply,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Đã gửi phản hồi.');
    }

    public function destroy($id)
    {
        DB::table('reviews')->where('id', $id)->delete();

        return redirect()->back()->with('success', 'Đánh giá đã được xóa.');
    }
}
