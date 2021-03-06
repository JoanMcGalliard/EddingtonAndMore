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
    protected $timezone;

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
    abstract public function getOvernightActivities();

    /**
     * @param $id
     * @return array eg array('brand' => "Giant", 'model' => "Avail 2")
     */
    abstract public function getBike($id);
    abstract public function bikeMatch($brand, $model, $id);

    /**
     * @param $date - string
     * @param $ride - object
     * @param $points - instance of Points class (or null)
     * @return string - id of new activity added, or null if error.
     */
    abstract public function addRide($date, $ride, $points);
    abstract public function activityUrl($id);
    abstract public function getPoints($id,$tz);
    abstract public function waitForPendingUploads($sleep);

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

    protected function isOvernight($start_time, $tz, $duration)
    {
        date_default_timezone_set($tz);
        $start = strtotime($start_time);
        $midnight = strtotime(date("Y-m-d", $start));
        $start_seconds = $start - $midnight;
        return (($start_seconds + $duration) / self::TWENTY_FOUR_HOURS >1);
    }

    protected function rareDot()
    {
        static $rareDotCount;
        if (!isset($rareDotCount)) {
            $rareDotCount = 1;
        }
        $rareDotCount++;
        if ($rareDotCount > 1000) {
            $this->output('.');
            $rareDotCount = 1;
        }
        flush();
    }

    public static function generateExternalId($ride)
    {
        if (isset($ride['endo_id'])) {
        return "endomondo_".$ride['endo_id'];
        }
        return null;
    }

    public function stravaActivityUrl($activityId)
    {
        return "http://www.strava.com/activities/$activityId";
    }
    public function endomondoActivityUrl($workoutId, $useriId)
    {
        return "https://www.endomondo.com/users/" . $useriId . "/workouts/$workoutId";
    }


}

?>