<?php
namespace Tigusigalpa\ByBit;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Tigusigalpa\ByBit\Exceptions\BybitHttpException;

class BybitClient
{
    protected string $apiKey;
    protected ?string $apiSecret;
    protected bool $testnet;
    protected bool $demoTrading;
    protected string $region;
    protected int $recvWindow;
    protected string $signature;
    protected ?string $rsaPrivateKey;
    protected Client $http;
    protected array $fees;
    protected float $lastRequestTime = 0;
    protected bool $throwOnError = false;
    protected ?string $brokerId;

    public function __construct(string $apiKey, ?string $apiSecret, bool $testnet = false, string $region = 'global', int $recvWindow = 5000, string $signature = 'hmac', ?string $rsaPrivateKey = null, ?Client $http = null, ?array $fees = null, bool $demoTrading = false, bool $throwOnError = false, ?string $brokerId = null)
    {
        $signature = strtolower(trim($signature));
        if ($signature === '') {
            $signature = 'hmac';
        }
        if (!in_array($signature, ['hmac', 'rsa'], true)) {
            throw new \InvalidArgumentException('Unsupported signature type. Use hmac or rsa.');
        }
        if ($signature === 'rsa' && empty($rsaPrivateKey)) {
            throw new \InvalidArgumentException('RSA signature requires an RSA private key.');
        }
        if ($testnet && $demoTrading) {
            throw new \InvalidArgumentException('Testnet and Demo Trading cannot be enabled together.');
        }
        if ($recvWindow <= 0) {
            throw new \InvalidArgumentException('Receive window must be greater than zero.');
        }

        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->testnet = $testnet;
        $this->demoTrading = $demoTrading;
        $this->region = strtolower(trim($region)) ?: 'global';
        $this->recvWindow = $recvWindow;
        $this->signature = $signature;
        $this->rsaPrivateKey = $rsaPrivateKey;
        $this->brokerId = $brokerId !== null && trim($brokerId) !== '' ? trim($brokerId) : null;
        $this->http = $http ?: new Client(['base_uri' => $this->baseUri()]);
        $this->fees = $fees ?: [
            'spot' => [
                'Non-VIP' => ['maker' => 0.0010, 'taker' => 0.0010],
                'VIP1' => ['maker' => 0.000675, 'taker' => 0.0010],
                'VIP2' => ['maker' => 0.000650, 'taker' => 0.000775],
                'VIP3' => ['maker' => 0.000625, 'taker' => 0.000750],
                'VIP4' => ['maker' => 0.000500, 'taker' => 0.000600],
                'VIP5' => ['maker' => 0.000400, 'taker' => 0.000500],
                'Supreme VIP' => ['maker' => 0.000300, 'taker' => 0.000450],
            ],
            'derivatives' => [
                'Non-VIP' => ['maker' => 0.000400, 'taker' => 0.001000],
            ],
        ];
        $this->throwOnError = $throwOnError;
    }

    /**
     * Create a client for Bybit's isolated Demo Trading environment.
     *
     * The factory deliberately has no Testnet or region argument: demo keys belong
     * to api-demo.bybit.com and must never be used with the Testnet endpoints.
     */
    public static function demo(string $apiKey, ?string $apiSecret, int $recvWindow = 5000, string $signature = 'hmac', ?string $rsaPrivateKey = null, ?Client $http = null, ?array $fees = null, bool $throwOnError = false, ?string $brokerId = null): self
    {
        return new self(
            $apiKey,
            $apiSecret,
            false,
            'global',
            $recvWindow,
            $signature,
            $rsaPrivateKey,
            $http,
            $fees,
            true,
            $throwOnError,
            $brokerId
        );
    }

    public function setThrowOnError(bool $throw): self
    {
        $this->throwOnError = $throw;
        return $this;
    }

