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

function dot()
{
    echo ".";
    flush();
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

function isDuplicateMCLRide($date, $distance, $strava_id, $mcl_rides)
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
            if (compareDistance($distance, $ride['distance'])==0) {
                return true;
            }
        }
    }
    return false;
}

/**
 * @param $distance1
 * @param $distance2
 * @return int. 0 if distances are with 2% of each other, -1 if $distance1 is less, +1 is it is greater.
 */
function compareDistance($distance1, $distance2)
{
    if ( abs(($distance2 - $distance1) / $distance1) < 0.02)
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
        $imperial_e = max(intval($imperial_history[$date]), $imperial_e);
        $metric_e = max(intval($metric_history[$date]), $metric_e);
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

?>