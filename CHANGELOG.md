# Changelog

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