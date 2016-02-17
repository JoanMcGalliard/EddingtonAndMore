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

    protected function cleanDirectory($dir)
    {
        if (!file_exists($dir)) {
            mkdir($dir);
        }
        foreach (scandir($dir) as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (!is_dir($path)) {
                unlink($path);
            }
        }

    }


    protected function getMethod($name)
    {
        $class = new ReflectionClass($this->classUnderTest);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    public function getProperty($propertyName)
    {
        $reflector = new ReflectionClass($this->classUnderTest);
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
    protected function setUp()
    {
        parent::setUp();
        date_default_timezone_set('UTC');
    }
}
