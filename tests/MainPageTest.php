<?php

require_once "JoanMcGalliard/EddingtonAndMore/BaseTestClass.php";
require_once 'data/input/server.php';
require_once "MainPage.php";

class MainPageTest extends JoanMcGalliard\EddingtonAndMore\BaseTestClass
{
    private $mainPage;
    protected $classUnderTest = 'MainPage';
    private $mockPreferences;
    /** @var PHPUnit_Framework_MockObject_MockObject $mockConnectedWriteScope */


    public function setUp()
    {
        parent::setUp();

        $this->mainPage = new MainPage(array($this, 'myEcho'));
        $this->mockPreferences = $this->getMockBuilder('Preferences')->disableOriginalConstructor()
            ->setMethods(array('getTimezone', 'getStravaSplitRides', 'getEndoSplitRides', 'getRwgpsSplitRides', 'getMclUseFeet',
                'getMclUsername', 'getStravaWriteScope', 'getMclAuth', 'getEndoAuth', 'getRwgpsAuth', 'getStravaAccessToken',
                'setStravaSplitRides'
            ))->getMock();
        $this->mockPreferences->expects($this->any())->method('getTimezone')->willReturn('UTC');
        $this->mockPreferences->expects($this->any())->method('getStravaSplitRides')->willReturn(false);
        $this->mockPreferences->expects($this->any())->method('getEndoSplitRides')->willReturn(false);
        $this->mockPreferences->expects($this->any())->method('getRwgpsSplitRides')->willReturn(false);
        $this->mockPreferences->expects($this->any())->method('getMclUseFeet')->willReturn(false);
        $this->mockPreferences->expects($this->any())->method('getMclUsername')->willReturn("helen");
        $this->mockPreferences->expects($this->any())->method('getStravaWriteScope')->willReturn(true);
        $this->mockPreferences->expects($this->any())->method('getMclAuth')->willReturn("auth");
        $this->mockPreferences->expects($this->any())->method('getTimezone')->willReturn("auth");
        $this->mockPreferences->expects($this->any())->method('getRwgpsAuth')->willReturn("auth");
        $this->mockPreferences->expects($this->any())->method('getStravaAccessToken')->willReturn("token");
        $this->mockPreferences->expects($this->any())->method('setStravaSplitRides')->willReturn(false);
    }

    public function testEmail()
    {
        $email = $this->getMethod('email');
        $this->assertEquals(include('data/expected/emailform.php'), $email->invokeArgs($this->mainPage, array()));
    }

    public function testSumActivities()
    {
        $sumActivities = $this->getMethod('sumActivities');
        $this->assertEquals(include('data/expected/sumActivities.php'), $sumActivities->invokeArgs($this->mainPage, array(include('data/input/sumActivities.php'))));
    }

    public function testAskForStravaGpx()
    {
        $_POST = array(
            'start_date' => '01-01-2015',
            'end_date' => '31-12-2015',
        );
        $askForStravaGpx = $this->getMethod('askForStravaGpx');
        $this->assertEquals(include('data/expected/askForStravaGpx.php'), $askForStravaGpx->invokeArgs($this->mainPage, array(include('data/input/askForStravaGpx.php'),
            1000, 'calculate_from_strava', 'recalculate your E-Number'

        )));
    }

    public function testNextGoals()
    {
        $nextGoals = $this->getMethod('nextGoals');
        $this->assertEquals(array(2, 5, 10, 50, 100), $nextGoals->invokeArgs($this->mainPage, array(1)));
        $this->assertEquals(array(51, 55, 60, 100), $nextGoals->invokeArgs($this->mainPage, array(50)));
        $this->assertEquals(array(104, 105, 110, 150, 200), $nextGoals->invokeArgs($this->mainPage, array(103)));
        $this->assertEquals(array(50, 100), $nextGoals->invokeArgs($this->mainPage, array(49)));
    }

