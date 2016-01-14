<?php

namespace JoanMcGalliard;
require_once "Iamstuartwilson/StravaApi.php";
require_once 'TrackerApiInterface.php';
use Iamstuartwilson;

class StravaApi extends Iamstuartwilson\StravaApi implements trackerApiInterface
{
    const GPX_SUFFIX = "\.gpx";
    protected $connected = false;
    protected $bikes = [];
    private $pending_uploads = [];
    private $fileUploadTimeout = 300;

    public function setAccessTokenFromCode($code)
    {
        $token = $this->tokenExchange($code)->access_token;
        $this->setAccessToken($token);
        $this->connected = true;
        return $token;
    }

    public function setAccessToken($token)
    {
        $this->connected = true;
        parent::setAccessToken($token);
    }

    public function uploadUrl()
    {
        return "https://www.strava.com/upload/select";
    }

    public function isConnected()
    {
        if (!$this->connected) return false;
        $this->connected = isset($this->get('athlete')->username);
        return $this->connected;
    }

    public function getRides($start_date, $end_date)
    {
        $activities_per_page = 200;
        $activities_list = [];
        if (!$start_date && !$end_date) {
            for ($i = 1; ; $i++) {
                $activities = $this->getWithDot('activities', ["per_page" => $activities_per_page, "page" => $i]);
                $this->newActivities($activities_list, $activities);
                if (sizeof($activities) < $activities_per_page) {
                    break;
                }
            }
        } else if (!$end_date) {
            $after = $start_date;
            for ($i = 1; ; $i++) {
                $activities = $this->getWithDot('activities', ["per_page" => $activities_per_page, "after" => $after]);
                $this->newActivities($activities_list, $activities);
                if (sizeof($activities) < $activities_per_page) {
                    break;
                }
                $after = strtotime($activities[sizeof($activities) - 1]->start_date) + 1;
            }
        } else if (!$start_date) {
            $before = $end_date;
            for ($i = 1; ; $i++) {
                $activities = $this->getWithDot('activities', ["per_page" => $activities_per_page, "before" => $before]);
                $this->newActivities($activities_list, $activities);
                if (sizeof($activities) < $activities_per_page) {
                    break;
                }
                $before = strtotime($activities[sizeof($activities) - 1]->start_date) - 1;
            }
            $start_date = strtotime($last);
        } else {
            // before and after date set.
            $after = $start_date;
            for ($i = 1; ; $i++) {
                $activities = $this->getWithDot('activities', ["per_page" => $activities_per_page, "after" => $after]);
                $after = strtotime($activities[sizeof($activities) - 1]->start_date) + 1;
                if ($after > $end_date) {
                    for ($i = sizeof($activities) - 1; i >= 0; $i--) {
                        if (strtotime($activities[$i]->start_date) > $end_date) {
                            unset($activities[$i]);
                        } else {
                            break;
                        }
                    }
                }
                $this->newActivities($activities_list, $activities);
                if (sizeof($activities) < $activities_per_page || $after > $end_date)
                    break;
            }
        }
        return $activities_list;
    }

    private function getWithDot($request, $parameters = array())
    {
        $return = $this->get($request, $parameters);
        self::dot();
        return $return;
    }

    private function dot()
    {
        echo ".";
        flush();
    }

    private function newActivities(&$activities_list, $to_add)
    {
        foreach ($to_add as $activity) {
            if ($activity->type != 'Ride') continue;
            $next = [];
            $next['distance'] = floatval($activity->distance);
            $next['name'] = $activity->name;
            $next['strava_id'] = $activity->id;
            $next['name'] = $activity->name;
            $next['start_time'] = $activity->start_date;
            $next['bike'] = $activity->gear_id;
            $next['moving_time'] = $activity->moving_time;
            $next['elapsed_time'] = $activity->elapsed_time;
            $next['total_elevation_gain'] = $activity->total_elevation_gain;
            $next['max_speed'] = $activity->max_speed;

            $pattern = "/^([0-9][0-9]*)" . self::GPX_SUFFIX . "/";
            if (preg_match($pattern, $activity->external_id, $matches) > 0) {
                $next['endo_id'] = intval($matches[1]);
            } else {
                $next['endo_id'] = null;
            }
            $date = date("Y-m-d", strtotime($activity->start_date_local));
            $activities_list[$date][] = $next;
        }
    }

    public function getBike($id)
    {
        if (!array_key_exists($id, $this->bikes)) {
            $gear = $this->getWithDot("gear/$id");
            $this->bikes[$id]["brand"] = $gear->brand_name;
            $this->bikes[$id]["model"] = $gear->model_name;
        }
        return $this->bikes[$id];

    }

    public function uploadGpx($file_path, $external_id, $external_msg, $name, $description)
    {
        $params = ["activity_type" => "ride", "file" => "@" . $file_path,
            "data_type" => "gpx", "external_id" => $external_id,
            "name"=>$name, "description" => $description];
        $result = $this->post("uploads", $params);
        if ($result->error) {
            return $result->error;
        }
        $queued = new \stdClass();
        $queued->message = $external_msg;
        $queued->external_id = $external_id;
        $queued->file = $file_path;
        $this->pending_uploads[$result->id] = $queued;

    }

    public function activityUrl($activityId) {
        return "http://www.strava.com/activities/$activityId";
    }


    public function waitForPendingUploads()
    {
        $timestamp = time();
        $results = [];

        while ((time() - $timestamp < $this->fileUploadTimeout) && $this->pending_uploads) {
            foreach ($this->pending_uploads as $pending_id => $queued) {
                $response = $this->getWithDot("uploads/" . $pending_id);
                if ($response->activity_id) {
                    $queued->status = $response->status;
                    $queued->strava_id = $response->activity_id;
                    $results[$queued->external_id] = $queued;
                    unset($this->pending_uploads[$pending_id]);
                } else if ($response->error) {
                    $queued->error = $response->error;
                    $queued->status = $response->status;
                    $results[$queued->external_id] = $queued;
                    unset($this->pending_uploads[$pending_id]);
                }
            }
            dot();
            sleep(1);
        }
        foreach ($this->pending_uploads as $pending_id => $queued) {
            $queued->error = "Timed out after $this->fileUploadTimeout seconds";
            $queued->status = "Unknown status";
            $results[$queued->external_id] = $queued;
            unset($this->pending_uploads[$pending_id]);
        }
        return $results;
    }
//curl -X POST https://www.strava.com/api/v3/uploads    \
//-H "Authorization: Bearer 93c020201fb0ec14d25396e494827109b9dc257d"     \
//-F activity_type=ride     -F file=@test.gpx     -F data_type=gpx
//
//curl -G https://www.strava.com/api/v3/uploads/519081907
//-H "Authorization: Bearer 93c020201fb0ec14d25396e494827109b9dc257d"


}

?>
