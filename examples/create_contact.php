<?php

use SaintSystems\OData\ODataClient;

require_once("_config.php");

$client = ODataClient::dsmFactoryFromTenantArray($config['tenant'], false);

var_dump(
    $client->post("contacts", [
        "Name" => "Test Testersen",
        "Address" => "Testvej 5",
        "Address_2" => "",
        "City" => "Randers",
        "Phone_No" => "11223344",
        "Language_Code" => "",
        "Country_Region_Code" => "DK",
        "Post_Code" => "8900",
        "E_Mail" => "test@email.com",
        "Image" => "00000000-0000-0000-0000-000000000000", // maybe
        "Type" => "Person",
        // "Company_No" => "E000001", // Only needed when creating a company
        "Company_Name" => "Hello",
        "First_Name" => "Test",
        "Middle_Name" => "",
        "Surname" => "Testersen",
        "Job_Title" => "",
        "Mobile_Phone_No" => "11223344",
        "Password" => password_hash('HelloWorld', PASSWORD_BCRYPT)
    ])
);
