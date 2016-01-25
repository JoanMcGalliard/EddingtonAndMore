<?php
namespace JoanMcGalliard\EddingtonAndMore;

require_once 'TrackerApiInterface.php';
require_once 'Points.php';

use ArrayObject;

class EndomondoApi implements trackerApiInterface
{
    const BASE_URL = "https://api.mobile.endomondo.com/mobile/";
    const COUNTRY = 'GB';
    const TWENTY_FOUR_HOURS = 86400;
    protected $auth = null;
    protected $connected = false;
    protected $deviceId = "";
    private $lastPage = null;
    private $errorMessage = null;
    private $googleApiKey;
    private $lastTZRequestedFromGoogle = null;
    private $tz;
    private $splitOvernightRides = false;
    private $userId;

    /**
     * EndomondoApi constructor.
     * @param string $deviceId
     */
    public function __construct($deviceId, $googleApiKey, $tz)
    {
        $this->deviceId = $deviceId;
        $this->tz = $tz;
        $this->googleApiKey = $googleApiKey;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

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

    public function setAuth($auth)
    {
        $this->auth = $auth;
    }

    public function isConnected()
    {
        if (!$this->auth) {
            $this->connected = false;
        } else if (!$this->connected) {
            $page = $this->getPage('api/profile/account/get');
            $this->connected = isset(json_decode($page)->data->id);
            $this->userId = json_decode($page)->data->id;
        }
        return $this->connected;
    }

    protected function getPage($url, $params = [])
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
        $this->lastPage = $page;
        $this->lastPath = $path;
        return $page;
    }

    public function activityUrl($workoutId)
    {
        return "https://www.endomondo.com/users/$this->userId/workouts/$workoutId";
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
                    $this->connected = true;
                    return $this->auth;
                }

            }
            $this->errorMessage = $page;

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

        $before = date("Y-m-d H:i:s e", $end_date);
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
                $before = date("Y-m-d H:i:s e", $timestamp);
                if ($start_date > $timestamp) {
                    $done = true;
                    break;
                }
                $id = $ride->id;
                if (!in_array(intval($ride->sport), [1, 2, 3]) || !$ride->is_valid) {
                    continue; // not a bike ride or not include in stats
                }
                $record = [];
                date_default_timezone_set($this->tz);
                $date = date("Y-m-d", $timestamp);
                $record['distance'] = $ride->distance / self::METRE_TO_KM;
                $record['elapsed_time'] = $ride->duration;
                if (isset($ride->speed_max)) {
                    $record['max_speed'] = $ride->speed_max / (60 * 60 * self::METRE_TO_KM);
                }
                $record['endo_id'] = $id;
                if (isset($ride->ascent)) {
                    $record['ascent'] = $ride->ascent;
                }
                $record['start_time'] = $ride->start_time;
                $record['name'] = isset($ride->name) ? $ride->name : '';
                if ($this->splitOvernightRides && $this->isOverNightRide($ride)) {
                    $points = $this->getPoints($record['endo_id']);
                    foreach ($points->getSplits() as $split_date => $split) {
                        $new = new ArrayObject($record);
                        $new['distance'] = $split;
                        $records[$split_date][] = $new;
                    }
                    $points=null; // free memory
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

    protected function getPageWithDot($url, $params)
    {
        $return = $this->getPage($url, $params);
        self::dot();
        return $return;
    }

    private function dot()
    {
        echo ".";
        flush();
    }

    private function isOverNightRide($ride)
    {
        date_default_timezone_set($this->tz);
        $start = strtotime($ride->start_time);
        $midnight = strtotime(date("Y-m-d", $start));
        $start_seconds = $start - $midnight;
        if (($start_seconds + $ride->duration) > self::TWENTY_FOUR_HOURS) {
            return true;
        }
        return false;
    }

    public function getPoints($workoutId)
    {
        $url = "api/workout/get";
        $params = ['fields' => 'points,simple', 'workoutId' => $workoutId];
        $page = $this->getPageWithDot($url, $params);
        $json_decode = json_decode($page);
        $points = new Points($json_decode->start_time, $this->googleApiKey);
        if (is_array($json_decode->points)) {
            foreach ($json_decode->points as $point) {
                if (isset($point->lat) && isset($point->lng) && isset($point->time)) {
                    $points->add($point->lat, $point->lng, $point->time);
                }
            }
        }
        return $points;
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

        $points = (new Points(date("Y-m-d", $timestamp), $this->googleApiKey));
        if (!$this->lastTZRequestedFromGoogle ||
            $points->distance($this->lastTZRequestedFromGoogle->lat, $this->lastTZRequestedFromGoogle->long, $lat, $long) > 100000
        ) {

            $tz = $points->timezoneFromCoords($lat, $long, $timestamp);
            $this->lastTZRequestedFromGoogle = new \stdClass();
            $this->lastTZRequestedFromGoogle->tz = $tz;
            $this->lastTZRequestedFromGoogle->lat = $lat;
            $this->lastTZRequestedFromGoogle->long = $long;
        }
        $points=null;
        return $this->lastTZRequestedFromGoogle->tz;

    }

}

?>