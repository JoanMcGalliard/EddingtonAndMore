<?php

namespace JoanMcGalliard\EddingtonAndMore;

require_once "JoanMcGalliard/EddingtonAndMore/Endomondo.php";
require_once 'BaseTestClass.php';


class EndomondoTest extends BaseTestClass
{
    protected $classUnderTest = 'JoanMcGalliard\EddingtonAndMore\Endomondo';

    public function testIsOverNightRide()
    {
        $isOverNightRide = $this->getMethod('isOverNightRide');

        $ride = new \stdClass();
        $ride->duration = 4 * 60 * 60; //4 hours
        $ride->start_time = "2016-02-11T10:16:54Z"; //10:16 GMT, 21:16 Melbourne time, 4:16 chicago.

        $endomondo = new Endomondo("", "", "UTC", array($this, 'myEcho'), null);
        $this->assertEquals(false, $isOverNightRide->invokeArgs($endomondo, array($ride)));
        $endomondo = new Endomondo("", "", "Australia/Melbourne", array($this, 'myEcho'), null);
        $this->assertEquals(true, $isOverNightRide->invokeArgs($endomondo, array($ride)));
        $endomondo = new Endomondo("", "", "America/Chicago", array($this, 'myEcho'), null);
        $this->assertEquals(false, $isOverNightRide->invokeArgs($endomondo, array($ride)));
        $ride->duration = 14 * 60 * 60; //14 hours
        $endomondo = new Endomondo("", "", "UTC", array($this, 'myEcho'), null);
        $this->assertEquals(true, $isOverNightRide->invokeArgs($endomondo, array($ride)));
        $endomondo = new Endomondo("", "", "Australia/Melbourne", array($this, 'myEcho'), null);
        $this->assertEquals(true, $isOverNightRide->invokeArgs($endomondo, array($ride)));
        $endomondo = new Endomondo("", "", "America/Chicago", array($this, 'myEcho'), null);
        $this->assertEquals(false, $isOverNightRide->invokeArgs($endomondo, array($ride)));
    }

    public function testIsConnected()
    {
        $mock = $this->getMockBuilder('EndomondoApi')->setMethods(array('getAuth', 'get', 'setAuth'))->getMock();
        $endomondo = new Endomondo("", "", "UTC", array($this, 'myEcho'), $mock);

        // if we don't have an auth token, we can't connect
        $mock->expects($this->at(0))->method('getAuth')->with()->willReturn(null);
        $mock->expects($this->any())->method('setAuth');


        $this->assertFalse($endomondo->isConnected());

        // happy path.
        $mock->expects($this->any())->method('getAuth')->with()->willReturn("AUTH TOKEN");
        $mock->expects($this->at(1))->method('get')->with('api/profile/account/get')->willReturn(include('data/input/endoProfile.php'));
        $this->assertTrue($endomondo->isConnected());
        $this->assertEquals(9999999, $endomondo->getUserId());

        // if we are already connected, we don't have to do anything, just report current state.
        $this->assertTrue($endomondo->isConnected());


        //We don't get JSON back from get.
        $this->setProperty('connected', false, $endomondo);
        $mock->expects($this->at(1))->method('get')->with('api/profile/account/get')->willReturn("NOT AUTHORISED");
        $this->assertFalse($endomondo->isConnected());

        // or JSON without a user id
        $mock->expects($this->at(1))->method('get')->with('api/profile/account/get')->willReturn('{ "data": { "phone": "+44"} }');
        $this->assertFalse($endomondo->isConnected());

        // or JSON with just a user id
        $mock->expects($this->at(1))->method('get')->with('api/profile/account/get')->willReturn('{ "data": {"id": 9999999} }');
        $this->assertTrue($endomondo->isConnected());
    }

    public function testGpxDownloadUrl()
    {
        $mock = $this->getMockBuilder('EndomondoApi')->setMethods(array('getAuth', 'get', 'setAuth'))->getMock();
        $endomondo = new Endomondo("", "", "UTC", array($this, 'myEcho'), $mock);
        // not connected
        $mock->expects($this->at(0))->method('getAuth')->with()->willReturn(null);
        $this->assertNull($endomondo->gpxDownloadUrl(11111111));

        //connected, URL is based on userId
        $mock->expects($this->any())->method('setAuth');
        $mock->expects($this->any())->method('getAuth')->willReturn("AUTH TOKEN");
        $mock->expects($this->at(1))->method('get')->willReturn('{ "data": {"id": 9999999} }');
        $endomondo->isConnected();
        $this->assertEquals("https://www.endomondo.com/rest/v1/users/9999999/workouts/11111111/export?format=GPX", $endomondo->gpxDownloadUrl(11111111));
    }

