<?php

namespace JoanMcGalliard\EddingtonAndMore;

require_once 'BaseTestClass.php';
require_once 'JoanMcGalliard/EddingtonAndMore/RideWithGps.php';


class RideWithGpsTest extends BaseTestClass
{
    protected $classUnderTest = 'JoanMcGalliard\EddingtonAndMore\RideWithGps';

    public function testGetRides()
    {
        // set up.  We need a RideWithGps object with a valid user Id.  It's 99999
        $mock = $this->getMockBuilder('RideWithGpsApi')->setMethods(array('getAuth', 'setAuth', 'get'))->getMock();
        $rideWithGps = new RideWithGps("", array($this, 'myEcho'), "UTC", $mock);
        $mock->expects($this->at(0))->method('get')
            ->with('/users/current.json', array('email' => 'u', 'password' => 'p'))
            ->willReturn(include("data/input/rwgpsConnect1.php"));
        $rideWithGps->connect("u", "p");
        // User with one ride
        $mock->expects($this->at(0))->method('get')
            ->with('/users/99999/trips.json', array('limit' => 100, 'offset' => 0))
            ->willReturn(include("data/input/rwgpsActivities1.php"));

        $this->assertEquals(include("data/expected/rwgpsActivites1.php"), $rideWithGps->getRides(null, null));
        $this->assertEquals("", $rideWithGps->getError());
        $this->assertEquals(".", $this->output);

        $mock->expects($this->at(0))->method('get')
            ->with('/users/99999/trips.json', array('limit' => 100, 'offset' => 0))
            ->willReturn(include("data/input/rwgpsActivities2.php"));

        // User with 3 rides, two on the same day.
        $this->assertEquals(include("data/expected/rwgpsActivites2.php"), $rideWithGps->getRides(null, null));
        $this->assertEquals("", $rideWithGps->getError());
        $this->assertEquals(".", $this->output);


        // Something goes wrong with authentication.
        $mock->expects($this->at(0))->method('get')
            ->with('/users/99999/trips.json', array('limit' => 100, 'offset' => 0))
            ->willReturn('{"error":"Unable to authenticate, please provide a valid username/password, auth_token or a session"}');

        $this->assertEquals([], $rideWithGps->getRides(null, null));
        $this->assertEquals("Unable to authenticate, please provide a valid username/password, auth_token or a session", $rideWithGps->getError());
        $this->assertEquals(".", $this->output);

        // Something goes very wrong.
        $mock->expects($this->at(0))->method('get')
            ->with('/users/99999/trips.json', array('limit' => 100, 'offset' => 0))
            ->willReturn('PAGE NOT FOUND');
        $this->assertEquals([], $rideWithGps->getRides(null, null));
        $this->assertEquals("PAGE NOT FOUND", $rideWithGps->getError());
        $this->assertEquals(".", $this->output);


        // Getting more than one page of results.
        $mock->expects($this->at(0))->method('get')
            ->with('/users/99999/trips.json', array('limit' => 3, 'offset' => 0))
            ->willReturn(include("data/input/rwgpsActivities3a.php"));
        $mock->expects($this->at(1))->method('get')
            ->with('/users/99999/trips.json', array('limit' => 3, 'offset' => 3))
            ->willReturn(include("data/input/rwgpsActivities3b.php"));
        $mock->expects($this->at(2))->method('get')
            ->with('/users/99999/trips.json', array('limit' => 3, 'offset' => 6))
            ->willReturn(include("data/input/rwgpsActivities3c.php"));

        $rides = $rideWithGps->getRides(null, null, 3);
        $this->assertEquals(include("data/expected/rwgpsActivites3.php"), $rides);
        $this->assertEquals("", $rideWithGps->getError());
        $this->assertEquals("...", $this->output);


        // As above, but last page is empty list.

        $mock->expects($this->at(0))->method('get')
            ->with('/users/99999/trips.json', array('limit' => 2, 'offset' => 0))
            ->willReturn(include("data/input/rwgpsActivities3a.php"));
        $mock->expects($this->at(1))->method('get')
            ->with('/users/99999/trips.json', array('limit' => 2, 'offset' => 2))
            ->willReturn(include("data/input/rwgpsActivities3b.php"));
        $mock->expects($this->at(2))->method('get')
            ->with('/users/99999/trips.json', array('limit' => 2, 'offset' => 4))
            ->willReturn(include("data/input/rwgpsActivities3c.php"));
        $mock->expects($this->at(3))->method('get')
            ->with('/users/99999/trips.json', array('limit' => 2, 'offset' => 6))
            ->willReturn('{"results":[],"results_count":73}');

        $rides = $rideWithGps->getRides(null, null, 2);
        $this->assertEquals(include("data/expected/rwgpsActivites3.php"), $rides);
        $this->assertEquals("", $rideWithGps->getError());
        $this->assertEquals("......", $this->output);
        // Getting more than one page of results, but second page is an error.

        $mock->expects($this->at(0))->method('get')
            ->with('/users/99999/trips.json', array('limit' => 3, 'offset' => 0))
            ->willReturn(include("data/input/rwgpsActivities3a.php"));
        $mock->expects($this->at(1))->method('get')
            ->with('/users/99999/trips.json', array('limit' => 3, 'offset' => 3))
            ->willReturn('{"error":"Unable to authenticate, please provide a valid username/password, auth_token or a session"}');;


        $rides = $rideWithGps->getRides(null, null, 3);
        $this->assertEquals(include("data/expected/rwgpsActivites4.php"), $rides);
        $this->assertEquals("Unable to authenticate, please provide a valid username/password, auth_token or a session", $rideWithGps->getError());
        $this->assertEquals(".......", $this->output);

        // tests with date fields populated.

        // both dates set, but all rides lie within them.

        $mock->expects($this->at(0))->method('get')
            ->with('/users/99999/trips.json', array('limit' => 3, 'offset' => 0))
            ->willReturn(include("data/input/rwgpsActivities3a.php"));
        $mock->expects($this->at(1))->method('get')
            ->with('/users/99999/trips.json', array('limit' => 3, 'offset' => 3))
            ->willReturn(include("data/input/rwgpsActivities3b.php"));
        $mock->expects($this->at(2))->method('get')
            ->with('/users/99999/trips.json', array('limit' => 3, 'offset' => 6))
            ->willReturn(include("data/input/rwgpsActivities3c.php"));

        $rides = $rideWithGps->getRides(strtotime("1 January 2012"), strtotime("now"), 3);
        $this->assertEquals(include("data/expected/rwgpsActivites3.php"), $rides);
        $this->assertEquals("", $rideWithGps->getError());
        $this->assertEquals(".........", $this->output);

        // both dates set, some rides are before start_date.
        $mock->expects($this->at(0))->method('get')
            ->with('/users/99999/trips.json', array('limit' => 3, 'offset' => 0))
            ->willReturn(include("data/input/rwgpsActivities3a.php"));
        $mock->expects($this->at(1))->method('get')
            ->with('/users/99999/trips.json', array('limit' => 3, 'offset' => 3))
            ->willReturn(include("data/input/rwgpsActivities3b.php"));


        date_default_timezone_set("UTC");
        $rides = $rideWithGps->getRides(strtotime("2014-10-18T09:00:00Z"), strtotime("now"), 3);
        $this->assertEquals(include("data/expected/rwgpsActivite5.php"), $rides);
        $this->assertEquals("", $rideWithGps->getError());
        $this->assertEquals("..........", $this->output);

        // both dates set, some rides are before start_date and some are after the end date.
        date_default_timezone_set("UTC");
        $mock->expects($this->at(0))->method('get')
            ->with('/users/99999/trips.json', array('limit' => 3, 'offset' => 0))
            ->willReturn(include("data/input/rwgpsActivities3a.php"));
        $mock->expects($this->at(1))->method('get')
            ->with('/users/99999/trips.json', array('limit' => 3, 'offset' => 3))
            ->willReturn(include("data/input/rwgpsActivities3b.php"));

        $rides = $rideWithGps->getRides(strtotime("2014-10-18T09:00:00Z"), strtotime("2015-10-18T12:00:00Z"), 3);
        $this->assertEquals(include("data/expected/rwgpsActivite6.php"), $rides);
        $this->assertEquals("", $rideWithGps->getError());
        $this->assertEquals("...........", $this->output);

        // Includes overnight ride when split is false
        date_default_timezone_set("UTC");
        $mock->expects($this->at(0))->method('get')
            ->with('/users/99999/trips.json', array('limit' => 3, 'offset' => 0))
            ->willReturn(include("data/input/rwgpsActivitiesOvernight.php"));

        $rides = $rideWithGps->getRides(strtotime("2016-02-01T09:00:00Z"), strtotime("2016-02-29T12:00:00Z"), 3);
        $this->assertEquals(include("data/expected/rwgpsActiviteOvernight1.php"), $rides);
        $this->assertEquals("", $rideWithGps->getError());
        $this->assertEquals("...........", $this->output);

        // Includes overnight ride when split is true
        $rideWithGps->setSplitOvernightRides(true);
        $mock->expects($this->at(0))->method('get')
            ->with('/users/99999/trips.json', array('limit' => 3, 'offset' => 0))
            ->willReturn(include("data/input/rwgpsActivitiesOvernight.php"));
        $mock->expects($this->at(1))->method('get')
            ->with('/trips/4838021.json')
            ->willReturn(include('data/input/trip.php'));

        $rides = $rideWithGps->getRides(strtotime("2016-02-01T09:00:00Z"), strtotime("2016-02-29T12:00:00Z"), 3);
        $this->assertEquals(include("data/expected/rwgpsActiviteOvernight2.php"), $rides);
        $this->assertEquals("", $rideWithGps->getError());
        $this->assertEquals("............", $this->output);
    }

