<?php

namespace JoanMcGalliard\EddingtonAndMore;
require_once "APIs/StravaApi.php";
require_once 'TrackerAbstract.php';
require_once 'Points.php';
use CURLFile;
use Iamstuartwilson;


class Strava extends trackerAbstract
{
    const GPX_SUFFIX = "gpx";
    protected $connected = false;
    protected $bikes = [];
    private $writeScope = false;
    private $overnightActivities = [];
    private $accessTokenIsSet = false;

    public function __construct($clientId, $clientSecret, $echoCallback, $api = null)
    {
        $this->echoCallback = $echoCallback;
        if ($api) {
            $this->api = $api;
        } else {
            $this->api = new Iamstuartwilson\StravaApi($clientId, $clientSecret);
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
        $tokenExchange = $this->api->tokenExchange($code);
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
        $this->accessTokenIsSet = true;
        $this->api->setAccessToken($token);
    }

    public function uploadUrl()
    {
        return "https://www.strava.com/upload/select";
    }

    public function isConnected()
    {
        $this->error = "";
        if ($this->connected) return true;
        if (!$this->accessTokenIsSet) return false;
        $athlete = $this->api->get('athlete');
        if (isset($athlete->id)) {
            $this->connected = true;
            $this->userId = $athlete->id;
        } else {
            $this->accessTokenIsSet = false;
            if (isset($athlete->errors)) {
                if (isset($athlete->message)) {
                    $this->error = $athlete->message;
                } else {
                    $this->error = json_encode($athlete->errors);
                }
            }
        }
        return $this->connected;
    }

    /**
     * @param $start_date
     * @param $end_date
     * @param int $activities_per_page
     * @return null
     */

    public function getRides($start_date, $end_date, $activities_per_page = 200)
    {
        $this->error = "";
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
        } else {
            // before and after date set.
            $after = $start_date;
            for ($i = 1; ; $i++) {
                $activities = $this->getWithDot('activities', ["per_page" => $activities_per_page, "after" => $after]);
                if (sizeof($activities) > 0) {
                    $after = strtotime($activities[sizeof($activities) - 1]->start_date) + 1;
                }
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
        $return = $this->api->get($request, $parameters);
        $this->output('.');
        return $return;
    }

    private function newActivities(&$activities_list, $to_add)
    {
        global $scratchDirectory;
        if (is_string($to_add)) {
            // strava has given us an error instead of data :(
            $this->error .= $to_add . "<br>";
        } else {
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
                };;
                $next['kudos_count'] = $activity->kudos_count;
                $next['comment_count'] = $activity->comment_count;
                $date = date("Y-m-d", strtotime($activity->start_date_local));
                $pattern = "/^endomondo_[^_]*_([0-9][0-9]*)\." . self::GPX_SUFFIX . "/";
                if (preg_match($pattern, $activity->external_id, $matches) > 0) {
                    $next['endo_id'] = intval($matches[1]);
                } else {
                    $next['endo_id'] = null;
                }

                if ($this->splitOvernightRides && $this->isOvernight($activity->start_date, $next['timezone'], $activity->elapsed_time)) {

                    $points = $this->getPoints($activity->start_date, $next['timezone']);
                    if ($points) {
                        if (sizeof($points->getSplits()) > 0) {
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

                    } else {
                        // it's a multi day ride, but we don't have a file for it.
                        $this->overnightActivities[$activity->id] = $activity;
                        $activities_list[$date][] = $next;

                    }
                } else {
                    $activities_list[$date][] = $next;
                }
            }
        }
    }


    public function getBike($id)
    {
        if (!array_key_exists($id, $this->bikes)) {
            /** @var object $gear */
            $gear = $this->getWithDot("gear/$id");
            $this->bikes[$id]["brand"] = isset($gear->brand_name) ? $gear->brand_name : "";
            $this->bikes[$id]["model"] = isset($gear->model_name) ? $gear->model_name : "";
            if (isset($gear->errors)) {
                if (isset($gear->message)) {
                    $this->error = $gear->message;
                } else {
                    $this->error = json_encode($gear->errors);
                }
            }
        }
        return $this->bikes[$id];
    }

    public function uploadGpx($file_path, $external_id, $external_msg, $name, $description)
    {
        $params = ["activity_type" => "ride", "file" => new CURLFile($file_path, 'text', $name),
            "data_type" => "gpx", "external_id" => $external_id,
            "name" => $name, "description" => $description];
        /** @var object $result */
        $result = $this->api->post("uploads", $params);
        if ($result->error) {
            return $result->error;
        }
        $queued = new \stdClass();
        $queued->message = $external_msg;
        $queued->external_id = $external_id;
        $queued->file = $file_path;
        $this->pending_uploads[$result->id] = $queued;
        return null;
    }

    public function activityUrl($activityId)
    {
        return "http://www.strava.com/activities/$activityId";
    }

    public function waitForPendingUploads($sleep=1)
    {
        $timestamp = microtime(true);
        $results = [];

        $count=0;
        while ((microtime(true) - $timestamp < $this->fileUploadTimeout) && $this->pending_uploads) {
            foreach ($this->pending_uploads as $pending_id => $queued) {
                /** @var object $response */
                $response = $this->getWithDot("uploads/" . $pending_id);
                if (isset($response->activity_id) && $response->activity_id) {
                    $queued->status = $response->status;
                    $queued->strava_id = $response->activity_id;
                    $results[$queued->external_id] = $queued;
                    unset($this->pending_uploads[$pending_id]);
                } else if (isset($response->error) && $response->error) {
                    $queued->error = $response->error;
                    $queued->status = $response->status;
                    $results[$queued->external_id] = $queued;
                    unset($this->pending_uploads[$pending_id]);
                }
            }
            $this->output('.');
            usleep($sleep *1000000);
        }
        foreach ($this->pending_uploads as $pending_id => $queued) {
            $queued->error = "Timed out waiting for confirmation of upload after $this->fileUploadTimeout seconds";
            $queued->status = "Unknown status";
            $results[$queued->external_id] = $queued;
            unset($this->pending_uploads[$pending_id]);
        }
        return $results;
    }


    public function authenticationUrl($redirect, $approvalPrompt, $scope, $state)
    {
        return $this->api->authenticationUrl($redirect, $approvalPrompt, $scope, $state);
    }

    public function getOvernightActivities()
    {
        return $this->overnightActivities;
    }

    public function getPoints($start_date, $tz)
    {
        global $scratchDirectory;
        $gpx_file = $scratchDirectory . DIRECTORY_SEPARATOR . $this->userId . "-" .
            preg_replace("/:/", "_", $start_date) . "." . self::GPX_SUFFIX;
        if (!file_exists($gpx_file)) {
            return null;
        }

        $xml = file_get_contents($gpx_file);
        preg_match_all('/<trkpt[^>]*>.*?<\/trkpt>/s', $xml, $trkpts);
        $points = new Points($start_date, $this->echoCallback, null, $tz);
        foreach ($trkpts[0] as $trkpt) {
            preg_match('/<trkpt.*lat="([^"]*)"/', $trkpt, $matches);
            $lat = $matches[1];
            preg_match('/<trkpt.*lon="([^"]*)"/', $trkpt, $matches);
            $lon = $matches[1];
            preg_match('/<time>([^<]*)<\/time>/', $trkpt, $matches);
            $time = $matches[1];
            $points->add($lat, $lon, $time);
            $this->rareDot();
        }
        return $points;
    }

    public function getActivityDescription($strava_id)
    {
        $json = $this->api->get("activities/$strava_id");

        if (is_string($json)) {
            $this->error .= $json;
            return null;
        }
        if (!isset($json->id) || $json->id <> $strava_id) {
            $this->error .= "Not the expected activity";
            return null;
        }
        return $json->description;

    }

    public function deleteActivity($id)
    {
        $response = $this->api->delete("activities/$id");
        if ($response === '') {
            return true;
        } else {
            if ($response === null) {
                $this->error .= "Unknown error";
            } else if (isset($response->message)) {
                $this->error .= $response->message;
            } else {
                $this->error .= $response;
            }
            return false;
        }
    }

    public function generateEndoExternalId($endoActivityId, $endoUserId)
    {
        return "endomondo_{$endoUserId}_{$endoActivityId}";
    }

}

?>
