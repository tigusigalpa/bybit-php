<?php

/**
 * Example 05: Manage Orders
 * 
 * This example demonstrates how to:
 * - Get open orders
 * - Get order history
 * - Amend orders
 * - Cancel orders
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

    echo "🔧 Order Management Examples\n";
    echo str_repeat("=", 50) . "\n\n";

    // 1. Get Open Orders
    echo "1️⃣ Getting Open Orders...\n";
    $openOrders = $client->getOpenOrders([
        'category' => 'linear',
        'symbol' => 'BTCUSDT'
    ]);

    if (isset($openOrders['result']['list'])) {
        $orders = $openOrders['result']['list'];
        echo "   Found " . count($orders) . " open orders\n";
        
        foreach ($orders as $order) {
            echo "   - Order ID: {$order['orderId']}\n";
            echo "     Side: {$order['side']}, Type: {$order['orderType']}\n";
            echo "     Price: \${$order['price']}, Qty: {$order['qty']}\n";
            echo "     Status: {$order['orderStatus']}\n\n";
        }
        
        // Save first order ID for later examples
        $firstOrderId = $orders[0]['orderId'] ?? null;
    } else {
        echo "   No open orders found\n\n";
        $firstOrderId = null;
    }

    // 2. Get Order History
    echo "2️⃣ Getting Order History...\n";
    $history = $client->getHistoryOrders([
        'category' => 'linear',
        'symbol' => 'BTCUSDT',
        'limit' => 5
    ]);

    if (isset($history['result']['list'])) {
        echo "   Recent " . count($history['result']['list']) . " orders:\n";
        foreach ($history['result']['list'] as $order) {
            echo "   - {$order['symbol']} {$order['side']} {$order['orderType']}\n";
            echo "     Price: \${$order['price']}, Qty: {$order['qty']}\n";
            echo "     Status: {$order['orderStatus']}\n";
        }
        echo "\n";
    }

    // 3. Amend Order (if we have an open order)
    if ($firstOrderId) {
        echo "3️⃣ Amending Order...\n";
        
        $amendResult = $client->amendOrder([
            'category' => 'linear',
            'symbol' => 'BTCUSDT',
            'orderId' => $firstOrderId,
            'qty' => '0.002' // Change quantity
        ]);

        if (isset($amendResult['result']['orderId'])) {
            echo "   ✅ Order amended successfully!\n";
            echo "   Order ID: {$amendResult['result']['orderId']}\n\n";
        } else {
            echo "   ❌ Failed to amend: " . ($amendResult['retMsg'] ?? 'Unknown error') . "\n\n";
        }
    } else {
        echo "3️⃣ No open orders to amend\n\n";
    }

    // 4. Cancel Specific Order
    if ($firstOrderId) {
        echo "4️⃣ Canceling Order...\n";
        
        $cancelResult = $client->cancelOrder([
            'category' => 'linear',
            'symbol' => 'BTCUSDT',
            'orderId' => $firstOrderId
        ]);

        if (isset($cancelResult['result']['orderId'])) {
            echo "   ✅ Order canceled successfully!\n";
            echo "   Order ID: {$cancelResult['result']['orderId']}\n\n";
        } else {
            echo "   ❌ Failed to cancel: " . ($cancelResult['retMsg'] ?? 'Unknown error') . "\n\n";
        }
    } else {
        echo "4️⃣ No orders to cancel\n\n";
    }

    // 5. Cancel All Orders (commented out for safety)
    echo "5️⃣ Cancel All Orders Example (commented out):\n";
    echo "   // Uncomment to execute - USE WITH CAUTION!\n";
    echo "   /*\n";
    echo "   \$cancelAll = \$client->cancelAllOrders([\n";
    echo "       'category' => 'linear',\n";
    echo "       'symbol' => 'BTCUSDT'\n";
    echo "   ]);\n";
    echo "   */\n\n";

    // 6. Get Specific Order Details
    echo "6️⃣ Getting Specific Order Details...\n";
    if ($firstOrderId) {
        $orderDetail = $client->request('GET', '/v5/order/realtime', [
            'category' => 'linear',
            'orderId' => $firstOrderId
        ]);

        if (isset($orderDetail['result']['list'][0])) {
            $detail = $orderDetail['result']['list'][0];
            echo "   Order Details:\n";
            echo "   - ID: {$detail['orderId']}\n";
            echo "   - Symbol: {$detail['symbol']}\n";
            echo "   - Side: {$detail['side']}\n";
            echo "   - Price: \${$detail['price']}\n";
            echo "   - Quantity: {$detail['qty']}\n";
            echo "   - Filled: {$detail['cumExecQty']}\n";
            echo "   - Status: {$detail['orderStatus']}\n";
        }
    } else {
        echo "   No order ID available\n";
    }

    echo "\n✅ Order management examples completed!\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
