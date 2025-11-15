<div wire:poll.30s class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-gray-800">Recent Trades</h2>

        <!-- Filter -->
        <select wire:model.live="filter" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="all">All Trades</option>
            <option value="closed">Closed Only</option>
            <option value="cancelled">Cancelled Only</option>
        </select>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 rounded-lg p-4">
            <p class="text-sm text-gray-600">Total Trades</p>
            <p class="text-2xl font-bold text-blue-600">{{ $stats['total_trades'] }}</p>
        </div>
        <div class="bg-green-50 rounded-lg p-4">
            <p class="text-sm text-gray-600">Winning</p>
            <p class="text-2xl font-bold text-green-600">{{ $stats['winning_trades'] }}</p>
        </div>
        <div class="bg-red-50 rounded-lg p-4">
            <p class="text-sm text-gray-600">Losing</p>
            <p class="text-2xl font-bold text-red-600">{{ $stats['losing_trades'] }}</p>
        </div>
        <div class="bg-purple-50 rounded-lg p-4">
            <p class="text-sm text-gray-600">Win Rate</p>
            <p class="text-2xl font-bold text-purple-600">{{ number_format($stats['win_rate'], 1) }}%</p>
        </div>
    </div>

    <!-- Trades Table -->
    @if($trades->isEmpty())
        <p class="text-gray-500 text-center py-8">No trades found</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Symbol</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Side</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Entry</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">P&L</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">P&L %</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Opened</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Closed</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($trades as $trade)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap font-semibold">{{ $trade->symbol }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $trade->side === 'LONG' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $trade->side }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">${{ number_format($trade->entry_price, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($trade->exit_price)
                                    ${{ number_format($trade->exit_price, 2) }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-semibold {{ $trade->pnl >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                @if($trade->pnl)
                                    {{ $trade->pnl >= 0 ? '+' : '' }}${{ number_format($trade->pnl, 2) }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap {{ $trade->pnl_percentage >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                @if($trade->pnl_percentage)
                                    {{ $trade->pnl_percentage >= 0 ? '+' : '' }}{{ number_format($trade->pnl_percentage, 2) }}%
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    {{ $trade->status === 'OPEN' ? 'bg-blue-100 text-blue-800' : ($trade->status === 'CLOSED' ? 'bg-gray-100 text-gray-800' : 'bg-yellow-100 text-yellow-800') }}">
                                    {{ $trade->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $trade->opened_at->format('M d, H:i') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($trade->closed_at)
                                    {{ $trade->closed_at->format('M d, H:i') }}
                                @else
                                    <span class="text-gray-400">-</span>
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
