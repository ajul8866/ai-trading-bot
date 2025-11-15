<?php

namespace App\Services;

use App\Interfaces\ExchangeInterface;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BinanceService implements ExchangeInterface
{
    private string $apiKey;

    private string $apiSecret;

    private string $baseUrl = 'https://fapi.binance.com'; // Futures API

    public function __construct()
    {
        $this->apiKey = Setting::where('key', 'binance_api_key')->value('value') ?? '';
        $this->apiSecret = Setting::where('key', 'binance_api_secret')->value('value') ?? '';
    }

    public function getCurrentPrice(string $symbol): float
    {
        try {
            $response = Http::get("{$this->baseUrl}/fapi/v1/ticker/price", [
                'symbol' => $symbol,
            ]);

            if ($response->successful()) {
                return (float) $response->json('price');
            }

            Log::error('Failed to get current price', ['symbol' => $symbol, 'response' => $response->body()]);

            return 0.0;
        } catch (\Exception $e) {
            Log::error('Exception getting current price', ['symbol' => $symbol, 'error' => $e->getMessage()]);

            return 0.0;
        }
    }

    public function getOHLCV(string $symbol, string $timeframe, int $limit = 100): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/fapi/v1/klines", [
                'symbol' => $symbol,
                'interval' => $timeframe,
                'limit' => $limit,
            ]);

            if ($response->successful()) {
                $klines = $response->json();

                return array_map(function ($kline) {
                    return [
                        'timestamp' => $kline[0],
                        'open' => (float) $kline[1],
                        'high' => (float) $kline[2],
                        'low' => (float) $kline[3],
                        'close' => (float) $kline[4],
                        'volume' => (float) $kline[5],
                    ];
                }, $klines);
            }

            Log::error('Failed to get OHLCV', ['symbol' => $symbol, 'response' => $response->body()]);

            return [];
        } catch (\Exception $e) {
            Log::error('Exception getting OHLCV', ['symbol' => $symbol, 'error' => $e->getMessage()]);

            return [];
        }
    }

    public function placeMarketOrder(string $symbol, string $side, float $quantity, int $leverage = 1): array
    {
        if (empty($this->apiKey) || empty($this->apiSecret)) {
            return ['error' => 'API credentials not configured'];
        }

        try {
            // Set leverage first
            $this->setLeverage($symbol, $leverage);

            $timestamp = now()->timestamp * 1000;
            $params = [
                'symbol' => $symbol,
                'side' => $side,
                'type' => 'MARKET',
                'quantity' => $quantity,
                'timestamp' => $timestamp,
            ];

            $signature = $this->generateSignature($params);
            $params['signature'] = $signature;

            $response = Http::withHeaders([
                'X-MBX-APIKEY' => $this->apiKey,
            ])->post("{$this->baseUrl}/fapi/v1/order", $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to place market order', ['params' => $params, 'response' => $response->body()]);

            return ['error' => $response->body()];
        } catch (\Exception $e) {
            Log::error('Exception placing market order', ['error' => $e->getMessage()]);

            return ['error' => $e->getMessage()];
        }
    }

    public function placeLimitOrder(string $symbol, string $side, float $quantity, float $price, int $leverage = 1): array
    {
        // Implementation similar to market order
        return ['error' => 'Not implemented yet'];
    }

    public function closePosition(string $symbol, float $quantity, string $side): array
    {
        // To close a LONG, we SELL. To close a SHORT, we BUY
        $closeSide = $side === 'LONG' ? 'SELL' : 'BUY';

        return $this->placeMarketOrder($symbol, $closeSide, $quantity);
    }

    public function getBalance(): array
    {
        if (empty($this->apiKey) || empty($this->apiSecret)) {
            return ['error' => 'API credentials not configured'];
        }

        try {
            $timestamp = now()->timestamp * 1000;
            $params = ['timestamp' => $timestamp];
            $signature = $this->generateSignature($params);
            $params['signature'] = $signature;

            $response = Http::withHeaders([
                'X-MBX-APIKEY' => $this->apiKey,
            ])->get("{$this->baseUrl}/fapi/v2/balance", $params);

            if ($response->successful()) {
                return $response->json();
            }

            return ['error' => $response->body()];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get account balance for a specific asset (defaults to USDT)
     */
    public function getAccountBalance(string $asset = 'USDT'): float
    {
        $balance = $this->getBalance();

        if (isset($balance['error'])) {
            Log::warning('Failed to get account balance, using default', ['error' => $balance['error']]);

            return 10000.0; // Default fallback
        }

        // Find the asset in the balance array
        foreach ($balance as $assetBalance) {
            if (isset($assetBalance['asset']) && $assetBalance['asset'] === $asset) {
                return (float) ($assetBalance['availableBalance'] ?? $assetBalance['balance'] ?? 0);
            }
        }

        Log::warning('Asset not found in balance response, using default', ['asset' => $asset]);

        return 10000.0; // Default fallback
    }

    public function getOpenPositions(): array
    {
        if (empty($this->apiKey) || empty($this->apiSecret)) {
            return [];
        }

        try {
            $timestamp = now()->timestamp * 1000;
            $params = ['timestamp' => $timestamp];
            $signature = $this->generateSignature($params);
            $params['signature'] = $signature;

            $response = Http::withHeaders([
                'X-MBX-APIKEY' => $this->apiKey,
            ])->get("{$this->baseUrl}/fapi/v2/positionRisk", $params);

            if ($response->successful()) {
                $positions = $response->json();

                // Filter only positions with quantity > 0
                return array_filter($positions, function ($position) {
                    return abs((float) $position['positionAmt']) > 0;
                });
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Exception getting open positions', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function setStopLoss(string $symbol, float $stopPrice, string $side): array
    {
        // Implementation for stop loss order
        return ['error' => 'Not implemented yet'];
    }

    public function setTakeProfit(string $symbol, float $takeProfitPrice, string $side): array
    {
        // Implementation for take profit order
        return ['error' => 'Not implemented yet'];
    }

    public function getOrderStatus(string $orderId): array
    {
        // Implementation for order status
        return ['error' => 'Not implemented yet'];
    }

    private function setLeverage(string $symbol, int $leverage): void
    {
        if (empty($this->apiKey) || empty($this->apiSecret)) {
            return;
        }

        try {
            $timestamp = now()->timestamp * 1000;
            $params = [
                'symbol' => $symbol,
                'leverage' => $leverage,
                'timestamp' => $timestamp,
            ];

            $signature = $this->generateSignature($params);
            $params['signature'] = $signature;

            Http::withHeaders([
                'X-MBX-APIKEY' => $this->apiKey,
            ])->post("{$this->baseUrl}/fapi/v1/leverage", $params);
        } catch (\Exception $e) {
            Log::error('Exception setting leverage', ['error' => $e->getMessage()]);
        }
    }

    private function generateSignature(array $params): string
    {
        $queryString = http_build_query($params);

        return hash_hmac('sha256', $queryString, $this->apiSecret);
    }
}
