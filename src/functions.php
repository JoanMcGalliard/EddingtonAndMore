<?php
function sumActivities($activities)
{
    $days = [];
    foreach ($activities as $date => $rides) {
        $days[$date] = sumDay($rides);
    }
    return $days;
}

function sumDay($rides)
{
    $distance = 0;
    foreach ($rides as $ride) {
        $distance += floatval($ride['distance']);
    }
    return $distance;
}

function next_goals($x)
{
    $next = [];
    $next[$x+1]=1;
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

//returns true if endo id matches or a ride overlaps this ride.
function isDuplicateStravaRide($endo_ride, $strava_rides)
{

    if (!$strava_rides) {
        return false;
    }
    foreach ($strava_rides as $date => $ride_list) {

        foreach ($ride_list as $strava_ride) {

            if ($strava_ride['endo_id'] == $endo_ride['endo_id']) {
                return $strava_ride['strava_id'];
            }
            $endo_start = strtotime($endo_ride['start_time']);
            $endo_end = $endo_start + $endo_ride['elapsed_time'];
            $strava_start = strtotime($strava_ride['start_time']);
            $strava_end = $strava_start + $strava_ride['elapsed_time'];
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

function extractStravaIds($mcl_rides)
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
function compareDistance($distance1, $distance2)
{
    if ( $distance1 <> 0 &&  abs(($distance2 - $distance1) / $distance1) < 0.02)
    {return 0;}
    return $distance1 < $distance2 ? -1 : 1;
}

function calculateEddington($days, &$eddington_days, $factor)
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

function eddingtonHistory($days, $factor)
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
            $new_ed = calculateEddington($history, $scratch, $factor);
            if ($new_ed > $eddingtonNumber) {
                $eddingtonHistory[$day] = $new_ed;
                $eddingtonNumber = $new_ed;
            }
        }
    }
    return $eddingtonHistory;
}

function buildChart($imperial_history, $metric_history)
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

function askForStravaGpx($overnight_rides, $maxKmFileUploads, $state, $message)
{
    if (sizeof($overnight_rides) == 0) {return;}
    echo "<br>";
    echo "To split your strava rides, you'll need to download some of the GPX from Strava, them upload them to here. ";
    echo "<br><strong>First</strong> click the following links to download the GPX files. ";
    uasort($overnight_rides, function ($a, $b) {
        if ($a->distance == $b->distance) return 0; else return ($a->distance > $b->distance) ? -1 : 1;
    });
    echo "<ol>";

    $count = 0;
    $total = 0;
    foreach ($overnight_rides as $id => $details) {
        $distance = intval($details->distance * METRE_TO_KM);
        echo "<li><a target=\"_blank\" href=\"https://www.strava.com/activities/$id/export_gpx\">
                    $details->name $distance km</a></li>";

        $count++;
        $total += $distance;
        if ($total >= $maxKmFileUploads) break;
    }
    echo "</ol>";
    if (sizeof($overnight_rides) > $count) {
        echo "<em>(You've got another " . (sizeof($overnight_rides) - $count);
        echo " overnight ride(s) to add after this (you do like riding over midnight!), ";
        echo "but we are restricting it to $maxKmFileUploads kilometres or so at a time to keep the ";
        echo "server behaving nicely. The rides above are the longest of your rides that ";
        echo "are needed.)</em>";
    }

    echo '<form action="" method="post" enctype="multipart/form-data">';
    echo '<strong>Then</strong> select the GPX file(s) that you have just downloaded:<br>';
    echo '<input type="file" name="gpx[]" id="gpx" multiple>';
    echo '<input type="hidden" name="start_date" value="' . $_POST["start_date"] . '"/>';
    echo '<input type="hidden" name="end_date" value="' . $_POST["end_date"] . '"/>';
    echo '<input type="hidden" name="'.$state.'" />';
    echo '<input type="hidden" value="split" checked name="strava_split_rides"/>';
    echo "<br><strong>Finally</strong>, $message:";
    echo '<br><input type="submit" value="Upload and '.$message.'" name="submit"/>';
    echo '</form>';
    return $distance;
}

function processUploadedGpxFiles($userId, $scratchDirectory)
{
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
                echo("Skipping $name as it doesn't end in .GPX<br>");
            } else if ($error <> 0) {
                echo("Skipping $name: error number $error.<br>");
            } else {
                $doc = new DOMDocument();
                $doc->loadXML(file_get_contents($tmp_name));
                $time = str_replace(":", "_",
                    $doc->getElementsByTagName("gpx")->item(0)->getElementsByTagName("metadata")->item(0)->getElementsByTagName("time")->item(0)->nodeValue);

                copy($tmp_name, "$path-$time.gpx");
                echo("$name: uploaded successfully.<br>");
            }
            unlink($tmp_name);
        }
    }
}

function myEcho($msg) {
    echo $msg;
    echo str_pad('',4096);
}

?>