    public function testNumberOfDaysToGoal()
    {
        $numberOfDaysToGoal = $this->getMethod('numberOfDaysToGoal');
        $this->assertEquals(26, $numberOfDaysToGoal->invokeArgs($this->mainPage, array(28, array('2016-02-07' => 30, '2016-02-09' => 28, '2016-02-08' => 27), 1)));
        $this->assertEquals(12, $numberOfDaysToGoal->invokeArgs($this->mainPage, array(14, array('2016-02-07' => 30, '2016-02-09' => 28, '2016-02-08' => 26.9999999999), 0.5)));
        $this->assertEquals(6, $numberOfDaysToGoal->invokeArgs($this->mainPage, array(8, array('2016-02-07' => 29780.200000000001, '2016-02-09' => 22029.199999999997, '2016-02-08' => 6018.2000000000007), 0.00062137119223999997)));
    }

    public function testSumDay()
    {
        $sumDay = $this->getMethod('sumDay');
        $this->assertEquals(29780.2, $sumDay->invokeArgs($this->mainPage, array(include('data/input/daysRides.php'))));
        $this->assertEquals(7122.6, $sumDay->invokeArgs($this->mainPage, array(array(array('distance' => 7122.6)))));
        $this->assertEquals(0, $sumDay->invokeArgs($this->mainPage, array(array(array('distance' => 0)))));
        $this->assertEquals(0, $sumDay->invokeArgs($this->mainPage, array(array())));


    }

    public function testConnections()
    {
        $connections = $this->getMethod('connections');

        $this->setProperty('here', "HERE", $this->mainPage);

        $builder = $this->getMockBuilder('TrackerAbstract')
            ->setMethods(array('isConnected', 'writeScope', 'authenticationUrl', 'getUserId', 'getRides',
                'getOvernightActivities', 'getError', 'setUseFeetForElevation', 'setSplitOvernightRides',
                'setWriteScope', 'setAuth', 'setAccessToken'
            ));
        $mockConnectedWriteScope = $builder->getMock();
        $mockConnectedWriteScope->expects($this->any())->method('getOvernightActivities')->willReturn([]);
        $mockConnectedWriteScope->expects($this->any())->method('getError')->willReturn("");
        $mockConnectedWriteScope->expects($this->any())->method('isConnected')->willReturn(true);
        $mockConnectedWriteScope->expects($this->any())->method('writeScope')->willReturn(true);

        $mockConnectedReadOnly = $builder->getMock();
        $mockConnectedReadOnly->expects($this->any())->method('getOvernightActivities')->willReturn([]);
        $mockConnectedReadOnly->expects($this->any())->method('getError')->willReturn("");
        $mockConnectedReadOnly->expects($this->any())->method('isConnected')->willReturn(true);
        $mockConnectedReadOnly->expects($this->any())->method('writeScope')->willReturn(false);


        $mockNotConnected = $builder->getMock();
        $mockNotConnected->expects($this->any())->method('getOvernightActivities')->willReturn([]);
        $mockNotConnected->expects($this->any())->method('getError')->willReturn("");
        $mockNotConnected->expects($this->any())->method('isConnected')->willReturn(false);
        $mockNotConnected->expects($this->any())->method('writeScope')->willReturn(false);



        $this->setProperty('strava', $mockConnectedWriteScope, $this->mainPage);
        $this->setProperty('myCyclingLog', $mockConnectedReadOnly, $this->mainPage);
        $this->setProperty('endomondo', $mockConnectedReadOnly, $this->mainPage);
        $this->setProperty('rideWithGps', $mockConnectedReadOnly, $this->mainPage);
        // already connected to all services, there should be no connections section at all.
        $this->assertEquals('', $connections->invokeArgs($this->mainPage, array()));
        // not write connected to strava, there should be the writescope strava message
        $this->setProperty('strava', $mockConnectedReadOnly, $this->mainPage);
        $mockConnectedReadOnly->expects($this->any())->method('authenticationUrl')
            ->with('HERE', 'auto', 'write', 'write')->willReturn("URL");
        $this->assertEquals(include('data/expected/connectionsStravaWrite.php'), $connections->invokeArgs($this->mainPage, array()));
        // not connected to strava, should be
        $this->setProperty('strava', $mockNotConnected, $this->mainPage);
        $map = array(
            array('HERE', 'auto', 'write', 'write', 'Write Scope URL')
        , array('HERE', 'auto', null, 'read_only', 'Read Scope URL'),
        );
        $mockNotConnected->method('authenticationUrl')
            ->will($this->returnValueMap($map));
        $this->assertEquals(include('data/expected/connectionsStravaBoth.php'), $connections->invokeArgs($this->mainPage, array()));
        // all connected except MCL
        $this->setProperty('strava', $mockConnectedWriteScope, $this->mainPage);
        $this->setProperty('myCyclingLog', $mockNotConnected, $this->mainPage);
        $this->assertEquals(include('data/expected/connectionsMclOnly.php'), $connections->invokeArgs($this->mainPage, array()));
        // all connected except endomondo
        $this->setProperty('myCyclingLog', $mockConnectedReadOnly, $this->mainPage);
        $this->setProperty('endomondo', $mockNotConnected, $this->mainPage);
        $this->assertEquals(include('data/expected/connectionsEndoOnly.php'), $connections->invokeArgs($this->mainPage, array()));
        // all connected except rideWithGPS
        $this->setProperty('endomondo', $mockConnectedReadOnly, $this->mainPage);
        $this->setProperty('rideWithGps', $mockNotConnected, $this->mainPage);
        $this->assertEquals(include('data/expected/connectionsRwgpsOnly.php'), $connections->invokeArgs($this->mainPage, array()));

        $this->setProperty('strava', $mockNotConnected, $this->mainPage);
        $this->setProperty('myCyclingLog', $mockNotConnected, $this->mainPage);
        $this->setProperty('endomondo', $mockNotConnected, $this->mainPage);
        $this->setProperty('rideWithGps', $mockNotConnected, $this->mainPage);
        $this->assertEquals(include('data/expected/connectionsAll.php'), $connections->invokeArgs($this->mainPage, array()));


    }


