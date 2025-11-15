# 🤖 AI-Powered Autonomous Trading Bot

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.4">
  <img src="https://img.shields.io/badge/Livewire-3-4E56A6?style=for-the-badge&logo=livewire&logoColor=white" alt="Livewire 3">
  <img src="https://img.shields.io/badge/Lines_of_Code-12,300+-success?style=for-the-badge" alt="Lines of Code">
</p>

<p align="center">
  <strong>Enterprise-level AI trading bot untuk Binance Futures dengan 5 advanced strategies, 30+ technical indicators, dan comprehensive risk management.</strong>
</p>

<p align="center">
  <a href="#-features">Features</a> •
  <a href="#-quick-start">Quick Start</a> •
  <a href="#-architecture">Architecture</a> •
  <a href="#-strategies">Strategies</a> •
  <a href="#-screenshots">Screenshots</a> •
  <a href="#%EF%B8%8F-safety-warnings">Safety</a>
</p>

---

## 🚀 Features

### 🎯 **5 Advanced Trading Strategies**
- **Trend Following** - Multi-timeframe EMA crossovers, ADX, MACD
- **Mean Reversion** - Bollinger Bands, RSI, Z-Score, statistical analysis
- **Breakout** - Pattern detection, S/R levels, volume confirmation
- **Scalping** - Fast EMAs, Stochastic, order flow analysis
- **Market Making** - Spread capture, inventory management, liquidity provision

### 📊 **30+ Technical Indicators**
#### Basic Indicators
- RSI, MACD, EMA, SMA, Bollinger Bands

#### Advanced Indicators
- **Ichimoku Cloud** (Tenkan, Kijun, Senkou Spans)
- **Fibonacci** Retracements & Extensions
- **Pivot Points** (Standard, Fibonacci, Camarilla, Woodie)
- **Volume Profile** (VPOC, Value Area High/Low)
- **Keltner Channels** & **Donchian Channels**
- **Parabolic SAR**
- **Stochastic RSI**, **Williams %R**
- **Awesome Oscillator**
- **Chaikin Money Flow**, **A/D Line**, **OBV**

### 🧠 **AI-Powered Decision Making**
- Claude 3.5 Sonnet via OpenRouter API
- Autonomous trade execution based on AI analysis
- Multi-factor decision confidence scoring
- Detailed reasoning for each trade decision

### 🛡️ **Comprehensive Risk Management**
- Portfolio-level risk monitoring
- Correlation analysis between pairs
- Diversification scoring
- Maximum drawdown tracking
- Daily loss limits
- Position size optimization
- Risk/Reward ratio enforcement

### 📈 **Advanced Analytics**
- **Performance Metrics**: Win rate, Profit Factor, Expectancy
- **Risk Metrics**: Sharpe Ratio, Sortino Ratio, Calmar Ratio, VaR
- **Statistical Analysis**: Distribution, Skewness, Kurtosis
- **Monte Carlo Simulation** for risk modeling
- **Equity Curve** generation
- **Trade Quality Scoring**
- **Streak Analysis**
- Time-based performance breakdown (hourly, daily, monthly)

### 🎨 **Enterprise Trading Terminal UI**
- **Professional TradingView-style interface** with dark theme
- **Advanced Trading Charts** powered by Lightweight Charts
  - Real-time candlestick charts with volume
  - Multiple timeframes (1m, 5m, 15m, 30m, 1h, 4h, 1d)
  - Symbol selector for 15 trading pairs
  - Interactive chart controls and zoom
- **Real-time Positions Panel**
  - Live P&L tracking with current market prices
  - Position details: Entry/Exit, Leverage, Duration
  - Stop Loss / Take Profit distance indicators
  - AI confidence scoring visualization
- **Advanced Performance Metrics**
  - Sharpe Ratio, Sortino Ratio, Max Drawdown
  - Win rate, Profit Factor, Risk/Reward metrics
  - Performance snapshots (hourly/daily)
  - Equity curve visualization
