<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class LogVisitor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Exclude admin routes, API, and assets from logging
        if ($request->is('admin*', 'api*', 'build*', 'storage*', 'vendor*', 'fonts*', 'images*')) {
            return $next($request);
        }

        try {
            $ip = $request->ip();
            $userAgent = $request->userAgent() ?: 'Unknown';
            $uri = $request->getRequestUri();

            // 1. Update Online Users
            // Use DB::table direct manipulation for speed (no model needed for simple pivot/log tables)

            // Get Session ID
            $sessionId = $request->hasSession() ? $request->session()->getId() : $ip;

            // Upsert mechanism for online users
            DB::table('online_users')->updateOrInsert(
                ['session_id' => $sessionId], // Primary Key
                [
                    'ip_address' => $ip,
                    'user_agent' => substr($userAgent, 0, 255),
                    'last_activity' => now()->timestamp // Store as integer timestamp
                ]
            );

            // Prune users inactive for > 5 minutes
            $timeout = now()->subMinutes(5)->timestamp;
            DB::table('online_users')
                ->where('last_activity', '<', $timeout)
                ->delete();

            // 2. Log Visit
            // To prevent spam, we can limit logging:
            // - Only log if not visited same URI in last 1 minute? 
            // - Or log everything?
            // For now, let's log everything but maybe throttle simple reloads?
            // Let's stick to simple logging first.

            DB::table('visitor_logs')->insert([
                'ip_address' => $ip,
                'user_agent' => substr($userAgent, 0, 255),
                'request_uri' => substr($uri, 0, 255),
                'created_at' => now(),
            ]);

            // 3. Update Daily Stats
            $today = now()->format('Y-m-d');

            // Upsert mechanism for daily stats
            $affected = DB::table('visitor_stats')
                ->where('date', $today)
                ->increment('access_count');

            if ($affected === 0) {
                // If no row updated, insert new one. 
                // Race condition possible but unlikely to be critical for stats
                try {
                    DB::table('visitor_stats')->insert([
                        'date' => $today,
                        'access_count' => 1
                    ]);
                } catch (\Exception $e) {
                    // If insert fails (duplicate key), retry increment
                    DB::table('visitor_stats')
                        ->where('date', $today)
                        ->increment('access_count');
                }
            }

        } catch (\Exception $e) {
            // Fail silently to not impact user experience if DB logging fails
            // Log::error('Visitor tracking error: ' . $e->getMessage());
        }

        return $next($request);
    }
}
