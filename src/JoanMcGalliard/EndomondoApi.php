<?php
namespace JoanMcGalliard;

require_once 'TrackerApiInterface.php';

class EndomondoApi implements trackerApiInterface
{
    const BASE_URL = "https://api.mobile.endomondo.com/mobile/";
    const COUNTRY = 'GB';
    protected $auth = null;
    protected $connected = false;
    protected $deviceId = "";
    private $lastPage = null;
    private $errorMessage=null;

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
        vd( $this->lastPage);
        vd( $this->lastPath);
        return $this->lastPage;
    }

    /**
     * EndomondoApi constructor.
     * @param string $deviceId
     */
    public function __construct($deviceId)
    {
        $this->deviceId = $deviceId;
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
        date_default_timezone_set("UTC");
        $maxResults = 500;
        $params = [];
        $before = date("Y-m-d h:m:s e", $end_date);
        $done = false;
        $count = 0;
        while (!$done) {

            $params['before'] = $before;
            $params['maxResults'] = $maxResults;
            $params['fields'] = 'simple,basic';
            $page = $this->getPage("api/workouts", $params);

            foreach (json_decode($page)->data as $ride) {
                $count++;
                $timestamp = strtotime($ride->start_time);
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
                $date = date("Y-m-d", $timestamp);
                $record['distance'] = $ride->distance / self::METRE_TO_KM;
                $record['moving_time'] = $ride->duration;
                $record['max_speed'] = $ride->speed_max / (60 * 60 * self::METRE_TO_KM);
                $record['endo_id'] = $id;
                $record['ascent'] = $ride->ascent;
                $records[$date][] = $record;

            }

            if (sizeof(json_decode($page)->data) < $maxResults) {
                break;
            }

        }

        date_default_timezone_set($default_tz);
        return $records;

    }
}

?>