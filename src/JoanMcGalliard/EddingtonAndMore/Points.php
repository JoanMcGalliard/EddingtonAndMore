<?php

namespace JoanMcGalliard\EddingtonAndMore;

use stdClass;

class Points
{

    private static $previousPoint = null;
    private $points;
    private $timezone;
    private $splits;
    private $googleApiKey;
    private $start_times;
    private $end_times;
    private $generateGPX = false;
    private $echoCallback;
    private $previous = null;
    private $total_distance_day = [];
private $start_day = 0;
        private $current_day; // midnight local time in seconds on the day this ride started
    private $gpx;
    private $bad_points = 0;
    private $good_points = 0;

    /**
     * Points constructor.
     */
    public function __construct($start_day, $echoCallback, $timezone = "")
    {
        $this->points = [];
        if ($timezone <> "") {
            $this->timezone = $timezone;
        }
        $this->day($start_day);
        $this->echoCallback = $echoCallback;
        $this->gpx = '<?xml version="1.0" encoding="UTF-8"?> <gpx creator="Eddington &amp; More" >';
        $this->gpx .= "<trk><trkseg>";
        $this->gpx .= "\n";
    }

    private function day($timestring)
    {
        if ($timestring) {
            $default_timezone = date_default_timezone_get();
            if (isset($this->timezone)) {
                date_default_timezone_set($this->timezone);
            }
            $this->current_day = date("Y-m-d", strtotime($timestring));
            date_default_timezone_set($default_timezone);
        }
        return $this->current_day;
    }

    /**
     * @param mixed $generateGPX
     */
    public function setGenerateGPX($generateGPX)
    {
        $this->generateGPX = $generateGPX;
    }

    /**
     * @param mixed $googleApiKey
     */
    public function setGoogleApiKey($googleApiKey)
    {
        $this->googleApiKey = $googleApiKey;
    }

    public function add($lat, $long, $time)
    {
        $point = [];
        $point['long'] = $long;
        $point['lat'] = $lat;
        $this->addPointToGPX($lat, $long, $time);
        if (!isset($this->timezone)) {
            if (!isset($this->googleApiKey)) {
                $this->timezone = "UTC";
            } else {
                if ($time) {
                    $this->timezone = $this->timezoneFromCoords($lat, $long, strtotime($time));
                } else {
                    $this->timezone = $this->timezoneFromCoords($lat, $long, strtotime($this->start_day));
                }
            }
        }
        if ($time) {
            $default_timezone = date_default_timezone_get();
            date_default_timezone_set($this->timezone);

            $this->start_day = strtotime(date("Y-m-d\T00:00:00 e", strtotime($time)));
            date_default_timezone_set($default_timezone);

        }
        if (!$this->previous) {
            $this->previous = $point;
        } else {
            $distance = $this->distance($this->previous['lat'], $this->previous['long'], $point['lat'], $point['long']);
            if (!isset($this->splits[$this->day($time)])) {
                $this->splits[$this->day($time)] = 0;
                $this->start_times[$this->day($time)] = $time;
                $this->end_times[$this->day($time)] = $time;
            }
            $this->splits[$this->day($time)] += $distance;
            $this->end_times[$this->day($time)] = $time;
            $this->previous = $point;
        }
    }

    public function addPointToGPX($lat, $long, $timestr)
    {
        if (!$this->generateGPX) {
            return;
        }
        $default_tz = date_default_timezone_get();
        date_default_timezone_set("UTC");
        if (!$timestr) {
            $this->bad_points++;
        } else {
            $this->good_points++;
            $time = date("Y-m-d\TH:i:s", strtotime($timestr)) . "Z";


            $this->gpx .= "<trkpt lat=\"$lat\" lon=\"$long\"><time>$time</time></trkpt>";
            $this->gpx .= "\n";
            date_default_timezone_set($default_tz);
        }

    }

    public function timezoneFromCoords($lat, $long, $time)
    {
        if (isset(self::$previousPoint) &&
            abs($this->distance(self::$previousPoint->lat, self::$previousPoint->long, $lat, $long)) < 50000
        ) {
            //previous TZ was less than 50km from here.  Use same timezone.  Needed as google is taking >3s to return TZ.
            return self::$previousPoint->tz;
        }
        $retries = 3;
        $params = ['location' => "$lat,$long",
            'timestamp' => $time, 'key', $this->googleApiKey];

        $url = "https://maps.googleapis.com/maps/api/timezone/json" . "?" . http_build_query($params);
        $process = curl_init($url);

        curl_setopt($process, CURLOPT_HEADER, 0);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);

        for ($i = 0; $i < $retries; $i++) {
            $page = curl_exec($process);
            $error = curl_error($process);
            log_msg($url);
            log_msg($page);
            if ($error) log_msg("ERROR: " . $error);
            log_msg("Total time: " . curl_getinfo($process)["total_time"]);

            if ($page) break;
            log_msg("retrying");
        }
        curl_close($process);
        $tz = false;
        if ($page) {
            $tz = json_decode($page)->timeZoneId;
            self::$previousPoint = new stdClass();
            self::$previousPoint->lat = $lat;
            self::$previousPoint->long = $long;
            self::$previousPoint->tz = $tz;
        }
        if (!$tz) {
            $this->output("Unable to find TZ from ride on $this->current_day, defaulting to UTC<br>");
            $tz = "UTC";
        }
        return $tz;
    }

    public function distance($lat1, $long1, $lat2, $long2)
    {


        if (!$lat1 || !$long1 || !$lat2 || !$long2) {
            return 0;
        }
        $DtoR = 0.017453293;
        $R = 6371000;      // Earth radius in metres


        $rlat1 = $lat1 * $DtoR;
        $rlong1 = $long1 * $DtoR;
        $rlat2 = $lat2 * $DtoR;
        $rlong2 = $long2 * $DtoR;


        $dlon = $rlong1 - $rlong2;
        $dlat = $rlat1 - $rlat2;

        $a = pow(sin($dlat / 2), 2) + cos($rlat1) *
            cos($rlat2) * pow(sin($dlon / 2), 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $d = $R * $c;

        return $d;

    }

    private function output($msg)
    {
        call_user_func($this->echoCallback, $msg);
    }

    /**
     * @return mixed
     */
    public function getStartTimes()
    {
        return $this->start_times;
    }

    /*thank you stackexchange!
     * http://stackoverflow.com/questions/569980/how-to-calculate-distance-from-a-gpx-file
     */

    /**
     * @return mixed
     */
    public function getEndTimes()
    {
        return $this->end_times;
    }

    public function gpxBad()
    {
        if (!$this->good_points) {
            return "There are no valid points to map.";
        }
        if ($this->bad_points && ($this->good_points < ($this->bad_points / 2))) {
            return "More than a third ($this->bad_points of " . ($this->bad_points + $this->good_points) . ") of the points provided are missing time details.";
        }
    }

    public function gpx()
    {
        return $this->gpx . "</trkseg> </trk> </gpx>";
    }

    public function calculateDistance($first = 0, $last = -1)
    {
        $distance = 0;
        foreach ($this->splits as $day => $day_distance) {
            $distance += $day_distance;

        }
        return $distance;

    }

    public function getSplits()
    {
        return $this->splits;
    }

}