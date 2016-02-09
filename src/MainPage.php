<?php
set_include_path(get_include_path() . PATH_SEPARATOR . "..");

require_once 'local.php';
require_once 'MainPage.php';
require_once 'src/JoanMcGalliard/EddingtonAndMore/Strava.php';
require_once 'src/JoanMcGalliard/EddingtonAndMore/MyCyclingLog.php';
require_once 'src/JoanMcGalliard/EddingtonAndMore/Endomondo.php';
require_once 'src/JoanMcGalliard/EddingtonAndMore/RideWithGps.php';
require_once 'src/JoanMcGalliard/EddingtonAndMore/Points.php';
require_once 'src/Preferences.php';

class MainPage
{
    private $echoCallback;
    private $noEcho = true;
    private $info_message = "";
    private $error_message = "";


    const METRE_TO_MILE = 0.00062137119224;
    const METRE_TO_KM = 0.001;
    const TWENTY_FOUR_HOURS = 86400;
    private $preferences;
    private $myCyclingLog;
    private $endomondo;
    private $rideWithGps;
    private $strava;
    private $here;
    private $start_date;
    private $end_date;
    private $isConnected;
    private $connectedToAll;

    /**
     * MainPage constructor.
     * @param $echoCallback
     */
    public function __construct($echoCallback)
    {
        ob_implicit_flush();
        $this->noEcho = true;
        $this->echoCallback = $echoCallback;
    }

    public function render()
    {
        $this->noEcho = true;
        $state = $this->setup();
        $this->noEcho = false;

        $this->output($this->topOfPage());
        $this->output($this->execute($state));
        $this->output($this->mainForm());
        $this->output($this->notes());
        $this->output($this->connections());
        $this->output($this->email());
        $this->output($this->bottomOfPage());

    }

    public function output($msg)
    {
        if (!$this->noEcho) {
            call_user_func($this->echoCallback, $msg);
        }
    }

    private function setup()
    {
        global $stravaClientId, $stravaClientSecret, $deviceId, $googleApiKey, $scratchDirectory, $workingEmailAddress;

        set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . DIRECTORY_SEPARATOR . "src" . PATH_SEPARATOR);

        date_default_timezone_set("UTC");
        $this->info_message = "";
        $this->error_message = "";


        $this->here = "http://$_SERVER[HTTP_HOST]$_SERVER[SCRIPT_NAME]";
        $state = null;

        $this->preferences = new Preferences();
        $this->start_date = null;
        $this->end_date = null;
        if (isset($_POST["start_date"]) && $_POST["start_date"] <> '') {
            $this->start_date = strtotime($_POST["start_date"]);
        }
        if (isset($_POST["end_date"]) && $_POST["end_date"] <> "") {
            $this->end_date = strtotime($_POST["end_date"] . " 23:59:59");
        }
        if (array_key_exists("tz", $_POST)) {
            $this->preferences->setTimezone($_POST["tz"]);
        }
        if (array_key_exists("calculate_from_endo", $_POST)) {
            $this->preferences->setEndoSplitRides(array_key_exists("endo_split_rides", $_POST));
        }
        if (array_key_exists("calculate_from_rwgps", $_POST)) {
            $this->preferences->setRwgpsSplitRides(array_key_exists("rwgps_split_rides", $_POST));
        }
        if (array_key_exists("calculate_from_strava", $_POST) || array_key_exists("copy_strava_to_mcl", $_POST)) {
            $this->preferences->setStravaSplitRides((array_key_exists("strava_split_rides", $_POST)));
        }


        if (array_key_exists("clear_cookies", $_POST)) {
            $this->preferences->clear();
            unset($_GET["code"]);
            unset($_GET["state"]);
        }

        $this->strava = new JoanMcGalliard\EddingtonAndMore\Strava($stravaClientId, $stravaClientSecret, array($this, 'output'));
        $this->myCyclingLog = new JoanMcGalliard\EddingtonAndMore\MyCyclingLog(array($this, 'output'));
        $this->endomondo = new JoanMcGalliard\EddingtonAndMore\Endomondo($deviceId, $googleApiKey, $this->preferences->getTimezone(), array($this, 'output'));
        $this->rideWithGps = new JoanMcGalliard\EddingtonAndMore\RideWithGps($googleApiKey, array($this, 'output'));


        $this->myCyclingLog->setUseFeetForElevation($this->preferences->getMclUseFeet());
        $this->endomondo->setSplitOvernightRides($this->preferences->getEndoSplitRides());
        $this->rideWithGps->setSplitOvernightRides($this->preferences->getRwgpsSplitRides());
        $this->strava->setSplitOvernightRides($this->preferences->getStravaSplitRides());
        $this->strava->setWriteScope($this->preferences->getStravaWriteScope());


        if (array_key_exists("login_mcl", $_POST)) {
            $mcl_username = $_POST['username'];
            $mcl_password = $_POST['password'];
            $auth = base64_encode("$mcl_username:$mcl_password");
            $this->myCyclingLog->setAuth("$auth");
            if ($this->myCyclingLog->isConnected()) {
                $this->preferences->setMclAuth($auth);
                $this->preferences->setMclUsername($_POST['username']);
            } else {
                $this->error_message = "There was a problem connecting to MyCyclingLog, please try again";
            }
        } else if ($this->preferences->getMclAuth()) {
            $this->myCyclingLog->setAuth($this->preferences->getMclAuth());
        }
        if (array_key_exists("login_endo", $_POST)) {
            $endo_username = $_POST['username'];
            $endo_password = $_POST['password'];
            $auth = $this->endomondo->connect($endo_username, $endo_password);
            if ($this->endomondo->isConnected()) {
                $this->preferences->setEndoAuth($auth);
            } else {
                $this->error_message = "There was a problem connecting to Endomondo, please try again.<br>(" . $this->endomondo->getError() . ")";
            }
        } else if ($this->preferences->getEndoAuth() != null) {
            $this->endomondo->setAuth($this->preferences->getEndoAuth());
        }
        if (array_key_exists("login_rwgps", $_POST)) {
            $rwgps_username = $_POST['username'];
            $rwgps_password = $_POST['password'];
            $auth = $this->rideWithGps->connect($rwgps_username, $rwgps_password);
            if ($this->rideWithGps->isConnected()) {
                $this->preferences->setRwgpsAuth($auth);
            } else {
                $this->error_message = "There was a problem connecting to RideWithGps, please try again.<br>(" . $this->rideWithGps->getError() . ")";
            }
        } else if ($this->preferences->getRwgpsAuth() != null) {
            $this->rideWithGps->setAuth($this->preferences->getRwgpsAuth());
        }
        if (array_key_exists("state", $_GET)) {
            if (!array_key_exists("error", $_GET) && array_key_exists("code", $_GET)) {
                $code = $_GET["code"];
                $token = $this->strava->setAccessTokenFromCode($code);
                if ($this->strava->isConnected()) {
                    $this->strava->setWriteScope(($_GET["state"] == "write"));
                    $this->preferences->setStravaWriteScope($this->strava->writeScope());
                    $this->preferences->setStravaAccessToken($token);
                }
            } else {
                $this->error_message .= 'There was a problem connecting to strava, please try again: ' . $_GET["error"] . " ";
            }
            if ($this->strava->getError()) {
                $this->error_message .= 'There was a problem connecting to strava, please try again: ' . $this->strava->getError() . " ";

            }
            unset($_GET["state"]);
            unset($_GET["code"]);
            unset($_GET["error"]);
        } else if ($this->preferences->getStravaAccessToken() != null) {
            $this->strava->setAccessToken($this->preferences->getStravaAccessToken());
        }

