<?php

namespace Tests;

use SaintSystems\OData\ODataV4Client;

trait ProvideODataV4Client
{
    /**
     * Overrides the instance of ODataClient and TenantClient
     *
     * @param  string $tenantToken
     * @return void
     */
    public function provideODataV4Client(string $tenantToken)
    {
        // Construct instance of OData client and provide it to the application
        $oDataClient = ODataV4Client::dsmFactory('805c29d0-2d38-4699-9011-f70d2ef11240','kho','https://kho.jmatest.dk:8172/bcdk2_webtest-ws/','DSMWS','iav0UhMqwlDgZNCwVFVWd87aO5a+VWYaMqnjn5FCDyA=');
        $this->app->instance(ODataV4Client::class, $oDataClient);

        
    }
}
