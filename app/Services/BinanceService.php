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
        $this->apiKey = Setting::getValue('binance_api_key', '');
        $this->apiSecret = Setting::getValue('binance_api_secret', '');
    }

    public function getCurrentPrice(string $symbol): float
    {
        try {
            $response = Http::get("{$this->baseUrl}/fapi/v1/ticker/price", [
                'symbol' => $symbol,
            ]);

            if ($response->successful()) {
                $price = (float) $response->json('price');
                if ($price > 0) {
                    return $price;
                }
            }

            Log::error('Failed to get current price', ['symbol' => $symbol, 'response' => $response->body()]);
            throw new \RuntimeException("Failed to get current price for {$symbol}");
        } catch (\Exception $e) {
            Log::error('Exception getting current price', ['symbol' => $symbol, 'error' => $e->getMessage()]);
            throw $e;
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
            if (!$this->setLeverage($symbol, $leverage)) {
                Log::warning('Failed to set leverage, continuing with current leverage', [
                    'symbol' => $symbol,
                    'requested_leverage' => $leverage
                ]);
            }

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
                $data = $response->json();

                return array_merge(['success' => true], $data);
            }

            Log::error('Failed to place market order', ['params' => $params, 'response' => $response->body()]);

            return [
                'success' => false,
                'error' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('Exception placing market order', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function placeLimitOrder(string $symbol, string $side, float $quantity, float $price, int $leverage = 1): array
    {
        if (empty($this->apiKey) || empty($this->apiSecret)) {
            return ['error' => 'API credentials not configured'];
        }

        try {
            // Set leverage first
            if (!$this->setLeverage($symbol, $leverage)) {
                Log::warning('Failed to set leverage, continuing with current leverage', [
                    'symbol' => $symbol,
                    'requested_leverage' => $leverage
                ]);
            }

            $timestamp = now()->timestamp * 1000;
            $params = [
                'symbol' => $symbol,
                'side' => $side,
                'type' => 'LIMIT',
                'timeInForce' => 'GTC', // Good Till Cancel
                'quantity' => $quantity,
                'price' => $price,
                'timestamp' => $timestamp,
            ];

            $signature = $this->generateSignature($params);
            $params['signature'] = $signature;

            $response = Http::withHeaders([
                'X-MBX-APIKEY' => $this->apiKey,
            ])->post("{$this->baseUrl}/fapi/v1/order", $params);

            if ($response->successful()) {
                $data = $response->json();

                return array_merge(['success' => true], $data);
            }

            Log::error('Failed to place limit order', ['params' => $params, 'response' => $response->body()]);

            return [
                'success' => false,
                'error' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('Exception placing limit order', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
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
            Log::error('Failed to get account balance from API', ['error' => $balance['error']]);

            // Use configured account balance as fallback
            $fallbackBalance = (float) Setting::getValue('account_balance', 0);
            if ($fallbackBalance > 0) {
                Log::warning('Using configured account_balance setting as fallback', ['balance' => $fallbackBalance]);
                return $fallbackBalance;
            }

            throw new \RuntimeException('Cannot get account balance from API and no valid fallback configured');
        }

        // Find the asset in the balance array
        foreach ($balance as $assetBalance) {
            if (isset($assetBalance['asset']) && $assetBalance['asset'] === $asset) {
                return (float) ($assetBalance['availableBalance'] ?? $assetBalance['balance'] ?? 0);
            }
        }

        Log::error('Asset not found in balance response', ['asset' => $asset, 'available_assets' => array_column($balance, 'asset')]);

        // Use configured account balance as fallback
        $fallbackBalance = (float) Setting::getValue('account_balance', 0);
        if ($fallbackBalance > 0) {
            Log::warning('Asset not found, using configured account_balance setting as fallback', ['balance' => $fallbackBalance]);
            return $fallbackBalance;
        }

        throw new \RuntimeException("Asset {$asset} not found in balance response and no valid fallback configured");
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
        if (empty($this->apiKey) || empty($this->apiSecret)) {
            return ['error' => 'API credentials not configured'];
        }

        try {
            // For stop loss, we use STOP_MARKET order
            // If we have a LONG position, stop loss is a SELL order below entry
            // If we have a SHORT position, stop loss is a BUY order above entry
            $orderSide = $side === 'LONG' ? 'SELL' : 'BUY';

            $timestamp = now()->timestamp * 1000;
            $params = [
                'symbol' => $symbol,
                'side' => $orderSide,
                'type' => 'STOP_MARKET',
                'stopPrice' => $stopPrice,
                'closePosition' => 'true', // Close the entire position
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

            Log::error('Failed to set stop loss', ['params' => $params, 'response' => $response->body()]);

            return ['error' => $response->body()];
        } catch (\Exception $e) {
            Log::error('Exception setting stop loss', ['error' => $e->getMessage()]);

            return ['error' => $e->getMessage()];
        }
    }

    public function setTakeProfit(string $symbol, float $takeProfitPrice, string $side): array
    {
        if (empty($this->apiKey) || empty($this->apiSecret)) {
            return ['error' => 'API credentials not configured'];
        }

        try {
            // For take profit, we use TAKE_PROFIT_MARKET order
            // If we have a LONG position, take profit is a SELL order above entry
            // If we have a SHORT position, take profit is a BUY order below entry
            $orderSide = $side === 'LONG' ? 'SELL' : 'BUY';

            $timestamp = now()->timestamp * 1000;
            $params = [
                'symbol' => $symbol,
                'side' => $orderSide,
                'type' => 'TAKE_PROFIT_MARKET',
                'stopPrice' => $takeProfitPrice,
                'closePosition' => 'true', // Close the entire position
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

            Log::error('Failed to set take profit', ['params' => $params, 'response' => $response->body()]);

            return ['error' => $response->body()];
        } catch (\Exception $e) {
            Log::error('Exception setting take profit', ['error' => $e->getMessage()]);

            return ['error' => $e->getMessage()];
        }
    }

    public function getOrderStatus(string $symbol, string $orderId): array
    {
        if (empty($this->apiKey) || empty($this->apiSecret)) {
            return ['error' => 'API credentials not configured'];
        }

        try {
            $timestamp = now()->timestamp * 1000;
            $params = [
                'symbol' => $symbol,
                'orderId' => $orderId,
                'timestamp' => $timestamp,
            ];

            $signature = $this->generateSignature($params);
            $params['signature'] = $signature;

            $response = Http::withHeaders([
                'X-MBX-APIKEY' => $this->apiKey,
            ])->get("{$this->baseUrl}/fapi/v1/order", $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to get order status', ['orderId' => $orderId, 'response' => $response->body()]);

            return ['error' => $response->body()];
        } catch (\Exception $e) {
            Log::error('Exception getting order status', ['orderId' => $orderId, 'error' => $e->getMessage()]);

            return ['error' => $e->getMessage()];
        }
    }

    private function setLeverage(string $symbol, int $leverage): bool
    {
        if (empty($this->apiKey) || empty($this->apiSecret)) {
            Log::warning('Cannot set leverage: API credentials not configured');
            return false;
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

            $response = Http::withHeaders([
                'X-MBX-APIKEY' => $this->apiKey,
            ])->post("{$this->baseUrl}/fapi/v1/leverage", $params);

            if ($response->successful()) {
                return true;
            }

            Log::error('Failed to set leverage', ['response' => $response->body()]);
            return false;
        } catch (\Exception $e) {
            Log::error('Exception setting leverage', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function generateSignature(array $params): string
    {
        $queryString = http_build_query($params);

        return hash_hmac('sha256', $queryString, $this->apiSecret);
    }
}
