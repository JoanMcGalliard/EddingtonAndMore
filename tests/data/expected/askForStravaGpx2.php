<?php
return "<br>
To split your strava rides, you'll need to download some of the GPX from Strava, them upload them to here. <br>
<strong>First</strong> click the following links to download the GPX files.
<ol>
    <li><a target=\"_blank\" href=\"https://www.strava.com/activities/460839481/export_gpx\">Afternoon Ride 3 km</a></li>
</ol>
<form action=\"\" method=\"post\" enctype=\"multipart/form-data\">
<strong>Then</strong> select the GPX file(s) that you have just downloaded:<br>
<input type=\"file\" name=\"gpx[]\" id=\"gpx\" multiple>
<input type=\"hidden\" name=\"start_date\" value=\"01-01-2015\"/>
<input type=\"hidden\" name=\"end_date\" value=\"31-12-2015\"/>
<input type=\"hidden\" name=\"copy_strava_to_mcl\" />
<input type=\"hidden\" value=\"split\" checked name=\"split_rides\"/><br>
<strong>Finally</strong>, add to MyCyclingLog:<br>
<input type=\"submit\" value=\"Upload and add to MyCyclingLog\" name=\"submit\"/></form>";
