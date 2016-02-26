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
    public function testRareDot() {
        $tester=new Tester(array($this, 'myEcho'));
        $rareDot = $this->getMethod('rareDot');
        $this->output="";
        for ($i=0;$i<3999; $i++) {
            $rareDot->invokeArgs($tester, array());
        }
        $this->assertEquals("...",$this->output);
        $this->output="";
        for ($i=0;$i<4000; $i++) {
            $rareDot->invokeArgs($tester, array());
        }
        $this->assertEquals("....",$this->output);
    }
}

class Tester extends TrackerAbstract {


    /**
     * Tester constructor.
     */
    public function __construct($echoCallback=null)
    {
        $this->echoCallback = $echoCallback;
    }

    public function isConnected()
    {
    }


    public function getRides($start_date, $end_date)
    {
    }

    public function getOvernightActivities()
    {
        // TODO: Implement getOvernightActivities() method.
    }

    public function getBike($id)
    {
        // TODO: Implement getBike() method.
    }

    public function bikeMatch($brand, $model, $id)
    {
        // TODO: Implement bikeMatch() method.
    }

    public function addRide($date, $ride, $points)
    {
        // TODO: Implement addRide() method.
    }

    public function activityUrl($id)
    {
        // TODO: Implement activityUrl() method.
    }

    public function waitForPendingUploads()
    {
        // TODO: Implement waitForPendingUploads() method.
    }
}