    public function baseUri(): string
    {
        if ($this->demoTrading) return 'https://api-demo.bybit.com';
        if ($this->testnet) {
            return 'https://api-testnet.bybit.com';
        }
        switch ($this->region) {
            case 'nl': return 'https://api.bybit.nl';
            case 'tr': return 'https://api.bybit.tr';
            case 'kz': return 'https://api.bybit.kz';
            case 'ge': return 'https://api.bybitgeorgia.ge';
            case 'ae': return 'https://api.bybit.ae';
            case 'eu': return 'https://api.bybit.eu';
            case 'id': return 'https://api.bybit.id';
            case 'jp': return 'https://api.manepa.jp';
            case 'hk': return 'https://api.spark-fintech.com';
            default: return 'https://api.bybit.com';
        }
    }

    protected function timestamp(): string
    {
        return (string)floor(microtime(true) * 1000);
    }

    protected function signString(string $string): string
    {
        if ($this->signature === 'rsa') {
            $key = openssl_pkey_get_private($this->rsaPrivateKey);
            if ($key === false) {
                throw new \RuntimeException('Unable to load the configured RSA private key.');
            }
            if (!openssl_sign($string, $signature, $key, OPENSSL_ALGO_SHA256)) {
                throw new \RuntimeException('Unable to create the RSA signature.');
            }
            return base64_encode($signature);
        }
        return hash_hmac('sha256', $string, (string)$this->apiSecret);
    }

    /**
     * Build authentication headers for an already serialized query string or body.
     */
    protected function headers(string $method, string $payload): array
    {
        $ts = $this->timestamp();
        $recv = (string)$this->recvWindow;
        $toSign = $ts . $this->apiKey . $recv . $payload;
        $sign = $this->signString($toSign);
        $headers = [
            'X-BAPI-API-KEY' => $this->apiKey,
            'X-BAPI-TIMESTAMP' => $ts,
            'X-BAPI-RECV-WINDOW' => $recv,
            'X-BAPI-SIGN' => $sign,
            'X-BAPI-SIGN-TYPE' => '2',
            'User-Agent' => 'bybit-php',
        ];
        if ($this->brokerId !== null) {
            $headers['X-Referer'] = $this->brokerId;
        }
        if (strtoupper($method) !== 'GET') {
            $headers['Content-Type'] = 'application/json';
            $headers['Accept'] = 'application/json';
        }
        return $headers;
    }

    protected function buildQuery(array $params): string
    {
        if (!$params) return '';
        ksort($params);
        return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    protected function jsonBody(array $params): string
    {
        return $params ? json_encode($params, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) : '{}';
    }

    public function request(string $method, string $path, array $params = [], array $options = []): array
    {
        $method = strtoupper($method);
        $this->rateLimit($method);
        $payload = $method === 'GET' ? $this->buildQuery($params) : $this->jsonBody($params);
        $headers = $this->headers($method, $payload);

        $customHeaders = $options[RequestOptions::HEADERS] ?? [];
        if (!is_array($customHeaders)) {
            throw new \InvalidArgumentException('Request headers must be an array.');
        }
        $protectedHeaders = array_map('strtolower', array_keys($headers));
        $customHeaders = array_filter(
            $customHeaders,
            static function ($value, $name) use ($protectedHeaders): bool {
                return !in_array(strtolower((string)$name), $protectedHeaders, true);
            },
            ARRAY_FILTER_USE_BOTH
        );

        unset(
            $options[RequestOptions::HEADERS],
            $options[RequestOptions::QUERY],
            $options[RequestOptions::JSON],
            $options[RequestOptions::BODY],
            $options[RequestOptions::FORM_PARAMS],
            $options[RequestOptions::MULTIPART],
            $options[RequestOptions::HTTP_ERRORS]
        );

        $opts = $options;
        $opts[RequestOptions::HEADERS] = array_replace($customHeaders, $headers);
        $opts[RequestOptions::HTTP_ERRORS] = false;
        if ($method === 'GET') {
            if ($payload !== '') {
                $path .= (strpos($path, '?') === false ? '?' : '&') . $payload;
            }
        } else {
            $opts[RequestOptions::BODY] = $payload;
        }
        $res = $this->http->request($method, $path, $opts);
        $body = (string)$res->getBody();
        $statusCode = $res->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new BybitHttpException($statusCode, $res->getReasonPhrase(), $body);
        }
        $data = json_decode($body, true);
        if (!is_array($data)) {
            return ['raw' => $body];
        }
        if ($this->throwOnError && isset($data['retCode']) && $data['retCode'] !== 0) {
            throw new \RuntimeException(
                'Bybit API error: ' . ($data['retMsg'] ?? 'Unknown error') . ' (code: ' . $data['retCode'] . ')',
                (int)$data['retCode']
            );
        }
        return $data;
    }

