<?php

namespace App\Livewire;

use App\Models\Trade;
use Livewire\Component;
use Livewire\WithPagination;

class RecentTrades extends Component
{
    use WithPagination;

    public $filter = 'all'; // all, closed, cancelled

    public function updatingFilter()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Trade::with('aiDecision')
            ->orderBy('created_at', 'desc');

        if ($this->filter === 'closed') {
            $query->where('status', 'CLOSED');
        } elseif ($this->filter === 'cancelled') {
            $query->where('status', 'CANCELLED');
        }

        $trades = $query->paginate(10);

        // Calculate statistics
        $stats = [
            'total_trades' => Trade::where('status', 'CLOSED')->count(),
            'winning_trades' => Trade::where('status', 'CLOSED')->where('pnl', '>', 0)->count(),
            'losing_trades' => Trade::where('status', 'CLOSED')->where('pnl', '<', 0)->count(),
            'total_pnl' => Trade::where('status', 'CLOSED')->sum('pnl'),
            'win_rate' => 0,
        ];

        if ($stats['total_trades'] > 0) {
            $stats['win_rate'] = ($stats['winning_trades'] / $stats['total_trades']) * 100;
        }

        return view('livewire.recent-trades', [
            'trades' => $trades,
            'stats' => $stats,
        ]);
    }
}