    public function testGetPoints()
    {
        $mock = $this->getMockBuilder('RideWithGpsApi')->setMethods(array('getAuth', 'setAuth', 'get'))->getMock();

        $rideWithGps = new RideWithGps("", array($this, 'myEcho'), "UTC", $mock);
        $mock->expects($this->at(0))->method('get')
            ->with("/trips/99999.json")
            ->willReturn(include('data/input/trip.php'));

        /** @var Points $points */
        $points = $rideWithGps->getPoints("99999", "America/New_York", "2016-02-11");
        $this->assertEquals("", $rideWithGps->getError());
        $this->assertNotNull($points);
        $splits = $points->getSplits();
        $this->assertEquals(1, sizeof($splits));
        $this->assertEquals(12.5, round($splits['2016-02-11'] / 1000, 1));

        // same points, but in australia where it's over midnight
        $mock->expects($this->at(0))->method('get')
            ->with("/trips/99999.json")
            ->willReturn(include('data/input/trip.php'));

        $points = $rideWithGps->getPoints("99999", "Australia/Melbourne", "2016-02-11");
        $this->assertEquals("", $rideWithGps->getError());
        $this->assertNotNull($points);
        $splits = $points->getSplits();
        $this->assertEquals(2, sizeof($splits));
        $this->assertEquals(6.0, round($splits['2016-02-11'] / 1000, 1));
        $this->assertEquals(6.5, round($splits['2016-02-12'] / 1000, 1));

        // set of points that caused errors IRL.  Has no valid trackpoints
        $mock->expects($this->at(0))->method('get')
            ->with("/trips/99999.json")
            ->willReturn(include('data/input/trip2.php'));

        $points = $rideWithGps->getPoints("99999", "Australia/Melbourne", "2016-02-11");
        $this->assertEquals("", $rideWithGps->getError());
        $this->assertNotNull($points);
        $splits = $points->getSplits();
        $this->assertEquals(0, sizeof($splits));
    }

