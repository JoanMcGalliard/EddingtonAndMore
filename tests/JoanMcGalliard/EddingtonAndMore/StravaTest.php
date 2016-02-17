<?php

namespace JoanMcGalliard\EddingtonAndMore;

require_once "JoanMcGalliard/EddingtonAndMore/Strava.php";
require_once 'BaseTestClass.php';


class StravaTest extends BaseTestClass
{
    protected $classUnderTest = 'JoanMcGalliard\EddingtonAndMore\Strava';

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
        $this->assertEquals(array(), $strava->getRides(1420070400, 1451606399));
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
        $scratchDirectory = "gpx_temp_dir";
        $this->cleanDirectory($scratchDirectory);

        $strava->setSplitOvernightRides(true);
        $mock->expects($this->at(0))->method('get')
            ->with('activities', array('per_page' => 200, 'page' => 1))
            ->willReturn(include("data/input/stravaActivities2.php"));
        $this->assertEquals(include("data/expected/stravaActivities2.php"), $strava->getRides(null, null, 200));
        $this->assertEquals("", $strava->getError());
        $obj = include('data/expected/overnightActivity.php');
        $this->assertEquals(include('data/expected/overnightActivity.php'), $strava->getOvernightActivities());


        copy(__DIR__ . DIRECTORY_SEPARATOR . 'data/input/London-Edinburgh-London_2013.gpx', "$scratchDirectory/" . $strava->getUserId() . "-2013-07-28T09_04_51Z.gpx");
        $mock->expects($this->at(0))->method('get')
            ->with('activities', array('per_page' => 200, 'page' => 1))
            ->willReturn(include("data/input/stravaActivities2.php"));
        $this->assertEquals(include("data/expected/stravaActivities2a.php"), $strava->getRides(null, null, 200));
        $this->assertEquals("", $strava->getError());


    }

    public function testGetActivityDescription()
    {
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

    public function testIsConnected()
    {
        $mock = $this->getMockBuilder('StravaApi')->setMethods(array('setAccessToken', 'get'))->getMock();
        $strava = new Strava("", "", array($this, 'myEcho'), $mock);
        $mock->expects($this->any())->method('setAccessToken');

        //We don't try to connect until we have a token
        $this->assertFalse($strava->isConnected());
        $this->assertEquals("", $strava->getError());


        $strava->setAccessToken("TOKEN");
        $mock->expects($this->at(0))->method('get')->with("athlete")
            ->willReturn(json_decode('{"message":"Authorization Error","errors":[{"resource":"Athlete","field":"access_token","code":"invalid"}]}'));
        $this->assertFalse($strava->isConnected());
        $this->assertEquals('Authorization Error', $strava->getError());

        // we don't try to connect until we get a new TOKEN
        $this->assertFalse($strava->isConnected());

        $strava->setAccessToken("TOKEN");
        $mock->expects($this->at(0))->method('get')->with("athlete")
            ->willReturn(include('data/input/stravaAthlete.php'));
        $this->assertTrue($strava->isConnected());

        // once we are connected, we don't queary API again.
        $this->assertTrue($strava->isConnected());

    }

    public function testDeleteActivity()
    {
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

    public function testSetAccessTokenFromCode()
    {
        $mock = $this->getMockBuilder('StravaApi')->setMethods(array('tokenExchange', 'setAccessToken'))->getMock();
        $strava = new Strava("", "", array($this, 'myEcho'), $mock);
        $mock->expects($this->any())->method('setAccessToken');
        $mock->expects($this->at(0))->method('tokenExchange')
            ->with('CODE')
            ->willReturn(json_decode('{"message":"Authorization Error","errors":[{"resource":"Athlete","field":"access_token","code":"invalid"}]}'));
        $this->assertNull($strava->setAccessTokenFromCode("CODE"));
        $this->assertEquals("", $strava->getError());

        $mock->expects($this->at(0))->method('tokenExchange')
            ->with('CODE')
            ->willReturn(json_decode('{"access_token": "ada886629d64a68f9077c63cda9c886641815295","token_type": "Bearer","athlete": {}}'));
        $this->assertEquals("ada886629d64a68f9077c63cda9c886641815295", $strava->setAccessTokenFromCode("CODE"));
        $this->assertEquals("", $strava->getError());
    }

    public function testGetBike()
    {

        $mock = $this->getMockBuilder('StravaApi')->setMethods(array('get'))->getMock();
        $strava = new Strava("", "", array($this, 'myEcho'), $mock);
        $mock->expects($this->at(0))->method('get')->with('gear/b222222')
            ->willReturn(json_decode('{"message":"Bad Request","errors":[{"resource":"Gear","field":"id","code":"invalid"}]}'));
        $this->assertEquals(array('brand' => "", 'model' => ""), $strava->getBike('b222222'));
        $this->assertEquals("Bad Request", $strava->getError());
        //as we have been told that the id is invalid, it should not ask strava again for this id
        $this->assertEquals(array('brand' => "", 'model' => ""), $strava->getBike('b222222'));

        $mock->expects($this->at(0))->method('get')->with('gear/b333333')
            ->willReturn(json_decode('{"id":"b267883","primary":true,"name":"Avail 2","resource_state":3,"distance":24908017.0,"brand_name":"Giant","model_name":"Avail 2","frame_type":3,"description":""}'));
        $this->setProperty('error', "", $strava);
        $avail = array('brand' => "Giant", 'model' => "Avail 2");
        $this->assertEquals($avail, $strava->getBike('b333333'));
        $this->assertEquals("", $strava->getError());

        // do it again
        $this->assertEquals($avail, $strava->getBike('b333333'));

    }

    public function testUploadGpx()
    {
        $mock = $this->getMockBuilder('StravaApi')->setMethods(array('post'))->getMock();
        $strava = new Strava("", "", array($this, 'myEcho'), $mock);

        $pending = $this->getProperty('pending_uploads');

        // bad file upload, return the error and don't put anything on the queue
        $mock->expects($this->at(0))->method('post')
            ->willReturn(json_decode('{"id":546529071,"external_id":"endomondo_2859253_672012172.gpx","error":"Improperly formatted data.","status":"There was an error processing your activity.","activity_id":null}
'));
        $this->assertEquals("Improperly formatted data.", $strava->uploadGpx("FILE", "EXTERNAL_ID", "EXTERNAL MESSAGE", "NAME", "DESCRIPTION"));
        $pending = $this->getProperty('pending_uploads');
        $this->assertEquals(0, sizeof($pending->getValue($strava)));

        //uploaded OK, so return null and add it to pending queue
        $mock->expects($this->at(0))->method('post')
            ->willReturn(json_decode('{"id":546495227,"external_id":"endomondo_2859253_672012172.gpx","error":null,"status":"Your activity is still being processed.","activity_id":null}'));
        $this->assertNull($strava->uploadGpx("FILE", "EXTERNAL_ID", "EXTERNAL MESSAGE", "NAME", "DESCRIPTION"));
        $this->assertEquals(1, sizeof($pending->getValue($strava)));
        $this->assertEquals(array(546495227 => (object)array('message' => 'EXTERNAL MESSAGE', 'external_id' => 'EXTERNAL_ID', 'file' => "FILE")), $pending->getValue($strava));

    }

    public function testWaitForPendingUploads()
    {
        //three tries before timeout
        $timeout=0.1;
        $sleep=0.045;

        $mock = $this->getMockBuilder('StravaApi')->setMethods(array('get'))->getMock();
        $strava = new Strava("", "", array($this, 'myEcho'), $mock);
        $this->setProperty('pending_uploads', [], $strava);

        // no pending uploads, so it should return immediately
        $time = microtime(true);
        $this->assertEquals([], $strava->waitForPendingUploads(0));
        $this->assertLessThan(1, microtime(true) - $time);

        //never completes, so we should a timeout message
        $pendingList = array(546495227 => (object)array('message' => 'EXTERNAL MESSAGE', 'external_id' => 'EXTERNAL_ID', 'file' => "FILE"));
        $this->setProperty('pending_uploads', $pendingList, $strava);
        $this->setProperty('fileUploadTimeout', $timeout, $strava);
        $mock->expects($this->at(0))->method('get')->with('uploads/546495227')->willReturn(json_decode('{"id":546637956,"external_id":"endomondo_2859253_664423790.gpx","error":null,"status":"Your activity is still being processed.","activity_id":null}'));
        $mock->expects($this->at(1))->method('get')->with('uploads/546495227')->willReturn(json_decode('{"id":546637956,"external_id":"endomondo_2859253_664423790.gpx","error":null,"status":"Your activity is still being processed.","activity_id":null}'));
        $mock->expects($this->at(2))->method('get')->with('uploads/546495227')->willReturn(json_decode('{"id":546637956,"external_id":"endomondo_2859253_664423790.gpx","error":null,"status":"Your activity is still being processed.","activity_id":null}'));
        $result = $strava->waitForPendingUploads($sleep);
        $this->assertEquals(array('EXTERNAL_ID' => (object)array('message' => 'EXTERNAL MESSAGE', 'external_id' => 'EXTERNAL_ID', 'file' => "FILE",
            'error' => "Timed out waiting for confirmation of upload after $timeout seconds",
            'status' => 'Unknown status'
        )), $result);
        $this->assertEquals([], $this->getProperty('pending_uploads')->getValue($strava));

        //never completes, so we should a timeout message
        $pendingList = array(546495227 => (object)array('message' => 'EXTERNAL MESSAGE', 'external_id' => 'EXTERNAL_ID', 'file' => "FILE"));
        $this->setProperty('pending_uploads', $pendingList, $strava);
        $this->setProperty('fileUploadTimeout', $timeout, $strava);
        $mock->expects($this->at(0))->method('get')->with('uploads/546495227')->willReturn(json_decode('{"id":546637956,"external_id":"endomondo_2859253_664423790.gpx","error":null,"status":"Your activity is still being processed.","activity_id":null}'));
        $mock->expects($this->at(1))->method('get')->with('uploads/546495227')->willReturn(json_decode('{"id":546637956,"external_id":"endomondo_2859253_664423790.gpx","error":null,"status":"Your activity is still being processed.","activity_id":null}'));
        $mock->expects($this->at(2))->method('get')->with('uploads/546495227')->willReturn(json_decode('{"id":546638063,"external_id":"endomondo_2859253_663547599.gpx","error":null,"status":"Your activity is ready.","activity_id":654321}'));
        $result = $strava->waitForPendingUploads($sleep);
        $this->assertEquals(array('EXTERNAL_ID' => (object)array('message' => 'EXTERNAL MESSAGE', 'external_id' => 'EXTERNAL_ID', 'file' => "FILE",
            'strava_id' => 654321,
            'status' => 'Your activity is ready.'
        )), $result);
        $this->assertEquals([], $this->getProperty('pending_uploads')->getValue($strava));

        // returns with a error
        $pendingList = array(546495227 => (object)array('message' => 'EXTERNAL MESSAGE', 'external_id' => 'EXTERNAL_ID', 'file' => "FILE"));
        $this->setProperty('pending_uploads', $pendingList, $strava);
        $this->setProperty('fileUploadTimeout', $timeout, $strava);
        $mock->expects($this->at(0))->method('get')->with('uploads/546495227')->willReturn(json_decode('{"id":546637956,"external_id":"endomondo_2859253_664423790.gpx","error":null,"status":"1Your activity is still being processed.","activity_id":null}'));
        $mock->expects($this->at(1))->method('get')->with('uploads/546495227')->willReturn(json_decode('{"id":546637956,"external_id":"endomondo_2859253_664423790.gpx","error":null,"status":"2Your activity is still being processed.","activity_id":null}'));
        $mock->expects($this->at(2))->method('get')->with('uploads/546495227')->willReturn(json_decode('{"id":546650766,"external_id":"endomondo_2859253_330886943.gpx","error":"endomondo_2859253_330886943.gpx duplicate of an uploading activity (546649053)","status":"There was an error processing your activity.","activity_id":null}'));
        $result = $strava->waitForPendingUploads($sleep);
        $this->assertEquals(array('EXTERNAL_ID' => (object)array('message' => 'EXTERNAL MESSAGE', 'external_id' => 'EXTERNAL_ID', 'file' => "FILE",
            'status' => 'There was an error processing your activity.','error' => 'endomondo_2859253_330886943.gpx duplicate of an uploading activity (546649053)'
        )), $result);
        $this->assertEquals([], $this->getProperty('pending_uploads')->getValue($strava));


        //TODO case with multiple uploads, some complete, some error, some timeout
//
//
    }
}


/*
 *
    public function waitForPendingUploads()
    public function authenticationUrl($redirect, $approvalPrompt, $scope, $state)

 */

?>
