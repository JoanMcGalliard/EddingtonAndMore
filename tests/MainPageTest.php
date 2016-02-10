<?php

require_once "JoanMcGalliard/EddingtonAndMore/BaseTestClass.php";
require_once "MainPage.php";

class MainPageTest extends JoanMcGalliard\EddingtonAndMore\BaseTestClass
{
    private $mainPage;

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
    public function testConnections()
    {
        $connections = $this->getMethod('connections');

        $this->setProperty('here', "HERE",$this->mainPage);

        $this->setProperty('strava', new MockTracker(true,true,"URL"),$this->mainPage);
        $this->setProperty('myCyclingLog', new MockTracker(true),$this->mainPage);
        $this->setProperty('endomondo', new MockTracker(true),$this->mainPage);
        $this->setProperty('rideWithGps', new MockTracker(true),$this->mainPage);
        // already connected to all services, there should be no connections section at all.
        $this->assertEquals('', $connections->invokeArgs($this->mainPage, array()));
        // not write connected to strava, there should be the writescope strava message
        $this->setProperty('strava', new MockTracker(true,false,"URL"),$this->mainPage);
        $this->assertEquals(include('data/expected/connectionsStravaWrite.php'), $connections->invokeArgs($this->mainPage, array()));
        // not connected to strava, should be
        $this->setProperty('strava', new MockTracker(false),$this->mainPage);
        $this->assertEquals(include('data/expected/connectionsStravaBoth.php'), $connections->invokeArgs($this->mainPage, array()));
        // all connected except MCL
        $this->setProperty('strava', new MockTracker(true,true),$this->mainPage);
        $this->setProperty('myCyclingLog', new MockTracker(false),$this->mainPage);
        $this->assertEquals(include('data/expected/connectionsMclOnly.php'), $connections->invokeArgs($this->mainPage, array()));
        // all connected except endomondo
        $this->setProperty('myCyclingLog', new MockTracker(true),$this->mainPage);
        $this->setProperty('endomondo', new MockTracker(false),$this->mainPage);
        $this->assertEquals(include('data/expected/connectionsEndoOnly.php'), $connections->invokeArgs($this->mainPage, array()));
        // all connected except rideWithGPS
        $this->setProperty('endomondo', new MockTracker(true),$this->mainPage);
        $this->setProperty('rideWithGps', new MockTracker(false),$this->mainPage);
        $this->assertEquals(include('data/expected/connectionsRwgpsOnly.php'), $connections->invokeArgs($this->mainPage, array()));
    }


    public function testProcessUploadedGpxFiles()
    {
        $scratchDirectory = __DIR__.DIRECTORY_SEPARATOR.'scratchDirectory';
        $sourceDirectory = __DIR__.DIRECTORY_SEPARATOR.'sourceDirectory';
        $this->cleanDirectory($scratchDirectory);
        $this->cleanDirectory($sourceDirectory);
        $sourceFile = $sourceDirectory . DIRECTORY_SEPARATOR . "20121116_085500.gpx";
        $gpxFile = __DIR__.DIRECTORY_SEPARATOR.'data/input/20121116_085500.gpx';
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
        $user_id=9999;

        $processUploadedGpxFiles = $this->getMethod('processUploadedGpxFiles');
        $this->assertEquals("20121116_085500.gpx: uploaded successfully.<br>",
            $processUploadedGpxFiles->invokeArgs($this->mainPage, array($user_id, $scratchDirectory)));
        $this->assertEquals(1, $this->countFiles($scratchDirectory));
        $createdFile=$scratchDirectory.DIRECTORY_SEPARATOR.$user_id."-2016-02-05T18_27_24Z.gpx";
        $this->assertTrue(file_exists($createdFile));
        $this->assertEquals(file_get_contents($gpxFile), file_get_contents($createdFile));
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
        $timezones = array("UTC", "Europe/Belfast", "America/North_Dakota/Beulah", "Australia/Melbourne");
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

    protected static function getMethod($name)
    {
        $class = new ReflectionClass('MainPage');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }
    public function getPrivateProperty( $className, $propertyName ) {
        $reflector = new ReflectionClass( $className );
        $property = $reflector->getProperty( $propertyName );
        $property->setAccessible( true );

        return $property;
    }
    protected static function setProperty($name, $value,$obj)
    {
        $class = new ReflectionClass('MainPage');
        $property = $class->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($obj,$value);
    }

    /*
     *     public function __construct($echoCallback)
    public function render()
    private function setup()
    private function execute($state)
    private function datePicker($timezone)
    private function mclDeleteButton($username)
    private function mainForm()
    private function sumDay($rides)
    private function next_goals($x)
    private function number_of_days_to_goal($goal, $days, $factor)
    private function isDuplicateRide($endo_ride, $rides, $id_key)
    private function ($distance1, $distance2)
    private function eddingtonHistory($days, $factor)
    private function buildChart($imperial_history, $metric_history)
    private function askForStravaGpx($overnight_rides, $maxKmFileUploads, $state, $message)
    private function processUploadedGpxFiles($userId, $scratchDirectory)

     */

}

class MockTracker {

    private $connected;
    private $writeScope;

    /**
     * MockTracker constructor.
     */
    public function __construct($connected,$writeScope=false)
    {
        $this->connected=$connected;
        $this->writeScope=$writeScope;
    }

    public function isConnected ()
    {
        return $this->connected;
    }
    public function writeScope ()
    {
        return $this->writeScope;
    }
    public function authenticationUrl ($redirect, $approvalPrompt, $scope, $state)
    {
        return "$redirect-$approvalPrompt-$scope-$state";
    }

}