    public function testProcessUploadedGpxFiles()
    {
        $scratchDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'scratchDirectory';
        $sourceDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'sourceDirectory';
        $this->cleanDirectory($scratchDirectory);
        $this->cleanDirectory($sourceDirectory);
        $sourceFile = $sourceDirectory . DIRECTORY_SEPARATOR . "20121116_085500.gpx";
        $gpxFile = __DIR__ . DIRECTORY_SEPARATOR . 'data/input/20121116_085500.gpx';
        copy($gpxFile, $sourceFile);

        $_FILES = array(
            'gpx' => array(
                'name' =>
                    array(
                        0 => '20121116_085500.gpx',
                    ),
                'type' =>
                    array(
                        0 => 'application/octet-stream',
                    ),
                'tmp_name' =>
                    array(
                        0 => $sourceFile,
                    ),
                'error' =>
                    array(
                        0 => 0,
                    ),
                'size' =>
                    array(
                        0 => 1114,
                    ),
            ),
        );
        $user_id = 9999;

        $processUploadedGpxFiles = $this->getMethod('processUploadedGpxFiles');
        $this->assertEquals("20121116_085500.gpx: uploaded successfully.<br>",
            $processUploadedGpxFiles->invokeArgs($this->mainPage, array($user_id, $scratchDirectory)));
        $this->assertEquals(1, $this->countFiles($scratchDirectory));
        $createdFile = $scratchDirectory . DIRECTORY_SEPARATOR . $user_id . "-2016-02-05T18_27_24Z.gpx";
        $this->assertTrue(file_exists($createdFile));
        $this->assertEquals(file_get_contents($gpxFile), file_get_contents($createdFile));
        $this->cleanDirectory($scratchDirectory);
        $this->cleanDirectory($sourceDirectory);

    }

    private function countFiles($dir)
    {
        $count = 0;
        foreach (scandir($dir) as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (!is_dir($path)) {
                $count++;
            }
        }
        return $count;
    }


    public function testNotes()
    {
        $notes = $this->getMethod('notes');
        $str = $notes->invokeArgs($this->mainPage, array("REVISION NUMBER"));
        $doc = new DOMDocument();
        $doc->loadXML($str);
        $this->assertEquals(include('data/expected/notesform.php'), $str);
    }

