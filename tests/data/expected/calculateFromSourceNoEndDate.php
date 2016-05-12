<?php return "<p>According to $source, for the period from $start_date to today, $elapsed_days elapsed days</p>
<br><a href=\"#imperial\">Your imperial Eddington Number</a> is <strong>9</strong>.<br>
You need to do 1 ride(s) of at least 10 to increase it to 10.<br>
You need to do 3 ride(s) of at least 11 to increase it to 11.<br>
You need to do 4 ride(s) of at least 12 to increase it to 12.<br>
You need to do 8 ride(s) of at least 15 to increase it to 15.<br>
You need to do 16 ride(s) of at least 20 to increase it to 20.<br>
You need to do 49 ride(s) of at least 50 to increase it to 50.<br>
You need to do 100 ride(s) of at least 100 to increase it to 100.<br>
<br><a href=\"#metric\">Your metric Eddington Number</a> is <strong>10</strong><br>
You need to do 1 ride(s) of at least 11 to increase it to 11.<br>
You need to do 2 ride(s) of at least 12 to increase it to 12.<br>
You need to do 3 ride(s) of at least 13 to increase it to 13.<br>
You need to do 5 ride(s) of at least 15 to increase it to 15.<br>
You need to do 12 ride(s) of at least 20 to increase it to 20.<br>
You need to do 46 ride(s) of at least 50 to increase it to 50.<br>
You need to do 100 ride(s) of at least 100 to increase it to 100.<br>
<br><a href=\"#eddington_chart\">See a chart of how your Eddington number has grown over the years.</a><br><p><em>Run time 0 seconds.</em></p>
<table id=\"imperial\" class=\"w3-table-all w3-right-align\"  style=\"width:60%\"><tr><th>Count</th><th>Date </th><th class=\"w3-right-align\">Distance</th></tr><tr><td> 1 </td><td> 2016-01-10</td><td class=\"w3-right-align\">61 miles</td></tr><tr><td> 2 </td><td> 2016-01-30</td><td class=\"w3-right-align\">41 miles</td></tr><tr><td> 3 </td><td> 2016-01-28</td><td class=\"w3-right-align\">39 miles</td></tr><tr><td> 4 </td><td> 2016-01-23</td><td class=\"w3-right-align\">31 miles</td></tr><tr><td> 5 </td><td> 2016-02-07</td><td class=\"w3-right-align\">19 miles</td></tr><tr><td> 6 </td><td> 2016-01-01</td><td class=\"w3-right-align\">16 miles</td></tr><tr><td> 7 </td><td> 2016-02-09</td><td class=\"w3-right-align\">15 miles</td></tr><tr><td> 8 </td><td> 2016-02-02</td><td class=\"w3-right-align\">13 miles</td></tr><tr><td> 9 </td><td> 2016-01-17</td><td class=\"w3-right-align\">10 miles</td></tr></table><table id=\"metric\" class=\"w3-table-all w3-right-align\"  style=\"width:60%\"><tr><th>Count</th><th>Date </th><th class=\"w3-right-align\">Distance</th></tr><tr><td> 1 </td><td>2016-01-10</td><td class=\"w3-right-align\">99 km</td></tr><tr><td> 2 </td><td>2016-01-30</td><td class=\"w3-right-align\">66 km</td></tr><tr><td> 3 </td><td>2016-01-28</td><td class=\"w3-right-align\">63 km</td></tr><tr><td> 4 </td><td>2016-01-23</td><td class=\"w3-right-align\">51 km</td></tr><tr><td> 5 </td><td>2016-02-07</td><td class=\"w3-right-align\">30 km</td></tr><tr><td> 6 </td><td>2016-01-01</td><td class=\"w3-right-align\">26 km</td></tr><tr><td> 7 </td><td>2016-02-09</td><td class=\"w3-right-align\">24 km</td></tr><tr><td> 8 </td><td>2016-02-02</td><td class=\"w3-right-align\">21 km</td></tr><tr><td> 9 </td><td>2016-01-17</td><td class=\"w3-right-align\">16 km</td></tr><tr><td> 10 </td><td>2016-01-15</td><td class=\"w3-right-align\">15 km</td></tr></table><script type=\"text/javascript\" src=\"https://www.gstatic.com/charts/loader.js\"></script><script type=\"text/javascript\">    google.charts.load('current', {'packages':['corechart']});    google.charts.setOnLoadCallback(drawChart);    function drawChart() {        var data = google.visualization.arrayToDataTable([            ['Date', 'Imperial', 'Metric'],                     [new Date(2016, 01, 01),  1, 1],
        [new Date(2016, 01, 06),  2, 2],
        [new Date(2016, 01, 08),  3, 3],
        [new Date(2016, 01, 10),  4, 4],
        [new Date(2016, 01, 15),  4, 5],
        [new Date(2016, 01, 17),  5, 6],
        [new Date(2016, 01, 28),  6, 7],
        [new Date(2016, 01, 30),  7, 8],
        [new Date(2016, 02, 02),  8, 8],
        [new Date(2016, 02, 07),  9, 9],
        [new Date(2016, 02, 09),  9, 10],
           ]);        var options = {            title: 'Change in Eddington Number over time',            legend: { position: 'bottom' }        };        var chart = new google.visualization.LineChart(document.getElementById('eddington_chart'));        chart.draw(data, options);    }</script><div id=\"eddington_chart\" style=\"width: 900px; height: 500px\"></div>";