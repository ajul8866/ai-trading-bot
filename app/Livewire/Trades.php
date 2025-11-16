<?php

namespace App\Livewire;

use App\Models\Trade;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Trades extends Component
{
    use WithPagination;

    public $filterSymbol = '';
    public $filterSide = '';
    public $filterStatus = '';
    public $filterDateFrom = '';
    public $filterDateTo = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';

    // Stats
    public $totalTrades = 0;
    public $openTrades = 0;
    public $closedTrades = 0;
    public $totalPnl = 0;
    public $winRate = 0;
    public $avgPnl = 0;
    public $totalVolume = 0;
    public $bestTrade = 0;
    public $worstTrade = 0;

    public function mount()
    {
        $this->loadStats();
    }

    public function loadStats()
    {
        $allTrades = Trade::all();
        $closedTrades = $allTrades->where('status', 'CLOSED');

        $this->totalTrades = $allTrades->count();
        $this->openTrades = $allTrades->where('status', 'OPEN')->count();
        $this->closedTrades = $closedTrades->count();
        $this->totalPnl = $closedTrades->sum('pnl');

        $winningTrades = $closedTrades->where('pnl', '>', 0)->count();
        $this->winRate = $this->closedTrades > 0 ? round(($winningTrades / $this->closedTrades) * 100, 1) : 0;

        $this->avgPnl = $this->closedTrades > 0 ? round($this->totalPnl / $this->closedTrades, 2) : 0;
        $this->totalVolume = $allTrades->sum('margin');
        $this->bestTrade = $closedTrades->max('pnl') ?? 0;
        $this->worstTrade = $closedTrades->min('pnl') ?? 0;
    }

    public function updatingFilterSymbol()
    {
        $this->resetPage();
    }

    public function updatingFilterSide()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterDateFrom()
    {
        $this->resetPage();
    }

    public function updatingFilterDateTo()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function clearFilters()
    {
        $this->filterSymbol = '';
        $this->filterSide = '';
        $this->filterStatus = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->resetPage();
    }

    public function render()
    {
        $query = Trade::query()->with('aiDecision');

        // Apply filters
        if ($this->filterSymbol) {
            $query->where('symbol', 'like', '%' . $this->filterSymbol . '%');
        }

        if ($this->filterSide) {
            $query->where('side', $this->filterSide);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterDateFrom) {
            $query->whereDate('created_at', '>=', $this->filterDateFrom);
        }

        if ($this->filterDateTo) {
            $query->whereDate('created_at', '<=', $this->filterDateTo);
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        $trades = $query->paginate(20);

        // Get unique symbols for filter dropdown
        $symbols = Trade::distinct()->pluck('symbol')->toArray();

        return view('livewire.trades', [
            'trades' => $trades,
            'symbols' => $symbols,
        ]);
    }
}