    public function testUploadGpx()
    {
        $mock = $this->getMockBuilder('RideWithGpsApi')->setMethods(array('upload', 'get', 'getError'))->getMock();

        $rideWithGps = new RideWithGps("", array($this, 'myEcho'), "UTC", $mock);

        // there was a problem
        $mock->expects($this->at(0))->method('upload')
            ->with("/trips.json", 'path/to/file.gpx', 'file name',
                array('trip[name]' => 'file name', 'trip[description]' => 'description'))
            ->willReturn('{"error": "There was a problem with your request. You must provide track_points, a route to create from or a file to process."}');

        $this->assertFalse($rideWithGps->uploadGpx('path/to/file.gpx', 999, "hello there", "file name", "description"));
        $this->assertEquals("There was a problem with your request. You must provide track_points, a route to create from or a file to process.", $rideWithGps->getError());

        $mock->expects($this->any())->method('get')
            ->with("/queued_tasks/status.json")
            ->willReturn('{"queued_tasks": []}');

        $rideWithGps->setFileUploadTimeout(0);
        $this->assertEquals([], $rideWithGps->waitForPendingUploads(0));


        // happy path, file uploaded correctly
        $mock->expects($this->at(0))->method('upload')
            ->with("/trips.json", 'path/to/file.gpx', 'file name',
                array('trip[name]' => 'file name', 'trip[description]' => 'description'))
            ->willReturn('{"success": 1,"task_id": 8218806}');

        $this->assertTrue($rideWithGps->uploadGpx('path/to/file.gpx', 1000, "hello there", "file name", "description"));
        $expected = new \stdClass();
        $expected->message = "hello there";
        $expected->external_id = 1000;
        $expected->file = "path/to/file.gpx";
        $expected->error = "Timed out waiting for confirmation of upload after 0 seconds";
        $this->assertEquals(array(1000 => $expected), $rideWithGps->waitForPendingUploads(0));
        $this->assertEquals("", $rideWithGps->getError());

        // upload a second file, there should be 2 in the queue.

        $mock->expects($this->at(0))->method('upload')
            ->with("/trips.json", 'path/to/file.gpx', 'file name',
                array('trip[name]' => 'file name', 'trip[description]' => 'description'))
            ->willReturn('{"success": 1,"task_id": 8218806}');

        $this->assertTrue($rideWithGps->uploadGpx('path/to/file.gpx', 1000, "hello there", "file name", "description"));
        $mock->expects($this->at(0))->method('upload')
            ->with("/trips.json", 'path/to/file2.gpx', 'file name2',
                array('trip[name]' => 'file name2', 'trip[description]' => 'description 2'))
            ->willReturn('{"success": 1,"task_id": 8218807}');
        $this->assertTrue($rideWithGps->uploadGpx('path/to/file2.gpx', 999, "hello there2", "file name2", "description 2"));
        $this->assertEquals(2, sizeof($rideWithGps->waitForPendingUploads(0)));
        $this->assertEquals("", $rideWithGps->getError());
    }

