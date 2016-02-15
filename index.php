<?php
function myEcho($msg)
{
    echo "$msg\r\n";
    echo str_pad('', 4096);
}
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . DIRECTORY_SEPARATOR . "src" . PATH_SEPARATOR);

require_once 'src/MainPage.php';
$page=new MainPage('myEcho');
$page->render();

function eb($x)
{
    global $debug;
    if (!isset($debug) || !$debug) return;
    echo "<br>" . $x . "<br>";
}

function vdx($xml)
{
    vd(str_replace("<", "&lt;", str_replace(">", "&gt;", $xml)));
}

function vd($x)
{
    global $debug;
    if (!isset($debug) || !$debug) return;
    echo "<pre>";
    var_export($x);
    echo "</pre>";
    flush();
}


function log_msg($message)
{
    global $logDiagnostics;
    if (!isset($logDiagnostics) || !$logDiagnostics) {
        return;
    }
    if (is_string($message)) {
        $string = $message;
    } else {
        $string = var_export($message, true);
    }
    date_default_timezone_set("UTC");
    $date = date("Y-m-d H:i:s", time());
    $file = fopen(dirname(__FILE__) . DIRECTORY_SEPARATOR . "diagnostic.log", "a");
    fwrite($file, $date . ": " . $string . "\n");
    fclose($file);
}