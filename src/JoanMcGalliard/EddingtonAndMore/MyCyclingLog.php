<?php
namespace JoanMcGalliard\EddingtonAndMore;

require_once 'JoanMcGalliard/EddingtonAndMore/APIs/MyCyclingLogApi.php';
require_once 'TrackerAbstract.php';

use DOMDocument;
use Iamstuartwilson;
use JoanMcGalliard;


class MyCyclingLog extends trackerAbstract
{
    protected $connected = false;
    protected $bikes = null;
    protected $strava_bike_match = [];
    protected $use_feet_for_elevation = false;


    /**
     * MyCyclingLogWrapper constructor.
     * @param null $api
     */
    public function __construct($echoCallback, $api = null)
    {
        $this->echoCallback = $echoCallback;
        if ($api) {
            $this->api = $api;
        } else {
            $this->api = new JoanMcGalliard\MyCyclingLogApi();
        }
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
        $this->api->setAuth($auth);
    }

    public function addRide($date, $ride, $points)
    {
        $parameters = [];
        $parameters['event_date'] = date("m/d/Y", strtotime($date));
        $parameters['is_ride'] = 'T';
        $time = isset($ride['moving_time']) ? $ride['moving_time'] : $ride['elapsed_time'];
        $secs = $time % 60;
        $time = ($time - $secs) / 60;
        $mins = $time % 60;
        $hours = ($time - $mins) / 60;
        $parameters['h'] = $hours;
        $parameters['m'] = $mins;
        $parameters['s'] = $secs;
        $parameters['distance'] = $ride['distance'] * self::METRE_TO_MILE;
        $parameters['user_unit'] = 'mi';
        if (isset($ride['description'])) {
            $parameters['notes'] = $ride['description'];
        }
        $parameters['max_speed'] = isset($ride['max_speed']) ? $ride['max_speed'] * 60 * 60 * self::METRE_TO_MILE : 0;
        $parameters['elevation'] = isset($ride['total_elevation_gain']) ? $ride['total_elevation_gain'] * ($this->use_feet_for_elevation ? self::METRE_TO_FOOT : 1) : 0;
        $parameters['bid'] = isset($ride['bike']) ? $ride['bike'] : "";

        $response = $this->postPageDom("?method=ride.new", $parameters);
        if ($response && isset($response->getElementsByTagName("response")->item(0)->nodeValue)) {
            return intval($response->getElementsByTagName("response")->item(0)->nodeValue);
        } else {
            return null;
        }
    }


    public function isConnected()
    {
        if ($this->connected) return true;
        if ($this->api->getAuth() != null) {
            $rides = $this->getPageDom("?method=ride.list&limit=0&offset=0");
            if ($rides != null && $rides->hasChildNodes() && $rides->childNodes->item(0)->hasChildNodes() &&
                $rides->childNodes->item(0)->childNodes->item(0)->hasAttributes() &&
                preg_match('/^[0-9][0-9]*$/',
                    $rides->childNodes->item(0)->childNodes->item(0)->getAttribute('total_size'))
            ) {
                $this->connected=true;
            } else {
                $this->api->setAuth(null);
            }
        }
        return $this->connected;
    }

    protected function getPageDom($url)
    {
        for ($i = 0; $i < self::RETRIES; $i++) {
            $xml = $this->api->getPage($url);
            if ($xml) break;
        }
        if (!$xml) {
            $this->output("There is a problem with MyCyclingLog.  Please try again");
            return null;
        }
        if ($xml == "You are not authorized.") {
            $this->setAuth(null);
            return null;
        }
        $xml = $this->removeCharactersInElement("bike", ($this->removeCharactersInElement("route", $this->removeCharactersInElement("notes", $xml))));
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $doc->formatOutput = true;
        return $doc;
    }

    protected function postPageDom($url,$params)
    {
        for ($i = 0; $i < self::RETRIES; $i++) {
            $xml = $this->api->postPage($url,$params);
            if ($xml) break;
        }
        if (!$xml) {
            $this->output("There is a problem with MyCyclingLog.  Please try again");
            return null;
        }
        if ($xml == "You are not authorized.") {
            $this->setAuth(null);
            return null;
        }
        $doc = new DOMDocument();
        try {
            $doc->loadXML($xml);
        } catch (\Exception $e) {
            $this->output("There is a problem with MyCyclingLog.  Please try again: $xml");
            return null;

        }
        $doc->formatOutput = true;
        return $doc;
    }


    protected function removeCharactersInElement($element, $xml)
    {
        // MyCyclingLog can create non-valid xml.  This removes any character except for a select list from the field
        // "<$element>"
        $old = "";
        $new = $xml;
        while ($old <> $new) {
            $old = $new;
            $new = preg_replace("/(<${element}[^>]*>[^<>]*?)[^-a-zA-Z0-9 +<>.!,\(\)_?\/=\"':;]([^<>]*?<\/$element>)/", "$1$2", $new);
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

    protected function getBikes($limit=20)
    {
        $records = [];
        $bikeCount = $this->getBikeCount();
        $offset = 0;

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
        $login = $this->api->login($username, $password);
        if ($login <> "OK") {
            return $login; // error message
        }

        $count = 0;
        $rides = $this->getRides($start_date, $end_date);
        foreach ($rides as $date => $ride_list) {
            foreach ($ride_list as $ride) {
                if ($ride['strava_id'] <> null || $ride['endo_id'] <> null) {
                    if ($ride['strava_id'] <> null) {
                        $this->output("Deleting " . $ride['mcl_id'] . " from " . $date . ", strava id " . $ride["strava_id"]);
                    } else {
                        $this->output("Deleting " . $ride['mcl_id'] . " from " . $date . ", endomondo id " . $ride["endo_id"]);
                    }
                    if($this->api->delete($ride['mcl_id'])) {
                        $count++;
                    } else {
                        $this->output(": FAILED");
                    }
                    $this->output(".<br>");

                }
            }
        }
        $this->api->logout();
        return $count;
    }


    public function getRides($start_date, $end_date, $limit=800)
    {
        $records = [];
        $offset = 0;
        $done = false;

        while (!$done) {
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
                    $record['distance'] = $distance / self::METRE_TO_KM;
                }
                $record['mcl_id'] = $ride->getAttribute('id');


                $pattern = "/" . str_replace("/", "\\/", $this->stravaActivityUrl("([1-9][0-9]*)")) . "/" ;
                if (preg_match($pattern, $ride->getElementsByTagName("notes")->item(0)->nodeValue, $matches) > 0) {
                    $record['strava_id'] = $matches[1];
                } else {
                    $record['strava_id'] = null;
                }
                $pattern = "/" . str_replace("/", "\\/", $this->endomondoActivityUrl("([1-9][0-9]*)", ".*")) . "/" ;
                if (preg_match($pattern, $ride->getElementsByTagName("notes")->item(0)->nodeValue, $matches) > 0) {
                    $record['endo_id'] = $matches[1];
                } else {
                    $record['endo_id'] = null;
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
            if ($rides->length<$limit) {$done=true;}
        }
        return $records;
    }


    public function getOvernightActivities()
    {
        return [];
    }

    public function getBike($id)
    {
        if ($this->bikes == null) {
            $this->bikes = $this->getBikes();
        }
        return isset($this->bikes[$id]) ? $this->bikes[$id] : null;



    }

    public function activityUrl($id)
    {
        return "http://www.mycyclinglog.com/add.php?lid=$id";
    }

    public function waitForPendingUploads($sleep=1)
    {
        return null;
    }

    public function getPoints($id, $tz)
    {
        return null;
    }
}

?>