    public function testWaitForPendingUploads()
    {
        $mock = $this->getMockBuilder('RideWithGpsApi')->setMethods(array('upload', 'get', 'getError'))->getMock();
        $rideWithGps = new RideWithGps("", array($this, 'myEcho'), "UTC", $mock);

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


        $mock->expects($this->at(0))->method('upload')
            ->willReturn('{"success": 1,"task_id": 1}');
        $rideWithGps->uploadGpx('path/to/file.gpx', 1000, "hello there", "file name", "description");
        $mock->expects($this->at(0))->method('upload')
            ->willReturn('{"success": 1,"task_id": 2}');
        $rideWithGps->uploadGpx('path/to/file2.gpx', 2000, "hello there", "file name", "description");
        $mock->expects($this->at(0))->method('upload')
            ->willReturn('{"success": 1,"task_id": 3}');
        $rideWithGps->uploadGpx('path/to/file3.gpx', 3000, "hello there", "file name", "description");

        $rideWithGps->setFileUploadTimeout(1);
        $mock->expects($this->any())->method('get')
            ->with("/queued_tasks/status.json")
            ->willReturn('{"queued_tasks": []}');
        $this->assertEquals($expected_array, $rideWithGps->waitForPendingUploads(0));


        // 2 of 3 rides are safely uploaded, 1 still pending

        $mock = $this->getMockBuilder('RideWithGpsApi')->setMethods(array('upload', 'get', 'getError'))->getMock();
        $rideWithGps = new RideWithGps("", array($this, 'myEcho'), "UTC", $mock);

        $mock->expects($this->at(0))->method('upload')
            ->willReturn('{"success": 1,"task_id": 1}');
        $rideWithGps->uploadGpx('path/to/file.gpx', 1000, "hello there", "file name", "description");
        $mock->expects($this->at(0))->method('upload')
            ->willReturn('{"success": 1,"task_id": 2}');
        $rideWithGps->uploadGpx('path/to/file2.gpx', 2000, "hello there", "file name", "description");
        $mock->expects($this->at(0))->method('upload')
            ->willReturn('{"success": 1,"task_id": 3}');
        $rideWithGps->uploadGpx('path/to/file3.gpx', 3000, "hello there", "file name", "description");

        $rideWithGps->setFileUploadTimeout(1);
        $mock->expects($this->any())->method('get')
            ->with("/queued_tasks/status.json")
            ->willReturn(include('data/input/rwgpsQueuedTasks1.php'));

        $expected_array[1000]->rwgps_id = 111111;
        $expected_array[2000]->rwgps_id = 222222;
        $expected_array[3000]->rwgps_id = 333333;
        unset($expected_array[1000]->error);
        unset($expected_array[2000]->error);
        unset($expected_array[3000]->error);
        $this->assertEquals($expected_array, $rideWithGps->waitForPendingUploads(0));


        // all 3 files successfully uploaded

        $mock = $this->getMockBuilder('RideWithGpsApi')->setMethods(array('upload', 'get', 'getError'))->getMock();
        $rideWithGps = new RideWithGps("", array($this, 'myEcho'), "UTC", $mock);

        $mock->expects($this->at(0))->method('upload')
            ->willReturn('{"success": 1,"task_id": 1}');
        $rideWithGps->uploadGpx('path/to/file.gpx', 1000, "hello there", "file name", "description");
        $mock->expects($this->at(0))->method('upload')
            ->willReturn('{"success": 1,"task_id": 2}');
        $rideWithGps->uploadGpx('path/to/file2.gpx', 2000, "hello there", "file name", "description");
        $mock->expects($this->at(0))->method('upload')
            ->willReturn('{"success": 1,"task_id": 3}');
        $rideWithGps->uploadGpx('path/to/file3.gpx', 3000, "hello there", "file name", "description");


        $rideWithGps->setFileUploadTimeout(1);
        $mock->expects($this->at(0))->method("get")
            ->with("/queued_tasks/status.json")
            ->willReturn(include('data/input/rwgpsQueuedTasks2.php'));


        unset($expected_array[3000]->rwgps_id);
        $expected_array[3000]->error = "There were no tracks or routes in your file.  Please try a different file format.";

        // 2 uploaded successfully, and 1 failed.
        $this->assertEquals($expected_array, $rideWithGps->waitForPendingUploads(0));

        $mock = $this->getMockBuilder('RideWithGpsApi')->setMethods(array('upload', 'get', 'getError'))->getMock();
        $rideWithGps = new RideWithGps("", array($this, 'myEcho'), "UTC", $mock);

        $mock->expects($this->at(0))->method('upload')
            ->willReturn('{"success": 1,"task_id": 1}');
        $rideWithGps->uploadGpx('path/to/file.gpx', 1000, "hello there", "file name", "description");
        $mock->expects($this->at(0))->method('upload')
            ->willReturn('{"success": 1,"task_id": 2}');
        $rideWithGps->uploadGpx('path/to/file2.gpx', 2000, "hello there", "file name", "description");
        $mock->expects($this->at(0))->method('upload')
            ->willReturn('{"success": 1,"task_id": 3}');
        $rideWithGps->uploadGpx('path/to/file3.gpx', 3000, "hello there", "file name", "description");



        $rideWithGps->setFileUploadTimeout(1);
        $mock->expects($this->any())->method("get")
            ->with("/queued_tasks/status.json")
            ->willReturn(include('data/input/rwgpsQueuedTasks3.php'));


        unset($expected_array[3000]->rwgps_id);
        $expected_array[3000]->error = "Timed out waiting for confirmation of upload after 1 seconds";

        // 2 uploaded successfully, and 1 pending.
        $this->assertEquals($expected_array, $rideWithGps->waitForPendingUploads(0));


        $mock = $this->getMockBuilder('RideWithGpsApi')->setMethods(array('upload', 'get', 'getError'))->getMock();
        $rideWithGps = new RideWithGps("", array($this, 'myEcho'), "UTC", $mock);

        $mock->expects($this->at(0))->method('upload')
            ->willReturn('{"success": 1,"task_id": 1}');
        $rideWithGps->uploadGpx('path/to/file.gpx', 1000, "hello there", "file name", "description");
        $mock->expects($this->at(0))->method('upload')
            ->willReturn('{"success": 1,"task_id": 2}');
        $rideWithGps->uploadGpx('path/to/file2.gpx', 2000, "hello there", "file name", "description");
        $mock->expects($this->at(0))->method('upload')
            ->willReturn('{"success": 1,"task_id": 3}');
        $rideWithGps->uploadGpx('path/to/file3.gpx', 3000, "hello there", "file name", "description");


        $rideWithGps->setFileUploadTimeout(1);
        $mock->expects($this->at(0))->method("get")
            ->with("/queued_tasks/status.json")
            ->willReturn(include('data/input/rwgpsQueuedTasks3.php'));
        $mock->expects($this->at(1))->method("get")
            ->with("/queued_tasks/status.json")
            ->willReturn(include('data/input/rwgpsQueuedTasks1.php'));


        unset($expected_array[3000]->rwgps_id);
        $expected_array[3000]->error = "Timed out waiting for confirmation of upload after 1 seconds";

        $expected_array[3000]->rwgps_id = 333333;
        unset($expected_array[3000]->error);

        $rideWithGps->setFileUploadTimeout(10);

        // 2 uploaded immediately, and 1 on second try.
        $start = time();
        $this->assertEquals($expected_array, $rideWithGps->waitForPendingUploads(0));
        // confirming that it stops waiting once it has processed all pending files.
        $this->assertLessThan(4, time() - $start);
    }

