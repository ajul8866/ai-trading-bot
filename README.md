# AI Trading Bot

**Autonomous AI-Powered Cryptocurrency Trading Bot for Binance Futures**

Laravel 12 | PHP 8.2+ | Livewire 3 | MySQL | Docker Sail

---

## Quick Start Guide

### Prerequisites

- **Docker & Docker Compose** (required)
- **Git**
- **Binance Futures Account** with API keys
- **OpenRouter API Key** for AI (Claude 3.5 Sonnet)

---

## Installation

### Step 1: Clone Repository

```bash
git clone https://github.com/ajul8866/ai-trading-bot.git
cd ai-trading-bot
```

### Step 2: Install Dependencies

```bash
# Install PHP dependencies (requires PHP 8.2+ and Composer on host)
composer install

# Copy environment file
cp .env.example .env
```

### Step 3: Configure Environment

Edit `.env` file with your settings:

```bash
nano .env
```

**Required Configuration:**

```env
# Application
APP_NAME=AItrading
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-server-ip

# Database (Docker Sail MySQL)
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=trading_bot
DB_USERNAME=trading_user
DB_PASSWORD=YourSecurePassword123!

# Session & Queue (use database driver)
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=false
QUEUE_CONNECTION=database
CACHE_STORE=database

# API Security (CRITICAL!)
API_ACCESS_KEY=your-64-character-secure-key-here

# Binance API
BINANCE_API_KEY=your_binance_api_key
BINANCE_API_SECRET=your_binance_api_secret
BINANCE_TESTNET=false

# OpenRouter AI
OPENROUTER_API_KEY=your_openrouter_api_key
```

**Generate Secure API Key:**

```bash
openssl rand -hex 32
```

### Step 4: Start Docker Containers

```bash
./vendor/bin/sail up -d
```

### Step 5: Setup Database

```bash
# Generate application key
./vendor/bin/sail artisan key:generate

# Run migrations
./vendor/bin/sail artisan migrate

# Seed default settings
./vendor/bin/sail artisan db:seed --class=SettingsSeeder
```

### Step 6: Build Frontend Assets

```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

### Step 7: Clear Cache

```bash
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan view:clear
./vendor/bin/sail artisan route:clear
```

---

## Starting the Bot

### Using Management Script (Recommended)

```bash
# Make script executable
chmod +x start.sh

# Start all services
./start.sh start

# Stop all services
./start.sh stop

# View logs
./start.sh logs

# Check status
./start.sh status

# Restart services
./start.sh restart
```

### Manual Start (Alternative)

```bash
# Start Docker containers
./vendor/bin/sail up -d

# Start Supervisor (manages queue workers and scheduler)
sudo supervisorctl start all

# Check status
sudo supervisorctl status
```

---

## Web Interface

Access the dashboard at your server IP:

| Page | URL | Description |
|------|-----|-------------|
| **Dashboard** | `http://your-ip/` | Main trading dashboard with charts |
| **Settings** | `http://your-ip/settings` | Configure all 19 bot settings |
| **Trades** | `http://your-ip/trades` | Trade history with filters |
| **AI Decisions** | `http://your-ip/ai-decisions` | AI decision history |

### Dashboard Features

- Real-time candlestick chart (Lightweight Charts)
- Bot start/stop control
- Open positions monitoring
- Performance metrics (P&L, Win Rate)
- Recent AI decisions
- Symbol and timeframe selection

### Settings Page

Configure all bot parameters:

- **Bot Status**: Enable/disable trading
- **API Keys**: Binance & OpenRouter credentials (encrypted)
- **Trading Configuration**: Pairs, timeframes, analysis interval
- **Risk Management**: Max positions, risk per trade, daily loss limit
- **AI Configuration**: Model, confidence threshold, prompts
- **Cache & UI**: Refresh intervals

---

## API Usage

All API endpoints require authentication with `X-API-Key` header.

### Authentication

```bash
# Include in all API requests
X-API-Key: your-api-access-key-here
```

### Available Endpoints

```bash
# Health Check (no auth required)
GET /api/health

# Bot Control
GET  /api/v1/bot/status
POST /api/v1/bot/start
POST /api/v1/bot/stop

# Trades
GET /api/v1/trades
GET /api/v1/trades/{id}

# Positions
GET /api/v1/positions

# Performance
GET /api/v1/performance
GET /api/v1/performance/metrics

# Chart Data
GET /api/v1/chart/{symbol}?timeframe=5m&limit=100

# Settings
GET /api/v1/settings
PUT /api/v1/settings
GET /api/v1/settings/{key}
```

### Example API Call

```bash
curl -X GET "http://your-ip/api/v1/bot/status" \
  -H "X-API-Key: your-api-access-key-here" \
  -H "Content-Type: application/json"
```

---

## Supervisor Configuration

The bot uses Supervisor to manage background processes. Configuration is in `/etc/supervisor/conf.d/trading-bot-worker.conf`:

