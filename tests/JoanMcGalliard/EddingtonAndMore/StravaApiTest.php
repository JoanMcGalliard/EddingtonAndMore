<?php

namespace JoanMcGalliard\EddingtonAndMore;

require_once "JoanMcGalliard/EddingtonAndMore/StravaApi.php";
require_once 'StravaApiMock.php';

use PHPUnit_Framework_TestCase;
use StravaApiMock;

class StravaApiTest extends PHPUnit_Framework_TestCase
{
    public function testgetRides()
    {

        $stravaResults =
        $expected = array(
            '2016-01-08' =>
                array(
                    0 =>
                        array(
                            'distance' => 2975.5,
                            'name' => 'Afternoon Ride',
                            'strava_id' => 470171383,
                            'start_time' => '2016-01-08T13:20:00Z',
                            'bike' => 'b267883',
                            'moving_time' => 599,
                            'elapsed_time' => 599,
                            'total_elevation_gain' => 0,
                            'max_speed' => 5.5,
                            'endo_id' => NULL,
                        ),
                    1 =>
                        array(
                            'distance' => 2919,
                            'name' => 'Evening Ride',
                            'strava_id' => 470166379,
                            'start_time' => '2016-01-08T20:30:00Z',
                            'bike' => 'b267883',
                            'moving_time' => 600,
                            'elapsed_time' => 600,
                            'total_elevation_gain' => 0,
                            'max_speed' => 5.2000000000000002,
                            'endo_id' => NULL,
                        ),
                ),
            '2016-01-10' =>
                array(
                    0 =>
                        array(
                            'distance' => 7123,
                            'name' => 'Morning Ride',
                            'strava_id' => 467826933,
                            'start_time' => '2016-01-10T10:30:00Z',
                            'bike' => 'b267883',
                            'moving_time' => 2159,
                            'elapsed_time' => 2159,
                            'total_elevation_gain' => 0,
                            'max_speed' => 3.6000000000000001,
                            'endo_id' => NULL,
                        )));
        $response = json_decode('[
    {
        "id": 470171383,
        "resource_state": 2,
        "external_id": "20160108_132000.gpx",
        "upload_id": 521449223,
        "athlete": {
            "id": 573245,
            "resource_state": 1
        },
        "name": "Afternoon Ride",
        "distance": 2975.5,
        "moving_time": 599,
        "elapsed_time": 599,
        "total_elevation_gain": 0.0,
        "type": "Ride",
        "start_date": "2016-01-08T13:20:00Z",
        "start_date_local": "2016-01-08T13:20:00Z",
        "timezone": "(GMT+00:00) Europe/London",
        "start_latlng": [
            50.00,
            0.0
        ],
        "end_latlng": [
            50.00,
            0.0
        ],
        "location_city": "Twickenham",
        "location_state": "England",
        "location_country": "United Kingdom",
        "start_latitude": 50.0,
        "start_longitude": 0.0,
        "achievement_count": 0,
        "kudos_count": 0,
        "comment_count": 0,
        "athlete_count": 1,
        "photo_count": 0,
        "map": {
            "id": "a470171383",
            "summary_polyline": "",
            "resource_state": 2
        },
        "trainer": false,
        "commute": false,
        "manual": false,
        "private": false,
        "flagged": false,
        "gear_id": "b267883",
        "average_speed": 4.967,
        "max_speed": 5.5,
        "average_watts": 48.7,
        "kilojoules": 29.2,
        "device_watts": false,
        "elev_high": 16.0,
        "elev_low": 8.7,
        "total_photo_count": 0,
        "has_kudoed": false,
        "workout_type": null
    },
    {
        "id": 470166379,
        "resource_state": 2,
        "external_id": "20160108_203000 (2).gpx",
        "upload_id": 521444288,
        "athlete": {
            "id": 573245,
            "resource_state": 1
        },
        "name": "Evening Ride",
        "distance": 2919.0,
        "moving_time": 600,
        "elapsed_time": 600,
        "total_elevation_gain": 0.0,
        "type": "Ride",
        "start_date": "2016-01-08T20:30:00Z",
        "start_date_local": "2016-01-08T20:30:00Z",
        "timezone": "(GMT+00:00) Europe/London",
        "start_latlng": [
            50.00,
            0.0
        ],
        "end_latlng": [
            50.00,
            0.0
        ],
        "location_city": "Hounslow",
        "location_state": "England",
        "location_country": "United Kingdom",
        "start_latitude": 50.0,
        "start_longitude": 0.0,
        "achievement_count": 1,
        "kudos_count": 0,
        "comment_count": 0,
        "athlete_count": 1,
        "photo_count": 0,
        "map": {
            "id": "a470166379",
            "summary_polyline": "",
            "resource_state": 2
        },
        "trainer": false,
        "commute": false,
        "manual": false,
        "private": false,
        "flagged": false,
        "gear_id": "b267883",
        "average_speed": 4.865,
        "max_speed": 5.2,
        "average_watts": 36.5,
        "kilojoules": 21.9,
        "device_watts": false,
        "elev_high": 19.7,
        "elev_low": 8.7,
        "total_photo_count": 0,
        "has_kudoed": false,
        "workout_type": null
    },
    {
        "id": 467826933,
        "resource_state": 2,
        "external_id": "20160110_103000.gpx",
        "upload_id": 519095266,
        "athlete": {
            "id": 573245,
            "resource_state": 1
        },
        "name": "Morning Ride",
        "distance": 7123.0,
        "moving_time": 2159,
        "elapsed_time": 2159,
        "total_elevation_gain": 0.0,
        "type": "Ride",
        "start_date": "2016-01-10T10:30:00Z",
        "start_date_local": "2016-01-10T10:30:00Z",
        "timezone": "(GMT+00:00) Europe/London",
        "start_latlng": [
            50.00,
            0.0
        ],
        "end_latlng": [
            50.00,
            0.0
        ],
        "location_city": "Twickenham",
        "location_state": "England",
        "location_country": "United Kingdom",
        "start_latitude": 50.0,
        "start_longitude": 0.0,
        "achievement_count": 0,
        "kudos_count": 0,
        "comment_count": 0,
        "athlete_count": 1,
        "photo_count": 0,
        "map": {
            "id": "a467826933",
            "summary_polyline": "",
            "resource_state": 2
        },
        "trainer": false,
        "commute": false,
        "manual": false,
        "private": false,
        "flagged": false,
        "gear_id": "b267883",
        "average_speed": 3.299,
        "max_speed": 3.6,
        "average_watts": 22.9,
        "kilojoules": 49.4,
        "device_watts": false,
        "elev_high": 13.8,
        "elev_low": 8.0,
        "total_photo_count": 0,
        "has_kudoed": false,
        "workout_type": null
    }
]');



        $this->assertTrue(true);
        $mock = new StravaApiMock();
        $mock->clearResponses("get", 'activities');
        $mock->primeResponse('get', 'activities', $response);
        $stravaApi = new StravaApi("", "", $mock);
        $this->assertEquals($expected, $stravaApi->getRides(null, null));


    }

    protected function setUp()
    {
        parent::setUp();
        date_default_timezone_set('UTC');
    }

    protected function tearDown()
    {
        parent::tearDown(); // TODO: Change the autogenerated stub
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
