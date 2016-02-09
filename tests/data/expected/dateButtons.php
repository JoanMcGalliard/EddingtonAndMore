<?php
return "<script> function populateDates(start, end) {
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
";