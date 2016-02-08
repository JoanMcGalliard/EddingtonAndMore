<?php

namespace JoanMcGalliard\EddingtonAndMore\mocks;


use Exception;

class BaseMockClass
{

    protected $responses = [];


    public function get($request, $parameters = array())
    {
        return $this->respond("get",$request,$parameters);
    }
    public function upload($url, $file, $name, $params)
    {
        return $this->respond("upload",$url,$params);
    }
    protected function respond($type, $request,$parameters)
    {
        if (isset($this->responses[$type][$request]->queued_messages) && sizeof($this->responses[$type][$request]->queued_messages) > 0) {
            $response = array_shift($this->responses[$type][$request]->queued_messages);
            if (isset ($this->responses[$type][$request]->repeat) && $this->responses[$type][$request]->repeat==true) {
                //push last response back on queue
                $this->primeResponse($type, $request,$response);
            }
            return $response;
        } else {
            throw new Exception("$type $request: no more responses available");
        }

    }

    public function primeResponse($type, $request, $response)
    {
        $this->responses[$type][$request]->queued_messages[] = $response;
    }
    public function primeRepeat($type, $request)
        // from now on this will keep sending same message over and over
    {
        $this->responses[$type][$request]->repeat=true;
    }

    public function clearResponses($type, $request)
    {
        unset($this->responses[$type][$request]);
    }


}