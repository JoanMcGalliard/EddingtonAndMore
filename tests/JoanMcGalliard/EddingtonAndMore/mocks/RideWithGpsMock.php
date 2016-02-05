<?php
/**
 * Created by PhpStorm.
 * User: jem
 * Date: 02/02/2016
 * Time: 16:56
 */

namespace JoanMcGalliard\EddingtonAndMore\mocks;

require_once 'BaseMockClass.php';


use StravaApiMock;

class RideWithGpsMock extends BaseMockClass
{


    private $auth_token;

    /**
     * @return mixed
     */
    public function getAuthToken()
    {
        return $this->auth_token;
    }

    /**
     * @param mixed $auth_token
     */
    public function setAuth($auth_token)
    {
        $this->auth_token = $auth_token;
    }

    public function getAuth()
    {
        return $this->auth_token;
    }

    public function getError()
    {
        return "";

    }
}