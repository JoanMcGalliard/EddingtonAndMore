<?php
ob_implicit_flush();
$no_echo = true;
define("TWENTY_FOUR_HOURS", 60 * 60 * 24);
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . DIRECTORY_SEPARATOR . "src" . PATH_SEPARATOR);

require_once 'local.php';
require_once 'src/JoanMcGalliard/EddingtonAndMore/Strava.php';
require_once 'src/JoanMcGalliard/EddingtonAndMore/MyCyclingLog.php';
require_once 'src/JoanMcGalliard/EddingtonAndMore/Endomondo.php';
require_once 'src/JoanMcGalliard/EddingtonAndMore/RideWithGps.php';
require_once 'src/JoanMcGalliard/EddingtonAndMore/Points.php';
require_once 'src/functions.php';
require_once 'src/Preferences.php';
date_default_timezone_set("$timezone");
$info_message = "";
$error_message = "";


$here = "http://$_SERVER[HTTP_HOST]$_SERVER[SCRIPT_NAME]";
$state = null;
const METRE_TO_MILE = 0.00062137119224;
const METRE_TO_KM = 0.001;
$last = null;

$preferences = new Preferences();
$start_date = null;
$end_date = null;
if (isset($_POST["start_date"]) && $_POST["start_date"] <> '') {
    $start_date = strtotime($_POST["start_date"]);
}
if (isset($_POST["end_date"]) && $_POST["end_date"] <> "") {
    $end_date = strtotime($_POST["end_date"] . " 23:59:59");
}
if (array_key_exists("tz", $_POST)) {
    $preferences->setTimezone($_POST["tz"]);
}
if (array_key_exists("calculate_from_endo", $_POST)) {
    $preferences->setEndoSplitRides((array_key_exists("endo_split_rides", $_POST)));
}
if (array_key_exists("calculate_from_strava", $_POST) || array_key_exists("copy_strava_to_mcl", $_POST)) {
    $preferences->setStravaSplitRides((array_key_exists("strava_split_rides", $_POST)));
}


if (array_key_exists("clear_cookies", $_POST)) {
    $preferences->clear();
    unset($_GET["code"]);
    unset($_GET["state"]);
}

$strava = new JoanMcGalliard\EddingtonAndMore\Strava($stravaClientId, $stravaClientSecret, 'myEcho');
$myCyclingLog = new JoanMcGalliard\EddingtonAndMore\MyCyclingLog('myEcho');
$endomondo = new JoanMcGalliard\EddingtonAndMore\Endomondo($deviceId, $googleApiKey, $preferences->getTimezone(), 'myEcho');
$rideWithGps = new JoanMcGalliard\EddingtonAndMore\RideWithGps('5a1c53f3', 'myEcho');

$myCyclingLog->setUseFeetForElevation($preferences->getMclUseFeet());
$endomondo->setSplitOvernightRides($preferences->getEndoSplitRides());
$strava->setSplitOvernightRides($preferences->getStravaSplitRides());
$strava->setWriteScope($preferences->getStravaWriteScope());


if (array_key_exists("login_mcl", $_POST)) {
    $mcl_username = $_POST['username'];
    $mcl_password = $_POST['password'];
    $auth = base64_encode("$mcl_username:$mcl_password");
    $myCyclingLog->setAuth("$auth");
    if ($myCyclingLog->isConnected()) {
        $preferences->setMclAuth($auth);
        $preferences->setMclUsername($_POST['username']);
    } else {
        $error_message = "There was a problem connecting to MyCyclingLog, please try again";
    }
} else if ($preferences->getMclAuth()) {
    $myCyclingLog->setAuth($preferences->getMclAuth());
}
if (array_key_exists("login_endo", $_POST)) {
    $endo_username = $_POST['username'];
    $endo_password = $_POST['password'];
    $auth = $endomondo->connect($endo_username, $endo_password);
    if ($endomondo->isConnected()) {
        $preferences->setEndoAuth($auth);
    } else {
        $error_message = "There was a problem connecting to Endomondo, please try again.<br>(" . $endomondo->getError() . ")";
    }
} else if ($preferences->getEndoAuth() != null) {
    $endomondo->setAuth($preferences->getEndoAuth());
}
if (array_key_exists("login_rwgps", $_POST)) {
    $rwgps_username = $_POST['username'];
    $rwgps_password = $_POST['password'];
    $auth = $rideWithGps->connect($rwgps_username, $rwgps_password);
    if ($rideWithGps->isConnected()) {
        $preferences->setRwgpsAuth($auth);
    } else {
        $error_message = "There was a problem connecting to RideWithGps, please try again.<br>(" . $rideWithGps->getError() . ")";
    }
} else if ($preferences->getRwgpsAuth() != null) {
    $rideWithGps->setAuth($preferences->getRwgpsAuth());
}
if (array_key_exists("state", $_GET)) {
    if (!array_key_exists("error", $_GET) && array_key_exists("code", $_GET)) {
        $code = $_GET["code"];
        $token = $strava->setAccessTokenFromCode($code);
        if ($strava->isConnected()) {
            $strava->setWriteScope(($_GET["state"] == "write"));
            $preferences->setStravaWriteScope($strava->writeScope());
            $preferences->setStravaAccessToken($token);
        }
    } else {
        $error_message .= 'There was a problem connecting to strava, please try again: ' . $_GET["error"] . " ";
    }
    if ($strava->getError()) {
        $error_message .= 'There was a problem connecting to strava, please try again: ' . $strava->getError() . " ";

    }
    unset($_GET["state"]);
    unset($_GET["code"]);
    unset($_GET["error"]);
} else if ($preferences->getStravaAccessToken() != null) {
    $strava->setAccessToken($preferences->getStravaAccessToken());
}
$strava_connected = $strava->isConnected();
$mcl_connected = $myCyclingLog->isConnected();
$endo_connected = $endomondo->isConnected();
$rwgps_connected = $rideWithGps->isConnected();

