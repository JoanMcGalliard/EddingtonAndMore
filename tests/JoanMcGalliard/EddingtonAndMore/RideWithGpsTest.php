<?php

namespace JoanMcGalliard\EddingtonAndMore;

require_once 'mocks/RideWithGpsMock.php';
require_once 'BaseTestClass.php';
require_once 'JoanMcGalliard/EddingtonAndMore/RideWithGps.php';

use JoanMcGalliard\EddingtonAndMore\mocks\RideWithGpsMock;

class RideWithGpsTest extends BaseTestClass
{
    public function testConnect()
    {
        $mock = new RideWithGpsMock();
        $rideWithGps = new RideWithGps("", array($this, 'myEcho'), $mock);
        $mock->clearResponses("get", "/users/current.json");

        // tests that a successful connect gets the correct auth_token and user_id
        $mock->primeResponse('get', '/users/current.json', include("data/apiResponses/rwgpsConnect1.php"));
        $this->output = "";
        $this->assertEquals("AUTHORITY TOKEN", $rideWithGps->connect("u", "p"));
        $this->assertEquals("AUTHORITY TOKEN", $rideWithGps->getAuth());
        $this->assertEquals(99999, $rideWithGps->getUserId());
        $this->assertEquals(".", $this->output);

        // If connect returns an error, handle it graciously.
        $mock = new RideWithGpsMock();
        $rideWithGps = new RideWithGps("", array($this, 'myEcho'), $mock);
        $mock->primeResponse('get', '/users/current.json', include("data/apiResponses/rwgpsConnect2.php"));
        $this->output = "";
        $this->assertEquals(null, $rideWithGps->connect("u", "p"));
        $this->assertEquals(null, $rideWithGps->getAuth());
        $this->assertEquals("", $rideWithGps->getUserId());
        $this->assertEquals("Unable to authenticate, please provide a valid username/password, auth_token or a session",
            $rideWithGps->getError());

        $this->assertEquals(".", $this->output);


    }

    protected function setUp()
    {
        parent::setUp();
        date_default_timezone_set('UTC');
    }

    protected function tearDown()
    {
        parent::tearDown();
    }
}

?>
