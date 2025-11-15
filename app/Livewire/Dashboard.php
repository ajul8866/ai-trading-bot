<?php

namespace App\Livewire;

use App\Models\AiDecision;
use App\Models\Setting;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $recentDecisions = AiDecision::with('trades')
            ->orderBy('analyzed_at', 'desc')
            ->limit(5)
            ->get();

        $settings = Setting::whereIn('key', [
            'trading_pairs',
            'max_positions',
            'risk_per_trade',
            'daily_loss_limit',
            'min_confidence',
        ])->get()->keyBy('key');

        return view('livewire.dashboard', [
            'recentDecisions' => $recentDecisions,
            'settings' => $settings,
        ]);
    }
}