    public function testIsConnected()
    {
        // set up.  We need a RideWithGps object with a valid user Id.  It's 99999
        $mock = $this->getMockBuilder('RideWithGpsApi')->setMethods(array('getAuth', 'setAuth', 'get'))->getMock();

        $rideWithGps = new RideWithGps("", array($this, 'myEcho'), "UTC", $mock);
        //  before we get auth token, it should fail.
        $mock->expects($this->at(0))->method('getAuth')
            ->with()->willReturn(null);

        $this->assertEquals(false, $rideWithGps->isConnected());
        $this->assertEquals(null, $rideWithGps->getUserId());

        //auth token set, but it's not correct
        $mock->expects($this->at(0))->method('setAuth')
            ->with('AUTH TOKEN');
        $rideWithGps->setAuth("AUTH TOKEN");

        $mock->expects($this->at(0))->method('getAuth')
            ->with()->willReturn('AUTH TOKEN');
        $mock->expects($this->at(1))->method('get')
            ->with('/users/current.json')
            ->willReturn('{"error":"Unable to authenticate, please provide a valid username/password, auth_token or a session"}');
        $mock->expects($this->at(2))->method('setAuth')
            ->with(null);

        $this->assertEquals(false, $rideWithGps->isConnected());
        $this->assertEquals(null, $rideWithGps->getUserId());
        $this->assertEquals("Unable to authenticate, please provide a valid username/password, auth_token or a session", $rideWithGps->getError());
        //happy path, everything correct
        $mock->expects($this->at(0))->method('setAuth')
            ->with('AUTH TOKEN');
        $rideWithGps->setAuth("AUTH TOKEN");
        $mock->expects($this->at(0))->method('getAuth')
            ->with()->willReturn('AUTH TOKEN');
        $mock->expects($this->at(1))->method('get')
            ->with('/users/current.json')
            ->willReturn(include("data/input/rwgpsCurrentUser.php"));

        assert($rideWithGps->isConnected());
        $this->assertEquals(99999, $rideWithGps->getUserId());

    }


