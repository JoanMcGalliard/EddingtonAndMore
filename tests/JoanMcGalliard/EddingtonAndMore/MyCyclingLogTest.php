<?php

namespace JoanMcGalliard\EddingtonAndMore;
require_once 'JoanMcGalliard/EddingtonAndMore/MyCyclingLog.php';
require_once 'BaseTestClass.php';

class MyCyclingLogTest extends BaseTestClass
{
    protected $classUnderTest = 'JoanMcGalliard\EddingtonAndMore\MyCyclingLog';

    protected function numberOfRides($rides)
    {
        $count = 0;
        foreach ($rides as $day => $ride_list) {
            foreach ($ride_list as $ride) {
                $count++;
            }
        }
        return $count;
    }

    public function testIsConnected()
    {

        $mock = $this->getMockBuilder('MyCyclingLogApi')->setMethods(array('getPage', 'getAuth', 'setAuth'))->getMock();
        $myCyclingLog = new MyCyclingLog(array($this, 'myEcho'), $mock);

        // no auth token saved
        $mock->expects($this->at(0))->method('getAuth')->with()
            ->willReturn(null);
        $this->assertFalse($myCyclingLog->isConnected());

        // we are not authorised
        $mock->expects($this->any())->method('getAuth')->with()
            ->willReturn("auth");
        $mock->expects($this->at(1))->method('getPage')
            ->with('?method=ride.list&limit=0&offset=0')
            ->willReturn("You are not authorized.");
        $this->assertFalse($myCyclingLog->isConnected());

        //happy path
        $mock->expects($this->at(1))->method('getPage')
            ->with('?method=ride.list&limit=0&offset=0')
            ->willReturn("<?xml version=\"1.0\" encoding=\"UTF-8\"?><response><list offset=\"0\" limit=\"0\" total_size=\"2934\"></list></response>");
        $this->assertTrue($myCyclingLog->isConnected());
        // doesn't test connection again, just returns true;
        $this->assertTrue($myCyclingLog->isConnected());

        // connect ok even if zero rides

        $this->setProperty('connected', false, $myCyclingLog);

        $mock->expects($this->at(1))->method('getPage')
            ->with('?method=ride.list&limit=0&offset=0')
            ->willReturn("<?xml version=\"1.0\" encoding=\"UTF-8\"?><response><list offset=\"0\" limit=\"0\" total_size=\"0\"></list></response>");
        $this->assertTrue($myCyclingLog->isConnected());


        // bad xml

        $this->setProperty('connected', false, $myCyclingLog);
        $mock->expects($this->at(1))->method('getPage')
            ->with('?method=ride.list&limit=0&offset=0')
            ->willReturn("<?xml version=\"1.0\"?> <gpx version=\"1.0\" creator=\"CardioTrainer - http://www.worksmartlabs.com/cardiotrainer\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns=\"http://www.topografix.com/GPX/1/0\" xsi:schemaLocation=\"http://www.topografix.com/GPX/1/0 http://www.topografix.com/GPX/1/0/gpx.xsd\"> <time>2015-02-13T15:53:58Z</time> <name>2015-02-13T15:53:58Z</name> <desc>Track recorded by CardioTrainer on 2015-02-13T15:53:58Z Distance: 2.5 km.</desc><trk> </trk> </gpx>");
        $this->assertFalse($myCyclingLog->isConnected());


        $this->setProperty('connected', false, $myCyclingLog);
        $mock->expects($this->at(1))->method('getPage')
            ->with('?method=ride.list&limit=0&offset=0')
            ->willReturn("<?xml version=\"1.0\" encoding=\"UTF-8\"?><response><list offset=\"0\" limit=\"0\" total_sizex=\"0\"></list></response>");
        $this->assertFalse($myCyclingLog->isConnected());


    }

    public function testGetRides()
    {

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


        // date range
        $mock->expects($this->at(0))->method('getPage')
            ->with('?method=ride.list&limit=4&offset=0')
            ->willReturn(include("data/input/mclActivities2a.php"));
        $mock->expects($this->at(1))->method('getPage')
            ->with('?method=ride.list&limit=4&offset=4')
            ->willReturn(include("data/input/mclActivities2b.php"));
        $mock->expects($this->at(2))->method('getPage')
            ->with('?method=ride.list&limit=4&offset=8')
            ->willReturn(include("data/input/mclActivities2c.php"));
        $rides = $myCyclingLog->getRides(strtotime("2016-02-03"), strtotime("2016-02-09"), 4);
        $this->assertEquals(6, $this->numberOfRides($rides));
        $this->assertEquals(include('data/expected/mclActivities3.php'), $rides);


    }

