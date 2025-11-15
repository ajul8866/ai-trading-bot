<?php

namespace App\Livewire\Dashboard;

use App\Models\AiDecision;
use Livewire\Attributes\On;
use Livewire\Component;

class AiDecisionsPanel extends Component
{
    public $decisions;

    public $filter = 'all'; // all, buy, sell, hold, executed

    public function mount()
    {
        $this->loadDecisions();
    }

    #[On('refresh-ai-decisions')]
    public function loadDecisions()
    {
        $query = AiDecision::with('trades')
            ->orderBy('analyzed_at', 'desc')
            ->limit(20);

        if ($this->filter === 'buy') {
            $query->where('decision', 'BUY');
        } elseif ($this->filter === 'sell') {
            $query->where('decision', 'SELL');
        } elseif ($this->filter === 'hold') {
            $query->where('decision', 'HOLD');
        } elseif ($this->filter === 'executed') {
            $query->where('executed', true);
        }

        $this->decisions = $query->get();
    }

    public function updatedFilter()
    {
        $this->loadDecisions();
    }

    public function render()
    {
        return view('livewire.dashboard.ai-decisions-panel');
    }
}
