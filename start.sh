#!/bin/bash

# AI Trading Bot Management Script
# Usage: ./start.sh [start|stop|restart|status|logs|bot-start|bot-stop]

set -e

PROJECT_DIR="/root/ai-trading-bot"
cd "$PROJECT_DIR"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to start all services
start_services() {
    print_status "Starting AI Trading Bot Services..."

    # Start Laravel Sail (Docker containers)
    print_status "Starting Docker containers (Laravel, MySQL, Redis)..."
    ./vendor/bin/sail up -d

    # Wait for containers to be ready
    print_status "Waiting for containers to be healthy..."
    sleep 10

    # Start Supervisor (Queue Workers & Scheduler)
    print_status "Starting Supervisor services..."
    supervisorctl start all

    # Check status
    sleep 3
    echo ""
    print_success "All services started!"
    echo ""

    # Show status
    show_status
}

# Function to stop all services
stop_services() {
    print_status "Stopping AI Trading Bot Services..."

    # Stop Supervisor first
    print_status "Stopping Supervisor services..."
    supervisorctl stop all 2>/dev/null || true

    # Stop Laravel Sail
    print_status "Stopping Docker containers..."
    ./vendor/bin/sail down

    print_success "All services stopped!"
}

# Function to restart all services
restart_services() {
    print_status "Restarting AI Trading Bot Services..."
    stop_services
    sleep 2
    start_services
}

# Function to show status
show_status() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}   AI Trading Bot System Status${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""

    # Docker containers status
    echo -e "${YELLOW}Docker Containers:${NC}"
    docker ps --format "table {{.Names}}\t{{.Status}}" | grep trading-bot || echo "No containers running"
    echo ""

    # Supervisor status
    echo -e "${YELLOW}Supervisor Services:${NC}"
    supervisorctl status 2>/dev/null || echo "Supervisor not running"
    echo ""

    # Bot status from database
    echo -e "${YELLOW}Bot Configuration:${NC}"
    if docker ps | grep -q "ai-trading-bot-laravel.test-1"; then
        docker exec -i ai-trading-bot-laravel.test-1 php artisan tinker --execute='
            $enabled = App\Models\Setting::where("key", "bot_enabled")->value("value");
            echo "Bot Status: " . ($enabled ? "ENABLED" : "DISABLED") . "\n";
            echo "AI Decisions: " . App\Models\AiDecision::count() . "\n";
            echo "Open Trades: " . App\Models\Trade::where("status", "OPEN")->count() . "\n";
            echo "Chart Data Records: " . App\Models\ChartData::count() . "\n";
        ' 2>/dev/null | grep -v ">>>" || echo "Cannot connect to Laravel"
    else
        echo "Laravel container not running"
    fi
    echo ""
}

# Function to show logs
show_logs() {
    LOG_TYPE="${1:-all}"

    case "$LOG_TYPE" in
        worker)
            print_status "Showing Queue Worker logs..."
            tail -f "$PROJECT_DIR/storage/logs/worker.log"
            ;;
        scheduler)
            print_status "Showing Scheduler logs..."
            tail -f "$PROJECT_DIR/storage/logs/scheduler.log"
            ;;
        laravel)
            print_status "Showing Laravel logs..."
            tail -f "$PROJECT_DIR/storage/logs/laravel.log"
            ;;
        all)
            print_status "Showing all logs (Ctrl+C to stop)..."
            tail -f "$PROJECT_DIR/storage/logs/laravel.log" \
                    "$PROJECT_DIR/storage/logs/worker.log" \
                    "$PROJECT_DIR/storage/logs/scheduler.log" 2>/dev/null
            ;;
        *)
            print_error "Unknown log type: $LOG_TYPE"
            echo "Available: all, worker, scheduler, laravel"
            ;;
    esac
}

# Function to enable trading bot
enable_bot() {
    print_status "Enabling Trading Bot..."
    if docker ps | grep -q "ai-trading-bot-laravel.test-1"; then
        docker exec -i ai-trading-bot-laravel.test-1 php artisan bot:start
        print_success "Bot enabled!"
    else
        print_error "Laravel container not running. Start services first."
        exit 1
    fi
}

# Function to disable trading bot
disable_bot() {
    print_status "Disabling Trading Bot..."
    if docker ps | grep -q "ai-trading-bot-laravel.test-1"; then
        docker exec -i ai-trading-bot-laravel.test-1 php artisan bot:stop
        print_success "Bot disabled!"
    else
        print_error "Laravel container not running."
        exit 1
    fi
}

# Function to clear caches
clear_cache() {
    print_status "Clearing application caches..."
    if docker ps | grep -q "ai-trading-bot-laravel.test-1"; then
        docker exec -i ai-trading-bot-laravel.test-1 php artisan optimize:clear
        print_success "Caches cleared!"
    else
        print_error "Laravel container not running."
        exit 1
    fi
}

# Function to run migrations
run_migrations() {
    print_status "Running database migrations..."
    if docker ps | grep -q "ai-trading-bot-laravel.test-1"; then
        docker exec -i ai-trading-bot-laravel.test-1 php artisan migrate --force
        print_success "Migrations completed!"
    else
        print_error "Laravel container not running."
        exit 1
    fi
}

# Function to seed settings
seed_settings() {
    print_status "Seeding default settings..."
    if docker ps | grep -q "ai-trading-bot-laravel.test-1"; then
        docker exec -i ai-trading-bot-laravel.test-1 php artisan db:seed --class=SettingsSeeder --force
        print_success "Settings seeded!"
    else
        print_error "Laravel container not running."
        exit 1
    fi
}

# Main command handler
case "${1:-help}" in
    start)
        start_services
        ;;
    stop)
        stop_services
        ;;
    restart)
        restart_services
        ;;
    status)
        show_status
        ;;
    logs)
        show_logs "${2:-all}"
        ;;
    bot-start)
        enable_bot
        ;;
    bot-stop)
        disable_bot
        ;;
    clear-cache)
        clear_cache
        ;;
    migrate)
        run_migrations
        ;;
    seed)
        seed_settings
        ;;
    help|*)
        echo ""
        echo -e "${BLUE}AI Trading Bot Management Script${NC}"
        echo -e "${BLUE}=================================${NC}"
        echo ""
        echo "Usage: ./start.sh <command>"
        echo ""
        echo "Commands:"
        echo "  start         Start all services (Docker + Supervisor)"
        echo "  stop          Stop all services"
        echo "  restart       Restart all services"
        echo "  status        Show system status"
        echo "  logs [type]   Show logs (all/worker/scheduler/laravel)"
        echo "  bot-start     Enable trading bot"
        echo "  bot-stop      Disable trading bot"
        echo "  clear-cache   Clear application caches"
        echo "  migrate       Run database migrations"
        echo "  seed          Seed default settings"
        echo "  help          Show this help message"
        echo ""
        echo "Examples:"
        echo "  ./start.sh start          # Start everything"
        echo "  ./start.sh stop           # Stop everything"
        echo "  ./start.sh logs           # View all logs"
        echo "  ./start.sh logs worker    # View only worker logs"
        echo "  ./start.sh bot-start      # Enable the trading bot"
        echo "  ./start.sh status         # Check system status"
        echo ""
        ;;
esac
