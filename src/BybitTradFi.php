<?php

namespace Tigusigalpa\ByBit;

/**
 * TradFi support for Bybit V5 API.
 *
 * Provides convenience methods for trading traditional financial instruments
 * — gold, silver, forex pairs, stock CFDs, and indices — all available as
 * linear perpetuals on Bybit through the standard V5 API.
 *
 * Usage:
 *   $tradfi = new BybitTradFi($client);
 *   $ticker = $tradfi->getTicker('XAUUSD');
 *   $tradfi->placeOrder('EURUSD', 'Buy', 'Limit', '1', '1.0850');
 */
class BybitTradFi
{
    public const CATEGORY = 'linear';

    /** @var string[] Gold, silver, platinum */
    public const METALS = ['XAUUSD', 'XAGUSD', 'XPTUSD'];

    /** @var string[] Major forex pairs */
    public const FOREX_MAJORS = ['EURUSD', 'GBPUSD', 'USDJPY', 'USDCHF', 'AUDUSD', 'NZDUSD', 'USDCAD'];

    /** @var string[] Minor forex pairs */
    public const FOREX_MINORS = ['EURGBP', 'EURJPY', 'GBPJPY', 'EURCHF', 'AUDCAD', 'AUDNZD', 'CADJPY'];

    /** @var string[] US stock CFDs */
    public const US_STOCKS = ['AAPLUSDT', 'AMZNUSDT', 'TSLAUSDT', 'GOOGLSDT', 'MSFTUSDT', 'METAUSDT', 'NVDAUSDT', 'NFLXUSDT'];

    /** @var string[] Major index CFDs */
    public const INDICES = ['US30USD', 'US100USD', 'US500USD', 'UK100USD', 'DE40USD', 'JP225USD'];

    /** @var string[] Commodity CFDs */
    private const COMMODITY_PREFIXES = ['OIL', 'NATGAS', 'COPPER', 'WHEAT', 'CORN'];

    /** @var string[] Well-known crypto prefixes (used to exclude from forex detection) */
    private const CRYPTO_PREFIXES = ['BTC', 'ETH', 'SOL', 'BNB', 'XRP', 'ADA', 'DOT', 'MATIC', 'AVAX', 'LINK', 'LTC', 'DOGE', 'SHIB'];

    protected BybitClient $client;

    public function __construct(BybitClient $client)
    {
        $this->client = $client;
    }

    // -------------------------------------------------------------------------
    // Market Data
    // -------------------------------------------------------------------------

    /**
     * Get all available TradFi instruments.
     * Pass $assetClass to filter: 'metal', 'forex', 'stock', 'index', 'commodity', or '' for all.
     */
    public function getInstruments(string $assetClass = ''): array
    {
        $result = $this->client->request('GET', '/v5/market/instruments-info', [
            'category' => self::CATEGORY,
        ]);

        if ($assetClass === '') {
            return $result;
        }

        return $this->filterByAssetClass($result, $assetClass);
    }

    /**
     * Get ticker for a single TradFi symbol.
     */
    public function getTicker(string $symbol): array
    {
        return $this->client->getTickers([
            'category' => self::CATEGORY,
            'symbol'   => $symbol,
        ]);
    }

    /**
     * Get tickers for multiple TradFi symbols.
     * Returns all linear tickers when $symbols is empty.
     */
    public function getTickers(array $symbols = []): array
    {
        if (count($symbols) === 1) {
            return $this->getTicker($symbols[0]);
        }

        return $this->client->getTickers(['category' => self::CATEGORY]);
    }

    /**
     * Get metals tickers (XAUUSD, XAGUSD, XPTUSD).
     */
    public function getMetalsTickers(): array
    {
        return $this->getTickers(self::METALS);
    }

    /**
     * Get major forex pair tickers.
     */
    public function getForexTickers(): array
    {
        return $this->getTickers(self::FOREX_MAJORS);
    }

    /**
     * Get US stock CFD tickers.
     */
    public function getStockTickers(): array
    {
        return $this->getTickers(self::US_STOCKS);
    }

    /**
     * Get index CFD tickers.
     */
    public function getIndexTickers(): array
    {
        return $this->getTickers(self::INDICES);
    }

    /**
     * Get kline/candlestick data for a TradFi symbol.
     *
     * @param string $symbol   e.g. 'XAUUSD'
     * @param string $interval V5 interval: 1, 3, 5, 15, 30, 60, 120, 240, 360, 720, D, W, M
     * @param int    $limit    Number of candles (max 200)
     */
    public function getKline(string $symbol, string $interval = '60', int $limit = 50): array
    {
        return $this->client->getKline([
            'category' => self::CATEGORY,
            'symbol'   => $symbol,
            'interval' => $interval,
            'limit'    => $limit,
        ]);
    }

