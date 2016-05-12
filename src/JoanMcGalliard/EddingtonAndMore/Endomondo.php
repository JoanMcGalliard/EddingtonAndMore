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


    private function addtrack() {
        //This doesn't work yet, so made private.  Not sure how to add track to endomondo
        $points=array(array('lat'=>"51.4633670", 'lon'=>"-0.3245220", 'time'=>"2016-03-13 21:35:46 UTC"),
            array('lat'=>"51.4633670", 'lon'=>"-0.3245220", 'time'=>"2016-03-13 21:36:00 UTC"),
            array('lat'=>"51.4635540", 'lon'=>"-0.3245110", 'time'=>"2016-03-13 21:36:28 UTC"),
            array('lat'=>"51.4637810", 'lon'=>"-0.3243980", 'time'=>"2016-03-13 21:36:35 UTC"),
            array('lat'=>"51.4640260", 'lon'=>"-0.3242900", 'time'=>"2016-03-13 21:36:40 UTC"),
            array('lat'=>"51.4642230", 'lon'=>"-0.3242450", 'time'=>"2016-03-13 21:36:48 UTC"),
            array('lat'=>"51.4643660", 'lon'=>"-0.3243340", 'time'=>"2016-03-13 21:37:00 UTC"),
            array('lat'=>"51.4644310", 'lon'=>"-0.3248120", 'time'=>"2016-03-13 21:37:17 UTC"),
            array('lat'=>"51.4644940", 'lon'=>"-0.3254860", 'time'=>"2016-03-13 21:37:25 UTC"),
            array('lat'=>"51.4645030", 'lon'=>"-0.3259260", 'time'=>"2016-03-13 21:37:31 UTC"),
            array('lat'=>"51.4645310", 'lon'=>"-0.3261410", 'time'=>"2016-03-13 21:37:34 UTC"),
            array('lat'=>"51.4645570", 'lon'=>"-0.3265440", 'time'=>"2016-03-13 21:37:40 UTC"),
            array('lat'=>"51.4645550", 'lon'=>"-0.3269200", 'time'=>"2016-03-13 21:37:45 UTC"),
            array('lat'=>"51.4645720", 'lon'=>"-0.3273040", 'time'=>"2016-03-13 21:37:51 UTC"),
            array('lat'=>"51.4645900", 'lon'=>"-0.3277040", 'time'=>"2016-03-13 21:37:58 UTC"),
            array('lat'=>"51.4646240", 'lon'=>"-0.3278560", 'time'=>"2016-03-13 21:38:08 UTC"),
            array('lat'=>"51.4647830", 'lon'=>"-0.3287620", 'time'=>"2016-03-13 21:38:25 UTC"),
            array('lat'=>"51.4647850", 'lon'=>"-0.3291140", 'time'=>"2016-03-13 21:38:30 UTC"),
            array('lat'=>"51.4647910", 'lon'=>"-0.3295770", 'time'=>"2016-03-13 21:38:36 UTC"),
            array('lat'=>"51.4647850", 'lon'=>"-0.3300170", 'time'=>"2016-03-13 21:38:42 UTC"),
            array('lat'=>"51.4648380", 'lon'=>"-0.3303940", 'time'=>"2016-03-13 21:38:49 UTC"),
            array('lat'=>"51.4648740", 'lon'=>"-0.3306170", 'time'=>"2016-03-13 21:38:56 UTC"),
            array('lat'=>"51.4648740", 'lon'=>"-0.3306170", 'time'=>"2016-03-13 21:39:03 UTC"),
            array('lat'=>"51.4648740", 'lon'=>"-0.3306170", 'time'=>"2016-03-13 21:39:03 UTC"),
            array('lat'=>"51.4648170", 'lon'=>"-0.3306810", 'time'=>"2016-03-13 21:39:16 UTC"),
            array('lat'=>"51.4647600", 'lon'=>"-0.3307450", 'time'=>"2016-03-13 21:39:16 UTC"),
            array('lat'=>"51.4646650", 'lon'=>"-0.3308070", 'time'=>"2016-03-13 21:39:19 UTC"),
            array('lat'=>"51.4645500", 'lon'=>"-0.3308760", 'time'=>"2016-03-13 21:39:22 UTC"),
            array('lat'=>"51.4643510", 'lon'=>"-0.3310170", 'time'=>"2016-03-13 21:39:26 UTC"),
            array('lat'=>"51.4641690", 'lon'=>"-0.3311450", 'time'=>"2016-03-13 21:39:30 UTC"),
            array('lat'=>"51.4639230", 'lon'=>"-0.3312780", 'time'=>"2016-03-13 21:39:35 UTC"),
            array('lat'=>"51.4637690", 'lon'=>"-0.3313470", 'time'=>"2016-03-13 21:39:38 UTC"),
            array('lat'=>"51.4634820", 'lon'=>"-0.3314780", 'time'=>"2016-03-13 21:39:43 UTC"),
            array('lat'=>"51.4631680", 'lon'=>"-0.3316010", 'time'=>"2016-03-13 21:39:49 UTC"),
            array('lat'=>"51.4629900", 'lon'=>"-0.3316400", 'time'=>"2016-03-13 21:39:52 UTC"),
            array('lat'=>"51.4628090", 'lon'=>"-0.3316720", 'time'=>"2016-03-13 21:39:56 UTC"),
            array('lat'=>"51.4625540", 'lon'=>"-0.3317330", 'time'=>"2016-03-13 21:40:01 UTC"),
            array('lat'=>"51.4622310", 'lon'=>"-0.3317740", 'time'=>"2016-03-13 21:40:07 UTC"),
            array('lat'=>"51.4619780", 'lon'=>"-0.3317950", 'time'=>"2016-03-13 21:40:12 UTC"),
            array('lat'=>"51.4617270", 'lon'=>"-0.3317980", 'time'=>"2016-03-13 21:40:17 UTC"),
            array('lat'=>"51.4614290", 'lon'=>"-0.3317990", 'time'=>"2016-03-13 21:40:23 UTC"),
            array('lat'=>"51.4611180", 'lon'=>"-0.3317490", 'time'=>"2016-03-13 21:40:28 UTC"),
            array('lat'=>"51.4604260", 'lon'=>"-0.3324460", 'time'=>"2016-03-13 21:41:02 UTC"),
            array('lat'=>"51.4603360", 'lon'=>"-0.3330790", 'time'=>"2016-03-13 21:41:10 UTC"),
            array('lat'=>"51.4602900", 'lon'=>"-0.3336000", 'time'=>"2016-03-13 21:41:16 UTC"),
            array('lat'=>"51.4602190", 'lon'=>"-0.3339110", 'time'=>"2016-03-13 21:41:20 UTC"),
            array('lat'=>"51.4601670", 'lon'=>"-0.3345160", 'time'=>"2016-03-13 21:41:28 UTC"),
            array('lat'=>"51.4601050", 'lon'=>"-0.3349280", 'time'=>"2016-03-13 21:41:34 UTC"),
            array('lat'=>"51.4600480", 'lon'=>"-0.3353490", 'time'=>"2016-03-13 21:41:40 UTC"),
            array('lat'=>"51.4600280", 'lon'=>"-0.3355470", 'time'=>"2016-03-13 21:41:43 UTC"),
            array('lat'=>"51.4599670", 'lon'=>"-0.3358490", 'time'=>"2016-03-13 21:41:48 UTC"),
            array('lat'=>"51.4599330", 'lon'=>"-0.3362730", 'time'=>"2016-03-13 21:41:54 UTC"),
            array('lat'=>"51.4599090", 'lon'=>"-0.3366640", 'time'=>"2016-03-13 21:41:59 UTC"),
            array('lat'=>"51.4598830", 'lon'=>"-0.3369160", 'time'=>"2016-03-13 21:42:02 UTC"),
            array('lat'=>"51.4598300", 'lon'=>"-0.3373260", 'time'=>"2016-03-13 21:42:07 UTC"),
            array('lat'=>"51.4598020", 'lon'=>"-0.3375690", 'time'=>"2016-03-13 21:42:10 UTC"),
            array('lat'=>"51.4597170", 'lon'=>"-0.3380500", 'time'=>"2016-03-13 21:42:17 UTC"),
            array('lat'=>"51.4596100", 'lon'=>"-0.3382680", 'time'=>"2016-03-13 21:42:23 UTC"),
            array('lat'=>"51.4595320", 'lon'=>"-0.3383670", 'time'=>"2016-03-13 21:42:30 UTC"),
            array('lat'=>"51.4595170", 'lon'=>"-0.3383420", 'time'=>"2016-03-13 21:42:35 UTC"),
            array('lat'=>"51.4595030", 'lon'=>"-0.3383170", 'time'=>"2016-03-13 21:42:39 UTC"),
            array('lat'=>"51.4594880", 'lon'=>"-0.3382920", 'time'=>"2016-03-13 21:42:39 UTC"),
            array('lat'=>"51.4593110", 'lon'=>"-0.3382580", 'time'=>"2016-03-13 21:42:46 UTC"),
            array('lat'=>"51.4591870", 'lon'=>"-0.3380820", 'time'=>"2016-03-13 21:42:54 UTC"),
            array('lat'=>"51.4589700", 'lon'=>"-0.3379250", 'time'=>"2016-03-13 21:43:05 UTC"),
            array('lat'=>"51.4589110", 'lon'=>"-0.3379470", 'time'=>"2016-03-13 21:43:08 UTC"),
            array('lat'=>"51.4588400", 'lon'=>"-0.3379510", 'time'=>"2016-03-13 21:43:14 UTC"),
            array('lat'=>"51.4587700", 'lon'=>"-0.3379540", 'time'=>"2016-03-13 21:44:42 UTC"),
            array('lat'=>"51.4586990", 'lon'=>"-0.3379580", 'time'=>"2016-03-13 21:44:42 UTC"),
            array('lat'=>"51.4585500", 'lon'=>"-0.3377430", 'time'=>"2016-03-13 21:45:00 UTC"),
            array('lat'=>"51.4585500", 'lon'=>"-0.3377430", 'time'=>"2016-03-13 22:04:47 UTC"),
            array('lat'=>"51.4585440", 'lon'=>"-0.3381600", 'time'=>"2016-03-13 22:04:59 UTC"),
            array('lat'=>"51.4587340", 'lon'=>"-0.3383170", 'time'=>"2016-03-13 22:05:19 UTC"),
            array('lat'=>"51.4588220", 'lon'=>"-0.3381770", 'time'=>"2016-03-13 22:05:25 UTC"),
            array('lat'=>"51.4588220", 'lon'=>"-0.3381770", 'time'=>"2016-03-13 22:05:33 UTC"),
            array('lat'=>"51.4588220", 'lon'=>"-0.3381770", 'time'=>"2016-03-13 22:06:20 UTC"),
            array('lat'=>"51.4588840", 'lon'=>"-0.3384810", 'time'=>"2016-03-13 22:06:55 UTC"),

            array('lat'=>"51.4596150", 'lon'=>"-0.3384200", 'time'=>"2016-03-13 22:14:30 UTC"),
            array('lat'=>"51.4596160", 'lon'=>"-0.3382490", 'time'=>"2016-03-13 22:14:39 UTC"),
            array('lat'=>"51.4597780", 'lon'=>"-0.3377390", 'time'=>"2016-03-13 22:14:52 UTC"),
            array('lat'=>"51.4598970", 'lon'=>"-0.3372950", 'time'=>"2016-03-13 22:15:00 UTC"),
            array('lat'=>"51.4599470", 'lon'=>"-0.3370620", 'time'=>"2016-03-13 22:15:04 UTC"),
            array('lat'=>"51.4600150", 'lon'=>"-0.3367440", 'time'=>"2016-03-13 22:15:09 UTC"),
            array('lat'=>"51.4600370", 'lon'=>"-0.3363590", 'time'=>"2016-03-13 22:15:15 UTC"),
            array('lat'=>"51.4600320", 'lon'=>"-0.3360760", 'time'=>"2016-03-13 22:15:19 UTC"),
            array('lat'=>"51.4600990", 'lon'=>"-0.3355320", 'time'=>"2016-03-13 22:15:26 UTC"),
            array('lat'=>"51.4601220", 'lon'=>"-0.3352360", 'time'=>"2016-03-13 22:15:31 UTC"),
            array('lat'=>"51.4601990", 'lon'=>"-0.3346180", 'time'=>"2016-03-13 22:15:38 UTC"),
            array('lat'=>"51.4602510", 'lon'=>"-0.3341200", 'time'=>"2016-03-13 22:15:44 UTC"),
            array('lat'=>"51.4603200", 'lon'=>"-0.3335650", 'time'=>"2016-03-13 22:15:50 UTC"),
            array('lat'=>"51.4606230", 'lon'=>"-0.3318610", 'time'=>"2016-03-13 22:16:27 UTC"),
            array('lat'=>"51.4611400", 'lon'=>"-0.3318570", 'time'=>"2016-03-13 22:16:43 UTC"),
            array('lat'=>"51.4613810", 'lon'=>"-0.3319040", 'time'=>"2016-03-13 22:16:48 UTC"),
            array('lat'=>"51.4616550", 'lon'=>"-0.3319390", 'time'=>"2016-03-13 22:16:53 UTC"),
            array('lat'=>"51.4619810", 'lon'=>"-0.3319150", 'time'=>"2016-03-13 22:17:00 UTC"),
            array('lat'=>"51.4622640", 'lon'=>"-0.3319200", 'time'=>"2016-03-13 22:17:06 UTC"),
            array('lat'=>"51.4625010", 'lon'=>"-0.3318850", 'time'=>"2016-03-13 22:17:11 UTC"),
            array('lat'=>"51.4627600", 'lon'=>"-0.3318460", 'time'=>"2016-03-13 22:17:16 UTC"),
            array('lat'=>"51.4630280", 'lon'=>"-0.3318080", 'time'=>"2016-03-13 22:17:22 UTC"),
            array('lat'=>"51.4632570", 'lon'=>"-0.3317420", 'time'=>"2016-03-13 22:17:27 UTC"),
            array('lat'=>"51.4635430", 'lon'=>"-0.3316470", 'time'=>"2016-03-13 22:17:33 UTC"),
            array('lat'=>"51.4637430", 'lon'=>"-0.3315070", 'time'=>"2016-03-13 22:17:38 UTC"),
            array('lat'=>"51.4640030", 'lon'=>"-0.3313290", 'time'=>"2016-03-13 22:17:44 UTC"),
            array('lat'=>"51.4641480", 'lon'=>"-0.3312480", 'time'=>"2016-03-13 22:17:47 UTC"),
            array('lat'=>"51.4643840", 'lon'=>"-0.3310850", 'time'=>"2016-03-13 22:17:52 UTC"),
            array('lat'=>"51.4646170", 'lon'=>"-0.3309140", 'time'=>"2016-03-13 22:17:58 UTC"),
            array('lat'=>"51.4647980", 'lon'=>"-0.3307600", 'time'=>"2016-03-13 22:18:03 UTC"),
            array('lat'=>"51.4649430", 'lon'=>"-0.3305310", 'time'=>"2016-03-13 22:18:09 UTC"),
            array('lat'=>"51.4648230", 'lon'=>"-0.3302310", 'time'=>"2016-03-13 22:18:18 UTC"),

            array('lat'=>"51.4633260", 'lon'=>"-0.3244050", 'time'=>"2016-03-13 23:18:42 UTC"),
            array('lat'=>"51.4633260", 'lon'=>"-0.3244050", 'time'=>"2016-03-13 23:18:43 UTC"),
        );
        $str="";
        $dist="0";
        $type=2;
        $p=new Points("2016-03-13T23:18:43 UTC", $this->echoCallback);

        foreach ($points as $num=>$point) {

            if ($num>0) {
                $dist+=$p->distance($points[$num-1]['lat'],$points[$num-1]['lon'],$point['lat'],$point['lon'])/1000;
            }

            if ($num== sizeof($points)-1) $type=3;
            $str.=$point['time'].";$type;".$point['lat'].";".$point['lon'].";".round($dist,2).";8.3;0;;;\n";
//            $str.=$point['time'].";$type;".$point['lat'].";".$point['lon'].";$dist;8.3;0;;;\n";
//            $str.=sprintf('%s;3;%s;%s;%s;', $point['time'], $point['lat'], $point['lon'], $dist, 8, 0, 0, 0);
            $type="";
//            $str .= "\n";
        }


        vd($str);
        $params= array(
                'workoutId' => "-9398q0r78r7993982",
                'duration' => 6177,
                'gzip' => 'true',
                'extendedResponse' => 'true');
        $post = $this->api->post("track", $params, gzencode($str));
        vd($post);

        $x = explode("\n", $post);
        if (isset($x[1]) && isset(explode("=", $x[1])[1])) $id=explode("=", $x[1])[1];

        echo "<a target='_blank' href=\"https://www.endomondo.com/users/2859253/workouts/$id\">$id</a>";
        vd($x);
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
        return [];
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