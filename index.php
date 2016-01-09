<?php
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . DIRECTORY_SEPARATOR . "src" . PATH_SEPARATOR);

require_once 'local.php';
require_once 'src/JoanMcGalliard/StravaApi.php';
require_once 'src/JoanMcGalliard/MyCyclingLogApi.php';
require_once 'src/JoanMcGalliard/EndomondoApi.php';

date_default_timezone_set("$timezone");
$here = "http://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]";
$scope = null;
$state = null;
const METRE_TO_MILE = 0.00062137119224;
const METRE_TO_KM = 0.001;
$error_message = "";
$last = null;
const STRAVA_COOKIE = "STRAVA_ACCESS_TOKEN";
const MCL_COOKIE = "MYCYCLINGLOG_AUTH";
const ENDO_COOKIE = "ENDOMONDO_AUTH";
$strava_api = new JoanMcGalliard\StravaApi($stravaClientId, $stravaClientSecret);
$mcl_api = new JoanMcGalliard\MyCyclingLogApi();
$endo_api = new JoanMcGalliard\EndomondoApi($deviceId);
$start_date = strtotime($_POST["start_date"]);
$end_date = strtotime($_POST["end_date"]);
$tz = strtotime($_POST["tz"]);


if (array_key_exists("clear_cookies", $_POST)) {
    setcookie(MCL_COOKIE, null, time() - 3600);
    unset($_COOKIE[MCL_COOKIE]);
    setcookie(ENDO_COOKIE, null, time() - 3600);
    unset($_COOKIE[ENDO_COOKIE]);
    setcookie(STRAVA_COOKIE, null, time() - 3600);
    unset($_COOKIE[STRAVA_COOKIE]);
    unset($_GET["code"]);
    unset($_GET["state"]);
}
if (array_key_exists("login_mcl", $_POST)) {
    $mcl_username = $_POST['username'];
    $mcl_password = $_POST['password'];
    $auth = base64_encode("$mcl_username:$mcl_password");
    $mcl_api->setAuth("$auth");
    if ($mcl_api->isConnected()) {
        setcookie(MCL_COOKIE, $auth, time() + 60 * 60 * 24 * 365); //expires in 1 year
    } else {
        $error_message = "There was a problem connecting to MyCyclingLog, please try again";
    }
} else if (array_key_exists(MCL_COOKIE, $_COOKIE)) {
    $mcl_api->setAuth($_COOKIE[MCL_COOKIE]);
}
if (array_key_exists("login_endo", $_POST)) {
    $endo_username = $_POST['username'];
    $endo_password = $_POST['password'];
    $auth = $endo_api->connect($endo_username, $endo_password);
    if ($endo_api->isConnected()) {
        setcookie(ENDO_COOKIE, $auth, time() + 60 * 60 * 24 * 365); //expires in 1 year
    } else {
        $error_message = "There was a problem connecting to Endomondo, please try again.<br>(" . $endo_api->getErrorMessage() . ")";
    }
} else if (array_key_exists(ENDO_COOKIE, $_COOKIE)) {
    $endo_api->setAuth($_COOKIE[ENDO_COOKIE]);
}
if (array_key_exists("state", $_GET) && ($_GET["state"] == "connecting")) {
    if (!array_key_exists("error", $_GET) && array_key_exists("code", $_GET)) {
        $code = $_GET["code"];
        $token = $strava_api->setAccessTokenFromCode($code);
        if ($strava_api->isConnected()) {
            setcookie(STRAVA_COOKIE, $token, time() + 60 * 60 * 24 * 365); //expires in 1 year
        }
    } else {
        $error_message .= 'There was a problem connecting to strava, please try again.';
    }
} else if (array_key_exists(STRAVA_COOKIE, $_COOKIE)) {
    $strava_api->setAccessToken($_COOKIE[STRAVA_COOKIE]);
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
} else if ($mcl_connected && $strava_connected && array_key_exists("MCL", $_POST)) {
    $state = "copy_strava_to_mcl";
}
?>
<html>
<head>
    <title>Strava &amp; MyCyclingLog Tools</title>
    <link rel="stylesheet" href="css/w3.css">
    <link
        href="data:image/x-icon;base64,AAABAAEAEBAAAAAAAABoBQAAFgAAACgAAAAQAAAAIAAAAAEACAAAAAAAAAEAAAAAAAAAAAAAAAEAAAAAAAAAAAAA3+DdALu7ugDFxsQAu766AEJBPwDS09EAvL6yAHZ3dACKjYgAzc/MAMHBugBLSkcA3uDeAGBgWwBGRkIAs7SuAN7c2QCsr6wA8PHwAFNTTwCMjYkAv8C+ADY1MQBpaGYAwsS7AKippQDk5+QA6+vpAGhnYQBlZmQAY2NcAN3f2gBzcW4ALi4tAJ6dlgCbnJkAz8/OANna2ABiZGIA6+vqAKCimQDW2NYAlJaSANLU0QCHh4MAp6ihADg5OADu7e0ArKupADU1MwCJioMAZWVgAI2NiwB9fnwAk5SQALW2qwB6encAj5CLAKOiogCjpaIAV1hUAMjJyACRko4AaWpjAHV1bQCNjokAr7CkALCyrwBaWlcAvL25AM/MyADS0tAAamtpALi4twCQkIwAk5SJAPHy8QCNjIcAY2NfAJaWlABJSUYAoKGeANPU0wDp6ucAsrOtAJiZlABKS0kAvL26APDw7wC4ubUA0tLRAGdoYgDEw8IAzs7MAOLk4AC/v70AY2NgALu7uACChH4As7SrAL3JugBJST8Aent3AN3e2QBHR0UA4eHhAOvs6wBzcG0AWFlUAMbHwABXWFcA0dLKAMC/vgBtbmsAXV9cADc3MQC2t7QA6urpAOTm5ADb29oA5ebkANbX1QDi4t8AycjGAGtsaQBERD4AUlJQALS1sgCrqqgA2NnYAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABgBWlbAAAAAAAAAAAAAAAlIhZKMmoAAAAAAAJIAABHf3oAAABhAAAAADZFfmIAOwwAAAAADgAAGlNPAABtADtJAAQ8DWcSP3hQXU0AJHw7cgArdC5BZkIAOXYbdwZsOxQARGNGfTpVAAgwPXWCbEdRawAmKQ8KZQA1XjN5A2wAgScAADdAARxuPlxWAAl4AAAYL28XTBMAZABXLSw0AAAAAHExAFo4VGhfEB0fKAAAAAAAAB5zAAcgGRVZAAAAAAAAAAARToAAcEMjAAAAAAAAAAAAAAAAAAALewAAAAAAAAAAAAAAAABYS1IqAAAAAAAAAAAAACEhAAAAACEhAMP/AACB8wAAHeEAAD2NAAAgBAAAIEAAACBAAAAQQAAAmAQAAMChAADkAQAA+QcAAPiPAAD/zwAA/4cAAP55AAA="
        rel="icon" type="image/x-icon"/>

    <link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css"/>

    <script src="http://code.jquery.com/jquery-1.9.1.js"></script>
    <script src="http://code.jquery.com/jquery-1.11.1.min.js"></script>
    <script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
    <script src="src/js/tz/timezones.full.js"></script>