    public function testConnect()
    {
        $mock = $this->getMockBuilder('RideWithGpsApi')->setMethods(array('getAuth', 'setAuth', 'get'))->getMock();

        $rideWithGps = new RideWithGps("", array($this, 'myEcho'), "UTC", $mock);

        // tests that a successful connect gets the correct auth_token and user_id
        $mock->expects($this->at(0))->method('get')
            ->with('/users/current.json', array('email' => 'u', 'password' => 'p'))
            ->willReturn(include("data/input/rwgpsConnect1.php"));
        $mock->expects($this->at(1))->method('setAuth')
            ->with('AUTHORITY TOKEN');
        $this->output = "";
        $this->assertEquals("AUTHORITY TOKEN", $rideWithGps->connect("u", "p"));
        $mock->expects($this->at(0))->method('getAuth')
            ->with()->willReturn('AUTHORITY TOKEN');
        $this->assertEquals("AUTHORITY TOKEN", $rideWithGps->getAuth());
        $this->assertEquals(99999, $rideWithGps->getUserId());
        $this->assertEquals(".", $this->output);

        // If connect returns an error, handle it graciously.
        $mock = $this->getMockBuilder('RideWithGpsApi')->setMethods(array('getAuth', 'setAuth', 'get'))->getMock();
        $rideWithGps = new RideWithGps("", array($this, 'myEcho'), "UTC", $mock);
        $this->output = "";
        $mock->expects($this->at(0))->method('get')
            ->with('/users/current.json', array('email' => 'u', 'password' => 'p'))
            ->willReturn(include("data/input/rwgpsConnect2.php"));
        $this->assertEquals(null, $rideWithGps->connect("u", "p"));
        $mock->expects($this->at(0))->method('getAuth')
            ->with()
            ->willReturn(null);
        $this->assertEquals(null, $rideWithGps->getAuth());
        $this->assertEquals("", $rideWithGps->getUserId());
        $this->assertEquals("Unable to authenticate, please provide a valid username/password, auth_token or a session",
            $rideWithGps->getError());
        $this->assertEquals(".", $this->output);

        // Good JSON, but no auth_token.
        $mock = $this->getMockBuilder('RideWithGpsApi')->setMethods(array('getAuth', 'setAuth', 'get'))->getMock();
        $rideWithGps = new RideWithGps("", array($this, 'myEcho'), "UTC", $mock);
        $mock->expects($this->at(0))->method('get')
            ->with('/users/current.json', array('email' => 'u', 'password' => 'p'))
            ->willReturn(include("data/input/rwgpsConnect3.php"));
        $this->output = "";
        $this->assertEquals(null, $rideWithGps->connect("u", "p"));
        $mock->expects($this->at(0))->method('getAuth')
            ->with()
            ->willReturn(null);
        $this->assertEquals(null, $rideWithGps->getAuth());
        $this->assertEquals("", $rideWithGps->getUserId());
        $this->assertEquals("Auth Token not found.",
            $rideWithGps->getError());
        $this->assertEquals(".", $this->output);

    }

