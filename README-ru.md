<div align="center">

# 🚀 Bybit PHP SDK

### Профессиональный клиент V5 API для PHP и Laravel

[![PHP Version](https://img.shields.io/badge/PHP-7.4%20%7C%208.0%20%7C%208.1%20%7C%208.2-blue.svg)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-8%20%7C%209%20%7C%2010%20%7C%2011%20%7C%2012-red.svg)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![WebSocket](https://img.shields.io/badge/WebSocket-Поддерживается-brightgreen.svg)](https://bybit-exchange.github.io/docs/v5/ws/connect)

![ByBit PHP SDK](https://github.com/user-attachments/assets/cd31c2a6-5853-4287-a79b-2fb16ca3fcaa)

**🌐 Язык:** [English](README.md) | [Русский](#)

*Мощная, легковесная библиотека для бесшовной интеграции с Bybit V5 API в проектах на чистом PHP и Laravel.*

[Возможности](#-возможности) • [Установка](#-установка) • [Быстрый старт](#-быстрый-старт) • [Документация](#-api-методы) • [WebSocket](#-websocket-потоки) • [Примеры](#-примеры)

</div>

---

## 📋 Содержание

- [✨ Возможности](#-возможности)
- [📦 Установка](#-установка)
  - [Чистый PHP](#чистый-php-без-laravel)
  - [Интеграция с Laravel](#интеграция-с-laravel)
- [⚙️ Конфигурация](#️-конфигурация)
- [🚀 Быстрый старт](#-быстрый-старт)
  - [Использование на чистом PHP](#использование-на-чистом-php)
  - [Использование с Laravel](#использование-с-laravel)
- [📚 API Методы](#-api-методы)
  - [Рыночные данные](#рыночные-данные)
  - [Управление ордерами](#управление-ордерами)
  - [Управление позициями](#управление-позициями)
  - [Аккаунт и кошелёк](#аккаунт-и-кошелёк)
- [🌐 WebSocket потоки](#-websocket-потоки)
  - [Публичные потоки](#публичные-потоки)
  - [Приватные потоки](#приватные-потоки)
- [💡 Продвинутое использование](#-продвинутое-использование)
- [🌍 Региональные эндпоинты](#-региональные-эндпоинты)
- [🔐 Аутентификация](#-аутентификация)
- [📖 Примеры](#-примеры)
- [🤝 Вклад в проект](#-вклад-в-проект)
- [📄 Лицензия](#-лицензия)

---

## ✨ Возможности

<table>
<tr>
<td width="50%">

### 🎯 Основные возможности
- ✅ Полная поддержка Bybit V5 API
- ✅ Подпись HMAC-SHA256 и RSA-SHA256
- ✅ Окружения Testnet и Mainnet
- ✅ Выбор региональных эндпоинтов
- ✅ Совместимость с PHP и Laravel
- ✅ Типобезопасная обработка запросов

</td>
<td width="50%">

### ⚡ Продвинутые возможности
- ✅ WebSocket потоки в реальном времени
- ✅ Автоматическое переподключение
- ✅ Множественные подписки на топики
- ✅ Настраиваемый recv_window
- ✅ Комплексная обработка ошибок
- ✅ Фасады Laravel и DI поддержка

</td>
</tr>
</table>

---

## 📦 Установка

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
    { "type": "path", "url": "public_html/packages/bybit-php" }
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

> 💡 **Примечание:** Пакет использует автообнаружение Laravel для регистрации провайдера и фасада.

---

## ⚙️ Конфигурация

### Переменные окружения

Создайте или обновите файл `.env`:

```env
BYBIT_API_KEY=ваш_api_ключ
BYBIT_API_SECRET=ваш_секретный_ключ
BYBIT_TESTNET=true
BYBIT_REGION=global
BYBIT_RECV_WINDOW=5000
BYBIT_SIGNATURE=hmac

# Для RSA подписи (опционально):
# BYBIT_SIGNATURE=rsa
# BYBIT_RSA_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----"
```

### Параметры конфигурации

| Параметр | Тип | По умолчанию | Описание |
|----------|-----|--------------|----------|
| `BYBIT_API_KEY` | string | - | Публичный ключ API Bybit |
| `BYBIT_API_SECRET` | string | - | Секретный ключ API Bybit |
| `BYBIT_TESTNET` | boolean | `false` | Включить тестовое окружение |
| `BYBIT_REGION` | string | `global` | Региональный эндпоинт (`global`, `nl`, `tr`, `kz`, `ge`, `ae`) |
| `BYBIT_RECV_WINDOW` | integer | `5000` | Окно приёма запроса (мс) |
| `BYBIT_SIGNATURE` | string | `hmac` | Тип подписи (`hmac` или `rsa`) |
| `BYBIT_RSA_PRIVATE_KEY` | string | `null` | RSA приватный ключ (формат PEM) |

---

## 🚀 Быстрый старт

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

## 📚 API Методы

### Рыночные данные

```php
// Получить время сервера
$time = $client->getServerTime();

// Получить рыночные тикеры
$tickers = $client->getTickers([
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
```

### Аккаунт и кошелёк

```php
// Получить баланс кошелька
$balance = $client->getWalletBalance([
    'accountType' => 'UNIFIED',
    'coin' => 'USDT'
]);

// Рассчитать торговую комиссию
$fee = $client->computeFee('spot', 1000.0, 'Non-VIP', 'taker');
```

---

## 🌐 WebSocket потоки

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

## 💡 Продвинутое использование

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

## 🌍 Региональные эндпоинты

| Регион | Код | Эндпоинт |
|--------|-----|----------|
| 🌐 Глобальный | `global` | `https://api.bybit.com` |
| 🇳🇱 Нидерланды | `nl` | `https://api.bybit.nl` |
| 🇹🇷 Турция | `tr` | `https://api.bybit-tr.com` |
| 🇰🇿 Казахстан | `kz` | `https://api.bybit.kz` |
| 🇬🇪 Грузия | `ge` | `https://api.bybitgeorgia.ge` |
| 🇦🇪 ОАЭ | `ae` | `https://api.bybit.ae` |
| 🧪 Testnet | - | `https://api-testnet.bybit.com` |

---

## 🔐 Аутентификация

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

> 📖 **Официальная документация:** https://bybit-exchange.github.io/docs/v5/guide

---

## 📖 Примеры

### Полный пример торгового бота

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

## 🤝 Вклад в проект

Приветствуются любые вклады! Не стесняйтесь отправлять Pull Request.

1. Форкните репозиторий
2. Создайте ветку функции (`git checkout -b feature/AmazingFeature`)
3. Закоммитьте изменения (`git commit -m 'Add some AmazingFeature'`)
4. Запушьте в ветку (`git push origin feature/AmazingFeature`)
5. Откройте Pull Request

---

## 📄 Лицензия

**MIT License**

Copyright (c) 2026 Igor Sazonov

- **Автор:** Igor Sazonov (`tigusigalpa`)
- **Email:** sovletig@gmail.com
- **GitHub:** https://github.com/tigusigalpa/bybit-php

---

<div align="center">

### 🌟 Поставьте звезду этому репозиторию, если он вам помог!

**Сделано с ❤️ для крипто-трейдинг сообщества**

[Сообщить об ошибке](https://github.com/tigusigalpa/bybit-php/issues) • [Запросить функцию](https://github.com/tigusigalpa/bybit-php/issues) • [Документация](https://bybit-exchange.github.io/docs/v5/guide)

</div>