if ($strava_connected && array_key_exists("delete_files", $_POST)) {
    $files = scandir($scratchDirectory);
    $pattern = '/^' . $strava->getUserId() . "-.*\.gpx$/";
    $count = 0;
    foreach ($files as $file) {
        if (preg_match($pattern, $file, $match) > 0) {
            unlink($scratchDirectory . DIRECTORY_SEPARATOR . $file);
            $count++;
        }
    }
    myEcho("Deleted $count files.<br>");
}

if ($strava_connected && array_key_exists("calculate_from_strava", $_POST)) {
    $state = "calculate_from_strava";
} else if ($mcl_connected && array_key_exists("calculate_from_mcl", $_POST)) {
    $state = "calculate_from_mcl";
} else if ($endo_connected && array_key_exists("calculate_from_endo", $_POST)) {
    $state = "calculate_from_endo";
} else if ($rwgps_connected && array_key_exists("calculate_from_rwgps", $_POST)) {
    $state = "calculate_from_rwgps";
} else if ($mcl_connected && $strava_connected && array_key_exists("copy_strava_to_mcl", $_POST)) {
    $state = "copy_strava_to_mcl";
    if (array_key_exists("elevation_units", $_POST)) {
        $preferences->setMclUseFeet(true);
        $myCyclingLog->setUseFeetForElevation(true);
    } else {
        $preferences->setMclUseFeet(false);
        $myCyclingLog->setUseFeetForElevation(false);
    }

} else if ($endo_connected && $strava_connected && array_key_exists("copy_endo_to_strava", $_POST)) {
    $state = "copy_endo_to_strava";
} else if ($endo_connected && $rwgps_connected && array_key_exists("copy_endo_to_rwgps", $_POST)) {
    $state = "copy_endo_to_rwgps";
} else if ($mcl_connected && array_key_exists("delete_mcl_rides", $_POST)) {
    $state = "delete_mcl_rides";
}
if (isset($_POST['commentSend'])) {
    mail("$workingEmailAddress", "eddington enquiry",
        $_POST['commentComments'], "From: " . $_POST['commentRealname'] . "<"
        . $_POST['commentEmail'] . ">\r\n");
    $info_message = "Thanks.  Email sent.  " .
        " Name: " . $_POST['commentRealname'] .
        " Email: " . $_POST['commentEmail'] .
        " Comment: " . $_POST['commentComments'] . "";
}
unset($no_echo);
?>
<html>
<head>
    <title>Eddington &amp; More</title>
    <link rel="stylesheet" href="css/w3.css">
    <link
        href="data:image/x-icon;base64,AAABAAEAEBAAAAAAAABoBQAAFgAAACgAAAAQAAAAIAAAAAEACAAAAAAAAAEAAAAAAAAAAAAAAAEAAAAAAAAAAAAA3+DdALu7ugDFxsQAu766AEJBPwDS09EAvL6yAHZ3dACKjYgAzc/MAMHBugBLSkcA3uDeAGBgWwBGRkIAs7SuAN7c2QCsr6wA8PHwAFNTTwCMjYkAv8C+ADY1MQBpaGYAwsS7AKippQDk5+QA6+vpAGhnYQBlZmQAY2NcAN3f2gBzcW4ALi4tAJ6dlgCbnJkAz8/OANna2ABiZGIA6+vqAKCimQDW2NYAlJaSANLU0QCHh4MAp6ihADg5OADu7e0ArKupADU1MwCJioMAZWVgAI2NiwB9fnwAk5SQALW2qwB6encAj5CLAKOiogCjpaIAV1hUAMjJyACRko4AaWpjAHV1bQCNjokAr7CkALCyrwBaWlcAvL25AM/MyADS0tAAamtpALi4twCQkIwAk5SJAPHy8QCNjIcAY2NfAJaWlABJSUYAoKGeANPU0wDp6ucAsrOtAJiZlABKS0kAvL26APDw7wC4ubUA0tLRAGdoYgDEw8IAzs7MAOLk4AC/v70AY2NgALu7uACChH4As7SrAL3JugBJST8Aent3AN3e2QBHR0UA4eHhAOvs6wBzcG0AWFlUAMbHwABXWFcA0dLKAMC/vgBtbmsAXV9cADc3MQC2t7QA6urpAOTm5ADb29oA5ebkANbX1QDi4t8AycjGAGtsaQBERD4AUlJQALS1sgCrqqgA2NnYAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABgBWlbAAAAAAAAAAAAAAAlIhZKMmoAAAAAAAJIAABHf3oAAABhAAAAADZFfmIAOwwAAAAADgAAGlNPAABtADtJAAQ8DWcSP3hQXU0AJHw7cgArdC5BZkIAOXYbdwZsOxQARGNGfTpVAAgwPXWCbEdRawAmKQ8KZQA1XjN5A2wAgScAADdAARxuPlxWAAl4AAAYL28XTBMAZABXLSw0AAAAAHExAFo4VGhfEB0fKAAAAAAAAB5zAAcgGRVZAAAAAAAAAAARToAAcEMjAAAAAAAAAAAAAAAAAAALewAAAAAAAAAAAAAAAABYS1IqAAAAAAAAAAAAACEhAAAAACEhAMP/AACB8wAAHeEAAD2NAAAgBAAAIEAAACBAAAAQQAAAmAQAAMChAADkAQAA+QcAAPiPAAD/zwAA/4cAAP55AAA="
        rel="icon" type="image/x-icon"/>

    <link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css"/>

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


    <script src="http://code.jquery.com/jquery-1.9.1.js"></script>
    <script src="http://code.jquery.com/jquery-1.11.1.min.js"></script>
    <script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
    <script src="src/js/tz/timezones.full.js"></script>