    protected function rateLimit(string $method): void
    {
        $minInterval = ($method === 'GET') ? 0.1 : 0.3;
        $now = microtime(true);
        $elapsed = $now - $this->lastRequestTime;
        if ($elapsed < $minInterval) {
            usleep((int)(($minInterval - $elapsed) * 1000000));
        }
        $this->lastRequestTime = microtime(true);
    }

    public function endpoint(): string
    {
        return $this->baseUri();
    }

    public function getServerTime(): array
    {
        return $this->request('GET', '/v5/market/time');
    }

    public function getTickers(array $params): array
    {
        return $this->request('GET', '/v5/market/tickers', $params);
    }

    public function getKline(array $params): array
    {
        return $this->request('GET', '/v5/market/kline', $params);
    }

    public function getOrderbook(array $params): array
    {
        return $this->request('GET', '/v5/market/orderbook', $params);
    }

    public function getRPIOrderbook(array $params): array
    {
        return $this->request('GET', '/v5/market/rpi-orderbook', $params);
    }

    public function getOpenInterest(array $params): array
    {
        return $this->request('GET', '/v5/market/open-interest', $params);
    }

    public function getRecentTrades(array $params): array
    {
        return $this->request('GET', '/v5/market/recent-trade', $params);
    }

    public function getFundingRateHistory(array $params): array
    {
        return $this->request('GET', '/v5/market/funding/history', $params);
    }

    public function getHistoricalVolatility(array $params): array
    {
        return $this->request('GET', '/v5/market/historical-volatility', $params);
    }

    public function getInsurancePool(array $params): array
    {
        return $this->request('GET', '/v5/market/insurance', $params);
    }

    public function getRiskLimit(array $params): array
    {
        return $this->request('GET', '/v5/market/risk-limit', $params);
    }

    public function createOrder(array $params): array
    {
        return $this->request('POST', '/v5/order/create', $params);
    }

    public function batchCreateOrders(array $params): array
    {
        return $this->request('POST', '/v5/order/create-batch', $params);
    }

    public function batchAmendOrders(array $params): array
    {
        return $this->request('POST', '/v5/order/amend-batch', $params);
    }

    public function batchCancelOrders(array $params): array
    {
        return $this->request('POST', '/v5/order/cancel-batch', $params);
    }

    public function getOpenOrders(array $params): array
    {
        return $this->request('GET', '/v5/order/realtime', $params);
    }

    public function cancelOrder(array $params): array
    {
        return $this->request('POST', '/v5/order/cancel', $params);
    }

    public function amendOrder(array $params): array
    {
        return $this->request('POST', '/v5/order/amend', $params);
    }

    public function cancelAllOrders(array $params): array
    {
        return $this->request('POST', '/v5/order/cancel-all', $params);
    }

    public function getHistoryOrders(array $params): array
    {
        return $this->request('GET', '/v5/order/history', $params);
    }

    public function getTradeHistory(array $params): array
    {
        return $this->request('GET', '/v5/execution/list', $params);
    }

    public function getWalletBalance(array $params): array
    {
        return $this->request('GET', '/v5/account/wallet-balance', $params);
    }

    public function getTransferableAmount(array $params): array
    {
        return $this->request('GET', '/v5/account/transferable-amount', $params);
    }

    public function getTransferableCoins(array $params): array
    {
        return $this->request('GET', '/v5/asset/transfer/query-transfer-coin-list', $params);
    }

