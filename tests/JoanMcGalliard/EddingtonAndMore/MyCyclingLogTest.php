<?php
/**
 * Created by IntelliJ IDEA.
 * User: jem
 * Date: 14/02/2016
 * Time: 08:53
 */

namespace JoanMcGalliard\EddingtonAndMore;
require_once 'JoanMcGalliard/EddingtonAndMore/MyCyclingLog.php';
require_once 'BaseTestClass.php';

class MyCyclingLogTest extends BaseTestClass
{
    protected $classUnderTest = 'JoanMcGalliard\EddingtonAndMore\MyCyclingLog';

    protected function numberOfRides($rides) {
        $count=0;
        foreach ($rides as $day =>$ride_list) {
            foreach ($ride_list as $ride) {
                $count++;
            }
        }
    return $count;
    }
    public function testGetRides() {

        // all rides in single page
        $mock = $this->getMockBuilder('MyCyclingLogApi')->setMethods(array('getPage'))->getMock();
        $myCyclingLog = new MyCyclingLog(array($this, 'myEcho'), $mock);
        $mock->expects($this->at(0))->method('getPage')
            ->with('?method=ride.list&limit=800&offset=0')
            ->willReturn(include("data/input/mclActivities1.php"));
        $rides = $myCyclingLog->getRides(null, null);
        $this->assertEquals(10, $this->numberOfRides($rides));
        $this->assertEquals(include('data/expected/mclActivities1.php'), $rides);

        // all rides over 3 page
        $mock->expects($this->at(0))->method('getPage')
            ->with('?method=ride.list&limit=4&offset=0')
            ->willReturn(include("data/input/mclActivities2a.php"));
        $mock->expects($this->at(1))->method('getPage')
            ->with('?method=ride.list&limit=4&offset=4')
            ->willReturn(include("data/input/mclActivities2b.php"));
        $mock->expects($this->at(2))->method('getPage')
            ->with('?method=ride.list&limit=4&offset=8')
            ->willReturn(include("data/input/mclActivities2c.php"));
        $rides = $myCyclingLog->getRides(null, null, 4);
        $this->assertEquals(10, $this->numberOfRides($rides));
        $this->assertEquals(include('data/expected/mclActivities1.php'), $rides);


    }
    public function testDeleteRides() {
        $mock = $this->getMockBuilder('MyCyclingLogApi')->setMethods(array('getPage','login','delete','logout'))->getMock();
        $myCyclingLog = new MyCyclingLog(array($this, 'myEcho'), $mock);

        // login fails
        $mock->expects($this->at(0))->method('login')
            ->with('username', 'password')
            ->willReturn("Could not resolve host: www.mycyclinglog.com");
        $this->assertEquals("Could not resolve host: www.mycyclinglog.com",
            $myCyclingLog->deleteRides(null, null, 'username', 'password'));
        $mock->expects($this->at(0))->method('login')
            ->with('username', 'password')
            ->willReturn("Check username (username) and password");
        $this->assertEquals("Check username (username) and password",
            $myCyclingLog->deleteRides(null, null, 'username', 'password'));

        // login succeeds, all deleted
        $mock->expects($this->at(0))->method('login')
            ->with('username', 'password')
            ->willReturn("OK");
        $mock->expects($this->any())->method('getPage')
            ->with('?method=ride.list&limit=800&offset=0')
            ->willReturn(include("data/input/mclActivities1.php"));
        $mock->expects($this->at(2))->method('delete')->with(1210520)
            ->willReturn(true);
        $mock->expects($this->at(3))->method('delete')->with(1210521)
            ->willReturn(true);
        $mock->expects($this->at(4))->method('delete')->with(1210522)
            ->willReturn(true);
        $mock->expects($this->at(5))->method('delete')->with(1210523)
            ->willReturn(true);
        $mock->expects($this->at(6))->method('delete')->with(1210524)
            ->willReturn(true);
        $mock->expects($this->at(7))->method('delete')->with(1210528)
            ->willReturn(true);
        $mock->expects($this->at(8))->method('delete')->with(1210525)
            ->willReturn(true);
        $mock->expects($this->at(9))->method('delete')->with(1210526)
            ->willReturn(true);
        $mock->expects($this->at(10))->method('delete')->with(1210527)
            ->willReturn(true);
        $mock->expects($this->at(11))->method('delete')->with(1209139)
            ->willReturn(true);
        $mock->expects($this->at(12))->method('logout')->with();
        $this->output="";
        $this->assertEquals(10,
            $myCyclingLog->deleteRides(null, null, 'username', 'password'));
        $this->assertEquals('Deleting 1210520 from 2016-02-09, strava id 490216193.<br>Deleting 1210521 from 2016-02-09, strava id 490216213.<br>Deleting 1210522 from 2016-02-09, strava id 490216220.<br>Deleting 1210523 from 2016-02-08, strava id 490216230.<br>Deleting 1210524 from 2016-02-08, strava id 490216249.<br>Deleting 1210528 from 2016-02-07, strava id 490216308.<br>Deleting 1210525 from 2016-02-07, strava id 490216271.<br>Deleting 1210526 from 2016-02-07, strava id 490216294.<br>Deleting 1210527 from 2016-02-07, strava id 490216295.<br>Deleting 1209139 from 2016-02-02, strava id 484814865.<br>'
            ,$this->output);


        // some fail
        $mock->expects($this->at(0))->method('login')
            ->with('username', 'password')
            ->willReturn("OK");
        $mock->expects($this->any())->method('getPage')
            ->with('?method=ride.list&limit=800&offset=0')
            ->willReturn(include("data/input/mclActivities1.php"));
        $mock->expects($this->at(2))->method('delete')->with(1210520)
            ->willReturn(true);
        $mock->expects($this->at(3))->method('delete')->with(1210521)
            ->willReturn(true);
        $mock->expects($this->at(4))->method('delete')->with(1210522)
            ->willReturn(true);
        $mock->expects($this->at(5))->method('delete')->with(1210523)
            ->willReturn(true);
        $mock->expects($this->at(6))->method('delete')->with(1210524)
            ->willReturn(true);
        $mock->expects($this->at(7))->method('delete')->with(1210528)
            ->willReturn(false);
        $mock->expects($this->at(8))->method('delete')->with(1210525)
            ->willReturn(true);
        $mock->expects($this->at(9))->method('delete')->with(1210526)
            ->willReturn(true);
        $mock->expects($this->at(10))->method('delete')->with(1210527)
            ->willReturn(true);
        $mock->expects($this->at(11))->method('delete')->with(1209139)
            ->willReturn(true);
        $mock->expects($this->at(12))->method('logout')->with();
        $this->output="";
        $this->assertEquals(9,
            $myCyclingLog->deleteRides(null, null, 'username', 'password'));
        $this->assertEquals('Deleting 1210520 from 2016-02-09, strava id 490216193.<br>Deleting 1210521 from 2016-02-09, strava id 490216213.<br>Deleting 1210522 from 2016-02-09, strava id 490216220.<br>Deleting 1210523 from 2016-02-08, strava id 490216230.<br>Deleting 1210524 from 2016-02-08, strava id 490216249.<br>Deleting 1210528 from 2016-02-07, strava id 490216308: FAILED.<br>Deleting 1210525 from 2016-02-07, strava id 490216271.<br>Deleting 1210526 from 2016-02-07, strava id 490216294.<br>Deleting 1210527 from 2016-02-07, strava id 490216295.<br>Deleting 1209139 from 2016-02-02, strava id 484814865.<br>'
            ,$this->output);
    }
    public function setUp() {
        date_default_timezone_set("UTC");
    }
}