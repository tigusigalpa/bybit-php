<?php

namespace Tigusigalpa\ByBit\Exceptions;

/**
 * Thrown when Bybit responds with a non-successful HTTP status code.
 */
class BybitHttpException extends \RuntimeException
{
    public int $statusCode;
    public string $responseBody;

    public function __construct(int $statusCode, string $status, string $responseBody)
    {
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;

        parent::__construct(
            'Bybit API request failed: ' . $status . ($responseBody === '' ? '' : ': ' . $responseBody),
            $statusCode
        );
    }
}
