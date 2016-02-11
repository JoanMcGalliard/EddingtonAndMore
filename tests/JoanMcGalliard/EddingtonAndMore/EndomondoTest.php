<?php

namespace JoanMcGalliard\EddingtonAndMore;

require_once "JoanMcGalliard/EddingtonAndMore/Endomondo.php";
require_once 'BaseTestClass.php';


class EndomondoTest extends  BaseTestClass
{
    protected $classUnderTest='JoanMcGalliard\EddingtonAndMore\Endomondo';

    public function testIsOverNightRide() {
        $isOverNightRide = $this->getMethod('isOverNightRide');
        $mock = new EndomondoApiMock();



        $ride=new \stdClass();
        $ride->duration = 4*60*60; //4 hours
        $ride->start_time = "2016-02-11T10:16:54Z"; //10:16 GMT, 21:16 Melbourne time, 4:16 chicago.

        $endomondo = new Endomondo("", "", "UTC", array($this, 'myEcho'), $mock);
        $this->assertEquals(false, $isOverNightRide->invokeArgs($endomondo, array($ride)));
        $endomondo = new Endomondo("", "", "Australia/Melbourne", array($this, 'myEcho'), $mock);
        $this->assertEquals(true, $isOverNightRide->invokeArgs($endomondo, array($ride)));
        $endomondo = new Endomondo("", "", "America/Chicago", array($this, 'myEcho'), $mock);
        $this->assertEquals(false, $isOverNightRide->invokeArgs($endomondo, array($ride)));
        $ride->duration =14*60*60; //14 hours
        $endomondo = new Endomondo("", "", "UTC", array($this, 'myEcho'), $mock);
        $this->assertEquals(true, $isOverNightRide->invokeArgs($endomondo, array($ride)));
        $endomondo = new Endomondo("", "", "Australia/Melbourne", array($this, 'myEcho'), $mock);
        $this->assertEquals(true, $isOverNightRide->invokeArgs($endomondo, array($ride)));
        $endomondo = new Endomondo("", "", "America/Chicago", array($this, 'myEcho'), $mock);
        $this->assertEquals(false, $isOverNightRide->invokeArgs($endomondo, array($ride)));


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


/*
 *
    public function __construct($clientId, $clientSecret, $endomondoApi = null)
    public function writeScope()
    public function setWriteScope($scope)
    public function setAccessTokenFromCode($code)
    public function setAccessToken($token)
    public function uploadUrl()
    public function isConnected()
    public function getError()
    public function getRides($start_date, $end_date)
    public function getBike($id)
    public function uploadGpx($file_path, $external_id, $external_msg, $name, $description)
    public function activityUrl($activityId)
    public function waitForPendingUploads()
    public function setSplitOvernightRides($getEndomondoSplitRides)
    public function authenticationUrl($redirect, $approvalPrompt, $scope, $state)

 */

/**
 * A mock version of Iamstuartwilson\EndomondoApi
 */
class EndomondoApiMock extends BaseMockClass
{

}


?>
