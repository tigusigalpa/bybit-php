<?php
namespace Tigusigalpa\ByBit\Facades;

use Illuminate\Support\Facades\Facade;

class Bybit extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'bybit';
    }
}