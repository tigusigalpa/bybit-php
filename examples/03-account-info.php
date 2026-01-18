<?php

/**
 * Example 03: Account Information
 * 
 * This example demonstrates how to:
 * - Get wallet balance
 * - Check account information
 * - View available funds
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

    echo "💰 Account Information\n";
    echo str_repeat("=", 50) . "\n\n";

    // 1. Get Wallet Balance (Unified Account)
    echo "1️⃣ Unified Account Balance...\n";
    $balance = $client->getWalletBalance([
        'accountType' => 'UNIFIED'
    ]);

    if (isset($balance['result']['list'][0])) {
        $account = $balance['result']['list'][0];
        
        echo "   Total Equity: \${$account['totalEquity']}\n";
        echo "   Total Wallet Balance: \${$account['totalWalletBalance']}\n";
        echo "   Total Available Balance: \${$account['totalAvailableBalance']}\n";
        echo "   Total Margin Balance: \${$account['totalMarginBalance']}\n";
        echo "   Total Initial Margin: \${$account['totalInitialMargin']}\n\n";

        // Show coin balances
        if (isset($account['coin'])) {
            echo "   Coin Balances:\n";
            foreach ($account['coin'] as $coin) {
                if (floatval($coin['walletBalance']) > 0) {
                    echo "   - {$coin['coin']}: {$coin['walletBalance']} (Available: {$coin['availableToWithdraw']})\n";
                }
            }
            echo "\n";
        }
    }

    // 2. Get USDT Balance
    echo "2️⃣ USDT Balance...\n";
    $usdtBalance = $client->getWalletBalance([
        'accountType' => 'UNIFIED',
        'coin' => 'USDT'
    ]);

    if (isset($usdtBalance['result']['list'][0]['coin'][0])) {
        $usdt = $usdtBalance['result']['list'][0]['coin'][0];
        echo "   Wallet Balance: {$usdt['walletBalance']} USDT\n";
        echo "   Available: {$usdt['availableToWithdraw']} USDT\n";
        echo "   In Orders: " . (floatval($usdt['walletBalance']) - floatval($usdt['availableToWithdraw'])) . " USDT\n\n";
    }

    // 3. Get Fee Rate
    echo "3️⃣ Fee Rate Information...\n";
    $feeRate = $client->request('GET', '/v5/account/fee-rate', [
        'category' => 'linear',
        'symbol' => 'BTCUSDT'
    ]);

    if (isset($feeRate['result']['list'][0])) {
        $fee = $feeRate['result']['list'][0];
        echo "   Symbol: {$fee['symbol']}\n";
        echo "   Taker Fee: " . (floatval($fee['takerFeeRate']) * 100) . "%\n";
        echo "   Maker Fee: " . (floatval($fee['makerFeeRate']) * 100) . "%\n\n";
    }

    // 4. Calculate Example Trading Fee
    echo "4️⃣ Example Trading Fee Calculation...\n";
    $tradeVolume = 1000; // $1000 trade
    $spotFee = $client->computeFee('spot', $tradeVolume, 'Non-VIP', 'taker');
    $derivativesFee = $client->computeFee('derivatives', $tradeVolume, 'Non-VIP', 'taker');
    
    echo "   For \${$tradeVolume} trade:\n";
    echo "   - Spot Fee (taker): \${$spotFee}\n";
    echo "   - Derivatives Fee (taker): \${$derivativesFee}\n";

    echo "\n✅ Account information retrieved successfully!\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
