<div wire:poll.5s="loadStatus" class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold text-gray-800">Bot Status</h2>
        <button
            wire:click="toggleBot"
            class="px-4 py-2 rounded-lg font-semibold transition-colors {{ $botEnabled ? 'bg-red-500 hover:bg-red-600 text-white' : 'bg-green-500 hover:bg-green-600 text-white' }}">
            {{ $botEnabled ? 'Stop Bot' : 'Start Bot' }}
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Bot Status -->
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-3 h-3 rounded-full {{ $botEnabled ? 'bg-green-500 animate-pulse' : 'bg-red-500' }}"></div>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-600">Status</p>
                    <p class="text-lg font-semibold {{ $botEnabled ? 'text-green-600' : 'text-red-600' }}">
                        {{ $botEnabled ? 'ACTIVE' : 'STOPPED' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Open Positions -->
        <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm text-gray-600">Open Positions</p>
            <p class="text-2xl font-bold text-gray-800">{{ $openPositions }}</p>
        </div>

        <!-- Today's P&L -->
        <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm text-gray-600">Today's P&L</p>
            <p class="text-2xl font-bold {{ $todayPnL >= 0 ? 'text-green-600' : 'text-red-600' }}">
                ${{ number_format($todayPnL, 2) }}
            </p>
        </div>
    </div>
</div>
