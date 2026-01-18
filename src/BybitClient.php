<?php
namespace Tigusigalpa\ByBit;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

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

    public function __construct(string $apiKey, ?string $apiSecret, bool $testnet = false, string $region = 'global', int $recvWindow = 5000, string $signature = 'hmac', ?string $rsaPrivateKey = null, ?Client $http = null, ?array $fees = null, bool $demoTrading = false)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->testnet = $testnet;
        $this->demoTrading = $demoTrading;
        $this->region = $region;
        $this->recvWindow = $recvWindow;
        $this->signature = $signature;
        $this->rsaPrivateKey = $rsaPrivateKey;
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
    }

    public function baseUri(): string
    {
        if ($this->demoTrading) return 'https://api-demo.bybit.com';
        if ($this->testnet) return 'https://api-testnet.bybit.com';
        switch (strtolower($this->region)) {
            case 'nl': return 'https://api.bybit.nl';
            case 'tr': return 'https://api.bybit-tr.com';
            case 'kz': return 'https://api.bybit.kz';
            case 'ge': return 'https://api.bybitgeorgia.ge';
            case 'ae': return 'https://api.bybit.ae';
            default: return 'https://api.bybit.com';
        }
    }

    protected function timestamp(): string
    {
        return (string)floor(microtime(true) * 1000);
    }

    protected function signString(string $string): string
    {
        if ($this->signature === 'rsa' && $this->rsaPrivateKey) {
            $key = openssl_pkey_get_private($this->rsaPrivateKey);
            openssl_sign($string, $signature, $key, OPENSSL_ALGO_SHA256);
            return base64_encode($signature);
        }
        return strtolower(hash_hmac('sha256', $string, (string)$this->apiSecret));
    }

    protected function headers(string $method, string $path, array $params): array
    {
        $ts = $this->timestamp();
        $recv = (string)$this->recvWindow;
        if (strtoupper($method) === 'GET') {
            $query = $this->buildQuery($params);
            $toSign = $ts . $this->apiKey . $recv . $query;
        } else {
            $body = $this->jsonBody($params);
            $toSign = $ts . $this->apiKey . $recv . $body;
        }
        $sign = $this->signString($toSign);
        $headers = [
            'X-BAPI-API-KEY' => $this->apiKey,
            'X-BAPI-TIMESTAMP' => $ts,
            'X-BAPI-RECV-WINDOW' => $recv,
            'X-BAPI-SIGN' => $sign,
        ];
        if ($this->signature === 'hmac') {
            $headers['X-BAPI-SIGN-TYPE'] = '2';
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
        return $params ? json_encode($params, JSON_UNESCAPED_SLASHES) : '{}';
    }

    public function request(string $method, string $path, array $params = [], array $options = []): array
    {
        $method = strtoupper($method);
        $headers = $this->headers($method, $path, $params);
        $opts = [RequestOptions::HEADERS => $headers];
        if ($method === 'GET') {
            if ($params) $opts[RequestOptions::QUERY] = $params;
        } else {
            $opts[RequestOptions::JSON] = $params ?: new \stdClass();
        }
        if ($options) $opts = array_replace_recursive($opts, $options);
        $res = $this->http->request($method, $path, $opts);
        $body = (string)$res->getBody();
        $data = json_decode($body, true);
        return is_array($data) ? $data : ['raw' => $body];
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

    public function getWalletBalance(array $params): array
    {
        return $this->request('GET', '/v5/account/wallet-balance', $params);
    }

    public function getTransferableAmount(array $params): array
    {
        return $this->request('GET', '/v5/account/transferable-coin', $params);
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
        return $this->request('GET', '/v5/account/contract-info', $params);
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
        $payload = ['category' => $category, 'symbol' => $symbol];
        if ($side === 'Buy') {
            $payload['buyLeverage'] = (string)$leverage;
        } elseif ($side === 'Sell') {
            $payload['sellLeverage'] = (string)$leverage;
        } else {
            $payload['buyLeverage'] = (string)$leverage;
            $payload['sellLeverage'] = (string)$leverage;
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
        $isSpot = strtolower($type) === 'spot';
        $category = $isSpot ? 'spot' : 'linear';
        $orderType = strtolower($execution) === 'market' ? 'Market' : 'Limit';
        $payload = ['category' => $category, 'symbol' => $symbol];
        if ($isSpot) {
            $payload['side'] = $side ?: 'Buy';
            $payload['orderType'] = $orderType;
            if ($orderType === 'Limit' && $price) $payload['price'] = (string)$price;
            $payload['qty'] = (string)$size;
        } else {
            if (!$side) $side = 'Buy';
            $payload['side'] = $side;
            $payload['orderType'] = $orderType;
            $entryPrice = $orderType === 'Limit' && $price ? $price : ($this->lastPrice($symbol, $category) ?? ($price ?? 0.0));
            if ($leverage && $leverage > 0) $this->setLeverage($category, $symbol, $leverage, $side);
            $qty = $orderType === 'Market' && !$price ? $this->qtyFromMargin($size, max($entryPrice, 0.0000001), max($leverage ?? 1.0, 1.0)) : $this->qtyFromMargin($size, max($entryPrice, 0.0000001), max($leverage ?? 1.0, 1.0));
            $payload['qty'] = (string)$qty;
            if ($orderType === 'Limit' && $price) $payload['price'] = (string)$price;
            $payload['positionIdx'] = 0;
        }
        if (strtolower($execution) === 'trigger') {
            $payload['orderType'] = 'Market';
            if ($price) $payload['triggerPrice'] = (string)$price;
            $payload['triggerDirection'] = ($side === 'Buy') ? 1 : 2;
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
        return $this->request('POST', '/v5/account/demo-apply-money', $params);
    }

    public function createDemoAccount(): array
    {
        return $this->request('POST', '/v5/user/create-demo-member');
    }

    public function computeFee(string $type, float $volume, string $level = 'Non-VIP', string $liquidity = 'taker'): float
    {
        $type = strtolower($type) === 'spot' ? 'spot' : 'derivatives';
        $levelKey = $level;
        $rate = $this->fees[$type][$levelKey][$liquidity] ?? $this->fees[$type]['Non-VIP'][$liquidity] ?? 0.0;
        return $volume * $rate;
    }
}