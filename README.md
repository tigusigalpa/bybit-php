<div align="center">

# Bybit PHP SDK

### V5 API Client for PHP & Laravel

[![PHP Version](https://img.shields.io/badge/PHP-7.4%20%7C%208.0%20%7C%208.1%20%7C%208.2-blue.svg)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-8%20%7C%209%20%7C%2010%20%7C%2011%20%7C%2012-red.svg)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![WebSocket](https://img.shields.io/badge/WebSocket-Supported-brightgreen.svg)](https://bybit-exchange.github.io/docs/v5/ws/connect)

![ByBit PHP SDK](https://i.postimg.cc/tgv9j30p/bybit-php-banner-gh-(1).jpg)

**Language:** English | [Русский](README-ru.md)

> 📖 **[Full documentation available on Wiki](https://github.com/tigusigalpa/bybit-php/wiki)**

PHP-библиотека для работы с Bybit V5 API. Поддерживает REST и WebSocket, работает как standalone, так и с Laravel.

[Features](#features) • [Installation](#installation) • [Quick Start](#quick-start) • [API Methods](#api-methods) • [WebSocket](#websocket-streaming) • [Examples](#examples)

</div>

---

## Features

- Full Bybit V5 API coverage (spot, linear, inverse, options)
- HMAC-SHA256 and RSA-SHA256 signatures
- WebSocket for real-time data (orderbook, trades, klines, account updates)
- Testnet and demo trading support
- Regional endpoints (NL, TR, KZ, GE, AE)
- Laravel integration (facade, service provider, config publishing)
- Automatic reconnection for WebSocket

---

## Installation

### Pure PHP (without Laravel)

```bash
composer require tigusigalpa/bybit-php
```

### Laravel Integration

**For local monorepo setup:**

1. Add repository to `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "public_html/packages/bybit-php"
        }
    ]
}
```

2. Install the package:

```bash
composer require tigusigalpa/bybit-php:* --prefer-source
```

3. Publish configuration:

```bash
php artisan vendor:publish --tag=bybit-config
```

Laravel auto-discovery регистрирует service provider и facade автоматически.

---

## Configuration

### Environment Variables

Create or update your `.env` file:

```env
BYBIT_API_KEY=your_api_key_here
BYBIT_API_SECRET=your_api_secret_here
BYBIT_TESTNET=true
BYBIT_DEMO_TRADING=false
BYBIT_REGION=global
BYBIT_RECV_WINDOW=5000
BYBIT_SIGNATURE=hmac

# For RSA signature (optional):
# BYBIT_SIGNATURE=rsa
# BYBIT_RSA_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----"
```

### Configuration Options

| Parameter               | Type    | Default  | Description                                                |
|-------------------------|---------|----------|------------------------------------------------------------|
| `BYBIT_API_KEY`         | string  | -        | Your Bybit API public key                                  |
| `BYBIT_API_SECRET`      | string  | -        | Your Bybit API secret key                                  |
| `BYBIT_TESTNET`         | boolean | `false`  | Enable testnet environment                                 |
| `BYBIT_DEMO_TRADING`    | boolean | `false`  | Enable demo trading environment                            |
| `BYBIT_REGION`          | string  | `global` | Regional endpoint (`global`, `nl`, `tr`, `kz`, `ge`, `ae`) |
| `BYBIT_RECV_WINDOW`     | integer | `5000`   | Request receive window (ms)                                |
| `BYBIT_SIGNATURE`       | string  | `hmac`   | Signature type (`hmac` or `rsa`)                           |
| `BYBIT_RSA_PRIVATE_KEY` | string  | `null`   | RSA private key (PEM format)                               |

---

## Quick Start

### Pure PHP Usage

```php
<?php
require_once 'vendor/autoload.php';

use Tigusigalpa\ByBit\BybitClient;

// Initialize client
$client = new BybitClient(
    apiKey: 'your_api_key',
    apiSecret: 'your_api_secret',
    testnet: true,
    region: 'global',
    recvWindow: 5000,
    signature: 'hmac'
);

// Get server time
$serverTime = $client->getServerTime();
echo "Server Time: " . json_encode($serverTime) . "\n";

// Get market tickers
$tickers = $client->getTickers([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);
print_r($tickers);

// Place a limit order
$order = $client->createOrder([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'side' => 'Buy',
    'orderType' => 'Limit',
    'qty' => '0.01',
    'price' => '30000',
    'timeInForce' => 'GTC'
]);
print_r($order);

// Get positions
$positions = $client->getPositions([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);
print_r($positions);
```

### Laravel Usage

**Using Facade:**

```php
use Tigusigalpa\ByBit\Facades\Bybit;

// Get server time
$time = Bybit::getServerTime();

// Get market data
$tickers = Bybit::getTickers([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);

// Place order
$order = Bybit::createOrder([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'side' => 'Buy',
    'orderType' => 'Limit',
    'qty' => '0.01',
    'price' => '30000'
]);
```

**Using Dependency Injection:**

```php
use Tigusigalpa\ByBit\BybitClient;

class TradingController extends Controller
{
    public function __construct(
        private BybitClient $bybit
    ) {}
    
    public function placeOrder()
    {
        $order = $this->bybit->createOrder([
            'category' => 'linear',
            'symbol' => 'BTCUSDT',
            'side' => 'Buy',
            'orderType' => 'Market',
            'qty' => '0.01'
        ]);
        
        return response()->json($order);
    }
}
```

---

## API Methods

### Market Data

```php
// Get server time
$time = $client->getServerTime();

// Get market tickers
$tickers = $client->getTickers([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);

// Get kline/candlestick data
$klines = $client->getKline([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'interval' => '60',  // 1m, 3m, 5m, 15m, 30m, 60m, 120m, 240m, 360m, 720m, D, W, M
    'limit' => 200
]);

// Get orderbook depth
$orderbook = $client->getOrderbook([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'limit' => 50  // Max depth: 1, 25, 50, 100, 200, 500
]);

// Get RPI (Risk Premium Index) orderbook
$rpiOrderbook = $client->getRPIOrderbook([
    'category' => 'option',
    'symbol' => 'BTC-30DEC23-80000-C',
    'limit' => 25
]);

// Get open interest
$openInterest = $client->getOpenInterest([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'intervalTime' => '5min'  // 5min, 15min, 30min, 1h, 4h, 1d
]);

// Get recent public trades
$recentTrades = $client->getRecentTrades([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'limit' => 60
]);

// Get funding rate history
$fundingHistory = $client->getFundingRateHistory([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'limit' => 200
]);

// Get historical volatility (options)
$historicalVolatility = $client->getHistoricalVolatility([
    'category' => 'option',
    'baseCoin' => 'BTC',
    'period' => 7  // 7, 14, 21, 30, 60, 90, 180, 270 days
]);

// Get insurance pool data
$insurance = $client->getInsurancePool([
    'coin' => 'USDT'
]);

// Get risk limit
$riskLimit = $client->getRiskLimit([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);
```

### Order Management

```php
// Create order
$order = $client->createOrder([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'side' => 'Buy',
    'orderType' => 'Limit',
    'qty' => '0.01',
    'price' => '30000',
    'timeInForce' => 'GTC'
]);

// Get open orders
$openOrders = $client->getOpenOrders([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);

// Amend order
$amended = $client->amendOrder([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'orderId' => 'order_id_here',
    'qty' => '0.02',
    'price' => '31000'
]);

// Cancel order
$cancelled = $client->cancelOrder([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'orderId' => 'order_id_here'
]);

// Cancel all orders
$cancelledAll = $client->cancelAllOrders([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);

// Get order history
$history = $client->getHistoryOrders([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'limit' => 50
]);
```

### Position Management

```php
// Get positions
$positions = $client->getPositions([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);

// Set leverage
$client->setLeverage('linear', 'BTCUSDT', 10);

// Switch position mode (One-Way or Hedge)
$client->switchPositionMode([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'mode' => 0 // 0: One-Way, 3: Hedge
]);

// Set trading stop (TP/SL)
$client->setTradingStop([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'positionIdx' => 0,
    'takeProfit' => '35000',
    'stopLoss' => '28000'
]);

// Set auto add margin
$client->setAutoAddMargin([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'autoAddMargin' => 1,  // 0: off, 1: on
    'positionIdx' => 0
]);

// Manually add or reduce margin
$client->addOrReduceMargin([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'margin' => '100',  // positive to add, negative to reduce
    'positionIdx' => 0
]);

// Get closed P&L (last 2 years)
$closedPnl = $client->getClosedPnL([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'limit' => 50
]);

// Get closed options positions (last 6 months)
$closedOptions = $client->getClosedOptionsPositions([
    'category' => 'option',
    'symbol' => 'BTC-30DEC23-80000-C',
    'limit' => 50
]);

// Move position between UIDs
$moveResult = $client->movePosition([
    'fromUid' => '100307601',
    'toUid' => '592324',
    'list' => [
        [
            'category' => 'linear',
            'symbol' => 'BTCUSDT',
            'price' => '30000',
            'side' => 'Buy',
            'qty' => '0.01'
        ]
    ]
]);

// Get move position history
$moveHistory = $client->getMovePositionHistory([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'limit' => 50
]);

// Confirm new risk limit
$confirmRisk = $client->confirmNewRiskLimit([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);
```

### Account & Wallet

```php
// Get wallet balance
$balance = $client->getWalletBalance([
    'accountType' => 'UNIFIED',
    'coin' => 'USDT'
]);

// Get transferable amount (Unified account)
$transferable = $client->getTransferableAmount([
    'accountType' => 'UNIFIED',
    'coin' => 'USDT'
]);

// Get transaction log
$transactions = $client->getTransactionLog([
    'accountType' => 'UNIFIED',
    'category' => 'linear',
    'limit' => 50
]);

// Get account info
$accountInfo = $client->getAccountInfo();

// Get account instruments info
$instrumentsInfo = $client->getAccountInstrumentsInfo([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);

// Calculate trading fee
$fee = $client->computeFee('spot', 1000.0, 'Non-VIP', 'taker');
```

### Demo Trading

Demo trading — тестирование стратегий без риска. Используется домен `api-demo.bybit.com`.

```php
// Initialize demo trading client
$demoClient = new BybitClient(
    apiKey: 'your_demo_api_key',
    apiSecret: 'your_demo_api_secret',
    testnet: false,
    region: 'global',
    recvWindow: 5000,
    signature: 'hmac',
    rsaPrivateKey: null,
    http: null,
    fees: null,
    demoTrading: true  // Enable demo trading mode
);

// Create demo account (use production API key with api.bybit.com)
$productionClient = new BybitClient('prod_key', 'prod_secret');
$demoAccount = $productionClient->createDemoAccount();
// Returns: ['uid' => '123456789', ...]

// Request demo funds (use demo API key with api-demo.bybit.com)
$fundingResult = $demoClient->requestDemoFunds([
    'adjustType' => 0,  // 0: add, 1: reduce
    'utaDemoApplyMoney' => [
        ['coin' => 'USDT', 'amountStr' => '10000'],
        ['coin' => 'BTC', 'amountStr' => '1']
    ]
]);

// All trading methods work the same in demo mode
$order = $demoClient->createOrder([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'side' => 'Buy',
    'orderType' => 'Market',
    'qty' => '0.01'
]);
```

**Laravel Demo Trading:**

```php
// In .env file
BYBIT_DEMO_TRADING=true

// Use normally
$balance = Bybit::getWalletBalance(['accountType' => 'UNIFIED']);
```

---

## WebSocket Streaming

### Public Streams

**Pure PHP WebSocket:**

```php
use Tigusigalpa\ByBit\BybitWebSocket;

// Create WebSocket instance
$ws = new BybitWebSocket(
    apiKey: null,
    apiSecret: null,
    testnet: false,
    region: 'global',
    isPrivate: false
);

// Subscribe to orderbook
$ws->subscribeOrderbook('BTCUSDT', 50);

// Subscribe to trades
$ws->subscribeTrade('BTCUSDT');

// Subscribe to ticker
$ws->subscribeTicker('BTCUSDT');

// Subscribe to klines
$ws->subscribeKline('BTCUSDT', '1'); // 1m candles

// Handle messages
$ws->onMessage(function($data) {
    if (isset($data['topic'])) {
        echo "Topic: {$data['topic']}\n";
        print_r($data['data']);
    }
});

// Start listening (blocking)
$ws->listen();
```

### Private Streams

**Authenticated WebSocket for account updates:**

```php
use Tigusigalpa\ByBit\BybitWebSocket;

$ws = new BybitWebSocket(
    apiKey: 'your_api_key',
    apiSecret: 'your_api_secret',
    testnet: false,
    region: 'global',
    isPrivate: true
);

// Subscribe to position updates
$ws->subscribePosition();

// Subscribe to order updates
$ws->subscribeOrder();

// Subscribe to execution updates
$ws->subscribeExecution();

// Subscribe to wallet updates
$ws->subscribeWallet();

$ws->onMessage(function($data) {
    match($data['topic'] ?? null) {
        'position' => handlePositionUpdate($data),
        'order' => handleOrderUpdate($data),
        'execution' => handleExecutionUpdate($data),
        'wallet' => handleWalletUpdate($data),
        default => null
    };
});

$ws->listen();
```

**Laravel Background Command:**

```php
// app/Console/Commands/BybitWebSocketListener.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Tigusigalpa\ByBit\BybitWebSocket;

class BybitWebSocketListener extends Command
{
    protected $signature = 'bybit:listen {symbol=BTCUSDT}';
    protected $description = 'Listen to Bybit WebSocket streams';

    public function handle()
    {
        $symbol = $this->argument('symbol');
        $ws = app(BybitWebSocket::class);
        
        $ws->subscribeOrderbook($symbol, 50);
        $ws->subscribeTrade($symbol);
        
        $ws->onMessage(fn($data) => 
            $this->info(json_encode($data, JSON_PRETTY_PRINT))
        );
        
        $this->info("🚀 WebSocket listener started for {$symbol}...");
        $ws->listen();
    }
}
```

Run with: `php artisan bybit:listen BTCUSDT`

---

## Advanced Usage

### Universal Order Placement

```php
// Spot limit order
$order = $client->placeOrder(
    type: 'spot',
    symbol: 'BTCUSDT',
    execution: 'limit',
    price: 30000.0,
    side: 'Buy',
    leverage: null,
    size: 0.01,
    slTp: null
);

// Derivatives market order with leverage
$order = $client->placeOrder(
    type: 'derivatives',
    symbol: 'BTCUSDT',
    execution: 'market',
    price: null,
    side: 'Buy',
    leverage: 10,
    size: 100, // margin in USDT
    slTp: [
        'type' => 'percent',
        'takeProfit' => 0.02, // 2%
        'stopLoss' => 0.01    // 1%
    ]
);

// Trigger order with absolute TP/SL
$order = $client->placeOrder(
    type: 'derivatives',
    symbol: 'BTCUSDT',
    execution: 'trigger',
    price: 29500.0,
    side: 'Buy',
    leverage: 5,
    size: 150,
    slTp: [
        'type' => 'absolute',
        'takeProfit' => 31000.0,
        'stopLoss' => 29000.0
    ],
    extra: ['timeInForce' => 'GTC']
);
```

### Trading Fee Calculation

```php
// Spot trading fee
$feeSpot = $client->computeFee('spot', 1000.0, 'Non-VIP', 'taker');
// Result: 1.0 USDT (0.1%)

// Derivatives with leverage
$margin = 100.0;
$leverage = 10.0;
$volume = $margin * $leverage; // 1000
$feeDeriv = $client->computeFee('derivatives', $volume, 'VIP1', 'maker');
```

---

## Regional Endpoints

| Region           | Code     | Endpoint                        |
|------------------|----------|---------------------------------|
| 🌐 Global        | `global` | `https://api.bybit.com`         |
| 🇳🇱 Netherlands | `nl`     | `https://api.bybit.nl`          |
| 🇹🇷 Turkey      | `tr`     | `https://api.bybit-tr.com`      |
| 🇰🇿 Kazakhstan  | `kz`     | `https://api.bybit.kz`          |
| 🇬🇪 Georgia     | `ge`     | `https://api.bybitgeorgia.ge`   |
| 🇦🇪 UAE         | `ae`     | `https://api.bybit.ae`          |
| 🧪 Testnet       | -        | `https://api-testnet.bybit.com` |

---

## Authentication

### Signature Generation

Bybit V5 API uses HMAC-SHA256 or RSA-SHA256 for request signing:

**For GET requests:**

```
signature_payload = timestamp + api_key + recv_window + queryString
```

**For POST requests:**

```
signature_payload = timestamp + api_key + recv_window + jsonBody
```

**HMAC-SHA256:** Returns lowercase hex
**RSA-SHA256:** Returns base64

### Required Headers

```
X-BAPI-API-KEY: your_api_key
X-BAPI-TIMESTAMP: 1234567890000
X-BAPI-RECV-WINDOW: 5000
X-BAPI-SIGN: generated_signature
X-BAPI-SIGN-TYPE: 2 (for HMAC)
Content-Type: application/json (for POST)
```

Official docs: https://bybit-exchange.github.io/docs/v5/guide

---

## Examples

### Trading Bot Example

```php
<?php
require 'vendor/autoload.php';

use Tigusigalpa\ByBit\BybitClient;

$client = new BybitClient(
    apiKey: getenv('BYBIT_API_KEY'),
    apiSecret: getenv('BYBIT_API_SECRET'),
    testnet: true
);

// Check balance
$balance = $client->getWalletBalance([
    'accountType' => 'UNIFIED',
    'coin' => 'USDT'
]);

echo "Balance: {$balance['result']['list'][0]['totalWalletBalance']} USDT\n";

// Get current price
$ticker = $client->getTickers([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);

$currentPrice = $ticker['result']['list'][0]['lastPrice'];
echo "BTC Price: \${$currentPrice}\n";

// Place order
$order = $client->createOrder([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'side' => 'Buy',
    'orderType' => 'Limit',
    'qty' => '0.01',
    'price' => (string)($currentPrice * 0.99), // 1% below market
    'timeInForce' => 'GTC'
]);

echo "Order placed: {$order['result']['orderId']}\n";
```

---

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## License

**MIT License**

Copyright (c) 2026 Igor Sazonov

- **Author:** Igor Sazonov (`tigusigalpa`)
- **Email:** sovletig@gmail.com
- **GitHub:** https://github.com/tigusigalpa/bybit-php

---

<div align="center">

[Report Bug](https://github.com/tigusigalpa/bybit-php/issues) • [Request Feature](https://github.com/tigusigalpa/bybit-php/issues) • [Docs](https://bybit-exchange.github.io/docs/v5/guide)

</div>
