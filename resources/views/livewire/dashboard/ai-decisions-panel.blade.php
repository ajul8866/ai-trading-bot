<div class="bg-gray-900 rounded-lg border border-gray-800">
    <!-- Header -->
    <div class="bg-gray-800 border-b border-gray-700 px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h3 class="text-lg font-semibold text-white">AI Trading Decisions</h3>
                <span class="px-2.5 py-0.5 bg-blue-600 text-white text-xs font-semibold rounded-full">
                    {{ $decisions->count() }}
                </span>
            </div>

            <!-- Filter -->
            <div class="flex items-center gap-2">
                <select wire:model.live="filter" class="px-3 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="all">All Decisions</option>
                    <option value="buy">Buy Only</option>
                    <option value="sell">Sell Only</option>
                    <option value="hold">Hold Only</option>
                    <option value="executed">Executed Only</option>
                </select>

                <button wire:click="loadDecisions" class="p-2 bg-gray-700 hover:bg-gray-600 rounded transition-colors" title="Refresh">
                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Decisions List -->
    <div class="divide-y divide-gray-800">
        @forelse($decisions as $decision)
            <div class="p-6 hover:bg-gray-800 transition-colors">
                <!-- Header Row -->
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <!-- Symbol -->
                        <div class="text-lg font-bold text-white">{{ $decision->symbol }}</div>

                        <!-- Decision Badge -->
                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                            {{ $decision->decision === 'BUY' ? 'bg-green-900 text-green-300 border border-green-700' :
                               ($decision->decision === 'SELL' ? 'bg-red-900 text-red-300 border border-red-700' :
                               ($decision->decision === 'CLOSE' ? 'bg-orange-900 text-orange-300 border border-orange-700' :
                               'bg-gray-700 text-gray-300 border border-gray-600')) }}">
                            {{ $decision->decision }}
                        </span>

                        <!-- Confidence Badge -->
                        <span class="px-2 py-1 rounded text-xs font-semibold
                            {{ $decision->confidence >= 80 ? 'bg-green-900 bg-opacity-50 text-green-400' :
                               ($decision->confidence >= 60 ? 'bg-yellow-900 bg-opacity-50 text-yellow-400' :
                               'bg-red-900 bg-opacity-50 text-red-400') }}">
                            {{ number_format($decision->confidence, 0) }}% confidence
                        </span>

                        <!-- Executed Badge -->
                        @if($decision->executed)
                            <span class="flex items-center gap-1 px-2 py-1 bg-blue-900 bg-opacity-50 text-blue-300 rounded text-xs font-semibold">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Executed
                            </span>
                        @endif
                    </div>

                    <!-- Time -->
                    <div class="text-xs text-gray-500">
                        {{ $decision->analyzed_at->diffForHumans() }}
                    </div>
                </div>

                <!-- Reasoning -->
                <div class="mb-3">
                    <p class="text-sm text-gray-300 leading-relaxed">{{ $decision->reasoning }}</p>
                </div>

                <!-- Market Conditions & Risk Assessment Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                    <!-- Market Conditions -->
                    @if($decision->market_conditions)
                        <div class="bg-gray-800 rounded-lg p-3 border border-gray-700">
                            <div class="text-xs text-gray-400 uppercase tracking-wider mb-2">Market Conditions</div>
                            <div class="space-y-1">
                                @if(isset($decision->market_conditions['trend']))
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Trend:</span>
                                        <span class="font-semibold
                                            {{ $decision->market_conditions['trend'] === 'bullish' ? 'text-green-400' :
                                               ($decision->market_conditions['trend'] === 'bearish' ? 'text-red-400' : 'text-gray-400') }}">
                                            {{ ucfirst($decision->market_conditions['trend']) }}
                                        </span>
                                    </div>
                                @endif
                                @if(isset($decision->market_conditions['volatility']))
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Volatility:</span>
                                        <span class="font-semibold text-yellow-400">{{ ucfirst($decision->market_conditions['volatility']) }}</span>
                                    </div>
                                @endif
                                @if(isset($decision->market_conditions['strength']))
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Strength:</span>
                                        <span class="font-semibold text-blue-400">{{ ucfirst($decision->market_conditions['strength']) }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Risk Assessment -->
                    @if($decision->risk_assessment)
                        <div class="bg-gray-800 rounded-lg p-3 border border-gray-700">
                            <div class="text-xs text-gray-400 uppercase tracking-wider mb-2">Risk Assessment</div>
                            <div class="space-y-1">
                                @if(isset($decision->risk_assessment['risk_level']))
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Risk Level:</span>
                                        <span class="font-semibold
                                            {{ $decision->risk_assessment['risk_level'] === 'low' ? 'text-green-400' :
                                               ($decision->risk_assessment['risk_level'] === 'high' ? 'text-red-400' : 'text-yellow-400') }}">
                                            {{ ucfirst($decision->risk_assessment['risk_level']) }}
                                        </span>
                                    </div>
                                @endif
                                @if(isset($decision->risk_assessment['reward_ratio']))
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Reward Ratio:</span>
                                        <span class="font-semibold text-green-400">1:{{ number_format($decision->risk_assessment['reward_ratio'], 2) }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Trade Parameters (if not HOLD) -->
                @if($decision->decision !== 'HOLD' && ($decision->recommended_leverage || $decision->recommended_stop_loss || $decision->recommended_take_profit))
                    <div class="bg-gray-800 rounded-lg p-3 border border-gray-700">
                        <div class="text-xs text-gray-400 uppercase tracking-wider mb-2">Recommended Parameters</div>
                        <div class="grid grid-cols-3 gap-4">
                            @if($decision->recommended_leverage)
                                <div>
                                    <div class="text-xs text-gray-500">Leverage</div>
                                    <div class="text-sm font-semibold text-purple-400">{{ $decision->recommended_leverage }}x</div>
                                </div>
                            @endif
                            @if($decision->recommended_stop_loss)
                                <div>
                                    <div class="text-xs text-gray-500">Stop Loss</div>
                                    <div class="text-sm font-semibold text-red-400">${{ number_format($decision->recommended_stop_loss, 2) }}</div>
                                </div>
                            @endif
                            @if($decision->recommended_take_profit)
                                <div>
                                    <div class="text-xs text-gray-500">Take Profit</div>
                                    <div class="text-sm font-semibold text-green-400">${{ number_format($decision->recommended_take_profit, 2) }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Associated Trades -->
                @if($decision->trades && $decision->trades->count() > 0)
                    <div class="mt-3 pt-3 border-t border-gray-700">
                        <div class="text-xs text-gray-400 uppercase tracking-wider mb-2">Associated Trades</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach($decision->trades as $trade)
                                <div class="px-2 py-1 bg-gray-800 rounded border border-gray-700 text-xs">
                                    <span class="text-gray-400">{{ $trade->symbol }}</span>
                                    <span class="font-semibold {{ $trade->pnl >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                        {{ $trade->pnl ? (($trade->pnl >= 0 ? '+' : '') . '$' . number_format($trade->pnl, 2)) : 'Open' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Execution Error -->
                @if($decision->execution_error)
                    <div class="mt-3 p-2 bg-red-900 bg-opacity-20 border border-red-800 rounded">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-red-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <div class="text-xs font-semibold text-red-300 mb-1">Execution Error</div>
                                <div class="text-xs text-red-400">{{ $decision->execution_error }}</div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="p-12 text-center">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                <h3 class="text-lg font-semibold text-gray-400 mb-1">No AI Decisions Yet</h3>
                <p class="text-sm text-gray-500">AI decisions will appear here when the bot analyzes trading opportunities</p>
            </div>
        @endforelse
    </div>
</div>
