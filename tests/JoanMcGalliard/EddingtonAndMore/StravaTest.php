<?php

namespace JoanMcGalliard\EddingtonAndMore;

require_once "JoanMcGalliard/EddingtonAndMore/Strava.php";
require_once 'BaseTestClass.php';


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

/**
 * A mock version of Iamstuartwilson\StravaApi
 */
class StravaApiMock extends BaseMockClass
{
    public function authenticationUrl($redirect, $approvalPrompt = 'auto', $scope = null, $state = null)
    {
        throw new Exception('authenticationUrl: Not implemented');
    }

    /**
     * Authenticates token returned from API
     *
     * @param string $code
     *
     * @link http://strava.github.io/api/v3/oauth/#post-token
     *
     * @return string
     */
    public function tokenExchange($code)
    {
        return json_decode('{
    "access_token": "6a7dfb37cf8ff4eb5a63a1cb38f4b6aa38410e8b",
    "token_type": "Bearer",
    "athlete": {
        "id": 999999,
        "username": "grunt",
        "resource_state": 3,
        "firstname": "Joan",
        "lastname": "M",
        "profile_medium": "avatar/athlete/medium.png",
        "profile": "avatar/athlete/large.png",
        "city": "Twickenham",
        "state": "England",
        "country": "United Kingdom",
        "sex": "F",
        "friend": null,
        "follower": null,
        "premium": false,
        "created_at": "2012-05-30T16:07:02Z",
        "updated_at": "2016-01-14T21:47:48Z",
        "badge_type_id": 0,
        "follower_count": 10,
        "friend_count": 17,
        "mutual_friend_count": 0,
        "athlete_type": 0,
        "date_preference": "%d/%m/%Y",
        "measurement_preference": "meters",
        "email": "strava@example.com",
        "ftp": null,
        "weight": 60.0,
        "clubs": [
            {
                "id": 11111,
                "resource_state": 2,
                "name": "club1",
                "profile_medium": "avatar/club/medium.png",
                "profile": "avatar/club/large.png"
            },
            {
                "id": 2222,
                "resource_state": 2,
                "name": "club2",
                "profile_medium": "avatar/club/medium.png",
                "profile": "avatar/club/large.png"
            }
        ],
        "bikes": [
            {
                "id": "b111111",
                "primary": true,
                "name": "Avail 2",
                "resource_state": 2,
                "distance": 24811670.0
            },
            {
                "id": "b222222",
                "primary": false,
                "name": "Mezzo",
                "resource_state": 2,
                "distance": 16777003.0
            }
        ],
        "shoes": []
    }
}');
    }

    /**
     * Deauthorises application
     *
     * @link http://strava.github.io/api/v3/oauth/#deauthorize
     *
     * @return string
     */
    public function deauthorize()
    {
        throw new Exception('deauthorize: Not implemented');
    }

    /**
     * Sets the access token used to authenticate API requests
     *
     * @param string $token
     */
    public function setAccessToken($token)
    {
        throw new Exception('setAccessToken: Not implemented');
    }

    /**
     * Sends GET request to specified API endpoint
     *
     * @param string $request
     * @param array $parameters
     *
     * @example http://strava.github.io/api/v3/athlete/#koms
     *
     * @return string
     */
    /**
     * Sends PUT request to specified API endpoint
     *
     * @param string $request
     * @param array $parameters
     *
     * @example http://strava.github.io/api/v3/athlete/#update
     *
     * @return string
     */
    public function put($request, $parameters = array())
    {
        throw new Exception("put $request: Not implemented");
    }

    /**
     * Sends POST request to specified API endpoint
     *
     * @param string $request
     * @param array $parameters
     *
     * @example http://strava.github.io/api/v3/activities/#create
     *
     * @return string
     */
    public function post($request, $parameters = array())
    {
        throw new Exception("post $request: Not implemented");
    }

    // the next time we get a type (eg "get") request that matches the (eg 'activities') we will give the $response;

    /**
     * Sends DELETE request to specified API endpoint
     *
     * @param string $request
     * @param array $parameters
     *
     * @example http://strava.github.io/api/v3/activities/#delete
     *
     * @return string
     */
    public function delete($request, $parameters = array())
    {
        throw new Exception("delete $request: Not implemented");
    }


}


?>
