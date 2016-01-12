<?php
/**
 * Created by PhpStorm.
 * User: jem
 * Date: 10/01/2016
 * Time: 22:53
 */

namespace JoanMcGalliard;

use stdClass, DateTime;

class Points
{

    private $points;
    private $timezone;
    private $distances;
    private $googleApiKey;
    private $previous=null;

    private $total_distance_day = [];
    private $start_day = 0; // midnight local time in seconds on the day this ride started
    private $current_day="";
    private $current_time=0;

    private $gpx;
    /**
     * Points constructor.
     */
    public function __construct($googleApiKey)
    {
        $this->points = [];
        $this->googleApiKey = $googleApiKey;
        $this->gpx = '<?xml version="1.0" encoding="UTF-8"?> <gpx creator="Eddington &amp; More" >';
        $this->gpx .= "<trk><trkseg>";
        $this->gpx .= "\n";
    }

    public function add($lat, $long, $time)
    {
        $point = new stdClass();
        $point->long = $long;
        $point->lat = $lat;
        $this->addPointToGPX($lat,$long,$time);
        if (!isset($this->timezone) && $time) {
            $this->timezone = $this->timezoneFromCoords($lat , $long, strtotime($time));
            $default_timezone = date_default_timezone_get();
            date_default_timezone_set($this->timezone);

            $this->start_day = strtotime(date("Y-m-d\T00:00:00 e", strtotime($time)));
            date_default_timezone_set($default_timezone);

        }
        if (!$this->previous) {
            $this->previous=$point;
            $this->day($time); //sets current day
        } else {
            $distance=$this->distance($this->previous->lat, $this->previous->long, $point->lat,$point->long);
            $this->distances[$this->day($time)]+=$distance;
            $this->previous=$point;
        }
    }

    public function timezoneFromCoords($lat, $long, $time)
    {
        $params = ['location' => "$lat,$long",
            'timestamp' => $time, 'key', $this->googleApiKey];
        $params["authToken"] = $this->auth;

        $url = "https://maps.googleapis.com/maps/api/timezone/json" . "?" . http_build_query($params);
        $process = curl_init($url);

        curl_setopt($process, CURLOPT_HEADER, 0);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);

        $page = curl_exec($process);
        curl_close($process);
        $tz = json_decode($page)->timeZoneId;
        if (!$tz) {
            $tz = "UTC";
        }
        return $tz;
    }

    public function addPointToGPX($lat,$long,$timestr)
    {
        $default_tz = date_default_timezone_get();
        date_default_timezone_set("UTC");

        if ($timestr) {$this->current_time=strtotime($timestr);}
        $time = date("Y-m-d\Th:m:s", $this->current_time) . "Z";
        $this->gpx .= "<trkpt lat=\"$lat\" lon=\"$long\"><time>$time</time></trkpt>";
        $this->gpx .= "\n";
        date_default_timezone_set($default_tz);

    }

public function  gpx() {
    return $this->gpx. "</trkseg> </trk> </gpx>";
    }

    public function calculateDistance($first = 0, $last = -1)
    {
        $distance = 0;
        foreach ($this->distances as $day => $day_distance) {
            $distance+=$day_distance;

        }
        return $distance;

    }

    /*thank you stackexchange!
     * http://stackoverflow.com/questions/569980/how-to-calculate-distance-from-a-gpx-file
     */
    public function distance($lat1, $long1, $lat2, $long2)
    {

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


    private function day($timestring)
    {

        if ($timestring) {


            $default_timezone = date_default_timezone_get();

            date_default_timezone_set($this->timezone);

            $this->current_day = date("Y-m-d", strtotime($timestring));
            date_default_timezone_set($default_timezone);
        }
        return $this->current_day;


    }
}