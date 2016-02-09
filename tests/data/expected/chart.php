<?php
return "<script type=\"text/javascript\" src=\"https://www.gstatic.com/charts/loader.js\"></script><script type=\"text/javascript\">    google.charts.load('current', {'packages':['corechart']});    google.charts.setOnLoadCallback(drawChart);    function drawChart() {        var data = google.visualization.arrayToDataTable([            ['Date', 'Imperial', 'Metric'],                     [new Date(2016, 01, 01),  1, 1],
        [new Date(2016, 01, 06),  2, 2],
        [new Date(2016, 01, 08),  3, 3],
        [new Date(2016, 01, 10),  4, 4],
        [new Date(2016, 01, 15),  4, 5],
        [new Date(2016, 01, 17),  5, 6],
        [new Date(2016, 01, 28),  6, 7],
        [new Date(2016, 01, 30),  7, 8],
        [new Date(2016, 02, 02),  8, 8],
           ]);        var options = {            title: 'Change in Eddington Number over time',            legend: { position: 'bottom' }        };        var chart = new google.visualization.LineChart(document.getElementById('eddington_chart'));        chart.draw(data, options);    }</script><div id=\"eddington_chart\" style=\"width: 900px; height: 500px\"></div>";