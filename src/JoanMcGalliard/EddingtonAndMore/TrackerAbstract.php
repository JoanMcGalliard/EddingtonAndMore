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
    protected $pending_uploads = [];
    protected $fileUploadTimeout = 300;
    protected $splitOvernightRides;
    protected $api;

    /**
     * @param boolean $splitOvernightRides
     */
    public function setSplitOvernightRides($splitOvernightRides)
    {
        $this->splitOvernightRides = $splitOvernightRides;
    }

    /**
     * @param int $fileUploadTimeout
     */
    public function setFileUploadTimeout($fileUploadTimeout)
    {
        $this->fileUploadTimeout = $fileUploadTimeout;
    }




    /*
     * Returns true if currently connected (ie can get data) from specific tracker.
     */
    abstract public function isConnected();


    /*
     * Returns an array
     *  [ ["Y-m-d" => [ <array of rides> ] .....]
     *
     * each array of rides is all the rides on that day, they are associative arrays
     * ['distance' => float, // distance in metres
     *  'moving_time' => int, // seconds
     *  'elapsed_time' => int, // seconds
     *  'start_time' => string // date time
     *  'max_speed' => float // metres per second
     *  'name' => $activity->name
     *  'strava_id' => string
     *  'mcl_id' => string
     *  'endo_id' => string
     *  'rwgps_id' => string
     *  'bike' => string //bike_id
     *  'total_elevation_gain' => float // metre
     *
     *
     * Only distance and date are required for Eddington calculation.
     *
     * $start & $end_dates are seconds since epoch, or null.
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