<div class="min-h-screen bg-gray-950" wire:poll.10s>
    <!-- Enterprise Trading Terminal Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 p-6">

        <!-- Left Column: Trading Chart (2/3 width on large screens) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Advanced Trading Chart Component -->
            <livewire:dashboard.trading-chart />

            <!-- Open Positions Panel -->
            <livewire:dashboard.positions-panel />
        </div>

        <!-- Right Column: Stats & Info (1/3 width on large screens) -->
        <div class="space-y-6">
            <!-- Account Balance Card -->
            @php
                try {
                    $binanceService = app(\App\Services\BinanceService::class);

                    // Get full balance info from Binance
                    $balanceInfo = $binanceService->getBalance();
                    $totalBalance = 0;
                    $availableBalance = 0;

                    // Check if response has error
                    if (!isset($balanceInfo['error'])) {
                        foreach ($balanceInfo as $asset) {
                            if (isset($asset['asset']) && $asset['asset'] == 'USDT') {
                                $totalBalance = (float) $asset['balance'];
                                $availableBalance = (float) $asset['availableBalance'];
                                break;
                            }
                        }

                        $usedInMargin = $totalBalance - $availableBalance;

                        // Get unrealized P&L from Binance positions
                        $openPositions = $binanceService->getOpenPositions();
                        $unrealizedPnl = 0;
                        if (!isset($openPositions['error'])) {
                            foreach ($openPositions as $position) {
                                $unrealizedPnl += (float) ($position['unRealizedProfit'] ?? 0);
                            }
                        }

                        // Calculate equity (total balance + unrealized)
                        $equity = $totalBalance + $unrealizedPnl;
                    } else {
                        $totalBalance = 0;
                        $availableBalance = 0;
                        $usedInMargin = 0;
                        $unrealizedPnl = 0;
                        $equity = 0;
                    }
                } catch (\Exception $e) {
                    $totalBalance = 0;
                    $availableBalance = 0;
                    $usedInMargin = 0;
                    $unrealizedPnl = 0;
                    $equity = 0;
                }
            @endphp
            <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-lg border border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                        <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                    </svg>
                    Account Balance
                </h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-400 text-sm font-medium">Total Balance</span>
                        <span class="text-2xl font-bold text-white">${{ number_format($totalBalance, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500 pl-4">└ Available</span>
                        <span class="text-gray-300">${{ number_format($availableBalance, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500 pl-4">└ Used in Margin</span>
                        <span class="text-orange-400">${{ number_format($usedInMargin, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center border-t border-gray-700 pt-3">
                        <span class="text-gray-400 text-sm">Unrealized P&L</span>
                        <span class="text-lg font-semibold {{ $unrealizedPnl >= 0 ? 'text-green-400' : 'text-red-400' }}">
                            {{ $unrealizedPnl >= 0 ? '+' : '' }}${{ number_format($unrealizedPnl, 2) }}
                        </span>
                    </div>
                    <div class="border-t border-gray-700 pt-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300 font-medium">Total Equity</span>
                            <span class="text-2xl font-bold {{ $unrealizedPnl >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                ${{ number_format($equity, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bot Status Card -->
            <livewire:bot-status />

            <!-- Performance Metrics Card -->
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Performance Metrics</h3>
                <div class="space-y-4">
                    @php
                        $metrics = \App\Models\Trade::where('status', 'CLOSED')->get();
                        $totalTrades = $metrics->count();
                        $winningTrades = $metrics->where('pnl', '>', 0)->count();
                        $totalPnl = $metrics->sum('pnl');
                        $winRate = $totalTrades > 0 ? ($winningTrades / $totalTrades) * 100 : 0;
                    @endphp

                    <div class="flex justify-between items-center">
                        <span class="text-gray-400 text-sm">Total P&L</span>
                        <span class="text-lg font-bold {{ $totalPnl >= 0 ? 'text-green-400' : 'text-red-400' }}">
                            {{ $totalPnl >= 0 ? '+' : '' }}${{ number_format($totalPnl, 2) }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-gray-400 text-sm">Total Trades</span>
                        <span class="text-white font-semibold">{{ $totalTrades }}</span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-gray-400 text-sm">Win Rate</span>
                        <span class="text-white font-semibold">{{ number_format($winRate, 1) }}%</span>
                    </div>

                    <div class="w-full bg-gray-800 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full transition-all duration-300" style="width: {{ $winRate }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Recent AI Decisions Card -->
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Recent AI Decisions</h3>

                @if($recentDecisions->isEmpty())
                    <p class="text-gray-500 text-center py-8 text-sm">No AI decisions yet</p>
                @else
                    <div class="space-y-3">
                        @foreach($recentDecisions as $decision)
                            <div class="bg-gray-800 rounded-lg p-3 border border-gray-700 hover:border-gray-600 transition-colors">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-white font-bold text-sm">{{ $decision->symbol }}</span>
                                        <span class="px-2 py-0.5 rounded text-xs font-semibold
                                            {{ $decision->decision === 'BUY' ? 'bg-green-600 text-white' : ($decision->decision === 'SELL' ? 'bg-red-600 text-white' : 'bg-gray-600 text-white') }}">
                                            {{ $decision->decision }}
                                        </span>
                                    </div>
                                    <span class="text-xs {{ $decision->confidence >= 75 ? 'text-green-400' : 'text-yellow-400' }} font-semibold">
                                        {{ number_format($decision->confidence, 0) }}%
                                    </span>
                                </div>

                                <p class="text-xs text-gray-400 mb-1">{{ Str::limit($decision->reasoning, 100) }}</p>

                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500">{{ $decision->analyzed_at->diffForHumans() }}</span>
                                    @if($decision->executed)
                                        <span class="text-xs text-green-500 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            Executed
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Recent Trades Mini Panel -->
            <livewire:recent-trades />
        </div>
    </div>
</div>
