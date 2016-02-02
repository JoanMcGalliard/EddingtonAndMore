<?php
/**
 * Created by PhpStorm.
 * User: jem
 * Date: 02/02/2016
 * Time: 15:52
 */

namespace JoanMcGalliard\EddingtonAndMore;


require_once 'TrackerAbstract.php';
require_once 'JoanMcGalliard/RideWithGpsApi.php';

use JoanMcGalliard;

class RideWithGps extends TrackerAbstract
{


    public function __construct($apikey, $echoCallback, $api = null)
    {
        $this->echoCallback = $echoCallback;
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
        $page = $this->api->get('/users/current.json', $username, $password);
        $json = json_decode($page);
        if (!$json) {
            $this->error .= $page;
        }
        $auth_token=null;
        if (isset($json->user) && isset($json->user->auth_token)) {
            $auth_token = $json->user->auth_token;
            if (isset($json->user->id)) {
                $this->userId = $json->user->id;
            }
        }
        if (!$auth_token) {
            if (isset ($json->error)) {
                $this->error .= $json->error;
            } else {
                $this->error .= $this->api->getError();
            }
        } else {
            $this->api->setAuth($auth_token);
        }
        $this->output(".");
        return $auth_token;
    }


    public function isConnected()
    {
        // TODO: Implement isConnected() method.
    }


    public function getRides($start_date, $end_date)
    {
        // TODO: Implement getRides() method.
    }

    public function getAuth()
    {
        // only called in tests
        return $this->api->getAuth();
    }

}