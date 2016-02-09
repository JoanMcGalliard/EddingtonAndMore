<?php
namespace JoanMcGalliard\EddingtonAndMore;

use PHPUnit_Framework_TestCase;

abstract class BaseTestClass extends PHPUnit_Framework_TestCase
{
    protected $output;

    public function myEcho($msg)
    {
        $this->output .= $msg;
    }

}