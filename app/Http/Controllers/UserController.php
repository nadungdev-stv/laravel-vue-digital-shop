<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function profile()
    {
        $user = Auth::user();
        $totalOrders = \App\Models\Order::where('user_id', $user->id)->count();
        $totalSpent = \App\Models\Order::where('user_id', $user->id)->where('payment_status', 'paid')->sum('final_amount');

        // Fetch notifications (with graceful handling if model doesn't exist)
        $notifications = [];
        $unreadNotificationCount = 0;

        if (class_exists(\App\Models\Notification::class)) {
            try {
                $notifications = \App\Models\Notification::where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();
                $unreadNotificationCount = \App\Models\Notification::where('user_id', $user->id)
                    ->where('is_read', false)
                    ->count();
            } catch (\Exception $e) {
                // Notifications table doesn't exist yet - gracefully handle
                $notifications = [];
                $unreadNotificationCount = 0;
            }
        }

        return Inertia::render('User/Profile', [
            'user' => $user,
            'stats' => [
                'total_orders' => $totalOrders,
                'total_spent' => $totalSpent,
                'reward_points' => $user->reward_points ?? 0,
            ],
            'notifications' => $notifications,
            'unreadNotificationCount' => $unreadNotificationCount,
        ]);
    }

    public function profile2()
    {
        $user = Auth::user();
        $totalOrders = \App\Models\Order::where('user_id', $user->id)->count();
        $totalSpent = \App\Models\Order::where('user_id', $user->id)->where('payment_status', 'paid')->sum('final_amount');

        $notifications = [];
        $unreadNotificationCount = 0;

        if (class_exists(\App\Models\Notification::class)) {
            try {
                $notifications = \App\Models\Notification::where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();
                $unreadNotificationCount = \App\Models\Notification::where('user_id', $user->id)
                    ->where('is_read', false)
                    ->count();
            } catch (\Exception $e) {
                $notifications = [];
                $unreadNotificationCount = 0;
            }
        }

        return Inertia::render('User/Profile2', [
            'user' => $user,
            'stats' => [
                'total_orders' => $totalOrders,
                'total_spent' => $totalSpent,
                'reward_points' => $user->reward_points ?? 0,
            ],
            'notifications' => $notifications,
            'unreadNotificationCount' => $unreadNotificationCount,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'full_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            // Email updates might require verification, for now stick to basic info as per original PHP file
            // The original PHP file allowed email update.
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->update($validated);

        return back()->with('success', 'Cập nhật thông tin thành công.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user = Auth::user();
        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        // Create notification for password change
        if (class_exists(\App\Models\Notification::class)) {
            try {
                \App\Models\Notification::createForUser(
                    $user->id,
                    'Thay đổi mật khẩu',
                    'Mật khẩu của bạn đã được thay đổi thành công',
                    'success'
                );
            } catch (\Exception $e) {
                // Silently fail if notifications table doesn't exist
            }
        }

        return back()->with('success', 'Đổi mật khẩu thành công.');
    }

    /**
     * Mark all notifications as read for the authenticated user.
     */
    public function markAllNotificationsRead(Request $request)
    {
        try {
            $user = Auth::user();
            \App\Models\Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Mark a single notification as read.
     */
    public function markNotificationRead(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $notification = \App\Models\Notification::where('user_id', $user->id)
                ->where('id', $id)
                ->firstOrFail();

            $notification->markAsRead();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 404);
        }
    }
}
