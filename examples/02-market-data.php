<?php

/**
 * Example 02: Market Data
 * 
 * This example demonstrates how to:
 * - Get market tickers
 * - Fetch orderbook data
 * - Get kline/candlestick data
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Tigusigalpa\ByBit\BybitClient;

$config = require __DIR__ . '/config.php';

try {
    $client = new BybitClient(
        apiKey: $config['api_key'],
        apiSecret: $config['api_secret'],
        testnet: $config['testnet'],
        region: $config['region']
    );

    echo "📊 Market Data Examples\n";
    echo str_repeat("=", 50) . "\n\n";

    // 1. Get Tickers
    echo "1️⃣ Getting BTCUSDT Ticker...\n";
    $tickers = $client->getTickers([
        'category' => 'linear',
        'symbol' => 'BTCUSDT'
    ]);

    if (isset($tickers['result']['list'][0])) {
        $ticker = $tickers['result']['list'][0];
        echo "   Symbol: {$ticker['symbol']}\n";
        echo "   Last Price: \${$ticker['lastPrice']}\n";
        echo "   24h Volume: {$ticker['volume24h']}\n";
        echo "   24h Change: {$ticker['price24hPcnt']}%\n";
        echo "   High 24h: \${$ticker['highPrice24h']}\n";
        echo "   Low 24h: \${$ticker['lowPrice24h']}\n\n";
    }

    // 2. Get Multiple Tickers
    echo "2️⃣ Getting Multiple Tickers...\n";
    $multiTickers = $client->getTickers([
        'category' => 'linear'
    ]);

    if (isset($multiTickers['result']['list'])) {
        $count = count($multiTickers['result']['list']);
        echo "   Found {$count} trading pairs\n";
        
        // Show first 5
        echo "   Top 5 pairs:\n";
        foreach (array_slice($multiTickers['result']['list'], 0, 5) as $t) {
            echo "   - {$t['symbol']}: \${$t['lastPrice']}\n";
        }
        echo "\n";
    }

    // 3. Get Orderbook using universal request method
    echo "3️⃣ Getting Orderbook...\n";
    $orderbook = $client->request('GET', '/v5/market/orderbook', [
        'category' => 'linear',
        'symbol' => 'BTCUSDT',
        'limit' => 5
    ]);

    if (isset($orderbook['result'])) {
        echo "   Bids (Top 5):\n";
        foreach ($orderbook['result']['b'] as $bid) {
            echo "   - Price: \${$bid[0]}, Qty: {$bid[1]}\n";
        }
        echo "\n   Asks (Top 5):\n";
        foreach ($orderbook['result']['a'] as $ask) {
            echo "   - Price: \${$ask[0]}, Qty: {$ask[1]}\n";
        }
        echo "\n";
    }

    // 4. Get Kline Data
    echo "4️⃣ Getting Kline Data (1h candles)...\n";
    $klines = $client->request('GET', '/v5/market/kline', [
        'category' => 'linear',
        'symbol' => 'BTCUSDT',
        'interval' => '60', // 1 hour
        'limit' => 5
    ]);

    if (isset($klines['result']['list'])) {
        echo "   Recent 5 candles:\n";
        foreach ($klines['result']['list'] as $kline) {
            $time = date('Y-m-d H:i', $kline[0] / 1000);
            echo "   {$time} - O: \${$kline[1]}, H: \${$kline[2]}, L: \${$kline[3]}, C: \${$kline[4]}, V: {$kline[5]}\n";
        }
    }

    echo "\n✅ Market data fetched successfully!\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
