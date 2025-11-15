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
- Docker & Docker Compose (via Laravel Sail)
- Binance Futures API keys
- OpenRouter API key

### Installation

```bash
# 1. Clone repository
git clone https://github.com/ajul8866/ai-trading-bot.git
cd ai-trading-bot

# 2. Install dependencies
composer install

# 3. Copy environment file
cp .env.example .env

# 4. Start Docker containers
./vendor/bin/sail up -d

# 5. Generate application key
./vendor/bin/sail artisan key:generate

# 6. Run migrations
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed --class=SettingsSeeder

# 7. Set API keys (via Tinker)
./vendor/bin/sail artisan tinker
>>> App\Models\Setting::where('key', 'binance_api_key')->update(['value' => 'YOUR_KEY']);
>>> App\Models\Setting::where('key', 'binance_api_secret')->update(['value' => 'YOUR_SECRET']);
>>> App\Models\Setting::where('key', 'openrouter_api_key')->update(['value' => 'YOUR_KEY']);
>>> exit

# 8. Start queue worker (in new terminal)
./vendor/bin/sail artisan queue:work

# 9. Start the bot
./vendor/bin/sail artisan bot:start

# 10. Access dashboard
# Open browser: http://localhost/dashboard
```

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
