<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceSnapshot extends Model
{
    /** @use HasFactory<\Database\Factories\PerformanceSnapshotFactory> */
    use HasFactory;

    protected $fillable = [
        'snapshot_at',
        'period',
        'total_trades',
        'winning_trades',
        'total_pnl',
        'sharpe_ratio',
        'sortino_ratio',
        'max_drawdown',
        'win_rate',
        'avg_win',
        'avg_loss',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_at' => 'datetime',
            'total_trades' => 'integer',
            'winning_trades' => 'integer',
            'total_pnl' => 'decimal:8',
            'sharpe_ratio' => 'decimal:4',
            'sortino_ratio' => 'decimal:4',
            'max_drawdown' => 'decimal:4',
            'win_rate' => 'decimal:2',
            'avg_win' => 'decimal:8',
            'avg_loss' => 'decimal:8',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