    public function getTransactionLog(array $params): array
    {
        return $this->request('GET', '/v5/account/transaction-log', $params);
    }

    public function getAccountInfo(): array
    {
        return $this->request('GET', '/v5/account/info');
    }

    public function getAccountInstrumentsInfo(array $params): array
    {
        return $this->request('GET', '/v5/account/instruments-info', $params);
    }

    public function getBorrowHistory(array $params): array
    {
        return $this->request('GET', '/v5/account/borrow-history', $params);
    }

    public function setCollateralCoin(array $params): array
    {
        return $this->request('POST', '/v5/account/set-collateral-switch', $params);
    }

    public function getCollateralInfo(array $params = []): array
    {
        return $this->request('GET', '/v5/account/collateral-info', $params);
    }

    public function getCoinGreeks(array $params = []): array
    {
        return $this->request('GET', '/v5/asset/coin-greeks', $params);
    }

    public function setMarginMode(array $params): array
    {
        return $this->request('POST', '/v5/account/set-margin-mode', $params);
    }

    public function setSpotHedging(array $params): array
    {
        return $this->request('POST', '/v5/account/set-hedging-mode', $params);
    }

    public function getPositions(array $params): array
    {
        return $this->request('GET', '/v5/position/list', $params);
    }

    public function switchPositionMode(array $params): array
    {
        return $this->request('POST', '/v5/position/switch-mode', $params);
    }

    public function setTradingStop(array $params): array
    {
        return $this->request('POST', '/v5/position/trading-stop', $params);
    }

    public function setLeverage(string $category, string $symbol, float $leverage, ?string $side = null): array
    {
        if ($leverage <= 0) {
            throw new \InvalidArgumentException('Leverage must be greater than zero.');
        }
        $payload = ['category' => $category, 'symbol' => $symbol];
        $leverageValue = rtrim(rtrim(number_format($leverage, 8, '.', ''), '0'), '.');
        if ($side !== null && strcasecmp($side, 'Buy') === 0) {
            $payload['buyLeverage'] = $leverageValue;
        } elseif ($side !== null && strcasecmp($side, 'Sell') === 0) {
            $payload['sellLeverage'] = $leverageValue;
        } elseif ($side !== null) {
            throw new \InvalidArgumentException('Side must be Buy or Sell.');
        } else {
            $payload['buyLeverage'] = $leverageValue;
            $payload['sellLeverage'] = $leverageValue;
        }
        return $this->request('POST', '/v5/position/set-leverage', $payload);
    }

    public function setAutoAddMargin(array $params): array
    {
        return $this->request('POST', '/v5/position/set-auto-add-margin', $params);
    }

    public function addOrReduceMargin(array $params): array
    {
        return $this->request('POST', '/v5/position/add-margin', $params);
    }

    public function getClosedPnL(array $params): array
    {
        return $this->request('GET', '/v5/position/closed-pnl', $params);
    }

    public function getClosedOptionsPositions(array $params): array
    {
        return $this->request('GET', '/v5/position/close-position', $params);
    }

    public function movePosition(array $params): array
    {
        return $this->request('POST', '/v5/position/move-positions', $params);
    }

    public function getMovePositionHistory(array $params): array
    {
        return $this->request('GET', '/v5/position/move-history', $params);
    }

    public function confirmNewRiskLimit(array $params): array
    {
        return $this->request('POST', '/v5/position/confirm-pending-mmr', $params);
    }

    public function getDeliveryRecord(array $params): array
    {
        return $this->request('GET', '/v5/asset/delivery-record', $params);
    }

    public function getUSDCSettlement(array $params): array
    {
        return $this->request('GET', '/v5/asset/settlement-record', $params);
    }

    public function toggleMarginTrade(array $params): array
    {
        return $this->request('POST', '/v5/spot-margin-trade/switch-mode', $params);
    }

    public function setSpotMarginLeverage(array $params): array
    {
        return $this->request('POST', '/v5/spot-margin-trade/set-leverage', $params);
    }