</head>
<body>
<h2>Strava &amp; MyCyclingLog Tools</h2>
<p style="color:red;"><b><?php echo $error_message ?></b></p>
<p>On this page you can calculate your Eddington Number from your Strava or MyCyclingLog, or transfer rides from Strava
    to MyCyclingLog.
<p>
<hr>
<p>Your Eddington Number is the largest value of E where you have cycled at least E miles on E days. So if you have
    cycled 35 miles or more on 35 days, that's your E-number.</p>
<?php
if ($state == "calculate_from_strava" || $state == "calculate_from_mcl" || $state == "calculate_from_endo") {
    date_default_timezone_set($tz);
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
    echo "According to $source, for the period from $start_text to $end_text, "
        . round(($end_date - $start_date) / (60 * 60 * 24))
        . " elapsed days, ";
    uasort($days, function ($a, $b) {
        if ($a == $b) return 0; else return ($a > $b) ? -1 : 1;
    });
    $eddington_imperial = 0;
    $table_imperial = '<table id="imperial" class="w3-table-all w3-right-align"  style="width:60%"><tr><th>Count</th><th>Date </th><th class="w3-right-align">Distance</th></tr>';
    foreach ($days as $day => $distance) {
        $miles = round($distance * METRE_TO_MILE);
        if ($miles > $eddington_imperial) {
            $eddington_imperial++;
            $table_imperial .= "<tr><td> $eddington_imperial </td><td>$day</td><td class=\"w3-right-align\">$miles miles</td></tr>";
        } else {
            break;
        }
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
    $eddington_metric = 0;
    $table_metric = '<table id="metric" class="w3-table-all w3-right-align"  style="width:60%"><tr><th>Count</th><th>Date </th><th class="w3-right-align">Distance</th></tr>';
    foreach ($days as $day => $distance) {
        $kms = round($distance * METRE_TO_KM);
        if ($kms > $eddington_metric) {
            $eddington_metric++;
            $table_metric .= "<tr><td> $eddington_metric </td><td>$day</td><td class=\"w3-right-align\">$kms km</td></tr>";
        } else {
            break;
        }
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

    echo "<p><i>Run time " . (time() - $timestamp) . " seconds.</i></p>";

    echo $table_imperial;
    echo $table_metric;
} else if ($state == "copy_strava_to_mcl") {
    $rides_to_add = $strava_api->getRides($start_date, $end_date);
    $count = 0;
    for ($i = 0; $i < 5; $i++) {
        $rides_to_retry = [];
        $mcl_rides = $mcl_api->getRides($start_date, $end_date);
        foreach ($rides_to_add as $date => $ride_list) {
            foreach ($ride_list as $ride) {
                $distance = $ride['distance'];
                $message = "Ride with id " . $ride['strava_id'] . " on $date, distance " . round($distance * METRE_TO_MILE, 1) . " miles/" . round($distance * METRE_TO_KM, 1) . " kms. ";
                if (isDuplicateRide($date, $distance, $ride['strava_id'], $mcl_rides)) {
                    $message = $message . "Duplicate, skipping. ";
                } else {
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
            }
        }
        if (sizeof($rides_to_retry) == 0) {
            break;
        }
        $rides_to_add = $rides_to_retry;
        $rides_to_retry = [];
    }
    echo "$count rides added.<br>";
    if (sizeof($rides_to_retry) != 0) {
        echo "Some rides failed to be added.  See above.<br>";
    }
}

if ($strava_connected || $mcl_connected || $endo_connected) {
    ?>
    <hr>

    <p><i>Note: date format it dd-mm-yyyy, and the timezone is used to determine midnight for the date range. Each
            ride's date is the local time as recorded by strava. You can set either or both dates, or leave them both
            blank for your lifetime E-number. You can set either or both dates, or leave them both blank your lifetime
            E-number. It might take a minute or two to come back with an answer.</i><p>
    <form action="" method="post">
        <table>
            <tr>
                <td>Start Date <input type="text" name="start_date" id="datepicker1"/></td>
                <td> End Date <input type="text" name="end_date" id="datepicker2"/></td>
                <td><select name="tz" value="Europe/London" id="tz"> </select></td>
            </tr>
            <tr> </td></tr>
            <tr>
                <?php
                if ($strava_connected) {
                    echo '<td><input type="submit" name="calculate_from_strava" value="Eddington Number from Strava"></td>';
                }
                if ($mcl_connected) {
                    echo '<td><input type="submit" name="calculate_from_mcl" value="Eddington Number from MyCyclingLog"></td>';
                }
                if ($endo_connected) {
                    echo '<td><input type="submit" name="calculate_from_endo" value="Eddington Number from Endomondo"></td>';
                }
                echo "</tr><tr>";
                if ($strava_connected && $mcl_connected) {
                    echo '<td><input type="submit" name="MCL" value="Copy Rides from Strava to MyCyclingLog (see note below)"></td>';
                }
                ?>
                <td><input type="submit" name="clear_cookies" value="Delete Cookies"></td>
            </tr>
        </table>
    </form>
    <p>Note on copy: It's a real pain to delete multiple rides from MyCyclingLog, so use with caution. If you want your
        bike information to be included you must make sure you have bikes with <strong>exactly</strong> matching
        make/model in both accounts. To test, select start and end dates close together, then check MyCyclingLog to see
        if you like the result. It should not make duplicates if the ride has already been copied using this page, or if
        there is another ride on the same day with 2% of the distance.</p>
    <script>
        $("#datepicker1").datepicker({changeMonth: true, changeYear: true, dateFormat: 'dd-mm-yy'});
        $("#datepicker2").datepicker({changeMonth: true, changeYear: true, dateFormat: 'dd-mm-yy'});
        $("#tz").timezones();
        $("#tz").val('Europe/London');
    </script>
    <?php
}
if (!$strava_connected || !$mcl_connected || !$endo_connected) {
    ?>
    <hr>
    <h3>Connect to services</h3>
    <p>Click the buttons below to authorise access to your strava account and/or mycyclinglog accounts.</p>
    <p><i>This website uses cookies. If you have a problem with that, there are millions of other sites out there
            &#9786; Oh,
            and there is a button to delete the cookies when you are done. </i></p>
    <table>
        <tr>
            <?php if (!$strava_connected) {
                echo "<td>";
                echo '<a href="' .
                    $strava_api->authenticationUrl($here, $approvalPrompt = 'auto', $scope, "connecting") .
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
function eb($x)
{
    echo $x . "<br>";
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

function sumActivities($activities)
{
    $days = [];
    foreach ($activities as $date => $rides) {
        $distance = 0;
        foreach ($rides as $ride) {
            $distance += floatval($ride['distance']);
        }
        $days[$date] = $distance;
    }
    return $days;
}

function next_goals($x)
{
    $next = [];
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

function number_of_days_to_goal($goal, $days, $factor)
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
}


function isDuplicateRide($date, $distance, $strava_id, $mcl_rides)
{
    if ($mcl_rides == null || !array_key_exists($date, $mcl_rides)) {
        return false;
    } else {
        foreach ($mcl_rides[$date] as $ride) {
            if ($ride['strava_id'] != null) {
                if ($ride['strava_id'] == $strava_id) {
                    return true;
                }
                continue;
            }

            if ($distance == 0) {
                if ($ride['distance'] == 0) {
                    return true;
                } else {
                    continue;
                }
            }
            if (abs(($ride['distance'] - $distance) / $distance) < 0.02) {
                return true;
            }
        }
    }
    return false;
}

?>
</body>
</html>
