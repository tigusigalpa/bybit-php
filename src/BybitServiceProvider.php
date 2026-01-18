<?php
namespace Tigusigalpa\ByBit;

use Illuminate\Support\ServiceProvider;

class BybitServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/bybit.php', 'bybit');
        $this->app->singleton(BybitClient::class, function ($app) {
            $config = $app['config']->get('bybit', []);
            return new BybitClient(
                $config['api_key'] ?? '',
                $config['api_secret'] ?? '',
                (bool)($config['testnet'] ?? false),
                $config['region'] ?? 'global',
                (int)($config['recv_window'] ?? 5000),
                $config['signature'] ?? 'hmac',
                $config['rsa_private_key'] ?? null,
                null,
                $config['fees'] ?? null
            );
        });
        $this->app->alias(BybitClient::class, 'bybit');
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/bybit.php' => $this->app->configPath('bybit.php'),
        ], 'bybit-config');
    }
}