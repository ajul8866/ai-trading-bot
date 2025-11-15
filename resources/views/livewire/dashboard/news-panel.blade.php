<div class="bg-gray-900 rounded-lg border border-gray-800">
    <!-- Header -->
    <div class="bg-gray-800 border-b border-gray-700 px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                </svg>
                <h3 class="text-lg font-semibold text-white">Market News</h3>
                <span class="px-2.5 py-0.5 bg-blue-600 text-white text-xs font-semibold rounded-full">
                    {{ count($news) }}
                </span>
            </div>

            <!-- Controls -->
            <div class="flex items-center gap-2">
                <select wire:model.live="category" class="px-3 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="all">All News</option>
                    <option value="crypto">Crypto</option>
                    <option value="market">Market</option>
                    <option value="regulation">Regulation</option>
                </select>

                <button wire:click="loadNews" class="p-2 bg-gray-700 hover:bg-gray-600 rounded transition-colors" title="Refresh">
                    <svg class="w-4 h-4 text-gray-300 {{ $isLoading ? 'animate-spin' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- News List -->
    <div class="divide-y divide-gray-800 max-h-96 overflow-y-auto">
        @if($isLoading)
            <div class="flex items-center justify-center py-12">
                <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-500"></div>
            </div>
        @elseif(empty($news))
            <div class="p-12 text-center">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                </svg>
                <h3 class="text-lg font-semibold text-gray-400 mb-1">No News Available</h3>
                <p class="text-sm text-gray-500">Market news will appear here when available</p>
            </div>
        @else
            @foreach($news as $article)
                <div class="p-4 hover:bg-gray-800 transition-colors">
                    <div class="flex items-start gap-3">
                        <!-- Impact Indicator -->
                        <div class="flex-shrink-0 mt-1">
                            @if(isset($article['impact']))
                                @if($article['impact'] === 'high')
                                    <div class="w-2 h-2 rounded-full bg-red-500"></div>
                                @elseif($article['impact'] === 'medium')
                                    <div class="w-2 h-2 rounded-full bg-yellow-500"></div>
                                @else
                                    <div class="w-2 h-2 rounded-full bg-green-500"></div>
                                @endif
                            @else
                                <div class="w-2 h-2 rounded-full bg-gray-500"></div>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <!-- Title -->
                            <h4 class="text-sm font-semibold text-white hover:text-blue-400 transition-colors mb-1 line-clamp-2">
                                <a href="{{ $article['url'] ?? '#' }}" target="_blank" rel="noopener noreferrer">
                                    {{ $article['title'] }}
                                </a>
                            </h4>

                            <!-- Description -->
                            @if(isset($article['description']))
                                <p class="text-xs text-gray-400 mb-2 line-clamp-2">{{ $article['description'] }}</p>
                            @endif

                            <!-- Metadata -->
                            <div class="flex items-center gap-3 text-xs text-gray-500">
                                <!-- Source -->
                                @if(isset($article['source']))
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"/>
                                            <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"/>
                                        </svg>
                                        {{ $article['source'] }}
                                    </span>
                                @endif

                                <!-- Time -->
                                @if(isset($article['published_at']))
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ \Carbon\Carbon::parse($article['published_at'])->diffForHumans() }}
                                    </span>
                                @endif

                                <!-- Sentiment Badge -->
                                @if(isset($article['sentiment']))
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                        {{ $article['sentiment'] === 'positive' ? 'bg-green-900 bg-opacity-50 text-green-400' :
                                           ($article['sentiment'] === 'negative' ? 'bg-red-900 bg-opacity-50 text-red-400' :
                                           'bg-gray-700 text-gray-400') }}">
                                        {{ ucfirst($article['sentiment']) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <!-- Footer -->
    <div class="bg-gray-800 border-t border-gray-700 px-6 py-3">
        <div class="flex items-center justify-between text-xs text-gray-500">
            <span>Auto-refreshes every 15 minutes</span>
            <span>Last updated: {{ now()->format('H:i') }}</span>
        </div>
    </div>
</div>
