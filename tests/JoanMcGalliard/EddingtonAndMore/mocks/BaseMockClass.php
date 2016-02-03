<?php
/**
 * Created by PhpStorm.
 * User: jem
 * Date: 02/02/2016
 * Time: 19:01
 */

namespace JoanMcGalliard\EddingtonAndMore\mocks;


use Exception;

class BaseMockClass
{

    protected $responses = [];

    public function get($request, $parameters = array())
    {
        if (isset($this->responses['get'][$request]) && sizeof($this->responses['get'][$request]) > 0) {
            return $this->getResponse('get', $request, $parameters);
        } else {

            throw new Exception("get $request: no more responses available");
        }

    }

    protected function getResponse($type, $request, $params)
    {
        return array_shift($this->responses[$type][$request]);
    }
    public function primeResponse($type, $request, $response)
    {
        $this->responses[$type][$request][] = $response;
    }

    public function clearResponses($type, $request)
    {
        unset($this->responses[$type][$request]);
    }


}