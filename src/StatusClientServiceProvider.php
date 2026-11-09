<?php

declare(strict_types=1);

namespace Cbox\StatusClient;

use Cbox\StatusClient\Banners\DefaultBanners;
use Cbox\StatusClient\Console\StatusCheckCommand;
use Cbox\StatusClient\Contracts\Banners;
use Cbox\StatusClient\Contracts\EmergencyVerifier;
use Cbox\StatusClient\Contracts\StatusReader;
use Cbox\StatusClient\Emergency\SodiumEmergencyVerifier;
use Cbox\StatusClient\Http\HttpStatusReader;
use Cbox\StatusClient\Http\NullStatusReader;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class StatusClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cbox-status.php', 'cbox-status');

        $this->app->bind(StatusConfig::class, function (Application $app): StatusConfig {
            $config = $app->make(Config::class)->get('cbox-status');

            return StatusConfig::fromArray(is_array($config) ? $config : []);
        });

        $this->app->bind(EmergencyVerifier::class, fn (Application $app): EmergencyVerifier => new SodiumEmergencyVerifier(
            $app->make(StatusConfig::class)->emergencyKeys,
        ));

        $this->app->bind(StatusReader::class, function (Application $app): StatusReader {
            $config = $app->make(StatusConfig::class);

            if (! $config->configured()) {
                return new NullStatusReader;
            }

            return new HttpStatusReader(
                $app->make(Http::class),
                $app->make(CacheFactory::class)->store($config->cacheStore),
                $app->make(EmergencyVerifier::class),
                $config,
            );
        });

        $this->app->bind(Banners::class, function (Application $app): Banners {
            $config = $app->make(StatusConfig::class);

            return new DefaultBanners($config->components, $config->upcomingWithinHours, $config->showDelayed);
        });

        $this->app->bind(StatusClient::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'cbox-status');
        Blade::componentNamespace('Cbox\\StatusClient\\View\\Components', 'cbox-status');

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/cbox-status.php' => $this->app->configPath('cbox-status.php')], 'cbox-status-config');
            $this->publishes([__DIR__.'/../resources/views' => $this->app->resourcePath('views/vendor/cbox-status')], 'cbox-status-views');
            $this->commands([StatusCheckCommand::class]);
        }
    }
}
