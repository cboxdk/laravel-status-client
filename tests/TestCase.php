<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Tests;

use Cbox\StatusClient\StatusClientServiceProvider;
use Cbox\StatusClient\Testing\InteractsWithStatus;
use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use InteractsWithStatus;

    public const string PAGE = '01pageid';

    public const string PRIMARY = 'https://acme.cboxstatus.com';

    public const string MIRROR = 'https://data.cboxstatus.com';

    /**
     * What the fake network serves: URL => decoded JSON body, or `'down'` for a refused
     * connection. Anything else is a 404. Change it mid-test to take an origin down.
     *
     * @var array<string, array<mixed>|string>
     */
    protected array $routes = [];

    /**
     * @param  array<string, array<mixed>|string>  $routes
     */
    protected function network(array $routes): void
    {
        $this->routes = $routes;

        Http::preventStrayRequests();
        Http::fake(fn (Request $request): PromiseInterface|Closure => $this->serve($request));
    }

    /**
     * @return list<string>
     */
    protected function requested(): array
    {
        return array_values(array_map(fn (array $pair): string => $pair[0]->url(), Http::recorded()->all()));
    }

    private function serve(Request $request): PromiseInterface|Closure
    {
        $body = $this->routes[$request->url()] ?? null;

        if ($body === 'down') {
            return Http::failedConnection();
        }

        return is_array($body) ? Http::response($body) : Http::response('not found', 404);
    }

    /**
     * @param  Application  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [StatusClientServiceProvider::class];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $config = $app['config'];

        $config->set('cache.default', 'array');
        $config->set('cbox-status.page', self::PAGE);
        $config->set('cbox-status.url', self::PRIMARY);
        $config->set('cbox-status.fallbacks', self::MIRROR);
    }
}
