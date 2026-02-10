<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PublicChatController extends Controller
{
    public function startSession(Request $request)
    {
        $user = auth()->user();

        // Validation for guests
        if (!$user) {
            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
            }
        }

        $userId = $user ? $user->id : null;
        $guestName = $request->name;
        $guestEmail = $request->email;
        $sessionId = $request->session_id;

        // Check for existing active session
        if ($sessionId) {
            $session = DB::table('chat_sessions')
                ->where('id', $sessionId)
                ->where('status', 'active')
                ->first();

            if ($session) {
                // Verify ownership if user is logged in
                if ($userId && $session->user_id && $session->user_id != $userId) {
                    // Session belongs to another user, so we ignore it and continue to find/create own session
                } else {
                    return response()->json(['success' => true, 'session' => $session]);
                }
            }
        }

        // If logged in, check for existing active session for this user
        if ($userId) {
            $session = DB::table('chat_sessions')
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->orderByDesc('created_at')
                ->first();

            if ($session) {
                return response()->json(['success' => true, 'session' => $session]);
            }
        }

        // Create new session
        $sessionId = DB::table('chat_sessions')->insertGetId([
            'user_id' => $userId,
            'guest_name' => $userId ? $user->name : ($guestName ?: 'Guest'),
            'guest_email' => $userId ? $user->email : $guestEmail,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
            'last_message_at' => now(),
        ]);

        $session = DB::table('chat_sessions')->where('id', $sessionId)->first();

        // Send greeting message if it's a new session
        $existingMessages = DB::table('chat_messages')->where('session_id', $sessionId)->count();

        if ($existingMessages == 0) {
            $displayName = '';
            if ($user && $user->username) {
                $words = preg_split('/\s+/', $user->username);
                $displayName = ' ' . (count($words) === 1 ? $words[0] : end($words));
            } elseif ($guestName && $guestName !== 'Guest' && $guestName !== 'Khách') {
                $words = preg_split('/\s+/', $guestName);
                $displayName = ' ' . (count($words) === 1 ? $words[0] : end($words));
            }

            $greetingMessage = "Xin chào{$displayName}! 👋\n\nMình là trợ lý tư vấn của Veyrix. Bạn muốn được trợ giúp về vấn đề gì?";

            DB::table('chat_messages')->insert([
                'session_id' => $sessionId,
                'sender_type' => 'admin',
                'sender_name' => 'Dũng', // Or 'Admin'
                'message' => $greetingMessage,
                'is_read' => false,
                'created_at' => now(),
            ]);

            // Update session last_message_at
            DB::table('chat_sessions')->where('id', $sessionId)->update(['last_message_at' => now()]);
        }

        return response()->json(['success' => true, 'session' => $session]);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:chat_sessions,id',
            'message' => 'nullable|string',
            'image' => 'nullable|image|max:5120', // 5MB max
        ]);

        if (!$request->message && !$request->image) {
            return response()->json(['success' => false, 'message' => 'Message or image required'], 422);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('chat-images', 'public');
            $imagePath = '/storage/' . $path;
        }

        $user = auth()->user();
        $senderName = $user ? ($user->username ?? $user->name) : ($request->sender_name ?? 'Guest');

        $messageId = DB::table('chat_messages')->insertGetId([
            'session_id' => $request->session_id,
            'sender_type' => 'customer',
            'sender_name' => $senderName,
            'message' => $request->message ?? ($imagePath ? '[Hình ảnh]' : ''),
            'image' => $imagePath,
            'is_read' => false,
            'created_at' => now(),
        ]);

        DB::table('chat_sessions')->where('id', $request->session_id)->update([
            'last_message_at' => now(),
            'updated_at' => now(),
        ]);

        // TODO: Integrate Telegram notification if needed (copied from PHP project)

        return response()->json(['success' => true, 'message_id' => $messageId, 'image_url' => $imagePath]);
    }

    public function getMessages(Request $request)
    {
        $sessionId = $request->session_id;
        $after = $request->after; // Timestamp or ID

        $query = DB::table('chat_messages')
            ->where('session_id', $sessionId)
            ->orderBy('created_at', 'asc');

        if ($after) {
            $query->where('created_at', '>', $after);
        }

        $messages = $query->get();

        return response()->json(['success' => true, 'messages' => $messages]);
    }
}
