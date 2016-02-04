<?php
/**
 * Created by PhpStorm.
 * User: jem
 * Date: 02/02/2016
 * Time: 15:52
 */

namespace JoanMcGalliard\EddingtonAndMore;


require_once 'TrackerAbstract.php';
require_once 'JoanMcGalliard/EddingtonAndMore/APIs/RideWithGpsApi.php';

use JoanMcGalliard;

class RideWithGps extends TrackerAbstract
{


    private $api;
    private $connected;

    public function __construct($apikey, $echoCallback, $api = null)
    {
        $this->echoCallback = $echoCallback;
        if ($api) {
            $this->api = $api;
        } else {
            $this->api = new JoanMcGalliard\RideWithGpsApi($apikey);
        }
    }

    public function connect($username, $password)
    {
        $this->error = "";
        $params = [];
        $params['email'] = $username;
        $params['password'] = $password;
        $page = $this->api->get('/users/current.json', $params);
        $json = json_decode($page);
        if (!$json) {
            $this->error .= $page;
        }
        $auth_token = null;
        if (isset($json->user) && isset($json->user->auth_token)) {
            $auth_token = $json->user->auth_token;
            if (isset($json->user->id)) {
                $this->userId = $json->user->id;
            }
        }
        if (!$auth_token) {
            if (isset ($json->error)) {
                $this->error .= $json->error;
            } else {
                $this->error .= "Auth Token not found.";
            }
        } else {
            $this->api->setAuth($auth_token);
        }
        $this->output(".");
        return $auth_token;
    }


    public function isConnected()
    {
        $this->error="";
        if (!$this->api->getAuth()) {
            $this->connected = false;
        } else if (!$this->connected) {
            $json = json_decode($this->api->get('/users/current.json'));
            if (isset($json->user) && isset($json->user->id)) {
                $this->userId = $json->user->id;
                $this->connected = true;
            } else {
                if (isset($json->error)) {
                    $this->error.= $json->error;
                }
                $this->api->setAuth(null);
            }
        }
        return $this->connected;
    }


    public function getRides($start_date, $end_date, $limit = 100)
    {
        $rides = [];
        $this->error = "";
        $params = [];
        $params['limit'] = $limit;

        $offset = 0;
        while (true) {
            $params['offset'] = $offset;
            $page = $this->api->get("/users/$this->userId/trips.json", $params);
            $json = json_decode($page);
            if (!$json) {
                $this->error .= $page;
                return $rides;
            }
            if (!isset($json->results) || !is_array($json->results)) {
                if (isset ($json->error)) {
                    $this->error .= $json->error;
                } else {
                    $this->error .= $this->api->getError();
                }
                return $rides;
            }
            foreach ($json->results as $ride) {
                if ($end_date) {
                    if (strtotime($ride->departed_at)> $end_date) {
                        continue;
                    }
                }
                if ($start_date) {
                    if (strtotime($ride->departed_at)< $start_date) {
                        return $rides;
                    }
                }
                $next = [];
                $next['rwgps_id'] = $ride->id;
                $next['start_time'] = $ride->departed_at; //todo use timezone
//                $ride->time_zone;
//                $ride->first_lng;
//                $ride->first_lat;
                $next['distance'] = $ride->distance;
                $next['moving_time'] = $this->convertToSeconds($ride->moving_time);
                $next['elapsed_time'] = $this->convertToSeconds($ride->duration);
                $next['max_speed'] = $ride->max_speed;
                $next['name'] = $ride->name;
                $next['bike'] = $ride->gear_id;
                $next['total_elevation_gain'] = $ride->elevation_gain;

                $date = date("Y-m-d", strtotime($ride->departed_at));
                $rides[$date][] = $next;
            }
            if (sizeof($json->results) < $limit) {
                return $rides;
            }
            $offset++;
        }
    }

    private function convertToSeconds($str)
    {
        $units = explode(":", $str);
        $total = 0;
        foreach ($units as $unit) {
            $total *= 60;
            $total += intval($unit);
        }
        return $total;
    }

    public function getAuth()
    {
        // only called in tests
        return $this->api->getAuth();
    }
    public function setAuth($auth)
    {
         $this->api->setAuth($auth);
    }

}