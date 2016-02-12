<?php

namespace JoanMcGalliard\EddingtonAndMore;
require_once 'BaseTestClass.php';
require_once 'JoanMcGalliard/EddingtonAndMore/TrackerAbstract.php';



class TrackerAbstractTest extends BaseTestClass
{
    protected $classUnderTest='JoanMcGalliard\EddingtonAndMore\Tester';
    public function testIsOvernightRide() {
        $tester=new Tester();
        $isOvernightRide = $this->getMethod('isOvernight');

        $start_time = "2016-02-11T10:16:54Z"; //10:16 GMT, 21:16 Melbourne time, 4:16 chicago.
        $duration = 4*60*60; //4 hours

        $this->assertEquals(false, $isOvernightRide->invokeArgs($tester, array($start_time, "UTC", $duration)));
        $this->assertEquals(true, $isOvernightRide->invokeArgs($tester, array($start_time, "Australia/Melbourne", $duration)));
        $this->assertEquals(false, $isOvernightRide->invokeArgs($tester, array($start_time, "America/Chicago", $duration)));
        $duration =14*60*60; //14 hours
        $this->assertEquals(true, $isOvernightRide->invokeArgs($tester, array($start_time, "UTC", $duration)));
        $this->assertEquals(true, $isOvernightRide->invokeArgs($tester, array($start_time, "Australia/Melbourne", $duration)));
        $this->assertEquals(false, $isOvernightRide->invokeArgs($tester, array($start_time, "America/Chicago", $duration)));


    }
}

class Tester extends TrackerAbstract {

    public function isConnected()
    {
    }

    public function getRides($start_date, $end_date)
    {
    }
}