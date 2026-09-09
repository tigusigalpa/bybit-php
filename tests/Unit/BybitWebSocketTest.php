<?php

declare(strict_types=1);

use Tigusigalpa\ByBit\BybitWebSocket;

it('selects the documented WebSocket endpoint for each environment', function (
    BybitWebSocket $socket,
    string $endpoint
): void {
    expect($socket->endpoint())->toBe($endpoint);
})->with([
    'mainnet public linear' => [
        new BybitWebSocket(null, null, false, 'global', false, 'linear'),
        'wss://stream.bybit.com/v5/public/linear',
    ],
    'testnet public option' => [
        new BybitWebSocket(null, null, true, 'jp', false, 'option'),
        'wss://stream-testnet.bybit.com/v5/public/option',
    ],
    'demo private' => [
        new BybitWebSocket('key', 'secret', false, 'global', true, 'linear', true),
        'wss://stream-demo.bybit.com/v5/private',
    ],
    'demo public data uses mainnet' => [
        new BybitWebSocket(null, null, false, 'global', false, 'spread', true),
        'wss://stream.bybit.com/v5/public/spread',
    ],
]);

it('normalizes supported public categories and rejects conflicting environments', function (): void {
    $socket = new BybitWebSocket();

    expect($socket->setCategory('USDC')->endpoint())->toBe('wss://stream.bybit.com/v5/public/linear')
        ->and($socket->setCategory('unknown')->endpoint())->toBe('wss://stream.bybit.com/v5/public/spot')
        ->and(fn (): BybitWebSocket => new BybitWebSocket(null, null, true, 'global', false, 'spot', true))
        ->toThrow(InvalidArgumentException::class, 'cannot be enabled together');
});
