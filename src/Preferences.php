<?php

class Preferences
{
    const OLD_STRAVA_COOKIE = "STRAVA_ACCESS_TOKEN";
    const OLD_MCL_COOKIE = "MYCYCLINGLOG_AUTH";
    const OLD_ENDO_COOKIE = "ENDOMONDO_AUTH";
    const OLD_MCL_COOKIE_FEET = "MYCYCLINGLOG_USE_FEET";

    const COOKIE_NAME = "EDDINGTON_AND_MORE";

    const VERSION = 3;


    private $preferences;

    /**
     * Preferences constructor.
     */
    public function __construct()
    {
        $this->preferences = $this->newPrefs();

        if (array_key_exists(self::COOKIE_NAME, $_COOKIE)) {
            $this->preferences = json_decode($_COOKIE[self::COOKIE_NAME]);
            if ($this->preferences->version == 1) {
                $this->preferences->rwgps = new stdClass();
                $this->preferences->version = 2;
                $this->save();
            }
            if ($this->preferences->version == 2) {
                if (isset($this->preferences->endo->splitRides) || isset($this->preferences->rwgps->splitRides) || isset($this->preferences->strava->splitRides)) {
                    $this->preferences->general->splitRides = true;
                } else {
                    $this->preferences->general->splitRides = false;
                }
                unset($this->preferences->endo->splitRides);
                unset($this->preferences->rwgps->splitRides);
                unset($this->preferences->strava->splitRides);

                $this->preferences->version = 3;
                $this->save();
            }
        } else {
            $this->loadFromOldCookies();
            $this->save();
            $this->clearOldCookies();
        }

    }


    private function newPrefs()
    {
        $preferences = new stdClass();
        $preferences->version = self::VERSION;
        $preferences->mcl = new stdClass();
        $preferences->endo = new stdClass();
        $preferences->strava = new stdClass();
        $preferences->rwgps = new stdClass();
        $preferences->general = new stdClass();
        return $preferences;
    }

    private function loadFromOldCookies()
    {
        if (array_key_exists(self::OLD_MCL_COOKIE, $_COOKIE)) {
            $this->preferences->mcl->auth = $_COOKIE[self::OLD_MCL_COOKIE];
        }
        if (array_key_exists(self::OLD_MCL_COOKIE_FEET, $_COOKIE)) {
            $this->preferences->mcl->use_feet = $_COOKIE[self::OLD_MCL_COOKIE_FEET];
        }
        if (array_key_exists(self::OLD_STRAVA_COOKIE, $_COOKIE)) {
            $this->preferences->strava->access_token = $_COOKIE[self::OLD_STRAVA_COOKIE];
        }
        if (array_key_exists(self::OLD_ENDO_COOKIE, $_COOKIE)) {
            $this->preferences->endo->auth = $_COOKIE[self::OLD_ENDO_COOKIE];

        }
    }

    public function save()
    {
        if ($this->preferences != $this->newPrefs()) {
            setcookie(self::COOKIE_NAME, json_encode($this->preferences), time() + 60 * 60 * 24 * 365); //expires in 1 year);
        }
    }

    private
    function clearOldCookies()
    {
        $this->clearCookie(self::OLD_MCL_COOKIE);
        $this->clearCookie(self::OLD_MCL_COOKIE_FEET);
        $this->clearCookie(self::OLD_ENDO_COOKIE);
        $this->clearCookie(self::OLD_STRAVA_COOKIE);

    }

    private function clearCookie($cookie)
    {
        setcookie($cookie, null, time() - 3600);
        unset($_COOKIE[$cookie]);
    }

    public function clear()
    {
        $this->clearOldCookies();
        $this->preferences = $this->newPrefs();
        $this->clearCookie(self::COOKIE_NAME);
    }

    public function setMclUseFeet($bool)
    {
        $this->preferences->mcl->use_feet = $bool;
        $this->save();
    }

    public function getMclUseFeet()
    {
        return isset($this->preferences->mcl->use_feet) ? $this->preferences->mcl->use_feet : null;
    }


    /**
     * @return null
     */
    public function getSplitRides()
    {
        return isset($this->preferences->general->splitRides) ? $this->preferences->general->splitRides : false;
    }
    public function setSplitRides($bool)
    {
        $this->preferences->general->splitRides=$bool;
        $this->save();
    }

    public function getEndoAuth()
    {
        return isset($this->preferences->endo->auth) ? $this->preferences->endo->auth : null;
    }

    public function setEndoAuth($auth)
    {
        $this->preferences->endo->auth = $auth;
        $this->save();
    }

    public function setStravaAccessToken($token)
    {
        $this->preferences->strava->access_token = $token;
        $this->save();

    }

    public function getStravaAccessToken()
    {
        return isset($this->preferences->strava->access_token) ? $this->preferences->strava->access_token : null;
    }


    public function setMclAuth($auth)
    {
        $this->preferences->mcl->auth = $auth;
        $this->save();
    }

    public function getMclAuth()
    {
        return isset($this->preferences->mcl->auth) ? $this->preferences->mcl->auth : null;
    }

    public function setTimezone($tz)
    {
        $this->preferences->timezone = $tz;
        $this->save();
    }

    public function getTimezone()
    {
        if (isset($this->preferences->timezone)) {
            return $this->preferences->timezone;
        } else {
            return "Europe/London";
        }
    }

    public function setMclUsername($username)
    {
        $this->preferences->mcl->username = $username;
        $this->save();
    }

    public function getMclUsername()
    {
        return isset($this->preferences->mcl->username) ? $this->preferences->mcl->username : null;
    }

    public function getRwgpsAuth()
    {
        return isset($this->preferences->rwgps->auth) ? $this->preferences->rwgps->auth : null;
    }

    public function setRwgpsAuth($auth)
    {
        $this->preferences->rwgps->auth = $auth;
        $this->save();
    }

    public function setStravaWriteScope($writeScope)
    {
        $this->preferences->strava->writeScope = $writeScope;
        $this->save();
    }

    public function getStravaWriteScope()
    {
        return isset($this->preferences->strava->writeScope) ? $this->preferences->strava->writeScope : false;
    }

}

?>
