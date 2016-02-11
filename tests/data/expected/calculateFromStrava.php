<?php return "<p>According to Strava, for the period from the beginning to today, $elapsed_days elapsed days</p>
<br><a href=\"#imperial\">Your imperial Eddington Number</a> is <strong>1</strong>.<br>
You need to do 1 ride(s) of at least 2 to increase it to 2.<br>
You need to do 4 ride(s) of at least 5 to increase it to 5.<br>
You need to do 9 ride(s) of at least 10 to increase it to 10.<br>
You need to do 50 ride(s) of at least 50 to increase it to 50.<br>
You need to do 100 ride(s) of at least 100 to increase it to 100.<br>
<br><a href=\"#metric\">Your metric Eddington Number</a> is <strong>1</strong><br>
You need to do 1 ride(s) of at least 2 to increase it to 2.<br>
You need to do 4 ride(s) of at least 5 to increase it to 5.<br>
You need to do 9 ride(s) of at least 10 to increase it to 10.<br>
You need to do 50 ride(s) of at least 50 to increase it to 50.<br>
You need to do 100 ride(s) of at least 100 to increase it to 100.<br>
<br><a href=\"#eddington_chart\">See a chart of how your Eddington number has grown over the years.</a><br><p><em>Run time 0 seconds.</em></p>
<table id=\"imperial\" class=\"w3-table-all w3-right-align\"  style=\"width:60%\"><tr><th>Count</th><th>Date </th><th class=\"w3-right-align\">Distance</th></tr><tr><td> 1 </td><td> 2016-02-09</td><td class=\"w3-right-align\">14 miles</td></tr></table><table id=\"metric\" class=\"w3-table-all w3-right-align\"  style=\"width:60%\"><tr><th>Count</th><th>Date </th><th class=\"w3-right-align\">Distance</th></tr><tr><td> 1 </td><td>2016-02-09</td><td class=\"w3-right-align\">22 km</td></tr></table><script type=\"text/javascript\" src=\"https://www.gstatic.com/charts/loader.js\"></script><script type=\"text/javascript\">    google.charts.load('current', {'packages':['corechart']});    google.charts.setOnLoadCallback(drawChart);    function drawChart() {        var data = google.visualization.arrayToDataTable([            ['Date', 'Imperial', 'Metric'],                     [new Date(2016, 02, 09),  1, 1],
           ]);        var options = {            title: 'Change in Eddington Number over time',            legend: { position: 'bottom' }        };        var chart = new google.visualization.LineChart(document.getElementById('eddington_chart'));        chart.draw(data, options);    }</script><div id=\"eddington_chart\" style=\"width: 900px; height: 500px\"></div>";