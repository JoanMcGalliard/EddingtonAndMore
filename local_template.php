<?php
$timezone = "UTC";
$workingEmailAddress="email@example.com";
$scratchDirectory="/tmp";
$eddingtonAndMoreVersion=exec("git rev-parse HEAD");
$spamProofAddress="email @ example.com";

// device id sent to Endomondo.
$deviceId=$_SERVER['HTTP_HOST'];

// Obtain from Strava, https://www.strava.com/settings/api
$stravaClientId = null;
$stravaClientSecret = null;

//obtained from Google https://console.developers.google.com/flows/enableapi?apiid=timezone_backend&keyType=SERVER_SIDE&reusekey=true

$googleApiKey="";
$logDiagnostics=false;

?>
