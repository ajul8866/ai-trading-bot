<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;

class StartBotCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the AI Trading Bot';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🤖 Starting AI Trading Bot...');

        // Enable the bot
        Setting::updateOrCreate(
            ['key' => 'bot_enabled'],
            ['value' => 'true', 'type' => 'boolean', 'description' => 'Enable/disable trading bot']
        );

        $this->newLine();
        $this->info('✅ Bot ENABLED!');
        $this->newLine();

        $this->warn('⚠️  IMPORTANT REMINDERS:');
        $this->line('  1. Make sure queue worker is running: sail artisan queue:work');
        $this->line('  2. Make sure scheduler is running: sail artisan schedule:work');
        $this->line('  3. Configure Binance API keys in settings table');
        $this->line('  4. Configure OpenRouter API key in settings table');
        $this->line('  5. This bot trades with REAL MONEY - monitor closely!');

        $this->newLine();
        $this->info('📊 Bot Configuration:');

        $settings = Setting::whereIn('key', [
            'trading_pairs',
            'timeframes',
            'max_positions',
            'risk_per_trade',
            'daily_loss_limit',
            'analysis_interval',
            'min_confidence'
        ])->get();

        foreach ($settings as $setting) {
            $value = is_array($setting->value) ? json_encode($setting->value) : $setting->value;
            $this->line("  • {$setting->description}: {$value}");
        }

        $this->newLine();
        $this->info('🚀 Bot is now active and will start trading based on scheduler!');

        return Command::SUCCESS;
    }
}
