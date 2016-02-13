<?php

namespace JoanMcGalliard;


use CURLFile;

class GoogleApi
{

    const BASE_URL = "https://maps.googleapis.com/maps/api/";
    private $apikey = null;

    /**
     * @param null $apikey
     */
    public function setApikey($apikey)
    {
        $this->apikey = $apikey;
    }
    private $error = "";

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    public function get($url, $params = [])
    {
        if (!$this->apikey) {
            $this->error="No Google maps api key set";
            return false;
        }
        $this->error="";
        $retries = 3;
        $params['key']=$this->apikey;
        $url = self::BASE_URL.$url . "?" . http_build_query($params);
        $process = curl_init($url);

        curl_setopt($process, CURLOPT_HEADER, 0);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);

        $page=false; // make IDE happy
        for ($i = 0; $i < $retries; $i++) {
            $page = curl_exec($process);
            $error = curl_error($process);
            if ($error) {
                $this->error.="$error\n";
            }
            if ($page) break;
        }
        log_msg("google " . $url);
        log_msg($page);
        if ($error) log_msg("ERROR: " . $error);
        log_msg("Total time: " . curl_getinfo($process)["total_time"]);

        curl_close($process);
        return $page;
    }
}
