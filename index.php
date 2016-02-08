<?php
ob_implicit_flush();

set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . DIRECTORY_SEPARATOR . "src" . PATH_SEPARATOR);

require_once 'MainPage.php';
$page=new MainPage('myEcho');
$page->render();