    public function testCalculateEddington()
    {
        $calculateEddington = $this->getMethod('calculateEddington');
        $days = array(
            '2016-01-10' => 98670.699999999997,
            '2016-01-30' => 66378.800000000003,
            '2016-01-28' => 62564,
            '2016-01-23' => 50673.800000000003,
            '2016-01-01' => 26148.199999999997,
            '2016-02-02' => 20871,
            '2016-01-17' => 16211.700000000001,
            '2016-01-15' => 14513.9,
            '2016-01-06' => 7581.6000000000004,
            '2016-01-21' => 6479.6000000000004,
            '2016-01-08' => 5894.5,
            '2016-01-13' => 4391.3999999999996,
        );
        $result = null;
        $expected = array(
            '2016-01-10' => 61.0,
            '2016-01-30' => 41.0,
            '2016-01-28' => 39.0,
            '2016-01-23' => 31.0,
            '2016-01-01' => 16.0,
            '2016-02-02' => 13.0,
            '2016-01-17' => 10.0,
            '2016-01-15' => 9.0
        );
        $this->assertEquals(8, $calculateEddington->invokeArgs($this->mainPage, array($days, &$result, 0.00062137119224)));
        $this->assertEquals($expected, $result);
    }

    public function testBuildChart()
    {
        $buildChart = $this->getMethod('buildChart');


        $imperial_history = array(
            '2016-01-01' => 1,
            '2016-01-06' => 2,
            '2016-01-08' => 3,
            '2016-01-10' => 4,
            '2016-01-17' => 5,
            '2016-01-28' => 6,
            '2016-01-30' => 7,
            '2016-02-02' => 8,
        );
        $metric_history = array(
            '2016-01-01' => 1,
            '2016-01-06' => 2,
            '2016-01-08' => 3,
            '2016-01-10' => 4,
            '2016-01-15' => 5,
            '2016-01-17' => 6,
            '2016-01-28' => 7,
            '2016-01-30' => 8,
        );

        $this->assertEquals(include('data/expected/chart.php'), $buildChart->invokeArgs($this->mainPage, array($imperial_history, $metric_history)));
    }

    public function testDateButtons()
    {
        $twentyFourHours = 60 * 60 * 24;
        $dateButtons = $this->getMethod('dateButtons');

        $timezones = array("UTC", "Europe/Belfast", "America/North_Dakota/Beulah", "Australia/Melbourne");
        foreach ($timezones as $timezone) {
            date_default_timezone_set($timezone);
            $today = date("d-m-Y", time());
            $yesterday = date("d-m-Y", time() - $twentyFourHours);
            $sevendays = date("d-m-Y", time() - (7 * $twentyFourHours));
            $startOfMonth = date("01-m-Y", time());
            $startOfYear = date("01-01-Y", time());
            $lastYear = intval(date("Y", time())) - 1;
            $beginningOfLastYear = "01-01-$lastYear";
            $endOfLastYear = "31-12-$lastYear";
            $this->assertEquals(include('data/expected/dateButtons.php'), $dateButtons->invokeArgs($this->mainPage, array($timezone)));
        }
    }

    public function testDatePicker()
    {
        $twentyFourHours = 60 * 60 * 24;
        $datePicker = $this->getMethod('datePicker');
        $this->assertEquals(include('data/expected/datePicker.php'), $datePicker->invokeArgs($this->mainPage, array("TIME ZONE")));
    }

    public function testExtractStravaIds()
    {
        $extractStravaIds = $this->getMethod('extractStravaIds');;
        $this->assertEquals(include('data/expected/extractStravaIds.php'), $extractStravaIds->invokeArgs($this->mainPage, array(include("data/input/mclRides.php"))));
    }

    public function testSetup()
    {
        $setup = $this->getMethod('setup');
        include('data/input/server.php');
        $_POST = array('calculate_from_strava' => 'Eddington Number from Strava');

        $mockTracker = $this->getMockBuilder('trackerAbstract')
            ->setMethods(array('getUserId','getRides','getError','getOvernightActivities',
                'setUseFeetForElevation','setSplitOvernightRides','setWriteScope','setAuth','setAccessToken',
                'isConnected'))->getMock();
        $mockTracker->expects($this->any())->method('isConnected')->willReturn(true);

        $this->setProperty('preferences', $this->mockPreferences, $this->mainPage);
        $this->setProperty('strava', $mockTracker, $this->mainPage);
        $this->setProperty('myCyclingLog', $mockTracker, $this->mainPage);
        $this->setProperty('endomondo', $mockTracker, $this->mainPage);
        $this->setProperty('rideWithGps', $mockTracker, $this->mainPage);

        $this->assertEquals("calculate_from_strava", $setup->invokeArgs($this->mainPage, array()));
    }