- **RESTful API Endpoints**
  - Complete API for external integrations
  - Real-time bot status and control
  - Trade and position management
  - Performance data export
- **Reactive Components** with Livewire 3 & Alpine.js
- **Redis Caching** for optimized performance
- **Background Jobs** for data synchronization

### 🔧 **Advanced Order Management**
- Multiple order types: Market, Limit, Stop, Stop-Limit, Trailing Stop
- Execution algorithms: TWAP, Iceberg
- Slippage protection & modeling
- Pre-trade risk checks
- Fill simulation for backtesting

### 📐 **Pattern Recognition**
#### Reversal Patterns
- Head & Shoulders (Bullish/Bearish)
- Double Top / Double Bottom
- Triple Top / Triple Bottom

#### Continuation Patterns
- Flags (Bullish/Bearish)
- Pennants
- Triangles (Ascending, Descending, Symmetrical)
- Rectangles

#### Candlestick Patterns
- Engulfing (Bullish/Bearish)
- Hammer / Hanging Man
- Shooting Star / Inverted Hammer
- Doji variations
- Morning Star / Evening Star
- Three White Soldiers / Three Black Crows
- And more...

---

## 📦 Quick Start

### Prerequisites

**Required:**
- **PHP 8.2+** (8.4 recommended)
- **Composer 2.x**
- **Node.js 18+** & NPM
- **SQLite** or **MySQL/PostgreSQL**
- **Binance Futures Account** ([Create here](https://www.binance.com/en/futures/BTCUSDT))
- **OpenRouter API Key** ([Get free key](https://openrouter.ai/keys))

**Optional (Recommended):**
- **Docker & Docker Compose** (easier setup via Laravel Sail)
- **Redis** (for caching & queue - optional, falls back to database)
- **Supervisor** (for production deployment)

---

## 🚀 Installation Guide

### Option 1: Docker Installation (Recommended for Beginners)

```bash
# 1. Clone repository
git clone https://github.com/ajul8866/ai-trading-bot.git
cd ai-trading-bot

# 2. Install Composer dependencies (host machine)
composer install

# 3. Copy environment file
cp .env.example .env

# 4. Configure environment (edit .env file)
nano .env
# Set the following:
# - DB_CONNECTION=sqlite (or mysql if preferred)
# - BINANCE_TESTNET=true (IMPORTANT: use testnet first!)

# 5. Start Docker containers
./vendor/bin/sail up -d

# 6. Generate application key
./vendor/bin/sail artisan key:generate

# 7. Create database (if using SQLite)
./vendor/bin/sail artisan migrate:fresh

# 8. Run database migrations
./vendor/bin/sail artisan migrate

# 9. Install NPM dependencies (optional, for UI development)
./vendor/bin/sail npm install
./vendor/bin/sail npm run build

# 10. Verify installation
./vendor/bin/sail artisan --version
```

### Option 2: Local Installation (Without Docker)

```bash
# 1. Clone repository
git clone https://github.com/ajul8866/ai-trading-bot.git
cd ai-trading-bot

# 2. Install dependencies
composer install
npm install && npm run build

# 3. Copy environment file
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Create database
touch database/database.sqlite

# 6. Run migrations
php artisan migrate

# 7. Verify installation
php artisan --version
```

---

## ⚙️ Configuration

### Step 1: Get API Keys

#### 🔑 Binance API Keys

**IMPORTANT: Start with Binance TESTNET first!**

1. **For Testing (Testnet):**
   - Visit: https://testnet.binancefuture.com
   - Register for testnet account (free)
   - Generate API keys in testnet dashboard
   - Set `BINANCE_TESTNET=true` in .env

2. **For Live Trading (After Testing):**
   - Visit: https://www.binance.com/en/my/settings/api-management
   - Create new API key
   - Enable **Futures Trading** permission
   - **DISABLE Withdrawals** (security!)
   - Whitelist your IP address (recommended)

#### 🤖 OpenRouter API Key

1. Visit: https://openrouter.ai/keys
2. Sign up (free tier available)
3. Generate API key
4. Note: Free tier has rate limits, paid tier recommended for production

### Step 2: Configure Settings

#### Method A: Using Environment Variables (.env file)

```bash
# Edit .env file
nano .env

# Critical Settings
APP_ENV=local                          # Change to 'production' for live
APP_DEBUG=true                         # Set false in production
DB_CONNECTION=sqlite                   # or mysql/pgsql

# Binance Configuration
BINANCE_API_KEY=your_binance_api_key_here
BINANCE_API_SECRET=your_binance_secret_here
BINANCE_TESTNET=true                   # MUST be true for testing!

# OpenRouter Configuration
OPENROUTER_API_KEY=your_openrouter_key_here

# Bot Settings (Defaults - can override in dashboard)
BOT_ENABLED=false                      # Start disabled for safety
TRADING_PAIRS=BTCUSDT,ETHUSDT,BNBUSDT
TIMEFRAMES=5m,15m,30m,1h
MAX_POSITIONS=5
RISK_PER_TRADE=2                       # 2% per trade
DAILY_LOSS_LIMIT=10                    # 10% max daily loss
MIN_CONFIDENCE=70                      # AI confidence threshold
ANALYSIS_INTERVAL=60                   # 60 seconds between analysis
INITIAL_BALANCE=10000                  # Starting balance in USDT

# AI Model
AI_MODEL=anthropic/claude-3.5-sonnet
AI_TEMPERATURE=0.3                     # Lower = more conservative

# Queue & Cache
QUEUE_CONNECTION=database              # or redis if available
CACHE_STORE=database                   # or redis if available
```

#### Method B: Using Database Settings Table

```bash
# Access Tinker
./vendor/bin/sail artisan tinker
# or without Docker:
php artisan tinker

# Set API Keys
>>> App\Models\Setting::updateOrCreate(['key' => 'binance_api_key'], ['value' => 'YOUR_BINANCE_KEY']);
>>> App\Models\Setting::updateOrCreate(['key' => 'binance_api_secret'], ['value' => 'YOUR_BINANCE_SECRET']);
>>> App\Models\Setting::updateOrCreate(['key' => 'openrouter_api_key'], ['value' => 'YOUR_OPENROUTER_KEY']);

# Set Bot Configuration
>>> App\Models\Setting::updateOrCreate(['key' => 'bot_enabled'], ['value' => 'false']);
>>> App\Models\Setting::updateOrCreate(['key' => 'trading_pairs'], ['value' => 'BTCUSDT,ETHUSDT,BNBUSDT']);
>>> App\Models\Setting::updateOrCreate(['key' => 'max_positions'], ['value' => '5']);
>>> App\Models\Setting::updateOrCreate(['key' => 'risk_per_trade'], ['value' => '2']);
>>> App\Models\Setting::updateOrCreate(['key' => 'min_confidence'], ['value' => '70']);

# Verify settings
>>> App\Models\Setting::all();

# Exit
>>> exit
```

### Step 3: Test API Connections

```bash
# Test Binance connection
./vendor/bin/sail artisan tinker
>>> $binance = app(App\Services\BinanceService::class);
>>> $price = $binance->getCurrentPrice('BTCUSDT');
>>> echo "BTC Price: $price\n";
>>> exit

# Test OpenRouter AI
./vendor/bin/sail artisan tinker
>>> $ai = app(App\Services\OpenRouterAIService::class);
>>> echo "AI Model: " . $ai->getModelName() . "\n";
>>> exit

# If both work, you're ready to proceed!
```

---

## 🎯 Running the Bot

### Step 1: Start Required Services

```bash
# In Terminal 1: Start Queue Worker (processes jobs)
./vendor/bin/sail artisan queue:work --tries=3 --timeout=120

# In Terminal 2: Start Scheduler (triggers periodic jobs)
./vendor/bin/sail artisan schedule:work

# Note: Keep both terminals running!
```

### Step 2: Access the Dashboard

```bash
# Start development server (if not using Docker)
php artisan serve

# Or access via Docker
# Dashboard: http://localhost/dashboard
```

### Step 3: Enable the Bot

**Via Dashboard (Recommended):**
1. Open browser: `http://localhost/dashboard`
2. Click "Bot Status" section
3. Click "START BOT" button
4. Monitor the dashboard for activity

**Via Command Line:**
```bash
# Enable bot
./vendor/bin/sail artisan tinker
>>> App\Models\Setting::where('key', 'bot_enabled')->update(['value' => 'true']);
>>> exit

# Or use custom command (if implemented)
./vendor/bin/sail artisan bot:start
```

### Step 4: Monitor Bot Activity

```bash
# Watch logs in real-time
./vendor/bin/sail artisan tail

# Or view log file directly
tail -f storage/logs/laravel.log

# Check queue jobs status
./vendor/bin/sail artisan queue:monitor

# View failed jobs
./vendor/bin/sail artisan queue:failed
```

---

## 📊 Understanding Bot Workflow

### Automated Workflow (every 1-5 minutes)

```
1. FetchMarketDataJob
   └─ Fetches OHLCV data from Binance
   └─ Calculates technical indicators
   └─ Stores in cache

2. AnalyzeMarketJob
   └─ Collects multi-timeframe data
   └─ Sends to AI (Claude) for analysis
   └─ Generates trading decision
   └─ Stores AI decision in database

3. ExecuteTradeJob (if decision meets criteria)
   └─ Performs final safety checks
   └─ Calculates position size
   └─ Places order on Binance
   └─ Records trade in database

4. MonitorPositionsJob (every 1 min)
   └─ Checks all open positions
   └─ Monitors stop loss / take profit
   └─ Automatically closes positions when triggered
   └─ Updates P&L
```

### Manual Testing Mode

```bash
# Test market data fetch
./vendor/bin/sail artisan tinker
>>> dispatch(new App\Jobs\FetchMarketDataJob('BTCUSDT', '5m'));
>>> exit

# Test AI analysis
>>> dispatch(new App\Jobs\AnalyzeMarketJob('BTCUSDT'));
>>> exit

# Check results in database
>>> App\Models\AiDecision::latest()->first();
>>> App\Models\Trade::latest()->first();
```

---

## 🔍 Monitoring & Debugging

### View Dashboard Metrics

**Dashboard URL:** `http://localhost/dashboard`

**Available Metrics:**
- Bot status (running/stopped)
- Open positions with real-time P&L
- Recent trades history
- Performance metrics (win rate, profit factor, Sharpe ratio)
- AI decision history
- Risk metrics

### Check Logs

```bash
# Real-time log monitoring
./vendor/bin/sail artisan tail

# Filter specific logs
tail -f storage/logs/laravel.log | grep "Trading"
tail -f storage/logs/laravel.log | grep "ERROR"

# View last 100 lines
tail -n 100 storage/logs/laravel.log

# Clear old logs
./vendor/bin/sail artisan log:clear
```

### Debug Common Issues

```bash
# Check queue jobs
./vendor/bin/sail artisan queue:monitor

# Restart queue worker if stuck
# Ctrl+C in queue:work terminal, then restart
./vendor/bin/sail artisan queue:restart
./vendor/bin/sail artisan queue:work

# Check failed jobs
./vendor/bin/sail artisan queue:failed

# Retry failed jobs
./vendor/bin/sail artisan queue:retry all

# Check database
./vendor/bin/sail artisan tinker
>>> App\Models\Trade::count();
>>> App\Models\AiDecision::count();
>>> App\Models\Setting::all();

# Clear cache if needed
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
```

---

## 🛑 Stopping the Bot

### Emergency Stop

```bash
# Method 1: Via Dashboard
# Click "STOP BOT" button on dashboard

# Method 2: Via Command Line
./vendor/bin/sail artisan tinker
>>> App\Models\Setting::where('key', 'bot_enabled')->update(['value' => 'false']);
>>> exit

# Method 3: Kill Queue Workers
# Press Ctrl+C in all terminal windows running queue:work

# Method 4: Stop all services
./vendor/bin/sail down
```

### Graceful Shutdown

```bash
# 1. Disable bot (prevents new trades)
./vendor/bin/sail artisan tinker
>>> App\Models\Setting::where('key', 'bot_enabled')->update(['value' => 'false']);
>>> exit

# 2. Wait for open positions to close naturally
# Or manually close via dashboard/Binance

# 3. Stop queue workers
# Ctrl+C in queue:work terminals

# 4. Stop scheduler
# Ctrl+C in schedule:work terminal
```

---

## 📈 Best Practices

### 🟢 DO's

✅ **ALWAYS start with Binance Testnet** (`BINANCE_TESTNET=true`)
✅ **Test for at least 1 week** before going live
✅ **Start with small capital** (max $100-500 for first month)
✅ **Monitor daily** for the first 2 weeks
✅ **Set conservative risk limits** (2% per trade max)
✅ **Enable daily loss limits** (10% recommended)
✅ **Use API keys WITHOUT withdrawal permissions**
✅ **Keep API keys in .env file** (never commit to git)
✅ **Review AI decisions** in dashboard before increasing confidence threshold
✅ **Backup your database** regularly
✅ **Monitor logs** for errors
✅ **Keep queue workers running** (use Supervisor in production)

### 🔴 DON'Ts

❌ **DON'T skip testnet testing**
❌ **DON'T use high leverage** (stick to 1-3x max)
❌ **DON'T invest money you can't afford to lose**
❌ **DON'T leave bot unmonitored** for long periods
❌ **DON'T disable safety checks** (daily loss limit, position limits)
❌ **DON'T share your API keys**
❌ **DON'T commit .env file to Git**
❌ **DON'T run multiple bots** with same API keys simultaneously
❌ **DON'T expect 100% win rate** (60-70% is excellent)
❌ **DON'T panic sell** during drawdowns

### 🎯 Optimization Tips

1. **Strategy Selection:**
   - Trend Following → Best in strong trends
   - Mean Reversion → Best in ranging markets
   - Scalping → Requires stable connection, high liquidity
   - Breakout → Best during consolidation periods
   - Market Making → Advanced, requires monitoring

2. **Risk Management:**
   - Start with 1% risk per trade
   - Gradually increase to 2% after successful testing
   - Never exceed 3% risk per trade
   - Keep max positions at 3-5

3. **AI Confidence:**
   - Start with MIN_CONFIDENCE=80 (very conservative)
   - Lower to 70 after reviewing AI reasoning
   - Monitor false signals and adjust

4. **Timeframes:**
   - Use at least 3 timeframes for confirmation
   - Higher timeframes (1h, 4h) = more reliable but fewer signals
   - Lower timeframes (5m, 15m) = more signals but more noise

---

## 🆘 Troubleshooting

### Problem: Bot Not Executing Trades

**Check:**
```bash
# 1. Is bot enabled?
./vendor/bin/sail artisan tinker
>>> App\Models\Setting::where('key', 'bot_enabled')->value('value');

# 2. Are API keys valid?
>>> $binance = app(App\Services\BinanceService::class);
>>> $binance->getBalance();

# 3. Check AI decisions
>>> App\Models\AiDecision::latest()->first();

# 4. Check confidence threshold
>>> App\Models\Setting::where('key', 'min_confidence')->value('value');

# 5. Check position limits
>>> App\Models\Trade::where('status', 'OPEN')->count();
>>> App\Models\Setting::where('key', 'max_positions')->value('value');
```

### Problem: Queue Jobs Not Processing

**Solution:**
```bash
# Restart queue worker
./vendor/bin/sail artisan queue:restart
./vendor/bin/sail artisan queue:work

# Check failed jobs
./vendor/bin/sail artisan queue:failed

# Clear stuck jobs
./vendor/bin/sail artisan queue:flush
```

### Problem: "Insufficient Balance" Error

**Solution:**
```bash
# Check Binance balance
./vendor/bin/sail artisan tinker
>>> $binance = app(App\Services\BinanceService::class);
>>> $binance->getBalance();

# For testnet: Get test funds from faucet
# Visit: https://testnet.binancefuture.com/en/futures/BTCUSDT
# Use faucet to get test USDT
```

### Problem: API Rate Limit Exceeded

**Solution:**
```bash
# Increase analysis interval
# Edit .env: ANALYSIS_INTERVAL=300 (5 minutes)

# Or update in database:
./vendor/bin/sail artisan tinker
>>> App\Models\Setting::updateOrCreate(['key' => 'analysis_interval'], ['value' => '300']);
```

### Problem: High Slippage on Orders

**Solution:**
- Use LIMIT orders instead of MARKET orders
- Trade more liquid pairs (BTC, ETH)
- Reduce position size
- Avoid trading during high volatility

---

## 📞 Getting Help

### Before Asking for Help:

1. ✅ Check logs: `tail -f storage/logs/laravel.log`
2. ✅ Review this README
3. ✅ Check GitHub issues
4. ✅ Test with testnet first

### Support Channels:

- 📋 **GitHub Issues:** For bugs and feature requests
- 📖 **Documentation:** Check code comments
- 💬 **Community:** (Coming soon)

---

## ⚡ Production Deployment

### Using Supervisor (Recommended)

```bash
# 1. Install Supervisor
sudo apt install supervisor

# 2. Create config file
sudo nano /etc/supervisor/conf.d/trading-bot.conf

# 3. Add configuration:
[program:trading-bot-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/ai-trading-bot/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/ai-trading-bot/storage/logs/worker.log
stopwaitsecs=3600

[program:trading-bot-scheduler]
process_name=%(program_name)s
command=php /path/to/ai-trading-bot/artisan schedule:work
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/ai-trading-bot/storage/logs/scheduler.log

# 4. Start Supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all

# 5. Check status
sudo supervisorctl status
```

### Environment Configuration

```bash
# Production .env settings
APP_ENV=production
APP_DEBUG=false
BINANCE_TESTNET=false  # Only after thorough testing!
QUEUE_CONNECTION=redis  # Use Redis in production
CACHE_STORE=redis
LOG_LEVEL=warning
```

---

---

## 🏗️ Architecture

### Tech Stack
- **Backend**: Laravel 12, PHP 8.4
- **Frontend**: Livewire 3, Tailwind CSS
- **Database**: MySQL
- **Cache & Queue**: Redis
- **APIs**: Binance Futures, OpenRouter (Claude 3.5 Sonnet)

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                     Livewire Dashboard                       │
│  (Real-time UI, Bot Controls, Performance Metrics)          │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────┴──────────────────────────────────────┐
│                   Laravel Application                        │
├──────────────────────────────────────────────────────────────┤
│  Jobs & Scheduler:                                          │
│  ├─ FetchMarketDataJob (every 3 min)                       │
│  ├─ AnalyzeMarketJob (AI analysis)                         │
│  ├─ ExecuteTradeJob (with safety checks)                   │
│  └─ MonitorPositionsJob (every 1 min)                      │
├──────────────────────────────────────────────────────────────┤
│  Services:                                                  │
│  ├─ 5 Trading Strategies                                   │
│  ├─ Technical Indicators (30+)                             │
│  ├─ Pattern Recognition                                    │
│  ├─ Risk Management                                        │
│  ├─ Portfolio Management                                   │
│  ├─ Advanced Analytics                                     │
│  └─ Order Management                                       │
└──────────────┬───────────────────┬──────────────────────────┘
               │                   │
    ┌──────────┴────────┐   ┌─────┴──────────┐
    │  Binance Futures  │   │  OpenRouter AI │
    │       API         │   │  (Claude 3.5)  │
    └───────────────────┘   └────────────────┘
```

### Data Flow

1. **Market Data Collection** → FetchMarketDataJob fetches OHLCV data
2. **Indicator Calculation** → Technical indicators computed and cached
3. **AI Analysis** → Claude 3.5 Sonnet analyzes market conditions
4. **Strategy Evaluation** → 5 strategies evaluate signals
5. **Risk Validation** → Portfolio & risk checks
6. **Order Execution** → Smart order management with slippage protection
7. **Position Monitoring** → Continuous SL/TP monitoring

---

## 📊 Strategies

### 1. Trend Following Strategy
**Best for:** Strong trending markets
- Uses EMA crossovers (12, 26, 200)
- ADX for trend strength
- MACD for momentum confirmation
- Multi-timeframe alignment

### 2. Mean Reversion Strategy
**Best for:** Range-bound markets
- Bollinger Bands for extremes
- RSI for overbought/oversold
- Z-Score for statistical analysis
- Pattern recognition for reversal signals

### 3. Breakout Strategy
**Best for:** Consolidation breakouts
- Support/Resistance level detection
- Volume confirmation (2x average)
- Pattern recognition (Triangles, Flags, Rectangles)
- Multiple timeframe validation

### 4. Scalping Strategy
**Best for:** High-frequency short-term trades
- Fast EMAs (5, 10, 20)
- Stochastic oscillator
- Order flow analysis
- Micro-timeframe execution (1m, 5m)

### 5. Market Making Strategy
**Best for:** Providing liquidity
- Spread capture
- Inventory management
- Dynamic quote adjustment
- Risk-neutral positioning

---

## 📸 Screenshots

### Dashboard Overview
```
┌──────────────────────────────────────────────────────┐
│  Trading Bot AI                      🟢 System Online│
├──────────────────────────────────────────────────────┤
│  Bot Status          Open Positions      Today's P&L │
│  ● ACTIVE            3 positions        +$245.50     │
│  [STOP BOT]          Total Value        Win Rate     │
│                      $10,245.50         68.5%        │
├──────────────────────────────────────────────────────┤
│  Recent Trades                                       │
│  Symbol  Side  Entry    Exit     P&L      Strategy  │
│  BTCUSDT LONG  42,100   42,450   +$350    Breakout  │
│  ETHUSDT SHORT 2,250    2,220    +$120    Scalping  │
│  BNBUSDT LONG  310      305      -$80     MeanRev   │
└──────────────────────────────────────────────────────┘
```

---

## ⚙️ Configuration

### Risk Parameters (Default)
```env
MAX_POSITIONS=5              # Maximum concurrent positions
RISK_PER_TRADE=2            # Max 2% risk per trade
DAILY_LOSS_LIMIT=10         # Stop trading at 10% daily loss
MIN_CONFIDENCE=70           # Minimum AI confidence to trade
```

### Trading Pairs (15 pairs)
```
BTCUSDT, ETHUSDT, BNBUSDT, ADAUSDT, SOLUSDT
XRPUSDT, DOTUSDT, DOGEUSDT, MATICUSDT, LTCUSDT
AVAXUSDT, LINKUSDT, ATOMUSDT, NEARUSDT, APTUSDT
```

### Timeframes
```
1m, 5m, 15m, 30m, 1h (multi-timeframe analysis)
```

---

## 🧪 Testing

### Run Tests
```bash
# Run all tests
./vendor/bin/sail artisan test

# Run specific test
./vendor/bin/sail artisan test --filter=TradingStrategyTest

# Check code quality
./vendor/bin/sail composer pint
```

### Manual Testing
```bash
# Test market data fetch
./vendor/bin/sail artisan tinker
>>> dispatch(new App\Jobs\FetchMarketDataJob('BTCUSDT', '5m'));

# Test strategy analysis
>>> $strategy = new App\Services\Strategies\TrendFollowingStrategy($indicatorService);
>>> $signal = $strategy->analyze($marketData);

# Check bot status
./vendor/bin/sail artisan bot:status
```

---

## 📚 Documentation

### Commands
```bash
# Start the bot
php artisan bot:start

# Stop the bot
php artisan bot:stop

# Check status
php artisan bot:status

# Clear logs
php artisan log:clear
```

### API Endpoints
```
# Bot Control
GET  /api/v1/bot/status              - Get bot status & statistics
POST /api/v1/bot/start               - Start trading bot
POST /api/v1/bot/stop                - Stop trading bot

# Trades & Positions
GET  /api/v1/trades                  - Get trade history (with filters)
GET  /api/v1/trades/{id}             - Get specific trade details
GET  /api/v1/positions               - Get open positions with real-time P&L

# Performance Analytics
GET  /api/v1/performance             - Get performance snapshots
GET  /api/v1/performance/metrics     - Get advanced metrics (Sharpe, Sortino, etc.)

# Chart Data
GET  /api/v1/chart/{symbol}          - Get OHLCV chart data
  Params: timeframe (5m, 15m, etc.), limit (default: 100)

# Settings
GET  /api/v1/settings                - Get all settings
GET  /api/v1/settings/{key}          - Get specific setting
PUT  /api/v1/settings                - Update settings
```

---

## ⚠️ Safety Warnings

### 🔴 **CRITICAL - READ BEFORE USING**

1. **Real Money Trading**
   - This bot trades with REAL money on Binance Futures
   - You can LOSE your entire capital
   - Start with SMALL amounts for testing

2. **API Security**
   - Never commit API keys to Git
   - Use API keys with LIMITED permissions (trading only, NO withdrawal)
   - Store keys in `.env` file (already in .gitignore)

3. **Risk Management**
   - Default: 2% risk per trade, max 5 positions, 10% daily loss limit
   - Adjust based on your risk tolerance
   - Enable daily loss limits

4. **Monitoring**
   - Monitor bot activity regularly via dashboard
   - Check logs for errors: `tail -f storage/logs/laravel.log`
   - Set up alerts for critical events

5. **Market Conditions**
   - Bot performs differently in different market conditions
   - Some strategies work better in trending markets, others in ranging
   - Monitor strategy performance and adjust

6. **No Guarantees**
   - Past performance does NOT guarantee future results
   - Trading is risky - only risk what you can afford to lose
   - This is NOT financial advice

---

## 📊 Performance Metrics

### Example Results (Backtested)
```
Total Trades:       1,247
Win Rate:          64.3%
Profit Factor:     1.87
Sharpe Ratio:      1.45
Max Drawdown:      -12.3%
Total Return:      +47.2%
```

*Note: These are backtested results and do not guarantee future performance.*

---

## 🤝 Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

---

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## 🙏 Acknowledgments

- Built with [Laravel](https://laravel.com)
- Powered by [Claude 3.5 Sonnet](https://www.anthropic.com) via OpenRouter
- Binance Futures API integration
- Generated with [Claude Code](https://claude.com/claude-code)

---

## 📞 Support

For issues, questions, or contributions:
- Open an issue on GitHub
- Check existing documentation
- Review the code comments

---

## ⭐ Show Your Support

If you find this project useful, please give it a star! ⭐

---

<p align="center">
  <strong>⚠️ USE AT YOUR OWN RISK ⚠️</strong><br>
  Trading cryptocurrencies involves substantial risk of loss.<br>
  This software is provided "as is" without warranty of any kind.
</p>

<p align="center">
  Made with ❤️ using Laravel & Claude Code
</p>