    public function testPostPageDom()
    {
        $mock = $this->getMockBuilder('MyCyclingLogApi')->setMethods(array('postPage', 'setAuth'))->getMock();
        $myCyclingLog = new MyCyclingLog(array($this, 'myEcho'), $mock);
        $postPageDom = $this->getMethod('postPageDom');
        $params = array('event_date' => '02/07/2016', 'is_ride' => 'T', 'h' => 2, 'm' => 21, 's' => 24,
            'distance' => 32000 * 0.00062137119224, 'user_unit' => 'mi',
            'notes' => 'http://www.strava.com/activities/490216308',
            'max_speed' => 7.8 * 3600 * 0.00062137119224, 'elevation' => 0, 'bid' => '');
        $mock->expects($this->at(0))->method('postPage')
            ->with('?method=ride.new', $params)
            ->willReturn("<?xml version=\"1.0\" encoding=\"UTF-8\"?><response>1212796</response>");

        $this->output = "";
        $this->assertEquals("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<response>1212796</response>\n", $postPageDom->invokeArgs($myCyclingLog, array('?method=ride.new', $params))->saveXML());
        $this->assertEquals("", $this->output);


        // mock returns null once, then works
        $mock->expects($this->at(0))->method('postPage')
            ->with('?method=ride.new', $params)
            ->willReturn(null);
        $mock->expects($this->at(1))->method('postPage')
            ->with('?method=ride.new', $params)
            ->willReturn("<?xml version=\"1.0\" encoding=\"UTF-8\"?><response>1212796</response>");

        $this->output = "";
        $this->assertEquals("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<response>1212796</response>\n", $postPageDom->invokeArgs($myCyclingLog, array('?method=ride.new', $params))->saveXML());
        $this->assertEquals("", $this->output);

        // mock returns not auth message, we need to set auth to null.
        $mock->expects($this->at(0))->method('postPage')
            ->with('?method=ride.new', $params)
            ->willReturn("You are not authorized.");
        $mock->expects($this->once())->method('setAuth')
            ->with(null);

        $this->output = "";
        $this->assertEquals("", $postPageDom->invokeArgs($myCyclingLog, array('?method=ride.new', $params)));
        $this->assertEquals("", $this->output);

        // Unexpected non-xml value returned by API.
        $mock->expects($this->at(0))->method('postPage')
            ->with('?method=ride.new', $params)
            ->willReturn("RANDOM STRING");

        $this->output = "";
        $this->assertEquals("", $postPageDom->invokeArgs($myCyclingLog, array('?method=ride.new', $params)));
        $this->assertEquals("There is a problem with MyCyclingLog.  Please try again: RANDOM STRING", $this->output);

        // mock returns null each time, so should try 3 times before return null
        $mock->expects($this->exactly(3))->method('postPage')
            ->with('?method=ride.new', $params)
            ->willReturn(null);

        $this->output = "";
        $this->assertNull($postPageDom->invokeArgs($myCyclingLog, array('?method=ride.new', $params)));
        $this->assertEquals("There is a problem with MyCyclingLog.  Please try again", $this->output);


    }

