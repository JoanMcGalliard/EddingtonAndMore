<?php

namespace JoanMcGalliard\EddingtonAndMore;

require_once "JoanMcGalliard/EddingtonAndMore/Strava.php";
require_once 'mocks/StravaApiMock.php';
require_once 'BaseTestClass.php';

use JoanMcGalliard\EddingtonAndMore\mocks\StravaApiMock;

class StravaTest extends  BaseTestClass
{
    public function testGetRides()
    {
        $mock = new StravaApiMock();
        $stravaApi = new Strava("", "", array($this, 'myEcho'), $mock);
        $mock->clearResponses("get", 'activities');

        // tests that a simple request for rides returns expect structure.
        $mock->primeResponse('get', 'activities', include("data/apiResponses/stravaActivities1.php"));
        $this->output = "";
        $this->assertEquals(include("data/expected/stravaActivities1.php"), $stravaApi->getRides(null, null));
        $this->assertEquals("", $stravaApi->getError());
        $this->assertEquals(".", $this->output);


        // if we get an error from strava, we should record an error.
        $mock->primeResponse('get', 'activities', include("data/apiResponses/stravaActivities1.php"));
        $mock->primeResponse('get', 'activities', "Operation timed out after 0 milliseconds with 0 out of 0 bytes received");
        $this->assertEquals(include("data/expected/stravaActivities1.php"), $stravaApi->getRides(null, null, 2));
        $this->assertEquals("Operation timed out after 0 milliseconds with 0 out of 0 bytes received<br>",
            $stravaApi->getError());


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
    public function setSplitOvernightRides($getStravaSplitRides)
    public function authenticationUrl($redirect, $approvalPrompt, $scope, $state)

 */

?>
