<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tigusigalpa\ByBit\BybitClient;
use Tigusigalpa\ByBit\Facades\Bybit;

/**
 * Example Trading Controller
 * 
 * Demonstrates how to use Bybit SDK in Laravel controllers
 * with both dependency injection and facade patterns.
 */
class TradingController extends Controller
{
    /**
     * Constructor with dependency injection
     */
    public function __construct(
        private BybitClient $bybit
    ) {}

    /**
     * Get account balance
     */
    public function getBalance(): JsonResponse
    {
        try {
            $balance = $this->bybit->getWalletBalance([
                'accountType' => 'UNIFIED',
                'coin' => 'USDT'
            ]);

            if (isset($balance['result']['list'][0])) {
                $account = $balance['result']['list'][0];
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'total_equity' => $account['totalEquity'],
                        'total_wallet_balance' => $account['totalWalletBalance'],
                        'total_available' => $account['totalAvailableBalance'],
                        'coins' => $account['coin']
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch balance'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get market ticker (using facade)
     */
    public function getTicker(string $symbol): JsonResponse
    {
        try {
            $ticker = Bybit::getTickers([
                'category' => 'linear',
                'symbol' => strtoupper($symbol)
            ]);

            if (isset($ticker['result']['list'][0])) {
                $data = $ticker['result']['list'][0];
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'symbol' => $data['symbol'],
                        'last_price' => $data['lastPrice'],
                        'price_24h_pcnt' => $data['price24hPcnt'],
                        'high_24h' => $data['highPrice24h'],
                        'low_24h' => $data['lowPrice24h'],
                        'volume_24h' => $data['volume24h']
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Ticker not found'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Place a market order
     */
    public function placeMarketOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'symbol' => 'required|string',
            'side' => 'required|in:Buy,Sell',
            'qty' => 'required|numeric|min:0'
        ]);

        try {
            $order = $this->bybit->createOrder([
                'category' => 'linear',
                'symbol' => strtoupper($validated['symbol']),
                'side' => $validated['side'],
                'orderType' => 'Market',
                'qty' => (string)$validated['qty']
            ]);

            if (isset($order['result']['orderId'])) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'order_id' => $order['result']['orderId'],
                        'order_link_id' => $order['result']['orderLinkId']
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $order['retMsg'] ?? 'Failed to place order'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Place a limit order with TP/SL
     */
    public function placeLimitOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'symbol' => 'required|string',
            'side' => 'required|in:Buy,Sell',
            'qty' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'take_profit' => 'nullable|numeric|min:0',
            'stop_loss' => 'nullable|numeric|min:0'
        ]);

        try {
            $orderParams = [
                'category' => 'linear',
                'symbol' => strtoupper($validated['symbol']),
                'side' => $validated['side'],
                'orderType' => 'Limit',
                'qty' => (string)$validated['qty'],
                'price' => (string)$validated['price'],
                'timeInForce' => 'GTC'
            ];

            if (isset($validated['take_profit'])) {
                $orderParams['takeProfit'] = (string)$validated['take_profit'];
            }

            if (isset($validated['stop_loss'])) {
                $orderParams['stopLoss'] = (string)$validated['stop_loss'];
            }

            $order = $this->bybit->createOrder($orderParams);

            if (isset($order['result']['orderId'])) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'order_id' => $order['result']['orderId'],
                        'order_link_id' => $order['result']['orderLinkId']
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $order['retMsg'] ?? 'Failed to place order'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get open orders
     */
    public function getOpenOrders(string $symbol): JsonResponse
    {
        try {
            $orders = $this->bybit->getOpenOrders([
                'category' => 'linear',
                'symbol' => strtoupper($symbol)
            ]);

            if (isset($orders['result']['list'])) {
                return response()->json([
                    'success' => true,
                    'data' => $orders['result']['list']
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'symbol' => 'required|string',
            'order_id' => 'required|string'
        ]);

        try {
            $result = $this->bybit->cancelOrder([
                'category' => 'linear',
                'symbol' => strtoupper($validated['symbol']),
                'orderId' => $validated['order_id']
            ]);

            if (isset($result['result']['orderId'])) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order cancelled successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['retMsg'] ?? 'Failed to cancel order'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get positions
     */
    public function getPositions(): JsonResponse
    {
        try {
            $positions = $this->bybit->getPositions([
                'category' => 'linear',
                'settleCoin' => 'USDT'
            ]);

            if (isset($positions['result']['list'])) {
                // Filter only active positions
                $activePositions = array_filter(
                    $positions['result']['list'],
                    fn($pos) => floatval($pos['size']) > 0
                );

                return response()->json([
                    'success' => true,
                    'data' => array_values($activePositions)
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch positions'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
