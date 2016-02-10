<?php
return "<br>
To split your strava rides, you'll need to download some of the GPX from Strava, them upload them to here. <br>
<strong>First</strong> click the following links to download the GPX files.
<ol>
    <li><a target=\"_blank\" href=\"https://www.strava.com/activities/316089905/export_gpx\">Windsor-Chester-Windsor Audax - DNF 479 km</a></li>
    <li><a target=\"_blank\" href=\"https://www.strava.com/activities/478737831/export_gpx\">Severn Across 408 km</a></li>
    <li><a target=\"_blank\" href=\"https://www.strava.com/activities/330251139/export_gpx\">The Buzzard Audax (partial)  319 km</a></li>
</ol>
<em>(You've got another 4 overnight ride(s) to add after this (you do like riding over midnight!), but we are restricting
it to 1000 kilometres or so at a time to keep the server behaving nicely. The rides above are the longest of your rides that are needed.)</em>
<form action=\"\" method=\"post\" enctype=\"multipart/form-data\">
<strong>Then</strong> select the GPX file(s) that you have just downloaded:<br>
<input type=\"file\" name=\"gpx[]\" id=\"gpx\" multiple>
<input type=\"hidden\" name=\"start_date\" value=\"01-01-2015\"/>
<input type=\"hidden\" name=\"end_date\" value=\"31-12-2015\"/>
<input type=\"hidden\" name=\"calculate_from_strava\" />
<input type=\"hidden\" value=\"split\" checked name=\"strava_split_rides\"/><br>
<strong>Finally</strong>, recalculate your E-Number:<br>
<input type=\"submit\" value=\"Upload and recalculate your E-Number\" name=\"submit\"/></form>";
