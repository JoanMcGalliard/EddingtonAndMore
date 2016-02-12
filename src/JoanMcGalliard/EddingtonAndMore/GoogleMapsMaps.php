<?php
/**
 * Created by IntelliJ IDEA.
 * User: jem
 * Date: 12/02/2016
 * Time: 10:29
 */

namespace JoanMcGalliard\EddingtonAndMore;


use JoanMcGalliard\GoogleApi;

class GoogleMaps
{

    private $api;
    private $error;

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Google constructor.
     * @param $api
     */
    public function __construct($apiKey)
    {
        $this->api = new GoogleApi();
        $this->api->setApikey($apiKey);
    }

    public function timezoneFromCoords($lat,$long, $time) {
        $params = ['location' => "$lat,$long",
            'timestamp' => $time];
        $page = $this->api->get('timezone/json', $params);
        $json = json_decode($page);

        if ($page && $json && isset($json->timeZoneId)) {
            $tz= $json->timeZoneId;
        } else if (!$page) {
            $this->error .= $this->api->getError();
        } else if (!$json) {
            $this->error .= $page;
        } else { // it's json, just not what we expected
            if (isset($json->errorMessage)) {
                $this->error .= $json->errorMessage;
            } else {
                $this->error .= "Unknown JSON returned by Google API, $page";
            }
        }
    }

}