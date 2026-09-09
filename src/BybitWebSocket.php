<?php
namespace Tigusigalpa\ByBit;

use WebSocket\Client;
use WebSocket\ConnectionException;

class BybitWebSocket
{
    protected ?string $apiKey;
    protected ?string $apiSecret;
    protected bool $testnet;
    protected bool $demoTrading;
    protected string $region;
    protected string $signature;
    protected ?string $rsaPrivateKey;
    protected ?Client $connection = null;
    protected array $subscriptions = [];
    protected $messageCallback;
    protected bool $isPrivate;
    protected string $category;

    public function __construct(?string $apiKey = null, ?string $apiSecret = null, bool $testnet = false, string $region = 'global', bool $isPrivate = false, string $category = 'spot', bool $demoTrading = false, string $signature = 'hmac', ?string $rsaPrivateKey = null)
    {
        $signature = strtolower(trim($signature));
        if ($signature === '') {
            $signature = 'hmac';
        }
        if (!in_array($signature, ['hmac', 'rsa'], true)) {
            throw new \InvalidArgumentException('Unsupported signature type. Use hmac or rsa.');
        }
        if ($signature === 'rsa' && empty($rsaPrivateKey)) {
            throw new \InvalidArgumentException('RSA signature requires an RSA private key.');
        }
        if ($testnet && $demoTrading) {
            throw new \InvalidArgumentException('Testnet and Demo Trading cannot be enabled together.');
        }

        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->testnet = $testnet;
        $this->demoTrading = $demoTrading;
        $this->region = strtolower(trim($region)) ?: 'global';
        $this->signature = $signature;
        $this->rsaPrivateKey = $rsaPrivateKey;
        $this->isPrivate = $isPrivate;
        $this->category = $category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function setPrivate(bool $isPrivate): self
    {
        if ($this->connection !== null) {
            throw new \LogicException('Close the WebSocket connection before changing its privacy mode.');
        }
        $this->isPrivate = $isPrivate;
        return $this;
    }

    protected function getWebSocketUrl(): string
    {
        $publicPath = $this->getPublicPath();

        if ($this->demoTrading) {
            // Demo Trading exposes only private streams. Public market data is mainnet data.
            return $this->isPrivate
                ? 'wss://stream-demo.bybit.com/v5/private'
                : 'wss://stream.bybit.com/v5/public/' . $publicPath;
        }

        if ($this->testnet) {
            if ($this->region === 'jp') {
                return $this->isPrivate
                    ? 'wss://stream-testnet.manepa.jp/v5/private'
                    : 'wss://stream-testnet.manepa.jp/v5/public/' . $publicPath;
            }
            if ($this->region === 'hk') {
                return $this->isPrivate
                    ? 'wss://stream-testnet.spark-fintech.com/v5/private'
                    : 'wss://stream-testnet.spark-fintech.com/v5/public/' . $publicPath;
            }
            return $this->isPrivate 
                ? 'wss://stream-testnet.bybit.com/v5/private'
                : 'wss://stream-testnet.bybit.com/v5/public/' . $publicPath;
        }

        switch ($this->region) {
            case 'nl':
                return $this->isPrivate 
                    ? 'wss://stream.bybit.nl/v5/private'
                    : 'wss://stream.bybit.nl/v5/public/' . $publicPath;
            case 'tr':
                return $this->isPrivate 
                    ? 'wss://stream.bybit.tr/v5/private'
                    : 'wss://stream.bybit.tr/v5/public/' . $publicPath;
            case 'kz':
                return $this->isPrivate 
                    ? 'wss://stream.bybit.kz/v5/private'
                    : 'wss://stream.bybit.kz/v5/public/' . $publicPath;
            case 'ge':
                return $this->isPrivate 
                    ? 'wss://stream.bybitgeorgia.ge/v5/private'
                    : 'wss://stream.bybitgeorgia.ge/v5/public/' . $publicPath;
            case 'ae':
                return $this->isPrivate 
                    ? 'wss://stream.bybit.ae/v5/private'
                    : 'wss://stream.bybit.ae/v5/public/' . $publicPath;
            case 'id':
                return $this->isPrivate
                    ? 'wss://stream.bybit.id/v5/private'
                    : 'wss://stream.bybit.id/v5/public/' . $publicPath;
            case 'jp':
                return $this->isPrivate
                    ? 'wss://stream.manepa.jp/v5/private'
                    : 'wss://stream.manepa.jp/v5/public/' . $publicPath;
            case 'hk':
                return $this->isPrivate
                    ? 'wss://stream.spark-fintech.com/v5/private'
                    : 'wss://stream.spark-fintech.com/v5/public/' . $publicPath;
            default:
                return $this->isPrivate 
                    ? 'wss://stream.bybit.com/v5/private'
                    : 'wss://stream.bybit.com/v5/public/' . $publicPath;
        }
    }

    protected function getPublicPath(): string
    {
        switch (strtolower($this->category)) {
            case 'linear':
            case 'usdt':
            case 'usdc':
                return 'linear';
            case 'inverse':
                return 'inverse';
            case 'option':
                return 'option';
            case 'spread':
                return 'spread';
            case 'rfq':
                return 'rfq';
            default:
                return 'spot';
        }
    }

    /**
     * Return the WebSocket endpoint selected by the current configuration.
     */
    public function endpoint(): string
    {
        return $this->getWebSocketUrl();
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
        $signature = $this->signString('GET/realtime' . $expires);

        $authMessage = [
            'op' => 'auth',
            'args' => [$this->apiKey, $expires, $signature]
        ];

        $this->send($authMessage);
    }

    protected function signString(string $payload): string
    {
        if ($this->signature === 'rsa') {
            $key = openssl_pkey_get_private($this->rsaPrivateKey);
            if ($key === false) {
                throw new \RuntimeException('Unable to load the configured RSA private key.');
            }
            if (!openssl_sign($payload, $signature, $key, OPENSSL_ALGO_SHA256)) {
                throw new \RuntimeException('Unable to create the RSA signature.');
            }
            return base64_encode($signature);
        }

        return hash_hmac('sha256', $payload, (string)$this->apiSecret);
    }

    public function send(array $message): void
    {
        if (!$this->connection) {
            $this->connect();
        }

        $this->connection->text(json_encode($message, JSON_THROW_ON_ERROR));
    }

    public function subscribe(array $topics): void
    {
        $message = [
            'op' => 'subscribe',
            'args' => $topics
        ];

        $this->subscriptions = array_values(array_unique(array_merge($this->subscriptions, $topics)));
        $this->send($message);
    }

    public function unsubscribe(array $topics): void
    {
        $message = [
            'op' => 'unsubscribe',
            'args' => $topics
        ];

        $this->subscriptions = array_values(array_diff($this->subscriptions, $topics));
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
                    $pong = ['op' => 'pong'];
                    if (isset($data['req_id'])) {
                        $pong['req_id'] = $data['req_id'];
                    }
                    $this->send($pong);
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
