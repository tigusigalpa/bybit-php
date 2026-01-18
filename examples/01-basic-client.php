<?php

/**
 * Example 01: Basic Client Initialization
 * 
 * This example demonstrates how to:
 * - Initialize the Bybit client
 * - Get server time
 * - Check connection
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Tigusigalpa\ByBit\BybitClient;

// Load configuration
$config = require __DIR__ . '/config.php';

try {
    // Initialize client
    $client = new BybitClient(
        apiKey: $config['api_key'],
        apiSecret: $config['api_secret'],
        testnet: $config['testnet'],
        region: $config['region'],
        recvWindow: $config['recv_window'],
        signature: $config['signature']
    );

    echo "🚀 Bybit Client Initialized\n";
    echo "Environment: " . ($config['testnet'] ? 'Testnet' : 'Mainnet') . "\n";
    echo "Region: {$config['region']}\n";
    echo "Endpoint: " . $client->endpoint() . "\n\n";

    // Get server time
    echo "📡 Fetching server time...\n";
    $serverTime = $client->getServerTime();
    
    if (isset($serverTime['retCode']) && $serverTime['retCode'] === 0) {
        $timestamp = $serverTime['result']['timeSecond'];
        $datetime = date('Y-m-d H:i:s', $timestamp);
        
        echo "✅ Server Time: {$datetime} UTC\n";
        echo "✅ Timestamp: {$timestamp}\n";
        echo "✅ Connection successful!\n";
    } else {
        echo "❌ Error: " . ($serverTime['retMsg'] ?? 'Unknown error') . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
