<div class="min-h-screen bg-gray-950 p-6">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold text-white">AI Trading Decisions</h1>
            <button wire:click="loadStats" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refresh Stats
            </button>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-8">
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
                <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Total Decisions</div>
                <div class="text-2xl font-bold text-white">{{ number_format($totalDecisions) }}</div>
            </div>
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
                <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Executed</div>
                <div class="text-2xl font-bold text-green-400">{{ number_format($executedCount) }}</div>
            </div>
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
                <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Avg Confidence</div>
                <div class="text-2xl font-bold text-blue-400">{{ $avgConfidence }}%</div>
            </div>
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
                <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">BUY Signals</div>
                <div class="text-2xl font-bold text-green-500">{{ number_format($buyCount) }}</div>
            </div>
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
                <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">SELL Signals</div>
                <div class="text-2xl font-bold text-red-500">{{ number_format($sellCount) }}</div>
            </div>
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
                <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">HOLD Signals</div>
                <div class="text-2xl font-bold text-yellow-400">{{ number_format($holdCount) }}</div>
            </div>
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
                <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">High Confidence</div>
                <div class="text-2xl font-bold text-purple-400">{{ number_format($highConfidenceCount) }}</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-gray-900 rounded-lg border border-gray-800 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-white">Filters</h3>
                <button wire:click="clearFilters" class="text-sm text-gray-400 hover:text-white transition-colors">
                    Clear All Filters
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Symbol Filter -->
                <div>
                    <label class="block text-xs text-gray-400 uppercase tracking-wider mb-2">Symbol</label>
                    <select wire:model.live="filterSymbol" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">All Symbols</option>
                        @foreach($symbols as $sym)
                            <option value="{{ $sym }}">{{ $sym }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Decision Filter -->
                <div>
                    <label class="block text-xs text-gray-400 uppercase tracking-wider mb-2">Decision</label>
                    <select wire:model.live="filterDecision" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">All Decisions</option>
                        <option value="BUY">BUY</option>
                        <option value="SELL">SELL</option>
                        <option value="HOLD">HOLD</option>
                        <option value="CLOSE">CLOSE</option>
                    </select>
                </div>

                <!-- Executed Filter -->
                <div>
                    <label class="block text-xs text-gray-400 uppercase tracking-wider mb-2">Execution</label>
                    <select wire:model.live="filterExecuted" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">All</option>
                        <option value="yes">Executed Only</option>
                        <option value="no">Not Executed</option>
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <label class="block text-xs text-gray-400 uppercase tracking-wider mb-2">From Date</label>
                    <input type="date" wire:model.live="filterDateFrom" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-xs text-gray-400 uppercase tracking-wider mb-2">To Date</label>
                    <input type="date" wire:model.live="filterDateTo" class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white text-sm focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Decisions List -->
        <div class="bg-gray-900 rounded-lg border border-gray-800 overflow-hidden">
            <!-- Table Header -->
            <div class="bg-gray-800 border-b border-gray-700 p-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-white">Decision History</h3>
                    <div class="text-sm text-gray-400">
                        Showing {{ $decisions->firstItem() ?? 0 }} - {{ $decisions->lastItem() ?? 0 }} of {{ $decisions->total() }}
                    </div>
                </div>
            </div>

            <!-- Decisions -->
            <div class="divide-y divide-gray-800">
                @forelse($decisions as $decision)
                    <div class="p-6 hover:bg-gray-800/50 transition-colors">
                        <!-- Header Row -->
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-3 flex-wrap">
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

                                <!-- Timeframes -->
                                @if($decision->timeframes_analyzed)
                                    <div class="flex gap-1">
                                        @foreach($decision->timeframes_analyzed as $tf)
                                            <span class="px-1.5 py-0.5 bg-gray-700 text-gray-300 rounded text-xs">{{ $tf }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <!-- Time -->
                            <div class="text-right">
                                <div class="text-xs text-gray-500">{{ $decision->analyzed_at->format('Y-m-d H:i:s') }}</div>
                                <div class="text-xs text-gray-600">{{ $decision->analyzed_at->diffForHumans() }}</div>
                            </div>
                        </div>

                        <!-- Reasoning -->
                        <div class="mb-3">
                            <p class="text-sm text-gray-300 leading-relaxed">{{ $decision->reasoning }}</p>
                        </div>

                        <!-- Market Conditions & Risk Assessment Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                            <!-- Market Conditions -->
                            @if($decision->market_conditions && !isset($decision->market_conditions['error']))
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
                            @elseif(isset($decision->market_conditions['error']))
                                <div class="bg-red-900/20 rounded-lg p-3 border border-red-800">
                                    <div class="text-xs text-red-400 uppercase tracking-wider mb-2">Market Conditions</div>
                                    <div class="text-sm text-red-300">Error fetching market data</div>
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
                            <div class="bg-gray-800 rounded-lg p-3 border border-gray-700 mb-3">
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
                            <div class="pt-3 border-t border-gray-700">
                                <div class="text-xs text-gray-400 uppercase tracking-wider mb-2">Associated Trades</div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($decision->trades as $trade)
                                        <div class="px-3 py-1.5 bg-gray-800 rounded border border-gray-700 text-xs">
                                            <span class="text-gray-400">{{ $trade->side }}</span>
                                            <span class="text-white font-semibold">{{ $trade->quantity }}</span>
                                            <span class="text-gray-400">@</span>
                                            <span class="text-white">${{ number_format($trade->entry_price, 2) }}</span>
                                            <span class="font-semibold ml-2 {{ $trade->pnl >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                                {{ $trade->pnl ? (($trade->pnl >= 0 ? '+' : '') . '$' . number_format($trade->pnl, 2)) : ($trade->status === 'OPEN' ? 'Open' : 'Pending') }}
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
                        <h3 class="text-lg font-semibold text-gray-400 mb-1">No AI Decisions Found</h3>
                        <p class="text-sm text-gray-500">No decisions match your current filters</p>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($decisions->hasPages())
                <div class="bg-gray-800 border-t border-gray-700 p-4">
                    {{ $decisions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
