<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('notifications')
            ->leftJoin('users', 'notifications.user_id', '=', 'users.id')
            ->select(
                'notifications.*',
                DB::raw('COALESCE(users.full_name, users.username) as user_name'),
                'users.email as user_email'
            );

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('notifications.title', 'like', '%' . $request->search . '%')
                    ->orWhere('notifications.content', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->status === 'read') {
            $query->where('notifications.is_read', true);
        } elseif ($request->status === 'unread') {
            $query->where('notifications.is_read', false);
        }

        // Sorting
        $sort = $request->get('sort', 'created_at');
        $order = strtoupper($request->get('order', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $allowedSortColumns = ['title', 'type', 'created_at', 'is_read'];
        if (!in_array($sort, $allowedSortColumns)) {
            $sort = 'created_at';
        }

        $notifications = $query->orderBy('notifications.' . $sort, $order)
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => DB::table('notifications')->count(),
            'unread' => DB::table('notifications')->where('is_read', false)->count(),
        ];

        $users = DB::table('users')->where('status', 'active')
            ->select('id', DB::raw('COALESCE(full_name, username) as name'), 'email')
            ->orderBy(DB::raw('COALESCE(full_name, username)'))
            ->get();

        return Inertia::render('Admin/Notifications/Index', [
            'notifications' => $notifications,
            'filters' => $request->only(['search', 'status', 'sort', 'order']),
            'stats' => $stats,
            'users' => $users,
        ]);
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:1000',
            'type' => 'required|in:info,success,warning,error',
            'target_type' => 'required|in:all,single',
            'user_id' => 'required_if:target_type,single|nullable|exists:users,id',
            'link' => 'nullable|string|max:255',
        ]);

        if ($validated['target_type'] === 'all') {
            $users = DB::table('users')->where('status', 'active')->pluck('id');
            $records = $users->map(fn($userId) => [
                'user_id' => $userId,
                'title' => $validated['title'],
                'content' => $validated['content'],
                'type' => $validated['type'],
                'link' => $validated['link'] ?? null,
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ])->toArray();

            DB::table('notifications')->insert($records);
        } else {
            DB::table('notifications')->insert([
                'user_id' => $validated['user_id'],
                'title' => $validated['title'],
                'content' => $validated['content'],
                'type' => $validated['type'],
                'link' => $validated['link'] ?? null,
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->back()->with('success', 'Thông báo đã được gửi.');
    }

    public function destroy($id)
    {
        DB::table('notifications')->where('id', $id)->delete();

        return redirect()->back()->with('success', 'Thông báo đã được xóa.');
    }
}
