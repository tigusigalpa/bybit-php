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

namespace {
    require __DIR__ . '/../src/Exceptions/BybitHttpException.php';
    require __DIR__ . '/../src/BybitClient.php';
    require __DIR__ . '/../src/BybitWebSocket.php';
    require __DIR__ . '/../src/BybitTradFi.php';

    use GuzzleHttp\Client as FakeHttpClient;
    use GuzzleHttp\FakeResponse;
    use GuzzleHttp\RequestOptions;
    use Tigusigalpa\ByBit\BybitClient;
    use Tigusigalpa\ByBit\BybitTradFi;
    use Tigusigalpa\ByBit\BybitWebSocket;
    use Tigusigalpa\ByBit\Exceptions\BybitHttpException;

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

    $orderClient = new BybitClient('key', 'secret', false, 'global', 5000, 'hmac', null, new FakeHttpClient());
    assertThrows(\InvalidArgumentException::class, static function () use ($orderClient): void {
        $orderClient->placeOrder('spot', 'BTCUSDT', 'limit', null, 'Buy', null, 1);
    }, 'High-level limit orders must require a price');
    assertThrows(\RuntimeException::class, static function () use ($orderClient): void {
        $orderClient->placeOrder('derivatives', 'BTCUSDT', 'market', null, 'Buy', null, 100);
    }, 'High-level derivative market orders must not submit when market price is unavailable');

    assertSameValue('wss://stream-demo.bybit.com/v5/private', (new BybitWebSocket('key', 'secret', false, 'global', true, 'linear', true))->endpoint(), 'Demo private streams must use the demo endpoint');
    assertSameValue('wss://stream.bybit.com/v5/public/linear', (new BybitWebSocket(null, null, false, 'global', false, 'linear', true))->endpoint(), 'Demo public data must use mainnet stream');
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

    echo "All bybit-php tests passed.\n";
}
