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
