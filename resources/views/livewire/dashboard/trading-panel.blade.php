<div class="space-y-4">
    <!-- Account Information Card -->
    <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
        <h3 class="text-sm font-semibold text-white mb-3 flex items-center gap-2">
            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
            </svg>
            Account Summary
        </h3>
        <div class="grid grid-cols-3 gap-3">
            <div class="bg-gray-800 rounded p-2">
                <div class="text-xs text-gray-400">Balance</div>
                <div class="text-sm font-bold text-white font-mono">${{ number_format($accountBalance, 2) }}</div>
            </div>
            <div class="bg-gray-800 rounded p-2">
                <div class="text-xs text-gray-400">Available</div>
                <div class="text-sm font-bold text-green-400 font-mono">${{ number_format($availableMargin, 2) }}</div>
            </div>
            <div class="bg-gray-800 rounded p-2">
                <div class="text-xs text-gray-400">Used</div>
                <div class="text-sm font-bold text-red-400 font-mono">${{ number_format($usedMargin, 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Quick Trading Panel -->
    <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
        <h3 class="text-sm font-semibold text-white mb-3 flex items-center gap-2">
            <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
            Quick Trade
        </h3>

        <!-- Alerts -->
        @if($orderSuccess)
            <div class="mb-3 p-3 bg-green-900 bg-opacity-30 border border-green-700 rounded text-green-400 text-xs">
                {{ $orderSuccess }}
            </div>
        @endif

        @if($orderError)
            <div class="mb-3 p-3 bg-red-900 bg-opacity-30 border border-red-700 rounded text-red-400 text-xs">
                {{ $orderError }}
            </div>
        @endif

        <!-- Symbol Selection -->
        <div class="mb-3">
            <label class="block text-xs text-gray-400 mb-1">Symbol</label>
            <select wire:model.live="symbol" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:border-blue-500 focus:outline-none">
                @foreach($tradingPairs as $pair)
                    <option value="{{ trim($pair) }}">{{ trim($pair) }}</option>
                @endforeach
            </select>
        </div>

        <!-- Current Price Display -->
        <div class="mb-3 p-3 bg-gray-800 rounded">
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-400">Current Price</span>
                <span class="text-lg font-bold text-white font-mono">${{ number_format($currentPrice, 2) }}</span>
            </div>
        </div>

        <!-- Buy/Sell Toggle -->
        <div class="mb-3 grid grid-cols-2 gap-2">
            <button wire:click="$set('side', 'BUY')"
                    class="px-4 py-2 rounded text-sm font-semibold transition-colors {{ $side === 'BUY' ? 'bg-green-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">
                BUY / LONG
            </button>
            <button wire:click="$set('side', 'SELL')"
                    class="px-4 py-2 rounded text-sm font-semibold transition-colors {{ $side === 'SELL' ? 'bg-red-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">
                SELL / SHORT
            </button>
        </div>

        <!-- Order Type -->
        <div class="mb-3">
            <label class="block text-xs text-gray-400 mb-1">Order Type</label>
            <div class="grid grid-cols-2 gap-2">
                <button wire:click="$set('orderType', 'MARKET')"
                        class="px-3 py-2 rounded text-xs font-semibold transition-colors {{ $orderType === 'MARKET' ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">
                    MARKET
                </button>
                <button wire:click="$set('orderType', 'LIMIT')"
                        class="px-3 py-2 rounded text-xs font-semibold transition-colors {{ $orderType === 'LIMIT' ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">
                    LIMIT
                </button>
            </div>
        </div>

        <!-- Quantity Input -->
        <div class="mb-3">
            <label class="block text-xs text-gray-400 mb-1">Quantity</label>
            <input type="number"
                   wire:model.live="quantity"
                   step="0.0001"
                   class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white font-mono text-sm focus:border-blue-500 focus:outline-none"
                   placeholder="0.0000">
        </div>

        <!-- Price Input (for LIMIT orders) -->
        @if($orderType === 'LIMIT')
            <div class="mb-3">
                <label class="block text-xs text-gray-400 mb-1">Limit Price</label>
                <input type="number"
                       wire:model.live="price"
                       step="0.01"
                       class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white font-mono text-sm focus:border-blue-500 focus:outline-none"
                       placeholder="0.00">
            </div>
        @endif

        <!-- Leverage Slider -->
        <div class="mb-3">
            <label class="block text-xs text-gray-400 mb-1">Leverage: <span class="text-white font-semibold">{{ $leverage }}x</span></label>
            <input type="range"
                   wire:model.live="leverage"
                   min="1"
                   max="125"
                   class="w-full h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer slider">
            <div class="flex justify-between text-xs text-gray-500 mt-1">
                <span>1x</span>
                <span>25x</span>
                <span>50x</span>
                <span>75x</span>
                <span>125x</span>
            </div>
        </div>

        <!-- Stop Loss Input -->
        <div class="mb-3">
            <label class="block text-xs text-gray-400 mb-1">Stop Loss (Optional)</label>
            <input type="number"
                   wire:model.live="stopLoss"
                   step="0.01"
                   class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white font-mono text-sm focus:border-blue-500 focus:outline-none"
                   placeholder="0.00">
        </div>

        <!-- Take Profit Input -->
        <div class="mb-3">
            <label class="block text-xs text-gray-400 mb-1">Take Profit (Optional)</label>
            <input type="number"
                   wire:model.live="takeProfit"
                   step="0.01"
                   class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white font-mono text-sm focus:border-blue-500 focus:outline-none"
                   placeholder="0.00">
        </div>

        <!-- Risk Percentage -->
        <div class="mb-3">
            <label class="block text-xs text-gray-400 mb-1">Risk %: <span class="text-white font-semibold">{{ number_format($riskPercentage, 1) }}%</span></label>
            <input type="range"
                   wire:model.live="riskPercentage"
                   min="0.5"
                   max="10"
                   step="0.5"
                   class="w-full h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer slider">
            <div class="flex justify-between text-xs text-gray-500 mt-1">
                <span>0.5%</span>
                <span>2%</span>
                <span>5%</span>
                <span>10%</span>
            </div>
        </div>

        <!-- Position Calculator Button -->
        <button wire:click="calculateOptimalSize"
                class="w-full mb-3 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded text-sm font-semibold transition-colors">
            Calculate Optimal Size
        </button>

        <!-- Position Summary -->
        @if($positionValue > 0)
            <div class="mb-3 p-3 bg-gray-800 rounded border border-gray-700">
                <h4 class="text-xs font-semibold text-gray-300 mb-2">Position Summary</h4>
                <div class="space-y-1 text-xs">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Position Value:</span>
                        <span class="text-white font-mono">${{ number_format($positionValue, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Required Margin:</span>
                        <span class="text-white font-mono">${{ number_format($requiredMargin, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Risk Amount:</span>
                        <span class="text-yellow-400 font-mono">${{ number_format($riskAmount, 2) }}</span>
                    </div>
                    @if($potentialProfit > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-400">Potential Profit:</span>
                            <span class="text-green-400 font-mono">+${{ number_format($potentialProfit, 2) }}</span>
                        </div>
                    @endif
                    @if($potentialLoss > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-400">Potential Loss:</span>
                            <span class="text-red-400 font-mono">-${{ number_format($potentialLoss, 2) }}</span>
                        </div>
                    @endif
                    @if($riskRewardRatio > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-400">Risk/Reward:</span>
                            <span class="font-mono {{ $riskRewardRatio >= 2 ? 'text-green-400' : ($riskRewardRatio >= 1 ? 'text-yellow-400' : 'text-red-400') }}">
                                1:{{ number_format($riskRewardRatio, 2) }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Place Order Button -->
        <button wire:click="placeOrder"
                wire:loading.attr="disabled"
                class="w-full px-4 py-3 rounded text-sm font-bold transition-colors {{ $side === 'BUY' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' }} text-white disabled:opacity-50 disabled:cursor-not-allowed">
            <span wire:loading.remove wire:target="placeOrder">
                {{ $side === 'BUY' ? 'PLACE BUY ORDER' : 'PLACE SELL ORDER' }}
            </span>
            <span wire:loading wire:target="placeOrder">
                Processing...
            </span>
        </button>

        <!-- Quick Action Buttons -->
        <div class="mt-3 grid grid-cols-2 gap-2">
            <button wire:click="quickBuy"
                    class="px-3 py-2 bg-green-700 hover:bg-green-800 text-white rounded text-xs font-semibold transition-colors">
                Quick Buy
            </button>
            <button wire:click="quickSell"
                    class="px-3 py-2 bg-red-700 hover:bg-red-800 text-white rounded text-xs font-semibold transition-colors">
                Quick Sell
            </button>
        </div>

        <!-- Close All Positions Button -->
        <button wire:click="closeAllPositions"
                onclick="return confirm('Are you sure you want to close all positions?')"
                class="w-full mt-3 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded text-xs font-semibold transition-colors">
            Close All Positions
        </button>
    </div>

    <!-- Recent Orders -->
    <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
        <h3 class="text-sm font-semibold text-white mb-3 flex items-center gap-2">
            <svg class="w-4 h-4 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Recent Orders
        </h3>

        @if(empty($recentOrders))
            <p class="text-gray-500 text-center py-4 text-xs">No recent orders</p>
        @else
            <div class="space-y-2">
                @foreach($recentOrders as $order)
                    <div class="bg-gray-800 rounded p-2 border border-gray-700">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-semibold text-white">{{ $order['symbol'] }}</span>
                            <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $order['side'] === 'BUY' ? 'bg-green-600 text-white' : 'bg-red-600 text-white' }}">
                                {{ $order['side'] }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <div>
                                <span class="text-gray-400">Qty:</span>
                                <span class="text-white font-mono">{{ $order['quantity'] }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400">@</span>
                                <span class="text-white font-mono">${{ number_format($order['price'], 2) }}</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-xs mt-1">
                            <span class="px-1.5 py-0.5 rounded text-xs {{ $order['status'] === 'OPEN' ? 'bg-blue-900 text-blue-300' : 'bg-gray-700 text-gray-400' }}">
                                {{ $order['status'] }}
                            </span>
                            <span class="text-gray-500">{{ $order['created_at'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<style>
    input[type="range"]::-webkit-slider-thumb {
        appearance: none;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #3B82F6;
        cursor: pointer;
    }

    input[type="range"]::-moz-range-thumb {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #3B82F6;
        cursor: pointer;
        border: none;
    }
</style>
