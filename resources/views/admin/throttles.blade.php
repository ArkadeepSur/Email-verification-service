@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-xl font-bold mb-4">Throttle Events</h1>

    <div class="mb-4">
        <form method="GET" action="{{ route('admin.throttles') }}">
            <select name="window" onchange="this.form.submit()" class="border p-2">
                <option value="hour" {{ $window === 'hour' ? 'selected' : '' }}>Last hour</option>
                <option value="day" {{ $window === 'day' ? 'selected' : '' }}>Last day</option>
                <option value="week" {{ $window === 'week' ? 'selected' : '' }}>Last week</option>
            </select>
        </form>
    </div>

    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="font-semibold">Summary by IP</h2>
        </div>
        <div class="flex items-center">
            <form id="exportForm" method="GET" action="{{ route('admin.throttles.export') }}" class="flex items-center">
                <input type="hidden" name="window" value="{{ $window }}">
                <input type="text" name="ip" placeholder="Filter by IP" class="border p-2 mr-2" value="{{ request('ip') }}">
                <input type="datetime-local" name="start" class="border p-2 mr-2" value="{{ request('start') }}">
                <input type="datetime-local" name="end" class="border p-2 mr-2" value="{{ request('end') }}">
                <button type="submit" class="bg-blue-600 text-white px-3 py-2 rounded mr-4">Export CSV</button>
            </form>

            <select id="chart-window" name="window" onchange="window.location='?window='+this.value" class="border p-2">
                <option value="hour" {{ $window === 'hour' ? 'selected' : '' }}>Last hour</option>
                <option value="day" {{ $window === 'day' ? 'selected' : '' }}>Last day</option>
                <option value="week" {{ $window === 'week' ? 'selected' : '' }}>Last week</option>
            </select>
        </div>
    </div>

    <div class="mb-6">
        <div class="flex items-center justify-between mb-2">
            <div class="font-medium">Throttle events chart</div>
            <div>
                <button id="exportChartBtn" class="bg-gray-600 text-white px-3 py-1 rounded mr-2">Export PNG</button>
                <button id="resetZoomBtn" class="bg-gray-200 text-gray-800 px-3 py-1 rounded">Reset Zoom</button>
            </div>
        </div>
        <canvas id="throttleChart" width="800" height="200"></canvas>
    </div>
    <div class="mb-6">
        <h3 class="font-semibold mb-2">Top IPs (sparkline)</h3>
        <div class="grid grid-cols-2 gap-4">
            @foreach ($byIp as $ip => $meta)
                <div class="p-3 border rounded bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-medium">{{ $ip }}</div>
                            <div class="text-sm text-gray-600">{{ $meta['count'] }} events</div>
                        </div>
                        <canvas class="sparkline" data-ip="{{ $ip }}" width="120" height="40"></canvas>
                    </div>
                    <div class="text-xs text-gray-500 mt-2">{{ $meta['emails']->implode(', ') }}</div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="mb-6">
        <table class="w-full table-auto border-collapse">
            <thead>
                <tr class="text-left border-b">
                    <th class="py-2">IP</th>
                    <th class="py-2">Events</th>
                    <th class="py-2">Affected Emails</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($byIp as $ip => $meta)
                    <tr class="border-b">
                        <td class="py-2">{{ $ip }}</td>
                        <td class="py-2">{{ $meta['count'] }}</td>
                        <td class="py-2">{{ $meta['emails']->implode(', ') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <h2 class="font-semibold mb-2">Recent Events</h2>
    <table class="w-full table-auto border-collapse">
        <thead>
            <tr class="text-left border-b">
                <th class="py-2">Time</th>
                <th class="py-2">IP</th>
                <th class="py-2">Email</th>
                <th class="py-2">Key</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($events as $e)
                <tr class="border-b">
                    <td class="py-2">{{ $e->created_at->toDateTimeString() }}</td>
                    <td class="py-2">{{ $e->ip }}</td>
                    <td class="py-2">{{ $e->email }}</td>
                    <td class="py-2">{{ $e->throttle_key }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@1.2.1/dist/chartjs-plugin-zoom.min.js"></script>
    <script>
        let mainChart = null;

        async function fetchChart() {
            const res = await fetch('{{ route('admin.throttles.data') }}?window={{ $window }}');
            const json = await res.json();
            const ctx = document.getElementById('throttleChart').getContext('2d');

            if (mainChart) { mainChart.destroy(); }

            mainChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: json.labels,
                    datasets: [{
                        label: 'Throttle events',
                        data: json.counts,
                        borderColor: 'rgba(59,130,246,1)',
                        backgroundColor: 'rgba(59,130,246,0.08)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 2,
                        pointHoverRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: { mode: 'index', intersect: false },
                        zoom: {
                            pan: { enabled: true, mode: 'x' },
                            zoom: { enabled: true, mode: 'x' }
                        }
                    },
                    scales: {
                        x: { display: true },
                        y: { display: true, beginAtZero: true }
                    }
                }
            });
        }

        document.getElementById('exportChartBtn').addEventListener('click', function () {
            if (!mainChart) return;
            const base64 = mainChart.toBase64Image();
            const a = document.createElement('a');
            a.href = base64;
            a.download = 'throttle_chart_{{ date('Ymd_His') }}.png';
            document.body.appendChild(a);
            a.click();
            a.remove();
        });

        document.getElementById('resetZoomBtn').addEventListener('click', function () {
            if (!mainChart) return;
            mainChart.resetZoom();
        });

        async function fetchSparkline(canvas) {
            const ip = canvas.dataset.ip;
            const res = await fetch('{{ route('admin.throttles.data') }}?window={{ $window }}&ip=' + encodeURIComponent(ip));
            const json = await res.json();
            const ctx = canvas.getContext('2d');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: json.labels,
                    datasets: [{
                        data: json.counts,
                        borderColor: 'rgba(59,130,246,1)',
                        backgroundColor: 'rgba(59,130,246,0.08)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 0,
                    }]
                },
                options: {
                    responsive: false,
                    plugins: { legend: { display: false }, tooltip: { enabled: false } },
                    scales: { x: { display: false }, y: { display: false } }
                }
            });
        }

        fetchChart();
        document.querySelectorAll('.sparkline').forEach(canvas => fetchSparkline(canvas));
    </script>
</div>
@endsection
