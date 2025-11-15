<div class="space-y-4">
    <!-- Top Market Movers Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Top Gainers -->
        <div class="bg-gray-900 rounded-lg border border-gray-800 overflow-hidden">
            <div class="px-4 py-3 bg-green-900 bg-opacity-20 border-b border-gray-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    <h3 class="text-sm font-semibold text-white">Top Gainers</h3>
                </div>
                <span class="text-xs text-green-400 font-semibold">24h</span>
            </div>
            <div class="p-3 space-y-2">
                @forelse($topGainers as $gainer)
                    <div class="flex items-center justify-between p-2 bg-gray-800 rounded hover:bg-gray-750 transition-colors">
                        <div>
                            <div class="text-sm font-semibold text-white">{{ $gainer['symbol'] }}</div>
                            <div class="text-xs text-gray-400 font-mono">${{ number_format($gainer['price'], 2) }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold text-green-400">
                                +{{ number_format($gainer['change_24h'], 2) }}%
                            </div>
                            <div class="text-xs text-gray-500">
                                Vol: {{ number_format($gainer['volume_24h'] / 1000000, 2) }}M
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4 text-sm">No data</p>
                @endforelse
            </div>
        </div>

        <!-- Top Losers -->
        <div class="bg-gray-900 rounded-lg border border-gray-800 overflow-hidden">
            <div class="px-4 py-3 bg-red-900 bg-opacity-20 border-b border-gray-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"></path>
                    </svg>
                    <h3 class="text-sm font-semibold text-white">Top Losers</h3>
                </div>
                <span class="text-xs text-red-400 font-semibold">24h</span>
            </div>
            <div class="p-3 space-y-2">
                @forelse($topLosers as $loser)
                    <div class="flex items-center justify-between p-2 bg-gray-800 rounded hover:bg-gray-750 transition-colors">
                        <div>
                            <div class="text-sm font-semibold text-white">{{ $loser['symbol'] }}</div>
                            <div class="text-xs text-gray-400 font-mono">${{ number_format($loser['price'], 2) }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold text-red-400">
                                {{ number_format($loser['change_24h'], 2) }}%
                            </div>
                            <div class="text-xs text-gray-500">
                                Vol: {{ number_format($loser['volume_24h'] / 1000000, 2) }}M
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4 text-sm">No data</p>
                @endforelse
            </div>
        </div>

        <!-- Top Volume -->
        <div class="bg-gray-900 rounded-lg border border-gray-800 overflow-hidden">
            <div class="px-4 py-3 bg-blue-900 bg-opacity-20 border-b border-gray-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <h3 class="text-sm font-semibold text-white">Top Volume</h3>
                </div>
                <span class="text-xs text-blue-400 font-semibold">24h</span>
            </div>
            <div class="p-3 space-y-2">
                @forelse($topVolume as $vol)
                    <div class="flex items-center justify-between p-2 bg-gray-800 rounded hover:bg-gray-750 transition-colors">
                        <div>
                            <div class="text-sm font-semibold text-white">{{ $vol['symbol'] }}</div>
                            <div class="text-xs text-gray-400 font-mono">${{ number_format($vol['price'], 2) }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold text-blue-400">
                                ${{ number_format($vol['volume_24h'] / 1000000, 1) }}M
                            </div>
                            <div class="text-xs {{ $vol['change_24h'] >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                {{ $vol['change_24h'] >= 0 ? '+' : '' }}{{ number_format($vol['change_24h'], 2) }}%
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4 text-sm">No data</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Market Scanner Table -->
    <div class="bg-gray-900 rounded-lg border border-gray-800 overflow-hidden">
        <!-- Header with Filters -->
        <div class="px-4 py-3 border-b border-gray-800">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                    <h2 class="text-lg font-semibold text-white">Market Scanner</h2>
                    <span class="px-2.5 py-0.5 bg-blue-600 text-white text-xs font-semibold rounded-full">
                        {{ count($markets) }} pairs
                    </span>
                </div>
                <button wire:click="refresh"
                        class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-white text-sm rounded transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh
                </button>
            </div>

            <!-- Filter Tabs -->
            <div class="flex items-center gap-2 flex-wrap">
                <button wire:click="setFilter('all')"
                        class="px-3 py-1.5 text-xs font-medium rounded transition-colors {{ $filterBy === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }}">
                    All Markets
                </button>
                <button wire:click="setFilter('strong_buy')"
                        class="px-3 py-1.5 text-xs font-medium rounded transition-colors {{ $filterBy === 'strong_buy' ? 'bg-green-600 text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }}">
                    Strong Buy
                </button>
                <button wire:click="setFilter('buy')"
                        class="px-3 py-1.5 text-xs font-medium rounded transition-colors {{ $filterBy === 'buy' ? 'bg-green-500 text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }}">
                    Buy Signals
                </button>
                <button wire:click="setFilter('sell')"
                        class="px-3 py-1.5 text-xs font-medium rounded transition-colors {{ $filterBy === 'sell' ? 'bg-red-500 text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }}">
                    Sell Signals
                </button>
                <button wire:click="setFilter('oversold')"
                        class="px-3 py-1.5 text-xs font-medium rounded transition-colors {{ $filterBy === 'oversold' ? 'bg-purple-600 text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }}">
                    Oversold (RSI &lt; 30)
                </button>
                <button wire:click="setFilter('overbought')"
                        class="px-3 py-1.5 text-xs font-medium rounded transition-colors {{ $filterBy === 'overbought' ? 'bg-orange-600 text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }}">
                    Overbought (RSI &gt; 70)
                </button>
                <button wire:click="setFilter('trending')"
                        class="px-3 py-1.5 text-xs font-medium rounded transition-colors {{ $filterBy === 'trending' ? 'bg-indigo-600 text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }}">
                    Strong Trends
                </button>
                <button wire:click="setFilter('high_volume')"
                        class="px-3 py-1.5 text-xs font-medium rounded transition-colors {{ $filterBy === 'high_volume' ? 'bg-cyan-600 text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }}">
                    High Volume
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            @if($isLoading)
                <div class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-500"></div>
                </div>
            @elseif(empty($markets))
                <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                    <p class="text-lg font-medium">No markets found</p>
                    <p class="text-sm mt-1">Try different filters or refresh data</p>
                </div>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-gray-800 text-gray-300 text-xs uppercase sticky top-0">
                        <tr>
                            <th class="px-4 py-3 text-left cursor-pointer hover:bg-gray-700" wire:click="sort('symbol')">
                                <div class="flex items-center gap-1">
                                    Symbol
                                    @if($sortBy === 'symbol')
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="{{ $sortDirection === 'asc' ? 'M5 10l5-5 5 5H5z' : 'M5 10l5 5 5-5H5z' }}"/>
                                        </svg>
                                    @endif
                                </div>
                            </th>
                            <th class="px-4 py-3 text-right cursor-pointer hover:bg-gray-700" wire:click="sort('price')">
                                <div class="flex items-center justify-end gap-1">
                                    Price
                                    @if($sortBy === 'price')
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="{{ $sortDirection === 'asc' ? 'M5 10l5-5 5 5H5z' : 'M5 10l5 5 5-5H5z' }}"/>
                                        </svg>
                                    @endif
                                </div>
                            </th>
                            <th class="px-4 py-3 text-right cursor-pointer hover:bg-gray-700" wire:click="sort('change_1h')">
                                <div class="flex items-center justify-end gap-1">
                                    1h %
                                    @if($sortBy === 'change_1h')
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="{{ $sortDirection === 'asc' ? 'M5 10l5-5 5 5H5z' : 'M5 10l5 5 5-5H5z' }}"/>
                                        </svg>
                                    @endif
                                </div>
                            </th>
                            <th class="px-4 py-3 text-right cursor-pointer hover:bg-gray-700" wire:click="sort('change_4h')">
                                <div class="flex items-center justify-end gap-1">
                                    4h %
                                    @if($sortBy === 'change_4h')
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="{{ $sortDirection === 'asc' ? 'M5 10l5-5 5 5H5z' : 'M5 10l5 5 5-5H5z' }}"/>
                                        </svg>
                                    @endif
                                </div>
                            </th>
                            <th class="px-4 py-3 text-right cursor-pointer hover:bg-gray-700" wire:click="sort('change_24h')">
                                <div class="flex items-center justify-end gap-1">
                                    24h %
                                    @if($sortBy === 'change_24h')
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="{{ $sortDirection === 'asc' ? 'M5 10l5-5 5 5H5z' : 'M5 10l5 5 5-5H5z' }}"/>
                                        </svg>
                                    @endif
                                </div>
                            </th>
                            <th class="px-4 py-3 text-right cursor-pointer hover:bg-gray-700" wire:click="sort('volume_24h')">
                                <div class="flex items-center justify-end gap-1">
                                    Volume 24h
                                    @if($sortBy === 'volume_24h')
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="{{ $sortDirection === 'asc' ? 'M5 10l5-5 5 5H5z' : 'M5 10l5 5 5-5H5z' }}"/>
                                        </svg>
                                    @endif
                                </div>
                            </th>
                            <th class="px-4 py-3 text-center cursor-pointer hover:bg-gray-700" wire:click="sort('rsi')">
                                <div class="flex items-center justify-center gap-1">
                                    RSI
                                    @if($sortBy === 'rsi')
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="{{ $sortDirection === 'asc' ? 'M5 10l5-5 5 5H5z' : 'M5 10l5 5 5-5H5z' }}"/>
                                        </svg>
                                    @endif
                                </div>
                            </th>
                            <th class="px-4 py-3 text-center">MACD</th>
                            <th class="px-4 py-3 text-center cursor-pointer hover:bg-gray-700" wire:click="sort('trend')">
                                <div class="flex items-center justify-center gap-1">
                                    Trend
                                    @if($sortBy === 'trend')
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="{{ $sortDirection === 'asc' ? 'M5 10l5-5 5 5H5z' : 'M5 10l5 5 5-5H5z' }}"/>
                                        </svg>
                                    @endif
                                </div>
                            </th>
                            <th class="px-4 py-3 text-center cursor-pointer hover:bg-gray-700" wire:click="sort('signal')">
                                <div class="flex items-center justify-center gap-1">
                                    Signal
                                    @if($sortBy === 'signal')
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="{{ $sortDirection === 'asc' ? 'M5 10l5-5 5 5H5z' : 'M5 10l5 5 5-5H5z' }}"/>
                                        </svg>
                                    @endif
                                </div>
                            </th>
                            <th class="px-4 py-3 text-center cursor-pointer hover:bg-gray-700" wire:click="sort('signal_strength')">
                                <div class="flex items-center justify-center gap-1">
                                    Strength
                                    @if($sortBy === 'signal_strength')
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="{{ $sortDirection === 'asc' ? 'M5 10l5-5 5 5H5z' : 'M5 10l5 5 5-5H5z' }}"/>
                                        </svg>
                                    @endif
                                </div>
                            </th>
                            <th class="px-4 py-3 text-right cursor-pointer hover:bg-gray-700" wire:click="sort('volatility')">
                                <div class="flex items-center justify-end gap-1">
                                    Volatility
                                    @if($sortBy === 'volatility')
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="{{ $sortDirection === 'asc' ? 'M5 10l5-5 5 5H5z' : 'M5 10l5 5 5-5H5z' }}"/>
                                        </svg>
                                    @endif
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        @foreach($markets as $market)
                            <tr class="hover:bg-gray-800 transition-colors">
                                <!-- Symbol -->
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-white">{{ $market['symbol'] }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ $market['volume_profile'] }}
                                    </div>
                                </td>

                                <!-- Price -->
                                <td class="px-4 py-3 text-right">
                                    <div class="font-mono font-semibold text-white">
                                        ${{ number_format($market['price'], 2) }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        S: ${{ number_format($market['support'], 2) }} / R: ${{ number_format($market['resistance'], 2) }}
                                    </div>
                                </td>

                                <!-- 1h Change -->
                                <td class="px-4 py-3 text-right">
                                    <span class="font-semibold {{ $market['change_1h'] >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                        {{ $market['change_1h'] >= 0 ? '+' : '' }}{{ number_format($market['change_1h'], 2) }}%
                                    </span>
                                </td>

                                <!-- 4h Change -->
                                <td class="px-4 py-3 text-right">
                                    <span class="font-semibold {{ $market['change_4h'] >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                        {{ $market['change_4h'] >= 0 ? '+' : '' }}{{ number_format($market['change_4h'], 2) }}%
                                    </span>
                                </td>

                                <!-- 24h Change -->
                                <td class="px-4 py-3 text-right">
                                    <span class="font-bold {{ $market['change_24h'] >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                        {{ $market['change_24h'] >= 0 ? '+' : '' }}{{ number_format($market['change_24h'], 2) }}%
                                    </span>
                                </td>

                                <!-- Volume -->
                                <td class="px-4 py-3 text-right">
                                    <div class="font-mono text-white">
                                        ${{ number_format($market['volume_24h'] / 1000000, 2) }}M
                                    </div>
                                    <div class="text-xs {{ $market['volume_change'] >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                        {{ $market['volume_change'] >= 0 ? '+' : '' }}{{ number_format($market['volume_change'], 1) }}%
                                    </div>
                                </td>

                                <!-- RSI -->
                                <td class="px-4 py-3 text-center">
                                    @php
                                        $rsiColor = 'text-yellow-400';
                                        if ($market['rsi'] < 30) $rsiColor = 'text-green-400';
                                        elseif ($market['rsi'] > 70) $rsiColor = 'text-red-400';
                                    @endphp
                                    <div class="inline-flex items-center gap-1">
                                        <span class="font-semibold {{ $rsiColor }}">
                                            {{ number_format($market['rsi'], 1) }}
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-800 rounded-full h-1.5 mt-1">
                                        <div class="h-1.5 rounded-full transition-all {{ $market['rsi'] < 30 ? 'bg-green-500' : ($market['rsi'] > 70 ? 'bg-red-500' : 'bg-yellow-500') }}"
                                             style="width: {{ $market['rsi'] }}%"></div>
                                    </div>
                                </td>

                                <!-- MACD -->
                                <td class="px-4 py-3 text-center">
                                    <div class="text-xs {{ $market['macd_histogram'] >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                        {{ $market['macd_histogram'] >= 0 ? '▲' : '▼' }}
                                        {{ abs($market['macd_histogram']) > 10 ? 'Strong' : 'Weak' }}
                                    </div>
                                </td>

                                <!-- Trend -->
                                <td class="px-4 py-3 text-center">
                                    @php
                                        $trendConfig = match($market['trend']) {
                                            'STRONG_UPTREND' => ['bg' => 'bg-green-600', 'text' => 'text-white', 'label' => 'Strong ↗'],
                                            'UPTREND' => ['bg' => 'bg-green-500', 'text' => 'text-white', 'label' => 'Up ↗'],
                                            'STRONG_DOWNTREND' => ['bg' => 'bg-red-600', 'text' => 'text-white', 'label' => 'Strong ↘'],
                                            'DOWNTREND' => ['bg' => 'bg-red-500', 'text' => 'text-white', 'label' => 'Down ↘'],
                                            default => ['bg' => 'bg-gray-600', 'text' => 'text-white', 'label' => 'Neutral →'],
                                        };
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-semibold rounded {{ $trendConfig['bg'] }} {{ $trendConfig['text'] }}">
                                        {{ $trendConfig['label'] }}
                                    </span>
                                </td>

                                <!-- Signal -->
                                <td class="px-4 py-3 text-center">
                                    @php
                                        $signalConfig = match($market['signal']) {
                                            'STRONG_BUY' => ['bg' => 'bg-green-600', 'text' => 'text-white', 'label' => 'STRONG BUY'],
                                            'BUY' => ['bg' => 'bg-green-500', 'text' => 'text-white', 'label' => 'BUY'],
                                            'STRONG_SELL' => ['bg' => 'bg-red-600', 'text' => 'text-white', 'label' => 'STRONG SELL'],
                                            'SELL' => ['bg' => 'bg-red-500', 'text' => 'text-white', 'label' => 'SELL'],
                                            default => ['bg' => 'bg-gray-600', 'text' => 'text-white', 'label' => 'NEUTRAL'],
                                        };
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-bold rounded {{ $signalConfig['bg'] }} {{ $signalConfig['text'] }}">
                                        {{ $signalConfig['label'] }}
                                    </span>
                                </td>

                                <!-- Signal Strength -->
                                <td class="px-4 py-3 text-center">
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="text-xs font-semibold {{ $market['signal_strength'] > 70 ? 'text-green-400' : ($market['signal_strength'] > 40 ? 'text-yellow-400' : 'text-gray-400') }}">
                                            {{ number_format($market['signal_strength'], 0) }}%
                                        </span>
                                        <div class="w-12 bg-gray-800 rounded-full h-1.5">
                                            <div class="h-1.5 rounded-full {{ $market['signal_strength'] > 70 ? 'bg-green-500' : ($market['signal_strength'] > 40 ? 'bg-yellow-500' : 'bg-gray-500') }}"
                                                 style="width: {{ $market['signal_strength'] }}%"></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Volatility -->
                                <td class="px-4 py-3 text-right">
                                    <span class="text-xs font-mono {{ $market['volatility'] > 50 ? 'text-red-400' : ($market['volatility'] > 30 ? 'text-yellow-400' : 'text-green-400') }}">
                                        {{ number_format($market['volatility'], 1) }}%
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <!-- Footer Summary -->
        <div class="px-4 py-3 bg-gray-800 border-t border-gray-700 grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
            <div>
                <div class="text-gray-400">Total Markets</div>
                <div class="text-white font-semibold">{{ count($markets) }}</div>
            </div>
            <div>
                <div class="text-gray-400">Buy Signals</div>
                <div class="text-green-400 font-semibold">
                    {{ collect($markets)->whereIn('signal', ['BUY', 'STRONG_BUY'])->count() }}
                </div>
            </div>
            <div>
                <div class="text-gray-400">Sell Signals</div>
                <div class="text-red-400 font-semibold">
                    {{ collect($markets)->whereIn('signal', ['SELL', 'STRONG_SELL'])->count() }}
                </div>
            </div>
            <div>
                <div class="text-gray-400">Neutral</div>
                <div class="text-gray-400 font-semibold">
                    {{ collect($markets)->where('signal', 'NEUTRAL')->count() }}
                </div>
            </div>
        </div>
    </div>
</div>
