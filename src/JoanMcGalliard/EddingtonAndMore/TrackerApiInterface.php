<?php
namespace JoanMcGalliard;

// For classes that connected to tracking websites (eg strava, endomondo_
interface trackerApiInterface
{
    const METRE_TO_MILE = 0.00062137119224;
    const METRE_TO_KM = 0.001;
    const METRE_TO_FOOT = 3.280;

    public function isConnected();
    public function getUserId();

    /*
     * Returns an array
     *  [ ["Y-m-d" => [ <array of rides> ] .....]
     *
     * each array of rides is all the rides on that day, they are associative arrays
     * ['distance' => float, // distance in metres
     *  'moving_time' => int, // seconds
     *  'max_speed' => float // metres per second
     *  'name' => $activity->name
     *  'strava_id' => string
     *  'mcl_id' => string
     *  'endo_id' => string
     *  'bike' => string //bike_id
     *  'total_elevation_gain' => float // metre
     *
     * Only distance is required for Eddington calculation.
     */
    public function getRides($start_date, $end_date);

}

?>