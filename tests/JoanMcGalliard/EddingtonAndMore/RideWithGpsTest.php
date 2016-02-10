<?php

namespace JoanMcGalliard\EddingtonAndMore;

require_once 'BaseTestClass.php';
require_once 'JoanMcGalliard/EddingtonAndMore/RideWithGps.php';

use ReflectionClass;

class RideWithGpsTest extends BaseTestClass
{
    public function testUploadGpx()
    {
        $mock = new RideWithGpsMock();
        $rideWithGps = new RideWithGps("", array($this, 'myEcho'), $mock);

        // there was a problem
        $mock->clearResponses("upload", "/trips.json");
        $mock->primeResponse("upload", "/trips.json", '{"error": "There was a problem with your request. You must provide track_points, a route to create from or a file to process."}');
        $this->assertFalse($rideWithGps->uploadGpx('path/to/file.gpx', 999, "hello there", "file name", "description"));
        $this->assertEquals("There was a problem with your request. You must provide track_points, a route to create from or a file to process.", $rideWithGps->getError());
        $mock->primeResponse("get", "/queued_tasks/status.json", '{"queued_tasks": []}');//
        $mock->primeRepeat("get", "/queued_tasks/status.json");
        $rideWithGps->setFileUploadTimeout(0);
        $this->assertEquals([], $rideWithGps->waitForPendingUploads());


        // happy path, file uploaded correctly
        $mock->clearResponses("upload", "/trips.json");
        $mock->primeResponse("upload", "/trips.json", '{"success": 1,"task_id": 8218806}');
        $this->assertTrue($rideWithGps->uploadGpx('path/to/file.gpx', 1000, "hello there", "file name", "description"));
        $expected = new \stdClass();
        $expected->message = "hello there";
        $expected->external_id = 1000;
        $expected->file = "path/to/file.gpx";
        $expected->error = "Timed out waiting for confirmation of upload after 0 seconds";
        $this->assertEquals(array(1000 => $expected), $rideWithGps->waitForPendingUploads());
        $this->assertEquals("", $rideWithGps->getError());

        // upload a second file, there should be 2 in the queue.

        $mock->clearResponses("upload", "/trips.json");
        $mock->primeResponse("upload", "/trips.json", '{"success": 1,"task_id": 8218806}');
        $this->assertTrue($rideWithGps->uploadGpx('path/to/file.gpx', 1000, "hello there", "file name", "description"));
        $mock->primeResponse("upload", "/trips.json", '{"success": 1,"task_id": 8218807}');
        $this->assertTrue($rideWithGps->uploadGpx('path/to/file2.gpx', 999, "hello there2", "file name2", "description 2"));
        $this->assertEquals(2, sizeof($rideWithGps->waitForPendingUploads()));
        $this->assertEquals("", $rideWithGps->getError());
    }

