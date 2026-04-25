<div align="center">

# Bybit PHP SDK

### V5 API клиент для PHP и Laravel

[![PHP Version](https://img.shields.io/badge/PHP-7.4%20%7C%208.0%20%7C%208.1%20%7C%208.2-blue.svg)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-8%20%7C%209%20%7C%2010%20%7C%2011%20%7C%2012%20%7C%2013-red.svg)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![WebSocket](https://img.shields.io/badge/WebSocket-Supported-brightgreen.svg)](https://bybit-exchange.github.io/docs/v5/ws/connect)

![ByBit PHP SDK](https://i.postimg.cc/T3PpqGyn/bybit-php-banner-v2.jpg)

**Язык:** [English](README.md) | Русский

> 📖 **[Полная документация в разделе Wiki](https://github.com/tigusigalpa/bybit-php/wiki)**

PHP-библиотека для работы с Bybit V5 API. Поддерживает REST и WebSocket, работает как standalone, так и с Laravel.

[Возможности](#возможности) • [Установка](#установка) • [Быстрый старт](#быстрый-старт) • [API методы](#api-методы) • [TradFi](#tradfi) • [WebSocket](#websocket-потоки) • [Примеры](#примеры)

</div>

---

## Возможности

- Полное покрытие Bybit V5 API (spot, linear, inverse, options)
- **Поддержка TradFi** — золото, серебро, валютные пары, CFD акций и индексы через `BybitTradFi`
- Подписи HMAC-SHA256 и RSA-SHA256
- WebSocket для данных в реальном времени (стакан, сделки, свечи, обновления аккаунта)
- Поддержка testnet и demo trading
- Региональные эндпоинты (NL, TR, KZ, GE, AE)
- Интеграция с Laravel (facade, service provider, публикация конфига)
- Автоматическое переподключение WebSocket

---

## Установка

### Чистый PHP (без Laravel)

```bash
composer require tigusigalpa/bybit-php
```

### Интеграция с Laravel

**Для локального монорепозитория:**

1. Добавьте репозиторий в `composer.json`:

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

2. Установите пакет:

```bash
composer require tigusigalpa/bybit-php:* --prefer-source
```

3. Опубликуйте конфигурацию:

```bash
php artisan vendor:publish --tag=bybit-config
```

Laravel auto-discovery регистрирует service provider и facade автоматически.

---

## Конфигурация

### Переменные окружения

Создайте или обновите файл `.env`:

```env
BYBIT_API_KEY=ваш_api_ключ
BYBIT_API_SECRET=ваш_секретный_ключ
BYBIT_TESTNET=true
BYBIT_DEMO_TRADING=false
BYBIT_REGION=global
BYBIT_RECV_WINDOW=5000
BYBIT_SIGNATURE=hmac

# Для RSA подписи (опционально):
# BYBIT_SIGNATURE=rsa
# BYBIT_RSA_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----"
```

### Параметры конфигурации

| Параметр                | Тип     | По умолчанию | Описание                                                       |
|-------------------------|---------|--------------|----------------------------------------------------------------|
| `BYBIT_API_KEY`         | string  | -            | Публичный ключ API Bybit                                       |
| `BYBIT_API_SECRET`      | string  | -            | Секретный ключ API Bybit                                       |
| `BYBIT_TESTNET`         | boolean | `false`      | Включить тестовое окружение                                    |
| `BYBIT_DEMO_TRADING`    | boolean | `false`      | Включить демо-трейдинг окружение                               |
| `BYBIT_REGION`          | string  | `global`     | Региональный эндпоинт (`global`, `nl`, `tr`, `kz`, `ge`, `ae`) |
| `BYBIT_RECV_WINDOW`     | integer | `5000`       | Окно приёма запроса (мс)                                       |
| `BYBIT_SIGNATURE`       | string  | `hmac`       | Тип подписи (`hmac` или `rsa`)                                 |
| `BYBIT_RSA_PRIVATE_KEY` | string  | `null`       | RSA приватный ключ (формат PEM)                                |

---

## Быстрый старт

### Использование на чистом PHP

```php
<?php
require_once 'vendor/autoload.php';

use Tigusigalpa\ByBit\BybitClient;

// Инициализация клиента
$client = new BybitClient(
    apiKey: 'ваш_api_ключ',
    apiSecret: 'ваш_секретный_ключ',
    testnet: true,
    region: 'global',
    recvWindow: 5000,
    signature: 'hmac'
);

// Получить время сервера
$serverTime = $client->getServerTime();
echo "Время сервера: " . json_encode($serverTime) . "\n";

// Получить рыночные тикеры
$tickers = $client->getTickers([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);
print_r($tickers);

// Разместить лимитный ордер
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

// Получить позиции
$positions = $client->getPositions([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);
print_r($positions);
```

### Использование с Laravel

**Через фасад:**

```php
use Tigusigalpa\ByBit\Facades\Bybit;

// Получить время сервера
$time = Bybit::getServerTime();

// Получить рыночные данные
$tickers = Bybit::getTickers([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);

// Разместить ордер
$order = Bybit::createOrder([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'side' => 'Buy',
    'orderType' => 'Limit',
    'qty' => '0.01',
    'price' => '30000'
]);
```

**Через внедрение зависимостей:**

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

## API Методы

### Рыночные данные

```php
// Получить время сервера
$time = $client->getServerTime();

// Получить рыночные тикеры
$tickers = $client->getTickers([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);

// Получить данные свечей/kline
$klines = $client->getKline([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'interval' => '60',  // 1m, 3m, 5m, 15m, 30m, 60m, 120m, 240m, 360m, 720m, D, W, M
    'limit' => 200
]);

// Получить глубину стакана
$orderbook = $client->getOrderbook([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'limit' => 50  // Макс. глубина: 1, 25, 50, 100, 200, 500
]);

// Получить RPI (Risk Premium Index) стакан
$rpiOrderbook = $client->getRPIOrderbook([
    'category' => 'option',
    'symbol' => 'BTC-30DEC23-80000-C',
    'limit' => 25
]);

// Получить открытый интерес
$openInterest = $client->getOpenInterest([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'intervalTime' => '5min'  // 5min, 15min, 30min, 1h, 4h, 1d
]);

// Получить последние публичные сделки
$recentTrades = $client->getRecentTrades([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'limit' => 60
]);

// Получить историю ставок финансирования
$fundingHistory = $client->getFundingRateHistory([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'limit' => 200
]);

// Получить историческую волатильность (опционы)
$historicalVolatility = $client->getHistoricalVolatility([
    'category' => 'option',
    'baseCoin' => 'BTC',
    'period' => 7  // 7, 14, 21, 30, 60, 90, 180, 270 дней
]);

// Получить данные страхового фонда
$insurance = $client->getInsurancePool([
    'coin' => 'USDT'
]);

// Получить лимит риска
$riskLimit = $client->getRiskLimit([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);
```

### Управление ордерами

```php
// Создать ордер
$order = $client->createOrder([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'side' => 'Buy',
    'orderType' => 'Limit',
    'qty' => '0.01',
    'price' => '30000',
    'timeInForce' => 'GTC'
]);

// Получить открытые ордера
$openOrders = $client->getOpenOrders([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);

// Изменить ордер
$amended = $client->amendOrder([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'orderId' => 'id_ордера',
    'qty' => '0.02',
    'price' => '31000'
]);

// Отменить ордер
$cancelled = $client->cancelOrder([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'orderId' => 'id_ордера'
]);

// Отменить все ордера
$cancelledAll = $client->cancelAllOrders([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);

// Получить историю ордеров
$history = $client->getHistoryOrders([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'limit' => 50
]);
```

### Управление позициями

```php
// Получить позиции
$positions = $client->getPositions([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);

// Установить плечо
$client->setLeverage('linear', 'BTCUSDT', 10);

// Переключить режим позиции (односторонний или хедж)
$client->switchPositionMode([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'mode' => 0 // 0: односторонний, 3: хедж
]);

// Установить торговые стопы (TP/SL)
$client->setTradingStop([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'positionIdx' => 0,
    'takeProfit' => '35000',
    'stopLoss' => '28000'
]);

// Установить автодобавление маржи
$client->setAutoAddMargin([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'autoAddMargin' => 1,  // 0: выкл, 1: вкл
    'positionIdx' => 0
]);

// Вручную добавить или уменьшить маржу
$client->addOrReduceMargin([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'margin' => '100',  // положительное для добавления, отрицательное для уменьшения
    'positionIdx' => 0
]);

// Получить закрытый P&L (последние 2 года)
$closedPnl = $client->getClosedPnL([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'limit' => 50
]);

// Получить закрытые опционные позиции (последние 6 месяцев)
$closedOptions = $client->getClosedOptionsPositions([
    'category' => 'option',
    'symbol' => 'BTC-30DEC23-80000-C',
    'limit' => 50
]);

// Переместить позицию между UID
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

// Получить историю перемещений позиций
$moveHistory = $client->getMovePositionHistory([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'limit' => 50
]);

// Подтвердить новый лимит риска
$confirmRisk = $client->confirmNewRiskLimit([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);
```

### Аккаунт и кошелёк

```php
// Получить баланс кошелька
$balance = $client->getWalletBalance([
    'accountType' => 'UNIFIED',
    'coin' => 'USDT'
]);

// Получить доступную для перевода сумму (Unified аккаунт)
$transferable = $client->getTransferableAmount([
    'accountType' => 'UNIFIED',
    'coin' => 'USDT'
]);

// Получить журнал транзакций
$transactions = $client->getTransactionLog([
    'accountType' => 'UNIFIED',
    'category' => 'linear',
    'limit' => 50
]);

// Получить информацию об аккаунте
$accountInfo = $client->getAccountInfo();

// Получить информацию об инструментах аккаунта
$instrumentsInfo = $client->getAccountInstrumentsInfo([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);

// Рассчитать торговую комиссию
$fee = $client->computeFee('spot', 1000.0, 'Non-VIP', 'taker');
```

### Демо-трейдинг

Демо-трейдинг — тестирование стратегий без риска. Используется домен `api-demo.bybit.com`.

```php
// Инициализация клиента для демо-трейдинга
$demoClient = new BybitClient(
    apiKey: 'ваш_демо_api_ключ',
    apiSecret: 'ваш_демо_секретный_ключ',
    testnet: false,
    region: 'global',
    recvWindow: 5000,
    signature: 'hmac',
    rsaPrivateKey: null,
    http: null,
    fees: null,
    demoTrading: true  // Включить режим демо-трейдинга
);

// Создать демо-аккаунт (используйте продакшн API ключ с api.bybit.com)
$productionClient = new BybitClient('prod_key', 'prod_secret');
$demoAccount = $productionClient->createDemoAccount();
// Возвращает: ['uid' => '123456789', ...]

// Запросить демо-средства (используйте демо API ключ с api-demo.bybit.com)
$fundingResult = $demoClient->requestDemoFunds([
    'adjustType' => 0,  // 0: добавить, 1: уменьшить
    'utaDemoApplyMoney' => [
        ['coin' => 'USDT', 'amountStr' => '10000'],
        ['coin' => 'BTC', 'amountStr' => '1']
    ]
]);

// Все торговые методы работают одинаково в демо-режиме
$order = $demoClient->createOrder([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'side' => 'Buy',
    'orderType' => 'Market',
    'qty' => '0.01'
]);
```

**Laravel Демо-трейдинг:**

```php
// В файле .env
BYBIT_DEMO_TRADING=true

// Используйте как обычно
$balance = Bybit::getWalletBalance(['accountType' => 'UNIFIED']);
```

---

## WebSocket потоки

### Публичные потоки

**Чистый PHP WebSocket:**

```php
use Tigusigalpa\ByBit\BybitWebSocket;

// Создать WebSocket экземпляр
$ws = new BybitWebSocket(
    apiKey: null,
    apiSecret: null,
    testnet: false,
    region: 'global',
    isPrivate: false
);

// Подписаться на стакан
$ws->subscribeOrderbook('BTCUSDT', 50);

// Подписаться на сделки
$ws->subscribeTrade('BTCUSDT');

// Подписаться на тикер
$ws->subscribeTicker('BTCUSDT');

// Подписаться на свечи
$ws->subscribeKline('BTCUSDT', '1'); // 1м свечи

// Обработка сообщений
$ws->onMessage(function($data) {
    if (isset($data['topic'])) {
        echo "Топик: {$data['topic']}\n";
        print_r($data['data']);
    }
});

// Начать прослушивание (блокирующее)
$ws->listen();
```

### Приватные потоки

**Аутентифицированный WebSocket для обновлений аккаунта:**

```php
use Tigusigalpa\ByBit\BybitWebSocket;

$ws = new BybitWebSocket(
    apiKey: 'ваш_api_ключ',
    apiSecret: 'ваш_секретный_ключ',
    testnet: false,
    region: 'global',
    isPrivate: true
);

// Подписаться на обновления позиций
$ws->subscribePosition();

// Подписаться на обновления ордеров
$ws->subscribeOrder();

// Подписаться на обновления исполнений
$ws->subscribeExecution();

// Подписаться на обновления кошелька
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

**Laravel фоновая команда:**

```php
// app/Console/Commands/BybitWebSocketListener.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Tigusigalpa\ByBit\BybitWebSocket;

class BybitWebSocketListener extends Command
{
    protected $signature = 'bybit:listen {symbol=BTCUSDT}';
    protected $description = 'Прослушивание WebSocket потоков Bybit';

    public function handle()
    {
        $symbol = $this->argument('symbol');
        $ws = app(BybitWebSocket::class);
        
        $ws->subscribeOrderbook($symbol, 50);
        $ws->subscribeTrade($symbol);
        
        $ws->onMessage(fn($data) => 
            $this->info(json_encode($data, JSON_PRETTY_PRINT))
        );
        
        $this->info("🚀 WebSocket слушатель запущен для {$symbol}...");
        $ws->listen();
    }
}
```

Запуск: `php artisan bybit:listen BTCUSDT`

---

## TradFi

Класс `BybitTradFi` предоставляет удобный интерфейс для торговли традиционными финансовыми инструментами — золотом, серебром, валютными парами, CFD на акции и индексы. Все они доступны на Bybit как линейные бессрочные контракты через стандартный V5 API.

Создайте экземпляр, передав существующий `BybitClient`:

```php
use Tigusigalpa\ByBit\BybitClient;
use Tigusigalpa\ByBit\BybitTradFi;

$client = new BybitClient(
    apiKey: 'ваш_api_ключ',
    apiSecret: 'ваш_секретный_ключ',
    testnet: true
);

$tradfi = new BybitTradFi($client);
```

### Предопределённые списки символов

```php
BybitTradFi::METALS;       // ['XAUUSD', 'XAGUSD', 'XPTUSD']
BybitTradFi::FOREX_MAJORS; // ['EURUSD', 'GBPUSD', 'USDJPY', 'USDCHF', 'AUDUSD', 'NZDUSD', 'USDCAD']
BybitTradFi::FOREX_MINORS; // ['EURGBP', 'EURJPY', 'GBPJPY', ...]
BybitTradFi::US_STOCKS;    // ['AAPLUSDT', 'TSLAUSDT', 'NVDAUSDT', ...]
BybitTradFi::INDICES;      // ['US500USD', 'US100USD', 'US30USD', 'DE40USD', 'JP225USD', ...]
```

### Рыночные данные

```php
// Получить инструменты с фильтром по классу активов:
// 'metal', 'forex', 'stock', 'index', 'commodity' или '' для всех
$instruments = $tradfi->getInstruments('forex');

// Тикер одного символа
$gold = $tradfi->getTicker('XAUUSD');
echo $gold['result']['list'][0]['lastPrice'];

// Шорткаты для групп инструментов
$metals  = $tradfi->getMetalsTickers();  // XAUUSD, XAGUSD, XPTUSD
$forex   = $tradfi->getForexTickers();   // основные валютные пары
$stocks  = $tradfi->getStockTickers();   // CFD на акции США
$indices = $tradfi->getIndexTickers();   // US500USD, DE40USD и др.

// Данные свечей (интервал: 1, 3, 5, 15, 30, 60, 120, 240, 360, 720, D, W, M)
$klines = $tradfi->getKline('XAUUSD', '60', 50);

// Глубина стакана (1, 25, 50, 100, 200)
$orderbook = $tradfi->getOrderbook('EURUSD', 25);

// Информация о свопе (ночная комиссия за перенос позиции)
$swap = $tradfi->getSwapFee('XAUUSD');

// Ставка торговой комиссии
$fee = $tradfi->getFeeRate('EURUSD');
```

### Торговля

```php
// Разместить лимитный ордер на покупку золота с TP/SL
$order = $tradfi->placeOrder(
    symbol:    'XAUUSD',
    side:      'Buy',
    orderType: 'Limit',
    qty:       '0.01',
    price:     '3200',
    extra: [
        'timeInForce' => 'GTC',
        'takeProfit'  => '3350',
        'stopLoss'    => '3100',
    ]
);

// Рыночный ордер на продажу EURUSD
$order = $tradfi->placeOrder('EURUSD', 'Sell', 'Market', '1');

// Закрыть открытую длинную позицию по рынку
$tradfi->closePosition('XAUUSD', 'Buy', '0.01');

// Установить плечо
$tradfi->setLeverage('XAUUSD', 10);

// Отменить ордер
$tradfi->cancelOrder('XAUUSD', orderId: 'abc123');

// Открытые ордера
$orders = $tradfi->getOpenOrders('XAUUSD');

// История ордеров и сделок
$history = $tradfi->getOrderHistory('EURUSD', limit: 50);
$trades  = $tradfi->getTradeHistory('XAUUSD', limit: 50);

// Открытые позиции (передайте '' для всех TradFi позиций)
$positions = $tradfi->getPositions('XAUUSD');
```

### Хелпер для определения символа

```php
BybitTradFi::isTradFiSymbol('XAUUSD');   // true  — золото
BybitTradFi::isTradFiSymbol('EURUSD');   // true  — форекс
BybitTradFi::isTradFiSymbol('US500USD'); // true  — индекс
BybitTradFi::isTradFiSymbol('BTCUSDT');  // false — крипто
BybitTradFi::isTradFiSymbol('ETHUSDT');  // false — крипто

// Отфильтровать TradFi позиции из смешанного списка
$allPositions = $client->getPositions(['category' => 'linear']);
$tradfiOnly   = array_filter(
    $allPositions['result']['list'] ?? [],
    fn($p) => BybitTradFi::isTradFiSymbol($p['symbol'])
);
```

> **Важно:** TradFi инструменты торгуются по **расписанию рынка** (в отличие от крипты, которая работает 24/7). Вне торговых сессий API может возвращать пустой стакан или устаревшие цены. При удержании позиции через конец торгового дня начисляется своп — используйте `getSwapFee()` для проверки ставки перед открытием позиции.

---

## Продвинутое использование

### Универсальное размещение ордеров

```php
// Спотовый лимитный ордер
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

// Деривативный рыночный ордер с плечом
$order = $client->placeOrder(
    type: 'derivatives',
    symbol: 'BTCUSDT',
    execution: 'market',
    price: null,
    side: 'Buy',
    leverage: 10,
    size: 100, // маржа в USDT
    slTp: [
        'type' => 'percent',
        'takeProfit' => 0.02, // 2%
        'stopLoss' => 0.01    // 1%
    ]
);

// Триггерный ордер с абсолютным TP/SL
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

### Расчёт торговой комиссии

```php
// Комиссия на споте
$feeSpot = $client->computeFee('spot', 1000.0, 'Non-VIP', 'taker');
// Результат: 1.0 USDT (0.1%)

// Деривативы с плечом
$margin = 100.0;
$leverage = 10.0;
$volume = $margin * $leverage; // 1000
$feeDeriv = $client->computeFee('derivatives', $volume, 'VIP1', 'maker');
```

---

## Региональные эндпоинты

| Регион          | Код      | Эндпоинт                        |
|-----------------|----------|---------------------------------|
| 🌐 Глобальный   | `global` | `https://api.bybit.com`         |
| 🇳🇱 Нидерланды | `nl`     | `https://api.bybit.nl`          |
| 🇹🇷 Турция     | `tr`     | `https://api.bybit-tr.com`      |
| 🇰🇿 Казахстан  | `kz`     | `https://api.bybit.kz`          |
| 🇬🇪 Грузия     | `ge`     | `https://api.bybitgeorgia.ge`   |
| 🇦🇪 ОАЭ        | `ae`     | `https://api.bybit.ae`          |
| 🧪 Testnet      | -        | `https://api-testnet.bybit.com` |

---

## Аутентификация

### Генерация подписи

Bybit V5 API использует HMAC-SHA256 или RSA-SHA256 для подписи запросов:

**Для GET запросов:**

```
signature_payload = timestamp + api_key + recv_window + queryString
```

**Для POST запросов:**

```
signature_payload = timestamp + api_key + recv_window + jsonBody
```

**HMAC-SHA256:** Возвращает hex в нижнем регистре  
**RSA-SHA256:** Возвращает base64

### Необходимые заголовки

```
X-BAPI-API-KEY: ваш_api_ключ
X-BAPI-TIMESTAMP: 1234567890000
X-BAPI-RECV-WINDOW: 5000
X-BAPI-SIGN: сгенерированная_подпись
X-BAPI-SIGN-TYPE: 2 (для HMAC)
Content-Type: application/json (для POST)
```

Официальная документация: https://bybit-exchange.github.io/docs/v5/guide

---

## Примеры

### Пример торгового бота

```php
<?php
require 'vendor/autoload.php';

use Tigusigalpa\ByBit\BybitClient;

$client = new BybitClient(
    apiKey: getenv('BYBIT_API_KEY'),
    apiSecret: getenv('BYBIT_API_SECRET'),
    testnet: true
);

// Проверить баланс
$balance = $client->getWalletBalance([
    'accountType' => 'UNIFIED',
    'coin' => 'USDT'
]);

echo "Баланс: {$balance['result']['list'][0]['totalWalletBalance']} USDT\n";

// Получить текущую цену
$ticker = $client->getTickers([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);

$currentPrice = $ticker['result']['list'][0]['lastPrice'];
echo "Цена BTC: \${$currentPrice}\n";

// Разместить ордер
$order = $client->createOrder([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'side' => 'Buy',
    'orderType' => 'Limit',
    'qty' => '0.01',
    'price' => (string)($currentPrice * 0.99), // на 1% ниже рынка
    'timeInForce' => 'GTC'
]);

echo "Ордер размещён: {$order['result']['orderId']}\n";
```

---

## Вклад в проект

Pull Requestы приветствуются.

1. Форкните репозиторий
2. Создайте ветку функции (`git checkout -b feature/AmazingFeature`)
3. Закоммитьте изменения (`git commit -m 'Add some AmazingFeature'`)
4. Запушьте в ветку (`git push origin feature/AmazingFeature`)
5. Откройте Pull Request

---

## Лицензия

**MIT License**

Copyright (c) 2026 Igor Sazonov

- **Автор:** Igor Sazonov (`tigusigalpa`)
- **Email:** sovletig@gmail.com
- **GitHub:** https://github.com/tigusigalpa/bybit-php

---

<div align="center">

[Сообщить об ошибке](https://github.com/tigusigalpa/bybit-php/issues) • [Запросить функцию](https://github.com/tigusigalpa/bybit-php/issues) • [Документация](https://bybit-exchange.github.io/docs/v5/guide)

</div>
