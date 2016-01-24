<?php

define("TWENTY_FOUR_HOURS", 60 * 60 * 24);
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . DIRECTORY_SEPARATOR . "src" . PATH_SEPARATOR);

require_once 'local.php';
require_once 'src/JoanMcGalliard/EddingtonAndMore/StravaApi.php';
require_once 'src/JoanMcGalliard/EddingtonAndMore/MyCyclingLogApi.php';
require_once 'src/JoanMcGalliard/EddingtonAndMore/EndomondoApi.php';
require_once 'src/JoanMcGalliard/EddingtonAndMore/Points.php';
require_once 'src/functions.php';
require_once 'src/Preferences.php';
date_default_timezone_set("$timezone");
$info_message="";
$error_message="";


$here = "http://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]";
$state = null;
const METRE_TO_MILE = 0.00062137119224;
const METRE_TO_KM = 0.001;

$error_message = "";
$last = null;

$preferences = new Preferences();
$start_date=null;
$end_date=null;
if (isset($_POST["start_date"])) {
    $start_date = strtotime($_POST["start_date"]);
}
if (isset($_POST["end_date"])) {
    $end_date = strtotime($_POST["end_date"]." 23:59:59");
}
if (array_key_exists("tz", $_POST)) {
    $preferences->setTimezone($_POST["tz"]);
}
if (array_key_exists("calculate_from_endo", $_POST)) {
    $preferences->setEndoSplitRides((array_key_exists("endo_split_rides", $_POST)));
}
if (array_key_exists("calculate_from_strava", $_POST)) {
    $preferences->setStravaSplitRides((array_key_exists("strava_split_rides", $_POST)));
}


if (array_key_exists("clear_cookies", $_POST)) {
    $preferences->clear();
    unset($_GET["code"]);
    unset($_GET["state"]);
}

$strava_api = new JoanMcGalliard\EddingtonAndMore\StravaApi($stravaClientId, $stravaClientSecret);
$mcl_api = new JoanMcGalliard\EddingtonAndMore\MyCyclingLogApi();
$endo_api = new JoanMcGalliard\EddingtonAndMore\EndomondoApi($deviceId, $googleApiKey, $preferences->getTimezone());

$mcl_api->setUseFeetForElevation($preferences->getMclUseFeet());
$endo_api->setSplitOvernightRides($preferences->getEndoSplitRides());
$strava_api->setSplitOvernightRides($preferences->getStravaSplitRides());

