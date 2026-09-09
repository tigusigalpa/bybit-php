<?php

declare(strict_types=1);

namespace GuzzleHttp {
    class RequestOptions
    {
        public const HEADERS = 'headers';
        public const QUERY = 'query';
        public const JSON = 'json';
        public const BODY = 'body';
        public const FORM_PARAMS = 'form_params';
        public const MULTIPART = 'multipart';
        public const HTTP_ERRORS = 'http_errors';
    }

    class FakeResponse
    {
        private int $statusCode;
        private string $reasonPhrase;
        private string $body;

        public function __construct(int $statusCode = 200, string $reasonPhrase = 'OK', string $body = '{"retCode":0,"retMsg":"OK","result":{}}')
        {
            $this->statusCode = $statusCode;
            $this->reasonPhrase = $reasonPhrase;
            $this->body = $body;
        }

        public function getStatusCode(): int
        {
            return $this->statusCode;
        }

        public function getReasonPhrase(): string
        {
            return $this->reasonPhrase;
        }

        public function getBody(): string
        {
            return $this->body;
        }
    }

    class Client
    {
        public array $requests = [];
        public array $responses = [];
        public FakeResponse $response;

        public function __construct(array $config = [])
        {
            $this->response = new FakeResponse();
        }

        public function request(string $method, string $path, array $options): FakeResponse
        {
            $this->requests[] = compact('method', 'path', 'options');
            return $this->responses === [] ? $this->response : array_shift($this->responses);
        }
    }
}

namespace WebSocket {
    class ConnectionException extends \RuntimeException
    {
    }

    class Client
    {
        public static array $instances = [];
        public string $url;
        public array $options;
        public array $messages = [];
        public array $incoming = [];
        public bool $closed = false;

        public function __construct(string $url, array $options = [])
        {
            $this->url = $url;
            $this->options = $options;
            self::$instances[] = $this;
        }

        public function text(string $message): void
        {
            $this->messages[] = $message;
        }

        public function receive(): ?string
        {
            if ($this->incoming === []) {
                throw new ConnectionException('Fake connection closed.');
            }

            return array_shift($this->incoming);
        }

        public function close(): void
        {
            $this->closed = true;
        }
    }
}

namespace Illuminate\Support {
    class ServiceProvider
    {
        protected $app;
        public static array $published = [];

        public function __construct($app = null)
        {
            $this->app = $app;
        }

        protected function mergeConfigFrom(string $path, string $key): void
        {
        }

        protected function publishes(array $paths, string $group = null): void
        {
            self::$published[$group ?? 'default'] = $paths;
        }
    }
}

namespace {
    require __DIR__ . '/../src/Exceptions/BybitHttpException.php';
    require __DIR__ . '/../src/BybitClient.php';
    require __DIR__ . '/../src/BybitWebSocket.php';
    require __DIR__ . '/../src/BybitTradFi.php';
    require __DIR__ . '/../src/BybitServiceProvider.php';

    use GuzzleHttp\Client as FakeHttpClient;
    use GuzzleHttp\FakeResponse;
    use GuzzleHttp\RequestOptions;
    use Tigusigalpa\ByBit\BybitClient;
    use Tigusigalpa\ByBit\BybitTradFi;
    use Tigusigalpa\ByBit\BybitWebSocket;
    use Tigusigalpa\ByBit\BybitServiceProvider;
    use Tigusigalpa\ByBit\Exceptions\BybitHttpException;
    use WebSocket\Client as FakeWebSocketClient;

    class FastBybitClient extends BybitClient
    {
        protected function rateLimit(string $method): void
        {
        }
    }

    class FakeLaravelConfig
    {
        private array $values;

        public function __construct(array $values)
        {
            $this->values = $values;
        }

        public function get(string $key, $default = null)
        {
            return $this->values[$key] ?? $default;
        }
    }

    class FakeLaravelApp implements \ArrayAccess
    {
        private FakeLaravelConfig $config;
        public array $bindings = [];
        public array $aliases = [];

        public function __construct(array $config)
        {
            $this->config = new FakeLaravelConfig(['bybit' => $config]);
        }

        public function singleton(string $abstract, callable $factory): void
        {
            $this->bindings[$abstract] = $factory;
        }

