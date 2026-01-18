<?php

/**
 * Example 10: Public WebSocket Streams
 * 
 * This example demonstrates how to:
 * - Subscribe to orderbook updates
 * - Subscribe to public trades
 * - Subscribe to ticker updates
 * - Subscribe to kline data
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Tigusigalpa\ByBit\BybitWebSocket;

$config = require __DIR__ . '/config.php';

try {
    echo "🌐 Public WebSocket Streams\n";
    echo str_repeat("=", 50) . "\n\n";

    // Create WebSocket instance for public data
    $ws = new BybitWebSocket(
        apiKey: null,
        apiSecret: null,
        testnet: $config['testnet'],
        region: $config['region'],
        isPrivate: false
    );

    $symbol = 'BTCUSDT';

    echo "Subscribing to public streams for {$symbol}...\n";
    echo "Press Ctrl+C to stop\n\n";

    // Subscribe to orderbook (depth 50)
    $ws->subscribeOrderbook($symbol, 50);
    echo "✅ Subscribed to orderbook\n";

    // Subscribe to public trades
    $ws->subscribeTrade($symbol);
    echo "✅ Subscribed to trades\n";

    // Subscribe to ticker
    $ws->subscribeTicker($symbol);
    echo "✅ Subscribed to ticker\n";

    // Subscribe to 1-minute klines
    $ws->subscribeKline($symbol, '1');
    echo "✅ Subscribed to 1m klines\n\n";

    echo "Listening for messages...\n";
    echo str_repeat("-", 50) . "\n\n";

    // Handle incoming messages
    $ws->onMessage(function($data) {
        if (!isset($data['topic'])) {
            return;
        }

        $topic = $data['topic'];
        $timestamp = date('H:i:s');

        // Handle different message types
        if (strpos($topic, 'orderbook') !== false) {
            // Orderbook update
            if (isset($data['data']['b'][0]) && isset($data['data']['a'][0])) {
                $bestBid = $data['data']['b'][0];
                $bestAsk = $data['data']['a'][0];
                echo "[{$timestamp}] 📚 Orderbook - Bid: \${$bestBid[0]} | Ask: \${$bestAsk[0]} | Spread: \$" . 
                     round($bestAsk[0] - $bestBid[0], 2) . "\n";
            }
        } 
        elseif (strpos($topic, 'publicTrade') !== false) {
            // Public trade
            if (isset($data['data'][0])) {
                $trade = $data['data'][0];
                $side = $trade['S'] === 'Buy' ? '🟢' : '🔴';
                echo "[{$timestamp}] {$side} Trade - Price: \${$trade['p']}, Qty: {$trade['v']}\n";
            }
        }
        elseif (strpos($topic, 'tickers') !== false) {
            // Ticker update
            if (isset($data['data'])) {
                $ticker = $data['data'];
                $change = floatval($ticker['price24hPcnt']) * 100;
                $emoji = $change >= 0 ? '📈' : '📉';
                echo "[{$timestamp}] {$emoji} Ticker - Price: \${$ticker['lastPrice']}, 24h: " . 
                     round($change, 2) . "%, Vol: {$ticker['volume24h']}\n";
            }
        }
        elseif (strpos($topic, 'kline') !== false) {
            // Kline update
            if (isset($data['data'][0])) {
                $kline = $data['data'][0];
                $time = date('H:i', $kline['start'] / 1000);
                echo "[{$timestamp}] 📊 Kline {$time} - O: \${$kline['open']}, H: \${$kline['high']}, " .
                     "L: \${$kline['low']}, C: \${$kline['close']}, V: {$kline['volume']}\n";
            }
        }
    });

    // Start listening (blocking)
    $ws->listen();

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