    /**
     * Get order book depth for a TradFi symbol.
     *
     * @param int $depth 1, 25, 50, 100, or 200
     */
    public function getOrderbook(string $symbol, int $depth = 25): array
    {
        return $this->client->getOrderbook([
            'category' => self::CATEGORY,
            'symbol'   => $symbol,
            'limit'    => $depth,
        ]);
    }

    /**
     * Get instrument info (includes swap/overnight fee fields) for a TradFi symbol.
     */
    public function getSwapFee(string $symbol): array
    {
        return $this->client->request('GET', '/v5/market/instruments-info', [
            'category' => self::CATEGORY,
            'symbol'   => $symbol,
        ]);
    }

    // -------------------------------------------------------------------------
    // Account / Positions
    // -------------------------------------------------------------------------

    /**
     * Get open TradFi positions.
     * Pass $symbol = '' to get all linear positions.
     */
    public function getPositions(string $symbol = ''): array
    {
        $params = ['category' => self::CATEGORY];
        if ($symbol !== '') {
            $params['symbol'] = $symbol;
        }
        return $this->client->getPositions($params);
    }

    /**
     * Get trading fee rate for a TradFi symbol.
     */
    public function getFeeRate(string $symbol = ''): array
    {
        $params = ['category' => self::CATEGORY];
        if ($symbol !== '') {
            $params['symbol'] = $symbol;
        }
        return $this->client->request('GET', '/v5/account/fee-rate', $params);
    }

    // -------------------------------------------------------------------------
    // Orders
    // -------------------------------------------------------------------------

    /**
     * Place a TradFi order.
     *
     * @param string      $symbol      e.g. 'XAUUSD', 'EURUSD', 'US500USD'
     * @param string      $side        'Buy' or 'Sell'
     * @param string      $orderType   'Market' or 'Limit'
     * @param string      $qty         Quantity as string
     * @param string|null $price       Required for Limit orders
     * @param array       $extra       Optional overrides: timeInForce, takeProfit, stopLoss,
     *                                 positionIdx, reduceOnly, orderLinkId
     */
    public function placeOrder(
        string  $symbol,
        string  $side,
        string  $orderType,
        string  $qty,
        ?string $price = null,
        array   $extra = []
    ): array {
        $payload = [
            'category'    => self::CATEGORY,
            'symbol'      => $symbol,
            'side'        => $side,
            'orderType'   => $orderType,
            'qty'         => $qty,
            'timeInForce' => $extra['timeInForce'] ?? 'GTC',
            'positionIdx' => $extra['positionIdx'] ?? 0,
        ];

        if ($orderType === 'Limit' && $price !== null) {
            $payload['price'] = $price;
        }

        foreach (['takeProfit', 'stopLoss', 'reduceOnly', 'orderLinkId'] as $field) {
            if (isset($extra[$field])) {
                $payload[$field] = $extra[$field];
            }
        }

        unset($extra['timeInForce'], $extra['positionIdx'], $extra['takeProfit'],
              $extra['stopLoss'], $extra['reduceOnly'], $extra['orderLinkId']);

        if ($extra) {
            $payload = array_replace($payload, $extra);
        }

        return $this->client->createOrder($payload);
    }

    /**
     * Close an open TradFi position at market price.
     *
     * @param string $symbol      e.g. 'XAUUSD'
     * @param string $side        Current position side ('Buy' to close a long, 'Sell' to close a short)
     * @param string $qty         Quantity to close
     * @param int    $positionIdx 0 = one-way mode
     */
    public function closePosition(string $symbol, string $side, string $qty, int $positionIdx = 0): array
    {
        $closeSide = strtoupper($side) === 'BUY' ? 'Sell' : 'Buy';

        return $this->client->createOrder([
            'category'    => self::CATEGORY,
            'symbol'      => $symbol,
            'side'        => $closeSide,
            'orderType'   => 'Market',
            'qty'         => $qty,
            'timeInForce' => 'IOC',
            'positionIdx' => $positionIdx,
            'reduceOnly'  => true,
        ]);
    }

    /**
     * Set leverage for a TradFi symbol.
     */
    public function setLeverage(string $symbol, float $leverage): array
    {
        return $this->client->setLeverage(self::CATEGORY, $symbol, $leverage);
    }

