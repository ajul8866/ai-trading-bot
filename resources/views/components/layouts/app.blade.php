<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'AI Trading Bot' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js for interactive components -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Lightweight Charts for trading chart -->
    <script src="https://unpkg.com/lightweight-charts@4.1.0/dist/lightweight-charts.standalone.production.js"></script>
    <script>
        // Make createChart available globally
        window.createChart = LightweightCharts.createChart;
    </script>
    @livewireStyles
</head>
<body class="bg-gray-900 text-gray-100">
    <nav class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-white">🤖 AI Trading Bot</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/" class="text-gray-300 hover:text-white px-3 py-2 {{ request()->is('/') ? 'text-white bg-gray-700 rounded' : '' }}">Dashboard</a>
                    <a href="/settings" class="text-gray-300 hover:text-white px-3 py-2 {{ request()->is('settings') ? 'text-white bg-gray-700 rounded' : '' }}">Settings</a>
                    <a href="/trades" class="text-gray-300 hover:text-white px-3 py-2 {{ request()->is('trades') ? 'text-white bg-gray-700 rounded' : '' }}">Trades</a>
                    <a href="/ai-decisions" class="text-gray-300 hover:text-white px-3 py-2 {{ request()->is('ai-decisions') ? 'text-white bg-gray-700 rounded' : '' }}">AI Decisions</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{ $slot }}
        </div>
    </main>

    @livewireScripts
</body>
</html>
