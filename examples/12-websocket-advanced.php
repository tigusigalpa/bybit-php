<?php

/**
 * Example 12: Advanced WebSocket Usage
 * 
 * This example demonstrates:
 * - Multiple symbol subscriptions
 * - Dynamic subscription management
 * - Error handling and reconnection
 * - Data aggregation
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Tigusigalpa\ByBit\BybitWebSocket;

$config = require __DIR__ . '/config.php';

// Data storage for aggregation
$priceData = [];
$tradeCount = [];

try {
    echo "🚀 Advanced WebSocket Usage\n";
    echo str_repeat("=", 50) . "\n\n";

    $ws = new BybitWebSocket(
        apiKey: null,
        apiSecret: null,
        testnet: $config['testnet'],
        region: $config['region'],
        isPrivate: false
    );

    // Multiple symbols to monitor
    $symbols = ['BTCUSDT', 'ETHUSDT', 'SOLUSDT'];

    echo "Subscribing to multiple symbols...\n";
    foreach ($symbols as $symbol) {
        $ws->subscribeTicker($symbol);
        $ws->subscribeTrade($symbol);
        $priceData[$symbol] = [];
        $tradeCount[$symbol] = 0;
        echo "✅ Subscribed to {$symbol}\n";
    }

    echo "\nMonitoring " . count($symbols) . " symbols\n";
    echo "Press Ctrl+C to stop\n\n";
    echo str_repeat("-", 50) . "\n\n";

    // Statistics tracking
    $startTime = time();
    $totalMessages = 0;

    $ws->onMessage(function($data) use (&$priceData, &$tradeCount, &$totalMessages, $startTime) {
        $totalMessages++;
        
        if (!isset($data['topic'])) {
            return;
        }

        $topic = $data['topic'];
        $timestamp = date('H:i:s');

        // Extract symbol from topic
        preg_match('/\.([\w]+)$/', $topic, $matches);
        $symbol = $matches[1] ?? 'UNKNOWN';

        if (strpos($topic, 'tickers') !== false && isset($data['data'])) {
            $ticker = $data['data'];
            $price = floatval($ticker['lastPrice']);
            
            // Store price data
            $priceData[$symbol][] = $price;
            
            // Keep only last 10 prices
            if (count($priceData[$symbol]) > 10) {
                array_shift($priceData[$symbol]);
            }

            // Calculate simple moving average
            $sma = array_sum($priceData[$symbol]) / count($priceData[$symbol]);
            
            // Price change
            $change = floatval($ticker['price24hPcnt']) * 100;
            $emoji = $change >= 0 ? '📈' : '📉';
            
            echo "[{$timestamp}] {$emoji} {$symbol}\n";
            echo "   Price: \${$price} | 24h: " . round($change, 2) . "%\n";
            echo "   SMA(10): \$" . round($sma, 2) . "\n";
            echo "   Volume: {$ticker['volume24h']}\n\n";
        }
        elseif (strpos($topic, 'publicTrade') !== false && isset($data['data'][0])) {
            $trade = $data['data'][0];
            $tradeCount[$symbol]++;
            
            // Show trade every 5 trades to avoid spam
            if ($tradeCount[$symbol] % 5 === 0) {
                $side = $trade['S'] === 'Buy' ? '🟢 BUY' : '🔴 SELL';
                echo "[{$timestamp}] {$side} {$symbol}\n";
                echo "   Price: \${$trade['p']}, Qty: {$trade['v']}\n";
                echo "   Total trades: {$tradeCount[$symbol]}\n\n";
            }
        }

        // Show statistics every 50 messages
        if ($totalMessages % 50 === 0) {
            $runtime = time() - $startTime;
            $msgPerSec = $runtime > 0 ? round($totalMessages / $runtime, 2) : 0;
            
            echo str_repeat("=", 50) . "\n";
            echo "📊 Statistics\n";
            echo "   Runtime: {$runtime}s\n";
            echo "   Total Messages: {$totalMessages}\n";
            echo "   Messages/sec: {$msgPerSec}\n";
            echo "   Trade Counts:\n";
            foreach ($tradeCount as $sym => $count) {
                echo "   - {$sym}: {$count}\n";
            }
            echo str_repeat("=", 50) . "\n\n";
        }
    });

    // Start listening
    $ws->listen();

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    
    // Show final statistics
    if (!empty($tradeCount)) {
        echo "\n📊 Final Statistics:\n";
        foreach ($tradeCount as $symbol => $count) {
            echo "   {$symbol}: {$count} trades\n";
        }
    }
}
