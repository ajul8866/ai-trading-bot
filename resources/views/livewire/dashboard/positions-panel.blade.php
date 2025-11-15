<div class="bg-gray-900 rounded-lg border border-gray-800 overflow-hidden">
    <div class="bg-gray-800 border-b border-gray-700 p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <h2 class="text-lg font-semibold text-white">Open Positions</h2>
                <span class="px-2.5 py-0.5 bg-blue-600 text-white text-xs font-semibold rounded-full">{{ $totalPositions }}</span>
            </div>
            <div class="flex items-center gap-6">
                <div class="text-right">
                    <div class="text-xs text-gray-400">Unrealized P&L</div>
                    <div class="text-lg font-bold {{ $totalUnrealizedPnl >= 0 ? 'text-green-400' : 'text-red-400' }}">
                        {{ $totalUnrealizedPnl >= 0 ? '+' : '' }}${{ number_format($totalUnrealizedPnl, 2) }}
                    </div>
                </div>
                <button wire:click="refresh" class="p-2 bg-gray-700 hover:bg-gray-600 rounded transition-colors">
                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        @if($isLoading)
            <div class="flex items-center justify-center py-12">
                <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-500"></div>
            </div>
        @elseif($positions->isEmpty())
            <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                <p class="text-lg font-medium">No Open Positions</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-800 text-gray-300 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Symbol</th>
                        <th class="px-4 py-3 text-left">Side</th>
                        <th class="px-4 py-3 text-right">Entry/Current</th>
                        <th class="px-4 py-3 text-right">Unrealized P&L</th>
                        <th class="px-4 py-3 text-center">Leverage</th>
                        <th class="px-4 py-3 text-right">Duration</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    @foreach($positions as $position)
                        <tr class="hover:bg-gray-800">
                            <td class="px-4 py-3 font-semibold text-white">{{ $position->symbol }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-semibold rounded {{ in_array($position->side, ['BUY', 'LONG']) ? 'bg-green-900 text-green-300' : 'bg-red-900 text-red-300' }}">
                                    {{ $position->side }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="text-gray-300">${{ number_format($position->entry_price, 2) }}</div>
                                <div class="font-semibold {{ $position->unrealized_pnl >= 0 ? 'text-green-400' : 'text-red-400' }}">${{ number_format($position->current_price, 2) }}</div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="font-bold {{ $position->unrealized_pnl >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                    {{ $position->unrealized_pnl >= 0 ? '+' : '' }}${{ number_format($position->unrealized_pnl, 2) }}
                                </div>
                                <div class="text-xs">{{ $position->unrealized_pnl >= 0 ? '+' : '' }}{{ number_format($position->unrealized_pnl_percent, 2) }}%</div>
                            </td>
                            <td class="px-4 py-3 text-center"><span class="px-2 py-1 bg-blue-900 text-blue-300 text-xs font-semibold rounded">{{ $position->leverage }}x</span></td>
                            <td class="px-4 py-3 text-right text-gray-400">{{ $position->duration }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
