<?php
/**
 * Created by PhpStorm.
 * User: jem
 * Date: 11/01/2016
 * Time: 11:15
 */
?>
curl -X POST https://www.strava.com/api/v3/uploads     -H "Authorization: Bearer 93c020201fb0ec14d25396e494827109b9dc257d"     -F activity_type=ride     -F file=@test.gpx     -F data_type=gpx

curl -G https://www.strava.com/api/v3/uploads/519081907 -H "Authorization: Bearer 93c020201fb0ec14d25396e494827109b9dc257d"
