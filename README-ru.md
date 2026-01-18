# Bybit PHP — клиент V5 API с интеграцией Laravel 8–12

![ByBit PHP SDK](https://github.com/user-attachments/assets/cd31c2a6-5853-4287-a79b-2fb16ca3fcaa)

**🌐 Язык:** Русский | [English](README.md)

Лёгкая библиотека для работы с Bybit V5 API в проектах на чистом PHP и Laravel 8–12. Поддерживает тестовую торговлю (Testnet), выбор региональных эндпоинтов для Mainnet и встроенную авторизацию с подписью запросов (HMAC-SHA256 или RSA-SHA256).

## Возможности
- Клиент `BybitClient` с универсальным методом `request()` для `GET/POST`
- Подпись запросов по правилам Bybit V5 (`X-BAPI-*` заголовки)
- Выбор окружения: Testnet или Mainnet с региональными эндпоинтами
- Интеграция с Laravel: сервис-провайдер, фасад, публикуемый конфиг
- Настраиваемое `recv_window` и тип подписи (`hmac`/`rsa`)

## Требования
- PHP `^7.4|^8.0|^8.1|^8.2`
- Laravel `8–12` (для интеграции в фреймворк)

## Установка (локальный путь в монорепозитории)
1. Добавьте репозиторий типа `path` в корневой `composer.json` вашего проекта:
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

## Интеграция с Laravel
- Пакет использует авто‑обнаружение провайдера и алиаса фасада.
- Опубликуйте конфиг:
  ```bash
  php artisan vendor:publish --tag=bybit-config
  ```

## Конфигурация
Настройки находятся в `config/bybit.php` и управляются через `.env`:
- `BYBIT_API_KEY` — публичный ключ API
- `BYBIT_API_SECRET` — секретный ключ (для HMAC)
- `BYBIT_TESTNET` — `true/false` для включения тестовой среды
- `BYBIT_REGION` — `global|nl|tr|kz|ge|ae`
- `BYBIT_RECV_WINDOW` — окно приёма запроса в мс (по умолчанию `5000`)
- `BYBIT_SIGNATURE` — `hmac` или `rsa` (по умолчанию `hmac`)
- `BYBIT_RSA_PRIVATE_KEY` — приватный RSA ключ (PEM) для подписи RSA

Пример `.env`:
```env
BYBIT_API_KEY=your_api_key
BYBIT_API_SECRET=your_api_secret
BYBIT_TESTNET=true
BYBIT_REGION=global
BYBIT_RECV_WINDOW=5000
BYBIT_SIGNATURE=hmac
# Для RSA (если используется):
# BYBIT_SIGNATURE=rsa
# BYBIT_RSA_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----"
```

## Эндпоинты и регионы
- Testnet: `https://api-testnet.bybit.com`
- Mainnet по регионам:
  - Netherlands (`nl`): `https://api.bybit.nl`
  - Turkey (`tr`): `https://api.bybit-tr.com`
  - Kazakhstan (`kz`): `https://api.bybit.kz`
  - Georgia (`ge`): `https://api.bybitgeorgia.ge`
  - United Arab Emirates (`ae`): `https://api.bybit.ae`
- Иначе: `https://api.bybit.com` (доступен также `https://api.bytick.com`)

## Аутентификация и подпись
Правила формирования подписи соответствуют руководству Bybit V5:
- Для `GET`: `timestamp + api_key + recv_window + queryString`
- Для `POST`: `timestamp + api_key + recv_window + jsonBodyString`
- `HMAC-SHA256` → hex в нижнем регистре
- `RSA-SHA256` → base64

Заголовки запроса:
- `X-BAPI-API-KEY`
- `X-BAPI-TIMESTAMP` (UTC, миллисекунды)
- `X-BAPI-RECV-WINDOW` (мс)
- `X-BAPI-SIGN`
 - `X-BAPI-SIGN-TYPE: 2` для HMAC
 - `Content-Type: application/json` для `POST`

Документация: https://bybit-exchange.github.io/docs/v5/guide

## Использование
Через фасад:
```php
use Tigusigalpa\ByBit\Facades\Bybit;

$response = Bybit::request('GET', '/v5/order/realtime', [
    'category' => 'option',
    'symbol' => 'BTC-29JUL22-25000-C'
]);
```

Через DI (рекомендуется для тестирования):
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

## Отладка и стабильность
- Синхронизируйте время сервера (NTP), так как окно валидации: `server_time - recv_window <= timestamp < server_time + 1000`.
- При сетевых проблемах можно добавлять `cdn-request-id` (уникальный) в заголовки — для диагностики CDN.
- При ошибках подписи проверьте сортировку параметров и формат тела для `POST` (`JSON_UNESCAPED_SLASHES`).

## Версии и изменения
- Текущая версия: `0.1.0`
- История изменений: `CHANGELOG.md`

## Автор и лицензия
- Автор: Igor Sazonov (`tigusigalpa`)
- Email: `sovletig@gmail.com`
- GitHub: https://github.com/tigusigalpa/bybit-php
- Лицензия: MIT

## План развития
- Удобные методы-обёртки для популярных эндпоинтов (`server-time`, `orders`, `positions`)
- Ретраи и обработка rate-limit
- Логирование и трейсинг запросов

## Методы-обёртки

```php
use Tigusigalpa\ByBit\Facades\Bybit;

// Время сервера
$time = Bybit::getServerTime();

// Тикеры рынка
$tickers = Bybit::getTickers([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);

// Создание ордера
$order = Bybit::createOrder([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'side' => 'Buy',
    'orderType' => 'Limit',
    'qty' => '0.01',
    'price' => '30000',
    'timeInForce' => 'GTC'
]);

// Открытые ордера
$open = Bybit::getOpenOrders([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);

// Отмена ордера
$cancel = Bybit::cancelOrder([
    'category' => 'linear',
    'symbol' => 'BTCUSDT',
    'orderId' => 'xxxxxxxxxxxxxxxxxxxx'
]);

// Баланс кошелька
$balance = Bybit::getWalletBalance([
    'accountType' => 'UNIFIED',
    'coin' => 'USDT'
]);

// Позиции
$positions = Bybit::getPositions([
    'category' => 'linear',
    'symbol' => 'BTCUSDT'
]);
```

### Универсальный метод создания ордера

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
    size: 100, // маржа в USDT
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
    size: 150, // маржа в USDT
    slTp: [
        'type' => 'absolute',
        'takeProfit' => 30500.0,
        'stopLoss' => 29000.0
    ],
    extra: [
        // дополнительные поля для гибкости
        'timeInForce' => 'GTC'
    ]
);
```

Параметры:
- `type`: `spot` или `derivatives`
- `symbol`: код инструмента, например `BTCUSDT`
- `execution`: `market` | `limit` | `trigger`
- `price`: цена входа для `limit`, либо `triggerPrice` для `trigger`; для `market` — `null`
- `side`: `Buy`/`Sell`; для `spot` по умолчанию `Buy`
- `leverage`: плечо (только для `derivatives`); при задании устанавливается через `/v5/position/set-leverage`
- `size`: для `spot` — количество; для `derivatives` — сумма маржи в котируемой валюте (например, USDT)
- `slTp`: `['type'=>'absolute'|'percent','takeProfit'=>..., 'stopLoss'=>...]` — для деривативов; проценты считаются от цены входа
- `extra`: ассоциативный массив дополнительных параметров (например, `timeInForce`, `orderLinkId`)

Примечания:
- Для деривативов количество вычисляется как `qty = margin * leverage / price`. Для `market` цена берётся из `/v5/market/tickers`.
- Для `trigger` направленность триггера выставляется по `side`: `Buy → triggerDirection=1`, `Sell → triggerDirection=2`.

Через DI интерфейс:
```php
use Tigusigalpa\ByBit\BybitClient;

public function __construct(BybitClient $bybit)
{
    $this->bybit = $bybit;
}

$serverTime = $this->bybit->getServerTime();
$order = $this->bybit->createOrder([...]);
```

### Расчёт торговой комиссии

```php
use Tigusigalpa\ByBit\Facades\Bybit;

// Для Spot (по умолчанию Non-VIP, taker)
$feeSpot = Bybit::computeFee('spot', 1000.0, 'Non-VIP', 'taker'); // 1000 * 0.001 = 1.0

// Для деривативов: объём = маржа * плечо
$margin = 100.0;
$leverage = 5.0;
$volume = $margin * $leverage; // 500
$feeDeriv = Bybit::computeFee('derivatives', $volume, 'Non-VIP', 'taker');
```

Таблица базовых ставок задаётся в конфиге `config/bybit.php` в секции `fees`. Можно изменять уровни и ставки под актуальные данные аккаунта или использовать запросы Bybit для получения точной ставки аккаунта.