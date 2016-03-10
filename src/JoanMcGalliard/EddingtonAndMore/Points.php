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
    private $splits=[];
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
    /** @var GoogleMaps $googleMaps */
    private $googleMaps;
    private $error = "";

    /**
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Points constructor.
     */
    public function __construct($start_day, $echoCallback, $googleMaps=null, $timezone = "")
    {
        $this->points = [];
        if ($googleMaps) {
            $this->googleMaps = $googleMaps;
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


    private function day($time)
    {

        if ($time) {
            if (isset($this->timezone)) {
                date_default_timezone_set($this->timezone);
            }
            if (is_string($time)) {
                $time=strtotime($time);
            }
            $this->current_day = date("Y-m-d", $time);
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
     * @param $lat float
     * @param $long float
     * @param $time string/int
     * Note $time can be null/empty string from endomondo API
     */
    public function add($lat, $long, $time)
    {
        $point = [];
        $point['long'] = $long;
        $point['lat'] = $lat;
        if (is_string($time)) {
            $time=strtotime($time);
        }

        $this->addPointToGPX($lat, $long, $time);
        $this->timezone = $this->timezoneFromCoords($lat, $long, $time);
        if ($time) {
            $this->start_day = strtotime(date("Y-m-d\T00:00:00 e", $time));
        }
        if (!$this->previous) {
            $this->previous = $point;
        } else {
            $distance = $this->distance($this->previous['lat'], $this->previous['long'], $point['lat'], $point['long']);
            date_default_timezone_set($this->timezone);
            $timestring=date("Y-m-d\TH:i:s e", $time);
            if (!isset($this->splits[$this->day($time)])) {
                $this->splits[$this->day($time)] = 0;
                $this->start_times[$this->day($time)] = $timestring;
                $this->end_times[$this->day($time)] = $timestring;
            }
            $this->splits[$this->day($time)] += $distance;
            $this->end_times[$this->day($time)] = $timestring;
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

    private function addPointToGPX($lat, $long, $time)
    {
        if (!$this->generateGPX) {
            return;
        }
        if (!$time) {
            $this->bad_points++;
        } else {
            date_default_timezone_set("UTC");
            $time_str=date("Y-m-d\TH:i:s\Z",$time);
            $this->good_points++;
            $this->gpx .= "<trkpt lat=\"$lat\" lon=\"$long\"><time>$time_str</time></trkpt>";
            $this->gpx .= "\n";
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
        if (!isset($this->googleMaps) || !$this->googleMaps) {
            return $default;
        }
        if (!$time) {
            $time = $this->start_day;
        }
        $tz=$this->googleMaps->timezoneFromCoords($lat,$long,$time);
        if (!$tz) {
            $error = $this->googleMaps->getError();
            $this->error.= $error;
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