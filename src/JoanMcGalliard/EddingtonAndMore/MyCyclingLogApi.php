<?php
namespace JoanMcGalliard\EddingtonAndMore;

require_once 'TrackerApiInterface.php';
use DOMDocument;
use Iamstuartwilson;

class MyCyclingLogApi implements trackerApiInterface
{
    const BASE_URL = "http://www.mycyclinglog.com/api/restserver.php";
    const STRAVA_NOTE_PREFIX = "http://www.strava.com/activities/";


    protected $auth = null;
    protected $connected = false;
    protected $bikes = null;
    protected $strava_bike_match = [];
    protected $use_feet_for_elevation = false;
    private $user_id;

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @return boolean
     */
    public function isUseFeetForElevation()
    {
        return $this->use_feet_for_elevation;
    }

    /**
     * @param boolean $use_feet_for_elevation
     */
    public function setUseFeetForElevation($use_feet_for_elevation)
    {
        $this->use_feet_for_elevation = $use_feet_for_elevation;
    }


    public function setAuth($auth)
    {
        $this->auth = $auth;
    }

    public function addRide($date, $ride)
    {
        $parameters = [];
        $parameters['event_date'] = date("m/d/Y", strtotime($date));
        $parameters['is_ride'] = 'T';
        $time = $ride['moving_time'];
        $secs = $time % 60;
        $time = ($time - $secs) / 60;
        $mins = $time % 60;
        $hours = ($time - $mins) / 60;
        $parameters['h'] = $hours;
        $parameters['m'] = $mins;
        $parameters['s'] = $secs;
        $parameters['distance'] = $ride['distance'] * self::METRE_TO_MILE;
        $parameters['user_unit'] = 'mi';
        $parameters['notes'] = self::STRAVA_NOTE_PREFIX . $ride['strava_id'];
//        $parameters['heart_rate'] = $ride[''];
        $parameters['max_speed'] = $ride['max_speed'] * 60 * 60 * self::METRE_TO_MILE;
        $parameters['elevation'] = $ride['total_elevation_gain'] * ($this->use_feet_for_elevation ? self::METRE_TO_FOOT : 1);
//        $parameters['tags'] = $ride[''];
        $parameters['bid'] = $ride['mcl_bid']; // bid. Optional. Bike ID as returned by New Bike API.
        return $this->postPage("?method=ride.new", $parameters);
    }

    protected function postPage($url, $parameters)
    {
        $process = curl_init(self::BASE_URL . $url);
        $headers = array('Authorization: Basic ' . $this->auth);
        curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($process, CURLOPT_HEADER, 0);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
        curl_setopt($process, CURLOPT_POST, 1);
        curl_setopt($process, CURLOPT_POSTFIELDS, http_build_query($parameters));
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($process);
        curl_close($process);
        log_msg("MCL post URL " . $url);
        log_msg($parameters);
        log_msg($response);

        return $response;
    }

    public function isConnected()
    {
        if ($this->auth != null) {
            $rides = $this->getPageDom("?method=ride.list&limit=0&offset=0");
            if ($rides != null && preg_match('/^[0-9][0-9]*$/',
                    $rides->childNodes->item(0)->childNodes->item(0)->getAttribute('total_size'))
            ) {
                return true;
            }
        }
        return false;
    }

    protected function getPageDom($url)
    {
        $retries = 3;
        for ($i = 0; $i < $retries; $i++) {
            $xml = $this->getPage($url);
            if ($xml) break;
        }
        if (!$xml) {
            echo "There is a problem with MyCyclingLog.  Please try again";
            exit();
        }
        if ($xml == "You are not authorized.") {
            $this->auth = null;
            return null;
        }
        $xml = $this->removeCharactersInElement("notes", $xml);  //todo find out if the same problem can occur in route or bike id.
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $doc->formatOutput = true;
        return $doc;
    }

    protected function getPage($url)
    {
        $process = curl_init(self::BASE_URL . $url);
        $headers = array('Authorization: Basic ' . $this->auth);
        curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($process, CURLOPT_HEADER, 0);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
        $page = curl_exec($process);
        curl_close($process);
        log_msg("MCL get URL " . $url);
        log_msg($page);
        return $page;
    }

    protected function removeCharactersInElement($element, $xml)
    {
        $old = "";
        $new = $xml;
        while ($old <> $new) {
            $old = $new;
            $new = preg_replace("/(<$element>[^<>]*)[^-a-zA-Z0-9 +<>.!,()_?\/=\"':;]([^\/])/", "$1$2", $new);
        }
        return $new;
    }

    public function bikeMatch($brand, $model, $stravaId)
    {
        if (!array_key_exists($stravaId, $this->strava_bike_match)) {
            if ($this->bikes == null) {
                $this->bikes = $this->getBikes();
            }
            $this->strava_bike_match[$stravaId] = '';
            foreach ($this->bikes as $id => $bike) {
                if ($bike['brand'] == $brand &&
                    ($bike['model'] == $model || $bike['model'] == '' || $model = '')
                ) {
                    $this->strava_bike_match[$stravaId] = $id;
                    break;
                }
            }
        }
        return $this->strava_bike_match[$stravaId];
    }

