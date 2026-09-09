# Changelog

## Unreleased
### Fixed
- Signed REST requests now send the exact canonical query string or JSON body that was used to calculate the signature.
- Corrected V5 routes for transferable amount and account instruments info.
- Corrected Turkey REST/WebSocket domains and added current regional domains for EU, Indonesia, Japan, and Hong Kong.
- Fixed TradFi GOOGL ticker, multi-ticker aggregation, stock filtering, and paginated instruments lookup.

### Added
- Batch order, execution history, account, asset, spot-margin, and demo-account helper methods aligned with currently supported Demo Trading endpoints.
- Demo Trading and RSA support for private WebSocket streams; the Laravel WebSocket singleton can now be configured as private.
- Optional broker ID configuration, typed HTTP exceptions, and a dependency-free signed-request test suite.
- GitHub Actions workflows for PHP test matrices, Xdebug coverage and Codecov, CodeQL, OpenSSF Scorecard, and Dependabot updates.

### Changed
- Testnet and Demo Trading are now explicitly mutually exclusive.
- `X-Referer` is emitted only when a broker ID is configured, per Bybit's broker guidance.

## 1.2.1 - 2026-04-13
### Added
- Laravel 13 support

### Changed
- Updated `illuminate/support` dependency to include `^13.0`
- Updated documentation badges to reflect Laravel 13 compatibility

## 0.2.0 - 2026-01-18
### Added
- **REST API Methods:**
  - `amendOrder()` - Modify existing orders
  - `cancelAllOrders()` - Cancel all orders for a symbol
  - `getHistoryOrders()` - Retrieve order history
  - `switchPositionMode()` - Switch between One-Way and Hedge mode
  - `setTradingStop()` - Set TP/SL for existing positions

- **WebSocket Support:**
  - New `BybitWebSocket` class for real-time data streaming
  - Public streams: orderbook, trades, tickers, klines
  - Private streams: positions, orders, executions, wallet updates
  - Auto-reconnection and ping/pong handling
  - Support for Testnet and regional endpoints
  - Laravel facade `BybitWebSocket` for easy integration
  - Multiple topic subscriptions
  - Background processing support via Laravel commands

### Changed
- Updated `composer.json` to include `textalk/websocket` dependency
- Enhanced service provider to register WebSocket client
- Updated documentation with comprehensive WebSocket examples

### Documentation
- Added WebSocket usage examples for both public and private streams
- Added Laravel command example for background WebSocket listeners
- Added examples for new REST API methods
- Updated both English and Russian README files

## 0.1.0 - 2025-11-18
- Initial package scaffolding
- Base client with HMAC/RSA signing and endpoint selection
- Laravel service provider and facade
- Publishable config
- Added `X-BAPI-SIGN-TYPE: 2` and JSON content-type for POST
- Added wrapper methods: server time, tickers, orders, positions, wallet balance
- Added universal placeOrder with TPSL, leverage, trigger support
- Added computeFee with configurable fee table in config