if (array_key_exists("login_mcl", $_POST)) {
    $mcl_username = $_POST['username'];
    $mcl_password = $_POST['password'];
    $auth = base64_encode("$mcl_username:$mcl_password");
    $mcl_api->setAuth("$auth");
    if ($mcl_api->isConnected()) {
        $preferences->setMclAuth($auth);
        $preferences->setMclUsername($_POST['username']);
    } else {
        $error_message = "There was a problem connecting to MyCyclingLog, please try again";
    }
} else if ($preferences->getMclAuth()) {
    $mcl_api->setAuth($preferences->getMclAuth());
}
if (array_key_exists("login_endo", $_POST)) {
    $endo_username = $_POST['username'];
    $endo_password = $_POST['password'];
    $auth = $endo_api->connect($endo_username, $endo_password);
    if ($endo_api->isConnected()) {
        $preferences->setEndoAuth($auth);
    } else {
        $error_message = "There was a problem connecting to Endomondo, please try again.<br>(" . $endo_api->getErrorMessage() . ")";
    }
} else if ($preferences->getEndoAuth() != null) {
    $endo_api->setAuth($preferences->getEndoAuth());
}
if (array_key_exists("state", $_GET)) {
    if (!array_key_exists("error", $_GET) && array_key_exists("code", $_GET)) {
        $code = $_GET["code"];
        $token = $strava_api->setAccessTokenFromCode($code);
        if ($strava_api->isConnected()) {
            $strava_api->setWriteScope(($_GET["state"] == "write"));
            $preferences->setStravaAccessToken($token);
        }
    } else {
        $error_message .= 'There was a problem connecting to strava, please try again: ' . $_GET["error"] . " ";
    }
    if ($strava_api->getError()) {
        $error_message .= 'There was a problem connecting to strava, please try again: ' . $strava_api->getError() . " ";

    }
    unset($_GET["state"]);
    unset($_GET["code"]);
    unset($_GET["error"]);
} else if ($preferences->getStravaAccessToken() != null) {
    $strava_api->setAccessToken($preferences->getStravaAccessToken());
}
$strava_connected = $strava_api->isConnected();
$mcl_connected = $mcl_api->isConnected();
$endo_connected = $endo_api->isConnected();
if ($strava_connected && array_key_exists("calculate_from_strava", $_POST)) {
    $state = "calculate_from_strava";
} else if ($mcl_connected && array_key_exists("calculate_from_mcl", $_POST)) {
    $state = "calculate_from_mcl";
} else if ($endo_connected && array_key_exists("calculate_from_endo", $_POST)) {
    $state = "calculate_from_endo";
} else if ($mcl_connected && $strava_connected && array_key_exists("copy_strava_to_mcl", $_POST)) {
    $state = "copy_strava_to_mcl";
    if (array_key_exists("elevation_units", $_POST)) {
        $preferences->setMclUseFeet(true);
        $mcl_api->setUseFeetForElevation(true);
    } else {
        $preferences->setMclUseFeet(false);
        $mcl_api->setUseFeetForElevation(false);
    }

} else if ($endo_connected && $strava_connected && array_key_exists("copy_endo_to_strava", $_POST)) {
    $state = "copy_endo_to_strava";
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
<p style="color:red;"><b><?php echo $error_message ?></b></p>
<p style="color:blueviolet;"><em><?php echo $info_message ?></em></p>


<p>On this page you can calculate your Eddington Number from your Strava, Endomondo or MyCyclingLog accounts, or
    transfer rides from Strava
    to MyCyclingLog.
<p>
<hr>
<p>Your Eddington Number is the largest value of E where you have cycled at least E miles on E days. So if you have
    cycled 35 miles or more on 35 days, that's your E-number.</p>
<?php
if ($state == "calculate_from_strava" || $state == "calculate_from_mcl" || $state == "calculate_from_endo") {
    set_time_limit(300);
    echo "<H3>Calculating....</H3>";
    date_default_timezone_set($preferences->getTimezone());
    $start_text = "the beginning";
    $end_text = "today";
    $activities = [];
    $timestamp = time();
    if ($start_date) $start_text = $_POST["start_date"];
    if ($end_date) $end_text = $_POST["end_date"];
    if ($state == "calculate_from_strava") {
        $source = "Strava";
        $activities = $strava_api->getRides($start_date, $end_date);
    } else if ($state == "calculate_from_mcl") {
        $source = "MyCyclingLog";
        $activities = $mcl_api->getRides($start_date, $end_date);
    } else if ($state == "calculate_from_endo") {
        $source = "Endomondo";
        $activities = $endo_api->getRides($start_date, $end_date);
    }
    if (!$start_date) {
        $start_date = strtotime(array_keys($activities)[sizeof($activities) - 1]);
    }
    if (!$end_date) {
        $end_date = time();
    }
    $days = sumActivities($activities);
    echo "<p>According to $source, for the period from $start_text to $end_text, "
        . round(($end_date - $start_date) / TWENTY_FOUR_HOURS)
        . " elapsed days</p>";
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
    echo "<br><a href=\"#imperial\">Your imperial Eddington Number</a> is <strong>$eddington_imperial</strong>.<br>";
    if ($end_text == "today") {
        $goals = next_goals($eddington_imperial);
        foreach ($goals as $goal) {
            $num = number_of_days_to_goal($goal, $days, METRE_TO_MILE);
            echo "You need to do $num rides of at least $goal to increase it to $goal.<br>";
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
    echo "<br><a href=\"#metric\">Your metric Eddington Number</a> is <strong>$eddington_metric</strong><br>";
    if ($end_text == "today") {
        $goals = next_goals($eddington_metric);
        foreach ($goals as $goal) {
            $num = number_of_days_to_goal($goal, $days, METRE_TO_KM);
            echo "You need to do $num rides of at least $goal to increase it to $goal.<br>";
        }
    }

    echo '<br><a href="#eddington_chart">See a chart of how your Eddington number has grown over the years.</a><br>';
    echo "<p><em>Run time " . (time() - $timestamp) . " seconds.</em></p>";

    echo $table_imperial;
    echo $table_metric;
    $imperial_history = eddingtonHistory($days, METRE_TO_MILE);
    $metric_history = eddingtonHistory($days, METRE_TO_KM);

    echo buildChart($imperial_history, $metric_history);


} else if ($state == "copy_strava_to_mcl") {
    echo "<H3>Copying data from Strava to MyCyclingLog...</H3>";
    set_time_limit(300);

    $strava_rides_to_add = $strava_api->getRides($start_date, $end_date);
    $count = 0;
    for ($i = 0; $i < 5; $i++) {
        $rides_to_retry = [];
        $mcl_rides = $mcl_api->getRides($start_date, $end_date);
        foreach ($strava_rides_to_add as $date => $ride_list) {
            $strava_day_total=sumDay($ride_list);
            $mcl_day_total=sumDay($mcl_rides[$date]);
            if (compareDistance($mcl_day_total,$strava_day_total)>=0) {
                continue; //there is at least this many miles for this day already in strava
            }
            foreach ($ride_list as $ride) {
                if (compareDistance($mcl_day_total,$strava_day_total)>=0) {
                    break;
                }

                    $distance = $ride['distance'];
                $message = "Ride with id " . $ride['strava_id'] . " on $date, distance " . round($distance * METRE_TO_MILE, 1) . " miles/" . round($distance * METRE_TO_KM, 1) . " kms. ";
                if (isDuplicateMCLRide($date, $distance, $ride['strava_id'], $mcl_rides)) {
                    $message = $message . "Duplicate, skipping. ";
                } else {
                    if (compareDistance($mcl_day_total+$distance,$strava_day_total)>=0) {
                        //this ride will make our day total on MCL bigger than strava
                        $distance=$strava_day_total-$mcl_day_total;
                    }

                    $bike = $strava_api->getBike($ride["bike"]);
                    $mcl_bike_id = $mcl_api->bikeMatch($bike['brand'], $bike['model'], $ride['bike']);
                    $ride['mcl_bid'] = $mcl_bike_id;
                    $new_id = $mcl_api->addRide($date, $ride);
                    if (strlen($new_id) == 0) {
                        $message = $message . "Appears to be a problem. Queued to retry.";
                        $rides_to_retry[$date][] = $ride;
                    } else {
                        $message = $message . "Added new ride, id: $new_id";
                        $mcl_rides[$date][] = $ride; // in case strava gives us duplicates, we won't post them twice
                        $count++;
                    }
                }
                echo "$message <br>";
                flush();
            }
        }
        if (sizeof($rides_to_retry) == 0) {
            break;
        }
        $strava_rides_to_add = $rides_to_retry;
        $rides_to_retry = [];
    }
    echo "$count rides added.<br>";
    if (sizeof($rides_to_retry) != 0) {
        echo "Some rides failed to be added.  See above.<br>";
    }
} else if ($state == "copy_endo_to_strava") {
    echo "<H3>Copying rides from Endomondo to Strava...</H3>";
    set_time_limit(300);

    $endo_rides_to_add = $endo_api->getRides($start_date, $end_date);
    $strava_rides = $strava_api->getRides($start_date, $end_date);

    foreach ($endo_rides_to_add as $date => $ride_list) {
        foreach ($ride_list as $ride) {
            $distance = $ride['distance'];
            $start_time = $ride['start_time'];
            $message = 'Ride with id <a target="_blank" href="' . $endo_api->activityUrl($ride['endo_id']) . '">' . $ride['endo_id'] . '</a>' . " on $start_time, distance " . round($distance * METRE_TO_MILE, 1) . " miles/" . round($distance * METRE_TO_KM, 1) . " kms. ";
            if (!$distance || $distance < 500) {
                $message .= "Skipping, too short: $distance metres";
            } else {
                $duplicateStravaRide = isDuplicateStravaRide($ride, $strava_rides);

                if ($duplicateStravaRide) {
                    if (is_int($duplicateStravaRide)) {
                        $message .= 'Duplicate with  <a target="_blank" href="' . $strava_api->activityUrl($duplicateStravaRide) .
                            '">' . $duplicateStravaRide . '</a>, skipping. ';
                    } else {
                        $message .= "Duplicate, skipping. ";
                    }
                } else {
                    $path = $scratchDirectory . DIRECTORY_SEPARATOR . "endomondo+" . $ride['endo_id'] . ".gpx";
                    $points = $endo_api->getPoints($ride['endo_id']);
                    if ($points->gpxBad()) {
                        $message .= '<span style="color:red;">' . $points->gpxBad() . '</span>';
                        $message .= 'To add manually, try going downloading GPX from  <a href="'
                            . $endo_api->activityUrl($ride['endo_id']) . '" target="_blank">Endomondo</a>'
                            . ' then uploading it to <a href="' . $strava_api->uploadUrl() . '" target="_blank">Strava</a>.';

                    } else {
                        file_put_contents($path, $points->gpx());
                        $error = $strava_api->uploadGpx($path, $ride['endo_id'], $message,
                            $ride['name'], $endo_api->activityUrl($ride['endo_id']));
                        if ($error) {
                            $message = $message . '<span style="color:red;">Failed: </span>' . $error;
                        } else {
                            $message = $message . 'Queued for upload.';
                        }
                        unlink($path);
                    }
                }

            }
            echo "<br>$message ";
            flush();

        }


    }
    $results = $strava_api->waitForPendingUploads();
    $count = 0;

    foreach ($results as $endo_id => $result) {
        if (isset($result->strava_id)) {
            $message = $result->message . ' Uploaded successfully, id: <a target="_blank" href="' . $strava_api->activityUrl($result->strava_id) .
                '">' . $result->strava_id . '</a>.';
            $count++;
        } else {
            $message = $result->message . '<span style="color:red;"> There was a problem. </span>' . $result->error;

        }
        echo "<br>$message";
        flush();


    }
    echo "<br>$count rides added.<br>";
}

if ($strava_connected || $mcl_connected || $endo_connected) {
    ?>

    <form action="" method="post">
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

        ?>

        Fill in dates:


        <span class="roundbutton" onclick="populateDates('<?php echo $today ?>','')">today</span>
        <span class="roundbutton" onclick="populateDates('<?php echo $yesterday ?>','')">since yesterday</span>
        <span class="roundbutton" onclick="populateDates('<?php echo $seven_days_ago ?>','')">last 7 days</span>
        <span class="roundbutton" onclick="populateDates('<?php echo $start_of_month ?>','')">this month</span>
        <span class="roundbutton" onclick="populateDates('<?php echo $start_of_year ?>','')">this year</span>
        <span class="roundbutton"
              onclick="populateDates('<?php echo $start_of_last_year ?>','<?php echo $start_of_year ?>')">last year</span>
        <span class="roundbutton" onclick="populateDates('','')">reset</span>
        <br>
        <br>
        <table class="w3-table-all">
            <tr>
                <td>Start Date <input type="text" name="start_date" id="datepicker_start"/></td>
                <td> End Date <input type="text" name="end_date" id="datepicker_end"/></td>
                <td><select name="tz" value="<?php echo $preferences->getTimezone(); ?>" id="tz"> </select></td>
            </tr>
            <tr> </td></tr>
            <tr>
                <?php
                if ($strava_connected) {
                    echo '<tr><td colspan="3"><input type="submit" name="calculate_from_strava" value="Eddington Number from Strava"><br>';
                echo 'Split multiday rides?:
            <input type="checkbox" value="split" ' . ($preferences->getStravaSplitRides() ? "checked" : "") .
                    ' name="strava_split_rides"/></td></tr>';
                }
                if ($mcl_connected) {

                    echo '<tr><td colspan="3"><input type="submit" name="calculate_from_mcl" value="Eddington Number from MyCyclingLog"></td></tr>';
                }
                if ($endo_connected) {
                    echo '<tr><td colspan="3"><input type="submit" name="calculate_from_endo" value="Eddington Number from Endomondo"><br>';
                    echo 'Split multiday rides?:
            <input type="checkbox" value="split" ' . ($preferences->getEndoSplitRides() ? "checked" : "") .
                        ' name="endo_split_rides"/></td></tr>';
                }
                if ($strava_connected && $mcl_connected) {
                    echo '<tr><td colspan="3"><input type="submit" name="copy_strava_to_mcl" value="Copy ride data from Strava to MyCyclingLog">  <br>';
                    echo 'Save elevation as feet: <input type="checkbox" name="elevation_units" value="feet" ' .
                        ($preferences->getMclUseFeet() ? "checked" : "") . "></td></tr>";
                }
                if ($strava_connected && $endo_connected && $strava_api->writeScope()) {
                    echo '<tr><td colspan="3"><input type="submit" name="copy_endo_to_strava" value="Copy rides and routes from Endomondo to Strava">  <br>';
                    echo "</td></tr>";
                }
                ?>
            <tr>
                <td colspan="3"><input type="submit" name="clear_cookies" value="Delete Cookies"></td>
            </tr>
            </tr>
        </table>
        <script>
            $("#datepicker_start").datepicker({changeMonth: true, changeYear: true, dateFormat: 'dd-mm-yy'});
            $("#datepicker_end").datepicker({changeMonth: true, changeYear: true, dateFormat: 'dd-mm-yy'});
            $("#tz").timezones();
            $("#tz").val('<?php echo $preferences->getTimezone();?>');
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
            <li><em>Rides from Strava can't be split, as I can't get the GPS points from Strava. </em></li>
            <li><em>It might take a minute or two to come back with an answer</em></li>
            <li><em>It's much slower if you split the rides.</em></li>
            <li><em>Rides copied from endomondo are considered duplicates if there is already a ride on strava that
                    overlaps it.
                </em>
            </li>
            <li><em>MyCyclingLog doesn't stores elevation as a number without units. By default, copy will leave the
                    elevation
                    in metres, but if you check the box, it will multiply elevation by 3.2, converting it to feet.</em>
            </li>

            <li><em>It's a real pain to delete multiple rides from MyCyclingLog, so use copy with caution. If you want
                    your
                    bike information to be included you must make sure you have bikes with <strong>exactly</strong>
                    matching
                    make/model in both accounts. To test, select start and end dates close together, then check
                    MyCyclingLog to see
                    if you like the result. It should not make duplicates if the ride has already been copied using this
                    page, or if
                    there is another ride on the same day with 2% of the distance.</em></li>
            <li><em>This is open source, you can download the source from <a
                        href="http://github.com/JoanMcGalliard/EddingtonAndMore">
                        http://github.com/JoanMcGalliard/EddingtonAndMore</a>. This is
                    revision <?php echo $eddingtonAndMoreVersion ?>.
                    </a></em></li>

        </ol>


    </div>
    <?php
}
if (!$strava_connected || !$mcl_connected || !$endo_connected || !$strava_api->writeScope() ) {
    ?>
    <hr>
    <h3>Connect to services</h3>
    <p>Click the buttons below to authorise access to your strava account and/or mycyclinglog accounts.</p>
    <p><em>This website uses cookies. If you have a problem with that, there are millions of other sites out there
            &#9786; Oh,
            and there is a button to delete the cookies when you are done. </em></p>
    <table>
        <tr>
            <?php if (!$strava_connected || !$strava_api->writeScope()  ) {
                echo "<td>";
                if (!$strava_connected) {
                    echo "Read acccess (You need this to calculate E-number from Strava):<br>";
                    echo '<a href="' .
                        $strava_api->authenticationUrl($here, 'auto', null, "read_only") .
                        '"> <img src="images/ConnectWithStrava@2x.png"></a><br><br>';
                }
                echo "Read/write acccess (only click this if you want to upload rides from Endomondo to Strava): <br>";
                echo '<a href="' .
                    $strava_api->authenticationUrl($here, 'auto', "write", "write") .
                    '"> <img src="images/ConnectWithStrava@2x.png"></a>';
                echo "</td>";
            }
            ?>
            <?php if (!$mcl_connected) { ?>
                <td>
                    <form action="" method="post">
                        <table>
                            <tr>
                                <td> MyCyclingLog Username:</td>
                                <td><input type="text" name="username"></td>
                            </tr>
                            <tr>
                                <td>MyCyclingLog Password:</td>
                                <td><input type="password" name="password">
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
                    <form action="" method="post">
                        <table>
                            <tr>
                                <td> Endomondo Username:</td>
                                <td><input type="text" name="username"></td>
                            </tr>
                            <tr>
                                <td>Endomondo Password:</td>
                                <td><input type="password" name="password">
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
        </tr>
    </table>


    <?php
}
?>

<hr>
<p>Bug reports, feature requests, thanks? Please use this form. <em>Note this will only stay here until the spam bots
        find it.</em></p>
<FORM METHOD="POST">
    <INPUT TYPE=HIDDEN NAME="subject" VALUE="eSquad">
    <input type=hidden name="env_report" value="REMOTE_ADDR, HTTP_USER_AGENT">

    <p><strong>Your Name:</strong> <INPUT TYPE=TEXT NAME="commentRealname"
        >
        <strong>Email Address:</strong> <INPUT TYPE=TEXT NAME="commentEmail"
        >
    <p><strong>Comments:</strong>
        <TEXTAREA NAME="commentComments"></textarea></p>

    <p><INPUT TYPE="SUBMIT" name="commentSend" VALUE="Send">
        <INPUT TYPE="RESET" VALUE="Clear">
</FORM>

<?php
function eb($x)
{
    echo "<br>" . $x . "<br>";
}

function vdx($xml) {
    vd(str_replace("<", "&lt;", str_replace(">", "&gt;", $xml)));
}
function vd($x)
{
    echo "<pre>";
    $dump = var_export($x, true);
    echo $dump;
    echo "</pre>";
}

function br()
{
    echo "<br>";
}

function log_msg($message) {
    if (is_string($message)) {
        $string=$message;
    } else {
        $string=var_export($message, true);
    }
    $tz=date_default_timezone_get();
    date_default_timezone_set("UTC");
    $date=date("Y-m-d H:i:s", time());
    $file=fopen(dirname(__FILE__).DIRECTORY_SEPARATOR."diagnostic.log", "a");
    fwrite($file, $date.": ".$string."\n");
    fclose($file);
    date_default_timezone_set($tz);
}


?>
</body>
</html>
