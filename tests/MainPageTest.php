<?php

require_once "JoanMcGalliard/EddingtonAndMore/BaseTestClass.php";
require_once 'data/input/server.php';
require_once "MainPage.php";

class MainPageTest extends JoanMcGalliard\EddingtonAndMore\BaseTestClass
{
    private $mainPage;
    protected $classUnderTest = 'MainPage';


    public function setUp()
    {
        parent::setUp();

        $this->mainPage = new MainPage(array($this, 'myEcho'));
    }

    public function testDeleteEndoRidesFromStrava()
    {
        $deleteEndoRidesFromStrava = $this->getMethod('deleteEndoRidesFromStrava');
        $this->setProperty('noEcho', false, $this->mainPage);
        $strava = $this->getMockBuilder('trackerAbstract')
            ->setMethods(array('getUserId', 'getActivityDescription', 'getRides', 'getError', 'getOvernightActivities',
                'setUseFeetForElevation', 'setSplitOvernightRides', 'activityUrl', 'setWriteScope', 'setAuth', 'setAccessToken'))->getMock();
        $endo = $this->getMockBuilder('trackerAbstract')
            ->setMethods(array('getUserId', 'getRides', 'getError', 'getOvernightActivities',
                'setUseFeetForElevation', 'setSplitOvernightRides', 'activityUrl', 'getWorkout', 'setWriteScope', 'setAuth', 'setAccessToken'))->getMock();
        $this->setProperty('strava', $strava, $this->mainPage);
        $this->setProperty('endomondo', $endo, $this->mainPage);

        // ride is from endomondo
        $rides = array('2016-01-01' =>
            array(array('distance' => 17124.799999999999,
                'name' => 'Afternoon Ride', 'strava_id' => 494647884, 'start_time' => '2016-01-01T15:59:17Z',
                'bike' => 'b267883', 'moving_time' => 3800, 'elapsed_time' => 6897, 'max_speed' => 8.9000000000000004,
                'total_elevation_gain' => 114.90000000000001, 'timezone' => 'Europe/London', 'kudos_count' => 0,
                'photo_count' => 0, 'comment_count' => 0, 'description' => 'endo activity URL', 'endo_id' => 99999999)));
        $strava->expects($this->at(0))->method('getRides')->willReturn($rides);
        $endo->expects($this->at(0))->method('activityUrl')->willReturn('endo activity URL');
        $endo->expects($this->at(1))->method('activityUrl')->willReturn('endo activity URL');
        $endo->expects($this->at(2))->method('getWorkout')->willReturn((object)array('distance' => 17125, 'startTime' => 1451663957, 'id' => 9));
        $this->output = "";
        $expected = '<br>As listed above, the following rides seem to have been copied from Endomondo, and can be deleted from Strava (and then re-added, if you choose).<br><ol>
<li><a target="_blank" href="\">494647884</a></li></ol><form action="" method="post" name="delete_strava_rides_form"><input type="submit" name="delete_from_strava" value="Delete these rides from Strava?"/><input type="hidden" name="activity_numbers" value="494647884,"></form>';
        $this->assertEquals($expected, $deleteEndoRidesFromStrava->invokeArgs($this->mainPage, array()));
        $this->assertEquals('<br><a target="_blank" href="\">494647884</a> (<a target="_blank" href="endo activity URL\">99999999</a>) queued for deletion<br>', $this->output);

        // ride has no endo_id
        $rides = array('2016-01-01' =>
            array(array('distance' => 17124.799999999999,
                'name' => 'Afternoon Ride', 'strava_id' => 494647884, 'start_time' => '2016-01-01T15:59:17Z',
                'bike' => 'b267883', 'moving_time' => 3800, 'elapsed_time' => 6897, 'max_speed' => 8.9000000000000004,
                'total_elevation_gain' => 114.90000000000001, 'timezone' => 'Europe/London', 'kudos_count' => 0,
                'photo_count' => 0, 'comment_count' => 0, 'description' => 'endo activity URL', 'endo_id' => null)));
        $strava->expects($this->at(0))->method('getRides')->willReturn($rides);
        $endo->expects($this->at(0))->method('activityUrl')->willReturn('endo activity URL');
        $this->output = "";
        $this->assertEquals('', $deleteEndoRidesFromStrava->invokeArgs($this->mainPage, array()));
        $this->assertEquals('.', $this->output);
        // ride has  endo_id, but the description does not include activity URL
        $rides = array('2016-01-01' =>
            array(array('distance' => 17124.799999999999,
                'name' => 'Afternoon Ride', 'strava_id' => 494647884, 'start_time' => '2016-01-01T15:59:17Z',
                'bike' => 'b267883', 'moving_time' => 3800, 'elapsed_time' => 6897, 'max_speed' => 8.9000000000000004,
                'total_elevation_gain' => 114.90000000000001, 'timezone' => 'Europe/London', 'kudos_count' => 0,
                'photo_count' => 0, 'comment_count' => 0, 'description' => 'random', 'endo_id' => null)));
        $strava->expects($this->at(0))->method('getRides')->willReturn($rides);
        $endo->expects($this->at(0))->method('activityUrl')->willReturn('endo activity URL');
        $this->output = "";
        $this->assertEquals('', $deleteEndoRidesFromStrava->invokeArgs($this->mainPage, array()));
        $this->assertEquals('.', $this->output);

        // ride has  endo_id, needs to get description from strava
        $rides = array('2016-01-01' =>
            array(array('distance' => 17124.799999999999,
                'name' => 'Afternoon Ride', 'strava_id' => 494647884, 'start_time' => '2016-01-01T15:59:17Z',
                'bike' => 'b267883', 'moving_time' => 3800, 'elapsed_time' => 6897, 'max_speed' => 8.9000000000000004,
                'total_elevation_gain' => 114.90000000000001, 'timezone' => 'Europe/London', 'kudos_count' => 0,
                'photo_count' => 0, 'comment_count' => 0, 'endo_id' => 99999999)));
        $strava->expects($this->at(0))->method('getRides')->willReturn($rides);
        $strava->expects($this->at(2))->method('getActivityDescription')->willReturn('endo activity URL');
        $endo->expects($this->at(0))->method('activityUrl')->willReturn('endo activity URL');
        $endo->expects($this->at(1))->method('activityUrl')->willReturn('endo activity URL');
        $endo->expects($this->at(2))->method('getWorkout')->willReturn((object)array('distance' => 17125, 'startTime' => 1451663957, 'id' => 9));
        $this->output = "";
        $expected = '<br>As listed above, the following rides seem to have been copied from Endomondo, and can be deleted from Strava (and then re-added, if you choose).<br><ol>
<li><a target="_blank" href="\">494647884</a></li></ol><form action="" method="post" name="delete_strava_rides_form"><input type="submit" name="delete_from_strava" value="Delete these rides from Strava?"/><input type="hidden" name="activity_numbers" value="494647884,"></form>';
        $this->assertEquals($expected, $deleteEndoRidesFromStrava->invokeArgs($this->mainPage, array()));
        $this->assertEquals('<br><a target="_blank" href="\">494647884</a> (<a target="_blank" href="endo activity URL\">99999999</a>) queued for deletion<br>', $this->output);

           // ride has  endo_id, needs to get description from strava which doesn't include URL
        $rides = array('2016-01-01' =>
            array(array('distance' => 17124.799999999999,
                'name' => 'Afternoon Ride', 'strava_id' => 494647884, 'start_time' => '2016-01-01T15:59:17Z',
                'bike' => 'b267883', 'moving_time' => 3800, 'elapsed_time' => 6897, 'max_speed' => 8.9000000000000004,
                'total_elevation_gain' => 114.90000000000001, 'timezone' => 'Europe/London', 'kudos_count' => 0,
                'photo_count' => 0, 'comment_count' => 0, 'endo_id' => 99999999)));
        $strava->expects($this->at(0))->method('getRides')->willReturn($rides);
        $strava->expects($this->at(2))->method('getActivityDescription')->willReturn('random description');
        $endo->expects($this->at(0))->method('activityUrl')->willReturn('endo activity URL');
        $endo->expects($this->at(1))->method('activityUrl')->willReturn('endo activity URL');
        $endo->expects($this->at(2))->method('getWorkout')->willReturn((object)array('distance' => 17125, 'startTime' => 1451663957, 'id' => 9));
        $this->output = "";
        $this->assertEquals('', $deleteEndoRidesFromStrava->invokeArgs($this->mainPage, array()));
        $this->assertEquals('', $this->output);

        // ride has matching endo id, but has been deleted from endomondo
        $rides = array('2016-01-01' =>
            array(array('distance' => 17124.799999999999,
                'name' => 'Afternoon Ride', 'strava_id' => 494647884, 'start_time' => '2016-01-01T15:59:17Z',
                'bike' => 'b267883', 'moving_time' => 3800, 'elapsed_time' => 6897, 'max_speed' => 8.9000000000000004,
                'total_elevation_gain' => 114.90000000000001, 'timezone' => 'Europe/London', 'kudos_count' => 0,
                'photo_count' => 0, 'comment_count' => 0, 'description' => 'endo activity URL', 'endo_id' => 99999999)));
        $strava->expects($this->at(0))->method('getRides')->willReturn($rides);
        $endo->expects($this->at(0))->method('activityUrl')->willReturn('endo activity URL');
        $endo->expects($this->at(1))->method('activityUrl')->willReturn('endo activity URL');
        $endo->expects($this->at(2))->method('getWorkout')->willReturn(null);
        $this->output = "";
        $this->assertEquals('', $deleteEndoRidesFromStrava->invokeArgs($this->mainPage, array()));
        $this->assertEquals('<br>Skipping <a target="_blank" href="\">494647884</a> (<a target="_blank" href="endo activity URL\">99999999</a>) because the associated endo ride has issues: <br>', $this->output);



        // ride starts 29 minutes, 59 seconds earlier
        $rides = array('2016-01-01' =>
            array(array('distance' => 17124.799999999999,
                'name' => 'Afternoon Ride', 'strava_id' => 494647884, 'start_time' => '2016-01-01T15:59:17Z',
                'bike' => 'b267883', 'moving_time' => 3800, 'elapsed_time' => 6897, 'max_speed' => 8.9000000000000004,
                'total_elevation_gain' => 114.90000000000001, 'timezone' => 'Europe/London', 'kudos_count' => 0,
                'photo_count' => 0, 'comment_count' => 0, 'description' => 'endo activity URL', 'endo_id' => 99999999)));
        $strava->expects($this->at(0))->method('getRides')->willReturn($rides);
        $endo->expects($this->at(0))->method('activityUrl')->willReturn('endo activity URL');
        $endo->expects($this->at(1))->method('activityUrl')->willReturn('endo activity URL');
        $endo->expects($this->at(2))->method('getWorkout')->willReturn((object)array('distance' => 17125, 'startTime' => 1451662158, 'id' => 9));
        $this->output = "";
        $expected = '<br>As listed above, the following rides seem to have been copied from Endomondo, and can be deleted from Strava (and then re-added, if you choose).<br><ol>
<li><a target="_blank" href="\">494647884</a></li></ol><form action="" method="post" name="delete_strava_rides_form"><input type="submit" name="delete_from_strava" value="Delete these rides from Strava?"/><input type="hidden" name="activity_numbers" value="494647884,"></form>';
        $this->assertEquals($expected, $deleteEndoRidesFromStrava->invokeArgs($this->mainPage, array()));
        $this->assertEquals('<br><a target="_blank" href="\">494647884</a> (<a target="_blank" href="endo activity URL\">99999999</a>) queued for deletion<br>', $this->output);

      // ride starts 30 minutes, 1 seconds earlier
        $rides = array('2016-01-01' =>
            array(array('distance' => 17124.799999999999,
                'name' => 'Afternoon Ride', 'strava_id' => 494647884, 'start_time' => '2016-01-01T15:59:17Z',
                'bike' => 'b267883', 'moving_time' => 3800, 'elapsed_time' => 6897, 'max_speed' => 8.9000000000000004,
                'total_elevation_gain' => 114.90000000000001, 'timezone' => 'Europe/London', 'kudos_count' => 0,
                'photo_count' => 0, 'comment_count' => 0, 'description' => 'endo activity URL', 'endo_id' => 99999999)));
        $strava->expects($this->at(0))->method('getRides')->willReturn($rides);
        $endo->expects($this->at(0))->method('activityUrl')->willReturn('endo activity URL');
        $endo->expects($this->at(1))->method('activityUrl')->willReturn('endo activity URL');
        $endo->expects($this->at(2))->method('getWorkout')->willReturn((object)array('distance' => 17125, 'startTime' => 1451662156, 'id' => 9));
        $this->output = "";
        $this->assertEquals('', $deleteEndoRidesFromStrava->invokeArgs($this->mainPage, array()));
        $this->assertEquals('<br>Skipping <a target="_blank" href="\">494647884</a> (<a target="_blank" href="endo activity URL\">99999999</a>) because the associated endo ride didn\'t start within 30 minutes of this ride <br>', $this->output);

        // ride starts 29 minutes, 59 seconds later
        $rides = array('2016-01-01' =>
            array(array('distance' => 17124.799999999999,
                'name' => 'Afternoon Ride', 'strava_id' => 494647884, 'start_time' => '2016-01-01T15:59:17Z',
                'bike' => 'b267883', 'moving_time' => 3800, 'elapsed_time' => 6897, 'max_speed' => 8.9000000000000004,
                'total_elevation_gain' => 114.90000000000001, 'timezone' => 'Europe/London', 'kudos_count' => 0,
                'photo_count' => 0, 'comment_count' => 0, 'description' => 'endo activity URL', 'endo_id' => 99999999)));
        $strava->expects($this->at(0))->method('getRides')->willReturn($rides);
        $endo->expects($this->at(0))->method('activityUrl')->willReturn('endo activity URL');
        $endo->expects($this->at(1))->method('activityUrl')->willReturn('endo activity URL');
        $endo->expects($this->at(2))->method('getWorkout')->willReturn((object)array('distance' => 17125, 'startTime' => 1451665756, 'id' => 9));
        $this->output = "";
        $expected = '<br>As listed above, the following rides seem to have been copied from Endomondo, and can be deleted from Strava (and then re-added, if you choose).<br><ol>
<li><a target="_blank" href="\">494647884</a></li></ol><form action="" method="post" name="delete_strava_rides_form"><input type="submit" name="delete_from_strava" value="Delete these rides from Strava?"/><input type="hidden" name="activity_numbers" value="494647884,"></form>';
        $this->assertEquals($expected, $deleteEndoRidesFromStrava->invokeArgs($this->mainPage, array()));
        $this->assertEquals('<br><a target="_blank" href="\">494647884</a> (<a target="_blank" href="endo activity URL\">99999999</a>) queued for deletion<br>', $this->output);


        // ride starts 30 minutes, 1 seconds later
        $rides = array('2016-01-01' =>
            array(array('distance' => 17124.799999999999,
                'name' => 'Afternoon Ride', 'strava_id' => 494647884, 'start_time' => '2016-01-01T15:59:17Z',
                'bike' => 'b267883', 'moving_time' => 3800, 'elapsed_time' => 6897, 'max_speed' => 8.9000000000000004,
                'total_elevation_gain' => 114.90000000000001, 'timezone' => 'Europe/London', 'kudos_count' => 0,
                'photo_count' => 0, 'comment_count' => 0, 'description' => 'endo activity URL', 'endo_id' => 99999999)));
        $strava->expects($this->at(0))->method('getRides')->willReturn($rides);
        $endo->expects($this->at(0))->method('activityUrl')->willReturn('endo activity URL');
        $endo->expects($this->at(1))->method('activityUrl')->willReturn('endo activity URL');
        $endo->expects($this->at(2))->method('getWorkout')->willReturn((object)array('distance' => 17125, 'startTime' => 1451665758, 'id' => 9));
        $this->output = "";
        $this->assertEquals('', $deleteEndoRidesFromStrava->invokeArgs($this->mainPage, array()));
        $this->assertEquals('<br>Skipping <a target="_blank" href="\">494647884</a> (<a target="_blank" href="endo activity URL\">99999999</a>) because the associated endo ride didn\'t start within 30 minutes of this ride <br>', $this->output);


        // ride has kudos
        $rides = array('2016-01-01' =>
            array(array('distance' => 17124.799999999999,
                'name' => 'Afternoon Ride', 'strava_id' => 494647884, 'start_time' => '2016-01-01T15:59:17Z',
                'bike' => 'b267883', 'moving_time' => 3800, 'elapsed_time' => 6897, 'max_speed' => 8.9000000000000004,
                'total_elevation_gain' => 114.90000000000001, 'timezone' => 'Europe/London', 'kudos_count' => 1,
                'photo_count' => 0, 'comment_count' => 0, 'description' => 'endo activity URL', 'endo_id' => 99999999)));
        $strava->expects($this->at(0))->method('getRides')->willReturn($rides);
        $endo->expects($this->at(0))->method('activityUrl')->willReturn('endo activity URL');
        $endo->expects($this->at(1))->method('activityUrl')->willReturn('endo activity URL');
        $this->output = "";
        $this->assertEquals('', $deleteEndoRidesFromStrava->invokeArgs($this->mainPage, array()));
        $this->assertEquals('<br>Skipping <a target="_blank" href="\">494647884</a> (<a target="_blank" href="endo activity URL\">99999999</a>) because it has 1 kudo(s)<br>', $this->output);

       // ride has photos
        $rides = array('2016-01-01' =>
            array(array('distance' => 17124.799999999999,
                'name' => 'Afternoon Ride', 'strava_id' => 494647884, 'start_time' => '2016-01-01T15:59:17Z',
                'bike' => 'b267883', 'moving_time' => 3800, 'elapsed_time' => 6897, 'max_speed' => 8.9000000000000004,
                'total_elevation_gain' => 114.90000000000001, 'timezone' => 'Europe/London', 'kudos_count' => 0,
                'photo_count' => 3, 'comment_count' => 0, 'description' => 'endo activity URL', 'endo_id' => 99999999)));
        $strava->expects($this->at(0))->method('getRides')->willReturn($rides);
        $endo->expects($this->at(0))->method('activityUrl')->willReturn('endo activity URL');
        $endo->expects($this->at(1))->method('activityUrl')->willReturn('endo activity URL');
        $this->output = "";
        $this->assertEquals('', $deleteEndoRidesFromStrava->invokeArgs($this->mainPage, array()));
        $this->assertEquals('<br>Skipping <a target="_blank" href="\">494647884</a> (<a target="_blank" href="endo activity URL\">99999999</a>) because it has 3 photo(s)<br>', $this->output);

       // ride has comments
        $rides = array('2016-01-01' =>
            array(array('distance' => 17124.799999999999,
                'name' => 'Afternoon Ride', 'strava_id' => 494647884, 'start_time' => '2016-01-01T15:59:17Z',
                'bike' => 'b267883', 'moving_time' => 3800, 'elapsed_time' => 6897, 'max_speed' => 8.9000000000000004,
                'total_elevation_gain' => 114.90000000000001, 'timezone' => 'Europe/London', 'kudos_count' => 0,
                'photo_count' => 0, 'comment_count' => 2, 'description' => 'endo activity URL', 'endo_id' => 99999999)));
        $strava->expects($this->at(0))->method('getRides')->willReturn($rides);
        $endo->expects($this->at(0))->method('activityUrl')->willReturn('endo activity URL');
        $endo->expects($this->at(1))->method('activityUrl')->willReturn('endo activity URL');
        $this->output = "";
        $this->assertEquals('', $deleteEndoRidesFromStrava->invokeArgs($this->mainPage, array()));
        $this->assertEquals('<br>Skipping <a target="_blank" href="\">494647884</a> (<a target="_blank" href="endo activity URL\">99999999</a>) because it has 2 comment(s)<br>', $this->output);


        // multiple rides, the ones with endo ids should be deleted.
        $rides = include('data/input/getRides5.php');
        $strava->expects($this->at(0))->method('getRides')->willReturn($rides);
        $strava->expects($this->any())->method('getActivityDescription')->willReturn('endo activity URL');
        $endo->expects($this->any())->method('activityUrl')->willReturn('endo activity URL');
        $endo->expects($this->any())->method('getWorkout')->willReturn((object)array('distance' => 17125, 'startTime' => 1451658737, 'id' => 9));
        $this->output = "";
        $expected = '<br>As listed above, the following rides seem to have been copied from Endomondo, and can be deleted from Strava (and then re-added, if you choose).<br><ol>
<li><a target="_blank" href="\">494647884</a></li>
<li><a target="_blank" href="\">494647784</a></li>
<li><a target="_blank" href="\">494647777</a></li>
<li><a target="_blank" href="\">494647768</a></li>
<li><a target="_blank" href="\">494647748</a></li>
<li><a target="_blank" href="\">494647728</a></li>
<li><a target="_blank" href="\">494647725</a></li>
<li><a target="_blank" href="\">494647711</a></li>
<li><a target="_blank" href="\">494647705</a></li>
<li><a target="_blank" href="\">494661555</a></li>
<li><a target="_blank" href="\">494661550</a></li>
<li><a target="_blank" href="\">494661538</a></li>
<li><a target="_blank" href="\">494661520</a></li>
<li><a target="_blank" href="\">494661502</a></li>
<li><a target="_blank" href="\">494661491</a></li></ol><form action="" method="post" name="delete_strava_rides_form"><input type="submit" name="delete_from_strava" value="Delete these rides from Strava?"/><input type="hidden" name="activity_numbers" value="494647884,494647784,494647777,494647768,494647748,494647728,494647725,494647711,494647705,494661555,494661550,494661538,494661520,494661502,494661491,"></form>';
        $this->assertEquals($expected, $deleteEndoRidesFromStrava->invokeArgs($this->mainPage, array()));
        $this->assertEquals('..<br><a target="_blank" href="\">494647884</a> (<a target="_blank" href="endo activity URL\">650970286</a>) queued for deletion<br>.........<br><a target="_blank" href="\">494647784</a> (<a target="_blank" href="endo activity URL\">656519664</a>) queued for deletion<br><br><a target="_blank" href="\">494647777</a> (<a target="_blank" href="endo activity URL\">656716916</a>) queued for deletion<br><br><a target="_blank" href="\">494647768</a> (<a target="_blank" href="endo activity URL\">657505835</a>) queued for deletion<br><br><a target="_blank" href="\">494647748</a> (<a target="_blank" href="endo activity URL\">657480811</a>) queued for deletion<br><br><a target="_blank" href="\">494647728</a> (<a target="_blank" href="endo activity URL\">657554078</a>) queued for deletion<br><br><a target="_blank" href="\">494647725</a> (<a target="_blank" href="endo activity URL\">658323900</a>) queued for deletion<br><br><a target="_blank" href="\">494647711</a> (<a target="_blank" href="endo activity URL\">658434723</a>) queued for deletion<br><br><a target="_blank" href="\">494647705</a> (<a target="_blank" href="endo activity URL\">658500404</a>) queued for deletion<br>..............<br><a target="_blank" href="\">494661555</a> (<a target="_blank" href="endo activity URL\">668479655</a>) queued for deletion<br><br><a target="_blank" href="\">494661550</a> (<a target="_blank" href="endo activity URL\">668574585</a>) queued for deletion<br><br><a target="_blank" href="\">494661538</a> (<a target="_blank" href="endo activity URL\">668663527</a>) queued for deletion<br><br><a target="_blank" href="\">494661520</a> (<a target="_blank" href="endo activity URL\">668841147</a>) queued for deletion<br><br><a target="_blank" href="\">494661502</a> (<a target="_blank" href="endo activity URL\">669119895</a>) queued for deletion<br><br><a target="_blank" href="\">494661491</a> (<a target="_blank" href="endo activity URL\">669286757</a>) queued for deletion<br>.....', $this->output);


    }


