<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class VisitorController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'live');
        $sortBy = $request->get('sort', $tab === 'live' ? 'time' : 'created_at');
        $sortOrder = strtoupper($request->get('order', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        // Get total views all time
        $totalViews = DB::table('visitor_stats')->sum('access_count') ?? 0;
        $currentOnline = DB::table('online_users')->count();
        $totalViews += $currentOnline;

        if ($tab === 'live') {
            // Sort mapping for live tab
            $sortMap = [
                'ip' => 'ip_address',
                'ua' => 'user_agent',
                'time' => 'last_activity',
                'hits' => 'hits',
            ];
            $orderBy = $sortMap[$sortBy] ?? 'last_activity';

            // Get online users grouped by IP (Last 5 minutes)
            $onlineUsers = DB::table('online_users')
                ->select(
                    'ip_address',
                    DB::raw('MAX(user_agent) as user_agent'),
                    DB::raw('MAX(last_activity) as last_activity'),
                    DB::raw('COUNT(*) as hits')
                )
                ->groupBy('ip_address')
                ->orderBy($orderBy, $sortOrder)
                ->get();

            // Get visitors history (Last 7 days)
            $historyStats = DB::table('visitor_stats')
                ->where('date', '>=', DB::raw('DATE_SUB(CURDATE(), INTERVAL 7 DAY)'))
                ->orderByDesc('date')
                ->get();

            return Inertia::render('Admin/Visitors/Index', [
                'totalViews' => $totalViews,
                'tab' => $tab,
                'onlineUsers' => $onlineUsers,
                'historyStats' => $historyStats,
                'logs' => null,
                'filters' => $request->only(['sort', 'order']),
            ]);
        } else {
            // History Tab
            $searchIp = $request->get('search_ip', '');

            // Valid sortable columns for history
            $validHistSort = ['created_at', 'ip_address', 'request_uri', 'user_agent', 'hits'];
            $histSort = in_array($sortBy, $validHistSort) ? $sortBy : 'created_at';

            $query = DB::table('visitor_logs')
                ->select(
                    'ip_address',
                    DB::raw('COUNT(*) as hits'),
                    DB::raw('MAX(created_at) as created_at'),
                    DB::raw('MAX(request_uri) as request_uri'),
                    DB::raw('MAX(user_agent) as user_agent')
                )
                ->groupBy('ip_address');

            if ($searchIp) {
                $query->where('ip_address', 'like', '%' . $searchIp . '%');
            }

            $query->orderBy($histSort, $sortOrder);

            $logs = $query->paginate(20)->withQueryString();

            return Inertia::render('Admin/Visitors/Index', [
                'totalViews' => $totalViews,
                'tab' => $tab,
                'onlineUsers' => [],
                'historyStats' => [],
                'logs' => $logs,
                'filters' => $request->only(['search_ip', 'sort', 'order']),
            ]);
        }
    }
}
