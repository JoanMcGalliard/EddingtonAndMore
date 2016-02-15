<?php

namespace JoanMcGalliard;


class EndomondoApi
{

    const BASE_URL = "https://api.mobile.endomondo.com/mobile/";
    const COUNTRY = 'GB';
    protected $auth = null;
    private $error;

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }


    public function get($url, $params = [])
    {
        if (!$this->auth) {
            return null;
        }
        if (!$params) {
            $params = [];
        }
        $params["authToken"] = $this->auth;

        $path = self::BASE_URL . $url . "?" . http_build_query($params);
        $process = curl_init($path);

        curl_setopt($process, CURLOPT_HEADER, 0);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);

        $page = curl_exec($process);
        $this->error = curl_error($process);
        log_msg("endomondo " . $path);
        log_msg($page);
        if ($this->error) log_msg("ERROR: " . $this->error);
        log_msg("Total time: " . curl_getinfo($process)["total_time"]);
        curl_close($process);
        if ($page) {
            return $page;
        } else {
            return $this->error;
        }
    }


    public function connect($username, $password, $deviceId)
    {
        $url = "auth";
        $params = [];
        $params['deviceId'] = $deviceId;
        $params['action'] = 'pair';
        $params['email'] = $username;
        $params['password'] = $password;
        $params['country'] = self::COUNTRY;

        {

            $path = self::BASE_URL . $url . "?" . http_build_query($params);
            $process = curl_init($path);
            curl_setopt($process, CURLOPT_HEADER, 0);
            curl_setopt($process, CURLOPT_TIMEOUT, 30);
            curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
            $page = curl_exec($process);
            curl_close($process);
            $pattern = "/^authToken=(.*)/";
            foreach (explode("\n", $page) as $line) {
                if (preg_match($pattern, $line, $matches) > 0) {
                    $this->auth = $matches[1];
                    $this->connected = true;
                    return $this->auth;
                }

            }
            $this->error = $page;

            return null;
        }

    }

    /**
     * @return null
     */
    public function getAuth()
    {
        return $this->auth;
    }

    /**
     * @param null $auth
     */
    public function setAuth($auth)
    {
        $this->auth = $auth;
    }


}