    public function testAddRide()
    {
        $mock = $this->getMockBuilder('MyCyclingLogApi')->setMethods(array('postPage', 'setAuth'))->getMock();
        $myCyclingLog = new MyCyclingLog(array($this, 'myEcho'), $mock);

        $params = array('event_date' => '02/07/2016', 'is_ride' => 'T', 'h' => 2, 'm' => 21, 's' => 24,
            'distance' => 32000 * 0.00062137119224, 'max_speed' => 7.8 * 3600 * 0.00062137119224,
            'notes' => 'http://www.strava.com/activities/490216308',
            'elevation' => 0, 'bid' => 'b267883', 'user_unit' => 'mi');
        $mock->expects($this->at(0))->method('postPage')
            ->with('?method=ride.new', $params)
            ->willReturn("<?xml version=\"1.0\" encoding=\"UTF-8\"?><response>1212796</response>");

        $ride = array('distance' => 32000, 'name' => 'Lunch Ride', 'strava_id' => 490216308,
            'start_time' => '2016-02-07T11:19:54Z', 'bike' => 'b267883', 'moving_time' => 8484,
            'elapsed_time' => 2125, 'total_elevation_gain' => 0, 'max_speed' => 7.8,
            'timezone' => 'Europe/London', 'endo_id' => 668479655,);

        $this->assertEquals(1212796, $myCyclingLog->addRide('2016-02-07', $ride, null));
        // no response from API
        $this->assertEquals(null, $myCyclingLog->addRide('2016-02-07', $ride, null));
        // api returns error
        $mock->expects($this->at(0))->method('postPage')
            ->with('?method=ride.new', $params)
            ->willReturn("You are not authorized.");
        $this->assertEquals(null, $myCyclingLog->addRide('2016-02-07', $ride, null));

        // make sure correct values are passed to api
        $params = array('event_date' => '02/07/2016', 'is_ride' => 'T', 'h' => 2, 'm' => 21, 's' => 24,
            'distance' => 32000 * 0.00062137119224, 'max_speed' => 7.8 * 3600 * 0.00062137119224,
            'elevation' => 400, 'bid' => null, 'user_unit' => 'mi');
        $mock->expects($this->at(0))->method('postPage')
            ->with('?method=ride.new', $params)
            ->willReturn("<?xml version=\"1.0\" encoding=\"UTF-8\"?><response>1212796</response>");

        $ride = array('bike' => null, 'max_speed' => 7.8, 'distance' => 32000, 'moving_time' => 8484,
            'total_elevation_gain' => 400);
        $this->assertEquals(1212796, $myCyclingLog->addRide('2016-02-07', $ride, null));

        $this->setProperty('use_feet_for_elevation', true, $myCyclingLog);
        $params['elevation'] = 1312;

        $mock->expects($this->at(0))->method('postPage')
            ->with('?method=ride.new', $params)
            ->willReturn("<?xml version=\"1.0\" encoding=\"UTF-8\"?><response>1212796</response>");
        $this->assertEquals(1212796, $myCyclingLog->addRide('2016-02-07', $ride, null));


        $ride['moving_time'] = 10921;
        $params['h'] = 3;
        $params['m'] = 2;
        $params['s'] = 1;

        $mock->expects($this->at(0))->method('postPage')
            ->with('?method=ride.new', $params)
            ->willReturn("<?xml version=\"1.0\" encoding=\"UTF-8\"?><response>1212796</response>");
        $this->assertEquals(1212796, $myCyclingLog->addRide('2016-02-07', $ride, null));

        $params['event_date'] = '01/07/2015';
        $mock->expects($this->at(0))->method('postPage')
            ->with('?method=ride.new', $params)
            ->willReturn("<?xml version=\"1.0\" encoding=\"UTF-8\"?><response>1212796</response>");
        $this->assertEquals(1212796, $myCyclingLog->addRide('2015-01-07', $ride, null));

        $ride['distance'] = 0;
        $params['distance'] = 0;

        $mock->expects($this->at(0))->method('postPage')
            ->with('?method=ride.new', $params)
            ->willReturn("<?xml version=\"1.0\" encoding=\"UTF-8\"?><response>1212796</response>");
        $this->assertEquals(1212796, $myCyclingLog->addRide('2015-01-07', $ride, null));

        $ride['distance'] = 16000;
        $params['distance'] = 9.9419390758;

        $mock->expects($this->at(0))->method('postPage')
            ->with('?method=ride.new', $params)
            ->willReturn("<?xml version=\"1.0\" encoding=\"UTF-8\"?><response>1212796</response>");
        $this->assertEquals(1212796, $myCyclingLog->addRide('2015-01-07', $ride, null));

        $ride['max_speed'] = 10;
        $params['max_speed'] = 22.36936292064;

        $mock->expects($this->at(0))->method('postPage')
            ->with('?method=ride.new', $params)
            ->willReturn("<?xml version=\"1.0\" encoding=\"UTF-8\"?><response>1212796</response>");
        $this->assertEquals(1212796, $myCyclingLog->addRide('2015-01-07', $ride, null));
    }

