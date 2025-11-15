<?php

namespace App\Livewire;

use App\Models\Trade;
use Livewire\Component;

class OpenPositions extends Component
{
    public function render()
    {
        $positions = Trade::where('status', 'OPEN')
            ->with('aiDecision')
            ->orderBy('opened_at', 'desc')
            ->get();

        return view('livewire.open-positions', [
            'positions' => $positions
        ]);
    }
}
