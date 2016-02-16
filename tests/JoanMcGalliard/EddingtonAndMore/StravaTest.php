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

        //no rides are returned from Strava
        $mock->expects($this->at(0))->method('get')
            ->with('activities', array('per_page' => 200, 'page' => 1))
            ->willReturn(json_decode('[]'));
        $this->output = "";
        $this->assertEquals(array(), $strava->getRides(null, null));
        $this->assertEquals("", $strava->getError());
        $this->assertEquals(".", $this->output);

        $mock->expects($this->at(0))->method('get')
            ->with('activities', array('per_page' => 200, 'after' => 1420070400))
            ->willReturn(json_decode('[]'));
        $this->output = "";
        $this->assertEquals(array(), $strava->getRides(1420070400,1451606399));
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
        $obj=include('data/expected/overnightActivity.php');
        $this->assertEquals(include('data/expected/overnightActivity.php'), $strava->getOvernightActivities());


        copy(__DIR__ . DIRECTORY_SEPARATOR.'data/input/London-Edinburgh-London_2013.gpx',"$scratchDirectory/".$strava->getUserId()."-2013-07-28T09_04_51Z.gpx");
        $mock->expects($this->at(0))->method('get')
            ->with('activities', array('per_page' => 200, 'page' => 1))
            ->willReturn(include("data/input/stravaActivities2.php"));
        $this->assertEquals(include("data/expected/stravaActivities2a.php"), $strava->getRides(null, null, 200));
        $this->assertEquals("", $strava->getError());


    }
    public function testGetActivityDescription() {
        $mock = $this->getMockBuilder('StravaApi')->setMethods(array('getAuth', 'setAuth', 'get'))->getMock();
        $strava = new Strava("", "", array($this, 'myEcho'), $mock);


        // if api returns an error, we get it as a string

        $mock->expects($this->at(0))->method('get')->with('activities/99999')->willReturn("error message");
        $this->setProperty('error', "", $strava);
        $this->assertNull($strava->getActivityDescription(99999));
        $this->assertEquals("error message", $strava->getError());

        // incorrect JSON
        $mock->expects($this->at(0))->method('get')->with('activities/99999')
            ->willReturn(json_decode('{"message":"Record Not Found","errors":[{"resource":"resource","field":"path","code":"invalid"}]}'));
        $this->setProperty('error', "", $strava);
        $this->assertNull($strava->getActivityDescription(99999));
        $this->assertEquals("Not the expected activity", $strava->getError());

        // realistic JSON
        $mock->expects($this->at(0))->method('get')->with('activities/99999')->willReturn(include('data/input/stravaActivity.php'));
        $this->setProperty('error', "", $strava);
        $this->assertEquals("hello baby", $strava->getActivityDescription(99999));
        $this->assertEquals("", $strava->getError());

        // workout id doesn't match
        $mock->expects($this->at(0))->method('get')->with('activities/88888')->willReturn(include('data/input/stravaActivity.php'));
        $this->setProperty('error', "", $strava);
        $this->assertEquals(null, $strava->getActivityDescription(88888));
        $this->assertEquals("Not the expected activity", $strava->getError());

        // mininal JSON
        $mock->expects($this->at(0))->method('get')->with('activities/99999')
            ->willReturn(json_decode('{"id": 99999, "description": "hello dolly"}'));
        $this->setProperty('error', "", $strava);
        $this->assertEquals("hello dolly", $strava->getActivityDescription(99999));
        $this->assertEquals("", $strava->getError());

        // null description, as would be returned by strava
        $mock->expects($this->at(0))->method('get')->with('activities/99999')
            ->willReturn(json_decode('{"id": 99999, "description": null}'));
        $this->setProperty('error', "", $strava);
        $this->assertEquals(null, $strava->getActivityDescription(99999));
        $this->assertEquals("", $strava->getError());

    }

    public function testDeleteActivity (){
        $mock = $this->getMockBuilder('StravaApi')->setMethods(array('delete'))->getMock();
        $strava = new Strava("", "", array($this, 'myEcho'), $mock);
        $mock->expects($this->at(0))->method('delete')
            ->with('activities/99999')
            ->willReturn(json_decode('{"message":"Record Not Found","errors":[{"resource":"Activity","field":"id","code":"invalid"}]}'));
        $this->assertFalse($strava->deleteActivity(99999));
        $this->assertEquals("Record Not Found", $strava->getError());

        $mock->expects($this->at(0))->method('delete')
            ->with('activities/99999')
            ->willReturn("a string");
        $this->setProperty('error', "", $strava);
        $this->assertFalse($strava->deleteActivity(99999));
        $this->assertEquals("a string", $strava->getError());

        $mock->expects($this->at(0))->method('delete')
            ->with('activities/99999')
            ->willReturn("");
        $this->setProperty('error', "", $strava);
        $this->assertTrue($strava->deleteActivity(99999));
        $this->assertEquals("", $strava->getError());

        $mock->expects($this->at(0))->method('delete')
            ->with('activities/99999')
            ->willReturn(null);
        $this->setProperty('error', "", $strava);
        $this->assertFalse($strava->deleteActivity(99999));
        $this->assertEquals("Unknown error", $strava->getError());

        $error_page = '<html><body><h1>504 Gateway Time-out</h1>
The server didn\'t respond in time.
</body></html>';
        $mock->expects($this->at(0))->method('delete')
            ->with('activities/99999')
            ->willReturn($error_page);
        $this->setProperty('error', "", $strava);
        $this->assertFalse($strava->deleteActivity(99999));
        $this->assertEquals($error_page, $strava->getError());

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
