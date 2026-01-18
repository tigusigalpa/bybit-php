<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Tigusigalpa\ByBit\BybitWebSocket;

/**
 * Bybit WebSocket Listener Command
 * 
 * Run with: php artisan bybit:websocket {symbol}
 * Example: php artisan bybit:websocket BTCUSDT
 */
class BybitWebSocketCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'bybit:websocket 
                            {symbol=BTCUSDT : The trading symbol to monitor}
                            {--type=public : Stream type (public or private)}
                            {--streams=* : Specific streams to subscribe (ticker,trade,orderbook)}';

    /**
     * The console command description.
     */
    protected $description = 'Listen to Bybit WebSocket streams';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $symbol = strtoupper($this->argument('symbol'));
        $type = $this->option('type');
        $streams = $this->option('streams') ?: ['ticker', 'trade', 'orderbook'];

        $this->info("🚀 Starting Bybit WebSocket Listener");
        $this->info("Symbol: {$symbol}");
        $this->info("Type: {$type}");
        $this->info("Streams: " . implode(', ', $streams));
        $this->newLine();

        try {
            $isPrivate = $type === 'private';
            
            // Create WebSocket instance
            $ws = app(BybitWebSocket::class);
            
            // If private, create new instance with credentials
            if ($isPrivate) {
                $ws = new BybitWebSocket(
                    apiKey: config('bybit.api_key'),
                    apiSecret: config('bybit.api_secret'),
                    testnet: config('bybit.testnet'),
                    region: config('bybit.region'),
                    isPrivate: true
                );
            }

            // Subscribe to requested streams
            if ($isPrivate) {
                $this->subscribePrivateStreams($ws);
            } else {
                $this->subscribePublicStreams($ws, $symbol, $streams);
            }

            // Set up message handler
            $ws->onMessage(function($data) use ($isPrivate) {
                if ($isPrivate) {
                    $this->handlePrivateMessage($data);
                } else {
                    $this->handlePublicMessage($data);
                }
            });

            $this->info("✅ Connected and listening...");
            $this->info("Press Ctrl+C to stop");
            $this->newLine();

            // Start listening (blocking)
            $ws->listen();

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Subscribe to public streams
     */
    private function subscribePublicStreams(BybitWebSocket $ws, string $symbol, array $streams): void
    {
        foreach ($streams as $stream) {
            switch ($stream) {
                case 'ticker':
                    $ws->subscribeTicker($symbol);
                    $this->line("✅ Subscribed to ticker");
                    break;
                case 'trade':
                    $ws->subscribeTrade($symbol);
                    $this->line("✅ Subscribed to trades");
                    break;
                case 'orderbook':
                    $ws->subscribeOrderbook($symbol, 50);
                    $this->line("✅ Subscribed to orderbook");
                    break;
                case 'kline':
                    $ws->subscribeKline($symbol, '1');
                    $this->line("✅ Subscribed to 1m klines");
                    break;
            }
        }
    }

    /**
     * Subscribe to private streams
     */
    private function subscribePrivateStreams(BybitWebSocket $ws): void
    {
        $ws->subscribePosition();
        $this->line("✅ Subscribed to positions");
        
        $ws->subscribeOrder();
        $this->line("✅ Subscribed to orders");
        
        $ws->subscribeExecution();
        $this->line("✅ Subscribed to executions");
        
        $ws->subscribeWallet();
        $this->line("✅ Subscribed to wallet");
    }

    /**
     * Handle public stream messages
     */
    private function handlePublicMessage(array $data): void
    {
        if (!isset($data['topic'])) {
            return;
        }

        $topic = $data['topic'];
        $time = now()->format('H:i:s');

        if (strpos($topic, 'tickers') !== false && isset($data['data'])) {
            $ticker = $data['data'];
            $change = floatval($ticker['price24hPcnt']) * 100;
            $emoji = $change >= 0 ? '📈' : '📉';
            
            $this->line("[{$time}] {$emoji} {$ticker['symbol']} - \${$ticker['lastPrice']} ({$change}%)");
        }
        elseif (strpos($topic, 'publicTrade') !== false && isset($data['data'][0])) {
            $trade = $data['data'][0];
            $side = $trade['S'] === 'Buy' ? '🟢' : '🔴';
            
            $this->line("[{$time}] {$side} Trade - \${$trade['p']} x {$trade['v']}");
        }
        elseif (strpos($topic, 'orderbook') !== false && isset($data['data']['b'][0])) {
            $bid = $data['data']['b'][0];
            $ask = $data['data']['a'][0];
            $spread = round($ask[0] - $bid[0], 2);
            
            $this->line("[{$time}] 📚 Bid: \${$bid[0]} | Ask: \${$ask[0]} | Spread: \${$spread}");
        }
    }

    /**
     * Handle private stream messages
     */
    private function handlePrivateMessage(array $data): void
    {
        if (!isset($data['topic'])) {
            if (isset($data['op']) && $data['op'] === 'auth' && $data['success']) {
                $this->info("✅ Authenticated successfully");
            }
            return;
        }

        $topic = $data['topic'];
        $time = now()->format('H:i:s');

        switch ($topic) {
            case 'position':
                if (isset($data['data'][0])) {
                    foreach ($data['data'] as $pos) {
                        if (floatval($pos['size']) > 0) {
                            $this->line("[{$time}] 📊 Position: {$pos['symbol']} {$pos['side']} {$pos['size']} @ \${$pos['entryPrice']}");
                        }
                    }
                }
                break;

            case 'order':
                if (isset($data['data'][0])) {
                    foreach ($data['data'] as $order) {
                        $this->line("[{$time}] 📝 Order: {$order['symbol']} {$order['side']} {$order['orderStatus']}");
                    }
                }
                break;

            case 'execution':
                if (isset($data['data'][0])) {
                    foreach ($data['data'] as $exec) {
                        $this->line("[{$time}] 💰 Fill: {$exec['symbol']} {$exec['side']} {$exec['execQty']} @ \${$exec['execPrice']}");
                    }
                }
                break;

            case 'wallet':
                $this->line("[{$time}] 💼 Wallet updated");
                break;
        }
    }
}