    public function testIsDuplicateRide()
    {
        $isDuplicateRide = $this->getMethod('isDuplicateRide');

        // ride with an ID in one of the stored rides
        $ride = array(
            'distance' => 8359.2338562011719,
            'elapsed_time' => 5736,
            'max_speed' => 8.4078611111111101,
            'endo_id' => 669846213,
            'ascent' => 22,
            'start_time' => '2016-02-09 23:25:09 UTC',
            'name' => '',
        );
        $this->assertEquals(490216193, $isDuplicateRide->invokeArgs($this->mainPage, array($ride, include("data/input/duplicateCandidateRides.php"))));

        // not taking place on a day with other rides.
        $ride = array(
            'distance' => 1000,
            'elapsed_time' => 5736,
            'max_speed' => 8.4078611111111101,
            'endo_id' => 11111111,
            'ascent' => 22,
            'start_time' => '2016-02-10 23:25:09 UTC',
            'name' => '',
        );
        $this->assertFalse($isDuplicateRide->invokeArgs($this->mainPage, array($ride, include("data/input/duplicateCandidateRides.php"))));

        // ride takes place completely within another ride

        $ride = array(
            'distance' => 1000,
            'elapsed_time' => 1000,
            'max_speed' => 8.4078611111111101,
            'endo_id' => 11111111,
            'ascent' => 22,
            'start_time' => '2016-02-09T23:25:09 UTC',
            'name' => '',
        );
        $this->assertTrue($isDuplicateRide->invokeArgs($this->mainPage, array($ride, include("data/input/duplicateCandidateRides.php"))));

       // starts before a ride, and finishes after it finishes.
        $ride = array(
            'distance' => 1000,
            'elapsed_time' => 4000,
            'max_speed' => 8.4078611111111101,
            'endo_id' => 11111111,
            'ascent' => 22,
            'start_time' => '2016-02-09T23:25:00 UTC',
            'name' => '',
        );
        $this->assertTrue($isDuplicateRide->invokeArgs($this->mainPage, array($ride, include("data/input/duplicateCandidateRides.php"))));

       // starts before a ride, and finishes during it.
        $ride = array(
            'distance' => 1000,
            'elapsed_time' => 1000,
            'max_speed' => 8.4078611111111101,
            'endo_id' => 11111111,
            'ascent' => 22,
            'start_time' => '2016-02-09T23:25:00 UTC',
            'name' => '',
        );
        $this->assertTrue($isDuplicateRide->invokeArgs($this->mainPage, array($ride, include("data/input/duplicateCandidateRides.php"))));


       // starts after a ride starts, and finishes during it.
        $ride = array(
            'distance' => 1000,
            'elapsed_time' => 1000,
            'max_speed' => 8.4078611111111101,
            'endo_id' => 11111111,
            'ascent' => 22,
            'start_time' => '2016-02-09T23:25:20 UTC',
            'name' => '',
        );
        $this->assertTrue($isDuplicateRide->invokeArgs($this->mainPage, array($ride, include("data/input/duplicateCandidateRides.php"))));


      // Identical with existing ride, but don't share IDs
        $ride = array(
            'distance' => 1635.8,
            'elapsed_time' => 3014,
            'max_speed' => 10.6,
            'endo_id' => null,
            'ascent' => 0,
            'start_time' => '2016-02-07T22:02:08Z',
            'name' => 'Night Ride',
        );

        $this->assertTrue($isDuplicateRide->invokeArgs($this->mainPage, array($ride, include("data/input/duplicateCandidateRides.php"))));

        // starts after a ride starts, and finishes after it finishes it.
        $ride = array(
            'distance' => 1000,
            'elapsed_time' => 841,
            'max_speed' => 8.4078611111111101,
            'endo_id' => 11111111,
            'ascent' => 22,
            'start_time' => '2016-02-08T15:40:51 UTC',
            'name' => '',
        );
        $this->assertTrue($isDuplicateRide->invokeArgs($this->mainPage, array($ride, include("data/input/duplicateCandidateRides.php"))));


        //finishes 1 second before another ride starts
        $ride = array(
            'distance' => 1000,
            'elapsed_time' => 841,
            'max_speed' => 8.4078611111111101,
            'endo_id' => 11111111,
            'ascent' => 22,
            'start_time' => '2016-02-08T15:26:49 UTC',
            'name' => '',
        );
        $this->assertFalse($isDuplicateRide->invokeArgs($this->mainPage, array($ride, include("data/input/duplicateCandidateRides.php"))));

        //the above, starting 2 seconds later
        $ride['start_time']='2016-02-08T15:26:51 UTC';
        $this->assertTrue($isDuplicateRide->invokeArgs($this->mainPage, array($ride, include("data/input/duplicateCandidateRides.php"))));


    }

