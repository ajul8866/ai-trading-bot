<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketData extends Model
{
    /** @use HasFactory<\Database\Factories\MarketDataFactory> */
    use HasFactory;

    protected $fillable = [
        'symbol',
        'timeframe',
        'open',
        'high',
        'low',
        'close',
        'volume',
        'indicators',
        'candle_time',
    ];

    protected function casts(): array
    {
        return [
            'open' => 'decimal:8',
            'high' => 'decimal:8',
            'low' => 'decimal:8',
            'close' => 'decimal:8',
            'volume' => 'decimal:8',
            'indicators' => 'array',
            'candle_time' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