        if (array_key_exists("delete_files", $_POST) && $this->strava->isConnected()) {
            $files = scandir($scratchDirectory);
            $pattern = '/^' . $this->strava->getUserId() . "-.*\.gpx$/";
            $count = 0;
            foreach ($files as $file) {
                if (preg_match($pattern, $file, $match) > 0) {
                    unlink($scratchDirectory . DIRECTORY_SEPARATOR . $file);
                    $count++;
                }
            }
            $this->output("Deleted $count files.<br>");
        }

        if (array_key_exists("calculate_from_strava", $_POST) && $this->strava->isConnected()) {
            $state = "calculate_from_strava";
        } else if (array_key_exists("calculate_from_mcl", $_POST) && $this->myCyclingLog->isConnected()) {
            $state = "calculate_from_mcl";
        } else if (array_key_exists("calculate_from_endo", $_POST) && $this->endomondo->isConnected()) {
            $state = "calculate_from_endo";
        } else if (array_key_exists("calculate_from_rwgps", $_POST) && $this->rideWithGps->isConnected()) {
            $state = "calculate_from_rwgps";
        } else if (array_key_exists("copy_strava_to_mcl", $_POST) && $this->myCyclingLog->isConnected() && $this->strava->isConnected()) {
            $state = "copy_strava_to_mcl";
            if (array_key_exists("elevation_units", $_POST)) {
                $this->preferences->setMclUseFeet(true);
                $this->myCyclingLog->setUseFeetForElevation(true);
            } else {
                $this->preferences->setMclUseFeet(false);
                $this->myCyclingLog->setUseFeetForElevation(false);
            }

        } else if (array_key_exists("copy_endo_to_strava", $_POST) && $this->endomondo->isConnected() && $this->strava->isConnected()) {
            $state = "copy_endo_to_strava";
        } else if (array_key_exists("copy_endo_to_rwgps", $_POST) && $this->endomondo->isConnected() && $this->rideWithGps->isConnected()) {
            $state = "copy_endo_to_rwgps";
        } else if (array_key_exists("delete_mcl_rides", $_POST) && $this->myCyclingLog->isConnected()) {
            $state = "delete_mcl_rides";
        }
        if (isset($_POST['commentSend'])) {
            mail("$workingEmailAddress", "eddington enquiry",
                $_POST['commentComments'], "From: " . $_POST['commentRealName'] . "<"
                . $_POST['commentEmail'] . ">\r\n");
            $this->info_message = "Thanks.  Email sent.  " .
                " Name: " . $_POST['commentRealName'] .
                " Email: " . $_POST['commentEmail'] .
                " Comment: " . $_POST['commentComments'] . "";
        }
        $this->isConnected = $this->myCyclingLog->isConnected() || $this->strava->isConnected() || $this->rideWithGps->isConnected() || $this->endomondo->isConnected();
        $this->connectedToAll = $this->strava->isConnected() && $this->myCyclingLog->isConnected() && $this->endomondo->isConnected() && $this->strava->writeScope();

