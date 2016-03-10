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
    private $googleMaps;
    protected $timezone;

    public function __construct($deviceId, $googleMaps, $tz, $echoCallback, $api = null)
    {
        $this->deviceId = $deviceId;
        $this->timezone = $tz;
        $this->googleMaps = $googleMaps;
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

    public function getUserId()
    {
        $this->isConnected();
        return parent::getUserId();
    }

    public function isConnected()
    {
        if (!$this->connected && $this->api->getAuth()) {
            $page = $this->api->get('api/profile/account/get');
            if (isset(json_decode($page)->data->id)) {
                $this->connected = true;
                $this->userId = json_decode($page)->data->id;
            } else {
                $this->api->setAuth(null);
                $this->error = $page;
            }
        }
        return $this->connected;
    }

    public function activityUrl($workoutId)
    {
        if (!$this->getUserId()) {
            return null;
        }

        return $this->endomondoActivityUrl($workoutId,$this->getUserId());
    }

    public function gpxDownloadUrl($workoutId)
    {
        if (!$this->getUserId()) {
            return null;
        }
        return "https://www.endomondo.com/rest/v1/users/" . $this->getUserId() . "/workouts/$workoutId/export?format=GPX";
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

    public function getWorkout($workoutId)
    {
        if (!$this->isConnected()) {
            $this->error .= "Not connected to Endomondo";
            return null;
        }
        $params = array("workoutId" => $workoutId, "fields" => "basic");
        $page = $this->api->get('api/workout/get', $params);
        if (!isset($page)) {
            $this->error .= $this->api->getError();
            return null;
        }
        $ride = json_decode($page);
        if (!isset($ride)) {
            $this->error .= "API returned unexpected value: $page";
            return null;
        }
        if (isset($ride->error)) {
            if (isset($ride->error->type)) {
                $this->error .= $ride->error->type;
            } else {
                $this->error .= json_encode($ride->error);
            }
            return null;
        }
        if (!isset($ride->id) || !isset($ride->owner_id) || !isset($ride->distance) || !isset($ride->start_time)) {
            $this->error .= "Response not in a recognised format: $page";
            return null;
        }
        if ($ride->owner_id <> $this->getUserId()) {
            $this->error .= "Workout does not belong to current user.";
            return null;
        }
        if ($ride->id <> $workoutId) {
            $this->error .= "Endomondo returned the wrong workout";
            return null;
        }
        if (!$this->isValid($ride)) {
            $this->error .= "Not a valid ride";
            return null;
        }
        $workout = new \stdClass();
        $workout->distance = $ride->distance / self::METRE_TO_KM;
        $workout->startTime = strtotime($ride->start_time);
        $workout->id = $workoutId;
        return $workout;
    }

    // todo split overnight rides
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
                /** @var \stdClass $json_decode */
                $json_decode = json_decode($page);
                if ($json_decode) break;
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
                    if (!$this->isValid($ride)) {
                        continue; // not a bike ride or not include in stats
                    }
                    $record = [];
                    date_default_timezone_set($this->timezone);
                    $date = date("Y-m-d", $timestamp);
                    $record['distance'] = $ride->distance / self::METRE_TO_KM;
                    $record['elapsed_time'] = $ride->duration;
                    $record['moving_time'] = $ride->duration;
                    if (isset($ride->speed_max)) {
                        $record['max_speed'] = $ride->speed_max / (60 * 60 * self::METRE_TO_KM);
                    }
                    $record['endo_id'] = $id;
                    if (isset($ride->ascent)) {
                        $record['total_elevation_gain'] = $ride->ascent;
                    }
                    $record['start_time'] = $ride->start_time;
                    $record['name'] = isset($ride->name) ? $ride->name : '';
                    if ($this->splitOvernightRides && $this->isOvernightRide($ride)) {
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

        return $records;

    }

    protected function getPageWithDot($url, $params)
    {
        $return = $this->api->get($url, $params);
        $this->output(".");
        return $return;
    }

    protected function isOvernightRide($ride)
    {
        return parent::isOvernight($ride->start_time, $this->timezone, $ride->duration);
    }

    public function getPoints($workoutId, $tz=null)
    {
        $url = "api/workout/get";
        $params = ['fields' => 'points,simple', 'workoutId' => $workoutId];
        for ($i = 0; $i < self::RETRIES; $i++) {
            $page = $this->getPageWithDot($url, $params);
            $json_decode = json_decode($page);
            if ($json_decode) break;
        }
        if (!$json_decode) {
            // three tries, and we can't get points
            $this->error .= "$page<br>";
            return null;
        }
        $points = new Points($json_decode->start_time, $this->echoCallback, $this->googleMaps);
        $points->setGenerateGPX(true);
        if (isset($json_decode->points) && is_array($json_decode->points)) {
            foreach ($json_decode->points as $point) {
                if (isset($point->lat) && isset($point->lng) && isset($point->time)) {
                    $points->add($point->lat, $point->lng, $point->time);
                }
            }
        }
        return $points;
    }

    /**
     * @param $ride
     * @return bool
     */
    private function isValid($ride)
    {
        return isset($ride->sport) && in_array(intval($ride->sport), [1, 2, 3]) && isset($ride->is_valid) && $ride->is_valid;
    }

    public function getOvernightActivities()
    {
        // TODO: Implement getOvernightActivities() method.
    }

    public function getBike($id)
    {
        return null; //Endomondo doesn't support bikes
    }

    public function bikeMatch($brand, $model, $id)
    {
        return null; //Endomondo doesn't support bikes
    }

    public function addRide($date, $ride, $points)
    {
        // TODO: Implement addRide() method.
    }

    public function waitForPendingUploads($sleep)
    {
        // TODO: Implement waitForPendingUploads() method.
    }
}

?>