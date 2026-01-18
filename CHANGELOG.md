# Changelog

## 0.1.0 - 2025-11-18
- Initial package scaffolding
- Base client with HMAC/RSA signing and endpoint selection
- Laravel service provider and facade
- Publishable config
- Added `X-BAPI-SIGN-TYPE: 2` and JSON content-type for POST
- Added wrapper methods: server time, tickers, orders, positions, wallet balance
- Added universal placeOrder with TPSL, leverage, trigger support
- Added computeFee with configurable fee table in config