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

    /**
     * Fetch REAL crypto news from CryptoPanic API
     * NO FAKE DATA!
     */
    private function fetchCryptoNews(): array
    {
        $apiKey = env('CRYPTOPANIC_API_KEY');

        if (empty($apiKey)) {
            \Log::warning('CRYPTOPANIC_API_KEY not configured - news feed will be empty');
            return [];
        }

        try {
            // Fetch from CryptoPanic API - REAL NEWS ONLY!
            $filter = match ($this->category) {
                'crypto' => 'currencies',
                'market' => 'trending',
                'regulation' => 'news',
                default => 'all',
            };

            $response = Http::timeout(10)->get('https://cryptopanic.com/api/v1/posts/', [
                'auth_token' => $apiKey,
                'filter' => $filter,
                'public' => 'true',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $posts = $data['results'] ?? [];

                return array_map(function ($post) {
                    // Determine sentiment from votes
                    $positive = $post['votes']['positive'] ?? 0;
                    $negative = $post['votes']['negative'] ?? 0;
                    $total = $positive + $negative;

                    $sentiment = 'neutral';
                    if ($total > 0) {
                        $sentimentScore = ($positive - $negative) / $total;
                        if ($sentimentScore > 0.2) {
                            $sentiment = 'positive';
                        } elseif ($sentimentScore < -0.2) {
                            $sentiment = 'negative';
                        }
                    }

                    return [
                        'title' => $post['title'] ?? 'No title',
                        'description' => $post['metadata']['description'] ?? '',
                        'source' => $post['source']['title'] ?? 'Unknown',
                        'url' => $post['url'] ?? '#',
                        'published_at' => $post['published_at'] ?? now()->toIso8601String(),
                        'sentiment' => $sentiment,
                        'impact' => $post['metadata']['important'] ?? false ? 'high' : 'medium',
                    ];
                }, $posts);
            }

            \Log::error('Failed to fetch CryptoPanic news', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        } catch (\Exception $e) {
            \Log::error('Exception fetching crypto news from CryptoPanic', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Fallback when API fails - return EMPTY array, NOT FAKE NEWS!
     */
    private function getFallbackNews(): array
    {
        // NO FAKE NEWS! Return empty array when API fails
        \Log::warning('Using fallback news (empty) - CryptoPanic API unavailable');
        return [];
    }

    public function render()
    {
        return view('livewire.dashboard.news-panel');
    }
}
