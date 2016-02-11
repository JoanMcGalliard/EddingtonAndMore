<?php

namespace JoanMcGalliard\EddingtonAndMore;

require_once 'BaseTestClass.php';
require_once 'JoanMcGalliard/EddingtonAndMore/Points.php';
require_once 'JoanMcGalliard/EddingtonAndMore/APIs/GoogleApi.php';

use JoanMcGalliard;

date_default_timezone_set("UTC");

class PointsTest extends BaseTestClass
{
    /**
     * PointsTest constructor.
     */
    public function __construct()
    {
        date_default_timezone_set("UTC");
    }


    public function testTimezoneFromCoords()
    {
        $mock = $this->getMockBuilder('GoogleApiMock')->setMethods(array('getError', 'get'))->getMock();
        $mock->method('get')
            ->willReturnOnConsecutiveCalls(
                '{"dstOffset" : 0,
                  "rawOffset" : 36000,
                  "status" : "OK",
                  "timeZoneId" : "Australia/Hobart",
                  "timeZoneName" : "Australian Eastern Standard Time"}',
                include('data/input/googleApi404.php'),
                false,
                '{"errorMessage" : "The provided API key is invalid.",
                  "status" : "REQUEST_DENIED"}');
        $mock->method('getError')->willReturnOnConsecutiveCalls("API ERROR MESSAGE", 1 ,2,3,4,5,6,7,8);
        $points = new Points("2015-12-27 21:56:00 UTC", array($this, 'myEcho'), null, $mock);
        $points->clearStoredPoint();
        $this->assertEquals('Australia/Hobart', $points->timezoneFromCoords(10, 10, "2015-12-27 21:56:00 UTC"));
        $points = new Points("2015-12-27 21:56:00 UTC", array($this, 'myEcho'), null, $mock);
        $points->clearStoredPoint();
        $this->assertEquals('UTC', $points->timezoneFromCoords(10, 10, "2015-12-27 21:56:00 UTC"));
        $this->assertEquals(include('data/input/googleApi404.php'), $points->getError());
        $points->setError("");
        $this->assertEquals('UTC', $points->timezoneFromCoords(10, 10, "2015-12-27 21:56:00 UTC"));
        $this->assertEquals("API ERROR MESSAGE", $points->getError());
        $points->clearStoredPoint();
        $points->setError("");
        $this->assertEquals('UTC', $points->timezoneFromCoords(10, 10, "2015-12-27 21:56:00 UTC"));
        $this->assertEquals("The provided API key is invalid.", $points->getError());
    }


    public function testEmptyGpx()
    {
        $points = new Points("2015-12-27 21:56:00 UTC", array($this, 'myEcho'));
        $expected = '<?xml version="1.0" encoding="UTF-8"?> <gpx creator="Eddington &amp; More" ><trk><trkseg>
</trkseg> </trk> </gpx>';
        $this->assertEquals($expected, $points->gpx());
    }

    public function testGpx()
    {
        $points = new Points("2015-12-27 21:56:00 UTC", array($this, 'myEcho'));
        $points->setGenerateGPX(true);
        $points->add(51.4384070, -0.3319030, "2015-12-28T10:40:13Z");
        $points->add(51.4382350, -0.3317770, "2015-12-28T10:40:16Z");
        $points->add(51.4378720, -0.3315100, "2015-12-28T10:40:22Z");
        $points->add(51.4376860, -0.3313720, "2015-12-28T10:40:25Z");
        $points->add(51.4374980, -0.3312660, "2015-12-28T10:40:28Z");
        $points->add(51.4371450, -0.3310790, "2015-12-28T10:40:34Z");
        $this->assertEquals(include('data/expected/gpx.php'), $points->gpx());
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


