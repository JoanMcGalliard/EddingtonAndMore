<?php
namespace JoanMcGalliard\EddingtonAndMore;

require_once 'TrackerAbstract.php';
require_once 'Points.php';
require_once 'JoanMcGalliard/EddingtonAndMore/APIs/EndomondoApi.php';
use ArrayObject;
use JoanMcGalliard;


class Endomondo extends trackerAbstract
{
    protected $deviceId = "";
    protected $connected = false;
    private $googleApiKey;
    private $timezone;
    private $splitOvernightRides = false;

    public function __construct($deviceId, $googleApiKey, $tz, $echoCallback, $api = null)
    {
        $this->deviceId = $deviceId;
        $this->timezone = $tz;
        $this->googleApiKey = $googleApiKey;
        $this->echoCallback = $echoCallback;
        if ($api) {
            $this->api = $api;
        } else {
            $this->api = new JoanMcGalliard\EndomondoApi();
        }
    }


    public function setAuth($auth)
    {
        $this->api->setAuth($auth);
    }

    public function isConnected()
    {
        if (!$this->api->getAuth()) {
            $this->connected = false;
        } else if (!$this->connected) {
            $page = $this->api->getPage('api/profile/account/get');
            if (isset(json_decode($page)->data->id)) {
                $this->connected = true;
                $this->userId = json_decode($page)->data->id;
            } else {
                $this->connected = false;
            }
        }
        return $this->connected;
    }

    public function activityUrl($workoutId)
    {
        return "https://www.endomondo.com/users/{$this->userId}/workouts/$workoutId";
    }

    public function gpxDownloadUrl($workoutId)
    {
        return "https://www.endomondo.com/rest/v1/users/{$this->userId}/workouts/$workoutId/export?format=GPX";
    }


    public function connect($username, $password)
    {
        $this->error = "";
        $auth = $this->api->connect($username, $password, $this->deviceId);
        if (!$auth) {
            $this->error .= $this->api->getError();
        }
        return $auth;
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
        $this->error = "";
        $records = [];
        if (!isset($end_date) || !is_int($end_date)) {
            $end_date = time();
        }
        if (!isset($start_date) || !is_int($start_date)) {
            $start_date = 0;
        }
        $default_tz = date_default_timezone_get();
        date_default_timezone_set($this->timezone);
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
            for ($i = 0; $i < self::RETRIES; $i++) {
                $page = $this->getPageWithDot("api/workouts", $params);
                $json_decode = json_decode($page);
                if ($json_decode) break;
                log_msg("retrying");
            }
            if (!$json_decode) {
                // three tries, and we data
                $this->error .= "$page<br>";
                $done = true;
            } else {
                foreach ($json_decode->data as $ride) {
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
                    date_default_timezone_set($this->timezone);
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
                        if (!$points) {
                            $this->error .= "Could not split overnight ride on $ride->start_time due to errors.<br>";
                            $records[$date][] = $record;
                        } else {
                            foreach ($points->getSplits() as $split_date => $split) {
                                $new = new ArrayObject($record);
                                $new['distance'] = $split;
                                $records[$split_date][] = $new;
                            }
                        }
                        $points = null; // free memory
                    } else {
                        $records[$date][] = $record;
                    }
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
        $return = $this->api->getPage($url, $params);
        $this->output(".");
        return $return;
    }

    private function isOverNightRide($ride)
    {
        date_default_timezone_set($this->timezone);
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
        for ($i = 0; $i < self::RETRIES; $i++) {
            $page = $this->getPageWithDot($url, $params);
            $json_decode = json_decode($page);
            if ($json_decode) break;
            log_msg("retrying");
        }
        if (!$json_decode) {
            // three tries, and we can't get points
            $this->error .= "$page<br>";
            return null;
        }
        $points = new Points($json_decode->start_time, $this->echoCallback);
        $points->setGenerateGPX(true);
        $points->setGoogleApiKey($this->googleApiKey);
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

    public function gpxDownload($endo_id)
    {
        https://www.endomondo.com/rest/v1/users/2859253/workouts/655334427/export?format=GPX
    }

}

?>