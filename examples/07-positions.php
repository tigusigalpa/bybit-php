<?php

/**
 * Example 07: Position Management
 * 
 * This example demonstrates how to:
 * - Get current positions
 * - View position details
 * - Monitor position P&L
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

    echo "📊 Position Management\n";
    echo str_repeat("=", 50) . "\n\n";

    // 1. Get All Positions
    echo "1️⃣ Getting All Positions...\n";
    $positions = $client->getPositions([
        'category' => 'linear',
        'settleCoin' => 'USDT'
    ]);

    if (isset($positions['result']['list'])) {
        $positionList = $positions['result']['list'];
        echo "   Found " . count($positionList) . " positions\n\n";
        
        foreach ($positionList as $pos) {
            if (floatval($pos['size']) > 0) {
                echo "   Position: {$pos['symbol']}\n";
                echo "   - Side: {$pos['side']}\n";
                echo "   - Size: {$pos['size']}\n";
                echo "   - Entry Price: \${$pos['avgPrice']}\n";
                echo "   - Mark Price: \${$pos['markPrice']}\n";
                echo "   - Leverage: {$pos['leverage']}x\n";
                echo "   - Unrealized P&L: \${$pos['unrealisedPnl']}\n";
                echo "   - Position Value: \${$pos['positionValue']}\n";
                echo "   - Liquidation Price: \${$pos['liqPrice']}\n\n";
            }
        }
    }

    // 2. Get Specific Position
    echo "2️⃣ Getting BTCUSDT Position...\n";
    $btcPosition = $client->getPositions([
        'category' => 'linear',
        'symbol' => 'BTCUSDT'
    ]);

    if (isset($btcPosition['result']['list'][0])) {
        $pos = $btcPosition['result']['list'][0];
        
        if (floatval($pos['size']) > 0) {
            echo "   Active Position Found!\n";
            echo "   Symbol: {$pos['symbol']}\n";
            echo "   Side: {$pos['side']}\n";
            echo "   Size: {$pos['size']}\n";
            echo "   Entry Price: \${$pos['avgPrice']}\n";
            echo "   Current Price: \${$pos['markPrice']}\n";
            
            // Calculate P&L percentage
            $entryPrice = floatval($pos['avgPrice']);
            $markPrice = floatval($pos['markPrice']);
            $pnlPercent = (($markPrice - $entryPrice) / $entryPrice) * 100;
            if ($pos['side'] === 'Sell') {
                $pnlPercent = -$pnlPercent;
            }
            
            echo "   P&L: \${$pos['unrealisedPnl']} (" . round($pnlPercent, 2) . "%)\n";
            echo "   Leverage: {$pos['leverage']}x\n";
            echo "   Position Mode: " . ($pos['positionIdx'] == 0 ? 'One-Way' : 'Hedge') . "\n";
            echo "   Take Profit: " . ($pos['takeProfit'] ?: 'Not set') . "\n";
            echo "   Stop Loss: " . ($pos['stopLoss'] ?: 'Not set') . "\n";
        } else {
            echo "   No active position\n";
        }
        echo "\n";
    }

    // 3. Get Closed P&L
    echo "3️⃣ Getting Closed P&L...\n";
    $closedPnl = $client->request('GET', '/v5/position/closed-pnl', [
        'category' => 'linear',
        'symbol' => 'BTCUSDT',
        'limit' => 5
    ]);

    if (isset($closedPnl['result']['list'])) {
        echo "   Recent " . count($closedPnl['result']['list']) . " closed positions:\n";
        foreach ($closedPnl['result']['list'] as $pnl) {
            $closedPnlValue = floatval($pnl['closedPnl']);
            $emoji = $closedPnlValue >= 0 ? '✅' : '❌';
            echo "   {$emoji} {$pnl['symbol']} - P&L: \${$pnl['closedPnl']}\n";
            echo "      Qty: {$pnl['qty']}, Avg Entry: \${$pnl['avgEntryPrice']}, Avg Exit: \${$pnl['avgExitPrice']}\n";
        }
    }

    echo "\n✅ Position information retrieved successfully!\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