    public function testGetBikes()
    {
        $mock = $this->getMockBuilder('MyCyclingLogApi')->setMethods(array('getPage'))->getMock();
        $myCyclingLog = new MyCyclingLog(array($this, 'myEcho'), $mock);
        $getBikes = $this->getMethod('getBikes');
        $mock->expects($this->at(0))->method('getPage')
            ->with('?method=bike.list&limit=0&offset=0')
            ->willReturn(include('data/input/mclBikes0.php'));
        $mock->expects($this->at(1))->method('getPage')
            ->with('?method=bike.list&limit=20&offset=0')
            ->willReturn(include('data/input/mclBikes1.php'));

        $avail = array('brand' => 'Giant', 'model' => 'Avail 2', 'year' => 2012);
        $mezzo = array('brand' => 'Mezzo', 'model' => 'D9', 'year' => 2008);
        $this->assertEquals(array(18795 => $avail,18796 => $mezzo), $getBikes->invokeArgs($myCyclingLog, array()));

        // more than one page of bikes
        $myCyclingLog = new MyCyclingLog(array($this, 'myEcho'), $mock);
        $mock->expects($this->at(0))->method('getPage')
            ->with('?method=bike.list&limit=0&offset=0')
            ->willReturn(include('data/input/mclBikes3.php'));
        $mock->expects($this->at(1))->method('getPage')
            ->with('?method=bike.list&limit=2&offset=0')
            ->willReturn(include('data/input/mclBikes1.php'));
        $mock->expects($this->at(2))->method('getPage')
            ->with('?method=bike.list&limit=2&offset=2')
            ->willReturn(include('data/input/mclBikes4.php'));
        $mock->expects($this->at(3))->method('getPage')
            ->with('?method=bike.list&limit=2&offset=4')
            ->willReturn(include('data/input/mclBikes5.php'));

        $this->assertEquals(array(18795 => $avail,18796 => $mezzo, 18797 => $avail,18798 => $mezzo, 18799 => $avail), $getBikes->invokeArgs($myCyclingLog, array(2)));

        // no bikes store, empty array returned
        $myCyclingLog = new MyCyclingLog(array($this, 'myEcho'), $mock);
        $mock->expects($this->once())->method('getPage')
            ->with('?method=bike.list&limit=0&offset=0')
            ->willReturn(include('data/input/mclBikes2.php'));

        $this->assertEquals([], $getBikes->invokeArgs($myCyclingLog, array()));

    }
    public function testGetBike()
    {

        $mock = $this->getMockBuilder('MyCyclingLogApi')->setMethods(array('getPage'))->getMock();
        $myCyclingLog = new MyCyclingLog(array($this, 'myEcho'), $mock);
        $getBikes = $this->getMethod('getBikes');
        $mock->expects($this->at(0))->method('getPage')
            ->with('?method=bike.list&limit=0&offset=0')
            ->willReturn(include('data/input/mclBikes0.php'));
        $mock->expects($this->at(1))->method('getPage')
            ->with('?method=bike.list&limit=20&offset=0')
            ->willReturn(include('data/input/mclBikes1.php'));
        $this->assertEquals(array('brand' => "Giant", 'model' => "Avail 2", 'year' => '2012'), $myCyclingLog->getBike(18795));
        $this->assertEquals(array('brand' => "Mezzo", 'model' => "D9", 'year' => '2008'), $myCyclingLog->getBike(18796));
        $this->assertEquals(null, $myCyclingLog->getBike(18797));
    }

    public function testDeleteRides()
    {
        $mock = $this->getMockBuilder('MyCyclingLogApi')->setMethods(array('getPage', 'login', 'delete', 'logout'))->getMock();
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
        $this->output = "";
        $this->assertEquals(10,
            $myCyclingLog->deleteRides(null, null, 'username', 'password'));
        $this->assertEquals('Deleting 1210520 from 2016-02-09, strava id 490216193.<br>Deleting 1210521 from 2016-02-09, strava id 490216213.<br>Deleting 1210522 from 2016-02-09, strava id 490216220.<br>Deleting 1210523 from 2016-02-08, strava id 490216230.<br>Deleting 1210524 from 2016-02-08, strava id 490216249.<br>Deleting 1210528 from 2016-02-07, strava id 490216308.<br>Deleting 1210525 from 2016-02-07, strava id 490216271.<br>Deleting 1210526 from 2016-02-07, strava id 490216294.<br>Deleting 1210527 from 2016-02-07, strava id 490216295.<br>Deleting 1209139 from 2016-02-02, strava id 484814865.<br>'
            , $this->output);


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
        $this->output = "";
        $this->assertEquals(9,
            $myCyclingLog->deleteRides(null, null, 'username', 'password'));
        $this->assertEquals('Deleting 1210520 from 2016-02-09, strava id 490216193.<br>Deleting 1210521 from 2016-02-09, strava id 490216213.<br>Deleting 1210522 from 2016-02-09, strava id 490216220.<br>Deleting 1210523 from 2016-02-08, strava id 490216230.<br>Deleting 1210524 from 2016-02-08, strava id 490216249.<br>Deleting 1210528 from 2016-02-07, strava id 490216308: FAILED.<br>Deleting 1210525 from 2016-02-07, strava id 490216271.<br>Deleting 1210526 from 2016-02-07, strava id 490216294.<br>Deleting 1210527 from 2016-02-07, strava id 490216295.<br>Deleting 1209139 from 2016-02-02, strava id 484814865.<br>'
            , $this->output);
    }
}