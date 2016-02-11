<?php

require_once "JoanMcGalliard/EddingtonAndMore/BaseTestClass.php";
require_once "MainPage.php";

class MainPageTest extends JoanMcGalliard\EddingtonAndMore\BaseTestClass
{
    private $mainPage;
    protected $classUnderTest = 'MainPage';

    /**
     * MainPageTest constructor.
     */
    public function __construct()
    {
        $this->mainPage = new MainPage(array($this, 'myEcho'));

//        $setup = $this->getMethod('setup');
//        $setup->invokeArgs($this->mainPage, array());
        date_default_timezone_set("UTC");

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
        $this->assertEquals(29780.2, $sumDay->invokeArgs($this->mainPage, array(include('data/input/rides.php'))));
        $this->assertEquals(7122.6, $sumDay->invokeArgs($this->mainPage, array(array(array('distance' => 7122.6)))));
        $this->assertEquals(0, $sumDay->invokeArgs($this->mainPage, array(array(array('distance' => 0)))));
        $this->assertEquals(0, $sumDay->invokeArgs($this->mainPage, array(array())));


    }

    public function testConnections()
    {
        $connections = $this->getMethod('connections');

        $this->setProperty('here', "HERE", $this->mainPage);

        $this->setProperty('strava', new MockTracker(true, true), $this->mainPage);
        $this->setProperty('myCyclingLog', new MockTracker(true), $this->mainPage);
        $this->setProperty('endomondo', new MockTracker(true), $this->mainPage);
        $this->setProperty('rideWithGps', new MockTracker(true), $this->mainPage);
        // already connected to all services, there should be no connections section at all.
        $this->assertEquals('', $connections->invokeArgs($this->mainPage, array()));
        // not write connected to strava, there should be the writescope strava message
        $this->setProperty('strava', new MockTracker(true, false, "URL"), $this->mainPage);
        $this->assertEquals(include('data/expected/connectionsStravaWrite.php'), $connections->invokeArgs($this->mainPage, array()));
        // not connected to strava, should be
        $this->setProperty('strava', new MockTracker(false), $this->mainPage);
        $this->assertEquals(include('data/expected/connectionsStravaBoth.php'), $connections->invokeArgs($this->mainPage, array()));
        // all connected except MCL
        $this->setProperty('strava', new MockTracker(true, true), $this->mainPage);
        $this->setProperty('myCyclingLog', new MockTracker(false), $this->mainPage);
        $this->assertEquals(include('data/expected/connectionsMclOnly.php'), $connections->invokeArgs($this->mainPage, array()));
        // all connected except endomondo
        $this->setProperty('myCyclingLog', new MockTracker(true), $this->mainPage);
        $this->setProperty('endomondo', new MockTracker(false), $this->mainPage);
        $this->assertEquals(include('data/expected/connectionsEndoOnly.php'), $connections->invokeArgs($this->mainPage, array()));
        // all connected except rideWithGPS
        $this->setProperty('endomondo', new MockTracker(true), $this->mainPage);
        $this->setProperty('rideWithGps', new MockTracker(false), $this->mainPage);
        $this->assertEquals(include('data/expected/connectionsRwgpsOnly.php'), $connections->invokeArgs($this->mainPage, array()));

        $this->setProperty('strava', new MockTracker(false), $this->mainPage);
        $this->setProperty('myCyclingLog', new MockTracker(false), $this->mainPage);
        $this->setProperty('endomondo', new MockTracker(false), $this->mainPage);
        $this->setProperty('rideWithGps', new MockTracker(false), $this->mainPage);
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

    private function cleanDirectory($dir)
    {
        if (!file_exists($dir)) {
            mkdir($dir);
        }
        foreach (scandir($dir) as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (!is_dir($path)) {
                unlink($path);
            }
        }

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
        $_POST=array ('calculate_from_strava' => 'Eddington Number from Strava');

        $this->setProperty('preferences', new MockPreferences(), $this->mainPage);
        $this->setProperty('strava', new MockTracker(true, true), $this->mainPage);
        $this->setProperty('myCyclingLog', new MockTracker(true), $this->mainPage);
        $this->setProperty('endomondo', new MockTracker(true), $this->mainPage);
        $this->setProperty('rideWithGps', new MockTracker(true), $this->mainPage);

        $this->assertEquals("calculate_from_strava", $setup->invokeArgs($this->mainPage, array()));
    }

    public function testRender()
    {
        //todo

//        $mock=$csc = $this->getMockBuilder('MainPage')
//            ->setConstructorArgs(array($this, 'myEcho'))
//            ->setMethods(array('setup','execute','render'))
//            ->getMock();
//        $mock->method('setup')->willReturn("setup");
//        $mock->method('execute')->willReturn("execute");
//        $mock->method('render')->willReturn("render");
//        $this->output="";
//        /** @var MainPage $mock */
//        $mock->render();
//        $this->assertEquals("string", $this->output);
    }

    public function testIsDuplicateRide()
    {
        //todo !!!
        $isDuplicateRide = $this->getMethod('isDuplicateRide');

        $ride = array(
            'distance' => 8359.2338562011719,
            'elapsed_time' => 5736,
            'max_speed' => 8.4078611111111101,
            'endo_id' => 669846213,
            'ascent' => 22,
            'start_time' => '2016-02-09 23:25:09 UTC',
            'name' => '',
        );
        $this->assertEquals(490216193, $isDuplicateRide->invokeArgs($this->mainPage, array($ride, include("data/input/endoRides.php"), "strava_id")));
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

        $this->setProperty('preferences', new MockPreferences(), $this->mainPage);
        $this->setProperty('strava', new MockTracker(true, true), $this->mainPage);
        $this->setProperty('myCyclingLog', new MockTracker(true), $this->mainPage);
        $this->setProperty('endomondo', new MockTracker(true), $this->mainPage);
        $this->setProperty('rideWithGps', new MockTracker(true), $this->mainPage);


        $this->assertEquals(include('data/expected/calculateFromStrava.php'), $execute->invokeArgs($this->mainPage, array("calculate_from_strava")));
    }
    public function testEddingtonHistory()
    {
        $eddingtonHistory = $this->getMethod('eddingtonHistory');
        $this->assertEquals(include('data/expected/history.php'),
            $eddingtonHistory->invokeArgs($this->mainPage, array(include('data/input/history.php'), 0.00062137119223999997)));
    }

    public function testMainForm()
    {
        $mainForm = $this->getMethod('mainForm');
        $this->setProperty('preferences', new MockPreferences(), $this->mainPage);
        $this->setProperty('strava', new MockTracker(true, true), $this->mainPage);
        $this->setProperty('myCyclingLog', new MockTracker(true), $this->mainPage);
        $this->setProperty('endomondo', new MockTracker(true), $this->mainPage);
        $this->setProperty('rideWithGps', new MockTracker(true), $this->mainPage);

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

class MockPreferences {
    public function getTimezone() {return "UTC";}
    public function getStravaSplitRides() {return false;}
    public function getEndoSplitRides() {return false;}
    public function getRwgpsSplitRides() {return false;}
    public function getMclUseFeet() {return false;}
    public function getMclUsername() {return "helen";}
    public function getStravaWriteScope() {return true;}
    public function getMclAuth() {return "auth";}
    public function getEndoAuth() {return "auth";}
    public function getRwgpsAuth() {return "auth";}
    public function getStravaAccessToken() {return "token";}
    public function setStravaSplitRides($x) {}
}
class MockTracker
{

    private $connected;
    private $writeScope;

    /**
     * MockTracker constructor.
     */
    public function __construct($connected, $writeScope = false)
    {
        $this->connected = $connected;
        $this->writeScope = $writeScope;
    }

    public function isConnected()
    {
        return $this->connected;
    }

    public function writeScope()
    {
        return $this->writeScope;
    }

    public function authenticationUrl($redirect, $approvalPrompt, $scope, $state)
    {
        return "$redirect-$approvalPrompt-$scope-$state";
    }

    public function getUserId() {
        return 4444;
    }

    public function getRides()
    {
        return array(
            '2016-02-09' =>
                array(
                    0 =>
                        array(
                            'distance' => 2806.9000000000001,
                            'name' => 'Lunch Ride',
                            'strava_id' => 490216220,
                            'start_time' => '2016-02-09T11:58:11Z',
                            'bike' => 'b267883',
                            'moving_time' => 738,
                            'elapsed_time' => 738,
                            'total_elevation_gain' => 0,
                            'max_speed' => 8.1999999999999993,
                            'timezone' => 'Europe/London',
                            'endo_id' => 669521476,
                        ),
                    1 =>
                        array(
                            'distance' => 10953.799999999999,
                            'name' => 'Evening Ride',
                            'strava_id' => 490216213,
                            'start_time' => '2016-02-09T19:10:21Z',
                            'bike' => 'b267883',
                            'moving_time' => 2935,
                            'elapsed_time' => 3812,
                            'total_elevation_gain' => 11.6,
                            'max_speed' => 6.7999999999999998,
                            'timezone' => 'Europe/London',
                            'endo_id' => 669758003,
                        ),
                    2 =>
                        array(
                            'distance' => 8268.5,
                            'name' => 'Night Ride',
                            'strava_id' => 490216193,
                            'start_time' => '2016-02-09T23:25:09Z',
                            'bike' => 'b267883',
                            'moving_time' => 1552,
                            'elapsed_time' => 1552,
                            'total_elevation_gain' => 0,
                            'max_speed' => 8.5,
                            'timezone' => 'Europe/London',
                            'endo_id' => 669846213,
                        ),
                ),
        );
    }
    public function getOvernightActivities() {return [];}
    public function getError() {return "";}
    public function setUseFeetForElevation($x) {}
    public function setSplitOvernightRides($x) {}
    public function setWriteScope($x) {}
    public function setAuth($x) {}
    public function setAccessToken($x) {}
}