    public function testActivityUrl()
    {
        $mock = $this->getMockBuilder('EndomondoApi')->setMethods(array('getAuth', 'get', 'setAuth'))->getMock();
        $endomondo = new Endomondo("", "", "UTC", array($this, 'myEcho'), $mock);
        // not connected
        $mock->expects($this->at(0))->method('getAuth')->with()->willReturn(null);

        $this->assertNull($endomondo->activityUrl(11111111));

        //connected, URL is based on userId
        $mock->expects($this->any())->method('setAuth');
        $mock->expects($this->any())->method('getAuth')->willReturn("AUTH TOKEN");
        $mock->expects($this->at(1))->method('get')->willReturn('{ "data": {"id": 9999999} }');
        $endomondo->isConnected();
        $this->assertEquals("https://www.endomondo.com/users/9999999/workouts/11111111", $endomondo->activityUrl(11111111));
    }

    public function testGetWorkout()
    {
        $mock = $this->getMockBuilder('EndomondoApi')->setMethods(array('getAuth', 'get', 'setAuth', 'getError'))->getMock();
        $endomondo = new Endomondo("", "", "UTC", array($this, 'myEcho'), $mock);

        // not connected, workout is null.
        $mock->expects($this->at(0))->method('getAuth')->willReturn(null);
        $this->setProperty('error', "", $endomondo);
        $this->assertNull($endomondo->getWorkout(99999));
        $this->assertEquals("Not connected to Endomondo", $endomondo->getError());


        //connected .....
        $mock->expects($this->any())->method('getAuth')->willReturn("auth");

        $userId = 9999999;
        $mock->expects($this->at(1))->method('get')->willReturn("{ \"data\": {\"id\": {$userId}} }");
        $this->assertTrue($endomondo->isConnected());

        $workoutId = 123456;
        $params = array('workoutId' => $workoutId, 'fields' => 'basic');
        $mock->expects($this->at(0))->method('get')->with('api/workout/get', $params)->willReturn("Not json");
        $this->setProperty('error', "", $endomondo);
        $this->assertNull($endomondo->getWorkout($workoutId));
        $this->assertEquals("API returned unexpected value: Not json", $endomondo->getError());

        // bad auth token
        $mock->expects($this->at(0))->method('get')->with('api/workout/get',
            $params)->willReturn('{"error":{"type":"AUTH_FAILED"}}');
        $this->setProperty('error', "", $endomondo);
        $this->assertNull($endomondo->getWorkout($workoutId));
        $this->assertEquals('AUTH_FAILED', $endomondo->getError());

        // workout not one i can see
        $mock->expects($this->at(0))->method('get')->with('api/workout/get',
            $params)->willReturn('{"error":{"type":"ACCESS_DENIED"}}');
        $this->setProperty('error', "", $endomondo);
        $this->assertNull($endomondo->getWorkout($workoutId));
        $this->assertEquals('ACCESS_DENIED', $endomondo->getError());

        // error not in expected format
        $mock->expects($this->at(0))->method('get')->with('api/workout/get',
            $params)->willReturn('{"error":{"field":"VALUE"}}');
        $this->setProperty('error', "", $endomondo);
        $this->assertNull($endomondo->getWorkout($workoutId));
        $this->assertEquals('{"field":"VALUE"}', $endomondo->getError());

        // workout that has been deleted
        $mock->expects($this->at(0))->method('get')->with('api/workout/get',
            $params)->willReturn('{"error":{"type":"NOT_FOUND"}}');
        $this->setProperty('error', "", $endomondo);
        $this->assertNull($endomondo->getWorkout($workoutId));
        $this->assertEquals('NOT_FOUND', $endomondo->getError());

        // JSON comes back, but not expected and not an error message
        $mock->expects($this->at(0))->method('get')->with('api/workout/get',
            $params)->willReturn('{"field":{"field":"value"}}');
        $this->setProperty('error', "", $endomondo);
        $this->assertNull($endomondo->getWorkout($workoutId));
        $this->assertEquals('Response not in a recognised format: {"field":{"field":"value"}}', $endomondo->getError());

        // api gave a error
        $mock->expects($this->at(0))->method('get')->with('api/workout/get',
            $params)->willReturn(null);
        $this->setProperty('error', "", $endomondo);
        $mock->expects($this->at(1))->method('getError')->with()->willReturn("API ERROR");
        $this->setProperty('error', "", $endomondo);
        $this->assertNull($endomondo->getWorkout($workoutId));
        $this->assertEquals("API ERROR", $endomondo->getError());

        $insertUserId = $userId;
        $insertWorkoutId = $workoutId;
        $insertDistance = 10.8;
        $insertStartDate = "2015-12-27 21:56:00 UTC";
        $mock->expects($this->at(0))->method('get')->with('api/workout/get',
            $params)->willReturn(include('data/input/endoWorkout.php')); // yup, that's my workout
        $result = new \stdClass();
        $result->distance = 10800.0;
        $result->startTime = strtotime("2015-12-27 21:56:00 UTC");
        $result->id = $workoutId;
        $this->setProperty('error', "", $endomondo);
        $this->assertEquals($result, $endomondo->getWorkout($workoutId));
        $this->assertEquals("", $endomondo->getError());

        // returned workout id doesn't match parameter
        $insertWorkoutId = 44;
        $mock->expects($this->at(0))->method('get')->with('api/workout/get',
            $params)->willReturn(include('data/input/endoWorkout.php'));
        $this->setProperty('error', "", $endomondo);
        $this->assertNull($endomondo->getWorkout($workoutId));
        $this->assertEquals("Endomondo returned the wrong workout", $endomondo->getError());

        // returned used id isn't me!
        $insertWorkoutId = $workoutId;
        $insertUserId = 8888;
        $mock->expects($this->at(0))->method('get')->with('api/workout/get',
            $params)->willReturn(include('data/input/endoWorkout.php'));
        $this->setProperty('error', "", $endomondo);
        $this->assertNull($endomondo->getWorkout($workoutId));
        $this->assertEquals("Workout does not belong to current user.", $endomondo->getError());

        //returned JSON has only required fields

        $mock->expects($this->at(0))->method('get')->with('api/workout/get',
            $params)->willReturn('{"distance": 10.8,"owner_id": 9999999, "start_time": "2015-12-27 21:56:00 UTC", "id": 123456, "is_valid": true, "sport": 1}');
        $this->setProperty('error', "", $endomondo);
        $this->assertEquals($result, $endomondo->getWorkout($workoutId));
        $this->assertEquals("", $endomondo->getError());


        // not valid
        $mock->expects($this->at(0))->method('get')->with('api/workout/get',
            $params)->willReturn('{"distance": 10.8,"owner_id": 9999999, "start_time": "2015-12-27 21:56:00 UTC", "id": 123456, "is_valid": false, "sport": 1}');
        $this->setProperty('error', "", $endomondo);
        $this->assertNull($endomondo->getWorkout($workoutId));
        $this->assertEquals("Not a valid ride", $endomondo->getError());


        // valid is missing
        $mock->expects($this->at(0))->method('get')->with('api/workout/get',
            $params)->willReturn('{"distance": 10.8,"owner_id": 9999999, "start_time": "2015-12-27 21:56:00 UTC", "id": 123456, "sport": 1}');
        $this->setProperty('error', "", $endomondo);
        $this->assertNull($endomondo->getWorkout($workoutId));
        $this->assertEquals("Not a valid ride", $endomondo->getError());


        //sport is 2
        $mock->expects($this->at(0))->method('get')->with('api/workout/get',
            $params)->willReturn('{"distance": 10.8,"owner_id": 9999999, "start_time": "2015-12-27 21:56:00 UTC", "id": 123456, "is_valid": true, "sport": 2}');
        $this->setProperty('error', "", $endomondo);
        $this->assertEquals($result, $endomondo->getWorkout($workoutId));
        $this->assertEquals("", $endomondo->getError());


        //sport is 3
        $mock->expects($this->at(0))->method('get')->with('api/workout/get',
            $params)->willReturn('{"distance": 10.8,"owner_id": 9999999, "start_time": "2015-12-27 21:56:00 UTC", "id": 123456, "is_valid": true, "sport": 3}');
        $this->setProperty('error', "", $endomondo);
        $this->assertEquals($result, $endomondo->getWorkout($workoutId));
        $this->assertEquals("", $endomondo->getError());


        // sport is 4 -> not cycling!
        $mock->expects($this->at(0))->method('get')->with('api/workout/get',
            $params)->willReturn('{"distance": 10.8,"owner_id": 9999999, "start_time": "2015-12-27 21:56:00 UTC", "id": 123456, "is_valid": true, "sport": 4}');
        $this->setProperty('error', "", $endomondo);
        $this->assertNull($endomondo->getWorkout($workoutId));
        $this->assertEquals("Not a valid ride", $endomondo->getError());


        // sport is missing
        $mock->expects($this->at(0))->method('get')->with('api/workout/get',
            $params)->willReturn('{"distance": 10.8,"owner_id": 9999999, "start_time": "2015-12-27 21:56:00 UTC", "id": 123456, "is_valid": true}');
        $this->setProperty('error', "", $endomondo);
        $this->assertNull($endomondo->getWorkout($workoutId));
        $this->assertEquals("Not a valid ride", $endomondo->getError());


        // distance is missing
        $json = '{"owner_id": 9999999, "start_time": "2015-12-27 21:56:00 UTC", "id": 123456, "is_valid": true, "sport": 1}';
        $mock->expects($this->at(0))->method('get')->with('api/workout/get',
            $params)->willReturn($json);
        $this->setProperty('error', "", $endomondo);
        $this->assertNull($endomondo->getWorkout($workoutId));
        $this->assertEquals("Response not in a recognised format: $json", $endomondo->getError());


        // owner_id is missing
        $json = '{"distance": 10.8, "start_time": "2015-12-27 21:56:00 UTC", "id": 123456, "is_valid": true, "sport": 1}';
        $mock->expects($this->at(0))->method('get')->with('api/workout/get',
            $params)->willReturn($json);
        $this->setProperty('error', "", $endomondo);
        $this->assertNull($endomondo->getWorkout($workoutId));
        $this->assertEquals("Response not in a recognised format: $json", $endomondo->getError());


        // start_time is missing
        $json = '{"distance": 10.8,"owner_id": 9999999, "id": 123456, "is_valid": true, "sport": 1}';
        $mock->expects($this->at(0))->method('get')->with('api/workout/get',
            $params)->willReturn($json);
        $this->setProperty('error', "", $endomondo);
        $this->assertNull($endomondo->getWorkout($workoutId));
        $this->assertEquals("Response not in a recognised format: $json", $endomondo->getError());


        // workout id is missing
        $json = '{"distance": 10.8,"owner_id": 9999999, "start_time": "2015-12-27 21:56:00 UTC", "is_valid": true, "sport": 1}';
        $mock->expects($this->at(0))->method('get')->with('api/workout/get',
            $params)->willReturn($json);
        $this->setProperty('error', "", $endomondo);
        $this->assertNull($endomondo->getWorkout($workoutId));
        $this->assertEquals("Response not in a recognised format: $json", $endomondo->getError());
    }

