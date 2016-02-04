<?php

namespace JoanMcGalliard\EddingtonAndMore;

require_once 'mocks/RideWithGpsMock.php';
require_once 'BaseTestClass.php';
require_once 'JoanMcGalliard/EddingtonAndMore/RideWithGps.php';

use JoanMcGalliard\EddingtonAndMore\mocks\RideWithGpsMock;
use ReflectionClass;

class RideWithGpsTest extends BaseTestClass
{
public function testIsConnected()
{
    // set up.  We need a RideWithGps object with a valid user Id.  It's 99999
    $mock = new RideWithGpsMock();
    $rideWithGps = new RideWithGps("", array($this, 'myEcho'), $mock);
    //  before we get auth token, it should fail.
    $this->assertEquals(false,$rideWithGps->isConnected());
    $this->assertEquals(null,$rideWithGps->getUserId());

    //auth token set, but it's not correct
    $rideWithGps->setAuth("AUTH TOKEN");
    $mock->clearResponses("get", "/users/current.json");
    $mock->primeResponse('get', '/users/current.json', '{"error":"Unable to authenticate, please provide a valid username/password, auth_token or a session"}');
    $this->assertEquals(false,$rideWithGps->isConnected());
    $this->assertEquals(null,$rideWithGps->getUserId());
    $this->assertEquals("Unable to authenticate, please provide a valid username/password, auth_token or a session",$rideWithGps->getError());

    //happy path, everything correct
    $rideWithGps->setAuth("AUTH TOKEN");
    $mock->clearResponses("get", "/users/current.json");
    $mock->primeResponse('get', '/users/current.json', include("data/apiResponses/rwgpsCurrentUser.php"));
    assert($rideWithGps->isConnected());
    $this->assertEquals(99999,$rideWithGps->getUserId());
    // it not need to check with
    assert($rideWithGps->isConnected());
}
    public function testGetRides() {
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



    protected static function getMethod($name) {
        $class = new ReflectionClass('JoanMcGalliard\EddingtonAndMore\RideWithGps');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    public function testConvertToSeconds() {
        $convertToSeconds = self::getMethod('convertToSeconds');
        $obj = new RideWithGps("","");
        $convertToSeconds->invokeArgs($obj, array("01:00:00"));
        $this->assertEquals(1,$convertToSeconds->invokeArgs($obj, array("1")));
        $this->assertEquals(4032,$convertToSeconds->invokeArgs($obj, array("4032")));
        $this->assertEquals(70,$convertToSeconds->invokeArgs($obj, array("1:10")));
        $this->assertEquals(70,$convertToSeconds->invokeArgs($obj, array("01:10")));
        $this->assertEquals(362112,$convertToSeconds->invokeArgs($obj, array("100:35:12")));
        $this->assertEquals(0,$convertToSeconds->invokeArgs($obj, array("00:00:00")));
        $this->assertEquals(0,$convertToSeconds->invokeArgs($obj, array("random string")));
        $this->assertEquals(728,$convertToSeconds->invokeArgs($obj, array("random string:12:08")));
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

?>
