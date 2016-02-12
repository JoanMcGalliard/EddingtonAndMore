<?php
namespace JoanMcGalliard\EddingtonAndMore;

use Exception;
use PHPUnit_Framework_TestCase;
use ReflectionClass;

abstract class BaseTestClass extends PHPUnit_Framework_TestCase
{
    protected $output;
    protected $classUnderTest="YOU NEED TO SET THIS VALUE";

    public function myEcho($msg)
    {
        $this->output .= $msg;
    }

    protected function getMethod($name)
    {
        $class = new ReflectionClass($this->classUnderTest);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    public function getPrivateProperty($className, $propertyName)
    {
        $reflector = new ReflectionClass($className);
        $property = $reflector->getProperty($propertyName);
        $property->setAccessible(true);

        return $property;
    }

    protected function setProperty($name, $value, $obj)
    {
        $class = new ReflectionClass($this->classUnderTest);
        $property = $class->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($obj, $value);
    }


}

class BaseMockClass
{

    protected $responses = [];
    protected $error;

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param mixed $error
     */
    public function setError($error)
    {
        $this->error = $error;
    }


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