<?php
namespace Tigusigalpa\ByBit;

use WebSocket\Client;
use WebSocket\ConnectionException;

class BybitWebSocket
{
    protected ?string $apiKey;
    protected ?string $apiSecret;
    protected bool $testnet;
    protected string $region;
    protected ?Client $connection = null;
    protected array $subscriptions = [];
    protected $messageCallback;
    protected bool $isPrivate;

    public function __construct(?string $apiKey = null, ?string $apiSecret = null, bool $testnet = false, string $region = 'global', bool $isPrivate = false)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->testnet = $testnet;
        $this->region = $region;
        $this->isPrivate = $isPrivate;
    }

    protected function getWebSocketUrl(): string
    {
        if ($this->testnet) {
            return $this->isPrivate 
                ? 'wss://stream-testnet.bybit.com/v5/private'
                : 'wss://stream-testnet.bybit.com/v5/public/spot';
        }

        switch (strtolower($this->region)) {
            case 'nl':
                return $this->isPrivate 
                    ? 'wss://stream.bybit.nl/v5/private'
                    : 'wss://stream.bybit.nl/v5/public/spot';
            case 'tr':
                return $this->isPrivate 
                    ? 'wss://stream.bybit-tr.com/v5/private'
                    : 'wss://stream.bybit-tr.com/v5/public/spot';
            case 'kz':
                return $this->isPrivate 
                    ? 'wss://stream.bybit.kz/v5/private'
                    : 'wss://stream.bybit.kz/v5/public/spot';
            case 'ge':
                return $this->isPrivate 
                    ? 'wss://stream.bybitgeorgia.ge/v5/private'
                    : 'wss://stream.bybitgeorgia.ge/v5/public/spot';
            case 'ae':
                return $this->isPrivate 
                    ? 'wss://stream.bybit.ae/v5/private'
                    : 'wss://stream.bybit.ae/v5/public/spot';
            default:
                return $this->isPrivate 
                    ? 'wss://stream.bybit.com/v5/private'
                    : 'wss://stream.bybit.com/v5/public/spot';
        }
    }

    public function connect(): void
    {
        $url = $this->getWebSocketUrl();
        $this->connection = new Client($url, [
            'timeout' => 60,
            'persistent' => true,
        ]);

        if ($this->isPrivate && $this->apiKey && $this->apiSecret) {
            $this->authenticate();
        }
    }

    protected function authenticate(): void
    {
        $expires = (int)(microtime(true) * 1000) + 10000;
        $signature = hash_hmac('sha256', 'GET/realtime' . $expires, $this->apiSecret);

        $authMessage = [
            'op' => 'auth',
            'args' => [$this->apiKey, $expires, $signature]
        ];

        $this->send($authMessage);
    }

    public function send(array $message): void
    {
        if (!$this->connection) {
            $this->connect();
        }

        $this->connection->text(json_encode($message));
    }

    public function subscribe(array $topics): void
    {
        $message = [
            'op' => 'subscribe',
            'args' => $topics
        ];

        $this->subscriptions = array_merge($this->subscriptions, $topics);
        $this->send($message);
    }

    public function unsubscribe(array $topics): void
    {
        $message = [
            'op' => 'unsubscribe',
            'args' => $topics
        ];

        $this->subscriptions = array_diff($this->subscriptions, $topics);
        $this->send($message);
    }

    public function subscribeOrderbook(string $symbol, int $depth = 50): void
    {
        $topic = "orderbook.{$depth}.{$symbol}";
        $this->subscribe([$topic]);
    }

    public function subscribeTrade(string $symbol): void
    {
        $topic = "publicTrade.{$symbol}";
        $this->subscribe([$topic]);
    }

    public function subscribeTicker(string $symbol): void
    {
        $topic = "tickers.{$symbol}";
        $this->subscribe([$topic]);
    }

    public function subscribeKline(string $symbol, string $interval = '1'): void
    {
        $topic = "kline.{$interval}.{$symbol}";
        $this->subscribe([$topic]);
    }

    public function subscribePosition(): void
    {
        $this->subscribe(['position']);
    }

    public function subscribeExecution(): void
    {
        $this->subscribe(['execution']);
    }

    public function subscribeOrder(): void
    {
        $this->subscribe(['order']);
    }

    public function subscribeWallet(): void
    {
        $this->subscribe(['wallet']);
    }

    public function onMessage(callable $callback): void
    {
        $this->messageCallback = $callback;
    }

    public function listen(): void
    {
        if (!$this->connection) {
            $this->connect();
        }

        while (true) {
            try {
                $message = $this->connection->receive();
                
                if ($message === null) {
                    continue;
                }

                $data = json_decode($message, true);

                if ($data && $this->messageCallback) {
                    call_user_func($this->messageCallback, $data);
                }

                if (isset($data['op']) && $data['op'] === 'ping') {
                    $this->send(['op' => 'pong']);
                }

            } catch (ConnectionException $e) {
                if ($this->messageCallback) {
                    call_user_func($this->messageCallback, [
                        'error' => true,
                        'message' => $e->getMessage()
                    ]);
                }
                break;
            }
        }
    }

    public function ping(): void
    {
        $this->send(['op' => 'ping']);
    }

    public function close(): void
    {
        if ($this->connection) {
            $this->connection->close();
            $this->connection = null;
        }
    }

    public function getSubscriptions(): array
    {
        return $this->subscriptions;
    }

    public function isConnected(): bool
    {
        return $this->connection !== null;
    }
}
