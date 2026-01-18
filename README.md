<div align="center">

# 🚀 Bybit PHP SDK

### Professional V5 API Client for PHP & Laravel

[![PHP Version](https://img.shields.io/badge/PHP-7.4%20%7C%208.0%20%7C%208.1%20%7C%208.2-blue.svg)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-8%20%7C%209%20%7C%2010%20%7C%2011%20%7C%2012-red.svg)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![WebSocket](https://img.shields.io/badge/WebSocket-Supported-brightgreen.svg)](https://bybit-exchange.github.io/docs/v5/ws/connect)

![ByBit PHP SDK](https://github.com/user-attachments/assets/cd31c2a6-5853-4287-a79b-2fb16ca3fcaa)

**🌐 Language:** [English](#) | [Русский](README-ru.md)

*A powerful, lightweight library for seamless integration with Bybit V5 API in pure PHP and Laravel projects.*

[Features](#-features) • [Installation](#-installation) • [Quick Start](#-quick-start) • [Documentation](#-documentation) • [WebSocket](#-websocket-streaming) • [Examples](#-examples)

</div>

---

## 📋 Table of Contents

- [✨ Features](#-features)
- [📦 Installation](#-installation)
  - [Pure PHP](#pure-php-without-laravel)
  - [Laravel Integration](#laravel-integration)
- [⚙️ Configuration](#️-configuration)
- [🚀 Quick Start](#-quick-start)
  - [Pure PHP Usage](#pure-php-usage)
  - [Laravel Usage](#laravel-usage)
- [📚 API Methods](#-api-methods)
  - [Market Data](#market-data)
  - [Order Management](#order-management)
  - [Position Management](#position-management)
  - [Account & Wallet](#account--wallet)
- [🌐 WebSocket Streaming](#-websocket-streaming)
  - [Public Streams](#public-streams)
  - [Private Streams](#private-streams)
- [💡 Advanced Usage](#-advanced-usage)
- [🌍 Regional Endpoints](#-regional-endpoints)
- [🔐 Authentication](#-authentication)
- [📖 Examples](#-examples)
- [🤝 Contributing](#-contributing)
- [📄 License](#-license)

---

## ✨ Features

<table>
<tr>
<td width="50%">

### 🎯 Core Features
- ✅ Full Bybit V5 API support
- ✅ HMAC-SHA256 & RSA-SHA256 signing
- ✅ Testnet & Mainnet environments
- ✅ Regional endpoint selection
- ✅ Pure PHP & Laravel compatible
- ✅ Type-safe request handling

</td>
<td width="50%">

### ⚡ Advanced Features
- ✅ Real-time WebSocket streaming
- ✅ Auto-reconnection handling
- ✅ Multiple topic subscriptions
- ✅ Configurable recv_window
- ✅ Comprehensive error handling
- ✅ Laravel facade & DI support

</td>
</tr>
</table>

---

## 📦 Installation

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
    { "type": "path", "url": "public_html/packages/bybit-php" }
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

> 💡 **Note:** The package uses Laravel auto-discovery for service provider and facade registration.

---

## ⚙️ Configuration

### Environment Variables

Create or update your `.env` file:

```env
BYBIT_API_KEY=your_api_key_here
BYBIT_API_SECRET=your_api_secret_here
BYBIT_TESTNET=true
BYBIT_REGION=global
BYBIT_RECV_WINDOW=5000
BYBIT_SIGNATURE=hmac

# For RSA signature (optional):
# BYBIT_SIGNATURE=rsa
# BYBIT_RSA_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----"
```

### Configuration Options

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `BYBIT_API_KEY` | string | - | Your Bybit API public key |
| `BYBIT_API_SECRET` | string | - | Your Bybit API secret key |
| `BYBIT_TESTNET` | boolean | `false` | Enable testnet environment |
| `BYBIT_REGION` | string | `global` | Regional endpoint (`global`, `nl`, `tr`, `kz`, `ge`, `ae`) |
| `BYBIT_RECV_WINDOW` | integer | `5000` | Request receive window (ms) |
| `BYBIT_SIGNATURE` | string | `hmac` | Signature type (`hmac` or `rsa`) |
| `BYBIT_RSA_PRIVATE_KEY` | string | `null` | RSA private key (PEM format) |

---

## 🚀 Quick Start

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

## 📚 API Methods

### Market Data

```php
// Get server time
$time = $client->getServerTime();

// Get market tickers
$tickers = $client->getTickers([
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
```

### Account & Wallet

```php
// Get wallet balance
$balance = $client->getWalletBalance([
    'accountType' => 'UNIFIED',
    'coin' => 'USDT'
]);

// Calculate trading fee
$fee = $client->computeFee('spot', 1000.0, 'Non-VIP', 'taker');
```

---

## 🌐 WebSocket Streaming

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

## 💡 Advanced Usage

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

## 🌍 Regional Endpoints

| Region | Code | Endpoint |
|--------|------|----------|
| 🌐 Global | `global` | `https://api.bybit.com` |
| 🇳🇱 Netherlands | `nl` | `https://api.bybit.nl` |
| 🇹🇷 Turkey | `tr` | `https://api.bybit-tr.com` |
| 🇰🇿 Kazakhstan | `kz` | `https://api.bybit.kz` |
| 🇬🇪 Georgia | `ge` | `https://api.bybitgeorgia.ge` |
| 🇦🇪 UAE | `ae` | `https://api.bybit.ae` |
| 🧪 Testnet | - | `https://api-testnet.bybit.com` |

---

## 🔐 Authentication

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

> 📖 **Official Documentation:** https://bybit-exchange.github.io/docs/v5/guide

---

## 📖 Examples

### Complete Trading Bot Example

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

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📄 License

**MIT License**

Copyright (c) 2026 Igor Sazonov

- **Author:** Igor Sazonov (`tigusigalpa`)
- **Email:** sovletig@gmail.com
- **GitHub:** https://github.com/tigusigalpa/bybit-php

---

<div align="center">

### 🌟 Star this repository if you find it helpful!

**Made with ❤️ for the crypto trading community**

[Report Bug](https://github.com/tigusigalpa/bybit-php/issues) • [Request Feature](https://github.com/tigusigalpa/bybit-php/issues) • [Documentation](https://bybit-exchange.github.io/docs/v5/guide)

</div>
