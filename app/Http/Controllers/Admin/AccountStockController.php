<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountStock;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountStockController extends Controller
{
    public function index(Request $request)
    {
        // Filters
        $search = $request->get('search', '');
        $productId = $request->get('product', 0);
        $status = $request->get('status', '');
        $dateFrom = $request->get('date_from', '');
        $dateTo = $request->get('date_to', '');
        $perPage = $request->get('per_page', 50);

        // Sorting
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = strtoupper($request->get('order', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $allowedSortColumns = ['id', 'product_name', 'account_username', 'account_note', 'status', 'created_at'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'created_at';
        }

        $query = AccountStock::query()
            ->join('products as p', 'accounts_stock.product_id', '=', 'p.id')
            ->select('accounts_stock.*', 'p.name as product_name');

        // Apply filters
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('accounts_stock.account_username', 'like', '%' . $search . '%')
                    ->orWhere('accounts_stock.account_password', 'like', '%' . $search . '%')
                    ->orWhere('accounts_stock.account_note', 'like', '%' . $search . '%');
            });
        }

        if ($productId) {
            $query->where('accounts_stock.product_id', $productId);
        }

        if ($status) {
            $query->where('accounts_stock.status', $status);
        }

        if ($dateFrom) {
            $query->whereDate('accounts_stock.created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('accounts_stock.created_at', '<=', $dateTo);
        }

        // Apply sorting
        if ($sortBy === 'product_name') {
            $query->orderBy('p.name', $sortOrder);
        } else {
            $query->orderBy('accounts_stock.' . $sortBy, $sortOrder);
        }

        $accounts = $query->paginate($perPage)->withQueryString();

        // Stats
        $stats = [
            'total' => AccountStock::count(),
            'available' => AccountStock::where('status', 'available')->count(),
            'sold' => AccountStock::where('status', 'sold')->count(),
            'reserved' => AccountStock::where('status', 'reserved')->count(),
        ];

        // Product Stats
        $productStats = \DB::table('accounts_stock as a')
            ->join('products as p', 'a.product_id', '=', 'p.id')
            ->select('p.id', 'p.name')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN a.status = "available" THEN 1 ELSE 0 END) as available')
            ->selectRaw('SUM(CASE WHEN a.status = "sold" THEN 1 ELSE 0 END) as sold')
            ->selectRaw('SUM(CASE WHEN a.status = "reserved" THEN 1 ELSE 0 END) as reserved')
            ->groupBy('a.product_id', 'p.id', 'p.name')
            ->orderByRaw('available ASC, p.name')
            ->get();

        // Low stock products (< 5 available)
        $lowStockProducts = $productStats->filter(function ($stat) {
            return $stat->available < 5 && $stat->available > 0;
        })->values();

        // Daily stats (last 7 days)
        $dailyStats = \DB::table('accounts_stock')
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = "available" THEN 1 ELSE 0 END) as available')
            ->selectRaw('SUM(CASE WHEN status = "sold" THEN 1 ELSE 0 END) as sold')
            ->whereRaw('created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')
            ->groupBy(\DB::raw('DATE(created_at)'))
            ->orderByDesc('date')
            ->get();

        // All products for dropdown
        $products = Product::where('delivery_type', 'account')
            ->orderBy('name')
            ->select('id', 'name')
            ->get();

        return Inertia::render('Admin/Accounts/Index', [
            'accounts' => $accounts,
            'stats' => $stats,
            'productStats' => $productStats,
            'dailyStats' => $dailyStats,
            'lowStockProducts' => $lowStockProducts,
            'products' => $products,
            'filters' => $request->only(['search', 'product', 'status', 'date_from', 'date_to', 'per_page', 'sort', 'order']),
        ]);
    }

    public function inlineUpdate(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts_stock,id',
            'field' => 'required|in:status,account_note',
            'value' => 'required',
        ]);

        $account = AccountStock::findOrFail($validated['account_id']);
        $account->{$validated['field']} = $validated['value'];
        $account->save();

        return redirect()->back()->with('success', 'Đã cập nhật');
    }

    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:delete,mark_available,mark_reserved',
            'selected_accounts' => 'required|array|min:1',
        ]);

        $ids = $validated['selected_accounts'];

        switch ($validated['action']) {
            case 'delete':
                AccountStock::whereIn('id', $ids)->delete();
                return redirect()->back()->with('success', 'Đã xóa ' . count($ids) . ' tài khoản');

            case 'mark_available':
                AccountStock::whereIn('id', $ids)->update(['status' => 'available']);
                return redirect()->back()->with('success', 'Đã cập nhật ' . count($ids) . ' tài khoản thành "Sẵn sàng"');

            case 'mark_reserved':
                AccountStock::whereIn('id', $ids)->update(['status' => 'reserved']);
                return redirect()->back()->with('success', 'Đã cập nhật ' . count($ids) . ' tài khoản thành "Đang giữ"');
        }
    }

    public function bulkExport(Request $request)
    {
        $ids = $request->selected_accounts;

        $accounts = AccountStock::with('product')
            ->whereIn('id', $ids)
            ->get();

        $filename = 'accounts_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename={$filename}");

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Sản phẩm', 'Username', 'Password', 'Ghi chú', 'Trạng thái', 'Ngày tạo']);

        foreach ($accounts as $acc) {
            fputcsv($output, [
                $acc->id,
                $acc->product->name,
                $acc->account_username,
                $acc->account_password,
                $acc->account_note,
                $acc->status,
                $acc->created_at,
            ]);
        }

        fclose($output);
        exit;
    }

    public function exportAll()
    {
        $accounts = AccountStock::with('product')->orderByDesc('created_at')->get();

        $filename = 'all_accounts_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename={$filename}");

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Sản phẩm', 'Username', 'Password', 'Ghi chú', 'Trạng thái', 'Ngày tạo', 'Ngày cập nhật']);

        foreach ($accounts as $acc) {
            fputcsv($output, [
                $acc->id,
                $acc->product->name,
                $acc->account_username,
                $acc->account_password,
                $acc->account_note,
                $acc->status,
                $acc->created_at,
                $acc->updated_at,
            ]);
        }

        fclose($output);
        exit;
    }

    public function create()
    {
        return Inertia::render('Admin/Accounts/Create', [
            'products' => Product::where('delivery_type', 'account')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'account_username' => 'required|string|max:255',
            'account_password' => 'required|string|max:255',
            'account_note' => 'nullable|string|max:500',
        ]);

        $validated['status'] = 'available';
        AccountStock::create($validated);

        Product::where('id', $validated['product_id'])->increment('stock_quantity');

        return redirect()->route('admin.accounts.index')->with('success', 'Tài khoản đã được thêm.');
    }

    public function import()
    {
        return Inertia::render('Admin/Accounts/Import', [
            'products' => Product::where('delivery_type', 'account')->orderBy('name')->get(),
        ]);
    }

    public function importPost(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'accounts_data' => 'required|string',
        ]);

        $lines = array_filter(explode("\n", trim($request->accounts_data)));
        $count = 0;

        foreach ($lines as $line) {
            $parts = explode('|', trim($line));
            if (count($parts) >= 2) {
                AccountStock::create([
                    'product_id' => $request->product_id,
                    'account_username' => trim($parts[0]),
                    'account_password' => trim($parts[1]),
                    'account_note' => trim($parts[2] ?? ''),
                    'status' => 'available',
                ]);
                $count++;
            }
        }

        if ($count > 0) {
            Product::where('id', $request->product_id)->increment('stock_quantity', $count);
        }

        return redirect()->route('admin.accounts.index')->with('success', "Đã nhập {$count} tài khoản.");
    }

    public function destroy($id)
    {
        $account = AccountStock::findOrFail($id);

        if ($account->status === 'available') {
            Product::where('id', $account->product_id)->decrement('stock_quantity');
        }

        $account->delete();

        return redirect()->back()->with('success', 'Tài khoản đã được xóa.');
    }
}