    protected function getBikes()
    {
        $records = [];
        $bikeCount = $this->getBikeCount();
        $offset = 0;
        $limit = 20;

        while ($offset < $bikeCount) {
            $doc = $this->getPageDom("?method=bike.list&limit=$limit&offset=$offset");
            $bikes = $doc->childNodes->item(0)->childNodes->item(0)->childNodes;
            foreach ($bikes as $bike) {
                $record = [];
                $id = $bike->getAttribute("id");
                $record['brand'] = $bike->getElementsByTagName("make")->item(0)->nodeValue;
                $record['model'] = $bike->getElementsByTagName("model")->item(0)->nodeValue;
                $record['year'] = $bike->getElementsByTagName("year")->item(0)->nodeValue;
                $records[$id] = $record;
            }
            $offset = $offset + $limit;
        }
        return $records;
    }

    protected function getBikeCount()
    {
        $doc = $this->getPageDom("?method=bike.list&limit=0&offset=0");
        return intval($doc->childNodes->item(0)->childNodes->item(0)->getAttribute("total_size"));
    }

    public function deleteRides($start_date, $end_date, $username, $password)
    {

        $loginUrl = 'http://www.mycyclinglog.com';

        $count = 0;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $loginUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'username=' . $username . '&password=' . $password);
        curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $store = curl_exec($ch);
        if (strpos($store, "Logout") === false) {
            $error = curl_error($ch);
            curl_close($ch);
            if ($error) {
                return $error;
            }
            return "$error  Check username ($username) and password.";
        }

        curl_setopt($ch, CURLOPT_URL, "http://www.mycyclinglog.com//add.php");


        $rides = $this->getRides($start_date, $end_date);
        foreach ($rides as $date => $ride_list) {
            foreach ($ride_list as $ride) {
                if ($ride['strava_id'] <> null) {
                    echo "Deleting " . $ride['mcl_id'] . " from " . $date . ", strava id " . $ride["strava_id"] . ".<br>";
                    flush();
                    curl_setopt($ch, CURLOPT_POSTFIELDS, "r=1&lid=" . $ride['mcl_id']);
                    curl_exec($ch);
                    $count++;
                }
            }
        }
        curl_close($ch);
        return $count;
    }

    public function getRides($start_date, $end_date)
    {
        $records = [];
        $eventCount = $this->getEventCount();
        $offset = 0;
        $limit = 500;
        $done = false;

        while ($offset < $eventCount && !$done) {
            $doc = $this->getPageDom("?method=ride.list&limit=$limit&offset=$offset");
            $rides = $doc->childNodes->item(0)->childNodes->item(0)->childNodes;
            foreach ($rides as $ride) {
                $record = [];
                if ($ride->getElementsByTagName("is_ride")->item(0)->nodeValue != 'true') {
                    continue;
                }
                $item = $ride->getElementsByTagName("distance")->item(0);
                $distance = floatval($item->nodeValue);
                $units = $item->getAttribute("units");
                if ($units == 'mi') {
                    $record['distance'] = $distance / self::METRE_TO_MILE;
                } else if ($units == 'km') {
                    $record['distance'] = $distance /self::METRE_TO_KM;
                }
                $record['mcl_id'] = $ride->getAttribute('id');


                $pattern = "/" . str_replace("/", "\\/", self::STRAVA_NOTE_PREFIX) . "([1-9][0-9]*)$/";
                if (preg_match($pattern, $ride->getElementsByTagName("notes")->item(0)->nodeValue, $matches) > 0) {
                    $record['strava_id'] = $matches[1];
                } else {
                    $record['strava_id'] = null;
                }
                $record['moving_time'] = $ride->getElementsByTagName("time")->item(0)->nodeValue;
                $record['max_speed'] = floatval($ride->getElementsByTagName("max_speed")->item(0)->nodeValue) / (60 * 60 * self::METRE_TO_MILE); // convert to m/s
                $timestamp = strtotime($ride->getElementsByTagName("event_date")->item(0)->nodeValue);

                if ($start_date && $start_date > $timestamp) {
                    $done = true;
                    break;
                }
                if ($end_date && $end_date <= $timestamp) continue;
                $date = date("Y-m-d", $timestamp);

                $records[$date][] = $record;
            }
            $offset = $offset + $limit;
        }
        return $records;
    }

    protected function getEventCount()
    {
        $doc = $this->getPageDom("?method=ride.list&limit=0&offset=0");
        return intval($doc->childNodes->item(0)->childNodes->item(0)->getAttribute("total_size"));
    }
    // MyCyclingLog can create non-valid xml.  This removes any character except for a select list from the field
    // "<$element>"

    protected function sd($x)
    {
        echo "<pre>";
        if (is_a($x, "DOMDocument")) {
            echo htmlspecialchars($x->saveXML());
        } else {
            echo htmlspecialchars($x->ownerDocument->saveXML($x));
        }
        echo "</pre>";
    }


}

?>
