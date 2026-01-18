# Bybit PHP — V5 API Client with Laravel 8–12 Integration

![ByBit PHP SDK](https://github.com/user-attachments/assets/cd31c2a6-5853-4287-a79b-2fb16ca3fcaa)

**🌐 Language:** English | [Русский](README-ru.md)

A lightweight library for working with Bybit V5 API in pure PHP and Laravel 8–12 projects. Supports test trading (Testnet), regional endpoint selection for Mainnet, and built-in authentication with request signing (HMAC-SHA256 or RSA-SHA256).

## Features
- `BybitClient` with universal `request()` method for `GET/POST`
- Request signing according to Bybit V5 rules (`X-BAPI-*` headers)
- Environment selection: Testnet or Mainnet with regional endpoints
- Laravel integration: service provider, facade, publishable config
- Configurable `recv_window` and signature type (`hmac`/`rsa`)

## Requirements
- PHP `^7.4|^8.0|^8.1|^8.2`
- Laravel `8–12` (for framework integration)

## Installation (local path in monorepo)
1. Add a `path` type repository to your project's root `composer.json`:
   ```json
   {
     "repositories": [
       { "type": "path", "url": "public_html/packages/bybit-php" }
     ]
   }
   ```
2. Install the package:
   ```bash
   composer require tigusigalpa/bybit-php:* --prefer-source
   ```

## Laravel Integration
- The package uses auto-discovery for provider and facade alias.
- Publish the config:
  ```bash
  php artisan vendor:publish --tag=bybit-config
  ```

## Configuration
Settings are located in `config/bybit.php` and managed via `.env`:
- `BYBIT_API_KEY` — public API key
- `BYBIT_API_SECRET` — secret key (for HMAC)
- `BYBIT_TESTNET` — `true/false` to enable test environment
- `BYBIT_REGION` — `global|nl|tr|kz|ge|ae`
- `BYBIT_RECV_WINDOW` — request receive window in ms (default `5000`)
- `BYBIT_SIGNATURE` — `hmac` or `rsa` (default `hmac`)
- `BYBIT_RSA_PRIVATE_KEY` — RSA private key (PEM) for RSA signing

Example `.env`:
```env
BYBIT_API_KEY=your_api_key
BYBIT_API_SECRET=your_api_secret
BYBIT_TESTNET=true
BYBIT_REGION=global
BYBIT_RECV_WINDOW=5000
BYBIT_SIGNATURE=hmac
# For RSA (if used):
# BYBIT_SIGNATURE=rsa
# BYBIT_RSA_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----"
```

## Endpoints and Regions
- Testnet: `https://api-testnet.bybit.com`
- Mainnet by region:
  - Netherlands (`nl`): `https://api.bybit.nl`
  - Turkey (`tr`): `https://api.bybit-tr.com`
  - Kazakhstan (`kz`): `https://api.bybit.kz`
  - Georgia (`ge`): `https://api.bybitgeorgia.ge`
  - United Arab Emirates (`ae`): `https://api.bybit.ae`
- Otherwise: `https://api.bybit.com` (also available `https://api.bytick.com`)

## Authentication and Signing
Signature generation rules comply with Bybit V5 guidelines:
- For `GET`: `timestamp + api_key + recv_window + queryString`
- For `POST`: `timestamp + api_key + recv_window + jsonBodyString`
- `HMAC-SHA256` → lowercase hex
- `RSA-SHA256` → base64

Request headers:
- `X-BAPI-API-KEY`
- `X-BAPI-TIMESTAMP` (UTC, milliseconds)
- `X-BAPI-RECV-WINDOW` (ms)
- `X-BAPI-SIGN`
- `X-BAPI-SIGN-TYPE: 2` for HMAC
- `Content-Type: application/json` for `POST`

Documentation: https://bybit-exchange.github.io/docs/v5/guide

## Usage
Via facade:
```php
use Tigusigalpa\ByBit\Facades\Bybit;

$response = Bybit::request('GET', '/v5/order/realtime', [
    'category' => 'option',
    'symbol' => 'BTC-29JUL22-25000-C'
]);
```

Via DI (recommended for testing):
```php
use Tigusigalpa\ByBit\BybitClient;

public function __construct(BybitClient $bybit)
{
    $this->bybit = $bybit;
}

$data = $this->bybit->request('POST', '/v5/order/create', [
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'side' => 'Buy',
    'orderType' => 'Limit',
    'qty' => '0.01',
    'price' => '30000'
]);
```

## Debugging and Stability
- Synchronize server time (NTP), as validation window: `server_time - recv_window <= timestamp < server_time + 1000`.
- For network issues, you can add `cdn-request-id` (unique) to headers — for CDN diagnostics.
- For signature errors, check parameter sorting and body format for `POST` (`JSON_UNESCAPED_SLASHES`).

## Versions and Changes
- Current version: `0.1.0`
- Changelog: `CHANGELOG.md`

## Author and License
- Author: Igor Sazonov (`tigusigalpa`)
- Email: `sovletig@gmail.com`
- GitHub: https://github.com/tigusigalpa/bybit-php
- License: MIT

## Development Roadmap
- Convenient wrapper methods for popular endpoints (`server-time`, `orders`, `positions`)
- Retries and rate-limit handling
- Request logging and tracing

## Wrapper Methods

```php
use Tigusigalpa\ByBit\Facades\Bybit;

// Server time
$time = Bybit::getServerTime();

// Market tickers
$tickers = Bybit::getTickers([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);

// Create order
$order = Bybit::createOrder([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'side' => 'Buy',
    'orderType' => 'Limit',
    'qty' => '0.01',
    'price' => '30000',
    'timeInForce' => 'GTC'
]);

// Open orders
$open = Bybit::getOpenOrders([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);

// Cancel order
$cancel = Bybit::cancelOrder([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'orderId' => 'xxxxxxxxxxxxxxxxxxxx'
]);

// Wallet balance
$balance = Bybit::getWalletBalance([
    'accountType' => 'UNIFIED',
    'coin' => 'USDT'
]);

// Positions
$positions = Bybit::getPositions([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);
```

### Universal Order Placement Method

```php
use Tigusigalpa\ByBit\Facades\Bybit;

$resp = Bybit::placeOrder(
    type: 'spot',
    symbol: 'BTCUSDT',
    execution: 'limit',
    price: 30000.0,
    side: 'Buy',
    leverage: null,
    size: 0.01,
    slTp: null
);

$resp = Bybit::placeOrder(
    type: 'derivatives',
    symbol: 'BTCUSDT',
    execution: 'market',
    price: null,
    side: 'Buy',
    leverage: 5,
    size: 100, // margin in USDT
    slTp: [
        'type' => 'percent',
        'takeProfit' => 0.02,
        'stopLoss' => 0.01
    ]
);

$resp = Bybit::placeOrder(
    type: 'derivatives',
    symbol: 'BTCUSDT',
    execution: 'trigger',
    price: 29500.0, // triggerPrice
    side: 'Buy',
    leverage: 3,
    size: 150, // margin in USDT
    slTp: [
        'type' => 'absolute',
        'takeProfit' => 30500.0,
        'stopLoss' => 29000.0
    ],
    extra: [
        // additional fields for flexibility
        'timeInForce' => 'GTC'
    ]
);
```

Parameters:
- `type`: `spot` or `derivatives`
- `symbol`: instrument code, e.g., `BTCUSDT`
- `execution`: `market` | `limit` | `trigger`
- `price`: entry price for `limit`, or `triggerPrice` for `trigger`; for `market` — `null`
- `side`: `Buy`/`Sell`; for `spot` defaults to `Buy`
- `leverage`: leverage (only for `derivatives`); when set, it's configured via `/v5/position/set-leverage`
- `size`: for `spot` — quantity; for `derivatives` — margin amount in quote currency (e.g., USDT)
- `slTp`: `['type'=>'absolute'|'percent','takeProfit'=>..., 'stopLoss'=>...]` — for derivatives; percentages are calculated from entry price
- `extra`: associative array of additional parameters (e.g., `timeInForce`, `orderLinkId`)

Notes:
- For derivatives, quantity is calculated as `qty = margin * leverage / price`. For `market`, price is fetched from `/v5/market/tickers`.
- For `trigger`, trigger direction is set by `side`: `Buy → triggerDirection=1`, `Sell → triggerDirection=2`.

Via DI interface:
```php
use Tigusigalpa\ByBit\BybitClient;

public function __construct(BybitClient $bybit)
{
    $this->bybit = $bybit;
}

$serverTime = $this->bybit->getServerTime();
$order = $this->bybit->createOrder([...]);
```

### Trading Fee Calculation

```php
use Tigusigalpa\ByBit\Facades\Bybit;

// For Spot (default Non-VIP, taker)
$feeSpot = Bybit::computeFee('spot', 1000.0, 'Non-VIP', 'taker'); // 1000 * 0.001 = 1.0

// For derivatives: volume = margin * leverage
$margin = 100.0;
$leverage = 5.0;
$volume = $margin * $leverage; // 500
$feeDeriv = Bybit::computeFee('derivatives', $volume, 'Non-VIP', 'taker');
```

The base rate table is defined in the config `config/bybit.php` in the `fees` section. You can modify levels and rates according to actual account data or use Bybit requests to get the exact account rate.