    public function testCompareDistance()
    {
        $compareDistance = $this->getMethod('compareDistance');
        $this->assertEquals(0, $compareDistance->invokeArgs($this->mainPage, array(0, 0)));
        $this->assertEquals(0, $compareDistance->invokeArgs($this->mainPage, array(100, 100)));
        $this->assertEquals(0, $compareDistance->invokeArgs($this->mainPage, array(102, 100)));
        $this->assertEquals(0, $compareDistance->invokeArgs($this->mainPage, array(100, 102)));
        $this->assertEquals(-1, $compareDistance->invokeArgs($this->mainPage, array(0, 100)));
        $this->assertEquals(1, $compareDistance->invokeArgs($this->mainPage, array(100, 0)));
        $this->assertEquals(-1, $compareDistance->invokeArgs($this->mainPage, array(10, 100)));
        $this->assertEquals(1, $compareDistance->invokeArgs($this->mainPage, array(100, 10)));
        $this->assertEquals(-1, $compareDistance->invokeArgs($this->mainPage, array(100, 103)));
        $this->assertEquals(1, $compareDistance->invokeArgs($this->mainPage, array(103, 100)));

        //$margin parameter
        $this->assertEquals(0, $compareDistance->invokeArgs($this->mainPage, array(103, 100, 0.1)));

        $this->assertEquals(0, $compareDistance->invokeArgs($this->mainPage, array(100, 90, 0.1)));
        $this->assertEquals(1, $compareDistance->invokeArgs($this->mainPage, array(100, 89, 0.1)));
        $this->assertEquals(0, $compareDistance->invokeArgs($this->mainPage, array(100, 110, 0.1)));
        $this->assertEquals(-1, $compareDistance->invokeArgs($this->mainPage, array(100, 111, 0.1)));
    }

    public function testTopOfPage()
    {
        $topOfPage = $this->getMethod('topOfPage');
        $this->assertEquals(include('data/expected/topOfPage.php'), $topOfPage->invokeArgs($this->mainPage, array()));

    }

    public function testBottomOfPage()
    {
        $bottomOfPage = $this->getMethod('bottomOfPage');
        $this->assertEquals('</body></html>', $bottomOfPage->invokeArgs($this->mainPage, array()));

    }

    public function testMclDeleteButton()
    {
        $mclDeleteButton = $this->getMethod('mclDeleteButton');
        $this->assertEquals(include('data/expected/mclDeleteNoUsername.php'), $mclDeleteButton->invokeArgs($this->mainPage, array(null)));
        $this->assertEquals(include('data/expected/mclDeleteUsername.php'), $mclDeleteButton->invokeArgs($this->mainPage, array("joan")));
    }

