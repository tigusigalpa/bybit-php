# Installation and Setup Guide

## Prerequisites

- PHP 7.4 or higher
- Composer
- Laravel 8-12 (for Laravel integration)

## Installation Steps

### 1. Add Repository to composer.json

Add the package repository to your project's root `composer.json`:

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

### 2. Install the Package

```bash
composer require tigusigalpa/bybit-php:* --prefer-source
```

### 3. Publish Configuration (Laravel)

```bash
php artisan vendor:publish --tag=bybit-config
```

This will create `config/bybit.php` in your Laravel application.

### 4. Configure Environment Variables

Add the following to your `.env` file:

```env
BYBIT_API_KEY=your_api_key_here
BYBIT_API_SECRET=your_api_secret_here
BYBIT_TESTNET=true
BYBIT_REGION=global
BYBIT_RECV_WINDOW=5000
BYBIT_SIGNATURE=hmac
```

#### Environment Variables Explained

- **BYBIT_API_KEY**: Your Bybit API public key
- **BYBIT_API_SECRET**: Your Bybit API secret key
- **BYBIT_TESTNET**: Set to `true` for testnet, `false` for mainnet
- **BYBIT_REGION**: Regional endpoint (`global`, `nl`, `tr`, `kz`, `ge`, `ae`)
- **BYBIT_RECV_WINDOW**: Request receive window in milliseconds (default: 5000)
- **BYBIT_SIGNATURE**: Signature type (`hmac` or `rsa`)

For RSA signature (optional):
```env
BYBIT_SIGNATURE=rsa
BYBIT_RSA_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\nYourKeyHere\n-----END PRIVATE KEY-----"
```

## Getting API Keys

1. Log in to your Bybit account
2. Go to **API Management**
3. Create a new API key
4. Set appropriate permissions:
   - **Read**: For market data and account info
   - **Trade**: For placing/canceling orders
   - **Wallet**: For wallet operations
5. Save your API key and secret securely

⚠️ **Security Note**: Never commit your API keys to version control. Always use environment variables.

## Verification

Test your installation:

```php
use Tigusigalpa\ByBit\Facades\Bybit;

// Get server time
$time = Bybit::getServerTime();
print_r($time);
```

If successful, you should see the server time response.

## Regional Endpoints

Choose the appropriate region based on your location:

- **Global**: `global` (default) - https://api.bybit.com
- **Netherlands**: `nl` - https://api.bybit.nl
- **Turkey**: `tr` - https://api.bybit-tr.com
- **Kazakhstan**: `kz` - https://api.bybit.kz
- **Georgia**: `ge` - https://api.bybitgeorgia.ge
- **UAE**: `ae` - https://api.bybit.ae

## Testnet vs Mainnet

### Testnet (for testing)
```env
BYBIT_TESTNET=true
```
- Uses https://api-testnet.bybit.com
- No real money involved
- Perfect for development and testing

### Mainnet (production)
```env
BYBIT_TESTNET=false
```
- Uses production endpoints
- Real trading with real funds
- Ensure your code is thoroughly tested before using mainnet

## Next Steps

1. Review the [README.md](README.md) for usage examples
2. Check [CHANGELOG.md](CHANGELOG.md) for version history
3. Explore WebSocket functionality for real-time data
4. Read Bybit's official documentation: https://bybit-exchange.github.io/docs/v5/guide

## Troubleshooting

### Signature Errors

If you encounter signature errors:
1. Verify your API key and secret are correct
2. Check that your server time is synchronized (use NTP)
3. Ensure `recv_window` is appropriate (5000-20000 ms)
4. Verify parameter sorting for GET requests

### Connection Issues

If you have connection problems:
1. Check your internet connection
2. Verify the regional endpoint is accessible
3. Try switching to a different region
4. Check if Bybit API is operational

### WebSocket Issues

For WebSocket connection problems:
1. Ensure `textalk/websocket` package is installed
2. Check firewall settings for WebSocket connections
3. Verify API credentials for private streams
4. Test with public streams first

## Support

- GitHub: https://github.com/tigusigalpa/bybit-php
- Email: sovletig@gmail.com
- Bybit API Docs: https://bybit-exchange.github.io/docs/v5/guide
