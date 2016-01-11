<?php
namespace JoanMcGalliard;

require_once 'TrackerApiInterface.php';
require_once 'Points.php';

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
    public function getPoints($workoutId) {
        //649001790
        $url="api/workout/get?";
        $params=['fields' => 'points', 'workoutId' => $workoutId];
//        $page=$page = $this->getPage($url, $params);
        $page='{"id":649001790,"points":[{"lng":-0.324273,"inst":2,"dist":0,"time":"2015-12-27 21:56:00 UTC","lat":51.463464},{"lng":-0.324273,"alt":53,"dist":0,"time":"2015-12-27 21:56:49 UTC","lat":51.463464,"speed":0},{"lng":-0.324586,"alt":53.7,"dist":0.044719,"time":"2015-12-27 21:57:31 UTC","lat":51.463417,"speed":0},{"lng":-0.32451,"alt":53.2,"dist":0.072548,"time":"2015-12-27 21:57:59 UTC","lat":51.463662,"speed":0},{"lng":-0.324431,"alt":57.7,"dist":0.092359,"time":"2015-12-27 21:58:05 UTC","lat":51.463834,"speed":5.74715},{"lng":-0.324357,"alt":59.4,"dist":0.123434,"time":"2015-12-27 21:58:11 UTC","lat":51.464109,"speed":11.2845},{"lng":-0.32427,"alt":56.2,"dist":0.148557,"time":"2015-12-27 21:58:18 UTC","lat":51.464328,"speed":14.4489},{"lng":-0.324091,"alt":53.2,"dist":0.160978,"time":"2015-12-27 21:58:30 UTC","lat":51.46433,"speed":11.7765},{"lng":-0.323489,"alt":56.2,"dist":0.202838,"time":"2015-12-27 21:58:43 UTC","lat":51.464324,"speed":9.42346},{"lng":-0.323317,"alt":56.3,"dist":0.224244,"time":"2015-12-27 21:58:50 UTC","lat":51.464484,"speed":8.7918},{"lng":-0.323225,"alt":49.8,"dist":0.268042,"time":"2015-12-27 21:58:56 UTC","lat":51.464873,"speed":16.3755},{"lng":-0.323145,"alt":51.8,"dist":0.292829,"time":"2015-12-27 21:59:02 UTC","lat":51.465091,"speed":17.5125},{"lng":-0.323214,"alt":53.9,"dist":0.311703,"time":"2015-12-27 21:59:07 UTC","lat":51.465255,"speed":18.3113},{"lng":-0.323265,"alt":54.2,"dist":0.330981,"time":"2015-12-27 21:59:12 UTC","lat":51.465425,"speed":14.0605},{"lng":-0.323433,"alt":59.8,"dist":0.370746,"time":"2015-12-27 21:59:17 UTC","lat":51.465767,"speed":18.633},{"lng":-0.323769,"alt":60,"dist":0.408074,"time":"2015-12-27 21:59:23 UTC","lat":51.466028,"speed":21.6471},{"lng":-0.324088,"alt":59.7,"dist":0.432991,"time":"2015-12-27 21:59:28 UTC","lat":51.46613,"speed":23.0271},{"lng":-0.324477,"alt":58.9,"dist":0.471812,"time":"2015-12-27 21:59:35 UTC","lat":51.466381,"speed":20.1611},{"lng":-0.324666,"alt":57.7,"dist":0.497454,"time":"2015-12-27 21:59:40 UTC","lat":51.466579,"speed":18.7769},{"lng":-0.32471,"alt":55.5,"dist":0.524263,"time":"2015-12-27 21:59:45 UTC","lat":51.466818,"speed":19.2779},{"lng":-0.32467,"alt":53.5,"dist":0.546032,"time":"2015-12-27 21:59:49 UTC","lat":51.467012,"speed":19.0857},{"lng":-0.324582,"alt":52.8,"dist":0.580445,"time":"2015-12-27 21:59:55 UTC","lat":51.467316,"speed":19.7741},{"lng":-0.324485,"alt":52.1,"dist":0.611459,"time":"2015-12-27 22:00:00 UTC","lat":51.467589,"speed":20.7158},{"lng":-0.324443,"alt":51.7,"dist":0.636925,"time":"2015-12-27 22:00:06 UTC","lat":51.467816,"speed":19.4036},{"lng":-0.324316,"alt":53,"dist":0.664805,"time":"2015-12-27 22:00:12 UTC","lat":51.468054,"speed":18.1362},{"lng":-0.32423,"alt":51,"dist":0.694166,"time":"2015-12-27 22:00:17 UTC","lat":51.468312,"speed":17.7754},{"lng":-0.324401,"alt":52.4,"dist":0.714615,"time":"2015-12-27 22:00:21 UTC","lat":51.468462,"speed":18.6677},{"lng":-0.324813,"alt":49.6,"dist":0.743533,"time":"2015-12-27 22:00:26 UTC","lat":51.468501,"speed":19.9478},{"lng":-0.326028,"alt":57.7,"dist":0.828211,"time":"2015-12-27 22:00:45 UTC","lat":51.468556,"speed":19.9478},{"lng":-0.32621,"alt":56.6,"dist":0.84397,"time":"2015-12-27 22:00:51 UTC","lat":51.468472,"speed":19.9478},{"lng":-0.326188,"alt":57.2,"dist":0.849364,"time":"2015-12-27 22:00:53 UTC","lat":51.468425,"speed":0},{"lng":-0.326157,"alt":56.5,"dist":0.851476,"time":"2015-12-27 22:00:59 UTC","lat":51.468425,"speed":0},{"lng":-0.326157,"alt":56.5,"dist":0.851476,"time":"2015-12-27 22:11:04 UTC","lat":51.468425,"speed":0},{"lng":-0.32609,"alt":45.6,"dist":0.879646,"time":"2015-12-27 22:12:07 UTC","lat":51.468544,"speed":0},{"lng":-0.325856,"alt":41.7,"dist":0.897316,"time":"2015-12-27 22:12:21 UTC","lat":51.468607,"speed":0},{"lng":-0.325401,"alt":39.9,"dist":0.928958,"time":"2015-12-27 22:12:30 UTC","lat":51.468599,"speed":5.94511},{"lng":-0.324929,"alt":39.7,"dist":0.964396,"time":"2015-12-27 22:12:37 UTC","lat":51.46872,"speed":11.9787},{"lng":-0.324666,"alt":45.8,"dist":0.993311,"time":"2015-12-27 22:12:42 UTC","lat":51.468519,"speed":17.3579},{"lng":-0.324297,"alt":41.2,"dist":1.02732,"time":"2015-12-27 22:12:49 UTC","lat":51.468318,"speed":18.9885},{"lng":-0.324342,"alt":43.6,"dist":1.05406,"time":"2015-12-27 22:12:56 UTC","lat":51.468079,"speed":17.3005},{"lng":-0.324364,"alt":46,"dist":1.07967,"time":"2015-12-27 22:13:01 UTC","lat":51.467849,"speed":16.4767},{"lng":-0.32447,"alt":51.1,"dist":1.11441,"time":"2015-12-27 22:13:07 UTC","lat":51.467544,"speed":17.6244},{"lng":-0.324542,"alt":53.8,"dist":1.153,"time":"2015-12-27 22:13:14 UTC","lat":51.4672,"speed":19.6316},{"lng":-0.324635,"alt":52.7,"dist":1.19188,"time":"2015-12-27 22:13:21 UTC","lat":51.466855,"speed":20.2811},{"lng":-0.324464,"alt":53,"dist":1.23052,"time":"2015-12-27 22:13:27 UTC","lat":51.466525,"speed":21.0755},{"lng":-0.32425,"alt":56.7,"dist":1.26067,"time":"2015-12-27 22:13:32 UTC","lat":51.466289,"speed":21.6461},{"lng":-0.323826,"alt":55.1,"dist":1.29738,"time":"2015-12-27 22:13:38 UTC","lat":51.466092,"speed":22.3153},{"lng":-0.323539,"alt":56.1,"dist":1.32233,"time":"2015-12-27 22:13:42 UTC","lat":51.465958,"speed":21.9347},{"lng":-0.323222,"alt":55.5,"dist":1.36933,"time":"2015-12-27 22:13:50 UTC","lat":51.465584,"speed":21.8497},{"lng":-0.323116,"alt":57.2,"dist":1.39892,"time":"2015-12-27 22:13:56 UTC","lat":51.465327,"speed":20.4037},{"lng":-0.323119,"alt":56.4,"dist":1.43492,"time":"2015-12-27 22:14:04 UTC","lat":51.465003,"speed":18.4343},{"lng":-0.323161,"alt":54.6,"dist":1.47394,"time":"2015-12-27 22:14:10 UTC","lat":51.464654,"speed":19.1224},{"lng":-0.323395,"alt":53.1,"dist":1.50126,"time":"2015-12-27 22:14:17 UTC","lat":51.464456,"speed":17.8771},{"lng":-0.323789,"alt":51.1,"dist":1.52864,"time":"2015-12-27 22:14:23 UTC","lat":51.464464,"speed":18.0056},{"lng":-0.324097,"alt":51.1,"dist":1.5503,"time":"2015-12-27 22:14:28 UTC","lat":51.464433,"speed":15.3292},{"lng":-0.324312,"alt":46.6,"dist":1.56721,"time":"2015-12-27 22:14:35 UTC","lat":51.464362,"speed":13.5385},{"lng":-0.324342,"alt":47.9,"dist":1.60259,"time":"2015-12-27 22:14:46 UTC","lat":51.464044,"speed":11.8812},{"lng":-0.324398,"alt":47.7,"dist":1.62548,"time":"2015-12-27 22:14:53 UTC","lat":51.463842,"speed":10.6948},{"lng":-0.324438,"alt":48.1,"dist":1.6403,"time":"2015-12-27 22:14:58 UTC","lat":51.463711,"speed":11.3529},{"lng":-0.324468,"alt":49.9,"dist":1.64929,"time":"2015-12-27 22:15:01 UTC","lat":51.463632,"speed":11.1489},{"lng":-0.324246,"alt":42.1,"dist":1.67942,"time":"2015-12-27 22:16:14 UTC","lat":51.463865,"speed":0},{"lng":-0.324356,"alt":48.7,"dist":1.74563,"time":"2015-12-27 22:19:13 UTC","lat":51.463274,"speed":0},{"lng":-0.324301,"alt":50.9,"dist":1.77613,"time":"2015-12-27 22:20:20 UTC","lat":51.463546,"speed":0},{"lng":-0.324405,"alt":0,"dist":1.79588,"time":"2015-12-27 22:20:40 UTC","lat":51.463381,"speed":0},{"lng":-0.324405,"alt":0,"dist":1.79588,"time":"2015-12-27 22:21:25 UTC","lat":51.463381,"speed":0},{"lng":-0.324405,"alt":0,"dist":1.79588,"time":"2015-12-27 22:22:10 UTC","lat":51.463381,"speed":1.15224},{"lng":-0.324336,"alt":38.5,"dist":1.83798,"time":"2015-12-27 22:28:53 UTC","lat":51.463565,"speed":0},{"lng":-0.324336,"alt":38.5,"dist":1.83798,"time":"2015-12-27 22:29:00 UTC","lat":51.463565,"speed":0},{"lng":-0.324336,"alt":38.5,"dist":1.83798,"time":"2015-12-27 22:29:01 UTC","lat":51.463565,"speed":0.062783},{"lng":-0.324345,"alt":57.3,"dist":1.87657,"time":"2015-12-27 22:31:29 UTC","lat":51.463392,"speed":0},{"lng":-0.324345,"alt":57.3,"dist":1.87657,"time":"2015-12-27 22:31:35 UTC","lat":51.463392,"speed":0},{"lng":-0.324345,"alt":57.3,"dist":1.87657,"time":"2015-12-27 22:31:36 UTC","lat":51.463392,"speed":0.156473},{"lng":-0.324422,"alt":46.7,"dist":1.93001,"time":"2015-12-27 22:35:48 UTC","lat":51.463627,"speed":0},{"lng":-0.324348,"alt":47.3,"dist":1.94034,"time":"2015-12-27 22:35:59 UTC","lat":51.463708,"speed":0},{"lng":-0.324422,"alt":46.7,"dist":1.95068,"time":"2015-12-27 22:36:07 UTC","lat":51.463627,"speed":2.80054},{"lng":-0.324506,"alt":52.6,"dist":1.98447,"time":"2015-12-27 22:36:22 UTC","lat":51.463484,"speed":4.02509},{"lng":-0.324506,"alt":52.6,"dist":1.98447,"time":"2015-12-27 22:36:30 UTC","lat":51.463484,"speed":0},{"lng":-0.324506,"alt":52.6,"dist":1.98447,"time":"2015-12-27 22:36:31 UTC","lat":51.463484,"speed":0},{"lng":-0.324506,"inst":0,"dist":1.98447,"time":"2015-12-28 00:10:10 UTC","lat":51.463484},{"lng":-0.324506,"inst":3,"dist":1.98447,"time":"2015-12-28 00:10:11 UTC","lat":51.463484}]}';
        $points=new Points();
        foreach (json_decode($page)->points as $point) {
            $points->add($point->lat,$point->lng,$point->time);
        }
        return $points;





    }
}

?>