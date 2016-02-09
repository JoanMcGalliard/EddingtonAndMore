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
        $this->mainPage=new MainPage(array($this, 'myEcho'));
    }


    public function testEmail() {
        $email = self::getMethod('email');
        $this->assertEquals(include('data/expected/emailform.php'), $email->invokeArgs($this->mainPage, array()));

    }
    public function testTopOfPage() {
        $topOfPage = self::getMethod('topOfPage');
        $this->assertEquals(include('data/expected/topOfPageform.php'), $topOfPage->invokeArgs($this->mainPage, array()));

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
    public function output($msg)
    private function setup()
    private function execute($state)
    private function displayPage()
    private function topOfPage()
    private function notes()
    private function dateButtons($timezone)
    private function datePicker($timezone)
    private function mclDeleteButton($username)
    private function mainForm()
    private function connections()
    private function sumActivities($activities)
    private function sumDay($rides)
    private function next_goals($x)
    private function number_of_days_to_goal($goal, $days, $factor)
    private function isDuplicateRide($endo_ride, $rides, $id_key)
    private function extractStravaIds($mcl_rides)
    private function compareDistance($distance1, $distance2)
    private function calculateEddington($days, &$eddington_days, $factor)
    private function eddingtonHistory($days, $factor)
    private function buildChart($imperial_history, $metric_history)
    private function askForStravaGpx($overnight_rides, $maxKmFileUploads, $state, $message)
    private function processUploadedGpxFiles($userId, $scratchDirectory)
    private function bottomOfPage()

     */

}