        public function alias(string $abstract, string $alias): void
        {
            $this->aliases[$alias] = $abstract;
        }

        public function configPath(string $path): string
        {
            return '/fake-config/' . $path;
        }

        public function offsetExists($offset): bool
        {
            return $offset === 'config';
        }

        public function offsetGet($offset)
        {
            return $offset === 'config' ? $this->config : null;
        }

        public function offsetSet($offset, $value): void
        {
            throw new \LogicException('Fake Laravel app is read-only.');
        }

        public function offsetUnset($offset): void
        {
            throw new \LogicException('Fake Laravel app is read-only.');
        }
    }

    function assertSameValue($expected, $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new \RuntimeException($message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true));
        }
    }

    function assertThrows(string $exception, callable $callback, string $message): void
    {
        try {
            $callback();
        } catch (\Throwable $error) {
            if ($error instanceof $exception) {
                return;
            }
            throw new \RuntimeException($message . ': unexpected ' . get_class($error));
        }
        throw new \RuntimeException($message . ': exception was not thrown');
    }

    function assertEndpointContracts(BybitClient $client, FakeHttpClient $http, array $contracts): void
    {
        foreach ($contracts as $contract) {
            [$method, $httpMethod, $path, $arguments] = $contract;
            $requestIndex = count($http->requests);
            call_user_func_array([$client, $method], $arguments);
            $request = $http->requests[$requestIndex];

            assertSameValue($httpMethod, $request['method'], $method . ' must use the expected HTTP method');

            $params = $arguments[0] ?? [];
            if ($httpMethod === 'GET' && is_array($params) && $params !== []) {
                ksort($params);
                $path .= '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
            }
            assertSameValue($path, $request['path'], $method . ' must use the expected V5 route');

            if ($httpMethod !== 'GET' && is_array($params)) {
                assertSameValue($params, json_decode($request['options'][RequestOptions::BODY], true), $method . ' must preserve its V5 payload');
            }
        }
    }

    $http = new FakeHttpClient();
    $client = new BybitClient('test-key', 'test-secret', false, 'tr', 5000, 'hmac', null, $http);
    $client->request('POST', '/v5/example', [
        'callbackUrl' => 'https://example.test/a/b',
        'values' => ['one', 'two'],
    ], [
        RequestOptions::HEADERS => ['X-Correlation-ID' => 'request-1', 'X-BAPI-SIGN' => 'invalid'],
        RequestOptions::JSON => ['must-not' => 'replace-the-signed-body'],
    ]);

    $post = $http->requests[0];
    $body = '{"callbackUrl":"https://example.test/a/b","values":["one","two"]}';
    assertSameValue($body, $post['options'][RequestOptions::BODY], 'POST body must be the exact signed JSON');
    assertSameValue(false, array_key_exists(RequestOptions::JSON, $post['options']), 'Guzzle JSON option must not replace the signed body');
    assertSameValue('request-1', $post['options'][RequestOptions::HEADERS]['X-Correlation-ID'], 'Safe custom request headers must be preserved');
    $headers = $post['options'][RequestOptions::HEADERS];
    $expectedSignature = hash_hmac(
        'sha256',
        $headers['X-BAPI-TIMESTAMP'] . 'test-key' . $headers['X-BAPI-RECV-WINDOW'] . $body,
        'test-secret'
    );
    assertSameValue($expectedSignature, $headers['X-BAPI-SIGN'], 'POST signature must match the wire body');
    assertSameValue(false, array_key_exists('X-Referer', $headers), 'Non-broker requests must not send X-Referer');
    assertSameValue('https://api.bybit.tr', $client->endpoint(), 'Turkey must use the current REST endpoint');

    $brokerHttp = new FakeHttpClient();
    $brokerClient = new BybitClient('key', 'secret', false, 'global', 5000, 'hmac', null, $brokerHttp, null, false, false, 'broker-123');
    $brokerClient->getServerTime();
    assertSameValue('broker-123', $brokerHttp->requests[0]['options'][RequestOptions::HEADERS]['X-Referer'], 'Broker requests must send the configured broker ID');

    $http = new FakeHttpClient();
    $client = new BybitClient('key', 'secret', false, 'global', 5000, 'hmac', null, $http);
    $client->request('GET', '/v5/example', ['symbol' => 'BTCUSDT', 'category' => 'linear']);
    $get = $http->requests[0];
    assertSameValue('/v5/example?category=linear&symbol=BTCUSDT', $get['path'], 'GET parameters must be canonically ordered on the wire');
    $getHeaders = $get['options'][RequestOptions::HEADERS];
    assertSameValue(
        hash_hmac('sha256', $getHeaders['X-BAPI-TIMESTAMP'] . 'key' . $getHeaders['X-BAPI-RECV-WINDOW'] . 'category=linear&symbol=BTCUSDT', 'secret'),
        $getHeaders['X-BAPI-SIGN'],
        'GET signature must match the wire query string'
    );

    $http = new FakeHttpClient();
    $client = new BybitClient('key', 'secret', false, 'global', 5000, 'hmac', null, $http);
    $client->getTransferableAmount(['accountType' => 'UNIFIED', 'coinName' => 'USDT']);
    assertSameValue('/v5/account/transferable-amount?accountType=UNIFIED&coinName=USDT', $http->requests[0]['path'], 'Transferable amount endpoint must use the current V5 route');
    $client->getAccountInstrumentsInfo(['category' => 'linear']);
    assertSameValue('/v5/account/instruments-info?category=linear', $http->requests[1]['path'], 'Account instruments endpoint must use the current V5 route');

    $http->response = new FakeResponse(429, 'Too Many Requests', '{"retCode":10006}');
    assertThrows(BybitHttpException::class, static function () use ($client): void {
        $client->getServerTime();
    }, 'HTTP errors must produce a typed exception');

    assertThrows(\InvalidArgumentException::class, static function (): void {
        new BybitClient('key', 'secret', true, 'global', 5000, 'hmac', null, null, null, true);
    }, 'Testnet and Demo Trading must be mutually exclusive');

    $demoHttp = new FakeHttpClient();
    $demoClient = BybitClient::demo('key', 'secret', 5000, 'hmac', null, $demoHttp);
    assertSameValue('https://api-demo.bybit.com', $demoClient->endpoint(), 'Demo factory must use the isolated Demo Trading endpoint');
    $demoClient->requestDemoFundsSimple('usdt', '10000');
    assertSameValue('/v5/account/demo-apply-money', $demoHttp->requests[0]['path'], 'Demo funds must use the Demo Trading endpoint');
    assertSameValue(
        ['adjustType' => 0, 'utaDemoApplyMoney' => [['coin' => 'USDT', 'amountStr' => '10000']]],
        json_decode($demoHttp->requests[0]['options'][RequestOptions::BODY], true),
        'Simple demo funds helper must build Bybit\'s expected payload'
    );
    $demoClient->getDemoApiKeyInfo();
    assertSameValue('/v5/user/query-api', $demoHttp->requests[1]['path'], 'Demo API key info must use the Demo Trading key');
    assertThrows(\InvalidArgumentException::class, static function () use ($demoClient): void {
        $demoClient->requestDemoFundsSimple('XRP', '1');
    }, 'Unsupported demo fund coins must be rejected before sending a request');
    assertThrows(\LogicException::class, static function (): void {
        (new BybitClient('key', 'secret'))->getDemoApiKeyInfo();
    }, 'Demo API key info must reject a production client');
    assertSameValue('https://api-testnet.bybit.com', (new BybitClient('key', 'secret', true, 'jp'))->endpoint(), 'Testnet must use Bybit\'s standard Testnet REST domain');

    $orderClient = new BybitClient('key', 'secret', false, 'global', 5000, 'hmac', null, new FakeHttpClient());
    assertThrows(\InvalidArgumentException::class, static function () use ($orderClient): void {
        $orderClient->placeOrder('spot', 'BTCUSDT', 'limit', null, 'Buy', null, 1);
    }, 'High-level limit orders must require a price');
    assertThrows(\RuntimeException::class, static function () use ($orderClient): void {
        $orderClient->placeOrder('derivatives', 'BTCUSDT', 'market', null, 'Buy', null, 100);
    }, 'High-level derivative market orders must not submit when market price is unavailable');

    assertSameValue('wss://stream-demo.bybit.com/v5/private', (new BybitWebSocket('key', 'secret', false, 'global', true, 'linear', true))->endpoint(), 'Demo private streams must use the demo endpoint');
    assertSameValue('wss://stream.bybit.com/v5/public/linear', (new BybitWebSocket(null, null, false, 'global', false, 'linear', true))->endpoint(), 'Demo public data must use mainnet stream');
    assertSameValue('wss://stream-testnet.bybit.com/v5/public/linear', (new BybitWebSocket(null, null, true, 'jp', false, 'linear'))->endpoint(), 'Testnet must use Bybit\'s standard Testnet WebSocket domain');
    assertSameValue('wss://stream.bybit.tr/v5/public/spread', (new BybitWebSocket(null, null, false, 'tr', false, 'spread'))->endpoint(), 'Turkey and spread stream routes must be current');

    assertSameValue(true, BybitTradFi::isTradFiSymbol('XAUUSD'), 'Gold must be detected as TradFi');
    assertSameValue('GOOGLUSDT', BybitTradFi::US_STOCKS[3], 'GOOGL stock symbol must be valid');

    $tradFiHttp = new FakeHttpClient();
    $tradFiHttp->responses = [
        new FakeResponse(200, 'OK', '{"retCode":0,"retMsg":"OK","result":{"list":[{"symbol":"AAPLUSDT"}]}}'),
        new FakeResponse(200, 'OK', '{"retCode":0,"retMsg":"OK","result":{"list":[{"symbol":"GOOGLUSDT"}]}}'),
    ];
    $tradFi = new BybitTradFi(new BybitClient('key', 'secret', false, 'global', 5000, 'hmac', null, $tradFiHttp));
    $stockTickers = $tradFi->getTickers(['AAPLUSDT', 'GOOGLUSDT']);
    assertSameValue(['AAPLUSDT', 'GOOGLUSDT'], array_column($stockTickers['result']['list'], 'symbol'), 'TradFi multi-ticker helper must return only the requested symbols');

    $instrumentsHttp = new FakeHttpClient();
    $instrumentsHttp->responses = [
        new FakeResponse(200, 'OK', '{"retCode":0,"retMsg":"OK","result":{"list":[{"symbol":"XAUUSD"}],"nextPageCursor":"next"}}'),
        new FakeResponse(200, 'OK', '{"retCode":0,"retMsg":"OK","result":{"list":[{"symbol":"EURUSD"}],"nextPageCursor":""}}'),
    ];
    $instruments = (new BybitTradFi(new BybitClient('key', 'secret', false, 'global', 5000, 'hmac', null, $instrumentsHttp)))->getInstruments();
    assertSameValue(['XAUUSD', 'EURUSD'], array_column($instruments['result']['list'], 'symbol'), 'TradFi instruments helper must combine all pages');

    // Contract-test every public V5 REST wrapper. These assertions protect routes,
    // HTTP verbs, canonical GET arguments, and POST payload forwarding.
    $contractHttp = new FakeHttpClient();
    $contractClient = new FastBybitClient('key', 'secret', false, 'global', 5000, 'hmac', null, $contractHttp);
    $marketParams = ['symbol' => 'BTCUSDT', 'category' => 'linear'];
    $accountParams = ['accountType' => 'UNIFIED', 'coin' => 'USDT'];
    assertEndpointContracts($contractClient, $contractHttp, [
        ['getServerTime', 'GET', '/v5/market/time', []],
        ['getTickers', 'GET', '/v5/market/tickers', [$marketParams]],
        ['getKline', 'GET', '/v5/market/kline', [$marketParams]],
        ['getOrderbook', 'GET', '/v5/market/orderbook', [$marketParams]],
        ['getRPIOrderbook', 'GET', '/v5/market/rpi-orderbook', [$marketParams]],
        ['getOpenInterest', 'GET', '/v5/market/open-interest', [$marketParams]],
        ['getRecentTrades', 'GET', '/v5/market/recent-trade', [$marketParams]],
        ['getFundingRateHistory', 'GET', '/v5/market/funding/history', [$marketParams]],
        ['getHistoricalVolatility', 'GET', '/v5/market/historical-volatility', [$marketParams]],
        ['getInsurancePool', 'GET', '/v5/market/insurance', [$marketParams]],
        ['getRiskLimit', 'GET', '/v5/market/risk-limit', [$marketParams]],
        ['createOrder', 'POST', '/v5/order/create', [$marketParams]],
        ['batchCreateOrders', 'POST', '/v5/order/create-batch', [$marketParams]],
        ['batchAmendOrders', 'POST', '/v5/order/amend-batch', [$marketParams]],
        ['batchCancelOrders', 'POST', '/v5/order/cancel-batch', [$marketParams]],
        ['getOpenOrders', 'GET', '/v5/order/realtime', [$marketParams]],
        ['cancelOrder', 'POST', '/v5/order/cancel', [$marketParams]],
        ['amendOrder', 'POST', '/v5/order/amend', [$marketParams]],
        ['cancelAllOrders', 'POST', '/v5/order/cancel-all', [$marketParams]],
        ['getHistoryOrders', 'GET', '/v5/order/history', [$marketParams]],
        ['getTradeHistory', 'GET', '/v5/execution/list', [$marketParams]],
        ['getWalletBalance', 'GET', '/v5/account/wallet-balance', [$accountParams]],
        ['getTransferableAmount', 'GET', '/v5/account/transferable-amount', [$accountParams]],
        ['getTransferableCoins', 'GET', '/v5/asset/transfer/query-transfer-coin-list', [$accountParams]],
        ['getTransactionLog', 'GET', '/v5/account/transaction-log', [$accountParams]],
        ['getAccountInfo', 'GET', '/v5/account/info', []],
        ['getAccountInstrumentsInfo', 'GET', '/v5/account/instruments-info', [$marketParams]],
        ['getBorrowHistory', 'GET', '/v5/account/borrow-history', [$accountParams]],
        ['setCollateralCoin', 'POST', '/v5/account/set-collateral-switch', [$accountParams]],
        ['getCollateralInfo', 'GET', '/v5/account/collateral-info', [$accountParams]],
        ['getCoinGreeks', 'GET', '/v5/asset/coin-greeks', [$accountParams]],
        ['setMarginMode', 'POST', '/v5/account/set-margin-mode', [$accountParams]],
        ['setSpotHedging', 'POST', '/v5/account/set-hedging-mode', [$accountParams]],
        ['getPositions', 'GET', '/v5/position/list', [$marketParams]],
        ['switchPositionMode', 'POST', '/v5/position/switch-mode', [$marketParams]],
        ['setTradingStop', 'POST', '/v5/position/trading-stop', [$marketParams]],
        ['setAutoAddMargin', 'POST', '/v5/position/set-auto-add-margin', [$marketParams]],
        ['addOrReduceMargin', 'POST', '/v5/position/add-margin', [$marketParams]],
        ['getClosedPnL', 'GET', '/v5/position/closed-pnl', [$marketParams]],
        ['getClosedOptionsPositions', 'GET', '/v5/position/close-position', [$marketParams]],
        ['movePosition', 'POST', '/v5/position/move-positions', [$marketParams]],
        ['getMovePositionHistory', 'GET', '/v5/position/move-history', [$marketParams]],
        ['confirmNewRiskLimit', 'POST', '/v5/position/confirm-pending-mmr', [$marketParams]],
        ['getDeliveryRecord', 'GET', '/v5/asset/delivery-record', [$accountParams]],
        ['getUSDCSettlement', 'GET', '/v5/asset/settlement-record', [$accountParams]],
        ['toggleMarginTrade', 'POST', '/v5/spot-margin-trade/switch-mode', [$accountParams]],
        ['setSpotMarginLeverage', 'POST', '/v5/spot-margin-trade/set-leverage', [$accountParams]],
        ['getSpotMarginStatus', 'GET', '/v5/spot-margin-uta/status', [$accountParams]],
    ]);

    $leverageIndex = count($contractHttp->requests);
    $contractClient->setLeverage('linear', 'BTCUSDT', 3.5, 'Buy');
    assertSameValue(['category' => 'linear', 'symbol' => 'BTCUSDT', 'buyLeverage' => '3.5'], json_decode($contractHttp->requests[$leverageIndex]['options'][RequestOptions::BODY], true), 'Set leverage must target only the requested Buy side');
    assertThrows(\InvalidArgumentException::class, static function () use ($contractClient): void {
        $contractClient->setLeverage('linear', 'BTCUSDT', 0);
    }, 'Zero leverage must be rejected');

    $mainHttp = new FakeHttpClient();
    $mainClient = new FastBybitClient('main-key', 'main-secret', false, 'global', 5000, 'hmac', null, $mainHttp);
    $mainClient->createDemoAccount();
    $mainClient->createDemoApiKey('123456', ['readOnly' => 1, 'permissions' => ['Spot' => ['SpotTrade']]]);
    $mainClient->updateDemoApiKey(['apiKey' => 'demo-key', 'readOnly' => 0]);
    $mainClient->deleteDemoApiKey(['apiKey' => 'demo-key']);
    assertSameValue(['/v5/user/create-demo-member', '/v5/user/create-sub-api', '/v5/user/update-sub-api', '/v5/user/delete-sub-api'], array_column($mainHttp->requests, 'path'), 'Demo account lifecycle must use production-only V5 routes');
    assertSameValue('123456', json_decode($mainHttp->requests[1]['options'][RequestOptions::BODY], true)['subuid'], 'Create demo API key must include the Demo account UID');
    assertThrows(\LogicException::class, static function (): void {
        (new FastBybitClient('key', 'secret', true))->createDemoAccount();
    }, 'Demo account management must reject Testnet clients');

    $spotOrderHttp = new FakeHttpClient();
    $spotOrderClient = new FastBybitClient('key', 'secret', false, 'global', 5000, 'hmac', null, $spotOrderHttp);
    $spotOrderClient->placeOrder('spot', 'BTCUSDT', 'market', null, 'buy', null, 25.0);
    assertSameValue(
        ['category' => 'spot', 'symbol' => 'BTCUSDT', 'side' => 'Buy', 'orderType' => 'Market', 'qty' => '25', 'marketUnit' => 'quoteCoin'],
        json_decode($spotOrderHttp->requests[0]['options'][RequestOptions::BODY], true),
        'Spot market buy helper must submit quote-coin quantity'
    );

    $derivativeHttp = new FakeHttpClient();
    $derivativeHttp->responses = [
        new FakeResponse(200, 'OK', '{"retCode":0,"result":{"list":[{"lastPrice":"100"}]}}'),
        new FakeResponse(),
        new FakeResponse(),
    ];
    $derivativeClient = new FastBybitClient('key', 'secret', false, 'global', 5000, 'hmac', null, $derivativeHttp);
    $derivativeClient->placeOrder('derivatives', 'BTCUSDT', 'market', null, 'Sell', 2.0, 100.0);
    assertSameValue('/v5/market/tickers?category=linear&symbol=BTCUSDT', $derivativeHttp->requests[0]['path'], 'Derivative market helper must price the order from the current ticker');
    assertSameValue('/v5/position/set-leverage', $derivativeHttp->requests[1]['path'], 'Derivative market helper must set the requested leverage');
    assertSameValue(['category' => 'linear', 'symbol' => 'BTCUSDT', 'side' => 'Sell', 'orderType' => 'Market', 'qty' => '2', 'positionIdx' => 0], json_decode($derivativeHttp->requests[2]['options'][RequestOptions::BODY], true), 'Derivative market helper must calculate the order quantity from margin and leverage');

    $businessErrorHttp = new FakeHttpClient();
    $businessErrorHttp->response = new FakeResponse(200, 'OK', '{"retCode":10001,"retMsg":"Parameter error"}');
    $businessErrorClient = new FastBybitClient('key', 'secret', false, 'global', 5000, 'hmac', null, $businessErrorHttp, null, false, true);
    assertThrows(\RuntimeException::class, static function () use ($businessErrorClient): void {
        $businessErrorClient->getServerTime();
    }, 'Configured business-error throwing must reject non-zero retCode responses');

    // WebSocket tests use the fake transport above, so they test auth and topic
    // construction without opening a real connection.
    FakeWebSocketClient::$instances = [];
    $privateWs = new BybitWebSocket('ws-key', 'ws-secret', false, 'global', true, 'linear', true);
    $privateWs->connect();
    $privateTransport = FakeWebSocketClient::$instances[0];
    $auth = json_decode($privateTransport->messages[0], true);
    assertSameValue('auth', $auth['op'], 'Private WebSocket must authenticate after connecting');
    assertSameValue('ws-key', $auth['args'][0], 'WebSocket auth must send the configured key');
    assertSameValue(hash_hmac('sha256', 'GET/realtime' . $auth['args'][1], 'ws-secret'), $auth['args'][2], 'WebSocket HMAC auth must sign the Bybit realtime payload');
    $privateWs->subscribePosition();
    $privateWs->subscribeOrder();
    $privateWs->subscribeExecution();
    $privateWs->subscribeWallet();
    $privateWs->subscribeGreeks();
    assertSameValue(['position', 'order', 'execution', 'wallet', 'greeks'], $privateWs->getSubscriptions(), 'Private WebSocket helpers must register all Demo Trading topics');
    assertThrows(\LogicException::class, static function () use ($privateWs): void {
        $privateWs->setPrivate(false);
    }, 'Privacy mode must not change while a WebSocket is connected');
    $privateWs->close();
    assertSameValue(true, $privateTransport->closed, 'Closing WebSocket must close the underlying transport');
    assertSameValue(false, $privateWs->isConnected(), 'Closed WebSocket must report a disconnected state');

    FakeWebSocketClient::$instances = [];
    $publicWs = new BybitWebSocket();
    $messages = [];
    $publicWs->onMessage(static function (array $message) use (&$messages): void {
        $messages[] = $message;
    });
    $publicWs->connect();
    $publicTransport = FakeWebSocketClient::$instances[0];
    $publicTransport->incoming = ['{"op":"ping","req_id":"heartbeat-1"}'];
    $publicWs->listen();
    assertSameValue(['op' => 'pong', 'req_id' => 'heartbeat-1'], json_decode($publicTransport->messages[0], true), 'WebSocket listener must answer server pings with the matching request ID');
    assertSameValue(true, $messages[1]['error'], 'WebSocket listener must report a closed transport through its callback');

    if (function_exists('openssl_pkey_new')) {
        $keyPair = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $privateKey = '';
        if ($keyPair !== false && @openssl_pkey_export($keyPair, $privateKey)) {
            $publicKey = openssl_pkey_get_details($keyPair)['key'];

            FakeWebSocketClient::$instances = [];
            $rsaWs = new BybitWebSocket('rsa-key', 'unused', false, 'global', true, 'linear', true, 'rsa', $privateKey);
            $rsaWs->connect();
            $rsaAuth = json_decode(FakeWebSocketClient::$instances[0]->messages[0], true);
            assertSameValue(1, openssl_verify('GET/realtime' . $rsaAuth['args'][1], base64_decode($rsaAuth['args'][2]), $publicKey, OPENSSL_ALGO_SHA256), 'WebSocket RSA auth must produce a verifiable signature');
        }
    }

    $laravelApp = new FakeLaravelApp([
        'api_key' => 'laravel-demo-key',
        'api_secret' => 'laravel-demo-secret',
        'testnet' => false,
        'demo_trading' => true,
        'region' => 'tr',
        'recv_window' => 6000,
        'signature' => 'hmac',
        'rsa_private_key' => null,
        'broker_id' => 'laravel-broker',
        'throw_on_error' => true,
        'websocket_category' => 'linear',
        'websocket_private' => true,
    ]);
    $serviceProvider = new BybitServiceProvider($laravelApp);
    $serviceProvider->register();
    $serviceProvider->boot();
    $laravelClient = $laravelApp->bindings[BybitClient::class]($laravelApp);
    $laravelWebSocket = $laravelApp->bindings[BybitWebSocket::class]($laravelApp);
    assertSameValue('https://api-demo.bybit.com', $laravelClient->endpoint(), 'Laravel Demo Trading config must override the production region');
    assertSameValue('wss://stream-demo.bybit.com/v5/private', $laravelWebSocket->endpoint(), 'Laravel private Demo Trading WebSocket config must use the demo endpoint');
    assertSameValue(BybitClient::class, $laravelApp->aliases['bybit'], 'Laravel provider must register the bybit alias');
    assertSameValue(BybitWebSocket::class, $laravelApp->aliases['bybit.websocket'], 'Laravel provider must register the WebSocket alias');
    assertSameValue('/fake-config/bybit.php', array_values(\Illuminate\Support\ServiceProvider::$published['bybit-config'])[0], 'Laravel provider must publish the package configuration');

    echo "All bybit-php tests passed.\n";
}
