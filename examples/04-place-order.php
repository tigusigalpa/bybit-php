<?php

/**
 * Example 04: Place Orders
 * 
 * This example demonstrates how to:
 * - Place limit orders
 * - Place market orders
 * - Place conditional orders
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

    echo "📝 Order Placement Examples\n";
    echo str_repeat("=", 50) . "\n\n";

    // Get current price first
    $ticker = $client->getTickers([
        'category' => 'linear',
        'symbol' => 'BTCUSDT'
    ]);
    $currentPrice = floatval($ticker['result']['list'][0]['lastPrice']);
    echo "Current BTC Price: \${$currentPrice}\n\n";

    // 1. Place Limit Order
    echo "1️⃣ Placing Limit Buy Order...\n";
    $limitPrice = round($currentPrice * 0.99, 2); // 1% below market
    
    $limitOrder = $client->createOrder([
        'category' => 'linear',
        'symbol' => 'BTCUSDT',
        'side' => 'Buy',
        'orderType' => 'Limit',
        'qty' => '0.001',
        'price' => (string)$limitPrice,
        'timeInForce' => 'GTC' // Good Till Cancel
    ]);

    if (isset($limitOrder['result']['orderId'])) {
        echo "   ✅ Order placed successfully!\n";
        echo "   Order ID: {$limitOrder['result']['orderId']}\n";
        echo "   Price: \${$limitPrice}\n";
        echo "   Quantity: 0.001 BTC\n\n";
    } else {
        echo "   ❌ Failed: " . ($limitOrder['retMsg'] ?? 'Unknown error') . "\n\n";
    }

    // 2. Place Market Order (commented out to avoid accidental execution)
    echo "2️⃣ Market Order Example (commented out):\n";
    echo "   // Uncomment to execute\n";
    echo "   /*\n";
    echo "   \$marketOrder = \$client->createOrder([\n";
    echo "       'category' => 'linear',\n";
    echo "       'symbol' => 'BTCUSDT',\n";
    echo "       'side' => 'Buy',\n";
    echo "       'orderType' => 'Market',\n";
    echo "       'qty' => '0.001'\n";
    echo "   ]);\n";
    echo "   */\n\n";

    // 3. Place Limit Order with Take Profit and Stop Loss
    echo "3️⃣ Limit Order with TP/SL...\n";
    $entryPrice = round($currentPrice * 0.98, 2);
    $takeProfit = round($currentPrice * 1.02, 2);
    $stopLoss = round($currentPrice * 0.95, 2);
    
    $tpslOrder = $client->createOrder([
        'category' => 'linear',
        'symbol' => 'BTCUSDT',
        'side' => 'Buy',
        'orderType' => 'Limit',
        'qty' => '0.001',
        'price' => (string)$entryPrice,
        'takeProfit' => (string)$takeProfit,
        'stopLoss' => (string)$stopLoss,
        'timeInForce' => 'GTC'
    ]);

    if (isset($tpslOrder['result']['orderId'])) {
        echo "   ✅ Order with TP/SL placed!\n";
        echo "   Order ID: {$tpslOrder['result']['orderId']}\n";
        echo "   Entry: \${$entryPrice}\n";
        echo "   Take Profit: \${$takeProfit}\n";
        echo "   Stop Loss: \${$stopLoss}\n\n";
    } else {
        echo "   ❌ Failed: " . ($tpslOrder['retMsg'] ?? 'Unknown error') . "\n\n";
    }

    // 4. Universal placeOrder method
    echo "4️⃣ Using Universal placeOrder Method...\n";
    $universalOrder = $client->placeOrder(
        type: 'derivatives',
        symbol: 'BTCUSDT',
        execution: 'limit',
        price: $limitPrice,
        side: 'Buy',
        leverage: 5,
        size: 100, // $100 margin
        slTp: [
            'type' => 'percent',
            'takeProfit' => 0.02, // 2%
            'stopLoss' => 0.01    // 1%
        ]
    );

    if (isset($universalOrder['result']['orderId'])) {
        echo "   ✅ Universal order placed!\n";
        echo "   Order ID: {$universalOrder['result']['orderId']}\n";
        echo "   Leverage: 5x\n";
        echo "   Margin: \$100\n\n";
    } else {
        echo "   ❌ Failed: " . ($universalOrder['retMsg'] ?? 'Unknown error') . "\n\n";
    }

    echo "✅ Order placement examples completed!\n";
    echo "⚠️  Remember to cancel test orders if needed.\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
