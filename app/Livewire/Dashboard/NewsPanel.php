<?php

namespace App\Livewire\Dashboard;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\On;
use Livewire\Component;

class NewsPanel extends Component
{
    public array $news = [];

    public bool $isLoading = true;

    public string $category = 'all'; // all, crypto, market, regulation

    public function mount()
    {
        $this->loadNews();
    }

    #[On('refresh-news')]
    public function loadNews()
    {
        $this->isLoading = true;

        // Cache news for 15 minutes
        $this->news = Cache::remember('market_news_'.$this->category, 900, function () {
            try {
                // Fetch from multiple sources for redundancy
                $newsData = $this->fetchCryptoNews();

                return collect($newsData)->take(10)->toArray();
            } catch (\Exception $e) {
                \Log::error('Failed to fetch news', ['error' => $e->getMessage()]);

                return $this->getFallbackNews();
            }
        });

        $this->isLoading = false;
    }

    public function updatedCategory()
    {
        $this->loadNews();
    }

    private function fetchCryptoNews(): array
    {
        // Simulate fetching news - in production, this would use News API or similar
        // For now, return sample data structure
        return [
            [
                'title' => 'Bitcoin Reaches New All-Time High',
                'description' => 'Bitcoin surpasses $100,000 mark as institutional adoption continues to grow.',
                'source' => 'CoinDesk',
                'url' => '#',
                'published_at' => now()->subMinutes(15)->toIso8601String(),
                'sentiment' => 'positive',
                'impact' => 'high',
            ],
            [
                'title' => 'Ethereum 2.0 Upgrade Successful',
                'description' => 'Latest network upgrade shows promising results for scalability.',
                'source' => 'CoinTelegraph',
                'url' => '#',
                'published_at' => now()->subHours(2)->toIso8601String(),
                'sentiment' => 'positive',
                'impact' => 'medium',
            ],
            [
                'title' => 'New Regulatory Framework Announced',
                'description' => 'Government releases comprehensive crypto regulation guidelines.',
                'source' => 'Bloomberg',
                'url' => '#',
                'published_at' => now()->subHours(5)->toIso8601String(),
                'sentiment' => 'neutral',
                'impact' => 'high',
            ],
        ];
    }

    private function getFallbackNews(): array
    {
        return [
            [
                'title' => 'Market Update',
                'description' => 'Crypto markets showing mixed signals today.',
                'source' => 'Trading Bot',
                'url' => '#',
                'published_at' => now()->toIso8601String(),
                'sentiment' => 'neutral',
                'impact' => 'low',
            ],
        ];
    }

    public function render()
    {
        return view('livewire.dashboard.news-panel');
    }
}
