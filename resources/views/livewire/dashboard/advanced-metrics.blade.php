<div class="space-y-6">
    <!-- Header with Period Selector -->
    <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-white">Advanced Performance Metrics</h2>

            <!-- Period Selector -->
            <div class="flex gap-2">
                <button wire:click="$set('selectedPeriod', '7d')"
                        class="px-3 py-1.5 text-xs font-medium rounded transition-colors {{ $selectedPeriod === '7d' ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }}">
                    7 Days
                </button>
                <button wire:click="$set('selectedPeriod', '30d')"
                        class="px-3 py-1.5 text-xs font-medium rounded transition-colors {{ $selectedPeriod === '30d' ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }}">
                    30 Days
                </button>
                <button wire:click="$set('selectedPeriod', '90d')"
                        class="px-3 py-1.5 text-xs font-medium rounded transition-colors {{ $selectedPeriod === '90d' ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }}">
                    90 Days
                </button>
                <button wire:click="$set('selectedPeriod', '1y')"
                        class="px-3 py-1.5 text-xs font-medium rounded transition-colors {{ $selectedPeriod === '1y' ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }}">
                    1 Year
                </button>
                <button wire:click="$set('selectedPeriod', 'all')"
                        class="px-3 py-1.5 text-xs font-medium rounded transition-colors {{ $selectedPeriod === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }}">
                    All Time
                </button>
            </div>
        </div>
    </div>

    @if($isLoading)
        <div class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>
    @else
        <!-- Basic Metrics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Trades -->
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider">Total Trades</p>
                        <p class="text-2xl font-bold text-white mt-1">{{ $metrics['basic']['total_trades'] ?? 0 }}</p>
                    </div>
                    <div class="p-3 bg-blue-900 bg-opacity-30 rounded-lg">
                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-2 flex items-center text-xs">
                    <span class="text-green-400">{{ $metrics['basic']['winning_trades'] ?? 0 }} wins</span>
                    <span class="text-gray-500 mx-1">•</span>
                    <span class="text-red-400">{{ $metrics['basic']['losing_trades'] ?? 0 }} losses</span>
                </div>
            </div>

            <!-- Win Rate -->
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider">Win Rate</p>
                        <p class="text-2xl font-bold {{ ($metrics['basic']['win_rate'] ?? 0) >= 50 ? 'text-green-400' : 'text-red-400' }} mt-1">
                            {{ number_format($metrics['basic']['win_rate'] ?? 0, 1) }}%
                        </p>
                    </div>
                    <div class="p-3 bg-{{ ($metrics['basic']['win_rate'] ?? 0) >= 50 ? 'green' : 'red' }}-900 bg-opacity-30 rounded-lg">
                        <svg class="w-6 h-6 text-{{ ($metrics['basic']['win_rate'] ?? 0) >= 50 ? 'green' : 'red' }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-2 w-full bg-gray-800 rounded-full h-2">
                    <div class="bg-{{ ($metrics['basic']['win_rate'] ?? 0) >= 50 ? 'green' : 'red' }}-500 h-2 rounded-full transition-all"
                         style="width: {{ min(100, $metrics['basic']['win_rate'] ?? 0) }}%"></div>
                </div>
            </div>

            <!-- Total P&L -->
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider">Total P&L</p>
                        <p class="text-2xl font-bold {{ ($metrics['basic']['total_pnl'] ?? 0) >= 0 ? 'text-green-400' : 'text-red-400' }} mt-1">
                            {{ ($metrics['basic']['total_pnl'] ?? 0) >= 0 ? '+' : '' }}${{ number_format($metrics['basic']['total_pnl'] ?? 0, 2) }}
                        </p>
                    </div>
                    <div class="p-3 bg-{{ ($metrics['basic']['total_pnl'] ?? 0) >= 0 ? 'green' : 'red' }}-900 bg-opacity-30 rounded-lg">
                        <svg class="w-6 h-6 text-{{ ($metrics['basic']['total_pnl'] ?? 0) >= 0 ? 'green' : 'red' }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-2 flex items-center text-xs">
                    <span class="text-green-400">${{ number_format($metrics['basic']['gross_profit'] ?? 0, 2) }}</span>
                    <span class="text-gray-500 mx-1">/</span>
                    <span class="text-red-400">${{ number_format($metrics['basic']['gross_loss'] ?? 0, 2) }}</span>
                </div>
            </div>

            <!-- Profit Factor -->
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider">Profit Factor</p>
                        <p class="text-2xl font-bold {{ ($metrics['basic']['profit_factor'] ?? 0) >= 1.5 ? 'text-green-400' : (($metrics['basic']['profit_factor'] ?? 0) >= 1 ? 'text-yellow-400' : 'text-red-400') }} mt-1">
                            {{ number_format($metrics['basic']['profit_factor'] ?? 0, 2) }}
                        </p>
                    </div>
                    <div class="p-3 bg-purple-900 bg-opacity-30 rounded-lg">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-2 text-xs text-gray-500">
                    Expectancy: ${{ number_format($metrics['basic']['expectancy'] ?? 0, 2) }}
                </div>
            </div>
        </div>

        <!-- Risk Metrics -->
        <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Risk Metrics
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Sharpe Ratio -->
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <p class="text-xs text-gray-400 mb-1">Sharpe Ratio</p>
                    <p class="text-xl font-bold {{ ($metrics['risk']['sharpe_ratio'] ?? 0) >= 1 ? 'text-green-400' : 'text-yellow-400' }}">
                        {{ number_format($metrics['risk']['sharpe_ratio'] ?? 0, 2) }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ ($metrics['risk']['sharpe_ratio'] ?? 0) >= 2 ? 'Excellent' : (($metrics['risk']['sharpe_ratio'] ?? 0) >= 1 ? 'Good' : 'Poor') }}
                    </p>
                </div>

                <!-- Sortino Ratio -->
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <p class="text-xs text-gray-400 mb-1">Sortino Ratio</p>
                    <p class="text-xl font-bold {{ ($metrics['risk']['sortino_ratio'] ?? 0) >= 1 ? 'text-green-400' : 'text-yellow-400' }}">
                        {{ number_format($metrics['risk']['sortino_ratio'] ?? 0, 2) }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">Downside risk-adjusted</p>
                </div>

                <!-- Max Drawdown -->
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <p class="text-xs text-gray-400 mb-1">Max Drawdown</p>
                    <p class="text-xl font-bold text-red-400">
                        -{{ number_format($metrics['risk']['max_drawdown'] ?? 0, 2) }}%
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $metrics['risk']['max_drawdown_duration'] ?? 0 }} days
                    </p>
                </div>

                <!-- Calmar Ratio -->
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <p class="text-xs text-gray-400 mb-1">Calmar Ratio</p>
                    <p class="text-xl font-bold {{ ($metrics['risk']['calmar_ratio'] ?? 0) >= 1 ? 'text-green-400' : 'text-yellow-400' }}">
                        {{ number_format($metrics['risk']['calmar_ratio'] ?? 0, 2) }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">Return/Drawdown</p>
                </div>

                <!-- VaR 95% -->
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <p class="text-xs text-gray-400 mb-1">VaR (95%)</p>
                    <p class="text-xl font-bold text-orange-400">
                        ${{ number_format($metrics['risk']['var_95'] ?? 0, 2) }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">Value at Risk</p>
                </div>

                <!-- CVaR 95% -->
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <p class="text-xs text-gray-400 mb-1">CVaR (95%)</p>
                    <p class="text-xl font-bold text-red-400">
                        ${{ number_format($metrics['risk']['cvar_95'] ?? 0, 2) }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">Conditional VaR</p>
                </div>

                <!-- Recovery Factor -->
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <p class="text-xs text-gray-400 mb-1">Recovery Factor</p>
                    <p class="text-xl font-bold {{ ($metrics['risk']['recovery_factor'] ?? 0) >= 2 ? 'text-green-400' : 'text-yellow-400' }}">
                        {{ number_format($metrics['risk']['recovery_factor'] ?? 0, 2) }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">Profit/Max DD</p>
                </div>

                <!-- Distribution Stats -->
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <p class="text-xs text-gray-400 mb-1">Distribution</p>
                    <div class="flex items-baseline gap-2">
                        <span class="text-sm font-semibold text-blue-400">Skew: {{ number_format($metrics['distribution']['skewness'] ?? 0, 2) }}</span>
                        <span class="text-sm font-semibold text-purple-400">Kurt: {{ number_format($metrics['distribution']['kurtosis'] ?? 0, 2) }}</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">σ: {{ number_format($metrics['distribution']['std_dev'] ?? 0, 2) }}</p>
                </div>
            </div>
        </div>

        <!-- Trade Quality Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Average Win/Loss -->
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
                <p class="text-xs text-gray-400 mb-2">Average Win/Loss</p>
                <div class="space-y-1">
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500">Avg Win:</span>
                        <span class="text-sm font-semibold text-green-400">${{ number_format($metrics['basic']['avg_win'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500">Avg Loss:</span>
                        <span class="text-sm font-semibold text-red-400">${{ number_format($metrics['basic']['avg_loss'] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Largest Trades -->
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
                <p class="text-xs text-gray-400 mb-2">Largest Win/Loss</p>
                <div class="space-y-1">
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500">Best:</span>
                        <span class="text-sm font-semibold text-green-400">${{ number_format($metrics['basic']['largest_win'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500">Worst:</span>
                        <span class="text-sm font-semibold text-red-400">${{ number_format($metrics['basic']['largest_loss'] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Streaks -->
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
                <p class="text-xs text-gray-400 mb-2">Win/Loss Streaks</p>
                <div class="space-y-1">
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500">Max Wins:</span>
                        <span class="text-sm font-semibold text-green-400">{{ $metrics['quality']['win_streak'] ?? 0 }} trades</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500">Max Losses:</span>
                        <span class="text-sm font-semibold text-red-400">{{ $metrics['quality']['loss_streak'] ?? 0 }} trades</span>
                    </div>
                </div>
            </div>

            <!-- Quality Metrics -->
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
                <p class="text-xs text-gray-400 mb-2">Trade Quality</p>
                <div class="space-y-1">
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500">Avg RRR:</span>
                        <span class="text-sm font-semibold text-blue-400">1:{{ number_format($metrics['quality']['avg_rrr'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500">Avg Duration:</span>
                        <span class="text-sm font-semibold text-purple-400">{{ number_format(($metrics['quality']['avg_duration'] ?? 0) / 60, 1) }}h</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Equity Curve -->
        @if(!empty($equityCurve))
        <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                </svg>
                Equity Curve
            </h3>

            <div class="bg-gray-950 rounded-lg p-4" style="height: 300px;">
                <div class="w-full h-full flex items-center justify-center text-gray-500">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <p class="text-sm">{{ count($equityCurve) }} data points</p>
                        <p class="text-xs mt-1">
                            Start: ${{ number_format($equityCurve[0]['equity'] ?? 10000, 2) }} →
                            End: ${{ number_format(end($equityCurve)['equity'] ?? 10000, 2) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Performance Breakdowns -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Monthly Performance -->
            @if(!empty($monthlyPerformance))
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Monthly Performance</h3>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    @foreach($monthlyPerformance as $month)
                        <div class="flex items-center justify-between p-2 bg-gray-800 rounded hover:bg-gray-750">
                            <span class="text-sm text-gray-300">{{ $month['month'] }}</span>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-gray-500">{{ $month['trade_count'] }} trades</span>
                                <span class="text-sm font-semibold {{ $month['total_pnl'] >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                    {{ $month['total_pnl'] >= 0 ? '+' : '' }}${{ number_format($month['total_pnl'], 2) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Pair Performance -->
            @if(!empty($pairPerformance))
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Performance by Pair</h3>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    @foreach($pairPerformance as $pair)
                        <div class="flex items-center justify-between p-2 bg-gray-800 rounded hover:bg-gray-750">
                            <div>
                                <span class="text-sm font-semibold text-white">{{ $pair['symbol'] }}</span>
                                <div class="text-xs text-gray-500">
                                    {{ $pair['trade_count'] }} trades •
                                    {{ number_format(($pair['winning_trades'] / max(1, $pair['trade_count'])) * 100, 1) }}% WR
                                </div>
                            </div>
                            <span class="text-sm font-semibold {{ $pair['total_pnl'] >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                {{ $pair['total_pnl'] >= 0 ? '+' : '' }}${{ number_format($pair['total_pnl'], 2) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Hourly Performance -->
            @if(!empty($hourlyPerformance))
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-6 lg:col-span-2">
                <h3 class="text-lg font-semibold text-white mb-4">Best Trading Hours</h3>
                <div class="grid grid-cols-6 md:grid-cols-12 gap-2">
                    @foreach($hourlyPerformance as $hour)
                        <div class="text-center p-2 rounded {{ $hour['total_pnl'] > 0 ? 'bg-green-900 bg-opacity-20 border border-green-800' : ($hour['total_pnl'] < 0 ? 'bg-red-900 bg-opacity-20 border border-red-800' : 'bg-gray-800') }}">
                            <div class="text-xs text-gray-400">{{ str_pad($hour['hour'], 2, '0', STR_PAD_LEFT) }}:00</div>
                            <div class="text-xs font-semibold {{ $hour['total_pnl'] >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                {{ $hour['total_pnl'] >= 0 ? '+' : '' }}{{ number_format($hour['total_pnl'], 0) }}
                            </div>
                            <div class="text-xs text-gray-600">{{ $hour['trade_count'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    @endif
</div>
