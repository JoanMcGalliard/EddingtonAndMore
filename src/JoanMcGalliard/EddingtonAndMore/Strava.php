<?php

namespace JoanMcGalliard\EddingtonAndMore;
require_once "Iamstuartwilson/StravaApi.php";
require_once 'TrackerInterface.php';
require_once 'Points.php';
use DOMDocument;
use Iamstuartwilson;


class Strava implements trackerInterface
{
    const GPX_SUFFIX = "\.gpx";
    protected $connected = true;
    protected $bikes = [];
    private $pending_uploads = [];
    private $fileUploadTimeout = 300;
    private $error = null;
    private $writeScope = false;
    private $userId;
    private $stravaApi;
    private $overnightActivities = [];
    private $splitOvernight;

    public function __construct($clientId, $clientSecret, $stravaApi = null)
    {
        if ($stravaApi) {
            $this->stravaApi = $stravaApi;
        } else {
            $this->stravaApi = new Iamstuartwilson\StravaApi($clientId, $clientSecret);
        }
    }

    /**
     * @return string
     */
    public function writeScope()
    {
        return $this->writeScope;
    }

    /**
     * @param string $scope
     */
    public function setWriteScope($scope)
    {
        $this->writeScope = $scope;
    }

    public function setAccessTokenFromCode($code)
    {
        $tokenExchange = $this->stravaApi->tokenExchange($code);
        if (isset($tokenExchange->access_token)) {
            $token = $tokenExchange->access_token;
            $this->setAccessToken($token);
            $this->connected = true;
            return $token;
        }
        return null;
    }

    public function setAccessToken($token)
    {
        $this->connected = true;
        $this->stravaApi->setAccessToken($token);
    }

    public function uploadUrl()
    {
        return "https://www.strava.com/upload/select";
    }

    public function isConnected()
    {
        if (!$this->connected) return false;
        $this->error = null;
        $athlete = $this->stravaApi->get('athlete');
        if (isset($athlete->id)) {
            $this->connected = true ;
            $this->userId = $athlete->id;
        } else {
            $this->connected=false;
        }
        if (isset($athlete->errors)) {
            $this->error = $athlete->message;
        }
        return $this->connected;
    }

    /**
     * @return null
     */
    public function getError()
    {
        return $this->error;
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
            $start_date = strtotime($before);
        } else {
            // before and after date set.
            $after = $start_date;
            for ($i = 1; ; $i++) {
                $activities = $this->getWithDot('activities', ["per_page" => $activities_per_page, "after" => $after]);
                $after = strtotime($activities[sizeof($activities) - 1]->start_date) + 1;
                if ($after > $end_date) {
                    for ($i = sizeof($activities) - 1; $i >= 0; $i--) {
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
        $return = $this->stravaApi->get($request, $parameters);
        $this->dot();
        return $return;
    }

    private function rareDot()
    {
        if (!isset($this->rareDotCount)){ $this->rareDotCount=0;}
        if ($this->rareDotCount++ > 1000) {$this->dot(); $this->rareDotCount=0;}
        flush();
    }
    public function dot($dot='.')
    {
        echo $dot;
        echo str_pad('',4096);  // so firefox will display something
        flush();  // maybe unnecessary, test later
    }

    private function newActivities(&$activities_list, $to_add)
    {
        global $scratchDirectory;
        foreach ($to_add as $activity) {
            if ($activity->type != 'Ride') continue;
            $next = [];
            $next['distance'] = floatval($activity->distance);
            $next['name'] = $activity->name;
            $next['strava_id'] = $activity->id;
            $next['start_time'] = $activity->start_date;
            $next['bike'] = $activity->gear_id;
            $next['moving_time'] = $activity->moving_time;
            $next['elapsed_time'] = $activity->elapsed_time;
            $next['total_elevation_gain'] = $activity->total_elevation_gain;
            $next['max_speed'] = $activity->max_speed;
            if (preg_match('/\([^\)]*\) (.*)$/', $activity->timezone, $matches) > 0) {
                $next['timezone'] = $matches[1];
            } else {
                $next['timezone'] = null;
            }
            $gpx_file = $scratchDirectory . DIRECTORY_SEPARATOR . $this->userId . "-" .
                preg_replace("/:/", "_", $activity->start_date) . ".gpx";
            $numberOfDays = $this->numberOfDays($activity->start_date, $next['timezone'], $activity->elapsed_time);
            if ($this->splitOvernight && $numberOfDays > 1 && file_exists($gpx_file)) {
                $xml = file_get_contents($gpx_file);
                preg_match_all('/<trkpt[^>]*>.*?<\/trkpt>/s', $xml, $trkpts);
                $points = new Points($activity->start_date, $next['timezone']);
                foreach ($trkpts[0] as $trkpt) {
                    preg_match('/<trkpt.*lat="([^"]*)"/',$trkpt, $matches);
                    $lat=$matches[1];
                    preg_match('/<trkpt.*lon="([^"]*)"/',$trkpt, $matches);
                    $lon=$matches[1];
                    preg_match('/<time>([^<]*)<\/time>/',$trkpt, $matches);
                    $time=$matches[1];
                    $points->add($lat, $lon, $time);
                    $this->rareDot();
                }

                if (sizeof($points->getSplits())> 0) {
                    $next['total_elevation_gain'] = $next['total_elevation_gain'] / sizeof($points->getSplits());
                }

                foreach ($points->getSplits() as $split_date => $split) {
                    $new = $next;
                    $new['distance'] = $split;
                    $new['start_time'] = $points->getStartTimes()[$split_date];
                    $new['elapsed_time'] = strtotime($points->getEndTimes()[$split_date]) - strtotime($points->getStartTimes()[$split_date]);
                    $new['moving_time'] = $new['elapsed_time'];
                    $activities_list[$split_date][] = $new;
                }

                $points = null;

            } else {
                if ($this->splitOvernight && $numberOfDays > 1) {
                    // it's a multi day ride, but we don't have a file for it.
                    $this->overnightActivities[$activity->id] = $activity;

                }
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
    }

    private function numberOfDays($start_time, $tz, $duration)
    {
        $def_tz = date_default_timezone_get();
        date_default_timezone_set($tz);
        $start = strtotime($start_time);
        $midnight = strtotime(date("Y-m-d", $start));
        date_default_timezone_set($def_tz);
        $start_seconds = $start - $midnight;
        return intval(($start_seconds + $duration) / self::TWENTY_FOUR_HOURS) + 1;
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
            "name" => $name, "description" => $description];
        $result = $this->stravaApi->post("uploads", $params);
        if ($result->error) {
            return $result->error;
        }
        $queued = new \stdClass();
        $queued->message = $external_msg;
        $queued->external_id = $external_id;
        $queued->file = $file_path;
        $this->pending_uploads[$result->id] = $queued;

    }

    public function activityUrl($activityId)
    {
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
            $this->dot();
            sleep(1);
        }
        foreach ($this->pending_uploads as $pending_id => $queued) {
            $queued->error = "Timed out waiting for confirmation of upload after $this->fileUploadTimeout seconds";
            $queued->status = "Unknown status";
            $results[$queued->external_id] = $queued;
            unset($this->pending_uploads[$pending_id]);
        }
        return $results;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setSplitOvernightRides($splitRides)
    {
        $this->splitOvernight = $splitRides;
    }

    public function authenticationUrl($redirect, $approvalPrompt, $scope, $state)
    {
        return $this->stravaApi->authenticationUrl($redirect, $approvalPrompt, $scope, $state);
    }

    public function getOvernightActivities()
    {
        return $this->overnightActivities;
    }

}

?>
