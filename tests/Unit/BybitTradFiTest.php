<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Response;
use Tests\Support\FixedClockBybitClient;
use Tigusigalpa\ByBit\BybitTradFi;

use function Tests\Support\mockHttpClient;

it('recognizes TradFi symbols without classifying crypto pairs as forex', function (string $symbol, bool $isTradFi): void {
    expect(BybitTradFi::isTradFiSymbol($symbol))->toBe($isTradFi);
})->with([
    'metal' => ['XAUUSD', true],
    'forex' => ['EURUSD', true],
    'index' => ['US500USD', true],
    'crypto pair' => ['BTCUSD', false],
    'stock CFD' => ['AAPLUSDT', false],
]);

it('combines paginated instrument responses before filtering their asset class', function (): void {
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
            new Response(200, [], '{"retCode":0,"result":{"list":[{"symbol":"XAUUSD"},{"symbol":"BTCUSDT"}],"nextPageCursor":"next"}}'),
            new Response(200, [], '{"retCode":0,"result":{"list":[{"symbol":"XAGUSD"}],"nextPageCursor":""}}'),
        ], $history)
    );

    $response = (new BybitTradFi($client))->getInstruments('metal');

    expect(array_column($response['result']['list'], 'symbol'))->toBe(['XAUUSD', 'XAGUSD'])
        ->and($history[0]['request']->getRequestTarget())->toBe('/v5/market/instruments-info?category=linear&limit=1000')
        ->and($history[1]['request']->getRequestTarget())->toBe('/v5/market/instruments-info?category=linear&cursor=next&limit=1000');
});
