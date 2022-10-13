<?php

namespace SaintSystems\OData\Laravel\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use JmaDsm\TenantService\Client as TenantServiceClient;
use SaintSystems\OData\ODataClient;

class ODataServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Bind ODataClient to container
     *
     * @return void
     */
    public function boot(\Illuminate\Http\Request $request)
    {
        $this->publishes([
            __DIR__ . '/../config/odata.php' => config_path('odata.php')
        ], 'odata-config');

        $tenantToken = self::getTenantToken($request);

        $this->app->singleton(ODataClient::class, function () use ($tenantToken) {
            if (is_null($tenantToken)) {
                if (Config::get('odata.exeption_without_tenant_token')) {
                    throw new \Exception('no_tenant_token', 1);
                }

                return null;
            }

            $tenantJson = Cache::remember(
                'tenant-token-verification_' . $tenantToken,
                Config::get('odata.tenant_token_cache_ttl', 10),
                function () use ($tenantToken) {
                    return json_encode((\Illuminate\Support\Facades\App::make(TenantServiceClient::class))->get($tenantToken));
                }
            );

            return ODataClient::dsmFactoryFromTenantArray(json_decode($tenantJson, true), Config::get('odata.verify_ssl'));
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [TenantServiceClient::class];
    }

    /**
     * Get the tenant token
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    public static function getTenantToken(\Illuminate\Http\Request $request)
    {
        return  $request->header('x-tenant-token',
                $request->input('tenant_token',
                $request->input('tenant-token')));
    }
}
