<?php
namespace Tigusigalpa\ByBit\Facades;

use Illuminate\Support\Facades\Facade;

class BybitWebSocket extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Tigusigalpa\ByBit\BybitWebSocket::class;
    }
}
