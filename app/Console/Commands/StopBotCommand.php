<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\Trade;
use Illuminate\Console\Command;

class StopBotCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:stop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stop the AI Trading Bot';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🛑 Stopping AI Trading Bot...');

        // Check for open positions
        $openPositions = Trade::where('status', 'OPEN')->count();

        if ($openPositions > 0) {
            $this->newLine();
            $this->warn("⚠️  WARNING: You have {$openPositions} open position(s)!");
            $this->line('   The bot will stop opening NEW positions, but:');
            $this->line('   • Existing positions will NOT be closed automatically');
            $this->line('   • Position monitoring will continue if scheduler is running');
            $this->line('   • You should close positions manually or wait for SL/TP');
            $this->newLine();

            if (!$this->confirm('Do you want to continue stopping the bot?', true)) {
                $this->info('❌ Bot stop cancelled.');
                return Command::FAILURE;
            }
        }

        // Disable the bot
        Setting::updateOrCreate(
            ['key' => 'bot_enabled'],
            ['value' => 'false', 'type' => 'boolean', 'description' => 'Enable/disable trading bot']
        );

        $this->newLine();
        $this->info('✅ Bot STOPPED!');
        $this->newLine();

        $this->info('📊 Status:');
        $this->line("  • Bot is now DISABLED");
        $this->line("  • No new trades will be opened");
        $this->line("  • Open positions: {$openPositions}");

        if ($openPositions > 0) {
            $this->newLine();
            $this->info('💡 Tip: Use `sail artisan tinker` to manually manage positions:');
            $this->line('   Trade::where(\'status\', \'OPEN\')->get()');
        }

        return Command::SUCCESS;
    }
}
