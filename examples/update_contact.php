<?php

use SaintSystems\OData\ODataClient;

require_once("_config.php");

$client = ODataClient::dsmFactoryFromTenantArray($config['tenant'], false);

var_dump(
    $client->patch("contacts(No='E000000')", [
        "Password" => password_hash('HelloWorld', PASSWORD_BCRYPT)
    ], '*')
);