        return $state;
    }

    private function execute($state)
    {
        $str = "";
        global $scratchDirectory, $maxKmFileUploads;
        $preferences = $this->preferences;
        /** @var Preferences $preferences */
        $strava = $this->strava;
        /** @var \JoanMcGalliard\EddingtonAndMore\Strava $strava */
        $myCyclingLog = $this->myCyclingLog;
        /** @var \JoanMcGalliard\EddingtonAndMore\MyCyclingLog $myCyclingLog */
        $endomondo = $this->endomondo;
        /** @var \JoanMcGalliard\EddingtonAndMore\Endomondo $endomondo */
        $rideWithGps = $this->rideWithGps;
        /** @var \JoanMcGalliard\EddingtonAndMore\RideWithGps $rideWithGps */
        $source = "we should never see this value!";

        if ($state == "calculate_from_strava" || $state == "calculate_from_mcl" || $state == "calculate_from_endo" || $state == "calculate_from_rwgps") {
            set_time_limit(300);
            $this->output("<H3>Calculating....</H3>");
            date_default_timezone_set($preferences->getTimezone());
            $start_text = "the beginning";
            $end_text = "today";
            $activities = [];
            $timestamp = time();
            if ($this->start_date) $start_text = $_POST["start_date"];
            if ($this->end_date) $end_text = $_POST["end_date"];
            if ($state == "calculate_from_strava") {

                $this->processUploadedGpxFiles($strava->getUserId(), $scratchDirectory);


                $source = "Strava";
                $activities = $strava->getRides($this->start_date, $this->end_date);
                if ($strava->getError()) {
                    return ("<br><span style=\"color:red;\">There was a problem getting data from $source.</span><br><em>"
                        . $strava->getError()
                        . "</em>");
                }

                $overnight_rides = $strava->getOvernightActivities();
                if ($preferences->getStravaSplitRides() && $overnight_rides) {
                    $str .= $this->askForStravaGpx($overnight_rides, $maxKmFileUploads, "calculate_from_strava", "recalculate your E-Number");

                }
            } else if ($state == "calculate_from_mcl") {
                $source = "MyCyclingLog";
                $activities = $myCyclingLog->getRides($this->start_date, $this->end_date);
            } else if ($state == "calculate_from_endo") {
                $source = "Endomondo";
                $activities = $endomondo->getRides($this->start_date, $this->end_date);
                $error = $endomondo->getError();
                if ($error) {
                    return ("There was a problem getting data from $source:<br>" . $error);
                }
            } else if ($state == "calculate_from_rwgps") {
                $source = "RideWithGPS";
                $activities = $rideWithGps->getRides($this->start_date, $this->end_date);
                $error = $rideWithGps->getError();
                if ($error) {
                    return ("There was a problem getting data from $source:<br>" . $error);
                }
            }
            if (!$this->start_date) {
                $this->start_date = strtotime(array_keys($activities)[sizeof($activities) - 1]);
            }
            if (!$this->end_date) {
                $this->end_date = time();
            }
            $days = $this->sumActivities($activities);
            $str .= "<p>According to $source, for the period from $start_text to $end_text, "
                . round(($this->end_date - $this->start_date) / self::TWENTY_FOUR_HOURS)
                . " elapsed days</p>\r\n";
            uasort($days, function ($a, $b) {
                if ($a == $b) return 0; else return ($a > $b) ? -1 : 1;
            });

            $eddington_imperial = $this->calculateEddington($days, $result, self::METRE_TO_MILE);

            $table_imperial = '<table id="imperial" class="w3-table-all w3-right-align"  style="width:60%"><tr><th>Count</th><th>Date </th><th class="w3-right-align">Distance</th></tr>';
            for ($i = 1; $i <= $eddington_imperial; $i++) {
                $day = array_keys($result)[$i - 1];
                $actual_distance = $result[$day];
                $table_imperial .= "<tr><td> $i </td><td> $day</td><td class=\"w3-right-align\">$actual_distance miles</td></tr>";
            }
            $table_imperial .= "</table>";
            $str .= "<br><a href=\"#imperial\">Your imperial Eddington Number</a> is <strong>$eddington_imperial</strong>.<br>\r\n";
            if ($end_text == "today") {
                $goals = $this->next_goals($eddington_imperial);
                foreach ($goals as $goal) {
                    $num = $this->number_of_days_to_goal($goal, $days, self::METRE_TO_MILE);
                    $str .= "You need to do $num ride(s) of at least $goal to increase it to $goal.<br>\r\n";
                }
            }
            $eddington_metric = $this->calculateEddington($days, $result, self::METRE_TO_KM);
            $table_metric = '<table id="metric" class="w3-table-all w3-right-align"  style="width:60%"><tr><th>Count</th><th>Date </th><th class="w3-right-align">Distance</th></tr>';
            for ($i = 1; $i <= $eddington_metric; $i++) {
                $date = array_keys($result)[$i - 1];
                $distance = $result[$date];
                $table_metric .= "<tr><td> $i </td><td>$date</td><td class=\"w3-right-align\">$distance km</td></tr>";

            }

            $table_metric .= "</table>";
            $str .= "<br><a href=\"#metric\">Your metric Eddington Number</a> is <strong>$eddington_metric</strong><br>\r\n";
            if ($end_text == "today") {
                $goals = $this->next_goals($eddington_metric);
                foreach ($goals as $goal) {
                    $num = $this->number_of_days_to_goal($goal, $days, self::METRE_TO_KM);
                    $str .= "You need to do $num ride(s) of at least $goal to increase it to $goal.<br>\r\n";
                }
            }

            $str .= '<br><a href="#eddington_chart">See a chart of how your Eddington number has grown over the years.</a><br>';
            $str .= "<p><em>Run time " . (time() - $timestamp) . " seconds.</em></p>\r\n";

            $str .= $table_imperial;
            $str .= $table_metric;
            $imperial_history = $this->eddingtonHistory($days, self::METRE_TO_MILE);
            $metric_history = $this->eddingtonHistory($days, self::METRE_TO_KM);

            $str .= $this->buildChart($imperial_history, $metric_history);


        } else if ($state == "copy_strava_to_mcl") {
            $this->processUploadedGpxFiles($strava->getUserId(), $scratchDirectory);
            $this->output("<H3>Copying data from Strava to MyCyclingLog...</H3>");
            set_time_limit(300);

            $strava_rides_to_add = $strava->getRides($this->start_date, $this->end_date);
            if ($strava->getError()) {
                return ("<br>There was a problem getting data from Strava.<br>" . $strava->getError() .
                    "\r\n<br>Please try again\r\n");
            } else {
                $count = 0;
                $overnightRidesNeeded = [];  // these are unsplit overnight rides that haven't already been added to MCL
                $rides_to_retry = [];
                for ($i = 0; $i < 5; $i++) {
                    $mcl_rides = $myCyclingLog->getRides($this->start_date, $this->end_date);
                    $strava_ids_in_mcl_rides = $this->extractStravaIds($mcl_rides);
                    $overnight_rides = $strava->getOvernightActivities();
                    foreach ($strava_rides_to_add as $date => $ride_list) {
                        $strava_day_total = $this->sumDay($ride_list);
                        $mcl_day_total = isset($mcl_rides[$date]) ? $this->sumDay($mcl_rides[$date]) : 0;
                        if ($this->compareDistance($mcl_day_total, $strava_day_total) >= 0) {
                            continue; //there is at least this many miles for this day already in strava
                        }
                        foreach ($ride_list as $ride) {

                            if ($this->compareDistance($mcl_day_total, $strava_day_total) >= 0) {
                                break;
                            }

                            $distance = $ride['distance'];
                            if (!in_array($ride['strava_id'], $strava_ids_in_mcl_rides)) { // not an already copied strava ride
                                if ($preferences->getStravaSplitRides() && isset($overnight_rides[$ride['strava_id']])) {
                                    $overnightRidesNeeded[$ride['strava_id']] = $overnight_rides[$ride['strava_id']];
                                    continue; // this is an unsplit overnight ride
                                }

                                if ($this->compareDistance($mcl_day_total + $distance, $strava_day_total) >= 0) {
                                    //this ride will make our day total on MCL bigger than strava
                                    $distance = $strava_day_total - $mcl_day_total;
                                }
                                $message = "Ride with id " . $ride['strava_id'] . " on $date, distance " . round($distance * self::METRE_TO_MILE, 1) . " miles/" . round($distance * self::METRE_TO_KM, 1) . " kms. ";
                                $bike = $strava->getBike($ride["bike"]);
                                $mcl_bike_id = $myCyclingLog->bikeMatch($bike['brand'], $bike['model'], $ride['bike']);
                                $ride['mcl_bid'] = $mcl_bike_id;
                                $new_id = $myCyclingLog->addRide($date, $ride);
                                if (strlen($new_id) == 0) {
                                    $message = $message . "Appears to be a problem. Queued to retry.";
                                    $rides_to_retry[$date][] = $ride;
                                } else {
                                    $message = $message . "Added new ride, id: $new_id";
                                    $mcl_rides[$date][] = $ride; // in case strava gives us duplicates, we won't post them twice
                                    $count++;
                                }
                                $this->output("$message <br>");
                                flush();
                            } else {
                                $this->output('.');
                            }

                        }
                    }
                    if (sizeof($rides_to_retry) == 0) {
                        break;
                    }
                    $strava_rides_to_add = $rides_to_retry;
                    $rides_to_retry = [];
                }
                $str .= "<br>$count rides added.<br>\r\n";
                if (sizeof($rides_to_retry) != 0) {
                    $str .= "Some rides failed to be added.  See above.<br>\r\n";
                }

                $str .= $this->askForStravaGpx($overnightRidesNeeded, $maxKmFileUploads, "copy_strava_to_mcl", "add to MyCyclingLog");
            }
        } else if ($state == "copy_endo_to_strava") {
            $this->output("<H3>Copying rides from Endomondo to Strava...</H3>");
            set_time_limit(300);

            $endo_rides_to_add = $endomondo->getRides($this->start_date, $this->end_date);
            $strava_rides = $strava->getRides($this->start_date, $this->end_date);
            if ($strava->getError()) {
                return ("<br>There was a problem getting data from Strava.<br>" . $strava->getError() .
                    "\r\n<br>Please try again\r\n");
            } else {
                foreach ($endo_rides_to_add as $date => $ride_list) {
                    foreach ($ride_list as $ride) {
                        $distance = $ride['distance'];
                        $start_time = $ride['start_time'];
                        $message = 'Ride with id <a target="_blank" href="' . $endomondo->activityUrl($ride['endo_id']) . '">' . $ride['endo_id'] . '</a>' . " on $start_time, distance " . round($distance * self::METRE_TO_MILE, 1) . " miles/" . round($distance * self::METRE_TO_KM, 1) . " kms. ";
                        if (!$distance || $distance < 500) {
                            $message .= "Skipping, too short: $distance metres";
                        } else {
                            $duplicateStravaRide = $this->isDuplicateRide($ride, $strava_rides, 'strava_id');

                            if ($duplicateStravaRide) {
                                $message = ".";
                            } else {
                                $path = $scratchDirectory . DIRECTORY_SEPARATOR . "endomondo+" . $ride['endo_id'] . ".gpx";
                                $points = $endomondo->getPoints($ride['endo_id']);
                                if ($points->gpxBad()) {
                                    $message .= '<span style="color:red;">' . $points->gpxBad() . '</span>';
                                    $message .= 'To add manually, try going downloading GPX from  <a href="'
                                        . $endomondo->gpxDownloadUrl($ride['endo_id']) . '" target="_blank">Endomondo</a>'
                                        . ' then uploading it to <a href="' . $strava->uploadUrl() . '" target="_blank">Strava</a>.';

                                } else {
                                    file_put_contents($path, $points->gpx());
                                    $error = $strava->uploadGpx($path, $ride['endo_id'], $message,
                                        $ride['name'], $endomondo->activityUrl($ride['endo_id']));
                                    if ($error) {
                                        $message = $message . '<span style="color:red;">Failed: </span>' . $error;
                                    } else {
                                        $message = $message . 'Queued for upload.';
                                    }
                                    unlink($path);
                                }
                                $points = null;
                            }

                        }
                        $this->output("<br>$message ");
                        flush();

                    }


                }
                $results = $strava->waitForPendingUploads();
                $count = 0;

                foreach ($results as $endo_id => $result) {
                    if (isset($result->strava_id)) {
                        $message = $result->message . ' Uploaded successfully, id: <a target="_blank" href="' . $strava->activityUrl($result->strava_id) .
                            '">' . $result->strava_id . '</a>.';
                        $count++;
                    } else {
                        $message = $result->message . '<span style="color:red;"> There was a problem. </span>' . $result->error;

                    }
                    $this->output("<br>$message");
                    flush();


                }
                $str .= "<br>$count rides added.<br>\r\n";
            }
        } else if ($state == 'delete_mcl_rides') {
            $result = $myCyclingLog->deleteRides($this->start_date, $this->end_date, $_POST['mcl_username'], $_POST['mcl_password']);
            if (is_int($result)) {
                $str .= "Deleted $result activities from MyCyclingLog\r\n";
            } else {
                return ("<p style=\"color:red;\"><b>Problem connecting to MyCyclingLog: $result</b></p>\r\n");
            }
        } else if ($state == "copy_endo_to_rwgps") {
            $this->output("<H3>Copying rides from Endomondo to RideWithGPS...</H3>");
            set_time_limit(300);

            $endo_rides_to_add = $endomondo->getRides($this->start_date, $this->end_date);
            $rwgps_rides = $rideWithGps->getRides($this->start_date, $this->end_date);
            if ($rideWithGps->getError()) {
                return ("<br>There was a problem getting data from RideWithGPS.<br>" . $rideWithGps->getError() .
                    "\r\n<br>Please try again\r\n");
            } else {

                foreach ($endo_rides_to_add as $date => $ride_list) {
                    foreach ($ride_list as $ride) {
                        $distance = $ride['distance'];
                        $start_time = $ride['start_time'];
                        $message = '<br>Ride with id <a target="_blank" href="' . $endomondo->activityUrl($ride['endo_id']) . '">' . $ride['endo_id'] . '</a>' . " on $start_time, distance " . round($distance * self::METRE_TO_MILE, 1) . " miles/" . round($distance * self::METRE_TO_KM, 1) . " kms. ";
                        if (!$distance || $distance < 500) {
                            $message .= "Skipping, too short: $distance metres";
                        } else {
                            $duplicateRwgpsRide = $this->isDuplicateRide($ride, $rwgps_rides, 'rwgps_id');

                            if ($duplicateRwgpsRide) {
                                $message = ".";
                            } else {
                                $path = $scratchDirectory . DIRECTORY_SEPARATOR . "endomondo+" . $ride['endo_id'] . ".gpx";
                                $points = $endomondo->getPoints($ride['endo_id']);
                                if ($points->gpxBad()) {
                                    $message .= '<span style="color:red;">' . $points->gpxBad() . '</span>';
                                    $message .= 'To add manually, try going downloading GPX from  <a href="'
                                        . $endomondo->gpxDownloadUrl($ride['endo_id']) . '" target="_blank">Endomondo</a>'
                                        . ' then uploading it to <a href="' . $rideWithGps->uploadUrl() . '" target="_blank">RideWithGPS</a>.';

                                } else {
                                    file_put_contents($path, $points->gpx());
                                    $success = $rideWithGps->uploadGpx($path, $ride['endo_id'], $message,
                                        $ride['name'], $endomondo->activityUrl($ride['endo_id']));
                                    if (!$success) {
                                        $message = $message . '<span style="color:red;">Failed: </span>' . $rideWithGps->getError();
                                    } else {
                                        $message = $message . 'Queued for upload.';
                                    }
                                    unlink($path);
                                }
                                $points = null;
                            }

                        }
                        $this->output("$message ");
                        flush();

                    }


                }
                $results = $rideWithGps->waitForPendingUploads();
                $count = 0;

                foreach ($results as $endo_id => $result) {
                    if (isset($result->rwgps_id)) {
                        $message = $result->message . ' Uploaded successfully, id: <a target="_blank" href="' . $rideWithGps->activityUrl($result->rwgps_id) .
                            '">' . $result->rwgps_id . '</a>.';
                        $count++;
                    } else {
                        $message = $result->message . '<span style="color:red;"> There was a problem. </span>' . $result->error;

                    }
                    $this->output("<br>$message");
                    flush();


                }
                $str .= "<br>$count rides added.<br>\r\n";
            }
        } else if ($state == 'delete_mcl_rides') {
            $result = $myCyclingLog->deleteRides($this->start_date, $this->end_date, $_POST['mcl_username'], $_POST['mcl_password']);
            if (is_int($result)) {
                $str .= "Deleted $result activities from MyCyclingLog\r\n";
            } else {
                $str .= "<p style=\"color:red;\"><b>Problem connecting to MyCyclingLog: $result</b></p>\r\n";
            }
        }

        return $str;
    }

    private function displayPage()
    {

    }

    private function topOfPage()
    {
        $str = "<!DOCTYPE html>
<html>
<head>
    <title>Eddington &amp; More</title>
    <link rel=\"stylesheet\" href=\"css/w3.css\">
    <link rel=\"stylesheet\" href=\"src/js/jquery/jquery-ui.css\">
    <link
        href=\"data:image/x-icon;base64,AAABAAEAEBAAAAAAAABoBQAAFgAAACgAAAAQAAAAIAAAAAEACAAAAAAAAAEAAAAAAAAAAAAAAAEAAAAAAAAAAAAA3+DdALu7ugDFxsQAu766AEJBPwDS09EAvL6yAHZ3dACKjYgAzc/MAMHBugBLSkcA3uDeAGBgWwBGRkIAs7SuAN7c2QCsr6wA8PHwAFNTTwCMjYkAv8C+ADY1MQBpaGYAwsS7AKippQDk5+QA6+vpAGhnYQBlZmQAY2NcAN3f2gBzcW4ALi4tAJ6dlgCbnJkAz8/OANna2ABiZGIA6+vqAKCimQDW2NYAlJaSANLU0QCHh4MAp6ihADg5OADu7e0ArKupADU1MwCJioMAZWVgAI2NiwB9fnwAk5SQALW2qwB6encAj5CLAKOiogCjpaIAV1hUAMjJyACRko4AaWpjAHV1bQCNjokAr7CkALCyrwBaWlcAvL25AM/MyADS0tAAamtpALi4twCQkIwAk5SJAPHy8QCNjIcAY2NfAJaWlABJSUYAoKGeANPU0wDp6ucAsrOtAJiZlABKS0kAvL26APDw7wC4ubUA0tLRAGdoYgDEw8IAzs7MAOLk4AC/v70AY2NgALu7uACChH4As7SrAL3JugBJST8Aent3AN3e2QBHR0UA4eHhAOvs6wBzcG0AWFlUAMbHwABXWFcA0dLKAMC/vgBtbmsAXV9cADc3MQC2t7QA6urpAOTm5ADb29oA5ebkANbX1QDi4t8AycjGAGtsaQBERD4AUlJQALS1sgCrqqgA2NnYAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABgBWlbAAAAAAAAAAAAAAAlIhZKMmoAAAAAAAJIAABHf3oAAABhAAAAADZFfmIAOwwAAAAADgAAGlNPAABtADtJAAQ8DWcSP3hQXU0AJHw7cgArdC5BZkIAOXYbdwZsOxQARGNGfTpVAAgwPXWCbEdRawAmKQ8KZQA1XjN5A2wAgScAADdAARxuPlxWAAl4AAAYL28XTBMAZABXLSw0AAAAAHExAFo4VGhfEB0fKAAAAAAAAB5zAAcgGRVZAAAAAAAAAAARToAAcEMjAAAAAAAAAAAAAAAAAAALewAAAAAAAAAAAAAAAABYS1IqAAAAAAAAAAAAACEhAAAAACEhAMP/AACB8wAAHeEAAD2NAAAgBAAAIEAAACBAAAAQQAAAmAQAAMChAADkAQAA+QcAAPiPAAD/zwAA/4cAAP55AAA=\"
        rel=\"icon\" type=\"image/x-icon\"/>


    <style>
        .roundbutton {
            width: 67px;
            height: 40px;
            background-color: #FFFFFF;
            moz-border-radius: 15px;
            -webkit-border-radius: 15px;
            border: 2px solid #000000;
            padding: 2px;
            cursor: pointer;
        }

    </style>


    <script src=\"src/js/jquery/jquery-1.9.1.js\"></script>
    <script src=\"src/js/jquery/jquery-1.11.1.min.js\"></script>
    <script src=\"src/js/jquery/jquery-ui.js\"></script>
    <script src=\"src/js/tz/timezones.full.js\"></script>
</head>
<body>
<h2>Eddington &amp; More</h2>
\r\n";
        $str .= "<p style=\"color:red;\"><b>$this->error_message</b></p>\r\n";
        $str .= "<p style=\"color:blueviolet;\"><em>$this->info_message</em></p>\r\n";
        $str .= "<p>On this page you can calculate your Eddington Number from your Strava, Endomondo, MyCyclingLog or RideWithGPS
 accounts, or
    transfer rides from Strava
    to MyCyclingLog, from Endomondo to Strava or from Endomondo to RideWithGPS</p>\r\n";
        $str .= "<hr>\r\n";
        $str .= "<p>The Eddington Number is a metric for long distance cyclists.  It's the largest value of E where you
    have cycled at least E miles on E days. So if you have
    cycled 35 miles or more on 35 days but have not cycled at 36 miles or more on 36 days, then your E-number is 35.</p>";
        return $str;
    }

    /**
     * @param
     * @return string
     */
    private function notes()
    {
        global $eddingtonAndMoreVersion;
        return "<div id=\"notes\">
    <hr>
    <p>Notes:</p>
    <ol>
        <li><em>date format is dd-mm-yyyy</em></li>
        <li><em>You can set either or both dates, or leave them both blank your lifetime
                E-number.</em></li>
        <li><em>the timezone is used to determine midnight for the date range</em></li>
        <li><em>If you upload files, they will be kept on a scratch directory with your Strava User Id,
                so you won't have to reupload them every time. You can remove the files from the server
                by pressing the appropriate button above.</em></li>
        <li><em>when using Strava,
                each
                ride's date is the local time saved by Strava</em></li>
        <li><em>Timezone set here will be used with Endomondo to determine the start of the new day</em></li>
        <li><em>You can set either or both dates, or leave them both
                blank for your lifetime E-number.</em></li>
        <li><em>By default, all the miles during a ride (even if it takes several days) count towards the total of
                the
                first day.</em></li>

        <li><em>You can choose to split it into multiple days, to get the
                mileage for each day midnight-midnight.</em></li>
        <li><em>As I can't get the GPS points directly from Strava, Strava rides can
                only be split by you downloading them onto your machine, and then uploading
                them here.</em></li>
        <li><em>As splitting Strava rides is such a faff, it's probably easiest to use the copy feature above to
                copy them to MyCyclingLog, then calculate your E-number from that. Then you will only need
                download/upload them
                once.</em></li>
        <li><em>It might take a minute or two to come back with an answer</em></li>
        <li><em>It's much slower if you split the rides.</em></li>
        <li><em>Rides of less than 500m are not copied between systems.</em></li>
        <li><em>Rides copied from endomondo are considered duplicates if there is already a ride on strava that
                overlaps it.
            </em>
        </li>
        <li><em>MyCyclingLog stores elevation as a number without units. By default, copy will leave the
                elevation
                in metres, but if you check the box, it will multiply elevation by 3.2, converting it to feet.</em>
        </li>

        <li><em>If you want your bike information to be included you must make sure you have bikes with
                <strong>exactly</strong>
                matching make/model in both accounts. To test, select start and end dates close together, then check
                MyCyclingLog to see if you like the result. </em></li>
        <li><em>It should not make duplicates if the ride has already been copied using this
                page, or if the total distance for the day on MCL is within
                2% or greater than the distance recorded in Strava.</em></li>
        <li><em>This is open source, you can download the source from <a
                    href=\"http://github.com/JoanMcGalliard/EddingtonAndMore\">
                    http://github.com/JoanMcGalliard/EddingtonAndMore</a>. This is
                revision $eddingtonAndMoreVersion.
            </em></li>

    </ol>


</div>
";
    }

    private function dateButtons($timezone)
    {
        date_default_timezone_set($timezone);
        $today = date("d-m-Y", time());
        $yesterday = date("d-m-Y", time() - (self::TWENTY_FOUR_HOURS));
        $seven_days_ago = date("d-m-Y", time() - (self::TWENTY_FOUR_HOURS * 7));
        $start_of_month = date("01-m-Y", time());
        $start_of_year = date("01-01-Y", time());
        $start_of_last_year = "01-01-" . (intval(date("Y", time())) - 1);
        $end_of_last_year = "31-12-" . (intval(date("Y", time())) - 1);
        $string = '<script> function populateDates(start, end) {
            document.getElementById("datepicker_start").value = start;
            document.getElementById("datepicker_end").value = end;
            }
            </script>';
        $string .= "Fill in dates:\r\n";
        $string .= "<span class=\"roundbutton\" onclick=\"populateDates('$today','')\">today</span>\r\n";
        $string .= "<span class=\"roundbutton\" onclick=\"populateDates('$yesterday','')\">since yesterday</span>\r\n";;
        $string .= "<span class=\"roundbutton\" onclick=\"populateDates('$seven_days_ago','')\">last 7 days</span>\r\n";;
        $string .= "<span class=\"roundbutton\" onclick=\"populateDates('$start_of_month','')\">this month</span>\r\n";;
        $string .= "<span class=\"roundbutton\" onclick=\"populateDates('$start_of_year','')\">this year</span>\r\n";;
        $string .= "<span class=\"roundbutton\" onclick=\"populateDates('$start_of_last_year','$end_of_last_year')\">last year</span>\r\n";;
        $string .= "<span class=\"roundbutton\" onclick=\"populateDates('','')\">reset</span>\r\n";;
        $string .= "<br>\r\n<br>\r\n";
        return $string;
    }

    private function datePicker($timezone)
    {
        $str = "<tr>
                <td>Start Date <input type=\"text\" name=\"start_date\" id=\"datepicker_start\"/></td>
                <td> End Date <input type=\"text\" name=\"end_date\" id=\"datepicker_end\"/></td>
                <td><select name=\"tz\" id=\"tz\"> </select></td>
            </tr>";
        $str .= "<script>
        $(\"#datepicker_start\").datepicker({changeMonth: true, changeYear: true, dateFormat: 'dd-mm-yy'});
        $(\"#datepicker_end\").datepicker({changeMonth: true, changeYear: true, dateFormat: 'dd-mm-yy'});
        $(\"#tz\").timezones();
        $(\"#tz\").val('$timezone');";
        $str .= "</script>\r\n";
        return $str;
    }

    private function mclDeleteButton($username)
    {
        $str = "<input onclick=\"confirm_mcl_deletes()\" type=\"button\" name=\"delete_mcl_rides\" value=\"Delete MyCyclingLog rides\"/>";
        $password_warning = "Are you sure you want to do this?  This will remove all activities from " .
            "MyCyclingLog between \" + start + \" and \" + end_date + \" that have a Strava ride in the notes." .
            "\\n\\nIf you are sure, enter your MCL password here.";
        $str .= "\r\n<script> function confirm_mcl_deletes() {
            var start = document.forms[\"main_form\"][\"start_date\"].value;
            var end_date = document.forms[\"main_form\"][\"end_date\"].value;
            if (start == \"\") {
                start = \"the beginning\"
            }
            if (end_date == \"\") {
                end_date = \"today\"
            }
            var password_warning = \"$password_warning\";";
        $str .= "\r\n";

        if (!$username) {
            $str .= 'var username = prompt("Please enter your MyCyclingLog username");';
        } else {
            $str .= "var username = '" . $username . "'; ";
        }
        $str .= "var password = prompt(password_warning);
            if (password != null) {
                document.forms[\"main_form\"][\"start_date\"].value;

                submit_field = document.createElement('input');
                submit_field.setAttribute('name', 'delete_mcl_rides');
                submit_field.setAttribute('type', 'hidden');
                submit_field.setAttribute('value', 'Delete MyCyclingLog rides');
                document.forms[\"main_form\"].appendChild(submit_field);


                username_field = document.createElement('input');
                username_field.setAttribute('name', 'mcl_username');
                username_field.setAttribute('type', 'hidden');
                username_field.setAttribute('value', username);
                document.forms[\"main_form\"].appendChild(username_field);

                password_field = document.createElement('input');
                password_field.setAttribute('name', 'mcl_password');
                password_field.setAttribute('type', 'hidden');
                password_field.setAttribute('value', password);
                document.forms[\"main_form\"].appendChild(password_field);

                ;

                document.forms[\"main_form\"].submit(\"hello\");
            }
            else
                return false;
        }
        </script>";


        return $str;
    }

    private function mainForm()
    {
        $str = "";
        /** @var \JoanMcGalliard\EddingtonAndMore\RideWithGps $rideWithGps */
        $rideWithGps = $this->rideWithGps;
        /** @var Preferences $preferences */
        $preferences = $this->preferences;
        /** @var \JoanMcGalliard\EddingtonAndMore\Strava $strava */
        $strava = $this->strava;
        /** @var \JoanMcGalliard\EddingtonAndMore\MyCyclingLog $myCyclingLog */
        $myCyclingLog = $this->myCyclingLog;
        /** @var \JoanMcGalliard\EddingtonAndMore\Endomondo $endomondo */
        $endomondo = $this->endomondo;
        $str .= "<form action=\"$this->here\" method=\"post\" name=\"main_form\">";
        if ($this->isConnected) {
            $str .= "<hr>";
            $str .= $this->dateButtons($preferences->getTimezone());
        }

        $str .= "<table class=\"w3-table-all\">";
        if ($this->isConnected) {
            $str .= $this->datePicker($preferences->getTimezone());
        }
        if ($this->isConnected) {
            $colSpan = ' colspan="3"';
        } else {
            $colSpan = '';
        }
        if ($strava->isConnected()) {
            $str .= '<tr><td' . $colSpan . '><input type="submit" name="calculate_from_strava" value="Eddington Number from Strava"/><br>';
            $str .= 'Split multiday rides?:
            <input type="checkbox" value="split" ' . ($preferences->getStravaSplitRides() ? "checked" : "") .
                ' id="strava_split_1" name="strava_split_rides"/>';
            $str .= '</td></tr>';
        }
        if ($myCyclingLog->isConnected()) {

            $str .= '<tr><td' . $colSpan . '><input type="submit" name="calculate_from_mcl" value="Eddington Number from MyCyclingLog"/></td></tr>';
        }
        if ($endomondo->isConnected()) {
            $str .= '<tr><td' . $colSpan . '><input type="submit" name="calculate_from_endo" value="Eddington Number from Endomondo"/><br>';
            $str .= 'Split multiday rides?:
            <input type="checkbox" value="split" ' . ($preferences->getEndoSplitRides() ? "checked" : "") .
                ' name="endo_split_rides"/></td></tr>';
        }
        if ($rideWithGps->isConnected()) {
            $str .= '<tr><td' . $colSpan . '><input type="submit" name="calculate_from_rwgps" value="Eddington Number from RideWithGPS"/><br>';
            $str .= 'Split multiday rides?:
            <input type="checkbox" value="split" ' . ($preferences->getRwgpsSplitRides() ? "checked" : "") .
                ' name="rwgps_split_rides"/></td></tr>';
        }
        if ($strava->isConnected() && $myCyclingLog->isConnected()) {
            $str .= '<tr><td' . $colSpan . '><input type="submit" name="copy_strava_to_mcl" value="Copy ride data from Strava to MyCyclingLog"/>  <br>';
            $str .= 'Save elevation as feet: <input type="checkbox" name="elevation_units" value="feet" ' .
                ($preferences->getMclUseFeet() ? "checked" : "") . "/>";
            $str .= '<br>Split multiday rides?:
            <input type="checkbox" value="split" ' . ($preferences->getStravaSplitRides() ? "checked" : "") .
                ' id="strava_split_2" name="strava_split_rides"/>';
            $str .= "</td></tr>";
        }
        if ($strava->isConnected() && $endomondo->isConnected() && $strava->writeScope()) {
            $str .= '<tr><td' . $colSpan . '><input type="submit" name="copy_endo_to_strava" value="Copy rides and routes from Endomondo to Strava"/>  <br>';
            $str .= "</td></tr>";
        }
        if ($rideWithGps->isConnected() && $endomondo->isConnected()) {
            $str .= '<tr><td' . $colSpan . '><input type="submit" name="copy_endo_to_rwgps" value="Copy rides and routes from Endomondo to RideWithGPS"/>  <br>';
            $str .= "</td></tr>";
        }

        if ($myCyclingLog->isConnected()) {
            $str .= '<tr><td' . $colSpan . '>' . $this->mclDeleteButton($preferences->getMclUsername());
            $str .= "</td></tr>";
        }
        $str .= " <tr>
            <td $colSpan><input type=\"submit\" name=\"clear_cookies\" value=\"Delete Cookies\"/></td>
        </tr>
        <tr>
            <td $colSpan><input type=\"submit\" name=\"delete_files\" value=\"Delete temporary files\"/>
            </td>
        </tr>
    </table>";

        if (!$this->connectedToAll) {
            $str .= '<p>More options are available if you connect to <a href="#services">other services</a>.</p>';
        }


        $str .= "<script> $(\"#strava_split_1\").click(function () {
            $(\"#strava_split_2\").prop('checked', $(\"#strava_split_1\").prop('checked'));
        });
        $(\"#strava_split_2\").click(function () {
            $(\"#strava_split_1\").prop('checked', $(\"#strava_split_2\").prop('checked'));
        });

    </script>
</form>";
        return $str;
    }

    private function connections()
    {
        if ($this->connectedToAll) {
            return "";
        }
        /** @var \JoanMcGalliard\EddingtonAndMore\RideWithGps $rideWithGps */
        $rideWithGps = $this->rideWithGps;
        /** @var \JoanMcGalliard\EddingtonAndMore\Strava $strava */
        $strava = $this->strava;
        /** @var \JoanMcGalliard\EddingtonAndMore\MyCyclingLog $myCyclingLog */
        $myCyclingLog = $this->myCyclingLog;
        /** @var \JoanMcGalliard\EddingtonAndMore\Endomondo $endomondo */
        $endomondo = $this->endomondo;

        $str = "<hr><h3 id=\"services\">Connect to services</h3>
    <p>Click the buttons below to authorise access to your strava account and/or mycyclinglog accounts.</p>
    <p><em>This website uses cookies. If you have a problem with that, there are millions of other sites out there
            &#9786; Oh,
            and there is a button to delete the cookies when you are done. </em></p><table>
        <tr>";
        if (!$strava->isConnected() || !$strava->writeScope()) {
            $str .= "<td>";
            if (!$strava->isConnected()) {
                $str .= "Read acccess (You need this to calculate E-number from Strava):<br>";
                $str .= '<a href="' .
                    $strava->authenticationUrl($this->here, 'auto', null, "read_only") .
                    '"> <img src="images/ConnectWithStrava@2x.png" alt="Connect with Strava"></a><br><br>';
            }
            $str .= "Read/write acccess (only click this if you want to upload rides from Endomondo to Strava): <br>";
            $str .= '<a href="' .
                $strava->authenticationUrl($this->here, 'auto', "write", "write") .
                '"> <img src="images/ConnectWithStrava@2x.png" alt="Connect with Strava"></a>';;
            $str .= "</td>";
        }
        if (!$myCyclingLog->isConnected()) {
            $str .= " <td>
                    <form action=\"$this->here\" method=\"post\">
                        <table>
                            <tr>
                                <td> MyCyclingLog Username:</td>
                                <td><input type=\"text\" name=\"username\"/></td>
                            </tr>
                            <tr>
                                <td>MyCyclingLog Password:</td>
                                <td><input type=\"password\" name=\"password\"/></td>
                            </tr>

                            <tr class=\"w3-centered\">
                                <td colspan=\"2\" class=\"w3-centered\"><input type=\"image\" src=\"images/mcl_logo.png\"
                                                                           alt=\"Connect with MCL\"/></td>
                            </tr>
                        </table>
                        <input type=\"hidden\" name=\"login_mcl\"/>
                    </form>
                </td>";
        }
        if (!$endomondo->isConnected()) {
            $str .= "<td>
                    <form action=\"$this->here\" method=\"post\">
                        <table>
                            <tr>
                                <td> Endomondo Username:</td>
                                <td><input type=\"text\" name=\"username\"/></td>
                            </tr>
                            <tr>
                                <td>Endomondo Password:</td>
                                <td><input type=\"password\" name=\"password\"/>
                                </td>
                            </tr>
                            <tr class=\"w3-centered\">
                                <td colspan=\"2\" class=\"w3-centered\"><input type=\"image\" src=\"images/endomondo.svg\"
                                                                           alt=\"Connect with Endomondo\"/>
                                </td>
                            </tr>
                        </table>
                        <input type=\"hidden\" name=\"login_endo\"/>
                    </form>
                </td>";


        }

        if (!$rideWithGps->isConnected()) {
            $str .= "<td>
                    <form action=\"$this->here\" method=\"post\">
                        <table>
                            <tr>
                                <td> RideWithGPS Username:</td>
                                <td><input type=\"text\" name=\"username\"/></td>
                            </tr>
                            <tr>
                                <td>RideWithGPS Password:</td>
                                <td><input type=\"password\" name=\"password\"/>
                                </td>
                            </tr>
                            <tr class=\"w3-centered\">
                                <td colspan=\"2\" class=\"w3-centered\"><input type=\"image\" src=\"images/rwgps.png\"
                                                                           alt=\"Connect with RideWithGPS\"/>
                                </td>
                            </tr>
                        </table>
                        <input type=\"hidden\" name=\"login_rwgps\"/>
                    </form>
                </td>";
        }

        $str .= "</tr>
    </table>
";

        return $str;
    }

    private function email()
    {
        $str = "<hr>
<p>Bug reports, feature requests, thanks? Please use this form. <em>Note this will only stay here until the spam bots
        find it.</em></p>
<FORM METHOD=\"POST\">
    <INPUT TYPE=HIDDEN NAME=\"subject\" VALUE=\"Eddington\"/>
    <input type=hidden name=\"env_report\" value=\"REMOTE_ADDR, HTTP_USER_AGENT\"/>

    <p><strong>Your Name:</strong> <INPUT TYPE=TEXT NAME=\"commentRealName\"/>
        <strong>Email Address:</strong> <INPUT TYPE=TEXT NAME=\"commentEmail\"/>
    <p><strong>Comments:</strong>
        <TEXTAREA NAME=\"commentComments\"></textarea></p>

    <p><INPUT TYPE=\"SUBMIT\" name=\"commentSend\" VALUE=\"Send\"/>
        <INPUT TYPE=\"RESET\" VALUE=\"Clear\"/>
</FORM>

";
        return $str;
    }

    private function sumActivities($activities)
    {
        $days = [];
        foreach ($activities as $date => $rides) {
            $days[$date] = $this->sumDay($rides);
        }
        return $days;
    }


    private function sumDay($rides)
    {
        $distance = 0;
        foreach ($rides as $ride) {
            $distance += floatval($ride['distance']);
        }
        return $distance;
    }

    private function next_goals($x)
    {
        $next = [];
        $next[$x + 1] = 1;
        $mod = ($x % 10);
        if ($mod < 5) {
            $next[$x - $mod + 5] = 1;
        }
        $next [$x - $mod + 10] = 1;
        $mod = ($x % 100);
        if ($mod < 50) {
            $next[$x - $mod + 50] = 1;
        }
        $next[$x - $mod + 100] = 1;
        return array_keys($next);
    }

    private function number_of_days_to_goal($goal, $days, $factor)
    {
        $num = $goal;
        foreach ($days as $day => $distance) {
            $corrected_distance = round($distance * $factor);
            if ($corrected_distance >= $goal) {
                $num--;
            } else {
                return $num;
            }
        }
        return $num;
    }

    private function isDuplicateRide($endo_ride, $rides, $id_key)
//returns true if endo id matches or a ride overlaps this ride.
    {

        if (!$rides) {
            return false;
        }
        foreach ($rides as $date => $ride_list) {

            foreach ($ride_list as $ride) {

                if ($ride['endo_id'] == $endo_ride['endo_id']) {
                    return $ride[$id_key];
                }
                $endo_start = strtotime($endo_ride['start_time']);
                $endo_end = $endo_start + $endo_ride['elapsed_time'];
                $strava_start = strtotime($ride['start_time']);
                $strava_end = $strava_start + $ride['elapsed_time'];
                if ($endo_start >= $strava_start && $endo_start <= $strava_end) {
                    return true;
                }
                if ($endo_end >= $strava_start && $endo_end <= $strava_end) {
                    return true;
                }
                if ($strava_start >= $endo_start && $strava_start <= $endo_end) {
                    return true;
                }
                if ($strava_end >= $endo_start && $strava_end <= $endo_end) {
                    return true;
                }
            }
        }
        return false;
    }

    private function extractStravaIds($mcl_rides)
    {
        $stravaIds = [];
        if ($mcl_rides) {
            foreach ($mcl_rides as $rides) {
                foreach ($rides as $ride) {
                    if ($ride['strava_id'] != null) {
                        $stravaIds[] = $ride['strava_id'];
                    }
                }
            }
        }
        return $stravaIds;
    }

    /**
     * @param $distance1
     * @param $distance2
     * @return int. 0 if distances are with 2% of each other, -1 if $distance1 is less, +1 is it is greater.
     */
    private function compareDistance($distance1, $distance2)
    {
        if ($distance1 <> 0 && abs(($distance2 - $distance1) / $distance1) < 0.02) {
            return 0;
        }
        return $distance1 < $distance2 ? -1 : 1;
    }

    private function calculateEddington($days, &$eddington_days, $factor)
    {
        uasort($days, function ($a, $b) {
            if ($a == $b) return 0; else return ($a > $b) ? -1 : 1;
        });
        $eddington = 0;
        $eddington_days = [];

        foreach ($days as $day => $distance) {
            $units = round($distance * $factor);
            if ($units > $eddington) {
                $eddington_days[$day] = $units;
                $eddington++;
            } else {
                break;
            }
        }
        return $eddington;
    }


    private function eddingtonHistory($days, $factor)
    {
        $eddingtonHistory = [];
        $history = [];
        $eddingtonNumber = 0;
        $day_list = array_keys($days);
        sort($day_list);
        foreach ($day_list as $day) {
            $distance = $days[$day];
            if ($distance >= $eddingtonNumber) {
                $history[$day] = $distance;
                $new_ed = $this->calculateEddington($history, $scratch, $factor);
                if ($new_ed > $eddingtonNumber) {
                    $eddingtonHistory[$day] = $new_ed;
                    $eddingtonNumber = $new_ed;
                }
            }
        }
        return $eddingtonHistory;
    }

    private function buildChart($imperial_history, $metric_history)
    {
        $dates = array_unique(array_merge(array_keys($imperial_history), array_keys($metric_history)));
        asort($dates);
        $chart = "";
        $metric_e = 0;
        $imperial_e = 0;

        foreach ($dates as $date) {
            $time = strtotime($date);
            $y = date("Y", $time);
            $m = date("m", $time);
            $d = date("d", $time);
            $imperial_e = max(isset($imperial_history[$date]) ? intval($imperial_history[$date]) : $imperial_e, $imperial_e);
            $metric_e = max(isset($metric_history[$date]) ? intval($metric_history[$date]) : $metric_e, $metric_e);
            $chart .= "        [new Date($y, $m, $d),  $imperial_e, $metric_e],\n";
        }


        $text = '<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>';
        $text .= '<script type="text/javascript">';
        $text .= "    google.charts.load('current', {'packages':['corechart']});";
        $text .= "    google.charts.setOnLoadCallback(drawChart);";
        $text .= "    function drawChart() {";
        $text .= "        var data = google.visualization.arrayToDataTable([";
        $text .= "            ['Date', 'Imperial', 'Metric'],";
        $text .= "             $chart   ";
        $text .= "        ]);";
        $text .= "        var options = {";
        $text .= "            title: 'Change in Eddington Number over time',";
        $text .= "            legend: { position: 'bottom' }";
        $text .= "        };";
        $text .= "        var chart = new google.visualization.LineChart(document.getElementById('eddington_chart'));";
        $text .= "        chart.draw(data, options);";
        $text .= "    }";
        $text .= "</script>";
        $text .= '<div id="eddington_chart" style="width: 900px; height: 500px"></div>';
        return $text;
    }

    private function askForStravaGpx($overnight_rides, $maxKmFileUploads, $state, $message)
    {
        $str = "";
        if (sizeof($overnight_rides) > 0) {

            $str .= "<br>";
            $str .= "To split your strava rides, you'll need to download some of the GPX from Strava, them upload them to here. ";
            $str .= "<br><strong>First</strong> click the following links to download the GPX files. ";
            uasort($overnight_rides, function ($a, $b) {
                if ($a->distance == $b->distance) return 0; else return ($a->distance > $b->distance) ? -1 : 1;
            });
            $str .= "<ol>";

            $count = 0;
            $total = 0;
            foreach ($overnight_rides as $id => $details) {
                $distance = intval($details->distance * self::METRE_TO_KM);
                $str .= "<li><a target=\"_blank\" href=\"https://www.strava.com/activities/$id/export_gpx\">
                    $details->name $distance km</a></li>";

                $count++;
                $total += $distance;
                if ($total >= $maxKmFileUploads) break;
            }
            $str .= "</ol>";
            if (sizeof($overnight_rides) > $count) {
                $str .= "<em>(You've got another " . (sizeof($overnight_rides) - $count);
                $str .= " overnight ride(s) to add after this (you do like riding over midnight!), ";
                $str .= "but we are restricting it to $maxKmFileUploads kilometres or so at a time to keep the ";
                $str .= "server behaving nicely. The rides above are the longest of your rides that ";
                $str .= "are needed.)</em>";
            }

            $str .= '<form action="" method="post" enctype="multipart/form-data">';
            $str .= '<strong>Then</strong> select the GPX file(s) that you have just downloaded:<br>';
            $str .= '<input type="file" name="gpx[]" id="gpx" multiple>';
            $str .= '<input type="hidden" name="start_date" value="' . $_POST["start_date"] . '"/>';
            $str .= '<input type="hidden" name="end_date" value="' . $_POST["end_date"] . '"/>';
            $str .= '<input type="hidden" name="' . $state . '" />';
            $str .= '<input type="hidden" value="split" checked name="strava_split_rides"/>';
            $str .= "<br><strong>Finally</strong>, $message:";
            $str .= '<br><input type="submit" value="Upload and ' . $message . '" name="submit"/>';
            $str .= '</form>';
        }
        return $str;
    }

    private function processUploadedGpxFiles($userId, $scratchDirectory)
    {
        $str = "";
        if (isset($_FILES) && isset ($_FILES['gpx'])) {  //gpx have been uploaded
            $user = $userId;
            $path = $scratchDirectory . DIRECTORY_SEPARATOR . $user;
            for ($i = 0; $i < sizeof($_FILES['gpx']['name']); $i++) {

                $name = $_FILES['gpx']['name'][$i];
                $type = $_FILES['gpx']['type'][$i];
                $tmp_name = $_FILES['gpx']['tmp_name'][$i];
                $error = $_FILES['gpx']['error'][$i];
                $size = $_FILES['gpx']['size'][$i];
                $pattern = "/\.gpx\$/";
                if (!preg_match($pattern, $name, $matches) > 0) {
                    $str .= ("Skipping $name as it doesn't end in .GPX<br>");
                } else if ($error <> 0) {
                    $str .= ("Skipping $name: error number $error.<br>");
                } else {
                    $doc = new DOMDocument();
                    $doc->loadXML(file_get_contents($tmp_name));
                    $time = str_replace(":", "_",
                        $doc->getElementsByTagName("gpx")->item(0)->getElementsByTagName("metadata")->item(0)->getElementsByTagName("time")->item(0)->nodeValue);

                    copy($tmp_name, "$path-$time.gpx");
                    $str .= ("$name: uploaded successfully.<br>");
                }
                unlink($tmp_name);
            }
        }
        return $str;
    }

    private function bottomOfPage()
    {
        return "</body></html>";
    }


}