    public function testWaitForPendingUploads()
    {
        $mock = new RideWithGpsMock();
        $rideWithGps = new RideWithGps("", array($this, 'myEcho'), $mock);

        $expected = new \stdClass();
        $expected->message = "hello there";
        $expected->external_id = 1000;
        $expected->file = "path/to/file.gpx";
        $expected->error = "Timed out waiting for confirmation of upload after 1 seconds";
        $expected_array = [];
        $expected_array[1000] = clone($expected);
        $expected->external_id = 2000;
        $expected->file = "path/to/file2.gpx";
        $expected_array[2000] = clone($expected);
        $expected->external_id = 3000;
        $expected->file = "path/to/file3.gpx";
        $expected_array[3000] = clone($expected);


        $mock->clearResponses("upload", "/trips.json");
        $mock->primeResponse("upload", "/trips.json", '{"success": 1,"task_id": 1}');
        $rideWithGps->uploadGpx('path/to/file.gpx', 1000, "hello there", "file name", "description");
        $mock->primeResponse("upload", "/trips.json", '{"success": 1,"task_id": 2}');
        $rideWithGps->uploadGpx('path/to/file2.gpx', 2000, "hello there", "file name", "description");
        $mock->primeResponse("upload", "/trips.json", '{"success": 1,"task_id": 3}');
        $rideWithGps->uploadGpx('path/to/file3.gpx', 3000, "hello there", "file name", "description");

        $rideWithGps->setFileUploadTimeout(1);
        $mock->clearResponses("get", "/queued_tasks/status.json");
        $mock->primeResponse("get", "/queued_tasks/status.json", '{"queued_tasks": []}');//
        $mock->primeRepeat("get", "/queued_tasks/status.json");

        // 2 of 3 rides are safely uploaded, 1 still pending
        $this->assertEquals($expected_array, $rideWithGps->waitForPendingUploads());

        $mock->clearResponses("upload", "/trips.json");
        $mock->primeResponse("upload", "/trips.json", '{"success": 1,"task_id": 1}');
        $rideWithGps->uploadGpx('path/to/file.gpx', 1000, "hello there", "file name", "description");
        $mock->primeResponse("upload", "/trips.json", '{"success": 1,"task_id": 2}');
        $rideWithGps->uploadGpx('path/to/file2.gpx', 2000, "hello there", "file name", "description");
        $mock->primeResponse("upload", "/trips.json", '{"success": 1,"task_id": 3}');
        $rideWithGps->uploadGpx('path/to/file3.gpx', 3000, "hello there", "file name", "description");

        $rideWithGps->setFileUploadTimeout(1);
        $mock->clearResponses("get", "/queued_tasks/status.json");
        $mock->primeResponse("get", "/queued_tasks/status.json", include('data/apiResponses/rwgpsQueuedTasks1.php'));//
        $mock->primeRepeat("get", "/queued_tasks/status.json");

        $expected_array[1000]->rwgps_id = 111111;
        $expected_array[2000]->rwgps_id = 222222;
        $expected_array[3000]->rwgps_id = 333333;
        unset($expected_array[1000]->error);
        unset($expected_array[2000]->error);
        unset($expected_array[3000]->error);


        // all 3 files successfully uploaded
        $this->assertEquals($expected_array, $rideWithGps->waitForPendingUploads());

        $mock->clearResponses("upload", "/trips.json");
        $mock->primeResponse("upload", "/trips.json", '{"success": 1,"task_id": 1}');
        $rideWithGps->uploadGpx('path/to/file.gpx', 1000, "hello there", "file name", "description");
        $mock->primeResponse("upload", "/trips.json", '{"success": 1,"task_id": 2}');
        $rideWithGps->uploadGpx('path/to/file2.gpx', 2000, "hello there", "file name", "description");
        $mock->primeResponse("upload", "/trips.json", '{"success": 1,"task_id": 3}');
        $rideWithGps->uploadGpx('path/to/file3.gpx', 3000, "hello there", "file name", "description");

        $rideWithGps->setFileUploadTimeout(1);
        $mock->clearResponses("get", "/queued_tasks/status.json");
        $mock->primeResponse("get", "/queued_tasks/status.json", include('data/apiResponses/rwgpsQueuedTasks2.php'));//
        $mock->primeRepeat("get", "/queued_tasks/status.json");

        unset($expected_array[3000]->rwgps_id);
        $expected_array[3000]->error="There were no tracks or routes in your file.  Please try a different file format.";

        // 2 uploaded successfully, and 1 failed.
        $this->assertEquals($expected_array, $rideWithGps->waitForPendingUploads());

        $mock->clearResponses("upload", "/trips.json");
        $mock->primeResponse("upload", "/trips.json", '{"success": 1,"task_id": 1}');
        $rideWithGps->uploadGpx('path/to/file.gpx', 1000, "hello there", "file name", "description");
        $mock->primeResponse("upload", "/trips.json", '{"success": 1,"task_id": 2}');
        $rideWithGps->uploadGpx('path/to/file2.gpx', 2000, "hello there", "file name", "description");
        $mock->primeResponse("upload", "/trips.json", '{"success": 1,"task_id": 3}');
        $rideWithGps->uploadGpx('path/to/file3.gpx', 3000, "hello there", "file name", "description");

        $rideWithGps->setFileUploadTimeout(1);
        $mock->clearResponses("get", "/queued_tasks/status.json");
        $mock->primeResponse("get", "/queued_tasks/status.json", include('data/apiResponses/rwgpsQueuedTasks3.php'));//
        $mock->primeRepeat("get", "/queued_tasks/status.json");

        unset($expected_array[3000]->rwgps_id);
        $expected_array[3000]->error="Timed out waiting for confirmation of upload after 1 seconds";

        // 2 uploaded successfully, and 1 pending.
        $this->assertEquals($expected_array, $rideWithGps->waitForPendingUploads());


        $mock->clearResponses("upload", "/trips.json");
        $mock->primeResponse("upload", "/trips.json", '{"success": 1,"task_id": 1}');
        $rideWithGps->uploadGpx('path/to/file.gpx', 1000, "hello there", "file name", "description");
        $mock->primeResponse("upload", "/trips.json", '{"success": 1,"task_id": 2}');
        $rideWithGps->uploadGpx('path/to/file2.gpx', 2000, "hello there", "file name", "description");
        $mock->primeResponse("upload", "/trips.json", '{"success": 1,"task_id": 3}');
        $rideWithGps->uploadGpx('path/to/file3.gpx', 3000, "hello there", "file name", "description");

        $rideWithGps->setFileUploadTimeout(1);
        $mock->clearResponses("get", "/queued_tasks/status.json");
        $mock->primeResponse("get", "/queued_tasks/status.json", include('data/apiResponses/rwgpsQueuedTasks3.php'));//
        $mock->primeResponse("get", "/queued_tasks/status.json", include('data/apiResponses/rwgpsQueuedTasks1.php'));//

        unset($expected_array[3000]->rwgps_id);
        $expected_array[3000]->error="Timed out waiting for confirmation of upload after 1 seconds";

        $expected_array[3000]->rwgps_id=333333;
        unset($expected_array[3000]->error);

        $rideWithGps->setFileUploadTimeout(10);

        // 2 uploaded immediately, and 1 on second try.
        $start=time();
        $this->assertEquals($expected_array, $rideWithGps->waitForPendingUploads());
        // confirming that it stops waiting once it has processed all pending files.
        $this->assertLessThan(4,time()-$start);
    }