```ini
[program:trading-bot-worker]
process_name=%(program_name)s_%(process_num)02d
command=docker exec -i ai-trading-bot-laravel.test-1 php /var/www/html/artisan queue:work database --sleep=3 --tries=3 --timeout=300
autostart=true
autorestart=true
numprocs=3
redirect_stderr=true
stdout_logfile=/root/ai-trading-bot/storage/logs/worker.log
stopwaitsecs=300

[program:trading-bot-scheduler]
process_name=%(program_name)s
command=docker exec -i ai-trading-bot-laravel.test-1 php /var/www/html/artisan schedule:work
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
redirect_stderr=true
stdout_logfile=/root/ai-trading-bot/storage/logs/scheduler.log
stopwaitsecs=300
```

After modifying:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart all
```

---

## Monitoring

### View Logs

```bash
# All logs
./start.sh logs

# Laravel logs only
tail -f storage/logs/laravel.log

# Worker logs
tail -f storage/logs/worker.log

# Scheduler logs
tail -f storage/logs/scheduler.log
```

### Check Queue Status

```bash
# Via Sail
./vendor/bin/sail artisan queue:monitor

# Failed jobs
./vendor/bin/sail artisan queue:failed

# Retry failed jobs
./vendor/bin/sail artisan queue:retry all

# Clear failed jobs
./vendor/bin/sail artisan queue:flush
```

### Database Check

```bash
./vendor/bin/sail artisan tinker

# Check counts
>>> App\Models\AiDecision::count();
>>> App\Models\Trade::count();
>>> App\Models\ChartData::count();

# Check bot status
>>> App\Models\Setting::where('key', 'bot_enabled')->value('value');

# Check recent decision
>>> App\Models\AiDecision::latest()->first();
```

---

## Troubleshooting

### Problem: 419 Page Expired

**Solution:** Set `SESSION_SECURE_COOKIE=false` in `.env` for HTTP:

```bash
SESSION_SECURE_COOKIE=false
```

### Problem: API Returns 401 Unauthorized

**Solution:** Configure API_ACCESS_KEY in `.env`:

```bash
# Generate key
openssl rand -hex 32

# Add to .env
API_ACCESS_KEY=your-generated-key
```

### Problem: Chart Not Displaying

**Solution:** Clear cache and refresh browser:

```bash
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan view:clear
```

Then hard refresh browser: `Ctrl+Shift+R`

### Problem: Bot Not Making Decisions

**Causes:**
1. Bot is disabled
2. OpenRouter API credits exhausted
3. Queue workers not running
4. Market data not cached

**Solution:**

```bash
# Check bot status
./vendor/bin/sail artisan tinker
>>> App\Models\Setting::where('key', 'bot_enabled')->value('value');

# Enable bot
>>> App\Models\Setting::where('key', 'bot_enabled')->update(['value' => 'true']);

# Check queue workers
sudo supervisorctl status

# Check failed jobs
./vendor/bin/sail artisan queue:failed
```

### Problem: Sail Not Running

**Solution:**

```bash
./vendor/bin/sail up -d
```

### Problem: Permission Denied

**Solution:**

```bash
sudo chown -R $USER:$USER .
chmod -R 755 storage bootstrap/cache
```

---

## Security Checklist

1. **API_ACCESS_KEY configured** - Protects API endpoints
2. **APP_DEBUG=false** - No debug info in production
3. **Strong database password** - Not default password
4. **Binance API without withdrawal** - Trading only permissions
5. **Regular backups** - Database and .env file
6. **Log monitoring** - Check for errors daily
7. **Risk limits configured** - Daily loss limit, max positions

---

## File Structure

```
ai-trading-bot/
├── app/
│   ├── Jobs/                  # Queue jobs (analyze, execute, monitor)
│   ├── Livewire/              # UI components
│   ├── Models/                # Database models
│   ├── Services/              # Business logic (Binance, AI, etc.)
│   └── Http/Controllers/Api/  # API endpoints
├── config/                    # Laravel configuration
├── database/
│   ├── migrations/            # Database schema
│   └── seeders/               # Default data
├── resources/views/           # Blade templates
├── routes/
│   ├── web.php                # Web routes
│   └── api.php                # API routes
├── storage/logs/              # Application logs
├── .env                       # Environment configuration
├── start.sh                   # Management script
└── compose.yaml               # Docker configuration
```

---

## Important Notes

1. **Test with small amounts first** - Bot trades real money
2. **Monitor regularly** - Check dashboard and logs daily
3. **OpenRouter credits** - AI requires credits, add balance if exhausted
4. **Binance rate limits** - Bot respects API limits automatically
5. **Database backups** - Backup regularly with `./vendor/bin/sail artisan backup:run`

---

## Support

- **GitHub Issues**: Report bugs and request features
- **Logs**: Check `storage/logs/` for errors
- **Documentation**: Read code comments for details

---

## License

MIT License - Use at your own risk.

---

## Warning

**This bot trades with REAL MONEY. You can LOSE your entire capital. Only invest what you can afford to lose. This is NOT financial advice.**

---

Generated with [Claude Code](https://claude.com/claude-code)
