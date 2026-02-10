<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('chat_sessions')
            ->leftJoin('users', 'chat_sessions.user_id', '=', 'users.id')
            ->select(
                'chat_sessions.*',
                'users.username as user_name',
                'users.email as user_email'
            );

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('users.username', 'like', '%' . $request->search . '%')
                    ->orWhere('users.email', 'like', '%' . $request->search . '%')
                    ->orWhere('chat_sessions.guest_name', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->status && $request->status !== 'all') {
            $query->where('chat_sessions.status', $request->status);
        }

        $sessions = $query->orderByDesc('chat_sessions.last_message_at')->paginate(20)->withQueryString();

        // Add unread count and last message for each session
        $sessionIds = collect($sessions->items())->pluck('id');
        $unreadCounts = DB::table('chat_messages')
            ->whereIn('session_id', $sessionIds)
            ->where('sender_type', 'customer')
            ->where('is_read', false)
            ->selectRaw('session_id, COUNT(*) as unread_count')
            ->groupBy('session_id')
            ->pluck('unread_count', 'session_id');

        $lastMessages = DB::table('chat_messages')
            ->whereIn('session_id', $sessionIds)
            ->whereIn('id', function ($q) use ($sessionIds) {
                $q->selectRaw('MAX(id)')
                    ->from('chat_messages')
                    ->whereIn('session_id', $sessionIds)
                    ->groupBy('session_id');
            })
            ->get()
            ->keyBy('session_id');

        foreach ($sessions->items() as $session) {
            $session->unread_count = $unreadCounts[$session->id] ?? 0;
            $last = $lastMessages[$session->id] ?? null;
            $session->last_message = $last ? $last->message : null;
            $session->last_sender = $last ? $last->sender_type : null;
        }

        $stats = [
            'active' => DB::table('chat_sessions')->where('status', 'active')->count(),
            'closed' => DB::table('chat_sessions')->where('status', 'closed')->count(),
            'total_unread' => DB::table('chat_messages')->where('sender_type', 'customer')->where('is_read', false)->count(),
        ];

        return Inertia::render('Admin/Chats/Index', [
            'sessions' => $sessions,
            'filters' => $request->only(['search', 'status']),
            'stats' => $stats,
        ]);
    }

    public function show($id)
    {
        $session = DB::table('chat_sessions')
            ->leftJoin('users', 'chat_sessions.user_id', '=', 'users.id')
            ->select('chat_sessions.*', 'users.username as user_name', 'users.email as user_email')
            ->where('chat_sessions.id', $id)
            ->first();

        if (!$session) {
            abort(404);
        }

        // Mark customer messages as read
        DB::table('chat_messages')
            ->where('session_id', $id)
            ->where('sender_type', 'customer')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = DB::table('chat_messages')
            ->where('session_id', $id)
            ->orderBy('created_at')
            ->get();

        return Inertia::render('Admin/Chats/Show', [
            'session' => $session,
            'messages' => $messages,
        ]);
    }

    public function sendMessage(Request $request, $id)
    {
        $request->validate(['message' => 'required|string|max:2000']);

        DB::table('chat_messages')->insert([
            'session_id' => $id,
            'sender_type' => 'admin',
            'sender_name' => auth()->user()->username ?? 'Admin',
            'message' => $request->message,
            'is_read' => true,
            'created_at' => now(),
        ]);

        DB::table('chat_sessions')->where('id', $id)->update([
            'last_message_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back();
    }

    public function closeChat($id)
    {
        DB::table('chat_sessions')->where('id', $id)->update([
            'status' => 'closed',
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Phiên chat đã được đóng.');
    }

    public function reopenChat($id)
    {
        DB::table('chat_sessions')->where('id', $id)->update([
            'status' => 'active',
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Phiên chat đã được mở lại.');
    }

    public function pollMessages(Request $request, $id)
    {
        $afterId = $request->get('after_id', 0);

        $messages = DB::table('chat_messages')
            ->where('session_id', $id)
            ->where('id', '>', $afterId)
            ->orderBy('created_at')
            ->get();

        // Mark customer messages as read
        if ($messages->isNotEmpty()) {
            DB::table('chat_messages')
                ->where('session_id', $id)
                ->where('sender_type', 'customer')
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        return response()->json(['messages' => $messages]);
    }
}
