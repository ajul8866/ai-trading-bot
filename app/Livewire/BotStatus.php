<?php

namespace App\Livewire;

use App\Models\Setting;
use App\Models\Trade;
use Livewire\Attributes\On;
use Livewire\Component;

class BotStatus extends Component
{
    public bool $botEnabled = false;
    public int $openPositions = 0;
    public float $todayPnL = 0;

    public function mount()
    {
        $this->loadStatus();
    }

    #[On('refresh-status')]
    public function loadStatus()
    {
        $this->botEnabled = Setting::where('key', 'bot_enabled')->value('value') === 'true';
        $this->openPositions = Trade::where('status', 'OPEN')->count();
        $this->todayPnL = Trade::where('status', 'CLOSED')
            ->whereDate('closed_at', today())
            ->sum('pnl');
    }

    public function toggleBot()
    {
        $this->botEnabled = !$this->botEnabled;

        Setting::updateOrCreate(
            ['key' => 'bot_enabled'],
            ['value' => $this->botEnabled ? 'true' : 'false']
        );

        $this->dispatch('bot-toggled', enabled: $this->botEnabled);
    }

    public function render()
    {
        return view('livewire.bot-status');
    }
}
