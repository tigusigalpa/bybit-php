<?php

/**
 * Example 09: Trading Stops (Take Profit / Stop Loss)
 * 
 * This example demonstrates how to:
 * - Set take profit and stop loss for existing positions
 * - Modify trading stops
 * - Remove trading stops
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

    echo "🎯 Trading Stops Management\n";
    echo str_repeat("=", 50) . "\n\n";

    $symbol = 'BTCUSDT';

    // 1. Check Current Position
    echo "1️⃣ Checking Current Position...\n";
    $position = $client->getPositions([
        'category' => 'linear',
        'symbol' => $symbol
    ]);

    if (isset($position['result']['list'][0])) {
        $pos = $position['result']['list'][0];
        $hasPosition = floatval($pos['size']) > 0;
        
        if ($hasPosition) {
            echo "   ✅ Active position found\n";
            echo "   Symbol: {$pos['symbol']}\n";
            echo "   Side: {$pos['side']}\n";
            echo "   Size: {$pos['size']}\n";
            echo "   Entry Price: \${$pos['avgPrice']}\n";
            echo "   Current TP: " . ($pos['takeProfit'] ?: 'Not set') . "\n";
            echo "   Current SL: " . ($pos['stopLoss'] ?: 'Not set') . "\n\n";
            
            $entryPrice = floatval($pos['avgPrice']);
            $side = $pos['side'];
        } else {
            echo "   ℹ️  No active position found\n";
            echo "   Creating example values for demonstration...\n\n";
            
            // Get current price for examples
            $ticker = $client->getTickers([
                'category' => 'linear',
                'symbol' => $symbol
            ]);
            $entryPrice = floatval($ticker['result']['list'][0]['lastPrice']);
            $side = 'Buy';
            $hasPosition = false;
        }
    }

    // 2. Set Take Profit and Stop Loss
    if ($hasPosition) {
        echo "2️⃣ Setting Take Profit and Stop Loss...\n";
        
        // Calculate TP/SL based on entry price
        if ($side === 'Buy') {
            $takeProfit = round($entryPrice * 1.05, 2); // 5% profit
            $stopLoss = round($entryPrice * 0.98, 2);   // 2% loss
        } else {
            $takeProfit = round($entryPrice * 0.95, 2); // 5% profit
            $stopLoss = round($entryPrice * 1.02, 2);   // 2% loss
        }
        
        $tpslResult = $client->setTradingStop([
            'category' => 'linear',
            'symbol' => $symbol,
            'positionIdx' => 0, // 0 for one-way mode
            'takeProfit' => (string)$takeProfit,
            'stopLoss' => (string)$stopLoss,
            'tpTriggerBy' => 'MarkPrice',
            'slTriggerBy' => 'MarkPrice'
        ]);

        if (isset($tpslResult['retCode']) && $tpslResult['retCode'] === 0) {
            echo "   ✅ Trading stops set successfully!\n";
            echo "   Take Profit: \${$takeProfit}\n";
            echo "   Stop Loss: \${$stopLoss}\n\n";
        } else {
            echo "   ❌ Failed: " . ($tpslResult['retMsg'] ?? 'Unknown error') . "\n\n";
        }
    } else {
        echo "2️⃣ Example: Setting TP/SL (requires active position)\n";
        echo "   If you had a Buy position at \${$entryPrice}:\n";
        $exampleTP = round($entryPrice * 1.05, 2);
        $exampleSL = round($entryPrice * 0.98, 2);
        echo "   - Take Profit: \${$exampleTP} (+5%)\n";
        echo "   - Stop Loss: \${$exampleSL} (-2%)\n\n";
    }

    // 3. Set Trailing Stop
    echo "3️⃣ Setting Trailing Stop...\n";
    if ($hasPosition) {
        $trailingStop = $client->setTradingStop([
            'category' => 'linear',
            'symbol' => $symbol,
            'positionIdx' => 0,
            'trailingStop' => '50', // 50 USDT trailing distance
            'activePrice' => (string)($entryPrice * 1.02) // Activate when 2% in profit
        ]);

        if (isset($trailingStop['retCode']) && $trailingStop['retCode'] === 0) {
            echo "   ✅ Trailing stop set!\n";
            echo "   Trailing Distance: 50 USDT\n";
            echo "   Activation Price: \$" . ($entryPrice * 1.02) . "\n\n";
        } else {
            echo "   ❌ Failed: " . ($trailingStop['retMsg'] ?? 'Unknown error') . "\n\n";
        }
    } else {
        echo "   Example code (requires active position):\n";
        echo "   \$client->setTradingStop([\n";
        echo "       'category' => 'linear',\n";
        echo "       'symbol' => '{$symbol}',\n";
        echo "       'positionIdx' => 0,\n";
        echo "       'trailingStop' => '50',\n";
        echo "       'activePrice' => '" . ($entryPrice * 1.02) . "'\n";
        echo "   ]);\n\n";
    }

    // 4. Modify Only Take Profit
    echo "4️⃣ Modifying Only Take Profit...\n";
    if ($hasPosition) {
        $newTP = round($entryPrice * 1.03, 2); // 3% profit
        
        $modifyTP = $client->setTradingStop([
            'category' => 'linear',
            'symbol' => $symbol,
            'positionIdx' => 0,
            'takeProfit' => (string)$newTP
        ]);

        if (isset($modifyTP['retCode']) && $modifyTP['retCode'] === 0) {
            echo "   ✅ Take profit updated to \${$newTP}\n\n";
        } else {
            echo "   ❌ Failed: " . ($modifyTP['retMsg'] ?? 'Unknown error') . "\n\n";
        }
    } else {
        echo "   (Requires active position)\n\n";
    }

    // 5. Remove Trading Stops
    echo "5️⃣ Removing Trading Stops...\n";
    if ($hasPosition) {
        $removeStops = $client->setTradingStop([
            'category' => 'linear',
            'symbol' => $symbol,
            'positionIdx' => 0,
            'takeProfit' => '0',
            'stopLoss' => '0'
        ]);

        if (isset($removeStops['retCode']) && $removeStops['retCode'] === 0) {
            echo "   ✅ Trading stops removed\n";
        } else {
            echo "   ❌ Failed: " . ($removeStops['retMsg'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "   (Requires active position)\n";
        echo "   Set TP/SL to '0' to remove them\n";
    }

    echo "\n✅ Trading stops examples completed!\n";
    echo "💡 Tip: Always use stop losses to manage risk!\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
