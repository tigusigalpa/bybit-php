<?php

/**
 * Example 13: TradFi — Gold, Forex, Stocks & Indices
 *
 * Demonstrates how to use BybitTradFi to:
 * - Fetch tickers for metals, forex, and indices
 * - Get kline and orderbook data
 * - Inspect open TradFi positions
 * - Place and cancel a limit order
 * - Detect TradFi symbols with isTradFiSymbol()
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Tigusigalpa\ByBit\BybitClient;
use Tigusigalpa\ByBit\BybitTradFi;

$config = require __DIR__ . '/config.php';

try {
    $client = new BybitClient(
        apiKey:    $config['api_key'],
        apiSecret: $config['api_secret'],
        testnet:   $config['testnet'],
        region:    $config['region']
    );

    $tradfi = new BybitTradFi($client);

    echo "📈 TradFi Examples (Gold / Forex / Stocks / Indices)\n";
    echo str_repeat('=', 55) . "\n\n";

    // -------------------------------------------------------------------------
    // 1. Gold ticker
    // -------------------------------------------------------------------------
    echo "1️⃣  Gold (XAUUSD) Ticker\n";
    $gold = $tradfi->getTicker('XAUUSD');
    if (isset($gold['result']['list'][0])) {
        $t = $gold['result']['list'][0];
        echo "   Last Price  : {$t['lastPrice']}\n";
        echo "   Mark Price  : {$t['markPrice']}\n";
        echo "   24h Change  : {$t['price24hPcnt']}%\n";
        echo "   24h Volume  : {$t['volume24h']}\n\n";
    } else {
        echo "   (no data — market may be closed)\n\n";
    }

    // -------------------------------------------------------------------------
    // 2. Forex ticker
    // -------------------------------------------------------------------------
    echo "2️⃣  Forex (EURUSD) Ticker\n";
    $eurusd = $tradfi->getTicker('EURUSD');
    if (isset($eurusd['result']['list'][0])) {
        $t = $eurusd['result']['list'][0];
        echo "   Last Price  : {$t['lastPrice']}\n";
        echo "   24h Change  : {$t['price24hPcnt']}%\n\n";
    } else {
        echo "   (no data)\n\n";
    }

    // -------------------------------------------------------------------------
    // 3. Index ticker
    // -------------------------------------------------------------------------
    echo "3️⃣  S&P 500 (US500USD) Ticker\n";
    $sp500 = $tradfi->getTicker('US500USD');
    if (isset($sp500['result']['list'][0])) {
        $t = $sp500['result']['list'][0];
        echo "   Last Price  : {$t['lastPrice']}\n";
        echo "   24h Change  : {$t['price24hPcnt']}%\n\n";
    } else {
        echo "   (no data)\n\n";
    }

    // -------------------------------------------------------------------------
    // 4. Kline — XAUUSD hourly candles
    // -------------------------------------------------------------------------
    echo "4️⃣  XAUUSD Kline (1h, last 5 candles)\n";
    $klines = $tradfi->getKline('XAUUSD', '60', 5);
    if (isset($klines['result']['list'])) {
        foreach ($klines['result']['list'] as $candle) {
            $time = date('Y-m-d H:i', (int)($candle[0] / 1000));
            echo "   {$time}  O:{$candle[1]}  H:{$candle[2]}  L:{$candle[3]}  C:{$candle[4]}\n";
        }
    }
    echo "\n";

    // -------------------------------------------------------------------------
    // 5. Orderbook — EURUSD
    // -------------------------------------------------------------------------
    echo "5️⃣  EURUSD Orderbook (depth 5)\n";
    $ob = $tradfi->getOrderbook('EURUSD', 5);
    if (isset($ob['result'])) {
        $bids = $ob['result']['b'] ?? [];
        $asks = $ob['result']['a'] ?? [];
        if ($bids) echo "   Best bid : {$bids[0][0]} × {$bids[0][1]}\n";
        if ($asks) echo "   Best ask : {$asks[0][0]} × {$asks[0][1]}\n";
    }
    echo "\n";

    // -------------------------------------------------------------------------
    // 6. Open positions
    // -------------------------------------------------------------------------
    if (!empty($config['api_key'])) {
        echo "6️⃣  Open TradFi Positions\n";
        $positions = $tradfi->getPositions();
        $list = $positions['result']['list'] ?? [];
        $tradfiPositions = array_filter($list, fn($p) => BybitTradFi::isTradFiSymbol($p['symbol'] ?? ''));

        if ($tradfiPositions) {
            foreach ($tradfiPositions as $pos) {
                echo "   {$pos['symbol']}  side={$pos['side']}  size={$pos['size']}  unrealisedPnl={$pos['unrealisedPnl']}\n";
            }
        } else {
            echo "   No open TradFi positions.\n";
        }
        echo "\n";

        // -------------------------------------------------------------------------
        // 7. Place a limit order far from market (safe demo)
        // -------------------------------------------------------------------------
        echo "7️⃣  Place Limit Buy on XAUUSD (far-from-market demo)\n";
        $order = $tradfi->placeOrder(
            symbol:    'XAUUSD',
            side:      'Buy',
            orderType: 'Limit',
            qty:       '0.01',
            price:     '1000',           // well below market — will not fill
            extra: [
                'timeInForce' => 'GTC',
                'takeProfit'  => '1100',
                'stopLoss'    => '950',
            ]
        );

        if (isset($order['result']['orderId'])) {
            $orderId = $order['result']['orderId'];
            echo "   ✅ Order placed!  ID: {$orderId}\n\n";

            // Cancel it immediately
            echo "8️⃣  Cancel the order\n";
            $cancel = $tradfi->cancelOrder('XAUUSD', $orderId);
            if (isset($cancel['result']['orderId'])) {
                echo "   ✅ Order cancelled.\n\n";
            } else {
                echo "   ⚠️  Cancel response: " . ($cancel['retMsg'] ?? 'n/a') . "\n\n";
            }
        } else {
            echo "   retCode={$order['retCode']} retMsg={$order['retMsg']}\n\n";
        }
    }

    // -------------------------------------------------------------------------
    // 8. isTradFiSymbol helper
    // -------------------------------------------------------------------------
    echo "9️⃣  isTradFiSymbol() Detection\n";
    $symbols = ['XAUUSD', 'EURUSD', 'GBPUSD', 'US500USD', 'DE40USD', 'BTCUSDT', 'ETHUSDT', 'XAGUSD'];
    foreach ($symbols as $sym) {
        $flag = BybitTradFi::isTradFiSymbol($sym) ? '✅ TradFi' : '🔸 Crypto';
        echo "   {$flag}  {$sym}\n";
    }
    echo "\n";

    echo "✅ TradFi example completed!\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
