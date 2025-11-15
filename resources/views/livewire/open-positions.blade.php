<div wire:poll.10s class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Open Positions</h2>

    @if($positions->isEmpty())
        <p class="text-gray-500 text-center py-8">No open positions</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Symbol</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Side</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Entry Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Leverage</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SL</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">TP</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Opened</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($positions as $position)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-semibold">{{ $position->symbol }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $position->side === 'LONG' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $position->side }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">${{ number_format($position->entry_price, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $position->quantity }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $position->leverage }}x</td>
                            <td class="px-6 py-4 whitespace-nowrap text-red-600">${{ number_format($position->stop_loss, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-green-600">${{ number_format($position->take_profit, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $position->opened_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
