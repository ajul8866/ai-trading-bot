<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we need to use raw SQL to modify enum
        // For MySQL/PostgreSQL, use ALTER TABLE
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite doesn't support ALTER COLUMN, so we update the constraint
            DB::statement("
                CREATE TABLE trades_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    symbol VARCHAR NOT NULL,
                    side VARCHAR CHECK(side IN ('BUY', 'SELL', 'LONG', 'SHORT')) NOT NULL,
                    entry_price DECIMAL(20, 8) NOT NULL,
                    exit_price DECIMAL(20, 8),
                    quantity DECIMAL(20, 8) NOT NULL,
                    leverage INTEGER DEFAULT 1 NOT NULL,
                    stop_loss DECIMAL(20, 8),
                    take_profit DECIMAL(20, 8),
                    status VARCHAR CHECK(status IN ('OPEN', 'CLOSED', 'CANCELLED')) DEFAULT 'OPEN' NOT NULL,
                    pnl DECIMAL(20, 8),
                    pnl_percentage DECIMAL(10, 4),
                    binance_order_id VARCHAR,
                    ai_decision_id INTEGER,
                    opened_at DATETIME NOT NULL,
                    closed_at DATETIME,
                    created_at DATETIME,
                    updated_at DATETIME,
                    margin DECIMAL(20, 8),
                    order_type VARCHAR
                );
            ");

            // Copy data if table exists
            if (Schema::hasTable('trades')) {
                DB::statement("
                    INSERT INTO trades_new (id, symbol, side, entry_price, exit_price, quantity, leverage,
                                           stop_loss, take_profit, status, pnl, pnl_percentage, binance_order_id,
                                           ai_decision_id, opened_at, closed_at, created_at, updated_at)
                    SELECT id, symbol, side, entry_price, exit_price, quantity, leverage,
                           stop_loss, take_profit, status, pnl, pnl_percentage, binance_order_id,
                           ai_decision_id, opened_at, closed_at, created_at, updated_at
                    FROM trades
                ");

                DB::statement("DROP TABLE trades");
            }

            DB::statement("ALTER TABLE trades_new RENAME TO trades");

            // Recreate indexes
            DB::statement("CREATE INDEX trades_symbol_status_index ON trades (symbol, status)");
            DB::statement("CREATE INDEX trades_opened_at_index ON trades (opened_at)");
        } else {
            // For MySQL/PostgreSQL
            DB::statement("ALTER TABLE trades MODIFY COLUMN side ENUM('BUY', 'SELL', 'LONG', 'SHORT')");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // Revert back to LONG/SHORT only
            DB::statement("
                CREATE TABLE trades_old (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    symbol VARCHAR NOT NULL,
                    side VARCHAR CHECK(side IN ('LONG', 'SHORT')) NOT NULL,
                    entry_price DECIMAL(20, 8) NOT NULL,
                    exit_price DECIMAL(20, 8),
                    quantity DECIMAL(20, 8) NOT NULL,
                    leverage INTEGER DEFAULT 1 NOT NULL,
                    stop_loss DECIMAL(20, 8),
                    take_profit DECIMAL(20, 8),
                    status VARCHAR CHECK(status IN ('OPEN', 'CLOSED', 'CANCELLED')) DEFAULT 'OPEN' NOT NULL,
                    pnl DECIMAL(20, 8),
                    pnl_percentage DECIMAL(10, 4),
                    binance_order_id VARCHAR,
                    ai_decision_id INTEGER,
                    opened_at DATETIME NOT NULL,
                    closed_at DATETIME,
                    created_at DATETIME,
                    updated_at DATETIME
                );
            ");

            if (Schema::hasTable('trades')) {
                DB::statement("
                    INSERT INTO trades_old SELECT * FROM trades WHERE side IN ('LONG', 'SHORT')
                ");
                DB::statement("DROP TABLE trades");
            }

            DB::statement("ALTER TABLE trades_old RENAME TO trades");
        } else {
            DB::statement("ALTER TABLE trades MODIFY COLUMN side ENUM('LONG', 'SHORT')");
        }
    }
};
