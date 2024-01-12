<?php

use SaintSystems\OData\ODataClient;

require_once("_config.php");

$client = ODataClient::dsmFactoryFromTenantArray($config['tenant'], false);

var_dump(
    $client->from('contacts')->where('E_Mail', 'test@email.com')->get()
);