    /**
     * Cancel a TradFi order by orderId or orderLinkId.
     */
    public function cancelOrder(string $symbol, string $orderId = '', string $orderLinkId = ''): array
    {
        $payload = [
            'category' => self::CATEGORY,
            'symbol'   => $symbol,
        ];
        if ($orderId !== '') {
            $payload['orderId'] = $orderId;
        }
        if ($orderLinkId !== '') {
            $payload['orderLinkId'] = $orderLinkId;
        }
        return $this->client->cancelOrder($payload);
    }

    /**
     * Get open TradFi orders.
     */
    public function getOpenOrders(string $symbol = ''): array
    {
        $params = ['category' => self::CATEGORY];
        if ($symbol !== '') {
            $params['symbol'] = $symbol;
        }
        return $this->client->getOpenOrders($params);
    }

    /**
     * Get TradFi order history.
     */
    public function getOrderHistory(string $symbol = '', int $limit = 50): array
    {
        $params = ['category' => self::CATEGORY, 'limit' => $limit];
        if ($symbol !== '') {
            $params['symbol'] = $symbol;
        }
        return $this->client->getHistoryOrders($params);
    }

    /**
     * Get TradFi trade/execution history.
     */
    public function getTradeHistory(string $symbol = '', int $limit = 50): array
    {
        $params = ['category' => self::CATEGORY, 'limit' => $limit];
        if ($symbol !== '') {
            $params['symbol'] = $symbol;
        }
        return $this->client->request('GET', '/v5/execution/list', $params);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Determine whether a symbol is a TradFi instrument (metal, forex, or index)
     * rather than a crypto perpetual.
     */
    public static function isTradFiSymbol(string $symbol): bool
    {
        $s = strtoupper($symbol);

        // Metals
        if (strpos($s, 'XAU') === 0 || strpos($s, 'XAG') === 0 || strpos($s, 'XPT') === 0) {
            return true;
        }

        // Forex: 6 alpha chars, not a known crypto prefix
        if (strlen($s) === 6 && preg_match('/^[A-Z]+$/', $s)) {
            foreach (self::CRYPTO_PREFIXES as $prefix) {
                if (strpos($s, $prefix) === 0) {
                    return false;
                }
            }
            return true;
        }

        // Indices
        $indexPrefixes = ['US30', 'US100', 'US500', 'UK100', 'DE40', 'JP225', 'AU200', 'EU50', 'HK50', 'CN50'];
        foreach ($indexPrefixes as $prefix) {
            if (strpos($s, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }

    // -------------------------------------------------------------------------
    // Internal
    // -------------------------------------------------------------------------

    private function filterByAssetClass(array $response, string $assetClass): array
    {
        $list = $response['result']['list'] ?? [];
        $assetClass = strtolower($assetClass);
        $filtered = [];

        foreach ($list as $instrument) {
            $symbol = strtoupper($instrument['symbol'] ?? '');
            $match  = false;

            switch ($assetClass) {
                case 'metal':
                    $match = strpos($symbol, 'XAU') === 0
                          || strpos($symbol, 'XAG') === 0
                          || strpos($symbol, 'XPT') === 0;
                    break;

                case 'forex':
                    $match = strlen($symbol) === 6
                          && preg_match('/^[A-Z]+$/', $symbol)
                          && self::isTradFiSymbol($symbol);
                    break;

                case 'index':
                    foreach (['US30', 'US100', 'US500', 'UK100', 'DE40', 'JP225', 'AU200', 'EU50', 'HK50'] as $pfx) {
                        if (strpos($symbol, $pfx) === 0) {
                            $match = true;
                            break;
                        }
                    }
                    break;

                case 'stock':
                    $match = substr($symbol, -4) === 'USDT'
                          && strlen($symbol) > 8
                          && !self::isTradFiSymbol(substr($symbol, 0, -4));
                    break;

                case 'commodity':
                    foreach (self::COMMODITY_PREFIXES as $pfx) {
                        if (strpos($symbol, $pfx) === 0) {
                            $match = true;
                            break;
                        }
                    }
                    break;
            }

            if ($match) {
                $filtered[] = $instrument;
            }
        }

        return [
            'retCode' => $response['retCode'] ?? 0,
            'retMsg'  => $response['retMsg']  ?? 'OK',
            'result'  => [
                'category'       => self::CATEGORY,
                'list'           => $filtered,
                'nextPageCursor' => $response['result']['nextPageCursor'] ?? '',
            ],
            'time'    => $response['time'] ?? null,
        ];
    }
}
