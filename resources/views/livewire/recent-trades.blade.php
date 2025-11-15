<div wire:poll.30s class="bg-gray-900 rounded-lg border border-gray-800 p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-white">Recent Trades</h2>

        <!-- Filter -->
        <select wire:model.live="filter" class="px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="all">All Trades</option>
            <option value="closed">Closed Only</option>
            <option value="cancelled">Cancelled Only</option>
        </select>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
            <p class="text-sm text-gray-400">Total Trades</p>
            <p class="text-2xl font-bold text-blue-400">{{ $stats['total_trades'] }}</p>
        </div>
        <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
            <p class="text-sm text-gray-400">Winning</p>
            <p class="text-2xl font-bold text-green-400">{{ $stats['winning_trades'] }}</p>
        </div>
        <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
            <p class="text-sm text-gray-400">Losing</p>
            <p class="text-2xl font-bold text-red-400">{{ $stats['losing_trades'] }}</p>
        </div>
        <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
            <p class="text-sm text-gray-400">Win Rate</p>
            <p class="text-2xl font-bold text-purple-400">{{ number_format($stats['win_rate'], 1) }}%</p>
        </div>
    </div>

    <!-- Trades Table -->
    @if($trades->isEmpty())
        <p class="text-gray-500 text-center py-8">No trades found</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-800">
                <thead class="bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Symbol</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Side</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Entry</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Exit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">P&L</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">P&L %</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Opened</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Closed</th>
                    </tr>
                </thead>
                <tbody class="bg-gray-900 divide-y divide-gray-800">
                    @foreach($trades as $trade)
                        <tr class="hover:bg-gray-800 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-white">{{ $trade->symbol }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ in_array($trade->side, ['LONG', 'BUY']) ? 'bg-green-900 text-green-300' : 'bg-red-900 text-red-300' }}">
                                    {{ $trade->side }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-300">${{ number_format($trade->entry_price, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($trade->exit_price)
                                    <span class="text-gray-300">${{ number_format($trade->exit_price, 2) }}</span>
                                @else
                                    <span class="text-gray-600">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-semibold {{ $trade->pnl >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                @if($trade->pnl)
                                    {{ $trade->pnl >= 0 ? '+' : '' }}${{ number_format($trade->pnl, 2) }}
                                @else
                                    <span class="text-gray-600">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap {{ $trade->pnl_percentage >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                @if($trade->pnl_percentage)
                                    {{ $trade->pnl_percentage >= 0 ? '+' : '' }}{{ number_format($trade->pnl_percentage, 2) }}%
                                @else
                                    <span class="text-gray-600">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    {{ $trade->status === 'OPEN' ? 'bg-blue-900 text-blue-300' : ($trade->status === 'CLOSED' ? 'bg-gray-700 text-gray-300' : 'bg-yellow-900 text-yellow-300') }}">
                                    {{ $trade->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">{{ $trade->opened_at->format('M d, H:i') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                @if($trade->closed_at)
                                    {{ $trade->closed_at->format('M d, H:i') }}
                                @else
                                    <span class="text-gray-600">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $trades->links() }}
        </div>
    @endif
</div>