    public function getSpotMarginStatus(array $params = []): array
    {
        return $this->request('GET', '/v5/spot-margin-uta/status', $params);
    }

    protected function lastPrice(string $symbol, string $category): ?float
    {
        $res = $this->getTickers(['category' => $category, 'symbol' => $symbol]);
        $list = $res['result']['list'] ?? [];
        if (!$list) return null;
        $p = $list[0]['lastPrice'] ?? $list[0]['markPrice'] ?? $list[0]['bid1Price'] ?? null;
        return $p !== null ? (float)$p : null;
    }

    protected function qtyFromMargin(float $margin, float $price, float $leverage): float
    {
        return $margin * $leverage / $price;
    }

    public function placeOrder(
        string $type,
        string $symbol,
        string $execution,
        ?float $price,
        ?string $side,
        ?float $leverage,
        float $size,
        ?array $slTp = null,
        array $extra = []
    ): array
    {
        $type = strtolower(trim($type));
        $execution = strtolower(trim($execution));
        if (!in_array($type, ['spot', 'derivatives'], true)) {
            throw new \InvalidArgumentException('Order type must be spot or derivatives.');
        }
        if (!in_array($execution, ['market', 'limit', 'trigger'], true)) {
            throw new \InvalidArgumentException('Execution must be market, limit, or trigger.');
        }
        if ($size <= 0) {
            throw new \InvalidArgumentException('Order size must be greater than zero.');
        }
        if (($execution === 'limit' || $execution === 'trigger') && ($price === null || $price <= 0)) {
            throw new \InvalidArgumentException('Limit and trigger orders require a positive price.');
        }
        if ($leverage !== null && $leverage <= 0) {
            throw new \InvalidArgumentException('Leverage must be greater than zero.');
        }

        $side = $side === null ? 'Buy' : ucfirst(strtolower($side));
        if (!in_array($side, ['Buy', 'Sell'], true)) {
            throw new \InvalidArgumentException('Side must be Buy or Sell.');
        }

        $isSpot = $type === 'spot';
        $category = $isSpot ? 'spot' : 'linear';
        $orderType = $execution === 'market' ? 'Market' : 'Limit';
        $payload = ['category' => $category, 'symbol' => $symbol];
        if ($isSpot) {
            $payload['side'] = $side;
            $payload['orderType'] = $orderType;
            if ($orderType === 'Limit') $payload['price'] = (string)$price;
            $payload['qty'] = (string)$size;
            if ($orderType === 'Market' && $side === 'Buy') {
                $payload['marketUnit'] = $extra['marketUnit'] ?? 'quoteCoin';
            }
        } else {
            $payload['side'] = $side;
            $payload['orderType'] = $orderType;
            $entryPrice = $orderType === 'Limit' ? $price : $this->lastPrice($symbol, $category);
            if ($entryPrice === null || $entryPrice <= 0) {
                throw new \RuntimeException('Unable to determine a positive market price for the derivative order.');
            }
            if ($leverage !== null) $this->setLeverage($category, $symbol, $leverage, $side);
            $qty = $this->qtyFromMargin($size, $entryPrice, $leverage ?? 1.0);
            $payload['qty'] = (string)$qty;
            if ($orderType === 'Limit') $payload['price'] = (string)$price;
            $payload['positionIdx'] = 0;
        }
        if ($execution === 'trigger') {
            $payload['orderType'] = 'Market';
            $payload['triggerPrice'] = (string)$price;
            $triggerDir = $extra['triggerDirection'] ?? null;
            if ($triggerDir === null) {
                $currentPrice = $this->lastPrice($symbol, $category);
                if ($currentPrice === null || $currentPrice <= 0) {
                    throw new \RuntimeException('Unable to determine the current price for the trigger direction.');
                }
                $triggerDir = ($price > $currentPrice) ? 1 : 2;
            }
            $payload['triggerDirection'] = (int)$triggerDir;
            unset($extra['triggerDirection']);
        }
        if ($slTp && !$isSpot) {
            $mode = $slTp['type'] ?? 'absolute';
            $tp = $slTp['takeProfit'] ?? null;
            $sl = $slTp['stopLoss'] ?? null;
            $entry = isset($payload['price']) ? (float)$payload['price'] : ($this->lastPrice($symbol, $category) ?? 0.0);
            if ($mode === 'percent') {
                if ($tp !== null) $tp = ($side === 'Buy') ? $entry * (1 + (float)$tp) : $entry * (1 - (float)$tp);
                if ($sl !== null) $sl = ($side === 'Buy') ? $entry * (1 - (float)$sl) : $entry * (1 + (float)$sl);
            }
            if ($tp !== null) $payload['takeProfit'] = (string)$tp;
            if ($sl !== null) $payload['stopLoss'] = (string)$sl;
        }
        if ($extra) $payload = array_replace($payload, $extra);
        return $this->request('POST', '/v5/order/create', $payload);
    }