    public function testIsConnected()
    {
        // set up.  We need a RideWithGps object with a valid user Id.  It's 99999
        $mock = new RideWithGpsMock();
        $rideWithGps = new RideWithGps("", array($this, 'myEcho'), $mock);
        //  before we get auth token, it should fail.
        $this->assertEquals(false, $rideWithGps->isConnected());
        $this->assertEquals(null, $rideWithGps->getUserId());

        //auth token set, but it's not correct
        $rideWithGps->setAuth("AUTH TOKEN");
        $mock->clearResponses("get", "/users/current.json");
        $mock->primeResponse('get', '/users/current.json', '{"error":"Unable to authenticate, please provide a valid username/password, auth_token or a session"}');
        $this->assertEquals(false, $rideWithGps->isConnected());
        $this->assertEquals(null, $rideWithGps->getUserId());
        $this->assertEquals("Unable to authenticate, please provide a valid username/password, auth_token or a session", $rideWithGps->getError());

        //happy path, everything correct
        $rideWithGps->setAuth("AUTH TOKEN");
        $mock->clearResponses("get", "/users/current.json");
        $mock->primeResponse('get', '/users/current.json', include("data/apiResponses/rwgpsCurrentUser.php"));
        assert($rideWithGps->isConnected());
        $this->assertEquals(99999, $rideWithGps->getUserId());
        // it not need to check with
        assert($rideWithGps->isConnected());
    }