    public function testConvertToSeconds()
    {
        $convertToSeconds = $this->getMethod('convertToSeconds');
        $obj = new RideWithGps("", "", "");
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

    public function testGetTimezone()
    {
        $getTimezone = $this->getMethod('getTimezone');
        $rideWithGps = new RideWithGps("", "", "America/Los_Angeles");


        $ride = json_decode('{"time_zone": "Sydney",
            "departed_at": "2016-02-11T12:30:00Z",
            "first_lng": 0.0,"first_lat": 51.9,
            "last_lng": 145.020811,"last_lat": -37.80029,
            "utc_offset": 36000}');
        $this->assertEquals("Australia/Sydney", $getTimezone->invokeArgs($rideWithGps, array($ride)));

        $ride = json_decode('{"utc_offset": 36000}');
        $this->assertEquals("Australia/Melbourne", $getTimezone->invokeArgs($rideWithGps, array($ride)));
        $ride = json_decode('{"utc_offset": -18000}'); //negative
        $this->assertEquals("America/New_York", $getTimezone->invokeArgs($rideWithGps, array($ride)));

        $mock = $this->getMockBuilder('GoogleApi')->setMethods(array('timezoneFromCoords'))->getMock();
        $mock->expects($this->at(0))->method('timezoneFromCoords')
            ->with(51.9, 0.0, "2016-02-11T12:30:00Z")
            ->willReturn('Australia/Hobart');

        $rideWithGps->setGoogleApi($mock);

        $ride = json_decode('{"departed_at": "2016-02-11T12:30:00Z",
                            "first_lng": 0.0,"first_lat": 51.9,
                            "last_lng": 145.020811,"last_lat": -37.80029}');
        $this->assertEquals("Australia/Hobart", $getTimezone->invokeArgs($rideWithGps, array($ride)));


        $ride = json_decode('{"utc_offset": 360000}'); // 100 hours, use default
        $this->assertEquals("America/Los_Angeles", $getTimezone->invokeArgs($rideWithGps, array($ride)));
        $ride = json_decode('{"other_field": 36000}'); // no utc or tz or points, use default
        $this->assertEquals("America/Los_Angeles", $getTimezone->invokeArgs($rideWithGps, array($ride)));


    }


}

?>
