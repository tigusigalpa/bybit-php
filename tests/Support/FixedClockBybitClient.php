<?php

declare(strict_types=1);

namespace Tests\Support;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Tigusigalpa\ByBit\BybitClient;

final class FixedClockBybitClient extends BybitClient
{
    protected function timestamp(): string
    {
        return '1700000000000';
    }

    protected function rateLimit(string $method): void
    {
    }
}

/**
 * Create an in-memory Guzzle transport and retain the dispatched requests.
 *
 * @param array<int, mixed> $responses
 * @param array<int, array<string, mixed>> $history
 */
function mockHttpClient(array $responses, array &$history): Client
{
    $stack = HandlerStack::create(new MockHandler($responses));
    $stack->push(Middleware::history($history));

    return new Client([
        'base_uri' => 'https://api.bybit.com',
        'handler' => $stack,
    ]);
}
