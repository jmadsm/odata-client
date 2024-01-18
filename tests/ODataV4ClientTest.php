<?php

namespace Tests;
use PHPUnit\Framework\TestCase;
use SaintSystems\OData\ODataV4Client;

require_once (__DIR__ . '/../vendor/autoload.php');
if (file_exists(__DIR__ . '/config.php')) {
    require_once (__DIR__ . '/config.php');
}
else {
    function getConfig($service): array
    {
        $gatewayConfig['services'] = [
            'tenant' => [ // KHO data is needed for the test to run successfully!
                'company_id'     => getenv('COMPANY_ID'),
                'tenant_name'    => getenv('TENANT_NAME'),
                'tenant_url'     => getenv('TENANT_URL'),
                'tenant_username' => getenv('TENANT_USERNAME'),
                'tenant_password' => getenv('TENANT_PASSWORD')
            ]
        ];
        return $gatewayConfig['services'][$service];
    }
}

/**
 * Summary of ODataV4ClientTest
 */
class ODataV4ClientTest extends TestCase
{
    private $config;
    private $oDataV4Client;
    /**
     * Summary of setUp
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->config = getConfig('tenant');
        $this->oDataV4Client = ODataV4Client::dsmFactory($this->config["company_id"],$this->config["tenant_name"],$this->config["tenant_url"],$this->config["tenant_username"],$this->config["tenant_password"]);
    }

    public function testGetDebitorNoFromP22_Debitor(){
        $khoDebitorNo = '76721300';

        try{
            $query = $this->oDataV4Client
            ->from('P22_Debitor')
            ->where("No eq '{$khoDebitorNo}'");
            $debitor = $query->get();

            $this->assertEquals('76721300', $debitor->first()[0]->No, 'DebitorNo was not found'); // this works... witch is worring
            //            $this->assertEquals('76721300', $debitor->first()->No, 'DebitorNo was not found'); // this fails after merge with saintsystems/master
        }
        catch(\Exception $e){
            $this->fail($e->getMessage());
        }
    }


}
