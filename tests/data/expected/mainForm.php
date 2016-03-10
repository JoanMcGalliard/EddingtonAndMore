<?php return "<form action=\"\" method=\"post\" name=\"main_form\"><input type=\"hidden\" name=\"main_form\"/><hr>Split multiday rides?:
            <input type=\"checkbox\" value=\"split\"   name=\"split_rides\"/><br>Save elevation as feet (MyCyclingLog only): <input type=\"checkbox\" name=\"elevation_units\" value=\"feet\" /><hr>
<script> function populateDates(start, end) {
            document.getElementById(\"datepicker_start\").value = start;
            document.getElementById(\"datepicker_end\").value = end;
            }
            </script>Fill in dates:
<span class=\"roundbutton\" onclick=\"populateDates('$today','')\">today</span>
<span class=\"roundbutton\" onclick=\"populateDates('$yesterday','')\">since yesterday</span>
<span class=\"roundbutton\" onclick=\"populateDates('$sevendays','')\">last 7 days</span>
<span class=\"roundbutton\" onclick=\"populateDates('$startOfMonth','')\">this month</span>
<span class=\"roundbutton\" onclick=\"populateDates('$startOfYear','')\">this year</span>
<span class=\"roundbutton\" onclick=\"populateDates('$beginningOfLastYear','$endOfLastYear')\">last year</span>
<span class=\"roundbutton\" onclick=\"populateDates('','')\">reset</span>
<br>
<br>
<table class=\"w3-table-all\"><tr>
                <td>Start Date <input type=\"text\" name=\"start_date\" id=\"datepicker_start\"/></td>
                <td> End Date <input type=\"text\" name=\"end_date\" id=\"datepicker_end\"/></td>
                <td><select name=\"tz\" id=\"tz\"> </select></td>
            </tr><script>
        $(\"#datepicker_start\").datepicker({changeMonth: true, changeYear: true, dateFormat: 'dd-mm-yy'});
        $(\"#datepicker_end\").datepicker({changeMonth: true, changeYear: true, dateFormat: 'dd-mm-yy'});
        $(\"#tz\").timezones();
        $(\"#tz\").val('UTC');</script>
<tr><td colspan=\"3\"><input type=\"submit\" name=\"calculate_from_strava\" value=\"Eddington Number from Strava\"/></td></tr>
<tr><td colspan=\"3\"><input type=\"submit\" name=\"calculate_from_mcl\" value=\"Eddington Number from MyCyclingLog\"/></td></tr>
<tr><td colspan=\"3\"><input type=\"submit\" name=\"calculate_from_endo\" value=\"Eddington Number from Endomondo\"/></td></tr>
<tr><td colspan=\"3\"><input type=\"submit\" name=\"calculate_from_rwgps\" value=\"Eddington Number from RideWithGPS\"/></td></tr>
<tr><td colspan=\"3\"><input type=\"submit\" name=\"copy_strava_to_mcl\" value=\"Copy ride data from Strava to MyCyclingLog\"/></td></tr>
<tr><td colspan=\"3\"><input type=\"submit\" name=\"copy_endo_to_strava\" value=\"Copy rides and routes from Endomondo to Strava\"/>  <br></td></tr>
<tr><td colspan=\"3\"><input type=\"submit\" name=\"queue_delete_endo_from_strava\" value=\"Delete Strava rides copied from Endomondo\"/>  <br></td></tr>
<tr><td colspan=\"3\"><input type=\"submit\" name=\"copy_endo_to_rwgps\" value=\"Copy rides and routes from Endomondo to RideWithGPS\"/>  <br></td></tr>
<tr><td colspan=\"3\"><input onclick=\"confirm_mcl_deletes()\" type=\"button\" name=\"delete_mcl_rides\" value=\"Delete MyCyclingLog rides\"/>
<script> function confirm_mcl_deletes() {
            var start = document.forms[\"main_form\"][\"start_date\"].value;
            var end_date = document.forms[\"main_form\"][\"end_date\"].value;
            if (start == \"\") {
                start = \"the beginning\"
            }
            if (end_date == \"\") {
                end_date = \"today\"
            }
            var password_warning = \"Are you sure you want to do this?  This will remove all activities from MyCyclingLog between \" + start + \" and \" + end_date + \" that have a Strava ride in the notes.\\n\\nIf you are sure, enter your MCL password here.\";
var username = prompt(\"Please enter your MyCyclingLog username\");var password = prompt(password_warning);
            if (password != null) {
                document.forms[\"main_form\"][\"start_date\"].value;

                submit_field = document.createElement('input');
                submit_field.setAttribute('name', 'delete_mcl_rides');
                submit_field.setAttribute('type', 'hidden');
                submit_field.setAttribute('value', 'Delete MyCyclingLog rides');
                document.forms[\"main_form\"].appendChild(submit_field);


                username_field = document.createElement('input');
                username_field.setAttribute('name', 'mcl_username');
                username_field.setAttribute('type', 'hidden');
                username_field.setAttribute('value', username);
                document.forms[\"main_form\"].appendChild(username_field);

                password_field = document.createElement('input');
                password_field.setAttribute('name', 'mcl_password');
                password_field.setAttribute('type', 'hidden');
                password_field.setAttribute('value', password);
                document.forms[\"main_form\"].appendChild(password_field);
                document.forms[\"main_form\"].submit(\"hello\");
            }
            else
                return false;
        }
        </script></td></tr>
 <tr>
            <td  colspan=\"3\"><input type=\"submit\" name=\"clear_cookies\" value=\"Delete Cookies\"/></td>
        </tr>
        <tr>
            <td  colspan=\"3\"><input type=\"submit\" name=\"delete_files\" value=\"Delete temporary files\"/>
            </td>
        </tr>
    </table><hr><H3>Copy rides....</H3><table><tr><td><select name=\"copySource\" >
<option value=''>From</option>
<option>MyCyclingLog</option>
<option>Strava</option>
<option>RideWithGps</option>
<option>Endomondo</option>

</select></td><td><select name=\"copyDestination\" >
<option value=''>To</option>
<option>MyCyclingLog</option>
<option>Strava</option>
<option>RideWithGps</option>
<option>Endomondo</option>

</select></td><td><input type=\"submit\" name=\"copy_rides\" value=\"Go\"/>  </td></tr></table><hr></form>";