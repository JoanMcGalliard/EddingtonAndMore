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

        $this->classUnderTest='JoanMcGalliard\EddingtonAndMore\Points';
        date_default_timezone_set("UTC");
    }
    public function testDistance() {
        // just checking that my distance calculation is close value given by another source.
        //  This data is also used in a test below, so it's important to know it gets these values.
        $points = new Points("2015-12-27 21:56:00 UTC", array($this, 'myEcho'), null, null);
        $points->clearStoredPoint();
        $this->assertEquals(16, intval($points->distance(51.50703,-0.12728,51.51152,-0.36048)/1000));
        $this->assertEquals(38, intval($points->distance(51.50703,-0.12728,51.49121,-0.68939)/1000));
        $this->assertEquals(59, intval($points->distance(51.50703,-0.12728,51.45657,-0.97709)/1000));
    }

    public function testDay() {
        $day = $this->getMethod('day');
        $points = new Points("2015-12-27 21:56:00 UTC", array($this, 'myEcho'),null, "Australia/Melbourne");
        $points->clearStoredPoint();
        $this->assertEquals("2016-02-10", $day->invokeArgs($points, array("2016-02-10T12:55:00Z")));
        $this->assertEquals("2016-02-12", $day->invokeArgs($points, array("2016-02-11T13:55:00Z"))); //todo review this
        $this->assertEquals("2016-07-10", $day->invokeArgs($points, array("2016-07-10T13:55:00Z")));
    }


    public function testSplittingRides()
    {

        // points that cross midnight in melbourne, summer.  6km before midnight, 6.5 after.
        $points = new Points("2015-12-27 21:56:00 UTC", array($this, 'myEcho'), null, "Australia/Melbourne");
        $points->clearStoredPoint();
        include('data/input/melbourneRide.php'); // points for a short ride crossing midnight in a remote timezone.
        $splits = $points->getSplits();
        $this->assertEquals(2, sizeof($splits));
        $this->assertEquals(array("2016-02-11", "2016-02-12"), array_keys($splits));
        $this->assertEquals(6.0, round($splits["2016-02-11"] / 1000, 1));
        $this->assertEquals(6.5, round($splits["2016-02-12"] / 1000, 1));


        // if the same ride happens in New York, then it should not be split.  Note because we set timezone,
        // it won't check so it doesn't matter that points are actually in melbourne.
        $points = new Points("2015-12-27 21:56:00 UTC", array($this, 'myEcho'), null, "America/New_York");
        $points->clearStoredPoint();
        include('data/input/melbourneRide.php'); // points for a short ride crossing midnight in a remote timezone.
        $splits = $points->getSplits();
        $this->assertEquals(1, sizeof($splits));
        $this->assertEquals(12.5, round($splits["2016-02-11"] / 1000, 1));
    }
        public function testTimezoneFromCoordsDifferentGoogleResponses()
        {
            $mock = $this->getMockBuilder('GoogleMaps')->setMethods(array('timezoneFromCoords', 'getError'))->getMock();
            $points = new Points("2015-12-27 21:56:00 UTC", array($this, 'myEcho'), $mock);

            $points->clearStoredPoint();
            // happy path, returns a properly formatted JSON with timezone
            $mock->expects($this->at(0))->method('timezoneFromCoords')
                ->with(0, 1, '2015-12-26 21:56:00 UTC')
                ->willReturn('Australia/Hobart');
            $this->assertEquals('Australia/Hobart', $points->timezoneFromCoords(0, 1, "2015-12-26 21:56:00 UTC"));


            // google doesn't recognise the request
            $mock->expects($this->at(0))->method('timezoneFromCoords')
                ->with(10, 20, '2015-12-27 21:56:00 UTC')
                ->willReturn(null);
            $mock->expects($this->any())->method('getError')
                ->willReturn('ERROR THINGY');
            $points = new Points("2015-12-27 21:56:00 UTC", array($this, 'myEcho'), $mock);
            $points->clearStoredPoint();;
            $this->assertEquals('UTC', $points->timezoneFromCoords(10, 20, "2015-12-27 21:56:00 UTC"));
            $this->assertEquals('ERROR THINGY', $points->getError());
            // can't connect, so we get a false/null or error message.

            $mock = $this->getMockBuilder('GoogleMaps')->setMethods(array('timezoneFromCoords', 'getError'))->getMock();
            $mock->expects($this->any())->method('getError')
                ->willReturn('ERROR THINGY 2');
            $points = new Points("2015-12-27 21:56:00 UTC", array($this, 'myEcho'), $mock);
            $points->clearStoredPoint();
            $mock->expects($this->at(0))->method('timezoneFromCoords')
                ->with(30, 40, '2015-12-28 21:56:00 UTC')
                ->willReturn(false);
            $this->assertEquals('UTC', $points->timezoneFromCoords(30, 40, "2015-12-28 21:56:00 UTC"));
            $this->assertEquals('ERROR THINGY 2', $points->getError());
            $points = new Points("2015-12-27 21:56:00 UTC", array($this, 'myEcho'), $mock);

        }

       public function testTimezoneFromCoordsReusingValues()
       {
           // if the points are with 50km of the last time we asked google api for anything, we should just return the same
           // timezone.
           $mock = $this->getMockBuilder('GoogleMaps')->setMethods(array('timezoneFromCoords'))->getMock();
           $points = new Points("2015-12-27 21:56:00 UTC", array($this, 'myEcho'), $mock);
           $points->clearStoredPoint();
           $mock->expects($this->any())->method('timezoneFromCoords')
               ->willReturnOnConsecutiveCalls("Europe/London", "Europe/Paris");
           $this->assertEquals('Europe/London', $points->timezoneFromCoords(51.50703, -0.12728, "2015-12-26 21:56:00 UTC"));
           //same point should not go to api again
           $this->assertEquals('Europe/London', $points->timezoneFromCoords(51.50703, -0.12728, "2015-12-26 21:56:00 UTC"));
           //point 38km away should not go to api again
           $this->assertEquals('Europe/London', $points->timezoneFromCoords(51.49121, -0.68939, "2015-12-26 21:56:00 UTC"));
           //point 59km away should  go to api again - note the point is Reading, not really Paris.
           $this->assertEquals('Europe/Paris', $points->timezoneFromCoords(51.45657, -0.97709, "2015-12-26 21:56:00 UTC"));


       }

      public function testEmptyGpx()
        {
            $points = new Points("2015-12-27 21:56:00 UTC", array($this, 'myEcho'));
            $points->clearStoredPoint();
            $expected = '<?xml version="1.0" encoding="UTF-8"?> <gpx creator="Eddington &amp; More" ><trk><trkseg>';
            $expected.="\n</trkseg> </trk> </gpx>";
            $this->assertEquals($expected, $points->gpx());
        }
        public function testGpx()
        {
            $points = new Points("2015-12-27 21:56:00 UTC", array($this, 'myEcho'));
            $points->clearStoredPoint();
            $points->setGenerateGPX(true);
            $points->add(51.4384070, -0.3319030, "2015-12-28T10:40:13Z");
            $points->add(51.4382350, -0.3317770, "2015-12-28T10:40:16Z");
            $points->add(51.4378720, -0.3315100, "2015-12-28T10:40:22Z");
            $points->add(51.4376860, -0.3313720, "2015-12-28T10:40:25Z");
            $points->add(51.4374980, -0.3312660, "2015-12-28T10:40:28Z");
            $points->add(51.4371450, -0.3310790, "2015-12-28T10:40:34Z");
            $this->assertEquals(include('data/expected/gpx.php'), $points->gpx());
        }


}


