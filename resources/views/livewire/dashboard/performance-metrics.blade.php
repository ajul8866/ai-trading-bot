<div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            Performance Overview
        </h3>

        <button wire:click="loadMetrics" class="p-2 bg-gray-800 hover:bg-gray-700 rounded transition-colors" title="Refresh">
            <svg class="w-4 h-4 text-gray-300 {{ $isLoading ? 'animate-spin' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
        </button>
    </div>

    @if($isLoading)
        <div class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-500"></div>
        </div>
    @else
        <!-- Main Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <!-- Total P&L -->
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Total P&L</div>
                <div class="text-2xl font-bold {{ ($metrics['total_pnl'] ?? 0) >= 0 ? 'text-green-400' : 'text-red-400' }}">
                    {{ ($metrics['total_pnl'] ?? 0) >= 0 ? '+' : '' }}${{ number_format($metrics['total_pnl'] ?? 0, 2) }}
                </div>
                <div class="text-xs text-gray-500 mt-1">All time</div>
            </div>

            <!-- Win Rate -->
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Win Rate</div>
                <div class="text-2xl font-bold {{ ($metrics['win_rate'] ?? 0) >= 50 ? 'text-green-400' : 'text-red-400' }}">
                    {{ number_format($metrics['win_rate'] ?? 0, 1) }}%
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    {{ $metrics['winning_trades'] ?? 0 }}/{{ $metrics['total_trades'] ?? 0 }} wins
                </div>
            </div>

            <!-- Profit Factor -->
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Profit Factor</div>
                <div class="text-2xl font-bold {{ ($metrics['profit_factor'] ?? 0) >= 1.5 ? 'text-green-400' : 'text-yellow-400' }}">
                    {{ number_format($metrics['profit_factor'] ?? 0, 2) }}
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    {{ ($metrics['profit_factor'] ?? 0) >= 2 ? 'Excellent' : (($metrics['profit_factor'] ?? 0) >= 1 ? 'Good' : 'Poor') }}
                </div>
            </div>

            <!-- Open Positions -->
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Open Positions</div>
                <div class="text-2xl font-bold text-blue-400">
                    {{ $metrics['open_positions'] ?? 0 }}
                </div>
                <div class="text-xs text-gray-500 mt-1">Active trades</div>
            </div>
        </div>

        <!-- Time-based Performance -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <!-- Today -->
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-xs text-gray-400 uppercase tracking-wider">Today</div>
                    <span class="text-xs text-gray-500">{{ $metrics['today_trades'] ?? 0 }} trades</span>
                </div>
                <div class="text-xl font-bold {{ ($metrics['today_pnl'] ?? 0) >= 0 ? 'text-green-400' : 'text-red-400' }}">
                    {{ ($metrics['today_pnl'] ?? 0) >= 0 ? '+' : '' }}${{ number_format($metrics['today_pnl'] ?? 0, 2) }}
                </div>
            </div>

            <!-- This Week -->
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-xs text-gray-400 uppercase tracking-wider">This Week</div>
                </div>
                <div class="text-xl font-bold {{ ($metrics['week_pnl'] ?? 0) >= 0 ? 'text-green-400' : 'text-red-400' }}">
                    {{ ($metrics['week_pnl'] ?? 0) >= 0 ? '+' : '' }}${{ number_format($metrics['week_pnl'] ?? 0, 2) }}
                </div>
            </div>

            <!-- This Month -->
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-xs text-gray-400 uppercase tracking-wider">This Month</div>
                </div>
                <div class="text-xl font-bold {{ ($metrics['month_pnl'] ?? 0) >= 0 ? 'text-green-400' : 'text-red-400' }}">
                    {{ ($metrics['month_pnl'] ?? 0) >= 0 ? '+' : '' }}${{ number_format($metrics['month_pnl'] ?? 0, 2) }}
                </div>
            </div>
        </div>

        <!-- Profit/Loss Breakdown -->
        <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
            <div class="text-xs text-gray-400 uppercase tracking-wider mb-3">Profit/Loss Distribution</div>

            <div class="space-y-3">
                <!-- Gross Profit -->
                <div>
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="text-gray-400">Gross Profit</span>
                        <span class="font-semibold text-green-400">${{ number_format($metrics['gross_profit'] ?? 0, 2) }}</span>
                    </div>
                    <div class="w-full bg-gray-700 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full transition-all"
                             style="width: {{ ($metrics['gross_profit'] ?? 0) > 0 ? min(100, (($metrics['gross_profit'] ?? 0) / max(1, ($metrics['gross_profit'] ?? 0) + ($metrics['gross_loss'] ?? 0))) * 100) : 0 }}%"></div>
                    </div>
                </div>

                <!-- Gross Loss -->
                <div>
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="text-gray-400">Gross Loss</span>
                        <span class="font-semibold text-red-400">-${{ number_format($metrics['gross_loss'] ?? 0, 2) }}</span>
                    </div>
                    <div class="w-full bg-gray-700 rounded-full h-2">
                        <div class="bg-red-500 h-2 rounded-full transition-all"
                             style="width: {{ ($metrics['gross_loss'] ?? 0) > 0 ? min(100, (($metrics['gross_loss'] ?? 0) / max(1, ($metrics['gross_profit'] ?? 0) + ($metrics['gross_loss'] ?? 0))) * 100) : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
