<div align="center">

# 🤖 AI Trading Bot

### **Autonomous AI-Powered Cryptocurrency Trading Bot for Binance Futures**

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Livewire](https://img.shields.io/badge/Livewire-3-4E56A6?style=for-the-badge&logo=livewire&logoColor=white)](https://livewire.laravel.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://docker.com)

**Powered by Claude 3.5 Sonnet AI** | **Real-time Trading** | **Advanced Risk Management**

[Features](#-key-features) • [Quick Start](#-quick-start) • [Screenshots](#-dashboard-screenshots) • [API](#-api-documentation) • [Support](#-support)

---

</div>

## 📋 Table of Contents

- [✨ Key Features](#-key-features)
- [🏗️ Tech Stack](#️-tech-stack)
- [🚀 Quick Start](#-quick-start)
- [⚙️ Configuration](#️-configuration)
- [🎯 Starting the Bot](#-starting-the-bot)
- [📊 Dashboard Screenshots](#-dashboard-screenshots)
- [🔌 API Documentation](#-api-documentation)
- [📈 Monitoring & Logs](#-monitoring--logs)
- [🛠️ Troubleshooting](#️-troubleshooting)
- [🔒 Security](#-security)
- [⚠️ Disclaimer](#️-disclaimer)

---

## ✨ Key Features

<table>
<tr>
<td width="50%">

### 🎯 **AI-Powered Trading**
- Claude 3.5 Sonnet for market analysis
- Intelligent decision-making engine
- Configurable confidence thresholds
- Custom AI prompts

### 📊 **Advanced Analytics**
- Real-time candlestick charts
- Technical indicator analysis
- Performance metrics tracking
- Win rate & P&L monitoring

</td>
<td width="50%">

### 🛡️ **Risk Management**
- Maximum position limits
- Risk per trade controls
- Daily loss limits
- Stop-loss & Take-profit automation

### 🔄 **Real-time Operations**
- Live Binance Futures integration
- Auto position synchronization
- Queue-based job processing
- WebSocket market data

</td>
</tr>
</table>

### 🎨 **Modern Web Interface**
- Beautiful Livewire 3 dashboard
- Real-time updates without page refresh
- Interactive TradingView-style charts
- Mobile-responsive design
- Dark/Light theme support

### 🔐 **Enterprise Security**
- API key authentication
- Encrypted credential storage
- Rate limiting protection
- Secure session management

---

## 🏗️ Tech Stack

```
┌─────────────────────────────────────────────────────────────┐
│                     AI TRADING BOT                          │
├─────────────────────────────────────────────────────────────┤
│  Frontend  │  Livewire 3  │  Alpine.js  │  TailwindCSS    │
│  Charts    │  Lightweight Charts (TradingView)             │
├─────────────────────────────────────────────────────────────┤
│  Backend   │  Laravel 12  │  PHP 8.2+   │  Queue Workers  │
│  AI        │  OpenRouter  │  Claude 3.5 Sonnet            │
├─────────────────────────────────────────────────────────────┤
│  Database  │  MySQL 8.0   │  Redis (optional)             │
│  Deploy    │  Docker Sail │  Supervisor │  Nginx          │
├─────────────────────────────────────────────────────────────┤
│  APIs      │  Binance Futures REST & WebSocket             │
└─────────────────────────────────────────────────────────────┘
```

---

## 🚀 Quick Start

### Prerequisites

Before you begin, ensure you have the following installed:

| Requirement | Version | Purpose |
|------------|---------|---------|
| 🐳 **Docker** | Latest | Container runtime |
| 🐙 **Docker Compose** | Latest | Multi-container orchestration |
| 📦 **Git** | Latest | Version control |
| 💰 **Binance Account** | - | Futures trading API |
| 🤖 **OpenRouter API** | - | AI analysis (Claude) |

---

### 📦 Installation

#### **Step 1: Clone Repository**

```bash
git clone https://github.com/ajul8866/ai-trading-bot.git
cd ai-trading-bot
```

#### **Step 2: Install Dependencies**

```bash
# Install PHP dependencies (requires PHP 8.2+ and Composer)
composer install

# Copy environment configuration
cp .env.example .env
```

#### **Step 3: Configure Environment**

Edit your `.env` file:

```bash
nano .env
```

<details>
<summary><b>📝 View Required Configuration</b></summary>

```env
# ═══════════════════════════════════════
#  APPLICATION SETTINGS
# ═══════════════════════════════════════
APP_NAME=AItrading
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-server-ip

# ═══════════════════════════════════════
#  DATABASE CONFIGURATION
# ═══════════════════════════════════════
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=trading_bot
DB_USERNAME=trading_user
DB_PASSWORD=YourSecurePassword123!

# ═══════════════════════════════════════
#  SESSION & CACHE
# ═══════════════════════════════════════
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=false
QUEUE_CONNECTION=database
CACHE_STORE=database

# ═══════════════════════════════════════
#  API SECURITY (CRITICAL!)
# ═══════════════════════════════════════
API_ACCESS_KEY=your-64-character-secure-key-here

# ═══════════════════════════════════════
#  BINANCE API CREDENTIALS
# ═══════════════════════════════════════
BINANCE_API_KEY=your_binance_api_key
BINANCE_API_SECRET=your_binance_api_secret
BINANCE_TESTNET=false

# ═══════════════════════════════════════
#  AI CONFIGURATION
# ═══════════════════════════════════════
OPENROUTER_API_KEY=your_openrouter_api_key
```

</details>

**🔑 Generate Secure API Key:**

```bash
openssl rand -hex 32
```

#### **Step 4: Start Docker Environment**

```bash
./vendor/bin/sail up -d
```

#### **Step 5: Database Setup**

```bash
# Generate application key
./vendor/bin/sail artisan key:generate

# Run database migrations
./vendor/bin/sail artisan migrate

# Seed default settings
./vendor/bin/sail artisan db:seed --class=SettingsSeeder
```

#### **Step 6: Build Frontend Assets**

```bash
# Install Node.js dependencies
./vendor/bin/sail npm install

# Build production assets
./vendor/bin/sail npm run build
```

#### **Step 7: Clear All Caches**

```bash
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan view:clear
./vendor/bin/sail artisan route:clear
```

✅ **Installation Complete!**

---

## ⚙️ Configuration

### Management Script Commands

The included `start.sh` script simplifies bot management:

```bash
# Make script executable (first time only)
chmod +x start.sh

┌─────────────────────────────────────────┐
│  📋 AVAILABLE COMMANDS                  │
├─────────────────────────────────────────┤
│  ./start.sh start    │ Start all services
│  ./start.sh stop     │ Stop all services
│  ./start.sh restart  │ Restart services
│  ./start.sh status   │ Check status
│  ./start.sh logs     │ View live logs
└─────────────────────────────────────────┘
```

### Manual Start (Alternative)

```bash
# Start Docker containers
./vendor/bin/sail up -d

# Start Supervisor (queue workers + scheduler)
sudo supervisorctl start all

# Verify status
sudo supervisorctl status
```

---

## 🎯 Starting the Bot

### Quick Start

```bash
./start.sh start
```

### What Happens:
1. ✅ Docker containers start (MySQL, Redis, Laravel)
2. ✅ Queue workers initialize (3 workers)
3. ✅ Scheduler starts (cron jobs)
4. ✅ Bot begins analyzing market data
5. ✅ Dashboard becomes accessible

---

## 📊 Dashboard Screenshots

<div align="center">

### **Main Trading Dashboard**

<img src="Screenshot%202025-11-16%20135941.png" alt="Dashboard Overview" width="800">

*Real-time candlestick charts, position monitoring, and AI decision tracking*

---

<img src="Screenshot%202025-11-16%20140008.png" alt="Dashboard Detailed View" width="800">

*Performance metrics, recent trades, and bot controls*

</div>

---

## 🌐 Web Interface

Access your bot dashboard:

| 📄 Page | 🔗 URL | 📝 Description |
|---------|--------|----------------|
| **Dashboard** | `http://your-ip/` | Main trading interface with live charts |
| **Settings** | `http://your-ip/settings` | Configure all 19 bot parameters |
| **Trades** | `http://your-ip/trades` | Complete trade history with filters |
| **AI Decisions** | `http://your-ip/ai-decisions` | AI analysis & decision logs |

### 🎛️ Dashboard Features

- ✅ **Real-time Candlestick Charts** - Powered by TradingView Lightweight Charts
- ✅ **Bot Control Panel** - Start/Stop trading with one click
- ✅ **Live Position Monitoring** - Track open trades in real-time
- ✅ **Performance Metrics** - P&L, Win Rate, Total Trades
- ✅ **AI Decision Feed** - See AI reasoning for each trade
- ✅ **Multi-Symbol Support** - Switch between trading pairs
- ✅ **Multiple Timeframes** - 1m, 5m, 15m, 1h, 4h, 1d

### ⚙️ Settings Configuration

Control every aspect of your trading bot:

<details>
<summary><b>🔧 View All Settings Categories</b></summary>

1. **🤖 Bot Status** - Enable/Disable trading
2. **🔑 API Credentials** - Binance & OpenRouter keys (encrypted)
3. **💹 Trading Config** - Pairs, timeframes, analysis intervals
4. **🛡️ Risk Management** - Max positions, risk %, daily limits
5. **🧠 AI Configuration** - Model selection, confidence threshold
6. **💬 Custom Prompts** - Customize AI analysis instructions
7. **⚡ Cache Settings** - Data refresh intervals
8. **🎨 UI Preferences** - Chart refresh rates

</details>

---

## 🔌 API Documentation

### Authentication

All API endpoints require authentication using the `X-API-Key` header:

```bash
X-API-Key: your-api-access-key-here
```

### 📡 Available Endpoints

#### **Bot Control**
```http
GET  /api/v1/bot/status          # Get current bot status
POST /api/v1/bot/start           # Start the trading bot
POST /api/v1/bot/stop            # Stop the trading bot
```

#### **Trading Data**
```http
GET /api/v1/trades               # List all trades
GET /api/v1/trades/{id}          # Get specific trade
GET /api/v1/positions            # Get open positions
```

#### **Performance Analytics**
```http
GET /api/v1/performance          # Overall performance stats
GET /api/v1/performance/metrics  # Detailed metrics
```

#### **Market Data**
```http
GET /api/v1/chart/{symbol}?timeframe=5m&limit=100
```

#### **Configuration**
```http
GET /api/v1/settings             # Get all settings
PUT /api/v1/settings             # Update settings
GET /api/v1/settings/{key}       # Get specific setting
```

#### **Health Check**
```http
GET /api/health                  # No auth required
```

### 📝 Example API Request

```bash
curl -X GET "http://your-ip/api/v1/bot/status" \
  -H "X-API-Key: your-api-access-key-here" \
  -H "Content-Type: application/json"
```

**Response:**
```json
{
  "status": "running",
  "enabled": true,
  "last_analysis": "2025-11-16 14:30:00",
  "open_positions": 2,
  "daily_pnl": 45.32
}
```

---

## 📈 Monitoring & Logs

### 📋 View Logs

```bash
# All logs (combined)
./start.sh logs

# Laravel application logs
tail -f storage/logs/laravel.log

# Queue worker logs
tail -f storage/logs/worker.log

# Scheduler logs
tail -f storage/logs/scheduler.log
```

### 🔍 Queue Management

```bash
# Monitor queue in real-time
./vendor/bin/sail artisan queue:monitor

# List failed jobs
./vendor/bin/sail artisan queue:failed

# Retry all failed jobs
./vendor/bin/sail artisan queue:retry all

# Clear failed jobs
./vendor/bin/sail artisan queue:flush
```

### 🗄️ Database Inspection

```bash
./vendor/bin/sail artisan tinker
```

```php
// Check record counts
>>> App\Models\AiDecision::count();
>>> App\Models\Trade::count();
>>> App\Models\ChartData::count();

// Verify bot status
>>> App\Models\Setting::where('key', 'bot_enabled')->value('value');

// View latest AI decision
>>> App\Models\AiDecision::latest()->first();
```

---

## 🛠️ Troubleshooting

<details>
<summary><b>❌ Problem: 419 Page Expired</b></summary>

**Solution:** Configure session for HTTP:

```env
SESSION_SECURE_COOKIE=false
```

Then clear cache:
```bash
./vendor/bin/sail artisan config:clear
```

</details>

<details>
<summary><b>❌ Problem: API Returns 401 Unauthorized</b></summary>

**Solution:** Generate and configure API key:

```bash
# Generate secure key
openssl rand -hex 32

# Add to .env
API_ACCESS_KEY=your-generated-key
```

Restart containers:
```bash
./vendor/bin/sail restart
```

</details>

<details>
<summary><b>❌ Problem: Chart Not Displaying</b></summary>

**Solution:** Clear all caches:

```bash
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan view:clear
./vendor/bin/sail artisan config:clear
```

Hard refresh browser: `Ctrl + Shift + R`

</details>

<details>
<summary><b>❌ Problem: Bot Not Making Decisions</b></summary>

**Common Causes:**
- ❌ Bot is disabled in settings
- ❌ OpenRouter API credits exhausted
- ❌ Queue workers not running
- ❌ Invalid API credentials

**Solution:**

```bash
# Check bot status
./vendor/bin/sail artisan tinker
>>> App\Models\Setting::where('key', 'bot_enabled')->value('value');

# Enable bot
>>> App\Models\Setting::where('key', 'bot_enabled')->update(['value' => 'true']);

# Check queue workers
sudo supervisorctl status

# View failed jobs
./vendor/bin/sail artisan queue:failed

# Check logs
tail -f storage/logs/laravel.log
```

</details>

<details>
<summary><b>❌ Problem: Permission Denied</b></summary>

**Solution:**

```bash
sudo chown -R $USER:$USER .
chmod -R 755 storage bootstrap/cache
```

</details>

<details>
<summary><b>❌ Problem: Docker Containers Not Running</b></summary>

**Solution:**

```bash
# Start containers
./vendor/bin/sail up -d

# Check status
./vendor/bin/sail ps

# View logs
./vendor/bin/sail logs
```

</details>

---

## 🔒 Security

### 🛡️ Security Checklist

- [x] **API_ACCESS_KEY configured** - Protects all API endpoints
- [x] **APP_DEBUG=false** - Prevents debug info leakage in production
- [x] **Strong database password** - Never use default passwords
- [x] **Binance API restrictions** - Use Trading Only permissions (NO withdrawal)
- [x] **Regular backups** - Backup database and `.env` file daily
- [x] **Log monitoring** - Review logs for suspicious activity
- [x] **Risk limits** - Configure daily loss limits and max positions
- [x] **HTTPS recommended** - Use SSL certificate in production
- [x] **Firewall rules** - Restrict access to necessary ports only

### 🔐 Best Practices

1. **Never commit `.env` file** to version control
2. **Rotate API keys** regularly (monthly recommended)
3. **Enable IP whitelist** on Binance API settings
4. **Set withdrawal restrictions** on Binance account
5. **Monitor logs daily** for errors or anomalies
6. **Test with small amounts** before full deployment
7. **Keep dependencies updated** for security patches

---

## 📁 Project Structure

```
ai-trading-bot/
├── 📂 app/
│   ├── 📂 Jobs/                    # Background jobs
│   │   ├── AnalyzeMarketJob.php    # AI market analysis
│   │   ├── ExecuteTradeJob.php     # Trade execution
│   │   └── MonitorPositionsJob.php # Position monitoring
│   ├── 📂 Livewire/                # Real-time UI components
│   │   ├── Dashboard.php
│   │   ├── Settings.php
│   │   └── TradeHistory.php
│   ├── 📂 Models/                  # Database models
│   │   ├── Trade.php
│   │   ├── AiDecision.php
│   │   └── Setting.php
│   ├── 📂 Services/                # Business logic
│   │   ├── BinanceService.php      # Binance API integration
│   │   ├── AiService.php           # OpenRouter AI client
│   │   └── TradingService.php      # Trading orchestration
│   └── 📂 Http/Controllers/Api/    # REST API endpoints
├── 📂 config/                      # Laravel configuration
├── 📂 database/
│   ├── 📂 migrations/              # Database schema
│   └── 📂 seeders/                 # Default data seeders
├── 📂 resources/
│   └── 📂 views/                   # Blade templates & Livewire
├── 📂 routes/
│   ├── web.php                     # Web routes
│   └── api.php                     # API routes
├── 📂 storage/
│   └── 📂 logs/                    # Application logs
├── 📄 .env                         # Environment config (not in git)
├── 📄 start.sh                     # Management script
├── 📄 compose.yaml                 # Docker Compose config
└── 📄 README.md                    # This file
```

---

## 📚 Important Notes

> **⚠️ CRITICAL WARNINGS**

1. **💰 Real Money at Risk** - This bot trades with real cryptocurrency. You can lose your entire capital.
2. **🧪 Test Thoroughly** - Start with Binance testnet and small amounts
3. **👀 Monitor Daily** - Check dashboard and logs regularly
4. **💳 OpenRouter Credits** - AI requires credits; bot stops if balance is zero
5. **⚡ API Rate Limits** - Bot respects Binance limits automatically
6. **💾 Backup Database** - Run regular backups: `./vendor/bin/sail artisan backup:run`
7. **🚫 No Financial Advice** - This is a tool, not investment guidance

---

## 📞 Support

<table>
<tr>
<td align="center" width="33%">

### 🐛 Bug Reports
[GitHub Issues](https://github.com/ajul8866/ai-trading-bot/issues)

Report bugs and problems

</td>
<td align="center" width="33%">

### 📖 Documentation
[Code Comments](https://github.com/ajul8866/ai-trading-bot)

Detailed inline documentation

</td>
<td align="center" width="33%">

### 📋 Logs
`storage/logs/`

Check error logs first

</td>
</tr>
</table>

---

## 📄 License

**MIT License** - See [LICENSE](LICENSE) file for details

```
Copyright (c) 2025 AI Trading Bot

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software.
```

---

## ⚠️ Disclaimer

<div align="center">

### **⚠️ TRADING INVOLVES SIGNIFICANT RISK ⚠️**

**This bot trades with REAL MONEY on Binance Futures.**

- ❌ You can lose your entire investment
- ❌ Cryptocurrency trading is highly volatile
- ❌ Past performance does not guarantee future results
- ❌ This is NOT financial advice
- ❌ Use at your own risk

**Only invest what you can afford to lose completely.**

**The developers assume NO responsibility for financial losses.**

---

### 🤖 Powered By

**Claude 3.5 Sonnet** via OpenRouter | **Binance Futures API** | **Laravel Framework**

---

<sub>Generated and enhanced with [Claude Code](https://claude.com/claude-code)</sub>

</div>
