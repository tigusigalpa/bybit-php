<?php

/**
 * Example 08: Leverage and Position Mode
 * 
 * This example demonstrates how to:
 * - Set leverage
 * - Switch position mode (One-Way / Hedge)
 * - View current leverage settings
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

    echo "⚙️ Leverage and Position Mode Management\n";
    echo str_repeat("=", 50) . "\n\n";

    $symbol = 'BTCUSDT';

    // 1. Get Current Position Info (includes leverage)
    echo "1️⃣ Current Position Settings...\n";
    $position = $client->getPositions([
        'category' => 'linear',
        'symbol' => $symbol
    ]);

    if (isset($position['result']['list'][0])) {
        $pos = $position['result']['list'][0];
        echo "   Symbol: {$pos['symbol']}\n";
        echo "   Current Leverage: {$pos['leverage']}x\n";
        echo "   Position Mode: " . ($pos['positionIdx'] == 0 ? 'One-Way Mode' : 'Hedge Mode') . "\n";
        echo "   Position Index: {$pos['positionIdx']}\n\n";
    }

    // 2. Set Leverage
    echo "2️⃣ Setting Leverage to 10x...\n";
    $leverageResult = $client->setLeverage(
        category: 'linear',
        symbol: $symbol,
        leverage: 10
    );

    if (isset($leverageResult['retCode']) && $leverageResult['retCode'] === 0) {
        echo "   ✅ Leverage set successfully to 10x\n";
        echo "   Both buy and sell leverage updated\n\n";
    } else {
        echo "   ❌ Failed: " . ($leverageResult['retMsg'] ?? 'Unknown error') . "\n\n";
    }

    // 3. Set Different Leverage for Buy and Sell (Hedge Mode)
    echo "3️⃣ Setting Different Leverage for Buy/Sell...\n";
    
    // Set buy leverage
    $buyLeverage = $client->setLeverage(
        category: 'linear',
        symbol: $symbol,
        leverage: 15,
        side: 'Buy'
    );

    // Set sell leverage
    $sellLeverage = $client->setLeverage(
        category: 'linear',
        symbol: $symbol,
        leverage: 20,
        side: 'Sell'
    );

    if (isset($buyLeverage['retCode']) && $buyLeverage['retCode'] === 0) {
        echo "   ✅ Buy leverage set to 15x\n";
    }
    if (isset($sellLeverage['retCode']) && $sellLeverage['retCode'] === 0) {
        echo "   ✅ Sell leverage set to 20x\n";
    }
    echo "\n";

    // 4. Switch Position Mode
    echo "4️⃣ Switching Position Mode...\n";
    echo "   Current mode check...\n";
    
    // Switch to One-Way Mode (mode = 0)
    echo "   Switching to One-Way Mode...\n";
    $switchMode = $client->switchPositionMode([
        'category' => 'linear',
        'symbol' => $symbol,
        'mode' => 0 // 0 = One-Way, 3 = Hedge Mode
    ]);

    if (isset($switchMode['retCode']) && $switchMode['retCode'] === 0) {
        echo "   ✅ Switched to One-Way Mode\n";
        echo "   Note: Position mode can only be changed when no positions are open\n\n";
    } else {
        echo "   ℹ️  " . ($switchMode['retMsg'] ?? 'Mode change may require no open positions') . "\n\n";
    }

    // 5. Example: Switch to Hedge Mode (commented out)
    echo "5️⃣ Hedge Mode Example (commented out):\n";
    echo "   /*\n";
    echo "   \$hedgeMode = \$client->switchPositionMode([\n";
    echo "       'category' => 'linear',\n";
    echo "       'symbol' => '{$symbol}',\n";
    echo "       'mode' => 3 // Hedge Mode\n";
    echo "   ]);\n";
    echo "   */\n\n";

    // 6. Get Risk Limit
    echo "6️⃣ Getting Risk Limit...\n";
    $riskLimit = $client->request('GET', '/v5/market/risk-limit', [
        'category' => 'linear',
        'symbol' => $symbol
    ]);

    if (isset($riskLimit['result']['list'][0])) {
        $risk = $riskLimit['result']['list'][0];
        echo "   Symbol: {$risk['symbol']}\n";
        echo "   Risk Limit: \${$risk['riskLimitValue']}\n";
        echo "   Max Leverage: {$risk['maxLeverage']}x\n";
        echo "   Initial Margin: {$risk['initialMargin']}%\n";
        echo "   Maintenance Margin: {$risk['maintenanceMargin']}%\n";
    }

    echo "\n✅ Leverage and position mode examples completed!\n";
    echo "⚠️  Remember: Higher leverage = Higher risk!\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
