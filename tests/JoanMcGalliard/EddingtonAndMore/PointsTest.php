<?php

namespace JoanMcGalliard\EddingtonAndMore;

require_once "JoanMcGalliard/EddingtonAndMore/Points.php";

use PHPUnit_Framework_TestCase;

class PointsTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();
        date_default_timezone_set('UTC');
    }

    protected function tearDown()
    {
        parent::tearDown();
    }


    public function testgetRides ()
    {
        $points = new Points("2015-12-27 21:56:00 UTC");
        $expected = '<?xml version="1.0" encoding="UTF-8"?> <gpx creator="Eddington &amp; More" ><trk><trkseg>
</trkseg> </trk> </gpx>';
        $this->assertEquals($expected, $points->gpx());
    }
}
