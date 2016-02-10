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

//        $setup = self::getMethod('setup');
//        $setup->invokeArgs($this->mainPage, array());
        date_default_timezone_set("UTC");

    }


    public function testEmail()
    {
        $email = self::getMethod('email');
        $this->assertEquals(include('data/expected/emailform.php'), $email->invokeArgs($this->mainPage, array()));
    }
    public function testSumActivities()
    {
        $sumActivities = self::getMethod('sumActivities');
        $this->assertEquals(include('data/expected/sumActivities.php'), $sumActivities->invokeArgs($this->mainPage, array(include('data/input/sumActivities.php'))));
    }
    public function testProcessUploadedGpxFiles()
    {
        $processUploadedGpxFiles = self::getMethod('processUploadedGpxFiles');
//        $this->assertEquals(include('data/expected/processUploadedGpxFiles.php'), $processUploadedGpxFiles->invokeArgs($this->mainPage, array(include('data/input/processUploadedGpxFiles.php'))));
    }

    public function testNotes()
    {
        $notes = self::getMethod('notes');
        $str = $notes->invokeArgs($this->mainPage, array("REVISION NUMBER"));
        $doc = new DOMDocument();
        $doc->loadXML($str);
        $this->assertEquals(include('data/expected/notesform.php'), $str);
    }

    public function testCalculateEddington()
    {
        $calculateEddington = self::getMethod('calculateEddington');
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
        $buildChart = self::getMethod('buildChart');


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
            $dateButtons = self::getMethod('dateButtons');
            $this->assertEquals(include('data/expected/dateButtons.php'), $dateButtons->invokeArgs($this->mainPage, array($timezone)));
        }
    }
    public function testExtractStravaIds()
    {
        $extractStravaIds = self::getMethod('extractStravaIds');
        ;
        $this->assertEquals(include('data/expected/extractStravaIds.php'), $extractStravaIds->invokeArgs($this->mainPage, array(include("data/input/mclRides.php"))));
        $timezones = array("UTC", "Europe/Belfast", "America/North_Dakota/Beulah", "Australia/Melbourne");
    }

    public function testCompareDistance()
    {
        $compareDistance = self::getMethod('compareDistance');
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
        $topOfPage = self::getMethod('topOfPage');
        $this->assertEquals(include('data/expected/topOfPage.php'), $topOfPage->invokeArgs($this->mainPage, array()));

    }

    public function testBottomOfPage()
    {
        $bottomOfPage = self::getMethod('bottomOfPage');
        $this->assertEquals('</body></html>', $bottomOfPage->invokeArgs($this->mainPage, array()));

    }

    protected static function getMethod($name)
    {
        $class = new ReflectionClass('MainPage');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    /*
     *     public function __construct($echoCallback)
    public function render()
    private function setup()
    private function execute($state)
    private function dateButtons($timezone)
    private function datePicker($timezone)
    private function mclDeleteButton($username)
    private function mainForm()
    private function connections()
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