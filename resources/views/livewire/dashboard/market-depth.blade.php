<div class="bg-gray-900 rounded-lg border border-gray-800 overflow-hidden">
    <!-- Header -->
    <div class="px-4 py-3 border-b border-gray-800 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <h3 class="text-sm font-semibold text-white">Market Depth</h3>
            <span class="text-xs text-gray-500">{{ $symbol }}</span>
        </div>
        <div class="flex items-center gap-4 text-xs">
            <div>
                <span class="text-gray-400">Mid Price:</span>
                <span class="text-white font-semibold font-mono ml-1">
                    ${{ number_format($midPrice, 2) }}
                </span>
            </div>
        </div>
    </div>

    <!-- Depth Chart Visualization -->
    <div class="p-4">
        <div class="bg-gray-950 rounded-lg border border-gray-800 p-4" style="height: 350px;">
            <div class="relative w-full h-full"
                 x-data="{
                     hoveredPrice: null,
                     hoveredDepth: null,
                     depthData: @js($depthData),
                     maxBidDepth: @js($maxBidDepth),
                     maxAskDepth: @js($maxAskDepth),
                     midPrice: @js($midPrice),

                     getX(price, isAsk) {
                         const container = this.$el.querySelector('.chart-container');
                         if (!container) return 0;
                         const width = container.clientWidth;
                         const midPoint = width / 2;

                         if (isAsk) {
                             // Asks on right side
                             const priceRange = (this.depthData.asks[this.depthData.asks.length - 1]?.price || this.midPrice) - this.midPrice;
                             const offset = ((price - this.midPrice) / priceRange) * (midPoint - 20);
                             return midPoint + offset;
                         } else {
                             // Bids on left side
                             const priceRange = this.midPrice - (this.depthData.bids[this.depthData.bids.length - 1]?.price || this.midPrice);
                             const offset = ((this.midPrice - price) / priceRange) * (midPoint - 20);
                             return midPoint - offset;
                         }
                     },

                     getY(cumulative, maxDepth) {
                         const container = this.$el.querySelector('.chart-container');
                         if (!container) return 0;
                         const height = container.clientHeight - 40;
                         return height - ((cumulative / maxDepth) * height);
                     }
                 }">

                <div class="chart-container relative w-full h-full">
                    <!-- SVG Canvas -->
                    <svg class="w-full h-full" viewBox="0 0 800 300" preserveAspectRatio="none">
                        <!-- Grid Lines -->
                        <line x1="0" y1="60" x2="800" y2="60" stroke="#374151" stroke-width="0.5" stroke-dasharray="5,5"/>
                        <line x1="0" y1="120" x2="800" y2="120" stroke="#374151" stroke-width="0.5" stroke-dasharray="5,5"/>
                        <line x1="0" y1="180" x2="800" y2="180" stroke="#374151" stroke-width="0.5" stroke-dasharray="5,5"/>
                        <line x1="0" y1="240" x2="800" y2="240" stroke="#374151" stroke-width="0.5" stroke-dasharray="5,5"/>

                        <!-- Mid Price Line -->
                        <line x1="400" y1="0" x2="400" y2="300" stroke="#FBBF24" stroke-width="2" stroke-dasharray="10,5"/>

                        <!-- Bid Area (Green) -->
                        @if(!empty($depthData['bids']))
                            <path
                                d="M 400,300
                                   @foreach($depthData['bids'] as $index => $bid)
                                       @php
                                           $x = 400 - ($index / count($depthData['bids'])) * 390;
                                           $y = 300 - (($bid['cumulative'] / $maxBidDepth) * 280);
                                       @endphp
                                       L {{ $x }},{{ $y }}
                                   @endforeach
                                   L 10,300 Z"
                                fill="rgba(16, 185, 129, 0.15)"
                                stroke="#10B981"
                                stroke-width="2"/>
                        @endif

                        <!-- Ask Area (Red) -->
                        @if(!empty($depthData['asks']))
                            <path
                                d="M 400,300
                                   @foreach($depthData['asks'] as $index => $ask)
                                       @php
                                           $x = 400 + ($index / count($depthData['asks'])) * 390;
                                           $y = 300 - (($ask['cumulative'] / $maxAskDepth) * 280);
                                       @endphp
                                       L {{ $x }},{{ $y }}
                                   @endforeach
                                   L 790,300 Z"
                                fill="rgba(239, 68, 68, 0.15)"
                                stroke="#EF4444"
                                stroke-width="2"/>
                        @endif
                    </svg>

                    <!-- Legend & Info Overlay -->
                    <div class="absolute top-2 left-2 flex gap-4 text-xs">
                        <div class="flex items-center gap-1.5 bg-gray-900 bg-opacity-90 px-2 py-1 rounded">
                            <div class="w-3 h-3 bg-green-500 rounded-sm"></div>
                            <span class="text-gray-300">Bids</span>
                        </div>
                        <div class="flex items-center gap-1.5 bg-gray-900 bg-opacity-90 px-2 py-1 rounded">
                            <div class="w-3 h-3 bg-red-500 rounded-sm"></div>
                            <span class="text-gray-300">Asks</span>
                        </div>
                    </div>

                    <!-- Mid Price Label -->
                    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                        <div class="bg-yellow-500 bg-opacity-90 px-2 py-1 rounded text-xs font-semibold text-gray-900">
                            Mid: ${{ number_format($midPrice, 2) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Depth Statistics -->
    <div class="px-4 pb-4 grid grid-cols-2 gap-4">
        <div class="bg-gray-950 rounded-lg border border-gray-800 p-3">
            <div class="text-xs text-gray-400 mb-1">Total Bid Depth</div>
            <div class="text-lg font-bold text-green-400 font-mono">
                {{ number_format($maxBidDepth, 2) }}
            </div>
            <div class="text-xs text-gray-500 mt-1">
                {{ count($depthData['bids'] ?? []) }} levels
            </div>
        </div>

        <div class="bg-gray-950 rounded-lg border border-gray-800 p-3">
            <div class="text-xs text-gray-400 mb-1">Total Ask Depth</div>
            <div class="text-lg font-bold text-red-400 font-mono">
                {{ number_format($maxAskDepth, 2) }}
            </div>
            <div class="text-xs text-gray-500 mt-1">
                {{ count($depthData['asks'] ?? []) }} levels
            </div>
        </div>
    </div>

    <!-- Depth Ratio Indicator -->
    <div class="px-4 pb-4">
        <div class="bg-gray-950 rounded-lg border border-gray-800 p-3">
            <div class="text-xs text-gray-400 mb-2">Bid/Ask Ratio</div>
            @php
                $totalDepth = $maxBidDepth + $maxAskDepth;
                $bidPercentage = $totalDepth > 0 ? ($maxBidDepth / $totalDepth) * 100 : 50;
                $askPercentage = 100 - $bidPercentage;
            @endphp
            <div class="flex items-center gap-2">
                <div class="flex-1 h-3 bg-gray-800 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-green-500 to-green-600"
                         style="width: {{ $bidPercentage }}%"></div>
                </div>
                <span class="text-xs font-semibold {{ $bidPercentage > 50 ? 'text-green-400' : 'text-red-400' }}">
                    {{ number_format($bidPercentage, 1) }}% / {{ number_format($askPercentage, 1) }}%
                </span>
            </div>
            <div class="flex justify-between text-xs text-gray-500 mt-1">
                <span>{{ $bidPercentage > 50 ? 'Bullish' : 'Bearish' }} pressure</span>
                <span>{{ abs(50 - $bidPercentage) > 10 ? 'Strong' : 'Weak' }}</span>
            </div>
        </div>
    </div>
</div>
