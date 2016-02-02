<?php
namespace JoanMcGalliard\EddingtonAndMore;

// For classes that connected to tracking websites (eg strava, endomondo_
abstract class trackerAbstract
{
    const METRE_TO_MILE = 0.00062137119224;
    const METRE_TO_KM = 0.001;
    const METRE_TO_FOOT = 3.280;
    const TWENTY_FOUR_HOURS = 86400;
    const RETRIES = 3;
    protected $echoCallback;
    protected $error;
    protected $userId;


    abstract public function isConnected();


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
    abstract public function getRides($start_date, $end_date);

    public function getError()
    {
        return $this->error;
    }


    protected function output($msg)
    {
        call_user_func($this->echoCallback, $msg);
    }

    public function getUserId()
    {
        return $this->userId;
    }

}

?>