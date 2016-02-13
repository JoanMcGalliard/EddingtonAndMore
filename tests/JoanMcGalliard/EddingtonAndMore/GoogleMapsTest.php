<?php

namespace JoanMcGalliard\EddingtonAndMore;

require_once 'BaseTestClass.php';
require_once 'JoanMcGalliard/EddingtonAndMore/GoogleMaps.php';



class GoogleMapsTest extends BaseTestClass
{

    public function testTimezoneFromCoordsDifferentGoogleResponses()
    {
        $mock = $this->getMockBuilder('GoogleApi')->setMethods(array('getError', 'get'))->getMock();
        /** @var GoogleMaps $googleMaps */

        $googleMaps = new GoogleMaps("",$mock);
        $mock->expects($this->at(0))->method('get')
            ->with("timezone/json",
                array('location' => '0,1','timestamp' => '2015-12-26 21:56:00 UTC'))
            ->willReturn('{"dstOffset" : 0,
                  "rawOffset" : 36000,
                  "status" : "OK",
                  "timeZoneId" : "Australia/Hobart",
                  "timeZoneName" : "Australian Eastern Standard Time"}');
        $this->assertEquals('Australia/Hobart', $googleMaps->timezoneFromCoords(0, 1, "2015-12-26 21:56:00 UTC"));


        // google doesn't recognise the request
        $googleMaps = new GoogleMaps("",$mock);

        $mock->expects($this->at(0))->method('get')
            ->with("timezone/json",
                array('location' => '10,20','timestamp' => '2015-12-27 21:56:00 UTC'))
            ->willReturn(include('data/input/googleApi404.php'));
        $this->assertEquals(null, $googleMaps->timezoneFromCoords(10, 20, "2015-12-27 21:56:00 UTC"));
        $this->assertEquals(include('data/input/googleApi404.php'), $googleMaps->getError());

        // can't connect, so we get a false/null or error message.
        $googleMaps = new GoogleMaps("",$mock);

        $mock->method('getError')->willReturnOnConsecutiveCalls("API ERROR MESSAGE", "API ERROR MESSAGE 2");
        $mock->expects($this->at(0))->method('get')
            ->with("timezone/json",
                array('location' => '30,40','timestamp' => '2015-12-28 21:56:00 UTC'))
            ->willReturn(false);
        $this->assertEquals(null, $googleMaps->timezoneFromCoords(30, 40, "2015-12-28 21:56:00 UTC"));
        $this->assertEquals("API ERROR MESSAGE", $googleMaps->getError());

        $googleMaps = new GoogleMaps("",$mock);
        $mock->expects($this->at(0))->method('get')
            ->with("timezone/json",
                array('location' => '50,60','timestamp' => '2015-12-29 21:56:00 UTC'))
            ->willReturn(null);
        $this->assertEquals(null, $googleMaps->timezoneFromCoords(50, 60, "2015-12-29 21:56:00 UTC"));
        $this->assertEquals("API ERROR MESSAGE 2", $googleMaps->getError());

        // We get JSON, but it's an error message
        $googleMaps = new GoogleMaps("",$mock);
        $mock->expects($this->at(0))->method('get')
            ->willReturn('{"errorMessage" : "The provided API key is invalid.",
                  "status" : "REQUEST_DENIED"}');
        $this->assertEquals(null, $googleMaps->timezoneFromCoords(10, 20, "2015-12-27 21:56:00 UTC"));
        $this->assertEquals("The provided API key is invalid.", $googleMaps->getError());

        // We get JSON, but it's nothing we expect
        $googleMaps = new GoogleMaps("",$mock);
        $mock->expects($this->at(0))->method('get')
            ->willReturn('{"blah" : "di blah.","di" : "blah"}');
        $this->assertEquals(null, $googleMaps->timezoneFromCoords(10, 20, "2015-12-27 21:56:00 UTC"));
        $this->assertEquals('Unknown JSON returned by Google API, {"blah" : "di blah.","di" : "blah"}', $googleMaps->getError());

    }

}