<?php

namespace JoanMcGalliard\EddingtonAndMore;


require_once 'TrackerAbstract.php';
require_once 'JoanMcGalliard/EddingtonAndMore/APIs/RideWithGpsApi.php';

use JoanMcGalliard;

class RideWithGps extends TrackerAbstract
{
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
        if (isset($json->user->auth_token)) {
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
        $this->error = "";
        if (!$this->api->getAuth()) {
            $this->connected = false;
        } else if (!$this->connected) {
            $page = $this->api->get('/users/current.json');
            $json = json_decode($page);
            if (!isset($json->user->id)) {
                $this->connected = false;
                $this->api->setAuth(null);
                if (isset($json->error)) {
                    $this->error .= $json->error;
                } else if (!$json) {
                    $this->error .= $page;
                }
            } else {
                $this->connected=true;
                $this->userId = $json->user->id;
            }
            if (isset($json->user->id)) {
                ;
            } else {
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
                    if (strtotime($ride->departed_at) > $end_date) {
                        continue;
                    }
                }
                if ($start_date) {
                    if (strtotime($ride->departed_at) < $start_date) {
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
                $next['endo_id']=null;

                if (isset($ride->description)) {
                    $pattern = "/https+:\/\/www.endomondo.com\/users\/.*\/workouts\/([0-9]*)/";
                    if (preg_match($pattern, $ride->description, $matches) > 0) {
                        $next['endo_id'] = $matches[1];
                    }
                }

                $date = date("Y-m-d", strtotime($ride->departed_at));
                $rides[$date][] = $next;
            }
            if (sizeof($json->results) < $limit) {
                return $rides;
            }
            $offset+=$limit;
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

    public function uploadGpx($file_path, $external_id, $external_msg, $name, $description)
    {
        $this->error="";
        $params = ["trip[name]" => $name, "trip[description]" => $description];
        $page = $this->api->upload("/trips.json",$file_path,$name, $params);
        if ($this->api->getError()) {
            $this->error.=$this->api->getError();
        }
        $json=json_decode($page);
        if (!$json) {
            $this->error.=$page;
        } else if (isset($json->error)) {
            $this->error .= $json->error;
        } else if (isset($json->success) && ($json->success < 1)) {
            $this->error.="Unexpected status on uploaded file ".$file_path;
        } else {
            $queued = new \stdClass();
            $queued->message = $external_msg;
            $queued->external_id = $external_id;
            $queued->file = $file_path;
            $this->pending_uploads[$json->task_id] = $queued;
            return true;
        }
        return false;
    }

    public function waitForPendingUploads()
    {
        $timestamp = time();
        $results = [];
        while ((time() - $timestamp < $this->fileUploadTimeout) && $this->pending_uploads) {
            $ids = join(',', array_keys($this->pending_uploads));
            $page = $this->api->get('/queued_tasks/status.json', ["ids" => $ids, "include_objects" => "true"]);
            $json = json_decode($page);
            if (isset($json->queued_tasks) && is_array($json->queued_tasks)) {
                //if are getting anything other than this in response, just loop until it times out
                foreach ($json->queued_tasks as $queued_task) {
                    $id = $queued_task->id;
                    if ($id && isset($this->pending_uploads[$id])) {
                        $task = $this->pending_uploads[$id];
                        $status = $queued_task->status;
                        // 0 is being processed, 1 is ok, -1 is error.
                        if ($status == 1) {
                            $newId = isset($queued_task->associated_objects[0]->trip->id) ? $queued_task->associated_objects[0]->trip->id : "unknown";
                            $task->rwgps_id = $newId;
                            $results[$task->external_id] = $task;
                            unset($this->pending_uploads[$id]);
                        } else if ($status == -1) {
                            $task->error = $queued_task->message;
                            $results[$task->external_id] = $task;
                            unset($this->pending_uploads[$id]);
                        }
                    }

                }
            }
            $this->output('.');
            sleep(1);
        }
        foreach ($this->pending_uploads as $pending_id => $queued) {
            $queued->error = "Timed out waiting for confirmation of upload after $this->fileUploadTimeout seconds";
            $results[$queued->external_id] = $queued;
            unset($this->pending_uploads[$pending_id]);
        }
        return $results;
    }

    public function activityUrl($id)
    {
        return "http://ridewithgps.com/trips/$id";
    }

    public function uploadUrl()
    {
        return "https://ridewithgps.com/log";
    }


}