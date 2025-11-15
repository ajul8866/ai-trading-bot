<div>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">AI Trading Bot Dashboard</h1>
        <p class="mt-2 text-gray-600">Monitor your automated trading activity in real-time</p>
    </div>

    <!-- Bot Status Section -->
    <div class="mb-6">
        <livewire:bot-status />
    </div>

    <!-- Open Positions Section -->
    <div class="mb-6">
        <livewire:open-positions />
    </div>

    <!-- Recent Trades Section -->
    <div class="mb-6">
        <livewire:recent-trades />
    </div>

    <!-- Recent AI Decisions -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Recent AI Decisions</h2>

        @if($recentDecisions->isEmpty())
            <p class="text-gray-500 text-center py-8">No AI decisions yet</p>
        @else
            <div class="space-y-4">
                @foreach($recentDecisions as $decision)
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center space-x-4">
                                <span class="text-lg font-bold">{{ $decision->symbol }}</span>
                                <span class="px-3 py-1 rounded-full text-sm font-semibold
                                    {{ $decision->decision === 'BUY' ? 'bg-green-100 text-green-800' : ($decision->decision === 'SELL' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                                    {{ $decision->decision }}
                                </span>
                                <span class="text-sm text-gray-600">
                                    Confidence: <span class="font-semibold {{ $decision->confidence >= 75 ? 'text-green-600' : 'text-yellow-600' }}">{{ number_format($decision->confidence, 1) }}%</span>
                                </span>
                            </div>
                            <span class="text-sm text-gray-500">{{ $decision->analyzed_at->diffForHumans() }}</span>
                        </div>

                        <p class="text-sm text-gray-700 mb-2">{{ Str::limit($decision->reasoning, 150) }}</p>

                        @if($decision->executed)
                            <div class="flex items-center text-sm text-green-600">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Executed
                            </div>
                        @else
                            <div class="text-sm text-gray-500">Not executed</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