    public function testCopy()
    {
        $copy = $this->getMethod('copy');

        $rides = include('data/input/getRides.php');
        $oneLessRides = include('data/input/getRides1.php');
        $oneDayLessRides = include('data/input/getRides4.php');
        $oneShorterRides = include('data/input/getRides2.php');
        $shortListOfRides = include('data/input/getRides3.php');

        // STRAVA TO MCL
        $preferences = $this->getMockBuilder('Preferences')->disableOriginalConstructor()
            ->setMethods(array('getStravaSplitRides'))->getMock();
        $strava = $this->getMockBuilder('trackerAbstract')
            ->setMethods(array('getUserId', 'getPoints', 'getRides', 'getError', 'activityUrl', 'waitForPendingUploads', 'getOvernightActivities', 'getBike'))->getMock();
        $mcl = $this->getMockBuilder('trackerAbstract')->setMethods(array('getRides', 'waitForPendingUploads', 'getError', 'getBike', 'bikeMatch', 'addRide'))->getMock();
        $this->setProperty('strava', $strava, $this->mainPage);
        $this->setProperty('myCyclingLog', $mcl, $this->mainPage);
        $this->setProperty('preferences', $preferences, $this->mainPage);
        $this->setProperty('noEcho', false, $this->mainPage);
        $mcl->expects($this->any())->method('getBike')->willReturn(null);
        $mcl->expects($this->any())->method('getError')->willReturn(null);
        $strava->expects($this->any())->method('getBike')->willReturn(null);

        // strava and MCL return exactly the same list of rides
        $mcl->expects($this->at(0))->method('getRides')->willReturn($rides);
        $strava->expects($this->at(0))->method('getRides')->willReturn($rides);
        $this->output = "";

        $this->assertEquals("<br>0 rides added.<br>", $copy->invokeArgs($this->mainPage, array("Strava", "MyCyclingLog", null, null)));
        $this->assertEquals("<H3>Copying data from Strava to MyCyclingLog...</H3>", $this->output);

        // no rides coming from Strava
        $mcl->expects($this->at(0))->method('getRides')->willReturn($rides);
        $strava->expects($this->at(0))->method('getRides')->willReturn([]);
        $this->output = "";
        $this->assertEquals("<br>0 rides added.<br>", $copy->invokeArgs($this->mainPage, array("Strava", "MyCyclingLog", null, null)));
        $this->assertEquals("<H3>Copying data from Strava to MyCyclingLog...</H3>", $this->output);

        //MCL has a ride strava doesn't
        $this->output = "";
        $mcl->expects($this->at(0))->method('getRides')->willReturn($rides);
        $strava->expects($this->at(0))->method('getRides')->willReturn($oneLessRides);
        $this->output = "";
        $this->assertEquals("<br>0 rides added.<br>", $copy->invokeArgs($this->mainPage, array("Strava", "MyCyclingLog", null, null)));
        $this->assertEquals("<H3>Copying data from Strava to MyCyclingLog...</H3>", $this->output);

        //Strava has a ride MCL doesn't
        $this->output = "";
        $mcl->expects($this->at(0))->method('getRides')->willReturn($oneLessRides);
        $strava->expects($this->at(0))->method('getRides')->willReturn($rides);
        $ride = array('distance' => 5268.5, 'name' => 'Evening Ride', 'strava_id' => 464768505,
            'start_time' => '2016-01-06T19:44:25Z', 'moving_time' => 1210,
            'elapsed_time' => 3833, 'total_elevation_gain' => 0, 'max_speed' => 8,
            'timezone' => 'Europe/London', 'kudos_count' => 0, 'comment_count' => 0,
            'endo_id' => NULL, 'bike' => NULL,
            'description' => null,
            'message' => 'Ride with id <a target="_blank" href="">464768505</a> on 2016-01-06, distance 3.3 miles/5.3 kms. '); // todo check message
        $mcl->expects($this->at(3))->method('addRide')->with('2016-01-06', $ride)->willReturn("999999");
        $this->output = "";
        $this->assertEquals("<br>1 rides added.<br>", $copy->invokeArgs($this->mainPage, array("Strava", "MyCyclingLog", null, null)));
        $this->assertEquals("<H3>Copying data from Strava to MyCyclingLog...</H3>.Ride with id <a target=\"_blank\" href=\"\">464768505</a> on 2016-01-06, distance 3.3 miles/5.3 kms. Added new ride, id: 999999 <br>", $this->output);


        //Same, but bike ids match
        $this->output = "";
        $mcl->expects($this->at(0))->method('getRides')->willReturn($oneLessRides);
        $mcl->expects($this->any())->method('bikeMatch')->willReturn("b121212");
        $strava->expects($this->at(0))->method('getRides')->willReturn($rides);
        $ride = array('distance' => 5268.5, 'name' => 'Evening Ride', 'strava_id' => 464768505,
            'start_time' => '2016-01-06T19:44:25Z', 'moving_time' => 1210,
            'elapsed_time' => 3833, 'total_elevation_gain' => 0, 'max_speed' => 8,
            'timezone' => 'Europe/London', 'kudos_count' => 0, 'comment_count' => 0,
            'endo_id' => NULL, 'bike' => 'b121212',
            'description' => null,
            'message' => 'Ride with id <a target="_blank" href="">464768505</a> on 2016-01-06, distance 3.3 miles/5.3 kms. '
        );
        $mcl->expects($this->at(3))->method('addRide')->with('2016-01-06', $ride)->willReturn("8888");
        $this->output = "";
        $this->assertEquals("<br>1 rides added.<br>", $copy->invokeArgs($this->mainPage, array("Strava", "MyCyclingLog", null, null)));
        $this->assertEquals("<H3>Copying data from Strava to MyCyclingLog...</H3>.Ride with id <a target=\"_blank\" href=\"\">464768505</a> on 2016-01-06, distance 3.3 miles/5.3 kms. Added new ride, id: 8888 <br>", $this->output);


        //MCL is missing a whole day that's in strava
        $this->output = "";
        $mcl->expects($this->at(0))->method('getRides')->willReturn($oneDayLessRides);
        $mcl->expects($this->any())->method('bikeMatch')->willReturn("b121212");
        $strava->expects($this->at(0))->method('getRides')->willReturn($rides);
        $ride = array(
            'distance' => 2975.5,
            'name' => 'Afternoon Ride',
            'strava_id' => 470171383,
            'start_time' => '2016-01-08T13:20:00Z',
            'moving_time' => 599,
            'elapsed_time' => 599,
            'total_elevation_gain' => 0,
            'max_speed' => 5.5,
            'timezone' => 'Europe/London',
            'kudos_count' => 0,
            'comment_count' => 0,
            'endo_id' => NULL, 'bike' => 'b121212',
            'description' => null,
            'message' => 'Ride with id <a target="_blank" href="">470171383</a> on 2016-01-08, distance 1.8 miles/3 kms. '
        );
        $mcl->expects($this->at(3))->method('addRide')->with('2016-01-08', $ride)->willReturn("6666");
        $ride = array(
            'distance' => 2919,
            'name' => 'Evening Ride',
            'strava_id' => 470166379,
            'start_time' => '2016-01-08T20:30:00Z',
            'moving_time' => 600,
            'elapsed_time' => 600,
            'total_elevation_gain' => 0,
            'max_speed' => 5.2000000000000002,
            'timezone' => 'Europe/London',
            'kudos_count' => 0,
            'comment_count' => 0,
            'endo_id' => NULL, 'bike' => 'b121212',
            'description' => null,
            'message' => 'Ride with id <a target="_blank" href="">470166379</a> on 2016-01-08, distance 1.8 miles/2.9 kms. '
        );
        $mcl->expects($this->at(5))->method('addRide')->with('2016-01-08', $ride)->willReturn("7777");
        $this->output = "";
        $this->assertEquals("<br>2 rides added.<br>", $copy->invokeArgs($this->mainPage, array("Strava", "MyCyclingLog", null, null)));
        $this->assertEquals("<H3>Copying data from Strava to MyCyclingLog...</H3>Ride with id <a target=\"_blank\" href=\"\">470171383</a> on 2016-01-08, distance 1.8 miles/3 kms. Added new ride, id: 6666 <br>Ride with id <a target=\"_blank\" href=\"\">470166379</a> on 2016-01-08, distance 1.8 miles/2.9 kms. Added new ride, id: 7777 <br>", $this->output);


        //They have the same number of rides on the same dates, but one is shorter on MCL
        $this->output = "";
        $mcl->expects($this->at(0))->method('getRides')->willReturn($oneShorterRides);
        $mcl->expects($this->any())->method('bikeMatch')->willReturn("b121212");
        $strava->expects($this->at(0))->method('getRides')->willReturn($rides);
        $ride = array(
            'distance' => 7124.8,
            'name' => 'Afternoon Ride',
            'strava_id' => 494647884,
            'start_time' => '2016-01-01T15:59:17Z',
            'moving_time' => 3800,
            'elapsed_time' => 6897,
            'total_elevation_gain' => 114.9,
            'max_speed' => 8.9,
            'timezone' => 'Europe/London',
            'kudos_count' => 0,
            'comment_count' => 0,
            'endo_id' => 650970286,
            'bike' => 'b121212',
            'description' => null,
            'message' => 'Ride with id <a target="_blank" href="">494647884</a> on 2016-01-01, distance 4.4 miles/7.1 kms. '

        );
        $mcl->expects($this->at(3))->method('addRide')->with('2016-01-01', $ride)->willReturn("8888");
        $this->output = "";
        $this->assertEquals("<br>1 rides added.<br>", $copy->invokeArgs($this->mainPage, array("Strava", "MyCyclingLog", null, null)));
        $this->assertEquals("<H3>Copying data from Strava to MyCyclingLog...</H3>..Ride with id <a target=\"_blank\" href=\"\">494647884</a> on 2016-01-01, distance 4.4 miles/7.1 kms. Added new ride, id: 8888 <br>", $this->output);


        //No rides on MCL
        $this->output = "";
        $mcl->expects($this->at(0))->method('getRides')->willReturn([]);
        $strava->expects($this->at(0))->method('getRides')->willReturn($shortListOfRides);
        $mcl->expects($this->any())->method('addRide')->willReturnOnConsecutiveCalls(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12);
        $this->output = "";
        $this->assertEquals("<br>7 rides added.<br>", $copy->invokeArgs($this->mainPage, array("Strava", "MyCyclingLog", null, null)));
        $this->assertEquals('<H3>Copying data from Strava to MyCyclingLog...</H3>Ride with id <a target="_blank" href="">460839170</a> on 2016-01-01, distance 3.1 miles/5 kms. Added new ride, id: 1 <br>Ride with id <a target="_blank" href="">460839481</a> on 2016-01-01, distance 2.5 miles/4 kms. Added new ride, id: 2 <br>Ride with id <a target="_blank" href="">494647884</a> on 2016-01-01, distance 10.6 miles/17.1 kms. Added new ride, id: 3 <br>Ride with id <a target="_blank" href="">464768504</a> on 2016-01-06, distance 1.4 miles/2.3 kms. Added new ride, id: 4 <br>Ride with id <a target="_blank" href="">464768505</a> on 2016-01-06, distance 3.3 miles/5.3 kms. Added new ride, id: 5 <br>Ride with id <a target="_blank" href="">470171383</a> on 2016-01-08, distance 1.8 miles/3 kms. Added new ride, id: 6 <br>Ride with id <a target="_blank" href="">470166379</a> on 2016-01-08, distance 1.8 miles/2.9 kms. Added new ride, id: 7 <br>',
            $this->output);

        //overnight rides
        $_POST = array(
            'start_date' => '01-01-2015',
            'end_date' => '31-12-2015',
        );
        $this->output = "";
        $mcl = $this->getMockBuilder('trackerAbstract')->setMethods(array('getRides', 'waitForPendingUploads', 'getBike', 'getError', 'bikeMatch', 'addRide'))->getMock();
        $mcl->expects($this->any())->method('getError')->willReturn(null);
        $mcl->expects($this->at(0))->method('getRides')->willReturn([]);
        $this->setProperty('myCyclingLog', $mcl, $this->mainPage);
        $strava->expects($this->any())->method('getRides')->willReturn($shortListOfRides);
        $strava->expects($this->any())->method('getOvernightActivities')->willReturn(include('data/input/overnightActivity.php'));
        $preferences->expects($this->any())->method('getStravaSplitRides')->willReturn(true);
        $mcl->expects($this->any())->method('addRide')->willReturnOnConsecutiveCalls(1, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12);
        $this->output = "";
        $this->assertEquals("<br>6 rides added.<br>" . include('data/expected/askForStravaGpx2.php'), $copy->invokeArgs($this->mainPage, array("Strava", "MyCyclingLog", null, null, true)));
        $this->assertEquals('<H3>Copying data from Strava to MyCyclingLog...</H3>Ride with id <a target="_blank" href="">460839170</a> on 2016-01-01, distance 3.1 miles/5 kms. Added new ride, id: 1 <br>Ride with id <a target="_blank" href="">494647884</a> on 2016-01-01, distance 10.6 miles/17.1 kms. Added new ride, id: 3 <br>Ride with id <a target="_blank" href="">464768504</a> on 2016-01-06, distance 1.4 miles/2.3 kms. Added new ride, id: 4 <br>Ride with id <a target="_blank" href="">464768505</a> on 2016-01-06, distance 3.3 miles/5.3 kms. Added new ride, id: 5 <br>Ride with id <a target="_blank" href="">470171383</a> on 2016-01-08, distance 1.8 miles/3 kms. Added new ride, id: 6 <br>Ride with id <a target="_blank" href="">470166379</a> on 2016-01-08, distance 1.8 miles/2.9 kms. Added new ride, id: 7 <br>',
            $this->output);

        // ENDOMONDO TO STRAVA


        $points = $this->getMockBuilder('Points')->setMethods(array('gpxBad', 'gpx'))->getMock();
        $builder = $this->getMockBuilder('TrackerAbstract')
            ->setMethods(array('getUserId', 'getRides', 'getError', 'activityUrl', 'getPoints',
                'waitForPendingUploads', 'getOvernightActivities', 'generateEndoExternalId', 'getBike', 'bikeMatch', 'addRide'));
        $this->setProperty('preferences', $preferences, $this->mainPage);
        $this->setProperty('noEcho', false, $this->mainPage);

        // endomondo and STRAVA return exactly the same list of rides
        $endomondo = $builder->getMock();
        $strava = $builder->getMock();

        $this->setProperty('endomondo', $endomondo, $this->mainPage);
        $this->setProperty('strava', $strava, $this->mainPage);
        $strava->expects($this->any())->method('getRides')->willReturn($rides);
        $strava->expects($this->never())->method('addRide');
        $endomondo->expects($this->never())->method('addRide');
        $endomondo->expects($this->any())->method('getRides')->willReturn($rides);
        $strava->expects($this->any())->method('waitForPendingUploads')->willReturn([]);
        $this->output = "";
        $this->assertEquals("<br>0 rides added.<br>", $copy->invokeArgs($this->mainPage, array("Endomondo", "Strava", null, null, false)));
        $this->assertEquals("<H3>Copying data from Endomondo to Strava...</H3>", $this->output);

        // no rides coming from Endomondo
        $endomondo = $builder->getMock();
        $strava = $builder->getMock();
        $strava->expects($this->never())->method('addRide');
        $endomondo->expects($this->never())->method('addRide');

        $strava->expects($this->any())->method('waitForPendingUploads')->willReturn([]);

        $this->setProperty('endomondo', $endomondo, $this->mainPage);
        $this->setProperty('strava', $strava, $this->mainPage);
        $strava->expects($this->any())->method('getRides')->willReturn($rides);
        $endomondo->expects($this->any())->method('getRides')->willReturn([]);
        $this->output = "";
        $this->assertEquals("<br>0 rides added.<br>", $copy->invokeArgs($this->mainPage, array("Endomondo", "Strava", null, null, false)));
        $this->assertEquals("<H3>Copying data from Endomondo to Strava...</H3>", $this->output);

        //STRAVA has a ride endomondo doesn't
        $endomondo = $builder->getMock();
        $strava = $builder->getMock();
        $endomondo->expects($this->never())->method('addRide');
        $strava->expects($this->never())->method('addRide');


        $strava->expects($this->any())->method('waitForPendingUploads')->willReturn([]);

        $this->setProperty('endomondo', $endomondo, $this->mainPage);
        $this->setProperty('strava', $strava, $this->mainPage);
        $this->output = "";
        $strava->expects($this->any())->method('getRides')->willReturn($rides);
        $endomondo->expects($this->any())->method('getRides')->willReturn($oneLessRides);
        $this->output = "";
        $this->assertEquals("<br>0 rides added.<br>", $copy->invokeArgs($this->mainPage, array("Endomondo", "Strava", null, null, false)));
        $this->assertEquals("<H3>Copying data from Endomondo to Strava...</H3>", $this->output);

        //Endomondo has a ride STRAVA doesn't
        $endomondo = $builder->getMock();
        $strava = $builder->getMock();
        $endomondo->expects($this->never())->method('addRide');
        $ride = array(
            'distance' => 5268.5,
            'name' => 'Evening Ride',
            'strava_id' => 464768505,
            'start_time' => '2016-01-06T19:44:25Z',
            'bike' => null,
            'moving_time' => 1210,
            'elapsed_time' => 3833,
            'total_elevation_gain' => 0,
            'max_speed' => 8,
            'timezone' => 'Europe/London',
            'kudos_count' => 0,
            'comment_count' => 0,
            'endo_id' => NULL,
            'message' => 'Ride with id <a target="_blank" href="activityUrl -xx"></a> on 2016-01-06, distance 3.3 miles/5.3 kms. ',
            'description' => 'activityUrl -xx'
        );
        $strava->expects($this->once())->method('addRide')->with("2016-01-06", $ride, $points)->willReturn(true);
        $endomondo->expects($this->any())->method('activityUrl')->willReturn("activityUrl -xx");
        $strava->expects($this->any())->method('waitForPendingUploads')->willReturn(array(
            'endomondo_2859253_674115438' =>
                (object)(array(
                    'message' => 'Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms. ',
                    'external_id' => 'endomondo_2859253_674115438',
                    'file' => '/tmp/endomondo+674115438.gpx',
                    'status' => 'Your activity is ready.',
                    'strava_id' => 501116906,
                ))));
        $endomondo->expects($this->any())->method('getPoints')->willReturn($points);

        $this->setProperty('endomondo', $endomondo, $this->mainPage);
        $this->setProperty('strava', $strava, $this->mainPage);
        $this->output = "";
        $strava->expects($this->any())->method('getRides')->willReturn($oneLessRides);
        $endomondo->expects($this->any())->method('getRides')->willReturn($rides);
        $ride = array('distance' => 5268.5, 'name' => 'Evening Ride',
            'start_time' => '2016-01-06T19:44:25Z', 'moving_time' => 1210,
            'elapsed_time' => 3833, 'total_elevation_gain' => 0, 'max_speed' => 8,
            'timezone' => 'Europe/London', 'kudos_count' => 0, 'comment_count' => 0,
            'endo_id' => NULL, 'bike' => NULL);
        $this->output = "";
        $this->assertEquals("<br>1 rides added.<br>", $copy->invokeArgs($this->mainPage, array("Endomondo", "Strava", null, null, false)));
        $this->assertEquals("<H3>Copying data from Endomondo to Strava...</H3>.Ride with id <a target=\"_blank\" href=\"activityUrl -xx\"></a> on 2016-01-06, distance 3.3 miles/5.3 kms. Queued for upload.<br><br>Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms.  Uploaded successfully, id: <a target=\"_blank\" href=\"\">501116906</a>.", $this->output);


        //Same, but bike ids match
        $endomondo = $builder->getMock();
        $strava = $builder->getMock();
        $endomondo->expects($this->any())->method('activityUrl')->willReturn("activityUrl");
        $ride = array(
            'distance' => 5268.5,
            'name' => 'Evening Ride',
            'strava_id' => 464768505,
            'start_time' => '2016-01-06T19:44:25Z',
            'bike' => 'b121212',
            'moving_time' => 1210,
            'elapsed_time' => 3833,
            'total_elevation_gain' => 0,
            'max_speed' => 8,
            'timezone' => 'Europe/London',
            'kudos_count' => 0,
            'comment_count' => 0,
            'endo_id' => NULL,
            'message' => 'Ride with id <a target="_blank" href="activityUrl"></a> on 2016-01-06, distance 3.3 miles/5.3 kms. '
        , 'description' => 'activityUrl'
        );
        $strava->expects($this->once())->method('addRide')->with("2016-01-06", $ride, $points)->willReturn(true);

        $strava->expects($this->any())->method('waitForPendingUploads')->willReturn(array(
            'endomondo_2859253_674115438' =>
                (object)(array(
                    'message' => 'Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms. ',
                    'external_id' => 'endomondo_2859253_674115438',
                    'file' => '/tmp/endomondo+674115438.gpx',
                    'status' => 'Your activity is ready.',
                    'strava_id' => 501116906,
                ))));
        $endomondo->expects($this->any())->method('getPoints')->willReturn($points);

        $this->setProperty('endomondo', $endomondo, $this->mainPage);
        $this->setProperty('strava', $strava, $this->mainPage);
        $this->output = "";
        $strava->expects($this->any())->method('getRides')->willReturn($oneLessRides);
        $strava->expects($this->any())->method('bikeMatch')->willReturn("b121212");
        $endomondo->expects($this->any())->method('getRides')->willReturn($rides);
        $this->output = "";
        $this->assertEquals("<br>1 rides added.<br>", $copy->invokeArgs($this->mainPage, array("Endomondo", "Strava", null, null, false)));
        $this->assertEquals("<H3>Copying data from Endomondo to Strava...</H3>.Ride with id <a target=\"_blank\" href=\"activityUrl\"></a> on 2016-01-06, distance 3.3 miles/5.3 kms. Queued for upload.<br><br>Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms.  Uploaded successfully, id: <a target=\"_blank\" href=\"\">501116906</a>.", $this->output);

        //Endomondo has a ride STRAVA doesn't that doesn't upload the first two times
        $endomondo = $builder->getMock();
        $strava = $builder->getMock();
        $endomondo->expects($this->any())->method('activityUrl')->willReturn("activityUrl");
        $ride = array(
            'distance' => 5268.5,
            'name' => 'Evening Ride',
            'strava_id' => 464768505,
            'start_time' => '2016-01-06T19:44:25Z',
            'bike' => 'b121212',
            'moving_time' => 1210,
            'elapsed_time' => 3833,
            'total_elevation_gain' => 0,
            'max_speed' => 8,
            'timezone' => 'Europe/London',
            'kudos_count' => 0,
            'comment_count' => 0,
            'endo_id' => NULL,
            'message' => 'Ride with id <a target="_blank" href="activityUrl"></a> on 2016-01-06, distance 3.3 miles/5.3 kms. '
        , 'description' => 'activityUrl'
        );
        $strava->expects($this->once())->method('addRide')->with('2016-01-06', $ride, $points)->willReturn(true);

        $strava->expects($this->any())->method('waitForPendingUploads')->willReturn(array(
            'endomondo_2859253_674115438' =>
                (object)(array(
                    'message' => 'Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms. ',
                    'external_id' => 'endomondo_2859253_674115438',
                    'file' => '/tmp/endomondo+674115438.gpx',
                    'status' => 'Your activity is ready.',
                    'strava_id' => 501116906,
                ))));
        $endomondo->expects($this->any())->method('getPoints')->willReturn($points);

        $this->setProperty('endomondo', $endomondo, $this->mainPage);
        $this->setProperty('strava', $strava, $this->mainPage);
        $this->output = "";
        $strava->expects($this->any())->method('getRides')->willReturn($oneLessRides);
        $strava->expects($this->any())->method('bikeMatch')->willReturn("b121212");
        $endomondo->expects($this->any())->method('getRides')->willReturn($rides);
        $ride = array('distance' => 5268.5, 'name' => 'Evening Ride',
            'start_time' => '2016-01-06T19:44:25Z', 'moving_time' => 1210,
            'elapsed_time' => 3833, 'total_elevation_gain' => 0, 'max_speed' => 8,
            'timezone' => 'Europe/London', 'kudos_count' => 0, 'comment_count' => 0,
            'endo_id' => NULL, 'bike' => 'b121212',
            'strava_id' => 464768505,
            'message' => 'Ride with id <a target="_blank" href="activityUrl"></a> on 2016-01-06, distance 3.3 miles/5.3 kms. '
        , 'description' => 'activityUrl'
        );
        $strava->expects($this->any())->method('addRide')->with('2016-01-06', $ride, $points)->willReturnOnConsecutiveCalls('', '', 123456);
        $this->output = "";
        $this->assertEquals("<br>1 rides added.<br>", $copy->invokeArgs($this->mainPage, array("Endomondo", "Strava", null, null, false)));
        $this->assertEquals("<H3>Copying data from Endomondo to Strava...</H3>.Ride with id <a target=\"_blank\" href=\"activityUrl\"></a> on 2016-01-06, distance 3.3 miles/5.3 kms. Queued for upload.<br><br>Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms.  Uploaded successfully, id: <a target=\"_blank\" href=\"\">501116906</a>.", $this->output);
        //cleanup
        $strava = $this->getMockBuilder('trackerAbstract')->setMethods(array('getRides', 'getBike', 'bikeMatch', 'addRide'))->getMock();
        $this->setProperty('strava', $strava, $this->mainPage);
        $strava->expects($this->any())->method('getError')->willReturn(null);

        //STRAVA is missing a whole day that's in endomondo
        $endomondo = $builder->getMock();
        $strava = $builder->getMock();
        $ride = array(
            'distance' => 2975.5,
            'name' => 'Afternoon Ride',
            'start_time' => '2016-01-08T13:20:00Z',
            'moving_time' => 599,
            'elapsed_time' => 599,
            'total_elevation_gain' => 0,
            'max_speed' => 5.5,
            'timezone' => 'Europe/London',
            'kudos_count' => 0,
            'comment_count' => 0,
            'endo_id' => NULL, 'bike' => 'b121212',
            'strava_id' => 470171383,
            'message' => 'Ride with id <a target="_blank" href="activityUrl"></a> on 2016-01-08, distance 1.8 miles/3 kms. '
        , 'description' => 'activityUrl'
        );
        $strava->expects($this->at(3))->method('addRide')->with("2016-01-08", $ride, $points)->willReturn(true);
        $ride = array(
            'distance' => 2919,
            'name' => 'Evening Ride',
            'start_time' => '2016-01-08T20:30:00Z',
            'moving_time' => 600,
            'elapsed_time' => 600,
            'total_elevation_gain' => 0,
            'max_speed' => 5.2000000000000002,
            'timezone' => 'Europe/London',
            'kudos_count' => 0,
            'comment_count' => 0,
            'endo_id' => null,
            'bike' => 'b121212',
            'strava_id' => 470166379,
            'message' => 'Ride with id <a target="_blank" href="activityUrl"></a> on 2016-01-08, distance 1.8 miles/2.9 kms. ',
            'description' => 'activityUrl'
        );
        $strava->expects($this->at(5))->method('addRide')->with("2016-01-08", $ride, $points)->willReturn(true);

        $endomondo->expects($this->any())->method('activityUrl')->willReturn("activityUrl");
        $strava->expects($this->any())->method('waitForPendingUploads')->willReturn(array(
            'endomondo_2859253_674115438' =>
                (object)(array(
                    'message' => 'Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms. ',
                    'external_id' => 'endomondo_2859253_674115438',
                    'file' => '/tmp/endomondo+674115438.gpx',
                    'status' => 'Your activity is ready.',
                    'strava_id' => 501116906,
                )), 'endomondo_2859253_6741154380' =>
                (object)(array(
                    'message' => 'Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms. ',
                    'external_id' => 'endomondo_2859253_674115438',
                    'file' => '/tmp/endomondo+674115438.gpx',
                    'status' => 'Your activity is ready.',
                    'strava_id' => 501116906,
                ))));
        $endomondo->expects($this->any())->method('getPoints')->willReturn($points);

        $this->setProperty('endomondo', $endomondo, $this->mainPage);
        $this->setProperty('strava', $strava, $this->mainPage);
        $this->output = "";
        $strava->expects($this->any())->method('getRides')->willReturn($oneDayLessRides);
        $strava->expects($this->any())->method('bikeMatch')->willReturn("b121212");
        $endomondo->expects($this->any())->method('getRides')->willReturn($rides);
        $this->output = "";
        $this->assertEquals("<br>2 rides added.<br>", $copy->invokeArgs($this->mainPage, array("Endomondo", "Strava", null, null, false)));
        $this->assertEquals("<H3>Copying data from Endomondo to Strava...</H3>Ride with id <a target=\"_blank\" href=\"activityUrl\"></a> on 2016-01-08, distance 1.8 miles/3 kms. Queued for upload.<br>Ride with id <a target=\"_blank\" href=\"activityUrl\"></a> on 2016-01-08, distance 1.8 miles/2.9 kms. Queued for upload.<br><br>Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms.  Uploaded successfully, id: <a target=\"_blank\" href=\"\">501116906</a>.<br>Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms.  Uploaded successfully, id: <a target=\"_blank\" href=\"\">501116906</a>.", $this->output);

        //They have the same number of rides on the same dates, but one is shorter on STRAVA
        $endomondo = $builder->getMock();
        $strava = $builder->getMock();
        $ride = array(
            'distance' => 7124.799999999999,
            'name' => 'Afternoon Ride',
            'strava_id' => 494647884,
            'start_time' => '2016-01-01T15:59:17Z',
            'bike' => 'b121212',
            'moving_time' => 3800,
            'elapsed_time' => 6897,
            'total_elevation_gain' => 114.90000000000001,
            'max_speed' => 8.9000000000000004,
            'timezone' => 'Europe/London',
            'kudos_count' => 0,
            'comment_count' => 0,
            'endo_id' => 650970286,
            'message' => 'Ride with id <a target="_blank" href="activityUrl">650970286</a> on 2016-01-01, distance 4.4 miles/7.1 kms. ', 'description' => 'activityUrl'

        );
        $strava->expects($this->once())->method('addRide')->with("2016-01-01", $ride, $points)->willReturn(true);

        $endomondo->expects($this->any())->method('activityUrl')->willReturn("activityUrl");
        $strava->expects($this->any())->method('waitForPendingUploads')->willReturn(array(
            'endomondo_2859253_674115438' =>
                (object)(array(
                    'message' => 'Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms. ',
                    'external_id' => 'endomondo_2859253_674115438',
                    'file' => '/tmp/endomondo+674115438.gpx',
                    'status' => 'Your activity is ready.',
                    'strava_id' => 501116906,
                ))));
        $endomondo->expects($this->any())->method('getPoints')->willReturn($points);

        $this->setProperty('endomondo', $endomondo, $this->mainPage);
        $this->setProperty('strava', $strava, $this->mainPage);
        $this->output = "";
        $strava->expects($this->any())->method('getRides')->willReturn($oneShorterRides);
        $strava->expects($this->any())->method('bikeMatch')->willReturn("b121212");
        $endomondo->expects($this->any())->method('getRides')->willReturn($rides);
        $this->output = "";
        $this->assertEquals("<br>1 rides added.<br>", $copy->invokeArgs($this->mainPage, array("Endomondo", "Strava", null, null, false)));
        $this->assertEquals('<H3>Copying data from Endomondo to Strava...</H3>..Ride with id <a target="_blank" href="activityUrl">650970286</a> on 2016-01-01, distance 4.4 miles/7.1 kms. Queued for upload.<br><br>Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms.  Uploaded successfully, id: <a target="_blank" href="">501116906</a>.', $this->output);


        //No rides on STRAVA
        $endomondo = $builder->getMock();
        $strava = $builder->getMock();
        $ride = array(

            'distance' => 5031.1999999999998,
            'name' => 'Afternoon Ride',
            'strava_id' => 460839170,
            'start_time' => '2016-01-01T13:10:18Z',
            'bike' => null,
            'moving_time' => 920,
            'elapsed_time' => 1651,
            'total_elevation_gain' => 0,
            'max_speed' => 8.3000000000000007,
            'timezone' => 'Europe/London',
            'kudos_count' => 0,
            'comment_count' => 0,
            'endo_id' => null,
            'message' => 'Ride with id <a target="_blank" href="activityUrl"></a> on 2016-01-01, distance 3.1 miles/5 kms. ',
            'description' => 'activityUrl'
        );
        $strava->expects($this->at(3))->method('addRide')->with("2016-01-01", $ride, $points)->willReturn(true);
        $ride = array(
            'distance' => 3992.1999999999998,
            'name' => 'Afternoon Ride',
            'strava_id' => 460839481,
            'start_time' => '2016-01-01T14:32:17Z',
            'bike' => null,
            'moving_time' => 792,
            'elapsed_time' => 1566,
            'total_elevation_gain' => 0,
            'max_speed' => 8.5,
            'timezone' => 'Europe/London',
            'kudos_count' => 0,
            'comment_count' => 0,
            'endo_id' => null,
            'message' => 'Ride with id <a target="_blank" href="activityUrl"></a> on 2016-01-01, distance 2.5 miles/4 kms. ',
            'description' => 'activityUrl'
        );
        $strava->expects($this->at(5))->method('addRide')->with("2016-01-01", $ride, $points)->willReturn(true);
        $ride = array(

            'distance' => 17124.799999999999,
            'name' => 'Afternoon Ride',
            'strava_id' => 494647884,
            'start_time' => '2016-01-01T15:59:17Z',
            'bike' => null,
            'moving_time' => 3800,
            'elapsed_time' => 6897,
            'total_elevation_gain' => 114.90000000000001,
            'max_speed' => 8.9000000000000004,
            'timezone' => 'Europe/London',
            'kudos_count' => 0,
            'comment_count' => 0,
            'endo_id' => 650970286,
            'message' => 'Ride with id <a target="_blank" href="activityUrl">650970286</a> on 2016-01-01, distance 10.6 miles/17.1 kms. ',
            'description' => 'activityUrl',
        );

        $strava->expects($this->at(7))->method('addRide')->with("2016-01-01", $ride, $points)->willReturn(true);
        $ride = array(
            'distance' => 2313.0999999999999,
            'name' => 'Afternoon Ride',
            'strava_id' => 464768504,
            'start_time' => '2016-01-06T13:30:58Z',
            'bike' => null,
            'moving_time' => 732,
            'elapsed_time' => 8312,
            'total_elevation_gain' => 0,
            'max_speed' => 8,
            'timezone' => 'Europe/London',
            'kudos_count' => 0,
            'comment_count' => 0,
            'endo_id' => null,
            'message' => 'Ride with id <a target="_blank" href="activityUrl"></a> on 2016-01-06, distance 1.4 miles/2.3 kms. '
        , 'description' => 'activityUrl'
        );

        $strava->expects($this->at(9))->method('addRide')->with("2016-01-06", $ride, $points)->willReturn(true);
        $ride = array('distance' => 5268.5,
            'name' => 'Evening Ride',
            'strava_id' => 464768505,
            'start_time' => '2016-01-06T19:44:25Z',
            'bike' => null,
            'moving_time' => 1210,
            'elapsed_time' => 3833,
            'total_elevation_gain' => 0,
            'max_speed' => 8,
            'timezone' => 'Europe/London',
            'kudos_count' => 0,
            'comment_count' => 0,
            'endo_id' => null,
            'message' => 'Ride with id <a target="_blank" href="activityUrl"></a> on 2016-01-06, distance 3.3 miles/5.3 kms. ',
            'description' => 'activityUrl',
        );
        $strava->expects($this->at(11))->method('addRide')->with("2016-01-06", $ride, $points)->willReturn(true);
        $ride = array(
            'distance' => 2975.5,
            'name' => 'Afternoon Ride',
            'strava_id' => 470171383,
            'start_time' => '2016-01-08T13:20:00Z',
            'bike' => null,
            'moving_time' => 599,
            'elapsed_time' => 599,
            'total_elevation_gain' => 0,
            'max_speed' => 5.5,
            'timezone' => 'Europe/London',
            'kudos_count' => 0,
            'comment_count' => 0,
            'endo_id' => null,
            'message' => 'Ride with id <a target="_blank" href="activityUrl"></a> on 2016-01-08, distance 1.8 miles/3 kms. '
        , 'description' => 'activityUrl'
        );
        $strava->expects($this->at(13))->method('addRide')->with("2016-01-08", $ride, $points)->willReturn(true);
        $ride = array(
            'distance' => 2919,
            'name' => 'Evening Ride',
            'strava_id' => 470166379,
            'start_time' => '2016-01-08T20:30:00Z',
            'bike' => null,
            'moving_time' => 600,
            'elapsed_time' => 600,
            'total_elevation_gain' => 0,
            'max_speed' => 5.2000000000000002,
            'timezone' => 'Europe/London',
            'kudos_count' => 0,
            'comment_count' => 0,
            'endo_id' => null,
            'message' => 'Ride with id <a target="_blank" href="activityUrl"></a> on 2016-01-08, distance 1.8 miles/2.9 kms. ',
            'description' => 'activityUrl'
        );
        $strava->expects($this->at(15))->method('addRide')->with("2016-01-08", $ride, $points)->willReturn(true);
        $ride = array(
            'distance' => 3992.1999999999998,
            'name' => 'Afternoon Ride',
            'strava_id' => 460839481,
            'start_time' => '2016-01-01T14:32:17Z',
            'bike' => null,
            'moving_time' => 792,
            'elapsed_time' => 1566,
            'total_elevation_gain' => 0,
            'max_speed' => 8.5,
            'timezone' => 'Europe/London',
            'kudos_count' => 0,
            'comment_count' => 0,
            'endo_id' => null,
            'message' => 'Ride with id <a target="_blank" href="activityUrl"></a> on 2016-01-01T14:32:17Z, distance 2.5 miles/4 kms. ',
            'description' => 'activityUrl'
        );

        $endomondo->expects($this->any())->method('activityUrl')->willReturn("activityUrl");
        $strava->expects($this->any())->method('waitForPendingUploads')->willReturn(array(
            'endomondo_2859253_674115431' =>
                (object)(array(
                    'message' => 'Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms. ',
                    'external_id' => 'endomondo_2859253_674115438',
                    'file' => '/tmp/endomondo+674115438.gpx',
                    'status' => 'Your activity is ready.',
                    'strava_id' => 501116906,
                )),
            'endomondo_2859253_674115432' =>
                (object)(array(
                    'message' => 'Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms. ',
                    'external_id' => 'endomondo_2859253_674115438',
                    'file' => '/tmp/endomondo+674115438.gpx',
                    'status' => 'Your activity is ready.',
                    'strava_id' => 501116906,
                )), 'endomondo_2859253_674115433' =>
                (object)(array(
                    'message' => 'Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms. ',
                    'external_id' => 'endomondo_2859253_674115438',
                    'file' => '/tmp/endomondo+674115438.gpx',
                    'status' => 'Your activity is ready.',
                    'strava_id' => 501116906,
                )),
            'endomondo_2859253_674115434' =>
                (object)(array(
                    'message' => 'Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms. ',
                    'external_id' => 'endomondo_2859253_674115438',
                    'file' => '/tmp/endomondo+674115438.gpx',
                    'status' => 'Your activity is ready.',
                    'strava_id' => 501116906,
                )),
            'endomondo_2859253_674115435' =>
                (object)(array(
                    'message' => 'Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms. ',
                    'external_id' => 'endomondo_2859253_674115438',
                    'file' => '/tmp/endomondo+674115438.gpx',
                    'status' => 'Your activity is ready.',
                    'strava_id' => 501116906,
                )), 'endomondo_2859253_674115436' =>
                (object)(array(
                    'message' => 'Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms. ',
                    'external_id' => 'endomondo_2859253_674115438',
                    'file' => '/tmp/endomondo+674115438.gpx',
                    'status' => 'Your activity is ready.',
                    'strava_id' => 501116906,
                )),
            'endomondo_2859253_6741154387' =>
                (object)(array(
                    'message' => 'Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms. ',
                    'external_id' => 'endomondo_2859253_674115438',
                    'file' => '/tmp/endomondo+674115438.gpx',
                    'status' => 'Your activity is ready.',
                    'strava_id' => 501116906,
                ))
        ));
        $endomondo->expects($this->any())->method('getPoints')->willReturn($points);

        $this->setProperty('endomondo', $endomondo, $this->mainPage);
        $this->setProperty('strava', $strava, $this->mainPage);
        $this->output = "";
        $strava->expects($this->any())->method('getRides')->willReturn([]);
        $endomondo->expects($this->any())->method('getRides')->willReturn($shortListOfRides);
        $strava->expects($this->any())->method('addRide')->willReturn(true);
        $this->output = "";
        $this->assertEquals("<br>7 rides added.<br>", $copy->invokeArgs($this->mainPage, array("Endomondo", "Strava", null, null, false)));
        $this->assertEquals("<H3>Copying data from Endomondo to Strava...</H3>Ride with id <a target=\"_blank\" href=\"activityUrl\"></a> on 2016-01-01, distance 3.1 miles/5 kms. Queued for upload.<br>Ride with id <a target=\"_blank\" href=\"activityUrl\"></a> on 2016-01-01, distance 2.5 miles/4 kms. Queued for upload.<br>Ride with id <a target=\"_blank\" href=\"activityUrl\">650970286</a> on 2016-01-01, distance 10.6 miles/17.1 kms. Queued for upload.<br>Ride with id <a target=\"_blank\" href=\"activityUrl\"></a> on 2016-01-06, distance 1.4 miles/2.3 kms. Queued for upload.<br>Ride with id <a target=\"_blank\" href=\"activityUrl\"></a> on 2016-01-06, distance 3.3 miles/5.3 kms. Queued for upload.<br>Ride with id <a target=\"_blank\" href=\"activityUrl\"></a> on 2016-01-08, distance 1.8 miles/3 kms. Queued for upload.<br>Ride with id <a target=\"_blank\" href=\"activityUrl\"></a> on 2016-01-08, distance 1.8 miles/2.9 kms. Queued for upload.<br><br>Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms.  Uploaded successfully, id: <a target=\"_blank\" href=\"\">501116906</a>.<br>Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms.  Uploaded successfully, id: <a target=\"_blank\" href=\"\">501116906</a>.<br>Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms.  Uploaded successfully, id: <a target=\"_blank\" href=\"\">501116906</a>.<br>Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms.  Uploaded successfully, id: <a target=\"_blank\" href=\"\">501116906</a>.<br>Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms.  Uploaded successfully, id: <a target=\"_blank\" href=\"\">501116906</a>.<br>Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms.  Uploaded successfully, id: <a target=\"_blank\" href=\"\">501116906</a>.<br>Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms.  Uploaded successfully, id: <a target=\"_blank\" href=\"\">501116906</a>.",
            $this->output);

        //overnight rides
        $endomondo = $builder->getMock();
        $strava = $builder->getMock();
        $endomondo->expects($this->any())->method('activityUrl')->willReturn("activityUrl");
        $strava->expects($this->any())->method('waitForPendingUploads')->willReturn(array(
            'endomondo_2859253_674115431' =>
                (object)(array(
                    'message' => 'Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms. ',
                    'external_id' => 'endomondo_2859253_674115438',
                    'file' => '/tmp/endomondo+674115438.gpx',
                    'status' => 'Your activity is ready.',
                    'strava_id' => 501116906,
                )),
            'endomondo_2859253_674115432' =>
                (object)(array(
                    'message' => 'Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms. ',
                    'external_id' => 'endomondo_2859253_674115438',
                    'file' => '/tmp/endomondo+674115438.gpx',
                    'status' => 'Your activity is ready.',
                    'strava_id' => 501116906,
                )), 'endomondo_2859253_674115433' =>
                (object)(array(
                    'message' => 'Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms. ',
                    'external_id' => 'endomondo_2859253_674115438',
                    'file' => '/tmp/endomondo+674115438.gpx',
                    'status' => 'Your activity is ready.',
                    'strava_id' => 501116906,
                )),
            'endomondo_2859253_674115434' =>
                (object)(array(
                    'message' => 'Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms. ',
                    'external_id' => 'endomondo_2859253_674115438',
                    'file' => '/tmp/endomondo+674115438.gpx',
                    'status' => 'Your activity is ready.',
                    'strava_id' => 501116906,
                )),
            'endomondo_2859253_674115435' =>
                (object)(array(
                    'message' => 'Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms. ',
                    'external_id' => 'endomondo_2859253_674115438',
                    'file' => '/tmp/endomondo+674115438.gpx',
                    'status' => 'Your activity is ready.',
                    'strava_id' => 501116906,
                )), 'endomondo_2859253_674115436' =>
                (object)(array(
                    'message' => 'Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms. ',
                    'external_id' => 'endomondo_2859253_674115438',
                    'file' => '/tmp/endomondo+674115438.gpx',
                    'status' => 'Your activity is ready.',
                    'strava_id' => 501116906,
                )),
        ));
        $endomondo->expects($this->any())->method('getPoints')->willReturn($points);

        $this->setProperty('endomondo', $endomondo, $this->mainPage);
        $this->setProperty('strava', $strava, $this->mainPage);
        $_POST = array(
            'start_date' => '01-01-2015',
            'end_date' => '31-12-2015',
        );
        $this->output = "";
        $strava->expects($this->any())->method('getError')->willReturn(null);
        $endomondo->expects($this->any())->method('getError')->willReturn(null);
        $strava->expects($this->any())->method('getRides')->willReturn([]);
        $this->setProperty('strava', $strava, $this->mainPage);
        $endomondo->expects($this->any())->method('getRides')->willReturn($shortListOfRides);
        $endomondo->expects($this->any())->method('getOvernightActivities')->willReturn(include('data/input/overnightActivity.php'));
        $preferences->expects($this->any())->method('getEndomondoSplitRides')->willReturn(true);
        $strava->expects($this->any())->method('addRide')->willReturn(true);
        $this->output = "";
        $this->assertEquals("<br>6 rides added.<br>", $copy->invokeArgs($this->mainPage, array("Endomondo", "Strava", null, null, false)));
        $this->assertEquals("<H3>Copying data from Endomondo to Strava...</H3>Ride with id <a target=\"_blank\" href=\"activityUrl\"></a> on 2016-01-01, distance 3.1 miles/5 kms. Queued for upload.<br>Ride with id <a target=\"_blank\" href=\"activityUrl\"></a> on 2016-01-01, distance 2.5 miles/4 kms. Queued for upload.<br>Ride with id <a target=\"_blank\" href=\"activityUrl\">650970286</a> on 2016-01-01, distance 10.6 miles/17.1 kms. Queued for upload.<br>Ride with id <a target=\"_blank\" href=\"activityUrl\"></a> on 2016-01-06, distance 1.4 miles/2.3 kms. Queued for upload.<br>Ride with id <a target=\"_blank\" href=\"activityUrl\"></a> on 2016-01-06, distance 3.3 miles/5.3 kms. Queued for upload.<br>Ride with id <a target=\"_blank\" href=\"activityUrl\"></a> on 2016-01-08, distance 1.8 miles/3 kms. Queued for upload.<br>Ride with id <a target=\"_blank\" href=\"activityUrl\"></a> on 2016-01-08, distance 1.8 miles/2.9 kms. Queued for upload.<br><br>Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms.  Uploaded successfully, id: <a target=\"_blank\" href=\"\">501116906</a>.<br>Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms.  Uploaded successfully, id: <a target=\"_blank\" href=\"\">501116906</a>.<br>Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms.  Uploaded successfully, id: <a target=\"_blank\" href=\"\">501116906</a>.<br>Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms.  Uploaded successfully, id: <a target=\"_blank\" href=\"\">501116906</a>.<br>Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms.  Uploaded successfully, id: <a target=\"_blank\" href=\"\">501116906</a>.<br>Ride with id 674115438 on 2016-02-18 15:54:22 UTC, distance 1.9 miles/3.1 kms.  Uploaded successfully, id: <a target=\"_blank\" href=\"\">501116906</a>.",
            $this->output);


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
        $this->assertEquals(array(2, 3, 4, 5, 10, 50, 100), $nextGoals->invokeArgs($this->mainPage, array(1)));
        $this->assertEquals(array(51, 52, 53, 55, 60, 100), $nextGoals->invokeArgs($this->mainPage, array(50)));
        $this->assertEquals(array(104, 105, 106, 110, 150, 200), $nextGoals->invokeArgs($this->mainPage, array(103)));
        $this->assertEquals(array(50, 51, 52, 55, 60, 100), $nextGoals->invokeArgs($this->mainPage, array(49)));
        $this->assertEquals(array(108, 109, 110, 115, 120, 150, 200), $nextGoals->invokeArgs($this->mainPage, array(107)));
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
            ->setMethods(array('getUserId', 'getRides', 'getError', 'getOvernightActivities',
                'setUseFeetForElevation', 'setSplitOvernightRides', 'setWriteScope', 'setAuth', 'setAccessToken',
                'isConnected'))->getMock();
        $mockTracker->expects($this->any())->method('isConnected')->willReturn(true);

        $mockPreferences = $this->getMockBuilder('Preferences')->disableOriginalConstructor()
            ->setMethods(array('getTimezone', 'getSplitRides', 'getMclUseFeet',
                'getMclUsername', 'getStravaWriteScope', 'getMclAuth', 'getEndoAuth', 'getRwgpsAuth', 'getStravaAccessToken',
                'setSplitRides'
            ))->getMock();
        $mockPreferences->expects($this->any())->method('getTimezone')->willReturn('UTC');


        $this->setProperty('preferences', $mockPreferences, $this->mainPage);
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
        $this->assertEquals(490216193, $isDuplicateRide->invokeArgs($this->mainPage, array($ride, include("data/input/duplicateCandidateRides.php"), 'endo_id')));

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
        $this->assertFalse($isDuplicateRide->invokeArgs($this->mainPage, array($ride, include("data/input/duplicateCandidateRides.php"), 'endo_id')));

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
        $this->assertTrue($isDuplicateRide->invokeArgs($this->mainPage, array($ride, include("data/input/duplicateCandidateRides.php"), 'endo_id')));

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
        $this->assertTrue($isDuplicateRide->invokeArgs($this->mainPage, array($ride, include("data/input/duplicateCandidateRides.php"), 'endo_id')));

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
        $this->assertTrue($isDuplicateRide->invokeArgs($this->mainPage, array($ride, include("data/input/duplicateCandidateRides.php"), 'endo_id')));


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
        $this->assertTrue($isDuplicateRide->invokeArgs($this->mainPage, array($ride, include("data/input/duplicateCandidateRides.php"), 'endo_id')));


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

        $this->assertTrue($isDuplicateRide->invokeArgs($this->mainPage, array($ride, include("data/input/duplicateCandidateRides.php"), 'endo_id')));

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
        $this->assertTrue($isDuplicateRide->invokeArgs($this->mainPage, array($ride, include("data/input/duplicateCandidateRides.php"), 'endo_id')));


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
        $this->assertFalse($isDuplicateRide->invokeArgs($this->mainPage, array($ride, include("data/input/duplicateCandidateRides.php"), 'endo_id')));

        //the above, starting 2 seconds later
        $ride['start_time'] = '2016-02-08T15:26:51 UTC';
        $this->assertTrue($isDuplicateRide->invokeArgs($this->mainPage, array($ride, include("data/input/duplicateCandidateRides.php"), 'endo_id')));


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
            ->setMethods(array('getUserId', 'getRides', 'getError', 'getOvernightActivities',
                'setUseFeetForElevation', 'setSplitOvernightRides', 'setWriteScope', 'setAuth', 'setAccessToken'))->getMock();
        $rides = include('data/input/getRides.php');
        $mockTracker->expects($this->any())->method('getRides')->willReturn($rides);
        $mockPreferences = $this->getMockBuilder('Preferences')->disableOriginalConstructor()
            ->setMethods(array('getTimezone', 'getStravaSplitRides', 'getEndoSplitRides', 'getRwgpsSplitRides', 'getMclUseFeet',
                'getMclUsername', 'getStravaWriteScope', 'getMclAuth', 'getEndoAuth', 'getRwgpsAuth', 'getStravaAccessToken',
                'setStravaSplitRides'
            ))->getMock();
        $mockPreferences->expects($this->any())->method('getTimezone')->willReturn('UTC');

        $this->setProperty('preferences', $mockPreferences, $this->mainPage);
        $this->setProperty('strava', $mockTracker, $this->mainPage);
        $this->setProperty('myCyclingLog', $mockTracker, $this->mainPage);
        $this->setProperty('endomondo', $mockTracker, $this->mainPage);
        $this->setProperty('rideWithGps', $mockTracker, $this->mainPage);
        $elapsed_days = round((time() - strtotime("2016-01-01T00:00:00Z")) / (60 * 60 * 24));
        $start_date = 'the beginning';
        $setup->invokeArgs($this->mainPage, array());  // need to call this with each case, because start_time/end_time variables change.  They probably shouldn't
        $source = 'Strava';
        $this->assertEquals(include('data/expected/calculateFromSourceNoEndDate.php'), $execute->invokeArgs($this->mainPage, array("calculate_from_strava")));
        $setup->invokeArgs($this->mainPage, array());
        $source = 'MyCyclingLog';
        $this->assertEquals(include('data/expected/calculateFromSourceNoEndDate.php'), $execute->invokeArgs($this->mainPage, array("calculate_from_mcl")));
        $setup->invokeArgs($this->mainPage, array());
        $source = 'Endomondo';
        $this->assertEquals(include('data/expected/calculateFromSourceNoEndDate.php'), $execute->invokeArgs($this->mainPage, array("calculate_from_endo")));
        $setup->invokeArgs($this->mainPage, array());
        $source = 'RideWithGPS';
        $this->assertEquals(include('data/expected/calculateFromSourceNoEndDate.php'), $execute->invokeArgs($this->mainPage, array("calculate_from_rwgps")));
        $_POST = array(
            'start_date' => '01-01-2015',
            'end_date' => null,
        );
        $start_date = '01-01-2015';
        $elapsed_days = round((time() - strtotime("2015-01-01T00:00:00Z")) / (60 * 60 * 24));
        $setup->invokeArgs($this->mainPage, array());
        $source = 'RideWithGPS';
        $this->assertEquals(include('data/expected/calculateFromSourceNoEndDate.php'), $execute->invokeArgs($this->mainPage, array("calculate_from_rwgps")));
        $_POST = array(
            'start_date' => '01-01-2015',
            'end_date' => '31-12-2015',
        );
        $start_date = '01-01-2015';
        $end_date = '31-12-2015';
        $elapsed_days = 365;
        $setup->invokeArgs($this->mainPage, array());
        $source = 'RideWithGPS';
        $this->assertEquals(include('data/expected/calculateFromSourceWithEndDate.php'), $execute->invokeArgs($this->mainPage, array("calculate_from_rwgps")));
        $_POST = array(
            'end_date' => '10-02-2016'
        );
        $start_date = 'the beginning';
        $end_date = '10-02-2016';
        $elapsed_days = 41;
        $setup->invokeArgs($this->mainPage, array());
        $source = 'RideWithGPS';
        $this->assertEquals(include('data/expected/calculateFromSourceWithEndDate.php'), $execute->invokeArgs($this->mainPage, array("calculate_from_rwgps")));
    }


