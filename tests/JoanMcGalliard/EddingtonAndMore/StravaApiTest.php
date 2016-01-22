<?php

require_once 'StravaApiMock.php';

class StravaApiTest extends PHPUnit_Framework_TestCase
{
public function testgetRides () {

    $this->assertTrue(true);
    $mock = new StravaApiMock();
    $stravaApi=new JoanMcGalliard\EddingtonAndMore\StravaApi("", "", $mock);
    $stravaApi->getRides(null, null);

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
}