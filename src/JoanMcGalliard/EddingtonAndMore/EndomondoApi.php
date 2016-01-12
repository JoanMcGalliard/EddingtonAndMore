<?php
namespace JoanMcGalliard;

require_once 'TrackerApiInterface.php';
require_once 'Points.php';

use ArrayObject;
class EndomondoApi implements trackerApiInterface
{
    const BASE_URL = "https://api.mobile.endomondo.com/mobile/";
    const COUNTRY = 'GB';
    protected $auth = null;
    protected $connected = false;
    protected $deviceId = "";
    private $lastPage = null;
    private $errorMessage=null;
    private $googleApiKey;
    private $lastTZRequestedFromGoogle=null;
    private $tz;
    private $splitOvernightRides=false;

    /**
     * @return null
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @return null
     */
    public function getLastPage()
    {
        return $this->lastPage;
    }

    /**
     * EndomondoApi constructor.
     * @param string $deviceId
     */
    public function __construct($deviceId,$googleApiKey,$tz)
    {
        $this->deviceId = $deviceId;
        $this->tz = $tz;
        $this->googleApiKey=$googleApiKey;
        define( "TWENTY_FOUR_HOURS", 60 * 60 * 24);
    }

    public function setAuth($auth)
    {
        $this->auth = $auth;
    }

    public function isConnected()
    {
        if (!$this->auth) {
            $this->connected = false;
        } else if (!$this->connected) {
            $page = $this->getPage('api/workouts', ['maxResults' => 0]);
            $this->connected = is_array(json_decode($page)->data);
        }
        return $this->connected;
    }

    protected function getPageWithDot($url, $params) {
        $return=$this->getPage($url,$params);
        self::dot();
        return $return;
    }
    protected function getPage($url, $params)
    {
        if (!$this->auth) {
            return null;
        }
        if (!$params) {
            $params = [];
        }
        $params["authToken"] = $this->auth;

        $path = self::BASE_URL . $url . "?" . http_build_query($params);
        $process = curl_init($path);

        curl_setopt($process, CURLOPT_HEADER, 0);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);

        $page = curl_exec($process);
        curl_close($process);
        $this->lastPage=$page;
        $this->lastPath=$path;
        return $page;
    }

    public function connect($username, $password)
    {
        $url = "auth";
        $params = [];
        $params['deviceId'] = $this->getDeviceId();
        $params['action'] = 'pair';
        $params['email'] = $username;
        $params['password'] = $password;
        $params['country'] = self::COUNTRY;

        {

            $path = self::BASE_URL . $url . "?" . http_build_query($params);
            $process = curl_init($path);
            curl_setopt($process, CURLOPT_HEADER, 0);
            curl_setopt($process, CURLOPT_TIMEOUT, 30);
            curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
            $page = curl_exec($process);
            curl_close($process);
            $pattern = "/^authToken=(.*)/";
            foreach (explode("\n", $page) as $line) {
                if (preg_match($pattern, $line, $matches) > 0) {
                    $this->auth = $matches[1];
                    $this->connected=true;
                    return $this->auth;
                }

            }
            $this->errorMessage=$page;

            return null;
        }

    }

    /**
     * @return string
     */
    public function getDeviceId()
    {
        return $this->deviceId;
    }

    public function getRides($start_date, $end_date)
    {
        $records = [];
        if (!isset($end_date) || !is_int($end_date)) {
            $end_date = time();
        }
        if (!isset($start_date) || !is_int($start_date)) {
            $start_date = 0;
        }
        $default_tz = date_default_timezone_get();
        date_default_timezone_set($this->tz);
        $maxResults = 500;
        $params = [];
        date_default_timezone_set("UTC");

        $before = date("Y-m-d h:m:s e", $end_date);
        $done = false;
        $count = 0;
        while (!$done) {

            $params['before'] = $before;
            $params['maxResults'] = $maxResults;
            $params['fields'] = 'simple,basic';
            $page = $this->getPageWithDot("api/workouts", $params);

            foreach (json_decode($page)->data as $ride) {
                $count++;
                $timestamp = strtotime($ride->start_time);
                date_default_timezone_set("UTC");
                $before = date("Y-m-d h:m:s e", $timestamp);
                if ($start_date > $timestamp) {
                    $done = true;
                    break;
                }
                $id = $ride->id;
                if (!in_array(intval($ride->sport), [1, 2, 3]) || !$ride->is_valid ) {
                    continue; // not a bike ride or not include in stats
                }
                $record = [];
                date_default_timezone_set($this->tz);
                $date = date("Y-m-d", $timestamp);
                $record['distance'] = $ride->distance / self::METRE_TO_KM;
                $record['moving_time'] = $ride->duration;
                $record['max_speed'] = $ride->speed_max / (60 * 60 * self::METRE_TO_KM);
                $record['endo_id'] = $id;
                $record['ascent'] = $ride->ascent;
                if ($this->splitOvernightRides &&  $this->isOverNightRide($ride)) {
                    $points=$this->getPoints($record['endo_id']);
                    foreach ($points->getSplits() as $split_date => $split){
                        $new=new ArrayObject($record);
                        $new['distance']=$split;
                        $records[$split_date][]=$new;
                    }
                } else {
                    $records[$date][] = $record;
                }
            }

            if (sizeof(json_decode($page)->data) < $maxResults) {
                break;
            }

        }

        date_default_timezone_set($default_tz);
        return $records;

    }
    public function getPoints($workoutId) {
        $url="api/workout/get";
        $params=['fields' => 'points', 'workoutId' => $workoutId];
        $page = $this->getPageWithDot($url, $params);
        $points=new Points($this->googleApiKey);
        $ts=time();
        foreach (json_decode($page)->points as $point) {
            $points->add($point->lat,$point->lng,$point->time);
        }
        return $points;
    }

    private function isOverNightRide($ride){
        date_default_timezone_set($this->tz);
        $start=strtotime($ride->start_time);
        $midnight=strtotime(date("Y-m-d", $start));
        $start_seconds=$start-$midnight;
        if (($start_seconds+$ride->duration)>TWENTY_FOUR_HOURS) {
            return true;
        }
        return false;
    }

    /**
     * @param boolean $splitOvernightRides
     */
    public function setSplitOvernightRides($splitOvernightRides)
    {
        $this->splitOvernightRides = $splitOvernightRides;
    }

    private function getTZ($lat, $long, $timestamp)
    {

        $points = (new Points($this->googleApiKey));
        if (!$this->lastTZRequestedFromGoogle ||
            $points->distance($this->lastTZRequestedFromGoogle->lat, $this->lastTZRequestedFromGoogle->long, $lat, $long) > 100000
        ) {

            $tz = $points->timezoneFromCoords($lat, $long, $timestamp);
            $this->lastTZRequestedFromGoogle = new \stdClass();
            $this->lastTZRequestedFromGoogle->tz = $tz;
            $this->lastTZRequestedFromGoogle->lat = $lat;
            $this->lastTZRequestedFromGoogle->long = $long;
        }
        return $this->lastTZRequestedFromGoogle->tz;

    }

    private function dot() {
        echo ".";
        flush();
    }

}

?>