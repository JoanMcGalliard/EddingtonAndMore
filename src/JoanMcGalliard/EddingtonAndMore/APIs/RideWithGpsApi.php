<?php

namespace JoanMcGalliard;


use CURLFile;

class RideWithGpsApi
{

    const BASE_URL = "https://ridewithgps.com/";
    const VERSTION = 2;
    private $apikey = null;
    private $auth_token = "";
    private $error = null;

    public function __construct($apikey, $auth_token = "")
    {
        $this->apikey = $apikey;
        $this->auth_token = $auth_token;
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    public function upload($url, $file, $name, $params) {
        $this->error="";
        $cfile = new CURLFile($file, 'text', $name);
        $params["file"]=$cfile;
        return $this->post($url,$params);
    }

    public function post($url, $params = [])
    {
        $params["auth_token"] = $this->auth_token;
        $params['apikey'] = $this->apikey;
        $params['version'] = self::VERSTION;
        $curl = curl_init(self::BASE_URL . $url);
        $curlOptions = array(
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_REFERER => $url,
            CURLOPT_RETURNTRANSFER => true,
        );

        $curlOptions[CURLOPT_POST] = true;
        $curlOptions[CURLOPT_POSTFIELDS] = $params;
        curl_setopt_array($curl, $curlOptions);
        $response = curl_exec($curl);
        $this->error = curl_error($curl);
        log_msg("URL rwgps: " . $url);
        log_msg($params);
        log_msg($response);
        if ($this->error) log_msg("ERROR: " . $this->error);
        log_msg("Total time: " . curl_getinfo($curl)["total_time"]);

        $this->lastRequestInfo = curl_getinfo($curl);

        curl_close($curl);
        return $response;
    }

    public function get($url, $params = [])
    {
        $params["auth_token"] = $this->auth_token;
        $params['apikey'] = $this->apikey;
        $params['version'] = self::VERSTION;

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

    public function getAuth()
    {
        return $this->auth_token;
    }
}