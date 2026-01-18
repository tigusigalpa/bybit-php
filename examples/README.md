# Bybit PHP SDK - Examples

This directory contains practical examples demonstrating how to use the Bybit PHP SDK.

## 📋 Available Examples

### Basic Examples
- **[01-basic-client.php](01-basic-client.php)** - Initialize client and get server time
- **[02-market-data.php](02-market-data.php)** - Fetch market data and tickers
- **[03-account-info.php](03-account-info.php)** - Get wallet balance and account information

### Order Management
- **[04-place-order.php](04-place-order.php)** - Place different types of orders
- **[05-manage-orders.php](05-manage-orders.php)** - Get, amend, and cancel orders
- **[06-advanced-orders.php](06-advanced-orders.php)** - Advanced order placement with TP/SL

### Position Management
- **[07-positions.php](07-positions.php)** - Get and manage positions
- **[08-leverage.php](08-leverage.php)** - Set leverage and position mode
- **[09-trading-stops.php](09-trading-stops.php)** - Set take profit and stop loss

### WebSocket Examples
- **[10-websocket-public.php](10-websocket-public.php)** - Public WebSocket streams (orderbook, trades, ticker)
- **[11-websocket-private.php](11-websocket-private.php)** - Private WebSocket streams (positions, orders, wallet)
- **[12-websocket-advanced.php](12-websocket-advanced.php)** - Advanced WebSocket usage with multiple subscriptions

### Laravel Examples
- **[laravel/](laravel/)** - Laravel-specific examples with facades and DI

## 🚀 Quick Start

### 1. Configuration

Copy the example configuration file:

```bash
cp config.example.php config.php
```

Edit `config.php` with your API credentials:

```php
return [
    'api_key' => 'your_api_key_here',
    'api_secret' => 'your_api_secret_here',
    'testnet' => true, // Use testnet for testing
    'region' => 'global',
];
```

### 2. Install Dependencies

Make sure you've installed the package dependencies:

```bash
cd ../
composer install
```

### 3. Run Examples

```bash
php examples/01-basic-client.php
php examples/02-market-data.php
```

## ⚠️ Important Notes

- **Never commit your API keys!** The `config.php` file is gitignored.
- **Use Testnet first** - Set `testnet => true` in config to test without real funds.
- **Check rate limits** - Be mindful of Bybit's API rate limits.
- **Error handling** - All examples include basic error handling, but add more for production use.

## 📚 Documentation

For detailed API documentation, visit:
- [Bybit V5 API Documentation](https://bybit-exchange.github.io/docs/v5/guide)
- [Package README](../README.md)

## 🆘 Support

If you encounter issues:
1. Check the [main README](../README.md)
2. Review [Bybit API documentation](https://bybit-exchange.github.io/docs/v5/guide)
3. Open an issue on [GitHub](https://github.com/tigusigalpa/bybit-php/issues)
