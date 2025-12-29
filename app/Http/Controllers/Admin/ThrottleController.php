<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ThrottleEvent;

class ThrottleController extends Controller
{
    public function index(Request $request)
    {
        $window = $request->query('window', 'hour');

        switch ($window) {
            case 'day':
                $since = now()->subDay();
                break;
            case 'week':
                $since = now()->subWeek();
                break;
            case 'hour':
            default:
                $since = now()->subHour();
                break;
        }

        // Apply optional filters from query params
        $query = ThrottleEvent::query()->where('created_at', '>=', $since);

        if ($ip = $request->query('ip')) {
            $query->where('ip', $ip);
        }

        if ($start = $request->query('start')) {
            $query->where('created_at', '>=', $start);
        }

        if ($end = $request->query('end')) {
            $query->where('created_at', '<=', $end);
        }

        $events = $query->orderBy('created_at', 'desc')->limit(500)->get();

        // Aggregate counts by ip
        $byIp = $events->groupBy('ip')->map(function ($group) {
            return ['count' => $group->count(), 'emails' => $group->pluck('email')->unique()->values()];
        });

        return view('admin.throttles', [
            'events' => $events,
            'byIp' => $byIp,
            'window' => $window,
        ]);
    }

    // Export CSV
    public function exportCsv(Request $request)
    {
        $window = $request->query('window', 'hour');

        switch ($window) {
            case 'day':
                $since = now()->subDay();
                break;
            case 'week':
                $since = now()->subWeek();
                break;
            case 'hour':
            default:
                $since = now()->subHour();
                break;
        }

        // Apply optional filters for export
        $query = ThrottleEvent::query()->where('created_at', '>=', $since);
        if ($ip = $request->query('ip')) {
            $query->where('ip', $ip);
        }
        if ($start = $request->query('start')) {
            $query->where('created_at', '>=', $start);
        }
        if ($end = $request->query('end')) {
            $query->where('created_at', '<=', $end);
        }

        $events = $query->orderBy('created_at', 'desc')->get();

        $csv = "time,ip,email,throttle_key\n";
        foreach ($events as $e) {
            $csv .= sprintf("%s,%s,%s,%s\n", $e->created_at->toDateTimeString(), $e->ip, $e->email, $e->throttle_key);
        }

        $filename = "throttle_events_{$window}_" . now()->format('Ymd_His') . ".csv";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // Chart data endpoint
    public function data(Request $request)
    {
        $window = $request->query('window', 'hour');

        switch ($window) {
            case 'day':
                $since = now()->subDay();
                $groupFormat = 'H:00';
                $period = 'hour';
                break;
            case 'week':
                $since = now()->subWeek();
                $groupFormat = 'Y-m-d';
                $period = 'day';
                break;
            case 'hour':
            default:
                $since = now()->subHour();
                $groupFormat = 'H:i';
                $period = 'minute';
                break;
        }

        // Allow filtering by IP for the chart data
        $query = ThrottleEvent::query()->where('created_at', '>=', $since);
        if ($ip = $request->query('ip')) {
            $query->where('ip', $ip);
        }
        $events = $query->get();

        $grouped = $events->groupBy(function ($e) use ($groupFormat) {
            return $e->created_at->format($groupFormat);
        })->map(function ($g) { return $g->count(); });

        // Prepare labels for the window even if there are no events
        $labels = [];
        $counts = [];

        $cursor = $since->copy();
        $now = now();

        while ($cursor <= $now) {
            $label = $cursor->format($groupFormat);
            $labels[] = $label;
            $counts[] = $grouped->get($label, 0);
            if ($period === 'minute') {
                $cursor->addMinute();
            } elseif ($period === 'hour') {
                $cursor->addHour();
            } else {
                $cursor->addDay();
            }
        }

        // Top IP summary
        $byIp = $events->groupBy('ip')->map(function ($g) { return $g->count(); })->sortDesc()->take(10);

        return response()->json(['labels' => $labels, 'counts' => $counts, 'top_ips' => $byIp]);
    }

    // Aggregated metrics endpoint
    public function aggregated(Request $request)
    {
        $window = $request->query('window', 'hour');

        switch ($window) {
            case 'day':
                $since = now()->subDay();
                $groupBy = 'H';
                break;
            case 'week':
                $since = now()->subWeek();
                $groupBy = 'Y-m-d';
                break;
            case 'hour':
            default:
                $since = now()->subHour();
                $groupBy = 'H:i';
                break;
        }

        $query = ThrottleEvent::query()->where('created_at', '>=', $since);

        $total = $query->count();

        $byIp = $query->get()->groupBy('ip')->map(function ($g) { return $g->count(); })->sortDesc()->take(10);

        $distinctAccounts = $query->distinct('email')->pluck('email')->filter()->unique()->count();

        // grouped counts
        $grouped = $query->get()->groupBy(function ($e) use ($groupBy) {
            return $e->created_at->format($groupBy);
        })->map(function ($g) { return $g->count(); });

        return response()->json([
            'total_events' => $total,
            'top_ips' => $byIp,
            'distinct_accounts' => $distinctAccounts,
            'grouped' => $grouped,
        ]);
    }
}
