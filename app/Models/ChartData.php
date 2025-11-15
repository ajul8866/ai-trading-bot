<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ChartData extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'timeframe',
        'timestamp',
        'open',
        'high',
        'low',
        'close',
        'volume',
        'number_of_trades',
        'taker_buy_volume',
        'taker_buy_quote_volume',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'open' => 'decimal:8',
        'high' => 'decimal:8',
        'low' => 'decimal:8',
        'close' => 'decimal:8',
        'volume' => 'decimal:8',
        'taker_buy_volume' => 'decimal:8',
        'taker_buy_quote_volume' => 'decimal:8',
    ];

    /**
     * Retrieve cached chart data
     */
    public static function getCached(string $symbol, string $timeframe, int $limit = 500): array
    {
        $cacheKey = "chart:{$symbol}:{$timeframe}:{$limit}";
        $ttl = self::getCacheTTL($timeframe);

        return Cache::remember($cacheKey, $ttl, function () use ($symbol, $timeframe, $limit) {
            return self::where('symbol', $symbol)
                ->where('timeframe', $timeframe)
                ->orderBy('timestamp', 'desc')
                ->limit($limit)
                ->get()
                ->reverse()
                ->values()
                ->toArray();
        });
    }

    private static function getCacheTTL(string $timeframe): int
    {
        return match ($timeframe) {
            '1m'   => 60,
            '5m'   => 300,
            '15m'  => 900,
            '30m'  => 1800,
            '1h'   => 3600,
            '4h'   => 14400,
            '1d'   => 86400,
            default => 300,
        };
    }

    /**
     * Store/update chart data
     */
    public static function storeData(array $data): void
    {
        foreach ($data as $candle) {
            self::updateOrCreate(
                [
                    'symbol' => $candle['symbol'],
                    'timeframe' => $candle['timeframe'],
                    'timestamp' => $candle['timestamp'],
                ],
                [
                    'open' => $candle['open'],
                    'high' => $candle['high'],
                    'low' => $candle['low'],
                    'close' => $candle['close'],
                    'volume' => $candle['volume'],
                    'number_of_trades' => $candle['number_of_trades'] ?? null,
                    'taker_buy_volume' => $candle['taker_buy_volume'] ?? null,
                    'taker_buy_quote_volume' => $candle['taker_buy_quote_volume'] ?? null,
                ]
            );
        }
    }

    /**
     * Invalidate cache
     */
    public static function invalidateCache(string $symbol, string $timeframe): void
    {
        Cache::tags(['charts', "chart:{$symbol}"])->flush();
    }

    /**
     * Latest candle
     */
    public static function getLatest(string $symbol, string $timeframe): ?self
    {
        return self::where('symbol', $symbol)
            ->where('timeframe', $timeframe)
            ->latest('timestamp')
            ->first();
    }

    /**
     * TradingView format
     */
    public function toTradingViewFormat(): array
    {
        return [
            'time' => $this->timestamp->timestamp,
            'open' => (float) $this->open,
            'high' => (float) $this->high,
            'low' => (float) $this->low,
            'close' => (float) $this->close,
            'volume' => (float) $this->volume,
        ];
    }
}
