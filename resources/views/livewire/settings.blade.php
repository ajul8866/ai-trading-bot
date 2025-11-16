<div class="min-h-screen bg-gray-950 p-6">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold text-white mb-8">🔧 Bot Configuration</h1>

        @if($message)
            <div class="mb-6 p-4 rounded-lg {{ $messageType === 'success' ? 'bg-green-900/50 text-green-200 border border-green-700' : 'bg-red-900/50 text-red-200 border border-red-700' }}">
                {{ $message }}
            </div>
        @endif

        <form wire:submit="save" class="space-y-8">
            <!-- Bot Status -->
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-2">
                    <span class="text-2xl">🤖</span> Bot Status
                </h2>
                <div class="flex items-center gap-4">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="bot_enabled" class="sr-only peer">
                        <div class="w-14 h-7 bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-green-600"></div>
                    </label>
                    <span class="text-lg {{ $bot_enabled ? 'text-green-400' : 'text-red-400' }}">
                        {{ $bot_enabled ? 'BOT ENABLED' : 'BOT DISABLED' }}
                    </span>
                </div>
            </div>

            <!-- API Keys -->
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-2">
                    <span class="text-2xl">🔑</span> API Keys
                </h2>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Binance API Key</label>
                        <div class="flex gap-2">
                            <input type="password" wire:model="binance_api_key"
                                   class="flex-1 bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:ring-2 focus:ring-blue-500"
                                   placeholder="Your Binance Futures API Key">
                            <button type="button" wire:click="testBinanceConnection"
                                    class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded transition-colors">
                                Test
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Binance API Secret</label>
                        <input type="password" wire:model="binance_api_secret"
                               class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:ring-2 focus:ring-blue-500"
                               placeholder="Your Binance API Secret">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">OpenRouter API Key</label>
                        <div class="flex gap-2">
                            <input type="password" wire:model="openrouter_api_key"
                                   class="flex-1 bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:ring-2 focus:ring-blue-500"
                                   placeholder="sk-or-...">
                            <button type="button" wire:click="testOpenRouterConnection"
                                    class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded transition-colors">
                                Test
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Get your API key from <a href="https://openrouter.ai/keys" target="_blank" class="text-blue-400 hover:underline">openrouter.ai/keys</a></p>
                    </div>
                </div>
            </div>

            <!-- Trading Configuration -->
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-2">
                    <span class="text-2xl">📊</span> Trading Configuration
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Trading Pairs</label>
                        <textarea wire:model="trading_pairs" rows="3"
                                  class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                                  placeholder="BTCUSDT,ETHUSDT,BNBUSDT"></textarea>
                        <p class="text-xs text-gray-400 mt-1">Comma-separated list of Binance Futures pairs (e.g., BTCUSDT,ETHUSDT,BNBUSDT)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Timeframes</label>
                        <input type="text" wire:model="timeframes"
                               class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:ring-2 focus:ring-blue-500"
                               placeholder="5m,15m,30m,1h">
                        <p class="text-xs text-gray-400 mt-1">Comma-separated (1m,3m,5m,15m,30m,1h,2h,4h,1d)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Analysis Interval (seconds)</label>
                        <input type="number" wire:model="analysis_interval"
                               class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:ring-2 focus:ring-blue-500"
                               min="60" max="3600" step="30">
                        <p class="text-xs text-gray-400 mt-1">How often to analyze markets (default: 180 = 3 min)</p>
                    </div>
                </div>
            </div>

            <!-- Risk Management -->
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-2">
                    <span class="text-2xl">⚠️</span> Risk Management
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Max Concurrent Positions</label>
                        <input type="number" wire:model="max_positions"
                               class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:ring-2 focus:ring-blue-500"
                               min="1" max="20">
                        <p class="text-xs text-gray-400 mt-1">Maximum open trades at once</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Risk Per Trade (%)</label>
                        <input type="number" wire:model="risk_per_trade"
                               class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:ring-2 focus:ring-blue-500"
                               min="0.1" max="10" step="0.1">
                        <p class="text-xs text-gray-400 mt-1">Max % of balance to risk per trade</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Daily Loss Limit (%)</label>
                        <input type="number" wire:model="daily_loss_limit"
                               class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:ring-2 focus:ring-blue-500"
                               min="1" max="50" step="0.5">
                        <p class="text-xs text-gray-400 mt-1">Stop trading if daily loss exceeds this %</p>
                    </div>
                </div>
            </div>

            <!-- AI Configuration -->
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-2">
                    <span class="text-2xl">🧠</span> AI Configuration
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">AI Model</label>
                        <select wire:model="ai_model"
                                class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:ring-2 focus:ring-blue-500">
                            <option value="anthropic/claude-3.5-sonnet">Claude 3.5 Sonnet (Recommended)</option>
                            <option value="anthropic/claude-3-opus">Claude 3 Opus (Most Capable)</option>
                            <option value="anthropic/claude-3-haiku">Claude 3 Haiku (Fast & Cheap)</option>
                            <option value="openai/gpt-4-turbo">GPT-4 Turbo</option>
                            <option value="openai/gpt-4">GPT-4</option>
                            <option value="openai/gpt-3.5-turbo">GPT-3.5 Turbo (Cheapest)</option>
                            <option value="google/gemini-pro">Gemini Pro</option>
                            <option value="meta-llama/llama-3-70b-instruct">Llama 3 70B</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Minimum Confidence (%)</label>
                        <input type="number" wire:model="min_confidence"
                               class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:ring-2 focus:ring-blue-500"
                               min="50" max="100">
                        <p class="text-xs text-gray-400 mt-1">Only execute trades if AI confidence >= this %</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Risk Profile</label>
                        <select wire:model="ai_prompt_risk_profile"
                                class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:ring-2 focus:ring-blue-500">
                            <option value="conservative">Conservative (Safe, Lower Returns)</option>
                            <option value="balanced">Balanced (Moderate Risk/Return)</option>
                            <option value="aggressive">Aggressive (High Risk/Return)</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">AI System Prompt</label>
                        <textarea wire:model="ai_prompt_system" rows="4"
                                  class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 text-sm"
                                  placeholder="You are an expert cryptocurrency trader..."></textarea>
                        <p class="text-xs text-gray-400 mt-1">Base instructions for the AI model</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-2">AI Prompt Templates (JSON)</label>
                        <textarea wire:model="ai_prompt_templates" rows="8"
                                  class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 font-mono text-xs"
                                  placeholder='{"trend": "...", "mean_reversion": "...", "breakout": "..."}'></textarea>
                        <p class="text-xs text-gray-400 mt-1">Strategy-specific prompt templates in JSON format</p>
                    </div>
                </div>
            </div>

            <!-- Cache & UI Configuration -->
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-2">
                    <span class="text-2xl">⚙️</span> Cache & UI Configuration
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Price Cache TTL (seconds)</label>
                        <input type="number" wire:model="cache_ttl_prices"
                               class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:ring-2 focus:ring-blue-500"
                               min="1" max="60">
                        <p class="text-xs text-gray-400 mt-1">How long to cache price data</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Chart Cache TTL (seconds)</label>
                        <input type="number" wire:model="cache_ttl_charts"
                               class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:ring-2 focus:ring-blue-500"
                               min="60" max="3600">
                        <p class="text-xs text-gray-400 mt-1">How long to cache chart data</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">UI Refresh Interval (seconds)</label>
                        <input type="number" wire:model="ui_refresh_interval"
                               class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:ring-2 focus:ring-blue-500"
                               min="1" max="60">
                        <p class="text-xs text-gray-400 mt-1">Auto-refresh dashboard interval</p>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end gap-4">
                <button type="button" wire:click="loadSettings"
                        class="bg-gray-700 hover:bg-gray-600 text-white font-semibold px-6 py-3 rounded-lg transition-colors">
                    Reset Changes
                </button>
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-8 py-3 rounded-lg transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Save All Settings
                </button>
            </div>
        </form>
    </div>
</div>
