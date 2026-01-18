# Laravel Examples

This directory contains Laravel-specific examples for using the Bybit PHP SDK.

## Examples

- **[TradingController.php](TradingController.php)** - Controller example with dependency injection
- **[BybitServiceExample.php](BybitServiceExample.php)** - Service class example
- **[BybitWebSocketCommand.php](BybitWebSocketCommand.php)** - Artisan command for WebSocket
- **[routes.example.php](routes.example.php)** - Example API routes

## Usage

These examples show best practices for integrating Bybit SDK in Laravel applications:

1. **Dependency Injection** - Inject `BybitClient` into controllers and services
2. **Facades** - Use the `Bybit` facade for quick access
3. **Commands** - Run WebSocket listeners as background processes
4. **Services** - Encapsulate trading logic in service classes

## Installation

Make sure you've published the config:

```bash
php artisan vendor:publish --tag=bybit-config
```

Configure your `.env`:

```env
BYBIT_API_KEY=your_key
BYBIT_API_SECRET=your_secret
BYBIT_TESTNET=true
```

## Running Examples

### WebSocket Command

```bash
php artisan bybit:websocket BTCUSDT
```

### API Routes

Add the routes to your `routes/api.php` and test with:

```bash
curl http://localhost/api/bybit/balance
curl http://localhost/api/bybit/ticker/BTCUSDT
```
