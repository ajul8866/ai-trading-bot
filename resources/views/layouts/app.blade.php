<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Trading Bot') }} - Enterprise Trading Terminal</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Livewire Styles -->
    @livewireStyles

    <style>
        body {
            background-color: #030712;
            background-image:
                linear-gradient(rgba(59, 130, 246, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(59, 130, 246, 0.03) 1px, transparent 1px);
            background-size: 20px 20px;
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-950 text-gray-100">
    <div class="min-h-screen">
        <!-- Enterprise Navigation Header -->
        <nav class="bg-gray-900 border-b border-gray-800 sticky top-0 z-50 backdrop-blur-sm bg-opacity-95">
            <div class="max-w-full px-6">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo & Brand -->
                    <div class="flex items-center gap-8">
                        <div class="flex items-center">
                            <div class="relative">
                                <div class="absolute inset-0 bg-blue-600 blur-lg opacity-30"></div>
                                <svg class="h-8 w-8 text-blue-500 relative" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                            <span class="ml-3 text-xl font-bold bg-gradient-to-r from-blue-400 to-blue-600 bg-clip-text text-transparent">
                                AI Trading Terminal
                            </span>
                        </div>

                        <!-- Quick Stats -->
                        @php
                            $botEnabled = \App\Models\Setting::where('key', 'bot_enabled')->value('value') === 'true';
                            $openPositions = \App\Models\Trade::where('status', 'OPEN')->count();
                            $totalPnl = \App\Models\Trade::where('status', 'CLOSED')->sum('pnl');
                        @endphp
                        <div class="hidden lg:flex items-center gap-6 text-sm">
                            <div class="flex items-center gap-2">
                                <div class="h-2 w-2 {{ $botEnabled ? 'bg-green-500' : 'bg-red-500' }} rounded-full animate-pulse"></div>
                                <span class="text-gray-400">Bot:</span>
                                <span class="font-semibold {{ $botEnabled ? 'text-green-400' : 'text-red-400' }}">
                                    {{ $botEnabled ? 'ACTIVE' : 'STOPPED' }}
                                </span>
                            </div>
                            <div class="h-4 w-px bg-gray-800"></div>
                            <div>
                                <span class="text-gray-400">Positions:</span>
                                <span class="font-semibold text-white ml-1">{{ $openPositions }}</span>
                            </div>
                            <div class="h-4 w-px bg-gray-800"></div>
                            <div>
                                <span class="text-gray-400">Total P&L:</span>
                                <span class="font-semibold ml-1 {{ $totalPnl >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                    {{ $totalPnl >= 0 ? '+' : '' }}${{ number_format($totalPnl, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side: Time & System Status -->
                    <div class="flex items-center gap-4">
                        <div class="text-sm text-gray-400" x-data="{ time: new Date().toLocaleTimeString() }" x-init="setInterval(() => time = new Date().toLocaleTimeString(), 1000)">
                            <span x-text="time"></span>
                        </div>
                        <div class="flex items-center gap-2 px-3 py-1.5 bg-gray-800 rounded-lg">
                            <div class="h-2 w-2 bg-green-500 rounded-full animate-pulse"></div>
                            <span class="text-xs text-gray-400">System Online</span>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content (Full Width, No Padding) -->
        <main class="min-h-screen">
            {{ $slot }}
        </main>

        <!-- Compact Footer -->
        <footer class="bg-gray-900 border-t border-gray-800 py-4">
            <div class="max-w-full px-6">
                <div class="flex justify-between items-center text-xs text-gray-500">
                    <p>&copy; {{ date('Y') }} AI Trading Terminal. Laravel {{ app()->version() }} + Livewire</p>
                    <p class="text-yellow-500">⚠️ <strong>Warning:</strong> Live trading - Real money at risk</p>
                </div>
            </div>
        </footer>
    </div>

    <!-- Livewire Scripts -->
    @livewireScripts

    <!-- Auto-refresh components every 3 seconds -->
    <script>
        setInterval(() => {
            Livewire.dispatch('refresh-chart');
            Livewire.dispatch('refresh-positions');
        }, 3000);
    </script>
</body>
</html>
