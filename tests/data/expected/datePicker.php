<?php return "<tr>
                <td>Start Date <input type=\"text\" name=\"start_date\" id=\"datepicker_start\"/></td>
                <td> End Date <input type=\"text\" name=\"end_date\" id=\"datepicker_end\"/></td>
                <td><select name=\"tz\" id=\"tz\"> </select></td>
            </tr><script>
        $(\"#datepicker_start\").datepicker({changeMonth: true, changeYear: true, dateFormat: 'dd-mm-yy'});
        $(\"#datepicker_end\").datepicker({changeMonth: true, changeYear: true, dateFormat: 'dd-mm-yy'});
        $(\"#tz\").timezones();
        $(\"#tz\").val('TIME ZONE');</script>
";