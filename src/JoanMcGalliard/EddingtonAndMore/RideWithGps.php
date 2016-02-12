<?php

namespace JoanMcGalliard\EddingtonAndMore;


require_once 'TrackerAbstract.php';
require_once 'JoanMcGalliard/EddingtonAndMore/APIs/RideWithGpsApi.php';
require_once 'JoanMcGalliard/EddingtonAndMore/Points.php';

use ArrayObject;
use JoanMcGalliard;

class RideWithGps extends TrackerAbstract
{
    private $connected;
    /** @var GoogleMaps $googleMaps */
    private $googleMaps;

    public function __construct($apikey, $echoCallback, $timezone, $api = null)
    {
        $this->echoCallback = $echoCallback;
        $this->timezone = $timezone;
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
                $this->connected = true;
                $this->userId = $json->user->id;
            }
            if (isset($json->user->id)) {
                ;
            } else {
            }
        }
        return $this->connected;
    }

    public function setGoogleApi($google)
    {
        $this->googleMaps = $google;
    }

    private function getTimezone($ride)
    {
        static $timezones;
        if (!$timezones) {
            $temp = [];

            foreach (timezone_abbreviations_list() as $tz_set) {
                foreach ($tz_set as $tz) {
                    $temp[$tz['timezone_id']] = "";
                }
            }
            $timezones = array_keys($temp);
        }
        static $timezones_map = array("International Date Line West" => "Pacific/Midway",
            "Midway Island" => "Pacific/Midway",
            "American Samoa" => "Pacific/Pago_Pago",
            "Hawaii" => "Pacific/Honolulu",
            "Alaska" => "America/Juneau",
            "Pacific Time (US & Canada)" => "America/Los_Angeles",
            "Tijuana" => "America/Tijuana",
            "Mountain Time (US & Canada)" => "America/Denver",
            "Arizona" => "America/Phoenix",
            "Chihuahua" => "America/Chihuahua",
            "Mazatlan" => "America/Mazatlan",
            "Central Time (US & Canada)" => "America/Chicago",
            "Saskatchewan" => "America/Regina",
            "Guadalajara" => "America/Mexico_City",
            "Mexico City" => "America/Mexico_City",
            "Monterrey" => "America/Monterrey",
            "Central America" => "America/Guatemala",
            "Eastern Time (US & Canada)" => "America/New_York",
            "Indiana (East)" => "America/Indiana/Indianapolis",
            "Bogota" => "America/Bogota",
            "Lima" => "America/Lima",
            "Quito" => "America/Lima",
            "Atlantic Time (Canada)" => "America/Halifax",
            "Caracas" => "America/Caracas",
            "La Paz" => "America/La_Paz",
            "Santiago" => "America/Santiago",
            "Newfoundland" => "America/St_Johns",
            "Brasilia" => "America/Sao_Paulo",
            "Buenos Aires" => "America/Argentina/Buenos_Aires",
            "Montevideo" => "America/Montevideo",
            "Georgetown" => "America/Guyana",
            "Greenland" => "America/Godthab",
            "Mid-Atlantic" => "Atlantic/South_Georgia",
            "Azores" => "Atlantic/Azores",
            "Cape Verde Is." => "Atlantic/Cape_Verde",
            "Dublin" => "Europe/Dublin",
            "Edinburgh" => "Europe/London",
            "Lisbon" => "Europe/Lisbon",
            "London" => "Europe/London",
            "Casablanca" => "Africa/Casablanca",
            "Monrovia" => "Africa/Monrovia",
            "UTC" => "Etc/UTC",
            "Belgrade" => "Europe/Belgrade",
            "Bratislava" => "Europe/Bratislava",
            "Budapest" => "Europe/Budapest",
            "Ljubljana" => "Europe/Ljubljana",
            "Prague" => "Europe/Prague",
            "Sarajevo" => "Europe/Sarajevo",
            "Skopje" => "Europe/Skopje",
            "Warsaw" => "Europe/Warsaw",
            "Zagreb" => "Europe/Zagreb",
            "Brussels" => "Europe/Brussels",
            "Copenhagen" => "Europe/Copenhagen",
            "Madrid" => "Europe/Madrid",
            "Paris" => "Europe/Paris",
            "Amsterdam" => "Europe/Amsterdam",
            "Berlin" => "Europe/Berlin",
            "Bern" => "Europe/Berlin",
            "Rome" => "Europe/Rome",
            "Stockholm" => "Europe/Stockholm",
            "Vienna" => "Europe/Vienna",
            "West Central Africa" => "Africa/Algiers",
            "Bucharest" => "Europe/Bucharest",
            "Cairo" => "Africa/Cairo",
            "Helsinki" => "Europe/Helsinki",
            "Kyiv" => "Europe/Kiev",
            "Riga" => "Europe/Riga",
            "Sofia" => "Europe/Sofia",
            "Tallinn" => "Europe/Tallinn",
            "Vilnius" => "Europe/Vilnius",
            "Athens" => "Europe/Athens",
            "Istanbul" => "Europe/Istanbul",
            "Minsk" => "Europe/Minsk",
            "Jerusalem" => "Asia/Jerusalem",
            "Harare" => "Africa/Harare",
            "Pretoria" => "Africa/Johannesburg",
            "Kaliningrad" => "Europe/Kaliningrad",
            "Moscow" => "Europe/Moscow",
            "St. Petersburg" => "Europe/Moscow",
            "Volgograd" => "Europe/Volgograd",
            "Samara" => "Europe/Samara",
            "Kuwait" => "Asia/Kuwait",
            "Riyadh" => "Asia/Riyadh",
            "Nairobi" => "Africa/Nairobi",
            "Baghdad" => "Asia/Baghdad",
            "Tehran" => "Asia/Tehran",
            "Abu Dhabi" => "Asia/Muscat",
            "Muscat" => "Asia/Muscat",
            "Baku" => "Asia/Baku",
            "Tbilisi" => "Asia/Tbilisi",
            "Yerevan" => "Asia/Yerevan",
            "Kabul" => "Asia/Kabul",
            "Ekaterinburg" => "Asia/Yekaterinburg",
            "Islamabad" => "Asia/Karachi",
            "Karachi" => "Asia/Karachi",
            "Tashkent" => "Asia/Tashkent",
            "Chennai" => "Asia/Kolkata",
            "Kolkata" => "Asia/Kolkata",
            "Mumbai" => "Asia/Kolkata",
            "New Delhi" => "Asia/Kolkata",
            "Kathmandu" => "Asia/Kathmandu",
            "Astana" => "Asia/Dhaka",
            "Dhaka" => "Asia/Dhaka",
            "Sri Jayawardenepura" => "Asia/Colombo",
            "Almaty" => "Asia/Almaty",
            "Novosibirsk" => "Asia/Novosibirsk",
            "Rangoon" => "Asia/Rangoon",
            "Bangkok" => "Asia/Bangkok",
            "Hanoi" => "Asia/Bangkok",
            "Jakarta" => "Asia/Jakarta",
            "Krasnoyarsk" => "Asia/Krasnoyarsk",
            "Beijing" => "Asia/Shanghai",
            "Chongqing" => "Asia/Chongqing",
            "Hong Kong" => "Asia/Hong_Kong",
            "Urumqi" => "Asia/Urumqi",
            "Kuala Lumpur" => "Asia/Kuala_Lumpur",
            "Singapore" => "Asia/Singapore",
            "Taipei" => "Asia/Taipei",
            "Perth" => "Australia/Perth",
            "Irkutsk" => "Asia/Irkutsk",
            "Ulaanbaatar" => "Asia/Ulaanbaatar",
            "Seoul" => "Asia/Seoul",
            "Osaka" => "Asia/Tokyo",
            "Sapporo" => "Asia/Tokyo",
            "Tokyo" => "Asia/Tokyo",
            "Yakutsk" => "Asia/Yakutsk",
            "Darwin" => "Australia/Darwin",
            "Adelaide" => "Australia/Adelaide",
            "Canberra" => "Australia/Melbourne",
            "Melbourne" => "Australia/Melbourne",
            "Sydney" => "Australia/Sydney",
            "Brisbane" => "Australia/Brisbane",
            "Hobart" => "Australia/Hobart",
            "Vladivostok" => "Asia/Vladivostok",
            "Guam" => "Pacific/Guam",
            "Port Moresby" => "Pacific/Port_Moresby",
            "Magadan" => "Asia/Magadan",
            "Srednekolymsk" => "Asia/Srednekolymsk",
            "Solomon Is." => "Pacific/Guadalcanal",
            "New Caledonia" => "Pacific/Noumea",
            "Fiji" => "Pacific/Fiji",
            "Kamchatka" => "Asia/Kamchatka",
            "Marshall Is." => "Pacific/Majuro",
            "Auckland" => "Pacific/Auckland",
            "Wellington" => "Pacific/Auckland",
            "Nuku'alofa" => "Pacific/Tongatapu",
            "Tokelau Is." => "Pacific/Fakaofo",
            "Chatham Is." => "Pacific/Chatham",
            "Samoa" => "Pacific/Apia");
        if (isset($ride->time_zone)) {
            if (in_array($ride->time_zone, $timezones)) {
                return $ride->time_zone;
            }
            if (isset($timezones_map[$ride->time_zone]) && in_array($timezones_map[$ride->time_zone], $timezones)) {
                return $timezones_map[$ride->time_zone];
            }
        }
        if (isset($ride->utc_offset)) {
            $tz = timezone_name_from_abbr('', $ride->utc_offset, 0);
            if ($tz && in_array($tz, $timezones)) {
                return $tz;
            }
        }
        if (isset($ride->first_lng) && isset($ride->first_lat) && isset($ride->departed_at) && isset($this->googleMaps)) {
            $tz = $this->googleMaps->timezoneFromCoords($ride->first_lat, $ride->first_lng, $ride->departed_at);
            if ($tz && in_array($tz, $timezones)) {
                return $tz;
            }

        }
        return $this->timezone;
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
                $next['start_time'] = $ride->departed_at;
                $next['distance'] = $ride->distance;
                $next['moving_time'] = $this->convertToSeconds($ride->moving_time);
                $next['elapsed_time'] = $this->convertToSeconds($ride->duration);
                $next['max_speed'] = $ride->max_speed;
                $next['name'] = $ride->name;
                $next['bike'] = $ride->gear_id;
                $next['total_elevation_gain'] = $ride->elevation_gain;
                $next['endo_id'] = null;

                if (isset($ride->description)) {
                    $pattern = "/https+:\/\/www.endomondo.com\/users\/.*\/workouts\/([0-9]*)/";
                    if (preg_match($pattern, $ride->description, $matches) > 0) {
                        $next['endo_id'] = $matches[1];
                    }
                }

                $date = date("Y-m-d", strtotime($ride->departed_at));
                $tz=$this->getTimezone($ride);
                if ($this->splitOvernightRides && $this->isOvernight($next['start_time'], $tz,$next['elapsed_time'])) {
                    $points = $this->getPoints($next['rwgps_id'],$this->getTimezone($ride),$next['start_time'] );
                    if (!$points || ! $points->getSplits() ) {
                        $this->error .= "Could not split overnight ride on $ride->departed_at due to errors.<br>";
                        $rides[$date][] = $next;
                    } else {
                        foreach ($points->getSplits() as $split_date => $split) {
                            $new = (new ArrayObject($next))->getArrayCopy();
                            $new['distance'] = $split;
                            $rides[$split_date][] = $new;
                        }
                    }
                    $points = null; // free memory
                } else {
                    $rides[$date][] = $next;
                }
            }
            if (sizeof($json->results) < $limit) {
                return $rides;
            }
            $offset += $limit;
            $this->output('.');
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
        $this->error = "";
        $params = ["trip[name]" => $name, "trip[description]" => $description];
        $page = $this->api->upload("/trips.json", $file_path, $name, $params);
        if ($this->api->getError()) {
            $this->error .= $this->api->getError();
        }
        $json = json_decode($page);
        if (!$json) {
            $this->error .= $page;
        } else if (isset($json->error)) {
            $this->error .= $json->error;
        } else if (isset($json->success) && ($json->success < 1)) {
            $this->error .= "Unexpected status on uploaded file " . $file_path;
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

    public function waitForPendingUploads($sleep = 1)
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
            sleep($sleep);
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

    public function getPoints($tripId, $timezone, $start_day)
    {
        $url = "/trips/$tripId.json";
        $page=$this->api->get($url);
        $this->output('.');
        if (!$page)
        {
            $this->error.=$this->api->getError();
            return null;
        }
        $json = json_decode($page);
        if (!$json) {
            $this->error.="RWGps didn't return JSON for $url, ".$page;
            return null;
        }
        $track_points=$json->trip->track_points;
        if (!$track_points || sizeof($track_points) == 0 ) {
            $this->error.="RWGps didn't return any points";
            return null;
        }
        $points=new Points($start_day, $this->echoCallback, $timezone);
        foreach ($track_points as $track_point)
        {
            if (!isset($track_point->y) || !isset($track_point->x)) {
                continue;
            }
            $lat=$track_point->y;
            $long=$track_point->x;
            $time=null;
            if (isset($track_point->t)) {
                $time=$track_point->t;
            }
            $points->add($lat,$long,$time);
        }
        return $points;
    }


}