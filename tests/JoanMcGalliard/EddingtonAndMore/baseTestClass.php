<?php
/**
 * Created by PhpStorm.
 * User: jem
 * Date: 02/02/2016
 * Time: 18:39
 */
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