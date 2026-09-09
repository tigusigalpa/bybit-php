<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Tests\Support\FixedClockBybitClient;
use Tigusigalpa\ByBit\BybitClient;
use Tigusigalpa\ByBit\Exceptions\BybitHttpException;

use function Tests\Support\mockHttpClient;

it('signs the canonical GET query that is sent to Bybit', function (): void {
    $history = [];
    $client = new FixedClockBybitClient(
        'api-key',
        'api-secret',
        false,
        'global',
        5000,
        'hmac',
        null,
        mockHttpClient([new Response(200, [], '{"retCode":0,"result":{"list":[]}}')], $history)
    );

    expect($client->getTickers(['symbol' => 'BTCUSDT', 'category' => 'linear']))
        ->toBe(['retCode' => 0, 'result' => ['list' => []]]);

    $request = $history[0]['request'];
    $query = 'category=linear&symbol=BTCUSDT';

    expect($request->getMethod())->toBe('GET')
        ->and($request->getRequestTarget())->toBe('/v5/market/tickers?' . $query)
        ->and($request->getHeaderLine('X-BAPI-API-KEY'))->toBe('api-key')
        ->and($request->getHeaderLine('X-BAPI-SIGN'))->toBe(
            hash_hmac('sha256', '1700000000000api-key5000' . $query, 'api-secret')
        );
});

it('uses a JSON body and does not allow callers to override authentication headers', function (): void {
    $history = [];
    $client = new FixedClockBybitClient(
        'api-key',
        'api-secret',
        false,
        'global',
        5000,
        'hmac',
        null,
        mockHttpClient([new Response(200, [], '{"retCode":0}')], $history)
    );

    $client->request('POST', '/v5/order/create', ['symbol' => 'BTCUSDT', 'category' => 'spot'], [
        RequestOptions::HEADERS => [
            'X-BAPI-API-KEY' => 'untrusted-key',
            'X-Correlation-ID' => 'request-42',
        ],
    ]);

    $request = $history[0]['request'];
    expect($request->getRequestTarget())->toBe('/v5/order/create')
        ->and((string) $request->getBody())->toBe('{"symbol":"BTCUSDT","category":"spot"}')
        ->and($request->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and($request->getHeaderLine('X-BAPI-API-KEY'))->toBe('api-key')
        ->and($request->getHeaderLine('X-Correlation-ID'))->toBe('request-42');
});

it('reports HTTP failures and optional Bybit business failures', function (): void {
    $history = [];
    $httpFailure = new FixedClockBybitClient(
        'key',
        'secret',
        false,
        'global',
        5000,
        'hmac',
        null,
        mockHttpClient([new Response(429, [], '{"retCode":10006}')], $history)
    );

    expect(fn (): array => $httpFailure->getServerTime())
        ->toThrow(BybitHttpException::class, 'Bybit API request failed');

    $history = [];
    $businessFailure = new FixedClockBybitClient(
        'key',
        'secret',
        false,
        'global',
        5000,
        'hmac',
        null,
        mockHttpClient([new Response(200, [], '{"retCode":10001,"retMsg":"Parameter error"}')], $history),
        null,
        false,
        true
    );

    expect(fn (): array => $businessFailure->getServerTime())
        ->toThrow(RuntimeException::class, 'Parameter error');
});

it('keeps Demo Trading isolated and builds its funding payload', function (): void {
    $history = [];
    $client = BybitClient::demo(
        'demo-key',
        'demo-secret',
        5000,
        'hmac',
        null,
        mockHttpClient([new Response(200, [], '{"retCode":0}')], $history)
    );

    $client->requestDemoFundsSimple('usdt', '10000');

    $request = $history[0]['request'];
    expect($client->endpoint())->toBe('https://api-demo.bybit.com')
        ->and($request->getRequestTarget())->toBe('/v5/account/demo-apply-money')
        ->and(json_decode((string) $request->getBody(), true))->toBe([
            'adjustType' => 0,
            'utaDemoApplyMoney' => [['coin' => 'USDT', 'amountStr' => '10000']],
        ]);

    expect(fn (): BybitClient => new BybitClient('key', 'secret', true, 'global', 5000, 'hmac', null, null, null, true))
        ->toThrow(InvalidArgumentException::class, 'cannot be enabled together');
});

it('builds a derivative market order from the ticker price and requested leverage', function (): void {
    $history = [];
    $client = new FixedClockBybitClient(
        'key',
        'secret',
        false,
        'global',
        5000,
        'hmac',
        null,
        mockHttpClient([
            new Response(200, [], '{"retCode":0,"result":{"list":[{"lastPrice":"100"}]}}'),
            new Response(200, [], '{"retCode":0}'),
            new Response(200, [], '{"retCode":0}'),
        ], $history)
    );

    $client->placeOrder('derivatives', 'BTCUSDT', 'market', null, 'Sell', 2.0, 100.0);

    expect($history[0]['request']->getRequestTarget())->toBe('/v5/market/tickers?category=linear&symbol=BTCUSDT')
        ->and($history[1]['request']->getRequestTarget())->toBe('/v5/position/set-leverage')
        ->and(json_decode((string) $history[1]['request']->getBody(), true))->toBe([
            'category' => 'linear',
            'symbol' => 'BTCUSDT',
            'sellLeverage' => '2',
        ])
        ->and(json_decode((string) $history[2]['request']->getBody(), true))->toBe([
            'category' => 'linear',
            'symbol' => 'BTCUSDT',
            'side' => 'Sell',
            'orderType' => 'Market',
            'qty' => '2',
            'positionIdx' => 0,
        ]);
});
