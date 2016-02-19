<?php return "<input onclick=\"confirm_mcl_deletes()\" type=\"button\" name=\"delete_mcl_rides\" value=\"Delete MyCyclingLog rides\"/>
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
        </script>";