</head>
<body>
<h2>Eddington &amp; More</h2>
<?php
myEcho("<p style=\"color:red;\"><b>$error_message</b></p>");
myEcho("<p style=\"color:blueviolet;\"><em>$info_message</em></p>");
myEcho("<p>On this page you can calculate your Eddington Number from your Strava, Endomondo or MyCyclingLog accounts, or
    transfer rides from Strava
    to MyCyclingLog or from Endomondo to Strava.</p>");
myEcho("<hr>");
myEcho("<p>The Eddington Number is a metric for long distance cyclists.  It's the largest value of E where you
    have cycled at least E miles on E days. So if you have
    cycled 35 miles or more on 35 days but have not cycled at 36 miles or more on 36 days, then your E-number is 35.</p>");
if ($state == "calculate_from_strava" || $state == "calculate_from_mcl" || $state == "calculate_from_endo" || $state == "calculate_from_rwgps") {
    set_time_limit(300);
    myEcho("<H3>Calculating....</H3>");
    date_default_timezone_set($preferences->getTimezone());
    $start_text = "the beginning";
    $end_text = "today";
    $activities = [];
    $timestamp = time();
    if ($start_date) $start_text = $_POST["start_date"];
    if ($end_date) $end_text = $_POST["end_date"];
    if ($state == "calculate_from_strava") {

        processUploadedGpxFiles($strava->getUserId(), $scratchDirectory);


        $source = "Strava";
        $activities = $strava->getRides($start_date, $end_date);
        if ($strava->getError()) {
            myEcho("<br><span style=\"color:red;\">There was a problem getting data from Strava.</span><br><em>"
                . $strava->getError()
                . "</em>");
        }

        $overnight_rides = $strava->getOvernightActivities();
        if ($preferences->getStravaSplitRides() && $overnight_rides) {
            askForStravaGpx($overnight_rides, $maxKmFileUploads, "calculate_from_strava", "recalculate your E-Number");

        }
    } else if ($state == "calculate_from_mcl") {
        $source = "MyCyclingLog";
        $activities = $myCyclingLog->getRides($start_date, $end_date);
    } else if ($state == "calculate_from_endo") {
        $source = "Endomondo";
        $activities = $endomondo->getRides($start_date, $end_date);
        $error = $endomondo->getError();
        if ($error) {
            myEcho("There was a problem getting data from $source:<br>" . $error);
        }
    } else if ($state == "calculate_from_rwgps") {
        $source = "RideWithGPS";
        $activities = $rideWithGps->getRides($start_date, $end_date);
        $error = $rideWithGps->getError();
        if ($error) {
            myEcho("There was a problem getting data from $source:<br>" . $error);
        }
    }
    if (!$start_date) {
        $start_date = strtotime(array_keys($activities)[sizeof($activities) - 1]);
    }
    if (!$end_date) {
        $end_date = time();
    }
    $days = sumActivities($activities);
    myEcho("<p>According to $source, for the period from $start_text to $end_text, "
        . round(($end_date - $start_date) / TWENTY_FOUR_HOURS)
        . " elapsed days</p>");
    uasort($days, function ($a, $b) {
        if ($a == $b) return 0; else return ($a > $b) ? -1 : 1;
    });

    $eddington_imperial = calculateEddington($days, $result, METRE_TO_MILE);

    $table_imperial = '<table id="imperial" class="w3-table-all w3-right-align"  style="width:60%"><tr><th>Count</th><th>Date </th><th class="w3-right-align">Distance</th></tr>';
    for ($i = 1; $i <= $eddington_imperial; $i++) {
        $day = array_keys($result)[$i - 1];
        $actual_distance = $result[$day];
        $table_imperial .= "<tr><td> $i </td><td> $day</td><td class=\"w3-right-align\">$actual_distance miles</td></tr>";
    }
    $table_imperial .= "</table>";
    myEcho("<br><a href=\"#imperial\">Your imperial Eddington Number</a> is <strong>$eddington_imperial</strong>.<br>");
    if ($end_text == "today") {
        $goals = next_goals($eddington_imperial);
        foreach ($goals as $goal) {
            $num = number_of_days_to_goal($goal, $days, METRE_TO_MILE);
            myEcho("You need to do $num ride(s) of at least $goal to increase it to $goal.<br>");
        }
    }
    $eddington_metric = calculateEddington($days, $result, METRE_TO_KM);
    $table_metric = '<table id="metric" class="w3-table-all w3-right-align"  style="width:60%"><tr><th>Count</th><th>Date </th><th class="w3-right-align">Distance</th></tr>';
    for ($i = 1; $i <= $eddington_metric; $i++) {
        $date = array_keys($result)[$i - 1];
        $distance = $result[$date];
        $table_metric .= "<tr><td> $i </td><td>$date</td><td class=\"w3-right-align\">$distance km</td></tr>";

    }

    $table_metric .= "</table>";
    myEcho("<br><a href=\"#metric\">Your metric Eddington Number</a> is <strong>$eddington_metric</strong><br>");
    if ($end_text == "today") {
        $goals = next_goals($eddington_metric);
        foreach ($goals as $goal) {
            $num = number_of_days_to_goal($goal, $days, METRE_TO_KM);
            myEcho("You need to do $num ride(s) of at least $goal to increase it to $goal.<br>");
        }
    }

    myEcho('<br><a href="#eddington_chart">See a chart of how your Eddington number has grown over the years.</a><br>');
    myEcho("<p><em>Run time " . (time() - $timestamp) . " seconds.</em></p>");

    myEcho($table_imperial);
    myEcho($table_metric);
    $imperial_history = eddingtonHistory($days, METRE_TO_MILE);
    $metric_history = eddingtonHistory($days, METRE_TO_KM);

    myEcho(buildChart($imperial_history, $metric_history));


} else if ($state == "copy_strava_to_mcl") {
    processUploadedGpxFiles($strava->getUserId(), $scratchDirectory);
    myEcho("<H3>Copying data from Strava to MyCyclingLog...</H3>");
    set_time_limit(300);

    $strava_rides_to_add = $strava->getRides($start_date, $end_date);
    if ($strava->getError()) {
        myEcho("<br>There was a problem getting data from Strava.<br>" . $strava->getError());
        myEcho("<br>Please try again");
    } else {
        $count = 0;
        $overnightRidesNeeded = [];  // these are unsplit overnight rides that haven't already been added to MCL
        for ($i = 0; $i < 5; $i++) {
            $rides_to_retry = [];
            $mcl_rides = $myCyclingLog->getRides($start_date, $end_date);
            $strava_ids_in_mcl_rides = extractStravaIds($mcl_rides);
            $overnight_rides = $strava->getOvernightActivities();
            foreach ($strava_rides_to_add as $date => $ride_list) {
                $strava_day_total = sumDay($ride_list);
                $mcl_day_total = isset($mcl_rides[$date]) ? sumDay($mcl_rides[$date]) : 0;
                if (compareDistance($mcl_day_total, $strava_day_total) >= 0) {
                    continue; //there is at least this many miles for this day already in strava
                }
                foreach ($ride_list as $ride) {

                    if (compareDistance($mcl_day_total, $strava_day_total) >= 0) {
                        break;
                    }

                    $distance = $ride['distance'];
                    if (!in_array($ride['strava_id'], $strava_ids_in_mcl_rides)) { // not an already copied strava ride
                        if ($preferences->getStravaSplitRides() && isset($overnight_rides[$ride['strava_id']])) {
                            $overnightRidesNeeded[$ride['strava_id']] = $overnight_rides[$ride['strava_id']];
                            continue; // this is an unsplit overnight ride
                        }

                        if (compareDistance($mcl_day_total + $distance, $strava_day_total) >= 0) {
                            //this ride will make our day total on MCL bigger than strava
                            $distance = $strava_day_total - $mcl_day_total;
                        }
                        $message = "Ride with id " . $ride['strava_id'] . " on $date, distance " . round($distance * METRE_TO_MILE, 1) . " miles/" . round($distance * METRE_TO_KM, 1) . " kms. ";
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
                        myEcho("$message <br>");
                        flush();
                    } else {
                        myEcho('.');
                    }

                }
            }
            if (sizeof($rides_to_retry) == 0) {
                break;
            }
            $strava_rides_to_add = $rides_to_retry;
            $rides_to_retry = [];
        }
        myEcho("<br>$count rides added.<br>");
        if (sizeof($rides_to_retry) != 0) {
            myEcho("Some rides failed to be added.  See above.<br>");
        }

        askForStravaGpx($overnightRidesNeeded, $maxKmFileUploads, "copy_strava_to_mcl", "add to MyCyclingLog");
    }
} else if ($state == "copy_endo_to_strava") {
    myEcho("<H3>Copying rides from Endomondo to Strava...</H3>");
    set_time_limit(300);

    $endo_rides_to_add = $endomondo->getRides($start_date, $end_date);
    $strava_rides = $strava->getRides($start_date, $end_date);
    if ($strava->getError()) {
        myEcho("<br>There was a problem getting data from Strava.<br>" . $strava->getError());
        myEcho("<br>Please try again");
    } else {
        foreach ($endo_rides_to_add as $date => $ride_list) {
            foreach ($ride_list as $ride) {
                $distance = $ride['distance'];
                $start_time = $ride['start_time'];
                $message = 'Ride with id <a target="_blank" href="' . $endomondo->activityUrl($ride['endo_id']) . '">' . $ride['endo_id'] . '</a>' . " on $start_time, distance " . round($distance * METRE_TO_MILE, 1) . " miles/" . round($distance * METRE_TO_KM, 1) . " kms. ";
                if (!$distance || $distance < 500) {
                    $message .= "Skipping, too short: $distance metres";
                } else {
                    $duplicateStravaRide = isDuplicateRide($ride, $strava_rides, 'strava_id');

                    if ($duplicateStravaRide) {
                        if (is_int($duplicateStravaRide)) {
                            $message .= 'Duplicate with  <a target="_blank" href="' . $strava->activityUrl($duplicateStravaRide) .
                                '">' . $duplicateStravaRide . '</a>, skipping. ';
                        } else {
                            $message .= "Duplicate, skipping. ";
                        }
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
                myEcho("<br>$message ");
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
            myEcho("<br>$message");
            flush();


        }
        myEcho("<br>$count rides added.<br>");
    }
} else if ($state == 'delete_mcl_rides') {
    $result = $myCyclingLog->deleteRides($start_date, $end_date, $_POST['mcl_username'], $_POST['mcl_password']);
    if (is_int($result)) {
        myEcho("Deleted $result activities from MyCyclingLog");
    } else {
        myEcho("<p style=\"color:red;\"><b>Problem connecting to MyCyclingLog: $result</b></p>");
    }
} else if ($state == "copy_endo_to_rwgps") {
    myEcho("<H3>Copying rides from Endomondo to RideWithGPS...</H3>");
    set_time_limit(300);

    $endo_rides_to_add = $endomondo->getRides($start_date, $end_date);
    $rwgps_rides = $rideWithGps->getRides($start_date, $end_date);
    if ($rideWithGps->getError()) {
        myEcho("<br>There was a problem getting data from RideWithGPS.<br>" . $rideWithGps->getError());
        myEcho("<br>Please try again");
    } else {

        foreach ($endo_rides_to_add as $date => $ride_list) {
            foreach ($ride_list as $ride) {
                $distance = $ride['distance'];
                $start_time = $ride['start_time'];
                $message = 'Ride with id <a target="_blank" href="' . $endomondo->activityUrl($ride['endo_id']) . '">' . $ride['endo_id'] . '</a>' . " on $start_time, distance " . round($distance * METRE_TO_MILE, 1) . " miles/" . round($distance * METRE_TO_KM, 1) . " kms. ";
                if (!$distance || $distance < 500) {
                    $message .= "Skipping, too short: $distance metres";
                } else {
                    $duplicateRwgpsRide = isDuplicateRide($ride, $rwgps_rides, 'rwgps_id');

                    if ($duplicateRwgpsRide) {
                        if (is_int($duplicateRwgpsRide)) {
                            $message .= 'Duplicate with  <a target="_blank" href="' . $rideWithGps->activityUrl($duplicateRwgpsRide) .
                                '">' . $duplicateRwgpsRide . '</a>, skipping. ';
                        } else {
                            $message .= "Duplicate, skipping. ";
                        }
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
                myEcho("<br>$message ");
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
            myEcho("<br>$message");
            flush();


        }
        myEcho("<br>$count rides added.<br>");
    }
} else if ($state == 'delete_mcl_rides') {
    $result = $myCyclingLog->deleteRides($start_date, $end_date, $_POST['mcl_username'], $_POST['mcl_password']);
    if (is_int($result)) {
        myEcho("Deleted $result activities from MyCyclingLog");
    } else {
        myEcho("<p style=\"color:red;\"><b>Problem connecting to MyCyclingLog: $result</b></p>");
    }
}

if ($strava_connected || $mcl_connected || $endo_connected || $rwgps_connected) {
    ?>

    <form action="<?php echo $here; ?>" method="post" name="main_form">
        <hr>

        <script> function populateDates(start, end) {

                document.getElementById("datepicker_start").value = start;
                document.getElementById("datepicker_end").value = end;
            }
        </script>
        <?php
        date_default_timezone_set($preferences->getTimezone());
        $today = date("d-m-Y", time());
        $yesterday = date("d-m-Y", time() - (TWENTY_FOUR_HOURS));
        $seven_days_ago = date("d-m-Y", time() - (TWENTY_FOUR_HOURS * 7));
        $start_of_month = date("01-m-Y", time());
        $start_of_year = date("01-01-Y", time());
        $start_of_last_year = "01-01-" . (intval(date("Y", time())) - 1);
        $end_of_last_year = "31-12-" . (intval(date("Y", time())) - 1);

        ?>

        Fill in dates:


        <span class="roundbutton" onclick="populateDates('<?php echo $today ?>','')">today</span>
        <span class="roundbutton" onclick="populateDates('<?php echo $yesterday ?>','')">since yesterday</span>
        <span class="roundbutton" onclick="populateDates('<?php echo $seven_days_ago ?>','')">last 7 days</span>
        <span class="roundbutton" onclick="populateDates('<?php echo $start_of_month ?>','')">this month</span>
        <span class="roundbutton" onclick="populateDates('<?php echo $start_of_year ?>','')">this year</span>
        <span class="roundbutton"
              onclick="populateDates('<?php echo $start_of_last_year ?>','<?php echo $end_of_last_year ?>')">last year</span>
        <span class="roundbutton" onclick="populateDates('','')">reset</span>
        <br>
        <br>
        <table class="w3-table-all">
            <tr>
                <td>Start Date <input type="text" name="start_date" id="datepicker_start"/></td>
                <td> End Date <input type="text" name="end_date" id="datepicker_end"/></td>
                <td><select name="tz" value="<?php echo $preferences->getTimezone(); ?>" id="tz"> </select></td>
            </tr>
            <tr>
                <?php
                if ($strava_connected) {
                    myEcho('<tr><td colspan="3"><input type="submit" name="calculate_from_strava" value="Eddington Number from Strava"/><br>');
                    echo 'Split multiday rides?:
            <input type="checkbox" value="split" ' . ($preferences->getStravaSplitRides() ? "checked" : "") .
                        ' id="strava_split_1" name="strava_split_rides"/>';
                    myEcho('</td></tr>');
                }
                if ($mcl_connected) {

                    myEcho('<tr><td colspan="3"><input type="submit" name="calculate_from_mcl" value="Eddington Number from MyCyclingLog"/></td></tr>');
                }
                if ($endo_connected) {
                    myEcho('<tr><td colspan="3"><input type="submit" name="calculate_from_endo" value="Eddington Number from Endomondo"/><br>');
                    echo 'Split multiday rides?:
            <input type="checkbox" value="split" ' . ($preferences->getEndoSplitRides() ? "checked" : "") .
                        ' name="endo_split_rides"/></td></tr>';
                }
                if ($rwgps_connected) {
                    myEcho('<tr><td colspan="3"><input type="submit" name="calculate_from_rwgps" value="Eddington Number from RideWithGPS"/><br> </td></tr>');
                }
                if ($strava_connected && $mcl_connected) {
                    myEcho('<tr><td colspan="3"><input type="submit" name="copy_strava_to_mcl" value="Copy ride data from Strava to MyCyclingLog"/>  <br>');
                    echo 'Save elevation as feet: <input type="checkbox" name="elevation_units" value="feet" ' .
                        ($preferences->getMclUseFeet() ? "checked" : "") . "/>";
                    echo '<br>Split multiday rides?:
            <input type="checkbox" value="split" ' . ($preferences->getStravaSplitRides() ? "checked" : "") .
                        ' id="strava_split_2" name="strava_split_rides"/>';
                    myEcho("</td></tr>");
                }
                if ($strava_connected && $endo_connected && $strava->writeScope()) {
                    myEcho('<tr><td colspan="3"><input type="submit" name="copy_endo_to_strava" value="Copy rides and routes from Endomondo to Strava"/>  <br>');
                    myEcho("</td></tr>");
                }
                if ($rwgps_connected && $endo_connected) {
                    myEcho('<tr><td colspan="3"><input type="submit" name="copy_endo_to_rwgps" value="Copy rides and routes from Endomondo to RideWithGPS"/>  <br>');
                    myEcho("</td></tr>");
                }

                if ($mcl_connected) {
                    myEcho('<tr><td colspan="3"><input onclick="confirm_mcl_deletes()" type="button" name="delete_mcl_rides" value="Delete MyCyclingLog rides"/>');
                    myEcho("</td></tr>");
                }

                ?>
            <tr>
                <td colspan="3"><input type="submit" name="clear_cookies" value="Delete Cookies"/></td>
            </tr>
            <?php if ($strava_connected) { ?>
                <tr>
                    <td colspan="3"><input type="submit" name="delete_files" value="Delete temporary files"/></td>
                </tr>
            <?php } ?>
        </table>
        <?php

        if (!$strava_connected || !$mcl_connected || !$endo_connected) { ?>
            <p>More options are available if you connect to <a href="#services">other services</a>.</p>
            <?php
        }
        ?>

        <script>
            $("#datepicker_start").datepicker({changeMonth: true, changeYear: true, dateFormat: 'dd-mm-yy'});
            $("#datepicker_end").datepicker({changeMonth: true, changeYear: true, dateFormat: 'dd-mm-yy'});
            $("#tz").timezones();
            $("#tz").val('<?php echo($preferences->getTimezone()); ?>');

            function confirm_mcl_deletes() {
                var start = document.forms["main_form"]["start_date"].value;
                var end_date = document.forms["main_form"]["end_date"].value;
                if (start == "") {
                    start = "the beginning"
                }
                if (end_date == "") {
                    end_date = "today"
                }
                var password_warning = "Are you sure you want to do this?  This will remove all activities from " +
                    "MyCyclingLog between " + start + " and " + end_date + " that have a Strava ride in the notes. " +
                    "If you are sure, enter your MCL password here.";

                <?php
                if (!$preferences->getMclUsername()) {
                    echo('var username = prompt("Please enter your MyCyclingLog username");');
                } else {
                    echo("var username = '" . $preferences->getMclUsername() . "';");
                }
                ?>
                var password = prompt(password_warning);
                if (password != null) {
                    document.forms["main_form"]["start_date"].value;

                    submit_field = document.createElement('input');
                    submit_field.setAttribute('name', 'delete_mcl_rides');
                    submit_field.setAttribute('type', 'hidden');
                    submit_field.setAttribute('value', 'Delete MyCyclingLog rides');
                    document.forms["main_form"].appendChild(submit_field);


                    username_field = document.createElement('input');
                    username_field.setAttribute('name', 'mcl_username');
                    username_field.setAttribute('type', 'hidden');
                    username_field.setAttribute('value', username);
                    document.forms["main_form"].appendChild(username_field);

                    password_field = document.createElement('input');
                    password_field.setAttribute('name', 'mcl_password');
                    password_field.setAttribute('type', 'hidden');
                    password_field.setAttribute('value', password);
                    document.forms["main_form"].appendChild(password_field);

                    ;

                    document.forms["main_form"].submit("hello");
                }
                else
                    return false;
            }

            $("#strava_split_1").click(function () {
                $("#strava_split_2").prop('checked', $("#strava_split_1").prop('checked'));
            });
            $("#strava_split_2").click(function () {
                $("#strava_split_1").prop('checked', $("#strava_split_2").prop('checked'));
            });

        </script>
    </form>


    <div id="notes">
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

            <li><em>If you are using Endomondo, you can choose to split it into multiple days, to get the
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

            <li><em>If you want your bike information to be included you must make sure you have bikes with <strong>exactly</strong>
                    matching make/model in both accounts. To test, select start and end dates close together, then check
                    MyCyclingLog to see if you like the result. </em></li>
            <li><em>It should not make duplicates if the ride has already been copied using this
                    page, or if the total distance for the day on MCL is within
                    2% or greater than the distance recorded in Strava.</em></li>
            <li><em>This is open source, you can download the source from <a
                        href="http://github.com/JoanMcGalliard/EddingtonAndMore">
                        http://github.com/JoanMcGalliard/EddingtonAndMore</a>. This is
                    revision <?php echo $eddingtonAndMoreVersion ?>.
                    </a></em></li>

        </ol>


    </div>
    <?php
}
if (!$strava_connected || !$mcl_connected || !$endo_connected || !$strava->writeScope()) {
    ?>
    <hr>
    <h3 id="services">Connect to services</h3>
    <p>Click the buttons below to authorise access to your strava account and/or mycyclinglog accounts.</p>
    <p><em>This website uses cookies. If you have a problem with that, there are millions of other sites out there
            &#9786; Oh,
            and there is a button to delete the cookies when you are done. </em></p>
    <table>
        <tr>
            <?php if (!$strava_connected || !$strava->writeScope()) {
                myEcho("<td>");
                if (!$strava_connected) {
                    echo "Read acccess (You need this to calculate E-number from Strava):<br>";
                    echo '<a href="' .
                        $strava->authenticationUrl($here, 'auto', null, "read_only") .
                        '"> <img src="images/ConnectWithStrava@2x.png"></a><br><br>';
                }
                myEcho("Read/write acccess (only click this if you want to upload rides from Endomondo to Strava): <br>");
                echo '<a href="' .
                    $strava->authenticationUrl($here, 'auto', "write", "write") .
                    '"> <img src="images/ConnectWithStrava@2x.png"></a>';
                myEcho("</td>");
            }
            ?>
            <?php if (!$mcl_connected) { ?>
                <td>
                    <form action="<?php echo $here; ?>" method="post">
                        <table>
                            <tr>
                                <td> MyCyclingLog Username:</td>
                                <td><input type="text" name="username"/></td>
                            </tr>
                            <tr>
                                <td>MyCyclingLog Password:</td>
                                <td><input type="password" name="password"/>
                                <td>
                            </tr>

                            <tr class="w3-centered">
                                <td colspan="2" class="w3-centered"><input type="image" src="images/mcl_logo.png"
                                                                           alt="Submit Form"/>
                                <td>
                            </tr>
                        </table>
                        <input type="hidden" name="login_mcl"/>
                    </form>
                </td>
            <?php } ?>
            <?php if (!$endo_connected) { ?>
                <td>
                    <form action="<?php echo $here; ?>" method="post">
                        <table>
                            <tr>
                                <td> Endomondo Username:</td>
                                <td><input type="text" name="username"/></td>
                            </tr>
                            <tr>
                                <td>Endomondo Password:</td>
                                <td><input type="password" name="password"/>
                                <td>
                            </tr>
                            <tr class="w3-centered">
                                <td colspan="2" class="w3-centered"><input type="image" src="images/endomondo.svg"
                                                                           alt="Submit Form"/>
                                <td>
                            </tr>
                        </table>
                        <input type="hidden" name="login_endo"/>
                    </form>
                </td>
            <?php } ?>

            <?php if (!$rwgps_connected) { ?>
                <td>
                    <form action="<?php echo $here; ?>" method="post">
                        <table>
                            <tr>
                                <td> RideWithGPS Username:</td>
                                <td><input type="text" name="username"/></td>
                            </tr>
                            <tr>
                                <td>RideWithGPS Password:</td>
                                <td><input type="password" name="password"/>
                                <td>
                            </tr>
                            <tr class="w3-centered">
                                <td colspan="2" class="w3-centered"><input type="image" src="images/rwgps.png"
                                                                           alt="Submit Form"/>
                                <td>
                            </tr>
                        </table>
                        <input type="hidden" name="login_rwgps"/>
                    </form>
                </td>
            <?php } ?>


        </tr>
    </table>


    <?php
}
?>

<hr>
<p>Bug reports, feature requests, thanks? Please use this form. <em>Note this will only stay here until the spam bots
        find it.</em></p>
<FORM METHOD="POST">
    <INPUT TYPE=HIDDEN NAME="subject" VALUE="Eddington"/>
    <input type=hidden name="env_report" value="REMOTE_ADDR, HTTP_USER_AGENT"/>

    <p><strong>Your Name:</strong> <INPUT TYPE=TEXT NAME="commentRealname"/>
        <strong>Email Address:</strong> <INPUT TYPE=TEXT NAME="commentEmail"/>
    <p><strong>Comments:</strong>
        <TEXTAREA NAME="commentComments"></textarea></p>

    <p><INPUT TYPE="SUBMIT" name="commentSend" VALUE="Send"/>
        <INPUT TYPE="RESET" VALUE="Clear"/>
</FORM>

<?php
function eb($x)
{
    global $debug;
    if (!isset($debug) || !$debug) return;
    echo "<br>" . $x . "<br>";
}

function vdx($xml)
{
    vd(str_replace("<", "&lt;", str_replace(">", "&gt;", $xml)));
}

function vd($x)
{
    global $debug;
    if (!isset($debug) || !$debug) return;
    echo "<pre>";
    $dump = var_export($x, true);
    echo $dump;
    echo "</pre>";
    flush();
}


function log_msg($message)
{
    global $scratchDirectory, $logDiagnostics;
    if (!isset($logDiagnostics) || !$logDiagnostics) {
        return;
    }
    if (is_string($message)) {
        $string = $message;
    } else {
        $string = var_export($message, true);
    }
    $tz = date_default_timezone_get();
    date_default_timezone_set("UTC");
    $date = date("Y-m-d H:i:s", time());
    $file = fopen(dirname(__FILE__) . DIRECTORY_SEPARATOR . "diagnostic.log", "a");
    fwrite($file, $date . ": " . $string . "\n");
    fclose($file);
    date_default_timezone_set($tz);
}


?>
</body>
</html>
