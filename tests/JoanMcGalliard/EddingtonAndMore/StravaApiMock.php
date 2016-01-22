<?php

/**
 * A mock version of Iamstuartwilson\StravaApi
 */
class StravaApiMock
{


    public $lastRequest;
    public $lastRequestData;
    public $lastRequestInfo;


    /**
     * Sets up the class with the $clientId and $clientSecret
     *
     * @param int $clientId
     * @param string $clientSecret
     */
    public function __construct($clientId = 1, $clientSecret = '')
    {
    }

    /**
     * Creates authentication URL for your app
     *
     * @param string $redirect
     * @param string $approvalPrompt
     * @param string $scope
     * @param string $state
     *
     * @link http://strava.github.io/api/v3/oauth/#get-authorize
     *
     * @return string
     */
    public function authenticationUrl($redirect, $approvalPrompt = 'auto', $scope = null, $state = null)
    {
        throw new Exception('authenticationUrl: Not implemented');
    }

    /**
     * Authenticates token returned from API
     *
     * @param string $code
     *
     * @link http://strava.github.io/api/v3/oauth/#post-token
     *
     * @return string
     */
    public function tokenExchange($code)
    {
        return json_decode('{
    "access_token": "6a7dfb37cf8ff4eb5a63a1cb38f4b6aa38410e8b",
    "token_type": "Bearer",
    "athlete": {
        "id": 999999,
        "username": "grunt",
        "resource_state": 3,
        "firstname": "Joan",
        "lastname": "M",
        "profile_medium": "avatar/athlete/medium.png",
        "profile": "avatar/athlete/large.png",
        "city": "Twickenham",
        "state": "England",
        "country": "United Kingdom",
        "sex": "F",
        "friend": null,
        "follower": null,
        "premium": false,
        "created_at": "2012-05-30T16:07:02Z",
        "updated_at": "2016-01-14T21:47:48Z",
        "badge_type_id": 0,
        "follower_count": 10,
        "friend_count": 17,
        "mutual_friend_count": 0,
        "athlete_type": 0,
        "date_preference": "%d/%m/%Y",
        "measurement_preference": "meters",
        "email": "strava@example.com",
        "ftp": null,
        "weight": 60.0,
        "clubs": [
            {
                "id": 11111,
                "resource_state": 2,
                "name": "club1",
                "profile_medium": "avatar/club/medium.png",
                "profile": "avatar/club/large.png"
            },
            {
                "id": 2222,
                "resource_state": 2,
                "name": "club2",
                "profile_medium": "avatar/club/medium.png",
                "profile": "avatar/club/large.png"
            }
        ],
        "bikes": [
            {
                "id": "b111111",
                "primary": true,
                "name": "Avail 2",
                "resource_state": 2,
                "distance": 24811670.0
            },
            {
                "id": "b222222",
                "primary": false,
                "name": "Mezzo",
                "resource_state": 2,
                "distance": 16777003.0
            }
        ],
        "shoes": []
    }
}');
    }

    /**
     * Deauthorises application
     *
     * @link http://strava.github.io/api/v3/oauth/#deauthorize
     *
     * @return string
     */
    public function deauthorize()
    {
        throw new Exception('deauthorize: Not implemented');
    }

    /**
     * Sets the access token used to authenticate API requests
     *
     * @param string $token
     */
    public function setAccessToken($token)
    {
        throw new Exception('setAccessToken: Not implemented');
    }

