<?php

namespace JoanMcGalliard;


class RideWithGpsApi
{

    const BASE_URL = "https://ridewithgps.com/";
    const VERSTION = 2;
    private $apikey = null;
    private $auth_token = "";
    private $error=null;

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    public function __construct($apikey, $auth_token = "")
    {
        $this->apikey = $apikey;
        $this->auth_token = $auth_token;
    }

    public function get($url, $params = [])
    {
        if (!$params) {
            $params = [];
        }
        $params["authToken"] = $this->auth_token;
        $params['apikey'] = $this->apikey;
        $params['apikey'] = self::VERSTION;

        $path = self::BASE_URL . $url . "?" . http_build_query($params);
        $process = curl_init($path);

        curl_setopt($process, CURLOPT_HEADER, 0);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);

        $page = curl_exec($process);
        $this->error = curl_error($process);
        log_msg("ridewithgps " . $path);
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


    public function setAuth($auth)
    {
        $this->auth_token = $auth;
    }
}