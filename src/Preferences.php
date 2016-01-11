<?php

class Preferences
{
    const OLD_STRAVA_COOKIE = "STRAVA_ACCESS_TOKEN";
    const OLD_MCL_COOKIE = "MYCYCLINGLOG_AUTH";
    const OLD_ENDO_COOKIE = "ENDOMONDO_AUTH";
    const OLD_MCL_COOKIE_FEET = "MYCYCLINGLOG_USE_FEET";

    const COOKIE_NAME = "EDDINGTON_AND_MORE";

    const VERSION = 1;


    private $preferences;

    /**
     * Preferences constructor.
     */
    public function __construct()
    {
        $this->reset();

        if (array_key_exists(self::COOKIE_NAME, $_COOKIE)) {

            $this->preferences = json_decode($_COOKIE[self::COOKIE_NAME]);
        } else {
            $this->loadFromOldCookies();
            $this->save();
            $this->clearOldCookies();
        }

    }

    public function save()
    {
        setcookie(self::COOKIE_NAME, json_encode($this->preferences),time() + 60 * 60 * 24 * 365); //expires in 1 year);

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
        /*
         * array (
  'version' => 1,
  'mcl' =>
  array (
    'auth' => 'amVmbWNnOk0yajQ2Mjk5RGo=',
  ),
  'strava' =>
  array (
    'access_token' => '93c020201fb0ec14d25396e494827109b9dc257d',
  ),
  'endo' =>
  array (
    'auth' => 'J7CU9Z8mQKCEC2R74MFb8g',
  ),
)
         */

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

    private function reset() {
        $this->preferences=new stdClass();
        $this->preferences->version=self::VERSION;
        $this->preferences->mcl=new stdClass();
        $this->preferences->endo=new stdClass();
        $this->preferences->strava=new stdClass();

    }
    public function clear()
    {
        $this->clearOldCookies();
        $this->reset();
        $this->clearCookie(self::COOKIE_NAME);
    }

    public function setMclUseFeet($bool)
    {
        $this->preferences->mcl->use_feet=$bool;
        $this->save();
    }

    public function getEndoAuth()
    {
        return $this->preferences->endo->auth;
    }
    public function setEndoAuth($auth)
    {
        $this->preferences->endo->auth=$auth;
        $this->save();
    }

    public function setStravaAccessToken($token)
    {
        $this->preferences->strava->access_token = $token;
        $this->save();

    }

    public function getStravaAccessToken()
    {
        return $this->preferences->strava->access_token;
    }

    public function setMclAuth($auth)
    {
        $this->preferences->mcl->auth=$auth;
        $this->save();
    }
    public function getMclAuth()
    {
        return $this->preferences->mcl->auth;
    }
}

?>
