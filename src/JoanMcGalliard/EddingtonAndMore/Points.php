<?php
/**
 * Created by PhpStorm.
 * User: jem
 * Date: 10/01/2016
 * Time: 22:53
 */

namespace JoanMcGalliard;


class Points
{

    private $points;

    /**
     * Points constructor.
     */
    public function __construct()
    {
        $this->points = [];
    }

    public function add($lat, $long, $time)
    {
        // expects time as an int (seconds since epoch) or a string with timezone
        $this->points[is_string($time) ? strtotime($time) : $time] = [$lat, $long];
    }

    public function gpx()
    {
        $default_tz = date_default_timezone_get();
        date_default_timezone_set("UTC");

        $result = '<?xml version="1.0" encoding="UTF-8"?> <gpx creator="Eddington &amp; More" >';
        $result .= "<trk><trkseg>";
        $result .= "\n";
        foreach ($this->points as $time => $point) {
            $lat = $point[0];
            $lon = $point[1];
            $timestr = date("Y-m-d\Th:m:s", $time)."Z";
            $result .= "<trkpt lat=\"$lat\" lon=\"$lon\"><time>$timestr</time></trkpt>";
            $result .= "\n";
        }
        $result .= "</trkseg> </trk> </gpx>";
        date_default_timezone_set($default_tz);

        return $result;

    }
}