    public function requestDemoFunds(array $params): array
    {
        $this->assertDemoTrading();
        return $this->request('POST', '/v5/account/demo-apply-money', $params);
    }

    /**
     * Request or reduce the balance of one supported Demo Trading coin.
     */
    public function requestDemoFundsSimple(string $coin, string $amount, int $adjustType = 0): array
    {
        $coin = strtoupper(trim($coin));
        if (!in_array($coin, ['BTC', 'ETH', 'USDT', 'USDC'], true)) {
            throw new \InvalidArgumentException('Demo funds support BTC, ETH, USDT, and USDC.');
        }
        if (trim($amount) === '') {
            throw new \InvalidArgumentException('Demo fund amount cannot be empty.');
        }
        if (!in_array($adjustType, [0, 1], true)) {
            throw new \InvalidArgumentException('Demo fund adjustType must be 0 (add) or 1 (reduce).');
        }

        return $this->requestDemoFunds([
            'adjustType' => $adjustType,
            'utaDemoApplyMoney' => [[
                'coin' => $coin,
                'amountStr' => $amount,
            ]],
        ]);
    }

    public function createDemoAccount(): array
    {
        $this->assertProductionMainnet();
        return $this->request('POST', '/v5/user/create-demo-member');
    }

    public function createDemoApiKey(string $demoUid, array $params = []): array
    {
        $this->assertProductionMainnet();
        $params['subuid'] = $demoUid;
        return $this->request('POST', '/v5/user/create-sub-api', $params);
    }

    public function updateDemoApiKey(array $params): array
    {
        $this->assertProductionMainnet();
        return $this->request('POST', '/v5/user/update-sub-api', $params);
    }

    public function getApiKeyInfo(): array
    {
        return $this->request('GET', '/v5/user/query-api');
    }

    /**
     * Get information about the key currently used by the Demo Trading client.
     */
    public function getDemoApiKeyInfo(): array
    {
        $this->assertDemoTrading();
        return $this->getApiKeyInfo();
    }

    public function deleteDemoApiKey(array $params): array
    {
        $this->assertProductionMainnet();
        return $this->request('POST', '/v5/user/delete-sub-api', $params);
    }

    public function computeFee(string $type, float $volume, string $level = 'Non-VIP', string $liquidity = 'taker'): float
    {
        $type = strtolower($type) === 'spot' ? 'spot' : 'derivatives';
        $levelKey = $level;
        $rate = $this->fees[$type][$levelKey][$liquidity] ?? $this->fees[$type]['Non-VIP'][$liquidity] ?? 0.0;
        return $volume * $rate;
    }

    private function assertDemoTrading(): void
    {
        if (!$this->demoTrading) {
            throw new \LogicException('This operation requires a Demo Trading client.');
        }
    }

    private function assertProductionMainnet(): void
    {
        if ($this->baseUri() !== 'https://api.bybit.com') {
            throw new \LogicException('This operation requires a global production client (api.bybit.com).');
        }
    }
}
