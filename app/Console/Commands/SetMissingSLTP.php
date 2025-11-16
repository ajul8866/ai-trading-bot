<?php

namespace App\Console\Commands;

use App\Models\Trade;
use App\Services\BinanceService;
use Illuminate\Console\Command;

class SetMissingSLTP extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trading:set-missing-sltp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set missing SL/TP orders on Binance for existing open positions';

    /**
     * Execute the console command.
     */
    public function handle(BinanceService $binanceService): int
    {
        $this->info('Checking for open positions without SL/TP on Binance...');

        $openTrades = Trade::where('status', 'OPEN')->get();

        if ($openTrades->isEmpty()) {
            $this->info('No open trades found.');
            return 0;
        }

        $this->info("Found {$openTrades->count()} open trades. Setting SL/TP on Binance...");

        foreach ($openTrades as $trade) {
            $this->info("\nProcessing Trade #{$trade->id} - {$trade->symbol} {$trade->side}");
            $this->info("Entry: {$trade->entry_price} | SL: {$trade->stop_loss} | TP: {$trade->take_profit}");

            $success = true;

            // Set Stop Loss
            if ($trade->stop_loss) {
                $this->info("Setting Stop Loss at {$trade->stop_loss}...");

                $slResult = $binanceService->setStopLoss(
                    $trade->symbol,
                    $trade->stop_loss,
                    $trade->side // LONG or SHORT
                );

                if (isset($slResult['error'])) {
                    $this->error("Failed to set SL: {$slResult['error']}");
                    $success = false;
                } else {
                    $this->info("✓ Stop Loss set successfully! Order ID: " . ($slResult['orderId'] ?? 'N/A'));
                }
            } else {
                $this->warn("No stop loss value in database, skipping...");
            }

            // Set Take Profit
            if ($trade->take_profit) {
                $this->info("Setting Take Profit at {$trade->take_profit}...");

                $tpResult = $binanceService->setTakeProfit(
                    $trade->symbol,
                    $trade->take_profit,
                    $trade->side // LONG or SHORT
                );

                if (isset($tpResult['error'])) {
                    $this->error("Failed to set TP: {$tpResult['error']}");
                    $success = false;
                } else {
                    $this->info("✓ Take Profit set successfully! Order ID: " . ($tpResult['orderId'] ?? 'N/A'));
                }
            } else {
                $this->warn("No take profit value in database, skipping...");
            }

            if ($success) {
                $this->info("✓ Trade #{$trade->id} completed successfully!");
            }
        }

        $this->info("\n" . str_repeat('=', 50));
        $this->info('Done! All open positions have been processed.');
        $this->info(str_repeat('=', 50));

        return 0;
    }
}
