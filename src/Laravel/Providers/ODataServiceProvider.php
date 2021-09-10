<?php

namespace SaintSystems\OData\Laravel\Providers;

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
        $tenantToken = self::getTenantToken($request);

        $this->app->singleton(ODataClient::class, function () use ($tenantToken) {
            if (is_null($tenantToken)) throw new \Exception("no_tenant_token", 1);

            $tenant = (\Illuminate\Support\Facades\App::make(TenantServiceClient::class))->get($tenantToken);

            return ODataClient::dsmFactoryFromTenantArray($tenant, config('services.business-central.verify_ssl'));
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