    public function testGetRides()
    {
        $mock = $this->getMockBuilder('EndomondoApi')->setMethods(array('get'))->getMock();
        $endomondo = new Endomondo("", "", "UTC", array($this, 'myEcho'), $mock);

        // no rides returned
        $mock->expects($this->at(0))->method('get')->with('api/workouts', $this->captureArg($params))->willReturn('{"data":[]}');
        $this->assertEquals([], $endomondo->getRides(null, null));
        $before = strtotime($params['before']);
        $this->assertTrue((time() - $before) <= 1); // the time of the before parameter should be about now, so in the last second
        $this->assertEquals(500, $params['maxResults']);
        $this->assertEquals('simple,basic', $params['fields']);

        //one ride returned
        $result = '{"data":[{"owner":{"premium_type":"pro","name":"joan m","last_name":"m","id":2859253,"first_name":"joan","picture":848122},"distance":1.9844679832458496,"speed_avg":0.887574200482676,"privacy_map":2,"owner_id":2859253,"privacy_workout":2,"calories":302,"duration":8049,"start_time":"2015-12-27 21:56:00 UTC","is_valid":true,"id":655334427,"burgers_burned":0.55925924,"sport":1,"live":false}]}';
        $mock->expects($this->at(0))->method('get')->with('api/workouts', $this->captureArg($params))->willReturn($result);

        $expected = array('2015-12-27' => array(array('elapsed_time' => 8049, 'distance' => 1984.4679832458496,
            'endo_id' => 655334427,
            'start_time' => '2015-12-27 21:56:00 UTC',
            'name' => '',
            'moving_time'=>8049)));


        $this->assertEquals($expected, $endomondo->getRides(null, null));


        //all possible data included
        $result = '{"data":[{"owner":{"premium_type":"pro","name":"joan m","last_name":"m","id":2859253,"first_name":"joan","picture":848122},"distance":1.9844679832458496,"speed_avg":0.887574200482676,"privacy_map":2,"owner_id":2859253,"privacy_workout":2,"calories":302,"duration":8049,"start_time":"2015-12-27 21:56:00 UTC","is_valid":true,"id":655334427,"speed_max": 23.0271,"name": "My Ride","burgers_burned":0.55925924,"sport":1,"live":false,"ascent": 69.4}]}';
        $mock->expects($this->at(0))->method('get')->with('api/workouts', $this->captureArg($params))->willReturn($result);

        $expected = array('2015-12-27' => array(array('elapsed_time' => 8049, 'distance' => 1984.4679832458496,
            'endo_id' => 655334427,
            'start_time' => '2015-12-27 21:56:00 UTC',
            'name' => 'My Ride',
            'max_speed' => 6.39641666666667,
            'total_elevation_gain' => 69.4,
            'moving_time'=>8049)));


        $this->assertEquals($expected, $endomondo->getRides(null, null));


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
    public function waitForPendingUploads()
    public function setSplitOvernightRides($getEndomondoSplitRides)
    public function authenticationUrl($redirect, $approvalPrompt, $scope, $state)

 */

?>
