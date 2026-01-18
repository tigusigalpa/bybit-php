<?php

/**
 * Example 11: Private WebSocket Streams
 * 
 * This example demonstrates how to:
 * - Subscribe to position updates
 * - Subscribe to order updates
 * - Subscribe to execution updates
 * - Subscribe to wallet updates
 * 
 * REQUIRES: Valid API credentials with appropriate permissions
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Tigusigalpa\ByBit\BybitWebSocket;

$config = require __DIR__ . '/config.php';

try {
    echo "🔐 Private WebSocket Streams\n";
    echo str_repeat("=", 50) . "\n\n";

    // Create WebSocket instance for private data
    $ws = new BybitWebSocket(
        apiKey: $config['api_key'],
        apiSecret: $config['api_secret'],
        testnet: $config['testnet'],
        region: $config['region'],
        isPrivate: true
    );

    echo "Subscribing to private streams...\n";
    echo "Press Ctrl+C to stop\n\n";

    // Subscribe to position updates
    $ws->subscribePosition();
    echo "✅ Subscribed to position updates\n";

    // Subscribe to order updates
    $ws->subscribeOrder();
    echo "✅ Subscribed to order updates\n";

    // Subscribe to execution updates
    $ws->subscribeExecution();
    echo "✅ Subscribed to execution updates\n";

    // Subscribe to wallet updates
    $ws->subscribeWallet();
    echo "✅ Subscribed to wallet updates\n\n";

    echo "Listening for account updates...\n";
    echo str_repeat("-", 50) . "\n\n";

    // Handle incoming messages
    $ws->onMessage(function($data) {
        if (!isset($data['topic'])) {
            // Handle connection messages
            if (isset($data['op'])) {
                if ($data['op'] === 'auth' && isset($data['success']) && $data['success']) {
                    echo "✅ Authentication successful!\n\n";
                }
            }
            return;
        }

        $topic = $data['topic'];
        $timestamp = date('H:i:s');

        // Handle different message types
        switch ($topic) {
            case 'position':
                // Position update
                if (isset($data['data'][0])) {
                    foreach ($data['data'] as $pos) {
                        if (floatval($pos['size']) > 0) {
                            echo "[{$timestamp}] 📊 Position Update\n";
                            echo "   Symbol: {$pos['symbol']}\n";
                            echo "   Side: {$pos['side']}\n";
                            echo "   Size: {$pos['size']}\n";
                            echo "   Entry Price: \${$pos['entryPrice']}\n";
                            echo "   Mark Price: \${$pos['markPrice']}\n";
                            echo "   Unrealized P&L: \${$pos['unrealisedPnl']}\n";
                            echo "   Leverage: {$pos['leverage']}x\n\n";
                        }
                    }
                }
                break;

            case 'order':
                // Order update
                if (isset($data['data'][0])) {
                    foreach ($data['data'] as $order) {
                        $statusEmoji = match($order['orderStatus']) {
                            'New' => '🆕',
                            'PartiallyFilled' => '⏳',
                            'Filled' => '✅',
                            'Cancelled' => '❌',
                            'Rejected' => '🚫',
                            default => '📝'
                        };
                        
                        echo "[{$timestamp}] {$statusEmoji} Order Update\n";
                        echo "   Order ID: {$order['orderId']}\n";
                        echo "   Symbol: {$order['symbol']}\n";
                        echo "   Side: {$order['side']}\n";
                        echo "   Type: {$order['orderType']}\n";
                        echo "   Status: {$order['orderStatus']}\n";
                        echo "   Price: \${$order['price']}\n";
                        echo "   Qty: {$order['qty']} (Filled: {$order['cumExecQty']})\n\n";
                    }
                }
                break;

            case 'execution':
                // Execution update (fills)
                if (isset($data['data'][0])) {
                    foreach ($data['data'] as $exec) {
                        echo "[{$timestamp}] 💰 Execution (Fill)\n";
                        echo "   Symbol: {$exec['symbol']}\n";
                        echo "   Side: {$exec['side']}\n";
                        echo "   Exec Price: \${$exec['execPrice']}\n";
                        echo "   Exec Qty: {$exec['execQty']}\n";
                        echo "   Exec Fee: \${$exec['execFee']}\n";
                        echo "   Order ID: {$exec['orderId']}\n\n";
                    }
                }
                break;

            case 'wallet':
                // Wallet update
                if (isset($data['data'][0])) {
                    foreach ($data['data'] as $wallet) {
                        if (isset($wallet['coin'])) {
                            foreach ($wallet['coin'] as $coin) {
                                if (floatval($coin['walletBalance']) > 0) {
                                    echo "[{$timestamp}] 💼 Wallet Update\n";
                                    echo "   Coin: {$coin['coin']}\n";
                                    echo "   Wallet Balance: {$coin['walletBalance']}\n";
                                    echo "   Available: {$coin['availableToWithdraw']}\n\n";
                                }
                            }
                        }
                    }
                }
                break;
        }
    });

    // Start listening (blocking)
    $ws->listen();

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
