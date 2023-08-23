<?php

namespace Tests;
use PHPUnit\Framework\TestCase;
use SaintSystems\OData\ODataV4Client;


/**
 * Summary of ODataV4ClientTest
 */
class ODataV4ClientTest extends TestCase
{
    //use ProvideODataV4Client, CreatesApplication;
    /**
     * Summary of setUp
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testGetDebitorNoFromP22_Debitor(){
        $oDataClient = ODataV4Client::dsmFactory('805c29d0-2d38-4699-9011-f70d2ef11240','kho','https://kho.jmatest.dk:8172/bcdk2_webtest-ws/','DSMWS','iav0UhMqwlDgZNCwVFVWd87aO5a+VWYaMqnjn5FCDyA=');
        $khoDebitorNo = '76721300';

        try{
            $query = $oDataClient
            ->from('P22_Debitor')
            ->where("No eq '{$khoDebitorNo}'");
            $debitor = $query->get();
        }
        catch(\Exception $e){
            $this->fail($e->getMessage());
        }
        $this->assertEquals('76721300', $debitor->first()->No, 'DebitorNo was not found');
    }
    
    
}