<?php

namespace JoanMcGalliard\EddingtonAndMore;
require_once 'JoanMcGalliard/EddingtonAndMore/APIs/GoogleApi.php';

use JoanMcGalliard;
use stdClass;

class Points
{
    /**
     * @param stdClass $previousPoint
     */
    private static $previousPoint = null;

    public static function clearStoredPoint()
    {
        // only used in testing:
        self::$previousPoint = null;
    }

    private $points;
    private $timezone;
    private $splits;
    private $start_times;
    private $end_times;
    private $generateGPX = false;
    private $echoCallback;
    private $previous = null;
    private $start_day = 0;
    private $current_day; // midnight local time in seconds on the day this ride started
    private $gpx;
    private $bad_points = 0;
    private $good_points = 0;
    /** @var JoanMcGalliard\GoogleApi $api */
    private $api;
    private $error = "";

    /**
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param string $error
     */
    public function setError($error)
    {
        $this->error = $error;
    }

    /**
     * Points constructor.
     */
    public function __construct($start_day, $echoCallback, $timezone = "", $api = null)
    {
        $this->points = [];
        if ($api) {
            $this->api = $api;
        } else {
            $this->api = new JoanMcGalliard\GoogleApi();
        }
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
            if (isset($this->timezone)) {
                date_default_timezone_set($this->timezone);
            }
            $this->current_day = date("Y-m-d", strtotime($timestring));
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
        $this->api->setApikey($googleApiKey);
    }

    /**
     * @param $lat float
     * @param $long float
     * @param $time string
     * Note $time can be null/empty string from endomondo API
     */
    public function add($lat, $long, $time)
    {
        $point = [];
        $point['long'] = $long;
        $point['lat'] = $lat;
        $this->addPointToGPX($lat, $long, $time);
        $this->timezone = $this->timezoneFromCoords($lat, $long, $time);
        if ($time) {
            $this->start_day = strtotime(date("Y-m-d\T00:00:00 e", strtotime($time)));
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

    /**
     * @param string $timezone
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
    }

    private function addPointToGPX($lat, $long, $timestr)
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
        $default="UTC";
        $tz=null;
        if ($this->timezone) {
            return $this->timezone;
        }
        if (isset(self::$previousPoint) &&
            abs($this->distance(self::$previousPoint->lat, self::$previousPoint->long, $lat, $long)) < 50000
        ) {
            //previous TZ was less than 50km from here.  Use same timezone.  Needed as google is taking >3s to return TZ.
            return self::$previousPoint->tz;
        }
        if (!isset($this->api)) {
            return $default;
        }
        if (!$time) {
            $time = $this->start_day;
        }
        $params = ['location' => "$lat,$long",
            'timestamp' => $time];
        $page = $this->api->get('timezone/json', $params);
        $json = json_decode($page);

        if ($page && $json && isset($json->timeZoneId)) {
            $tz= $json->timeZoneId;
        } else if (!$page) {
            $this->error .= $this->api->getError();
        } else if (!$json) {
            $this->error .= $page;
        } else { // it's json, just not what we expected
            if (isset($json->errorMessage)) {
                $this->error .= $json->errorMessage;
            } else {
                $this->error .= "Unknown JSON returned by Google API, $page";
            }
        }
        if (!$tz) {
            if (isset(self::$previousPoint)) {
                $tz = self::$previousPoint->tz;
            } else {
                $tz = $default;
            }
            $this->output("<br>Unable to find timezone for ride on $this->current_day, defaulting to $tz.<br>");
        } else {
            self::$previousPoint = new stdClass();
            self::$previousPoint->lat = $lat;
            self::$previousPoint->long = $long;
            self::$previousPoint->tz = $tz;
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