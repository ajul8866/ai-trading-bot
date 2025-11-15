<div class="bg-gray-900 rounded-lg border border-gray-800 overflow-hidden">
    <!-- Header -->
    <div class="px-4 py-3 border-b border-gray-800 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <h3 class="text-sm font-semibold text-white">Order Book</h3>
            <span class="text-xs text-gray-500">{{ $symbol }}</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-400">Spread:</span>
            <span class="text-xs font-semibold text-yellow-400">
                ${{ number_format($spread, 2) }}
                ({{ number_format($spreadPercent, 3) }}%)
            </span>
        </div>
    </div>

    <!-- Column Headers -->
    <div class="grid grid-cols-3 px-4 py-2 bg-gray-800 text-xs text-gray-400 font-medium">
        <div class="text-left">Price (USDT)</div>
        <div class="text-right">Size</div>
        <div class="text-right">Total</div>
    </div>

    <!-- Order Book Content -->
    <div class="relative" style="height: 500px; overflow-y: auto;">
        <!-- Asks (Sell Orders) - Red, reversed order (highest price on top) -->
        <div class="space-y-0.5">
            @foreach(array_reverse($asks) as $ask)
                <div class="relative group hover:bg-gray-800 transition-colors">
                    <!-- Background depth bar -->
                    <div class="absolute inset-0 bg-red-900 opacity-10"
                         style="width: {{ ($ask['total'] / max($askTotal, 1)) * 100 }}%"></div>

                    <!-- Order data -->
                    <div class="relative grid grid-cols-3 px-4 py-1.5 text-xs">
                        <div class="text-red-400 font-mono font-semibold">
                            {{ number_format($ask['price'], 2) }}
                        </div>
                        <div class="text-right text-gray-300 font-mono">
                            {{ number_format($ask['size'], 4) }}
                        </div>
                        <div class="text-right text-gray-500 font-mono">
                            {{ number_format($ask['total'], 2) }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Current Price Separator -->
        <div class="sticky top-0 z-10 bg-gray-900 border-y border-gray-700 px-4 py-2 my-1">
            <div class="flex items-center justify-between">
                @php
                    $currentPrice = !empty($bids) ? $bids[0]['price'] : 0;
                @endphp
                <span class="text-lg font-bold text-green-400 font-mono">
                    {{ number_format($currentPrice, 2) }}
                </span>
                <span class="text-xs text-gray-400">← Current Price</span>
            </div>
        </div>

        <!-- Bids (Buy Orders) - Green -->
        <div class="space-y-0.5">
            @foreach($bids as $bid)
                <div class="relative group hover:bg-gray-800 transition-colors">
                    <!-- Background depth bar -->
                    <div class="absolute inset-0 bg-green-900 opacity-10"
                         style="width: {{ ($bid['total'] / max($bidTotal, 1)) * 100 }}%"></div>

                    <!-- Order data -->
                    <div class="relative grid grid-cols-3 px-4 py-1.5 text-xs">
                        <div class="text-green-400 font-mono font-semibold">
                            {{ number_format($bid['price'], 2) }}
                        </div>
                        <div class="text-right text-gray-300 font-mono">
                            {{ number_format($bid['size'], 4) }}
                        </div>
                        <div class="text-right text-gray-500 font-mono">
                            {{ number_format($bid['total'], 2) }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Footer Stats -->
    <div class="px-4 py-3 border-t border-gray-800 grid grid-cols-2 gap-4 text-xs">
        <div>
            <span class="text-gray-400">Total Bids:</span>
            <span class="text-green-400 font-semibold ml-1 font-mono">
                {{ number_format($bidTotal, 2) }}
            </span>
        </div>
        <div class="text-right">
            <span class="text-gray-400">Total Asks:</span>
            <span class="text-red-400 font-semibold ml-1 font-mono">
                {{ number_format($askTotal, 2) }}
            </span>
        </div>
    </div>
</div>