    public function testExecute()
    {

        // todo
        $execute = $this->getMethod('execute');
        $setup = $this->getMethod('setup');

        $mockTracker = $this->getMockBuilder('trackerAbstract')
            ->setMethods(array('getUserId','getRides','getError','getOvernightActivities',
                'setUseFeetForElevation','setSplitOvernightRides','setWriteScope','setAuth','setAccessToken'))->getMock();
        $rides=include('data/input/getRides.php');
        $mockTracker->expects($this->any())->method('getRides')->willReturn($rides);
        $this->setProperty('preferences', $this->mockPreferences, $this->mainPage);
        $this->setProperty('strava', $mockTracker, $this->mainPage);
        $this->setProperty('myCyclingLog', $mockTracker, $this->mainPage);
        $this->setProperty('endomondo', $mockTracker, $this->mainPage);
        $this->setProperty('rideWithGps', $mockTracker, $this->mainPage);
        $elapsed_days = round((time() - strtotime("2016-01-01T00:00:00Z")) / (60 * 60 * 24));
        $start_date='the beginning';
        $setup->invokeArgs($this->mainPage, array());  // need to call this with each case, because start_time/end_time variables change.  They probably shouldn't
        $source='Strava';
        $this->assertEquals(include('data/expected/calculateFromSourceNoEndDate.php'), $execute->invokeArgs($this->mainPage, array("calculate_from_strava")));
        $setup->invokeArgs($this->mainPage, array());
        $source='MyCyclingLog';
        $this->assertEquals(include('data/expected/calculateFromSourceNoEndDate.php'), $execute->invokeArgs($this->mainPage, array("calculate_from_mcl")));
        $setup->invokeArgs($this->mainPage, array());
        $source='Endomondo';
        $this->assertEquals(include('data/expected/calculateFromSourceNoEndDate.php'), $execute->invokeArgs($this->mainPage, array("calculate_from_endo")));
        $setup->invokeArgs($this->mainPage, array());
        $source='RideWithGPS';
        $this->assertEquals(include('data/expected/calculateFromSourceNoEndDate.php'), $execute->invokeArgs($this->mainPage, array("calculate_from_rwgps")));
        $_POST = array(
            'start_date' => '01-01-2015',
            'end_date' => null,
        );
        $start_date='01-01-2015';
        $elapsed_days = round((time() - strtotime("2015-01-01T00:00:00Z")) / (60 * 60 * 24));
        $setup->invokeArgs($this->mainPage, array());
        $source='RideWithGPS';
        $this->assertEquals(include('data/expected/calculateFromSourceNoEndDate.php'), $execute->invokeArgs($this->mainPage, array("calculate_from_rwgps")));
        $_POST = array(
            'start_date' => '01-01-2015',
            'end_date' => '31-12-2015',
        );
        $start_date='01-01-2015';
        $end_date='31-12-2015';
        $elapsed_days = 365;
        $setup->invokeArgs($this->mainPage, array());
        $source='RideWithGPS';
        $this->assertEquals(include('data/expected/calculateFromSourceWithEndDate.php'), $execute->invokeArgs($this->mainPage, array("calculate_from_rwgps")));
        $_POST = array(
            'end_date' => '10-02-2016'
        );
        $start_date='the beginning';
        $end_date='10-02-2016';
        $elapsed_days = 41;
        $setup->invokeArgs($this->mainPage, array());
        $source='RideWithGPS';
        $this->assertEquals(include('data/expected/calculateFromSourceWithEndDate.php'), $execute->invokeArgs($this->mainPage, array("calculate_from_rwgps")));
    }

    public function testEddingtonHistory()
    {
        $eddingtonHistory = $this->getMethod('eddingtonHistory');
        $this->assertEquals(include('data/expected/history.php'),
            $eddingtonHistory->invokeArgs($this->mainPage, array(include('data/input/history.php'), 0.00062137119223999997)));
    }

    public function testMainForm()
    {
        $mockTracker = $this->getMockBuilder('trackerAbstract')
            ->setMethods(array('getUserId','getRides','getError','getOvernightActivities',
                'setUseFeetForElevation','setSplitOvernightRides','setWriteScope','setAuth','setAccessToken',
                'isConnected', 'writeScope'))->getMock();
        $mockTracker->expects($this->any())->method('isConnected')->willReturn(true);
        $mockTracker->expects($this->any())->method('writeScope')->willReturn(true);
        $mainForm = $this->getMethod('mainForm');
        $this->setProperty('preferences', $this->mockPreferences, $this->mainPage);
        $this->setProperty('strava', $mockTracker, $this->mainPage);
        $this->setProperty('myCyclingLog', $mockTracker, $this->mainPage);
        $this->setProperty('endomondo', $mockTracker, $this->mainPage);
        $this->setProperty('rideWithGps', $mockTracker, $this->mainPage);

        $today = date("d-m-Y", time());
        $twentyFourHours = 60 * 60 * 24;

        $yesterday = date("d-m-Y", time() - $twentyFourHours);
        $sevendays = date("d-m-Y", time() - (7 * $twentyFourHours));
        $startOfMonth = date("01-m-Y", time());
        $startOfYear = date("01-01-Y", time());
        $lastYear = intval(date("Y", time())) - 1;
        $beginningOfLastYear = "01-01-$lastYear";
        $endOfLastYear = "31-12-$lastYear";

        $this->assertEquals(include('data/expected/mainForm.php'), $mainForm->invokeArgs($this->mainPage, array()));

    }
}

