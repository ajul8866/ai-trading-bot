<div class="min-h-screen bg-gray-950 p-6">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold text-white">Trade History</h1>
            <button wire:click="loadStats" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refresh Stats
            </button>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
                <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Total P&L</div>
                <div class="text-2xl font-bold {{ $totalPnl >= 0 ? 'text-green-400' : 'text-red-400' }}">
                    {{ $totalPnl >= 0 ? '+' : '' }}${{ number_format($totalPnl, 2) }}
                </div>
            </div>
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
                <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Win Rate</div>
                <div class="text-2xl font-bold {{ $winRate >= 50 ? 'text-green-400' : 'text-red-400' }}">{{ $winRate }}%</div>
            </div>
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
                <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Total Trades</div>
                <div class="text-2xl font-bold text-white">{{ number_format($totalTrades) }}</div>
            </div>
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
                <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Open Positions</div>
                <div class="text-2xl font-bold text-blue-400">{{ number_format($openTrades) }}</div>
            </div>
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
                <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Best Trade</div>
                <div class="text-2xl font-bold text-green-500">+${{ number_format($bestTrade, 2) }}</div>
            </div>
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
                <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Worst Trade</div>
                <div class="text-2xl font-bold text-red-500">${{ number_format($worstTrade, 2) }}</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-gray-900 rounded-lg border border-gray-800 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-white">Filters</h3>
                <button wire:click="clearFilters" class="text-sm text-gray-400 hover:text-white transition-colors">
                    Clear All Filters
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Symbol Filter -->
                <div>
                    <label class="block text-xs text-gray-400 uppercase tracking-wider mb-2">Symbol</label>
                    <select wire:model.live="filterSymbol" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">All Symbols</option>
                        @foreach($symbols as $sym)
                            <option value="{{ $sym }}">{{ $sym }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Side Filter -->
                <div>
                    <label class="block text-xs text-gray-400 uppercase tracking-wider mb-2">Side</label>
                    <select wire:model.live="filterSide" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">All Sides</option>
                        <option value="LONG">LONG</option>
                        <option value="SHORT">SHORT</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-xs text-gray-400 uppercase tracking-wider mb-2">Status</label>
                    <select wire:model.live="filterStatus" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="OPEN">Open</option>
                        <option value="CLOSED">Closed</option>
                        <option value="CANCELLED">Cancelled</option>
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <label class="block text-xs text-gray-400 uppercase tracking-wider mb-2">From Date</label>
                    <input type="date" wire:model.live="filterDateFrom" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-xs text-gray-400 uppercase tracking-wider mb-2">To Date</label>
                    <input type="date" wire:model.live="filterDateTo" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Trades Table -->
        <div class="bg-gray-900 rounded-lg border border-gray-800 overflow-hidden">
            <!-- Table Header -->
            <div class="bg-gray-800 border-b border-gray-700 p-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-white">Trade Records</h3>
                    <div class="text-sm text-gray-400">
                        Showing {{ $trades->firstItem() ?? 0 }} - {{ $trades->lastItem() ?? 0 }} of {{ $trades->total() }}
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-800 border-b border-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Symbol</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Side</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Entry</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Exit</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Quantity</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Leverage</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Margin</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">SL / TP</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">P&L</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Opened</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        @forelse($trades as $trade)
                            <tr class="hover:bg-gray-800/50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-white">{{ $trade->symbol }}</div>
                                    @if($trade->binance_order_id)
                                        <div class="text-xs text-gray-500">{{ Str::limit($trade->binance_order_id, 10) }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded text-xs font-semibold
                                        {{ $trade->side === 'LONG' ? 'bg-green-900 text-green-300' : 'bg-red-900 text-red-300' }}">
                                        {{ $trade->side }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-white">${{ number_format($trade->entry_price, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-white">
                                    @if($trade->exit_price)
                                        ${{ number_format($trade->exit_price, 2) }}
                                    @else
                                        <span class="text-gray-500">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-white">{{ number_format($trade->quantity, 6) }}</td>
                                <td class="px-4 py-3 text-sm text-purple-400 font-semibold">{{ $trade->leverage }}x</td>
                                <td class="px-4 py-3 text-sm text-white">${{ number_format($trade->margin, 2) }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="text-red-400">${{ number_format($trade->stop_loss, 2) }}</div>
                                    <div class="text-green-400">${{ number_format($trade->take_profit, 2) }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    @if($trade->pnl !== null)
                                        <div class="font-semibold {{ $trade->pnl >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                            {{ $trade->pnl >= 0 ? '+' : '' }}${{ number_format($trade->pnl, 2) }}
                                        </div>
                                        @if($trade->pnl_percentage)
                                            <div class="text-xs {{ $trade->pnl >= 0 ? 'text-green-500' : 'text-red-500' }}">
                                                {{ $trade->pnl_percentage >= 0 ? '+' : '' }}{{ number_format($trade->pnl_percentage, 2) }}%
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-gray-500">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded text-xs font-semibold
                                        {{ $trade->status === 'OPEN' ? 'bg-blue-900 text-blue-300' :
                                           ($trade->status === 'CLOSED' ? 'bg-gray-700 text-gray-300' : 'bg-yellow-900 text-yellow-300') }}">
                                        {{ $trade->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    <div class="text-gray-400">{{ $trade->opened_at ? $trade->opened_at->format('Y-m-d H:i') : $trade->created_at->format('Y-m-d H:i') }}</div>
                                    @if($trade->closed_at)
                                        <div class="text-gray-500">Closed: {{ $trade->closed_at->format('Y-m-d H:i') }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-4 py-12 text-center">
                                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <h3 class="text-lg font-semibold text-gray-400 mb-1">No Trades Found</h3>
                                    <p class="text-sm text-gray-500">No trades match your current filters</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($trades->hasPages())
                <div class="bg-gray-800 border-t border-gray-700 p-4">
                    {{ $trades->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
