<?php

namespace JoanMcGalliard\EddingtonAndMore;

require_once "JoanMcGalliard/EddingtonAndMore/Strava.php";
require_once 'BaseTestClass.php';


class StravaTest extends  BaseTestClass
{
    protected $classUnderTest='JoanMcGalliard\EddingtonAndMore\Strava';
    public function testGetRides()
    {
        $mock = $this->getMockBuilder('StravaApi')->setMethods(array('getAuth', 'setAuth', 'get'))->getMock();

        $strava = new Strava("", "", array($this, 'myEcho'), $mock);

        // tests that a simple request for rides returns expect structure.

        $mock->expects($this->at(0))->method('get')
            ->with('activities', array('per_page' => 200, 'page' => 1))
            ->willReturn(include("data/input/stravaActivities1.php"));

        $this->output = "";
        $this->assertEquals(include("data/expected/stravaActivities1.php"), $strava->getRides(null, null));
        $this->assertEquals("", $strava->getError());
        $this->assertEquals(".", $this->output);


        // if we get an error from strava, we should record an error.
        $mock->expects($this->at(0))->method('get')
            ->with('activities', array('per_page' => 2, 'page' => 1))
            ->willReturn(include("data/input/stravaActivities1.php"));
        $mock->expects($this->at(1))->method('get')
            ->with('activities', array('per_page' => 2, 'page' => 2))
            ->willReturn("Operation timed out after 0 milliseconds with 0 out of 0 bytes received");

        $this->assertEquals(include("data/expected/stravaActivities1.php"), $strava->getRides(null, null, 2));
        $this->assertEquals("Operation timed out after 0 milliseconds with 0 out of 0 bytes received<br>",
            $strava->getError());

        // split rides from gpx file

//        Rides that would be split, if splitting was turned on
        $mock->expects($this->at(0))->method('get')
            ->with('activities', array('per_page' => 200, 'page' => 1))
            ->willReturn(include("data/input/stravaActivities2.php"));
        $this->assertEquals(include("data/expected/stravaActivities2.php"), $strava->getRides(null, null, 200));
        $this->assertEquals("", $strava->getError());

        //as above, with split on, but no gpx file

        global $scratchDirectory;
        $scratchDirectory="gpx_temp_dir";
        $this->cleanDirectory($scratchDirectory);

        $strava->setSplitOvernightRides(true);
        $mock->expects($this->at(0))->method('get')
            ->with('activities', array('per_page' => 200, 'page' => 1))
            ->willReturn(include("data/input/stravaActivities2.php"));
        $this->assertEquals(include("data/expected/stravaActivities2.php"), $strava->getRides(null, null, 200));
        $this->assertEquals("", $strava->getError());


        copy(__DIR__ . DIRECTORY_SEPARATOR.'data/input/London-Edinburgh-London_2013.gpx',"$scratchDirectory/".$strava->getUserId()."-2013-07-28T09_04_51Z.gpx");
        $mock->expects($this->at(0))->method('get')
            ->with('activities', array('per_page' => 200, 'page' => 1))
            ->willReturn(include("data/input/stravaActivities2.php"));
        $this->assertEquals(include("data/expected/stravaActivities2a.php"), $strava->getRides(null, null, 200));
        $this->assertEquals("", $strava->getError());


    }

//    public function testNumberOfDays() {
//        $numberOfDays = $this->getMethod('numberOfDays');
//        $strava = new Strava("", "", array($this, 'myEcho'));
//
//        $start_time = "2016-02-11T10:16:54Z"; //10:16 GMT, 21:16 Melbourne time, 4:16 chicago.
//        $duration = 4*60*60; //4 hours
//
//        $this->assertEquals(1, $numberOfDays->invokeArgs($strava, array($start_time, "UTC", $duration)));
//        $this->assertEquals(2, $numberOfDays->invokeArgs($strava, array($start_time, "Australia/Melbourne", $duration)));
//        $this->assertEquals(1, $numberOfDays->invokeArgs($strava, array($start_time, "America/Chicago", $duration)));
//        $duration =14*60*60; //14 hours
//        $this->assertEquals(2, $numberOfDays->invokeArgs($strava, array($start_time, "UTC", $duration)));
//        $this->assertEquals(2, $numberOfDays->invokeArgs($strava, array($start_time, "Australia/Melbourne", $duration)));
//        $this->assertEquals(1, $numberOfDays->invokeArgs($strava, array($start_time, "America/Chicago", $duration)));
//
//
//    }
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
    public function __construct($clientId, $clientSecret, $stravaApi = null)
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
    public function authenticationUrl($redirect, $approvalPrompt, $scope, $state)

 */

?>