    public function testGetRides()
    {
        // set up.  We need a RideWithGps object with a valid user Id.  It's 99999
        $mock = new RideWithGpsMock();
        $rideWithGps = new RideWithGps("", array($this, 'myEcho'), $mock);
        $mock->clearResponses("get", "/users/current.json");
        $mock->primeResponse('get', '/users/current.json', include("data/apiResponses/rwgpsConnect1.php"));
        $rideWithGps->connect("u", "p");

        // User with one ride
        $mock->clearResponses("get", '/users/99999/trips.json');
        $mock->primeResponse('get', '/users/99999/trips.json', include("data/apiResponses/rwgpsActivities1.php"));
        $this->assertEquals(include("data/expected/rwgpsActivites1.php"), $rideWithGps->getRides(null, null));
        $this->assertEquals("", $rideWithGps->getError());
        $this->assertEquals(".", $this->output);


        // User with 3 rides, two on the same day.
        $mock->clearResponses("get", '/users/99999/trips.json');
        $mock->primeResponse('get', '/users/99999/trips.json', include("data/apiResponses/rwgpsActivities2.php"));
        $this->assertEquals(include("data/expected/rwgpsActivites2.php"), $rideWithGps->getRides(null, null));
        $this->assertEquals("", $rideWithGps->getError());
        $this->assertEquals(".", $this->output);


        // Something goes wrong with authentication.
        $mock->clearResponses("get", '/users/99999/trips.json');
        $mock->primeResponse('get', '/users/99999/trips.json', '{"error":"Unable to authenticate, please provide a valid username/password, auth_token or a session"}');
        $this->assertEquals([], $rideWithGps->getRides(null, null));
        $this->assertEquals("Unable to authenticate, please provide a valid username/password, auth_token or a session", $rideWithGps->getError());
        $this->assertEquals(".", $this->output);

        // Something goes very wrong.
        $mock->clearResponses("get", '/users/99999/trips.json');
        $mock->primeResponse('get', '/users/99999/trips.json', 'PAGE NOT FOUND');
        $this->assertEquals([], $rideWithGps->getRides(null, null));
        $this->assertEquals("PAGE NOT FOUND", $rideWithGps->getError());
        $this->assertEquals(".", $this->output);


        // Getting more than one page of results.
        $mock->primeResponse('get', '/users/99999/trips.json', '{"results":[],"results_count":73}');
        $mock->clearResponses("get", '/users/99999/trips.json');
        $mock->primeResponse('get', '/users/99999/trips.json', include("data/apiResponses/rwgpsActivities3a.php"));
        $mock->primeResponse('get', '/users/99999/trips.json', include("data/apiResponses/rwgpsActivities3b.php"));
        $mock->primeResponse('get', '/users/99999/trips.json', include("data/apiResponses/rwgpsActivities3c.php"));
        $ride = $rideWithGps->getRides(null, null, 3);
        $this->assertEquals(include("data/expected/rwgpsActivites3.php"), $ride);
        $this->assertEquals("", $rideWithGps->getError());
        $this->assertEquals(".", $this->output);

        // As above, but last page is empty list.
        $mock->clearResponses("get", '/users/99999/trips.json');
        $mock->primeResponse('get', '/users/99999/trips.json', include("data/apiResponses/rwgpsActivities3a.php"));
        $mock->primeResponse('get', '/users/99999/trips.json', include("data/apiResponses/rwgpsActivities3b.php"));
        $mock->primeResponse('get', '/users/99999/trips.json', include("data/apiResponses/rwgpsActivities3c.php"));
        $mock->primeResponse('get', '/users/99999/trips.json', '{"results":[],"results_count":73}');
        $ride = $rideWithGps->getRides(null, null, 2);
        $this->assertEquals(include("data/expected/rwgpsActivites3.php"), $ride);
        $this->assertEquals("", $rideWithGps->getError());
        $this->assertEquals(".", $this->output);

        // Getting more than one page of results, but second page is an error.
        $mock->clearResponses("get", '/users/99999/trips.json');
        $mock->primeResponse('get', '/users/99999/trips.json', include("data/apiResponses/rwgpsActivities3a.php"));
        $mock->primeResponse('get', '/users/99999/trips.json', '{"error":"Unable to authenticate, please provide a valid username/password, auth_token or a session"}');
        $mock->primeResponse('get', '/users/99999/trips.json', include("data/apiResponses/rwgpsActivities3c.php"));
        $ride = $rideWithGps->getRides(null, null, 3);
        $this->assertEquals(include("data/expected/rwgpsActivites4.php"), $ride);
        $this->assertEquals("Unable to authenticate, please provide a valid username/password, auth_token or a session", $rideWithGps->getError());
        $this->assertEquals(".", $this->output);

        // tests with date fields populated.

        // both dates set, but all rides lie within them.
        date_default_timezone_set("UTC");
        $mock->clearResponses("get", '/users/99999/trips.json');
        $mock->primeResponse('get', '/users/99999/trips.json', include("data/apiResponses/rwgpsActivities3a.php"));
        $mock->primeResponse('get', '/users/99999/trips.json', include("data/apiResponses/rwgpsActivities3b.php"));
        $mock->primeResponse('get', '/users/99999/trips.json', include("data/apiResponses/rwgpsActivities3c.php"));
        $ride = $rideWithGps->getRides(strtotime("1 January 2012"), strtotime("now"), 3);
        $this->assertEquals(include("data/expected/rwgpsActivites3.php"), $ride);
        $this->assertEquals("", $rideWithGps->getError());
        $this->assertEquals(".", $this->output);

        // both dates set, some rides are before start_date.
        date_default_timezone_set("UTC");
        $mock->clearResponses("get", '/users/99999/trips.json');
        $mock->primeResponse('get', '/users/99999/trips.json', include("data/apiResponses/rwgpsActivities3a.php"));
        $mock->primeResponse('get', '/users/99999/trips.json', include("data/apiResponses/rwgpsActivities3b.php"));
        $mock->primeResponse('get', '/users/99999/trips.json', include("data/apiResponses/rwgpsActivities3c.php"));
        $ride = $rideWithGps->getRides(strtotime("2014-10-18T09:00:00Z"), strtotime("now"), 3);
        $this->assertEquals(include("data/expected/rwgpsActivite5.php"), $ride);
        $this->assertEquals("", $rideWithGps->getError());
        $this->assertEquals(".", $this->output);

        // both dates set, some rides are before start_date and some are after the end date.
        date_default_timezone_set("UTC");
        $mock->clearResponses("get", '/users/99999/trips.json');
        $mock->primeResponse('get', '/users/99999/trips.json', include("data/apiResponses/rwgpsActivities3a.php"));
        $mock->primeResponse('get', '/users/99999/trips.json', include("data/apiResponses/rwgpsActivities3b.php"));
        $mock->primeResponse('get', '/users/99999/trips.json', include("data/apiResponses/rwgpsActivities3c.php"));
        $ride = $rideWithGps->getRides(strtotime("2014-10-18T09:00:00Z"), strtotime("2015-10-18T12:00:00Z"), 3);
        $this->assertEquals(include("data/expected/rwgpsActivite6.php"), $ride);
        $this->assertEquals("", $rideWithGps->getError());
        $this->assertEquals(".", $this->output);
    }

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

