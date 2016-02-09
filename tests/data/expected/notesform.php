<?php
return "<div id=\"notes\">
    <p>Notes:</p>
    <ol>
        <li><em>date format is dd-mm-yyyy</em></li>
        <li><em>You can set either or both dates, or leave them both blank your lifetime
                E-number.</em></li>
        <li><em>the timezone is used to determine midnight for the date range</em></li>
        <li><em>If you upload files, they will be kept on a scratch directory with your Strava User Id,
                so you won't have to reupload them every time. You can remove the files from the server
                by pressing the appropriate button above.</em></li>
        <li><em>when using Strava,
                each
                ride's date is the local time saved by Strava</em></li>
        <li><em>Timezone set here will be used with Endomondo to determine the start of the new day</em></li>
        <li><em>You can set either or both dates, or leave them both
                blank for your lifetime E-number.</em></li>
        <li><em>By default, all the miles during a ride (even if it takes several days) count towards the total of
                the
                first day.</em></li>

        <li><em>You can choose to split it into multiple days, to get the
                mileage for each day midnight-midnight.</em></li>
        <li><em>As I can't get the GPS points directly from Strava, Strava rides can
                only be split by you downloading them onto your machine, and then uploading
                them here.</em></li>
        <li><em>As splitting Strava rides is such a faff, it's probably easiest to use the copy feature above to
                copy them to MyCyclingLog, then calculate your E-number from that. Then you will only need
                download/upload them
                once.</em></li>
        <li><em>It might take a minute or two to come back with an answer</em></li>
        <li><em>It's much slower if you split the rides.</em></li>
        <li><em>Rides of less than 500m are not copied between systems.</em></li>
        <li><em>Rides copied from endomondo are considered duplicates if there is already a ride on strava that
                overlaps it.
            </em>
        </li>
        <li><em>MyCyclingLog stores elevation as a number without units. By default, copy will leave the
                elevation
                in metres, but if you check the box, it will multiply elevation by 3.2, converting it to feet.</em>
        </li>

        <li><em>If you want your bike information to be included you must make sure you have bikes with
                <strong>exactly</strong>
                matching make/model in both accounts. To test, select start and end dates close together, then check
                MyCyclingLog to see if you like the result. </em></li>
        <li><em>It should not make duplicates if the ride has already been copied using this
                page, or if the total distance for the day on MCL is within
                2% or greater than the distance recorded in Strava.</em></li>
        <li><em>This is open source, you can download the source from <a
                    href=\"http://github.com/JoanMcGalliard/EddingtonAndMore\">
                    http://github.com/JoanMcGalliard/EddingtonAndMore</a>. This is
                revision ba78b6e7f3bfd259814946fa6d15b769d879cf37.
            </em></li>

    </ol>


</div>
";