    /**
     * Sends GET request to specified API endpoint
     *
     * @param string $request
     * @param array $parameters
     *
     * @example http://strava.github.io/api/v3/athlete/#koms
     *
     * @return string
     */
    public function get($request, $parameters = array())
    {
        if ($request == 'activities') {
            $json = '[
    {
        "id": 470171383,
        "resource_state": 2,
        "external_id": "20160108_132000.gpx",
        "upload_id": 521449223,
        "athlete": {
            "id": 573245,
            "resource_state": 1
        },
        "name": "Afternoon Ride",
        "distance": 2975.5,
        "moving_time": 599,
        "elapsed_time": 599,
        "total_elevation_gain": 0.0,
        "type": "Ride",
        "start_date": "2016-01-08T13:20:00Z",
        "start_date_local": "2016-01-08T13:20:00Z",
        "timezone": "(GMT+00:00) Europe/London",
        "start_latlng": [
            51.46,
            -0.32
        ],
        "end_latlng": [
            51.46,
            -0.3
        ],
        "location_city": "Twickenham",
        "location_state": "England",
        "location_country": "United Kingdom",
        "start_latitude": 51.46,
        "start_longitude": -0.32,
        "achievement_count": 0,
        "kudos_count": 0,
        "comment_count": 0,
        "athlete_count": 1,
        "photo_count": 0,
        "map": {
            "id": "a470171383",
            "summary_polyline": "iu`yHbj|@_FqVqOs\\\\iItBwGmO@sE",
            "resource_state": 2
        },
        "trainer": false,
        "commute": false,
        "manual": false,
        "private": false,
        "flagged": false,
        "gear_id": "b267883",
        "average_speed": 4.967,
        "max_speed": 5.5,
        "average_watts": 48.7,
        "kilojoules": 29.2,
        "device_watts": false,
        "elev_high": 16.0,
        "elev_low": 8.7,
        "total_photo_count": 0,
        "has_kudoed": false,
        "workout_type": null
    },
    {
        "id": 470166379,
        "resource_state": 2,
        "external_id": "20160108_203000 (2).gpx",
        "upload_id": 521444288,
        "athlete": {
            "id": 573245,
            "resource_state": 1
        },
        "name": "Evening Ride",
        "distance": 2919.0,
        "moving_time": 600,
        "elapsed_time": 600,
        "total_elevation_gain": 0.0,
        "type": "Ride",
        "start_date": "2016-01-08T20:30:00Z",
        "start_date_local": "2016-01-08T20:30:00Z",
        "timezone": "(GMT+00:00) Europe/London",
        "start_latlng": [
            51.46,
            -0.3
        ],
        "end_latlng": [
            51.46,
            -0.32
        ],
        "location_city": "Hounslow",
        "location_state": "England",
        "location_country": "United Kingdom",
        "start_latitude": 51.46,
        "start_longitude": -0.3,
        "achievement_count": 1,
        "kudos_count": 0,
        "comment_count": 0,
        "athlete_count": 1,
        "photo_count": 0,
        "map": {
            "id": "a470166379",
            "summary_polyline": "y_byHrcz@zEwCrBtYlGsCpOr\\\\~EtV",
            "resource_state": 2
        },
        "trainer": false,
        "commute": false,
        "manual": false,
        "private": false,
        "flagged": false,
        "gear_id": "b267883",
        "average_speed": 4.865,
        "max_speed": 5.2,
        "average_watts": 36.5,
        "kilojoules": 21.9,
        "device_watts": false,
        "elev_high": 19.7,
        "elev_low": 8.7,
        "total_photo_count": 0,
        "has_kudoed": false,
        "workout_type": null
    },
    {
        "id": 467826933,
        "resource_state": 2,
        "external_id": "20160110_103000.gpx",
        "upload_id": 519095266,
        "athlete": {
            "id": 573245,
            "resource_state": 1
        },
        "name": "Morning Ride",
        "distance": 7123.0,
        "moving_time": 2159,
        "elapsed_time": 2159,
        "total_elevation_gain": 0.0,
        "type": "Ride",
        "start_date": "2016-01-10T10:30:00Z",
        "start_date_local": "2016-01-10T10:30:00Z",
        "timezone": "(GMT+00:00) Europe/London",
        "start_latlng": [
            51.46,
            -0.33
        ],
        "end_latlng": [
            51.41,
            -0.31
        ],
        "location_city": "Twickenham",
        "location_state": "England",
        "location_country": "United Kingdom",
        "start_latitude": 51.46,
        "start_longitude": -0.33,
        "achievement_count": 0,
        "kudos_count": 0,
        "comment_count": 0,
        "athlete_count": 1,
        "photo_count": 0,
        "map": {
            "id": "a467826933",
            "summary_polyline": "{g`yH~n_AfHbHbKqLvMqGhErInRlHhFpF|Iz@nX}PdLgAfPsQhBkHjIqIfNiAznAqhA~IgAdAwY|F_@",
            "resource_state": 2
        },
        "trainer": false,
        "commute": false,
        "manual": false,
        "private": false,
        "flagged": false,
        "gear_id": "b267883",
        "average_speed": 3.299,
        "max_speed": 3.6,
        "average_watts": 22.9,
        "kilojoules": 49.4,
        "device_watts": false,
        "elev_high": 13.8,
        "elev_low": 8.0,
        "total_photo_count": 0,
        "has_kudoed": false,
        "workout_type": null
    },
    {
        "id": 467826951,
        "resource_state": 2,
        "external_id": "20160110_111200.gpx",
        "upload_id": 519095265,
        "athlete": {
            "id": 573245,
            "resource_state": 1
        },
        "name": "Lunch Ride",
        "distance": 35490.9,
        "moving_time": 6633,
        "elapsed_time": 10935,
        "total_elevation_gain": 40.1,
        "type": "Ride",
        "start_date": "2016-01-10T11:12:00Z",
        "start_date_local": "2016-01-10T11:12:00Z",
        "timezone": "(GMT+00:00) Europe/London",
        "start_latlng": [
            51.41,
            -0.31
        ],
        "end_latlng": [
            51.41,
            -0.31
        ],
        "location_city": null,
        "location_state": null,
        "location_country": "United Kingdom",
        "start_latitude": 51.41,
        "start_longitude": -0.31,
        "achievement_count": 7,
        "kudos_count": 0,
        "comment_count": 0,
        "athlete_count": 1,
        "photo_count": 0,
        "map": {
            "id": "a467826951",
            "summary_polyline": "kbxxHn|z@kBSaArZ`VxeApFxnAcBf]pIpFWzDqVxj@}Rzu@eEbd@^hXxCxC~Knc@|@jg@uAzb@lAfj@vPvw@zDvXzOpd@rM|RlAdFzR~RdG~MrApOtJhb@pJ|X|Izd@nBha@uHnWaB`T^xMlGnb@fB~b@{DvdAlKfg@yBj]yIjd@JtEnCfGwFtSP~CaBhBlC`EzAoC]eEjBbFbDf@fBjOe@fCxDtFdGsVfNq_@`Eu^vIcBzFcGb@oVvAmHlZ}j@~JyVpFug@rBkq@{CwWaEoNlAqFrGyI|AqHi@c\\\\gL{p@c^i\\\\}JeOy_@mZ{@cMrBwO_CaLy[sg@cd@kcAya@wj@oE_NyFa_@uA_m@}Cs_@uHmV_A{a@xBmt@pPqgAdGoPwVoP`AeRuLqBeG|EsEwIc|@iNdDya@vQ}q@lOyLxDaPlN}O{@wEv@_\\\\",
            "resource_state": 2
        },
        "trainer": false,
        "commute": false,
        "manual": false,
        "private": false,
        "flagged": false,
        "gear_id": "b267883",
        "average_speed": 5.351,
        "max_speed": 21.6,
        "average_watts": 55.2,
        "kilojoules": 366.2,
        "device_watts": false,
        "elev_high": 35.5,
        "elev_low": 7.7,
        "total_photo_count": 0,
        "has_kudoed": false,
        "workout_type": null
    },
    {
        "id": 467826934,
        "resource_state": 2,
        "external_id": "20160110_144526.gpx",
        "upload_id": 519095264,
        "athlete": {
            "id": 573245,
            "resource_state": 1
        },
        "name": "Afternoon Ride",
        "distance": 8259.8,
        "moving_time": 1649,
        "elapsed_time": 7623,
        "total_elevation_gain": 0.0,
        "type": "Ride",
        "start_date": "2016-01-10T14:45:26Z",
        "start_date_local": "2016-01-10T14:45:26Z",
        "timezone": "(GMT+00:00) Europe/London",
        "start_latlng": [
            51.41,
            -0.31
        ],
        "end_latlng": [
            51.46,
            -0.32
        ],
        "location_city": null,
        "location_state": null,
        "location_country": "United Kingdom",
        "start_latitude": 51.41,
        "start_longitude": -0.31,
        "achievement_count": 1,
        "kudos_count": 0,
        "comment_count": 0,
        "athlete_count": 1,
        "photo_count": 0,
        "map": {
            "id": "a467826934",
            "summary_polyline": "cgxxHn{z@V_DuHiCs@iIkIkOuc@fG}PjF}[nTePbAsKkAwVuK_l@uKwMzAaBiYgJG}GbCwPrNmWjIsHhI`Svg@jC|T",
            "resource_state": 2
        },
        "trainer": false,
        "commute": false,
        "manual": false,
        "private": false,
        "flagged": false,
        "gear_id": "b267883",
        "average_speed": 5.009,
        "max_speed": 8.3,
        "average_watts": 58.6,
        "kilojoules": 96.6,
        "device_watts": false,
        "elev_high": 20.9,
        "elev_low": 8.0,
        "total_photo_count": 0,
        "has_kudoed": false,
        "workout_type": null
    },
    {
        "id": 467826945,
        "resource_state": 2,
        "external_id": "20160110_172526.gpx",
        "upload_id": 519095262,
        "athlete": {
            "id": 573245,
            "resource_state": 1
        },
        "name": "Afternoon Ride",
        "distance": 23393.0,
        "moving_time": 5007,
        "elapsed_time": 6177,
        "total_elevation_gain": 61.3,
        "type": "Ride",
        "start_date": "2016-01-10T17:25:26Z",
        "start_date_local": "2016-01-10T17:25:26Z",
        "timezone": "(GMT+00:00) Europe/London",
        "start_latlng": [
            51.46,
            -0.32
        ],
        "end_latlng": [
            51.57,
            -0.11
        ],
        "location_city": "Twickenham",
        "location_state": "England",
        "location_country": "United Kingdom",
        "start_latitude": 51.46,
        "start_longitude": -0.32,
        "achievement_count": 1,
        "kudos_count": 1,
        "comment_count": 0,
        "athlete_count": 1,
        "photo_count": 0,
        "map": {
            "id": "a467826945",
            "summary_polyline": "eucyH|i~@qAs@iH_]kE@aD_HyHgHeWaPwFi@sFrIyDkMyG{p@gNgh@wLsl@_BsQiGoN`@ea@oA}Lm@ic@kEuq@wCmFwAgfAkBy]_@ud@jCya@mHeDQmm@cUhEe@wFwFyCNmSyCq@}KuY`AeKmH{SiKpBsSenAgGou@sBau@gEoh@sF{BaCtBoCyH_BaBgArBnAoDsOc^{KyP_HkCeJ}w@sHmcA{e@xC_]`LmAqDaOoOyO{^yOgP_a@qn@wYs^qZ}X}Uyh@eRsp@mA_AaNnScNzZ",
            "resource_state": 2
        },
        "trainer": false,
        "commute": false,
        "manual": false,
        "private": false,
        "flagged": false,
        "gear_id": "b267883",
        "average_speed": 4.672,
        "max_speed": 10.7,
        "average_watts": 58.8,
        "kilojoules": 294.3,
        "device_watts": false,
        "elev_high": 51.7,
        "elev_low": 6.0,
        "total_photo_count": 0,
        "has_kudoed": false,
        "workout_type": null
    },
    {
        "id": 467826942,
        "resource_state": 2,
        "external_id": "20160110_210745.gpx",
        "upload_id": 519095261,
        "athlete": {
            "id": 573245,
            "resource_state": 1
        },
        "name": "Night Ride",
        "distance": 24404.0,
        "moving_time": 5127,
        "elapsed_time": 6288,
        "total_elevation_gain": 37.6,
        "type": "Ride",
        "start_date": "2016-01-10T21:07:45Z",
        "start_date_local": "2016-01-10T21:07:45Z",
        "timezone": "(GMT+00:00) Europe/London",
        "start_latlng": [
            51.57,
            -0.11
        ],
        "end_latlng": [
            51.46,
            -0.32
        ],
        "location_city": null,
        "location_state": null,
        "location_country": "United Kingdom",
        "start_latitude": 51.57,
        "start_longitude": -0.11,
        "achievement_count": 2,
        "kudos_count": 1,
        "comment_count": 0,
        "athlete_count": 1,
        "photo_count": 0,
        "map": {
            "id": "a467826942",
            "summary_polyline": "}cwyHt`UrFmQnK{O|@mG`Jq@bEnJfEz@bPfShFx@~Nhg@~t@bAv_AwHrKbBbOhUbIjAO~Kn^lnA`A|K_@pLxBxBnBpQvClBhNgGrCa@b@fBzGrs@{K|LHjJjI`q@gCxHhc@f{@~CfC|AsBzEvGnE~p@|Ahl@jHpl@?bKjRdiAfK{@xH~SgAlJtIzUbF~CGrTlBUdCrCf@rFbDfDv@xFrYHeDpkA_Ao@~@~@fEfiBc@pn@lI`OfB|u@lApJ]hb@nGrMlIll@lShy@vHns@xD`MxEmHlEAzYfQ`NtOhDVdDpOnBtBfAtJ`Fd@",
            "resource_state": 2
        },
        "trainer": false,
        "commute": false,
        "manual": false,
        "private": false,
        "flagged": false,
        "gear_id": "b267883",
        "average_speed": 4.76,
        "max_speed": 9.7,
        "average_watts": 53.1,
        "kilojoules": 272.1,
        "device_watts": false,
        "elev_high": 49.8,
        "elev_low": 6.0,
        "total_photo_count": 0,
        "has_kudoed": false,
        "workout_type": null
    },
    {
        "id": 470202925,
        "resource_state": 2,
        "external_id": "656519664.gpx",
        "upload_id": 521480824,
        "athlete": {
            "id": 573245,
            "resource_state": 1
        },
        "name": "My Ride!",
        "distance": 958.7,
        "moving_time": 264,
        "elapsed_time": 2955,
        "total_elevation_gain": 0.0,
        "type": "Ride",
        "start_date": "2016-01-13T15:01:55Z",
        "start_date_local": "2016-01-13T15:01:55Z",
        "timezone": "(GMT+00:00) Europe/London",
        "start_latlng": [
            51.47,
            -0.33
        ],
        "end_latlng": [
            51.46,
            -0.32
        ],
        "location_city": "Twickenham",
        "location_state": "England",
        "location_country": "United Kingdom",
        "start_latitude": 51.47,
        "start_longitude": -0.33,
        "achievement_count": 0,
        "kudos_count": 0,
        "comment_count": 0,
        "athlete_count": 1,
        "photo_count": 0,
        "map": {
            "id": "a470202925",
            "summary_polyline": null,
            "resource_state": 2
        },
        "trainer": false,
        "commute": false,
        "manual": false,
        "private": false,
        "flagged": false,
        "gear_id": "b267883",
        "average_speed": 3.631,
        "max_speed": 6.4,
        "average_watts": 24.6,
        "kilojoules": 6.5,
        "device_watts": false,
        "elev_high": 11.1,
        "elev_low": 9.0,
        "total_photo_count": 0,
        "has_kudoed": false,
        "workout_type": null
    },
    {
        "id": 470202912,
        "resource_state": 2,
        "external_id": "656716916.gpx",
        "upload_id": 521480809,
        "athlete": {
            "id": 573245,
            "resource_state": 1
        },
        "name": "Night Ride",
        "distance": 3432.7,
        "moving_time": 1041,
        "elapsed_time": 6177,
        "total_elevation_gain": 0.0,
        "type": "Ride",
        "start_date": "2016-01-13T21:35:46Z",
        "start_date_local": "2016-01-13T21:35:46Z",
        "timezone": "(GMT+00:00) Europe/London",
        "start_latlng": [
            51.46,
            -0.32
        ],
        "end_latlng": [
            51.46,
            -0.32
        ],
        "location_city": "Twickenham",
        "location_state": "England",
        "location_country": "United Kingdom",
        "start_latitude": 51.46,
        "start_longitude": -0.32,
        "achievement_count": 0,
        "kudos_count": 0,
        "comment_count": 0,
        "athlete_count": 1,
        "photo_count": 0,
        "map": {
            "id": "a470202912",
            "summary_polyline": "quayHdaaArEgBaArCp@u@cED",
            "resource_state": 2
        },
        "trainer": false,
        "commute": false,
        "manual": false,
        "private": false,
        "flagged": false,
        "gear_id": "b267883",
        "average_speed": 3.298,
        "max_speed": 6.4,
        "average_watts": 30.2,
        "kilojoules": 31.5,
        "device_watts": false,
        "elev_high": 15.3,
        "elev_low": 9.0,
        "total_photo_count": 0,
        "has_kudoed": false,
        "workout_type": null
    },
    {
        "id": 470962326,
        "resource_state": 2,
        "external_id": "657505835.gpx",
        "upload_id": 522245474,
        "athlete": {
            "id": 573245,
            "resource_state": 1
        },
        "name": "Afternoon Ride",
        "distance": 5972.1,
        "moving_time": 1308,
        "elapsed_time": 1838,
        "total_elevation_gain": 0.0,
        "type": "Ride",
        "start_date": "2016-01-15T15:25:18Z",
        "start_date_local": "2016-01-15T15:25:18Z",
        "timezone": "(GMT+00:00) Europe/London",
        "start_latlng": [
            51.46,
            -0.32
        ],
        "end_latlng": [
            51.48,
            -0.28
        ],
        "location_city": "Twickenham",
        "location_state": "England",
        "location_country": "United Kingdom",
        "start_latitude": 51.46,
        "start_longitude": -0.32,
        "achievement_count": 0,
        "kudos_count": 0,
        "comment_count": 0,
        "athlete_count": 1,
        "photo_count": 0,
        "map": {
            "id": "a470962326",
            "summary_polyline": "mncyHtj~@}Fg@cA_K{DwFiAoJiDKePuRsY}OeDj@mEjHkD{H}AqSmCgJgAgSeIo_@}DyJkOi}@vByBzQa@zA_Q",
            "resource_state": 2
        },
        "trainer": false,
        "commute": false,
        "manual": false,
        "private": false,
        "flagged": false,
        "gear_id": "b267883",
        "average_speed": 4.566,
        "max_speed": 8.5,
        "average_watts": 45.4,
        "kilojoules": 59.4,
        "device_watts": false,
        "elev_high": 12.7,
        "elev_low": 5.0,
        "total_photo_count": 0,
        "has_kudoed": false,
        "workout_type": null
    },
    {
        "id": 470962293,
        "resource_state": 2,
        "external_id": "657480811.gpx",
        "upload_id": 522245441,
        "athlete": {
            "id": 573245,
            "resource_state": 1
        },
        "name": "Afternoon Ride",
        "distance": 5156.7,
        "moving_time": 1169,
        "elapsed_time": 1409,
        "total_elevation_gain": 0.0,
        "type": "Ride",
        "start_date": "2016-01-15T17:32:50Z",
        "start_date_local": "2016-01-15T17:32:50Z",
        "timezone": "(GMT+00:00) Europe/London",
        "start_latlng": [
            51.48,
            -0.29
        ],
        "end_latlng": [
            51.46,
            -0.32
        ],
        "location_city": null,
        "location_state": null,
        "location_country": "United Kingdom",
        "start_latitude": 51.48,
        "start_longitude": -0.29,
        "achievement_count": 0,
        "kudos_count": 0,
        "comment_count": 0,
        "athlete_count": 1,
        "photo_count": 0,
        "map": {
            "id": "a470962293",
            "summary_polyline": "{sfyHltv@_AzLsRjA}@f@?pDzNdy@bN~g@vJhy@zAlEtF{IpDX~VzNvQ|RbDPjIp^bGx@",
            "resource_state": 2
        },
        "trainer": false,
        "commute": false,
        "manual": false,
        "private": false,
        "flagged": false,
        "gear_id": "b267883",
        "average_speed": 4.411,
        "max_speed": 8.7,
        "average_watts": 48.7,
        "kilojoules": 57.0,
        "device_watts": false,
        "elev_high": 12.8,
        "elev_low": 5.2,
        "total_photo_count": 0,
        "has_kudoed": false,
        "workout_type": null
    },
    {
        "id": 472544270,
        "resource_state": 2,
        "external_id": "657554078.gpx",
        "upload_id": 523861305,
        "athlete": {
            "id": 573245,
            "resource_state": 1
        },
        "name": "Night Ride",
        "distance": 3385.1,
        "moving_time": 836,
        "elapsed_time": 2832,
        "total_elevation_gain": 0.0,
        "type": "Ride",
        "start_date": "2016-01-15T21:28:21Z",
        "start_date_local": "2016-01-15T21:28:21Z",
        "timezone": "(GMT+00:00) Europe/London",
        "start_latlng": [
            51.46,
            -0.32
        ],
        "end_latlng": [
            51.46,
            -0.32
        ],
        "location_city": "Twickenham",
        "location_state": "England",
        "location_country": "United Kingdom",
        "start_latitude": 51.46,
        "start_longitude": -0.32,
        "achievement_count": 0,
        "kudos_count": 0,
        "comment_count": 0,
        "athlete_count": 1,
        "photo_count": 0,
        "map": {
            "id": "a472544270",
            "summary_polyline": "quayHhaaAdF}A_FrB",
            "resource_state": 2
        },
        "trainer": false,
        "commute": false,
        "manual": false,
        "private": false,
        "flagged": false,
        "gear_id": "b267883",
        "average_speed": 4.049,
        "max_speed": 7.2,
        "average_watts": 39.2,
        "kilojoules": 32.8,
        "device_watts": false,
        "elev_high": 15.3,
        "elev_low": 9.0,
        "total_photo_count": 0,
        "has_kudoed": false,
        "workout_type": null
    },
    {
        "id": 472544225,
        "resource_state": 2,
        "external_id": "658323900.gpx",
        "upload_id": 523861265,
        "athlete": {
            "id": 573245,
            "resource_state": 1
        },
        "name": "Lunch Ride",
        "distance": 7899.7,
        "moving_time": 1772,
        "elapsed_time": 2578,
        "total_elevation_gain": 0.0,
        "type": "Ride",
        "start_date": "2016-01-17T11:55:21Z",
        "start_date_local": "2016-01-17T11:55:21Z",
        "timezone": "(GMT+00:00) Europe/London",
        "start_latlng": [
            51.46,
            -0.32
        ],
        "end_latlng": [
            51.41,
            -0.3
        ],
        "location_city": "Twickenham",
        "location_state": "England",
        "location_country": "United Kingdom",
        "start_latitude": 51.46,
        "start_longitude": -0.32,
        "achievement_count": 0,
        "kudos_count": 0,
        "comment_count": 0,
        "athlete_count": 1,
        "photo_count": 0,
        "map": {
            "id": "a472544225",
            "summary_polyline": "ii`yH`f}@nF`@zWhu@jG~EjBrEdRbInEdF~K|@rW}O`MsBf]ud@vM}@hoA_iArHs@dBsc@vK}@e@oGqHeK",
            "resource_state": 2
        },
        "trainer": false,
        "commute": false,
        "manual": false,
        "private": false,
        "flagged": false,
        "gear_id": "b267883",
        "average_speed": 4.458,
        "max_speed": 7.4,
        "average_watts": 50.3,
        "kilojoules": 89.2,
        "device_watts": false,
        "elev_high": 16.7,
        "elev_low": 7.7,
        "total_photo_count": 0,
        "has_kudoed": false,
        "workout_type": null
    },
    {
        "id": 472544111,
        "resource_state": 2,
        "external_id": "658500404.gpx",
        "upload_id": 523861150,
        "athlete": {
            "id": 573245,
            "resource_state": 1
        },
        "name": "Afternoon Ride",
        "distance": 7687.8,
        "moving_time": 1526,
        "elapsed_time": 5337,
        "total_elevation_gain": 0.0,
        "type": "Ride",
        "start_date": "2016-01-17T15:57:08Z",
        "start_date_local": "2016-01-17T15:57:08Z",
        "timezone": "(GMT+00:00) Europe/London",
        "start_latlng": [
            51.41,
            -0.31
        ],
        "end_latlng": [
            51.46,
            -0.32
        ],
        "location_city": null,
        "location_state": null,
        "location_country": "United Kingdom",
        "start_latitude": 51.41,
        "start_longitude": -0.31,
        "achievement_count": 0,
        "kudos_count": 0,
        "comment_count": 0,
        "athlete_count": 1,
        "photo_count": 0,
        "map": {
            "id": "a472544111",
            "summary_polyline": "ifxxHp~z@q@nWkFMisAlkAqLb@e_@pf@iJj@mYtQ}JkAmFoF}RaIcEiHcPjJ}FhJsJyI",
            "resource_state": 2
        },
        "trainer": false,
        "commute": false,
        "manual": false,
        "private": false,
        "flagged": false,
        "gear_id": "b267883",
        "average_speed": 5.038,
        "max_speed": 7.9,
        "average_watts": 51.1,
        "kilojoules": 78.0,
        "device_watts": false,
        "elev_high": 13.7,
        "elev_low": 8.1,
        "total_photo_count": 0,
        "has_kudoed": false,
        "workout_type": null
    },
    {
        "id": 475130612,
        "resource_state": 2,
        "external_id": "808e6c6d27811ed04393a8b4fbf8a62f",
        "upload_id": 526459210,
        "athlete": {
            "id": 573245,
            "resource_state": 1
        },
        "name": "Lunch Ride",
        "distance": 3513.7,
        "moving_time": 978,
        "elapsed_time": 2969,
        "total_elevation_gain": 0.0,
        "type": "Ride",
        "start_date": "2016-01-21T12:49:32Z",
        "start_date_local": "2016-01-21T12:49:32Z",
        "timezone": "(GMT+00:00) Europe/London",
        "start_latlng": [
            51.46,
            -0.32
        ],
        "end_latlng": [
            51.46,
            -0.3
        ],
        "location_city": "Twickenham",
        "location_state": "England",
        "location_country": "United Kingdom",
        "start_latitude": 51.46,
        "start_longitude": -0.32,
        "achievement_count": 0,
        "kudos_count": 0,
        "comment_count": 0,
        "athlete_count": 1,
        "photo_count": 0,
        "map": {
            "id": "a475130612",
            "summary_polyline": "it`yHhs|@cC}VeIeTiIoPyI`CqGqOOkD@lCEgFNzB",
            "resource_state": 2
        },
        "trainer": false,
        "commute": false,
        "manual": false,
        "private": false,
        "flagged": false,
        "gear_id": "b267883",
        "average_speed": 3.593,
        "max_speed": 9.6,
        "average_watts": 60.1,
        "kilojoules": 58.8,
        "device_watts": false,
        "elev_high": 16.4,
        "elev_low": 8.1,
        "total_photo_count": 0,
        "has_kudoed": false,
        "workout_type": 10
    },
    {
        "id": 475386747,
        "resource_state": 2,
        "external_id": "ff27dad377d6ecd0d0308417767b2f3a",
        "upload_id": 526713047,
        "athlete": {
            "id": 573245,
            "resource_state": 1
        },
        "name": "Evening Ride",
        "distance": 2965.9,
        "moving_time": 1007,
        "elapsed_time": 2905,
        "total_elevation_gain": 0.0,
        "type": "Ride",
        "start_date": "2016-01-21T19:38:35Z",
        "start_date_local": "2016-01-21T19:38:35Z",
        "timezone": "(GMT+00:00) Europe/London",
        "start_latlng": [
            51.46,
            -0.3
        ],
        "end_latlng": [
            51.46,
            -0.32
        ],
        "location_city": "Hounslow",
        "location_state": "England",
        "location_country": "United Kingdom",
        "start_latitude": 51.46,
        "start_longitude": -0.3,
        "achievement_count": 0,
        "kudos_count": 0,
        "comment_count": 0,
        "athlete_count": 1,
        "photo_count": 0,
        "map": {
            "id": "a475386747",
            "summary_polyline": "}_byHhaz@eEv@cAlEaCcBcBtP^xDiAtAzJrZ",
            "resource_state": 2
        },
        "trainer": false,
        "commute": false,
        "manual": false,
        "private": false,
        "flagged": false,
        "gear_id": "b267883",
        "average_speed": 2.945,
        "max_speed": 7.1,
        "average_watts": 39.6,
        "kilojoules": 39.9,
        "device_watts": false,
        "elev_high": 16.0,
        "elev_low": 7.0,
        "total_photo_count": 0,
        "has_kudoed": false,
        "workout_type": 10
    }
]';
            return json_decode($json);
        } else {

            throw new Exception("get $request: Not implemented");
        }

    }

    /**
     * Sends PUT request to specified API endpoint
     *
     * @param string $request
     * @param array $parameters
     *
     * @example http://strava.github.io/api/v3/athlete/#update
     *
     * @return string
     */
    public function put($request, $parameters = array())
    {
        throw new Exception("put $request: Not implemented");
    }

    /**
     * Sends POST request to specified API endpoint
     *
     * @param string $request
     * @param array $parameters
     *
     * @example http://strava.github.io/api/v3/activities/#create
     *
     * @return string
     */
    public function post($request, $parameters = array())
    {
        throw new Exception("post $request: Not implemented");
    }

    /**
     * Sends DELETE request to specified API endpoint
     *
     * @param string $request
     * @param array $parameters
     *
     * @example http://strava.github.io/api/v3/activities/#delete
     *
     * @return string
     */
    public function delete($request, $parameters = array())
    {
        throw new Exception("delete $request: Not implemented");
    }
    /**
     * Adds access token to paramters sent to API
     *
     * @param  array $parameters
     *
     * @return array
     */
}
