<div class="bg-gray-900 rounded-lg border border-gray-800 overflow-hidden" wire:poll.30s>
    <!-- Chart Header -->
    <div class="bg-gray-800 border-b border-gray-700 p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-6">
                <!-- Symbol Selector -->
                <div class="flex items-center gap-2">
                    <label class="text-xs text-gray-400 uppercase tracking-wider">Symbol</label>
                    <select wire:model.live="symbol"
                            class="bg-gray-700 text-white text-sm rounded px-3 py-1.5 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach($availableSymbols as $sym)
                            <option value="{{ $sym }}">{{ $sym }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Timeframe Selector -->
                <div class="flex items-center gap-2">
                    <label class="text-xs text-gray-400 uppercase tracking-wider">Timeframe</label>
                    <div class="flex gap-1">
                        @foreach($availableTimeframes as $tf)
                            <button wire:click="$set('timeframe', '{{ $tf }}')"
                                    class="px-3 py-1.5 text-xs font-medium rounded transition-colors
                                           {{ $timeframe === $tf
                                               ? 'bg-blue-600 text-white'
                                               : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                                {{ strtoupper($tf) }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Price Info -->
            <div class="flex items-center gap-6">
                <div class="text-right">
                    <div class="text-2xl font-bold {{ $priceChange >= 0 ? 'text-green-400' : 'text-red-400' }}">
                        ${{ number_format($currentPrice, 2) }}
                    </div>
                    <div class="text-sm {{ $priceChange >= 0 ? 'text-green-400' : 'text-red-400' }}">
                        {{ $priceChange >= 0 ? '+' : '' }}{{ number_format($priceChange, 2) }}
                        ({{ $priceChange >= 0 ? '+' : '' }}{{ number_format($priceChangePercent, 2) }}%)
                    </div>
                </div>

                <button wire:click="refresh"
                        class="p-2 bg-gray-700 hover:bg-gray-600 rounded transition-colors"
                        title="Refresh Chart">
                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Chart Container -->
    <div class="relative">
        @if($isLoading)
            <div class="absolute inset-0 flex items-center justify-center bg-gray-900 bg-opacity-75 z-10">
                <div class="flex flex-col items-center gap-3">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
                    <div class="text-gray-400 text-sm">Loading chart data...</div>
                </div>
            </div>
        @endif

        <div id="trading-chart-{{ $symbol }}"
             class="w-full"
             style="height: 500px;"
             wire:ignore
             x-data="{
                 chart: null,
                 candlestickSeries: null,
                 volumeSeries: null,
                 init() {
                     this.createChart();

                     // Listen for Livewire updates
                     Livewire.on('refresh-chart', () => {
                         this.updateChart();
                     });
                 },
                 createChart() {
                     const chartContainer = this.$el;

                     // Create chart
                     this.chart = window.createChart(chartContainer, {
                         layout: {
                             background: { color: '#111827' },
                             textColor: '#9CA3AF',
                         },
                         grid: {
                             vertLines: { color: '#1F2937' },
                             horzLines: { color: '#1F2937' },
                         },
                         crosshair: {
                             mode: 1,
                         },
                         rightPriceScale: {
                             borderColor: '#374151',
                         },
                         timeScale: {
                             borderColor: '#374151',
                             timeVisible: true,
                             secondsVisible: false,
                         },
                         watermark: {
                             visible: true,
                             fontSize: 24,
                             horzAlign: 'center',
                             vertAlign: 'center',
                             color: 'rgba(255, 255, 255, 0.05)',
                             text: '{{ $symbol }}',
                         },
                     });

                     // Add candlestick series
                     this.candlestickSeries = this.chart.addCandlestickSeries({
                         upColor: '#10B981',
                         downColor: '#EF4444',
                         borderUpColor: '#10B981',
                         borderDownColor: '#EF4444',
                         wickUpColor: '#10B981',
                         wickDownColor: '#EF4444',
                     });

                     // Add volume series
                     this.volumeSeries = this.chart.addHistogramSeries({
                         color: '#26a69a',
                         priceFormat: {
                             type: 'volume',
                         },
                         priceScaleId: '',
                         scaleMargins: {
                             top: 0.8,
                             bottom: 0,
                         },
                     });

                     // Load initial data
                     this.updateChart();

                     // Handle resize
                     window.addEventListener('resize', () => {
                         this.chart.applyOptions({
                             width: chartContainer.clientWidth
                         });
                     });
                 },
                 updateChart() {
                     const chartData = @js($chartData);

                     if (chartData && chartData.length > 0) {
                         // Update candlestick data
                         this.candlestickSeries.setData(chartData.map(d => ({
                             time: d.time,
                             open: d.open,
                             high: d.high,
                             low: d.low,
                             close: d.close,
                         })));

                         // Update volume data
                         this.volumeSeries.setData(chartData.map(d => ({
                             time: d.time,
                             value: d.volume,
                             color: d.close >= d.open ? 'rgba(16, 185, 129, 0.4)' : 'rgba(239, 68, 68, 0.4)',
                         })));

                         // Fit content
                         this.chart.timeScale().fitContent();
                     }
                 }
             }">
        </div>
    </div>

    <!-- Chart Footer Stats -->
    <div class="bg-gray-800 border-t border-gray-700 p-3">
        <div class="grid grid-cols-5 gap-4 text-center">
            <div>
                <div class="text-xs text-gray-400 mb-1">Open</div>
                <div class="text-sm font-semibold text-gray-200">
                    ${{ !empty($chartData) ? number_format(reset($chartData)['open'] ?? 0, 2) : '0.00' }}
                </div>
            </div>
            <div>
                <div class="text-xs text-gray-400 mb-1">High</div>
                <div class="text-sm font-semibold text-green-400">
                    ${{ !empty($chartData) ? number_format(max(array_column($chartData, 'high')), 2) : '0.00' }}
                </div>
            </div>
            <div>
                <div class="text-xs text-gray-400 mb-1">Low</div>
                <div class="text-sm font-semibold text-red-400">
                    ${{ !empty($chartData) ? number_format(min(array_column($chartData, 'low')), 2) : '0.00' }}
                </div>
            </div>
            <div>
                <div class="text-xs text-gray-400 mb-1">Close</div>
                <div class="text-sm font-semibold text-gray-200">
                    ${{ number_format($currentPrice, 2) }}
                </div>
            </div>
            <div>
                <div class="text-xs text-gray-400 mb-1">Volume</div>
                <div class="text-sm font-semibold text-blue-400">
                    {{ !empty($chartData) ? number_format(array_sum(array_column($chartData, 'volume'))) : '0' }}
                </div>
            </div>
        </div>
    </div>
</div>
