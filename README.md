# EddingtonAndMore

## Synopsis

A standalone PHP web page for cyclists that allows them to calculate their Eddington number, and to copy ride data.  It's live at [mcgalliard.org/eddington](http://mcgalliard.org/eddington/).

## Eddington Number.
It's the largest number, E, such that you have ridden at least E miles on at least E days.  So this  is for rides who like to ride long distances.

## More Detail

The rides are gathered from a user's Strava, Endomondo and/or MyCyclingLog account. It uses the published APIs of [Strava](http://strava.github.io/api/) via [Stuart Wilson](https://github.com/iamstuartwilson/strava), and [MyCyclingLog](https://www.mycyclinglog.com/api/docs.php), and the secret API of endomondo, with help from [sports-tracker-liberator](https://github.com/isoteemu/sports-tracker-liberator).

There's also a TZ pull down from a variant of [moment.js](http://momentjs.com/) *I found on the internet*. &#9786;

 It has some facilities for updating one site with rides recorded on another.  As of 9 January 2016, the only copy is from Strava  to MyCyclingLog.


## Environments
This has only been tested with PHP 5.4 & 5.5 on Unix (Linux and Mac OS X).  It only needs a webserver with PHP, and doesn't require any database.

## Motivation

Inspired by the demise of http://canini.me/eddington/index.php, that did the same thing for Strava only.

## Installation

Copy all code onto your webserver, copy *local_template.php* to *local.php* and edit as appropriate.  However, there is no need to install it unless you need/want your own version.  You can just use my [deployed version](http://mcgalliard.org/eddington/).


## License

GNU standard.