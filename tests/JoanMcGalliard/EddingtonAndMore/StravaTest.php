<?php

namespace JoanMcGalliard\EddingtonAndMore;

require_once "JoanMcGalliard/EddingtonAndMore/Strava.php";
require_once 'StravaApiMock.php';

use PHPUnit_Framework_TestCase;
use StravaApiMock;

class StravaTest extends PHPUnit_Framework_TestCase
{
    public function testGetRides()
    {
        $mock = new StravaApiMock();
        $mock->clearResponses("get", 'activities');
        $mock->primeResponse('get', 'activities', include("data/apiResponses/example1.php"));
        $stravaApi = new Strava("", "", $mock);
        // tests that a simple request for rides returns expect structure.
        $this->assertEquals(include("data/expected/example1.php"), $stravaApi->getRides(null, null));


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
    public function getUserId()
    public function setSplitOvernightRides($getStravaSplitRides)
    public function authenticationUrl($redirect, $approvalPrompt, $scope, $state)

 */

?>
