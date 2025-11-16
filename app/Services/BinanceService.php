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

    /**
     * Validate trading symbol format
     */
    private function validateSymbol(string $symbol): bool
    {
        // Binance symbols are uppercase alphanumeric, typically ending with USDT, BUSD, etc.
        return preg_match('/^[A-Z0-9]{2,20}$/', $symbol) === 1;
    }

    /**
     * Validate order parameters
     */
    private function validateOrderParams(string $symbol, string $side, float $quantity): array
    {
        $errors = [];

        if (!$this->validateSymbol($symbol)) {
            $errors[] = "Invalid symbol format: {$symbol}";
        }

        if (!in_array($side, ['BUY', 'SELL'])) {
            $errors[] = "Invalid side: {$side}. Must be BUY or SELL";
        }

        if ($quantity <= 0) {
            $errors[] = "Invalid quantity: {$quantity}. Must be greater than 0";
        }

        return $errors;
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

        // Validate input parameters
        $validationErrors = $this->validateOrderParams($symbol, $side, $quantity);
        if (!empty($validationErrors)) {
            return ['success' => false, 'error' => implode('; ', $validationErrors)];
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

            Log::error('Failed to place market order', [
                'symbol' => $symbol,
                'side' => $side,
                'quantity' => $quantity,
                'response' => $response->body()
            ]);

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

        // Validate input parameters
        $validationErrors = $this->validateOrderParams($symbol, $side, $quantity);
        if ($price <= 0) {
            $validationErrors[] = "Invalid price: {$price}. Must be greater than 0";
        }
        if (!empty($validationErrors)) {
            return ['success' => false, 'error' => implode('; ', $validationErrors)];
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

            Log::error('Failed to place limit order', [
                'symbol' => $symbol,
                'side' => $side,
                'quantity' => $quantity,
                'price' => $price,
                'response' => $response->body()
            ]);

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
                'closePosition' => true, // Close the entire position
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
                'closePosition' => true, // Close the entire position
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

    public function cancelOrder(string $symbol, string $orderId): array
    {
        if (empty($this->apiKey) || empty($this->apiSecret)) {
            return ['success' => false, 'error' => 'API credentials not configured'];
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
            ])->delete("{$this->baseUrl}/fapi/v1/order", $params);

            if ($response->successful()) {
                return array_merge(['success' => true], $response->json());
            }

            Log::error('Failed to cancel order', ['orderId' => $orderId, 'response' => $response->body()]);

            return [
                'success' => false,
                'error' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('Exception cancelling order', ['orderId' => $orderId, 'error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function placeStopMarketOrder(string $symbol, string $side, float $quantity, float $stopPrice, int $leverage = 1): array
    {
        if (empty($this->apiKey) || empty($this->apiSecret)) {
            return ['success' => false, 'error' => 'API credentials not configured'];
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
                'type' => 'STOP_MARKET',
                'quantity' => $quantity,
                'stopPrice' => $stopPrice,
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

            Log::error('Failed to place stop market order', ['params' => $params, 'response' => $response->body()]);

            return [
                'success' => false,
                'error' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('Exception placing stop market order', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function placeStopLimitOrder(string $symbol, string $side, float $quantity, float $stopPrice, float $limitPrice, int $leverage = 1): array
    {
        if (empty($this->apiKey) || empty($this->apiSecret)) {
            return ['success' => false, 'error' => 'API credentials not configured'];
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
                'type' => 'STOP',
                'timeInForce' => 'GTC',
                'quantity' => $quantity,
                'price' => $limitPrice,
                'stopPrice' => $stopPrice,
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

            Log::error('Failed to place stop limit order', ['params' => $params, 'response' => $response->body()]);

            return [
                'success' => false,
                'error' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('Exception placing stop limit order', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function placeTrailingStopOrder(string $symbol, string $side, float $quantity, float $activationPrice, float $callbackRate, int $leverage = 1): array
    {
        if (empty($this->apiKey) || empty($this->apiSecret)) {
            return ['success' => false, 'error' => 'API credentials not configured'];
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
                'type' => 'TRAILING_STOP_MARKET',
                'quantity' => $quantity,
                'activationPrice' => $activationPrice,
                'callbackRate' => $callbackRate, // 0.1 to 5 (represents %)
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

            Log::error('Failed to place trailing stop order', ['params' => $params, 'response' => $response->body()]);

            return [
                'success' => false,
                'error' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('Exception placing trailing stop order', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
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

    /**
     * Get order book depth (REAL DATA - NO FAKE GENERATION)
     *
     * @param string $symbol Trading pair symbol
     * @param int $limit Depth limit (5, 10, 20, 50, 100, 500, 1000)
     * @return array Order book with bids and asks
     */
    public function getDepth(string $symbol, int $limit = 50): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/fapi/v1/depth", [
                'symbol' => $symbol,
                'limit' => $limit,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Transform to consistent format with cumulative depth
                $bids = [];
                $bidCumulative = 0;
                foreach ($data['bids'] ?? [] as $bid) {
                    $price = (float) $bid[0];
                    $quantity = (float) $bid[1];
                    $bidCumulative += $quantity;

                    $bids[] = [
                        'price' => $price,
                        'quantity' => $quantity,
                        'cumulative' => $bidCumulative,
                    ];
                }

                $asks = [];
                $askCumulative = 0;
                foreach ($data['asks'] ?? [] as $ask) {
                    $price = (float) $ask[0];
                    $quantity = (float) $ask[1];
                    $askCumulative += $quantity;

                    $asks[] = [
                        'price' => $price,
                        'quantity' => $quantity,
                        'cumulative' => $askCumulative,
                    ];
                }

                return [
                    'bids' => $bids,
                    'asks' => $asks,
                    'maxBidDepth' => $bidCumulative,
                    'maxAskDepth' => $askCumulative,
                ];
            }

            Log::error('Failed to get depth', ['symbol' => $symbol, 'response' => $response->body()]);
            throw new \RuntimeException("Failed to get depth for {$symbol}");
        } catch (\Exception $e) {
            Log::error('Exception getting depth', ['symbol' => $symbol, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get 24hr ticker statistics (REAL DATA - NO FAKE GENERATION)
     *
     * @param string $symbol Trading pair symbol
     * @return array 24hr ticker data with price change, volume, etc
     */
    public function get24hrTicker(string $symbol): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/fapi/v1/ticker/24hr", [
                'symbol' => $symbol,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'symbol' => $data['symbol'],
                    'priceChange' => (float) $data['priceChange'],
                    'priceChangePercent' => (float) $data['priceChangePercent'],
                    'lastPrice' => (float) $data['lastPrice'],
                    'volume' => (float) $data['volume'],
                    'quoteVolume' => (float) $data['quoteVolume'],
                    'high' => (float) $data['highPrice'],
                    'low' => (float) $data['lowPrice'],
                    'openPrice' => (float) $data['openPrice'],
                ];
            }

            Log::error('Failed to get 24hr ticker', ['symbol' => $symbol, 'response' => $response->body()]);
            throw new \RuntimeException("Failed to get 24hr ticker for {$symbol}");
        } catch (\Exception $e) {
            Log::error('Exception getting 24hr ticker', ['symbol' => $symbol, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function generateSignature(array $params): string
    {
        $queryString = http_build_query($params);

        return hash_hmac('sha256', $queryString, $this->apiSecret);
    }
}