        // Good JSON, but no auth_token.
        $mock = new RideWithGpsMock();
        $rideWithGps = new RideWithGps("", array($this, 'myEcho'), $mock);
        $mock->primeResponse('get', '/users/current.json', include("data/apiResponses/rwgpsConnect3.php"));
        $this->output = "";
        $this->assertEquals(null, $rideWithGps->connect("u", "p"));
        $this->assertEquals(null, $rideWithGps->getAuth());
        $this->assertEquals("", $rideWithGps->getUserId());
        $this->assertEquals("Auth Token not found.",
            $rideWithGps->getError());
        $this->assertEquals(".", $this->output);
    }

    public function testConvertToSeconds()
    {
        $convertToSeconds = self::getMethod('convertToSeconds');
        $obj = new RideWithGps("", "");
        $convertToSeconds->invokeArgs($obj, array("01:00:00"));
        $this->assertEquals(1, $convertToSeconds->invokeArgs($obj, array("1")));
        $this->assertEquals(4032, $convertToSeconds->invokeArgs($obj, array("4032")));
        $this->assertEquals(70, $convertToSeconds->invokeArgs($obj, array("1:10")));
        $this->assertEquals(70, $convertToSeconds->invokeArgs($obj, array("01:10")));
        $this->assertEquals(362112, $convertToSeconds->invokeArgs($obj, array("100:35:12")));
        $this->assertEquals(0, $convertToSeconds->invokeArgs($obj, array("00:00:00")));
        $this->assertEquals(0, $convertToSeconds->invokeArgs($obj, array("random string")));
        $this->assertEquals(728, $convertToSeconds->invokeArgs($obj, array("random string:12:08")));
    }

    protected static function getMethod($name)
    {
        $class = new ReflectionClass('JoanMcGalliard\EddingtonAndMore\RideWithGps');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
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

class RideWithGpsMock extends BaseMockClass
{


    private $auth_token;

    /**
     * @return mixed
     */
    public function getAuthToken()
    {
        return $this->auth_token;
    }

    /**
     * @param mixed $auth_token
     */
    public function setAuth($auth_token)
    {
        $this->auth_token = $auth_token;
    }

    public function getAuth()
    {
        return $this->auth_token;
    }

    public function getError()
    {
        return "";

    }
}
?>
