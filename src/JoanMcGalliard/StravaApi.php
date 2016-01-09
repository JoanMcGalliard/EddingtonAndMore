<?php

namespace JoanMcGalliard;
require_once "Iamstuartwilson/StravaApi.php";
require_once 'TrackerApiInterface.php';
use Iamstuartwilson;

class StravaApi extends Iamstuartwilson\StravaApi implements trackerApiInterface
{
    protected $connected = false;
    protected $bikes = [];

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
                $activities = $this->get('activities', ["per_page" => $activities_per_page, "page" => $i]);
                $this->newActivities($activities_list, $activities);
                if (sizeof($activities) < $activities_per_page) {
                    break;
                }
            }
        } else if (!$end_date) {
            $after = $start_date;
            for ($i = 1; ; $i++) {
                $activities = $this->get('activities', ["per_page" => $activities_per_page, "after" => $after]);
                $this->newActivities($activities_list, $activities);
                if (sizeof($activities) < $activities_per_page) {
                    break;
                }
                $after = strtotime($activities[sizeof($activities) - 1]->start_date) + 1;
            }
        } else if (!$start_date) {
            $before = $end_date;
            for ($i = 1; ; $i++) {
                $activities = $this->get('activities', ["per_page" => $activities_per_page, "before" => $before]);
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
                $activities = $this->get('activities', ["per_page" => $activities_per_page, "after" => $after]);
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

    private function newActivities(&$activities_list, $to_add)
    {
        foreach ($to_add as $activity) {
            if ($activity->type != 'Ride') continue;
            $next = [];
            $next['distance'] = floatval($activity->distance);
            $next['name'] = $activity->name;
            $next['strava_id'] = $activity->id;
            $next['bike'] = $activity->gear_id;
            $next['moving_time'] = $activity->moving_time;
            $next['total_elevation_gain'] = $activity->total_elevation_gain;
            $date = date("Y-m-d", strtotime($activity->start_date_local));
            $next['max_speed'] = $activity->max_speed;
            $activities_list[$date][] = $next;
        }
    }

    public function getBike($id)
    {
        if (!array_key_exists($id, $this->bikes)) {
            $gear = $this->get("gear/$id");
            $this->bikes[$id]["brand"] = $gear->brand_name;
            $this->bikes[$id]["model"] = $gear->model_name;
        }
        return $this->bikes[$id];

    }


}

?>
