<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Filters
        $search = $request->get('search', '');
        $role = $request->get('role', '');
        $status = $request->get('status', '');

        // Sorting
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = strtoupper($request->get('order', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $allowedSortColumns = ['id', 'username', 'email', 'full_name', 'role', 'status', 'created_at', 'order_count', 'total_spent'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'created_at';
        }

        $query = User::query()
            ->selectRaw('users.*, 
                (SELECT COUNT(*) FROM orders WHERE user_id = users.id) as order_count,
                (SELECT COALESCE(SUM(final_amount), 0) FROM orders WHERE user_id = users.id AND payment_status = ?) as total_spent',
                ['paid']
            );

        // Apply filters
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('full_name', 'like', '%' . $search . '%');
            });
        }

        if ($role) {
            $query->where('role', $role);
        }

        if ($status) {
            $query->where('status', $status);
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortOrder);

        $users = $query->paginate(20)->withQueryString();

        // Stats
        $stats = [
            'total' => User::count(),
            'customers' => User::where('role', 'customer')->count(),
            'admins' => User::where('role', 'admin')->count(),
            'active' => User::where('status', 'active')->count(),
        ];

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'stats' => $stats,
            'filters' => $request->only(['search', 'role', 'status', 'sort', 'order']),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:customer,admin',
            'status' => 'required|in:active,inactive',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $user->role = $validated['role'];
        $user->status = $validated['status'];
        $user->save();

        return redirect()->back()->with('success', 'Đã cập nhật người dùng');
    }

    public function updateBalance(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:add,subtract',
            'amount' => 'required|numeric|min:1000',
            'note' => 'required|string|max:500',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $oldBalance = $user->balance;

        if ($validated['type'] === 'add') {
            $user->balance += $validated['amount'];
        } else {
            $user->balance -= $validated['amount'];
        }

        $user->save();

        // Log transaction
        WalletTransaction::create([
            'user_id' => $user->id,
            'type' => $validated['type'] === 'add' ? 'admin_add' : 'admin_subtract',
            'amount' => $validated['amount'],
            'balance_before' => $oldBalance,
            'balance_after' => $user->balance,
            'description' => $validated['note'],
            'status' => 'completed',
        ]);

        return redirect()->back()->with('success', 'Đã cập nhật số dư thành công');
    }

    public function show(User $user)
    {
        // Get user orders
        $orders = $user->orders()
            ->select('id', 'order_code', 'final_amount', 'order_status', 'payment_status', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Get user transactions
        $transactions = WalletTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Get login history if table exists
        $loginHistory = [];
        try {
            $loginHistory = \DB::table('user_logins')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();
        } catch (\Exception $e) {
            // Table may not exist
        }

        // Get statistics
        $stats = [
            'total_orders' => $user->orders()->count(),
            'total_spent' => $user->orders()->where('payment_status', 'paid')->sum('final_amount'),
            'completed_orders' => $user->orders()->where('order_status', 'completed')->count(),
            'pending_orders' => $user->orders()->where('payment_status', 'pending')->count(),
            'total_reviews' => \DB::table('reviews')->where('user_id', $user->id)->count(),
        ];

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
            'orders' => $orders,
            'transactions' => $transactions,
            'loginHistory' => $loginHistory,
            'stats' => $stats,
        ]);
    }

    public function destroy(User $user)
    {
        // Prevent deleting self
        if (auth()->id() === $user->id) {
            return back()->withErrors(['error' => 'Bạn không thể xóa chính mình.']);
        }

        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'Thành viên đã được xóa.');
    }
}