    public function testIsConnected()
    {
        $isConnected = $this->getMethod('isConnected');

        $builder = $this->getMockBuilder('TrackerAbstract')
            ->setMethods(array('isConnected'));
        $mockConnected = $builder->getMock();
        $mockConnected->expects($this->any())->method('isConnected')->willReturn(true);
        $mockNotConnected = $builder->getMock();
        $mockNotConnected->expects($this->any())->method('isConnected')->willReturn(false);

        // none connected
        $this->setProperty('strava', $mockNotConnected, $this->mainPage);
        $this->setProperty('myCyclingLog', $mockNotConnected, $this->mainPage);
        $this->setProperty('endomondo', $mockNotConnected, $this->mainPage);
        $this->setProperty('rideWithGps', $mockNotConnected, $this->mainPage);
        $this->assertFalse($isConnected->invokeArgs($this->mainPage, array()));


        // one connected
        $this->setProperty('strava', $mockConnected, $this->mainPage);
        $this->setProperty('myCyclingLog', $mockNotConnected, $this->mainPage);
        $this->setProperty('endomondo', $mockNotConnected, $this->mainPage);
        $this->setProperty('rideWithGps', $mockNotConnected, $this->mainPage);
        $this->assertTrue($isConnected->invokeArgs($this->mainPage, array()));


        $this->setProperty('strava', $mockNotConnected, $this->mainPage);
        $this->setProperty('myCyclingLog', $mockConnected, $this->mainPage);
        $this->setProperty('endomondo', $mockNotConnected, $this->mainPage);
        $this->setProperty('rideWithGps', $mockNotConnected, $this->mainPage);
        $this->assertTrue($isConnected->invokeArgs($this->mainPage, array()));


        $this->setProperty('strava', $mockNotConnected, $this->mainPage);
        $this->setProperty('myCyclingLog', $mockNotConnected, $this->mainPage);
        $this->setProperty('endomondo', $mockConnected, $this->mainPage);
        $this->setProperty('rideWithGps', $mockNotConnected, $this->mainPage);
        $this->assertTrue($isConnected->invokeArgs($this->mainPage, array()));


        $this->setProperty('strava', $mockNotConnected, $this->mainPage);
        $this->setProperty('myCyclingLog', $mockNotConnected, $this->mainPage);
        $this->setProperty('endomondo', $mockNotConnected, $this->mainPage);
        $this->setProperty('rideWithGps', $mockConnected, $this->mainPage);
        $this->assertTrue($isConnected->invokeArgs($this->mainPage, array()));


        // all connected
        $this->setProperty('strava', $mockConnected, $this->mainPage);
        $this->setProperty('myCyclingLog', $mockConnected, $this->mainPage);
        $this->setProperty('endomondo', $mockConnected, $this->mainPage);
        $this->setProperty('rideWithGps', $mockConnected, $this->mainPage);
        $this->assertTrue($isConnected->invokeArgs($this->mainPage, array()));


        //2 connected
        $this->setProperty('strava', $mockNotConnected, $this->mainPage);
        $this->setProperty('myCyclingLog', $mockConnected, $this->mainPage);
        $this->setProperty('endomondo', $mockNotConnected, $this->mainPage);
        $this->setProperty('rideWithGps', $mockConnected, $this->mainPage);
        $this->assertTrue($isConnected->invokeArgs($this->mainPage, array()));


        //3 connected
        $this->setProperty('strava', $mockNotConnected, $this->mainPage);
        $this->setProperty('myCyclingLog', $mockConnected, $this->mainPage);
        $this->setProperty('endomondo', $mockConnected, $this->mainPage);
        $this->setProperty('rideWithGps', $mockConnected, $this->mainPage);
        $this->assertTrue($isConnected->invokeArgs($this->mainPage, array()));


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
            ->setMethods(array('getUserId', 'getRides', 'getError', 'getOvernightActivities',
                'setUseFeetForElevation', 'setSplitOvernightRides', 'setWriteScope', 'setAuth', 'setAccessToken',
                'isConnected', 'writeScope'))->getMock();
        $mockTracker->expects($this->any())->method('isConnected')->willReturn(true);
        $mockTracker->expects($this->any())->method('writeScope')->willReturn(true);
        $mockPreferences = $this->getMockBuilder('Preferences')->disableOriginalConstructor()
            ->setMethods(array('getTimezone', 'getStravaSplitRides', 'getEndoSplitRides', 'getRwgpsSplitRides', 'getMclUseFeet',
                'getMclUsername', 'getStravaWriteScope', 'getMclAuth', 'getEndoAuth', 'getRwgpsAuth', 'getStravaAccessToken',
                'setStravaSplitRides'))->getMock();
        $mockPreferences->expects($this->any())->method('getTimezone')->willReturn('UTC');
        $mainForm = $this->getMethod('mainForm');
        $this->setProperty('preferences', $mockPreferences, $this->mainPage);
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

