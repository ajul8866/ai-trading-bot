<?php

namespace App\Livewire;

use App\Models\AiDecision;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class AiDecisions extends Component
{
    use WithPagination;

    public $filterSymbol = '';
    public $filterDecision = '';
    public $filterExecuted = '';
    public $filterDateFrom = '';
    public $filterDateTo = '';
    public $sortBy = 'analyzed_at';
    public $sortDirection = 'desc';

    // Stats
    public $totalDecisions = 0;
    public $executedCount = 0;
    public $avgConfidence = 0;
    public $buyCount = 0;
    public $sellCount = 0;
    public $holdCount = 0;
    public $highConfidenceCount = 0;

    public function mount()
    {
        $this->loadStats();
    }

    public function loadStats()
    {
        $this->totalDecisions = AiDecision::count();
        $this->executedCount = AiDecision::where('executed', true)->count();
        $this->avgConfidence = round(AiDecision::avg('confidence') ?? 0, 1);
        $this->buyCount = AiDecision::where('decision', 'BUY')->count();
        $this->sellCount = AiDecision::where('decision', 'SELL')->count();
        $this->holdCount = AiDecision::where('decision', 'HOLD')->count();
        $this->highConfidenceCount = AiDecision::where('confidence', '>=', 70)->count();
    }

    public function updatingFilterSymbol()
    {
        $this->resetPage();
    }

    public function updatingFilterDecision()
    {
        $this->resetPage();
    }

    public function updatingFilterExecuted()
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
        $this->filterDecision = '';
        $this->filterExecuted = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->resetPage();
    }

    public function render()
    {
        $query = AiDecision::query()->with('trades');

        // Apply filters
        if ($this->filterSymbol) {
            $query->where('symbol', 'like', '%' . $this->filterSymbol . '%');
        }

        if ($this->filterDecision) {
            $query->where('decision', $this->filterDecision);
        }

        if ($this->filterExecuted !== '') {
            $query->where('executed', $this->filterExecuted === 'yes');
        }

        if ($this->filterDateFrom) {
            $query->whereDate('analyzed_at', '>=', $this->filterDateFrom);
        }

        if ($this->filterDateTo) {
            $query->whereDate('analyzed_at', '<=', $this->filterDateTo);
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        $decisions = $query->paginate(15);

        // Get unique symbols for filter dropdown
        $symbols = AiDecision::distinct()->pluck('symbol')->toArray();

        return view('livewire.ai-decisions', [
            'decisions' => $decisions,
            'symbols' => $symbols,
        ]);
    }
}
