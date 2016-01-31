<?php

namespace JoanMcGalliard;


class MyCyclingLogApi
{

    protected $auth = null;
    const REST_SERVER_BASE_URL = "http://www.mycyclinglog.com/api/restserver.php";
    const WEB_PAGE_BASE_URL = 'http://www.mycyclinglog.com';
    private $session=null;


    /**
     * @param null $auth
     */
    public function setAuth($auth)
    {
        $this->auth = $auth;
    }

    /**
     * @return null
     */
    public function getAuth()
    {
        return $this->auth;
    }

    protected function postPage($url, $parameters)
    {
        $process = curl_init(self::REST_SERVER_BASE_URL . $url);
        $headers = array('Authorization: Basic ' . $this->auth);
        curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($process, CURLOPT_HEADER, 0);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
        curl_setopt($process, CURLOPT_POST, 1);
        curl_setopt($process, CURLOPT_POSTFIELDS, http_build_query($parameters));
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($process);
        curl_close($process);
        log_msg("MCL post URL " . $url);
        log_msg($parameters);
        log_msg($response);

        return $response;
    }
    public function getPage($url)
    {
        $process = curl_init(self::REST_SERVER_BASE_URL . $url);
        $headers = array('Authorization: Basic ' . $this->auth);
        curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($process, CURLOPT_HEADER, 0);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
        $page = curl_exec($process);
        curl_close($process);
        log_msg("MCL get URL " . $url);
        log_msg($page);
        return $page;
    }

    /*
     * This logs into via the web page, and starts a session.  Needed for bulk deletes, see below.
     */
    public function login($username, $password)
    {
        // returns true if logged in, otherwise error message.
        $loginUrl = self::WEB_PAGE_BASE_URL . "/";

        $this->session = curl_init();
        curl_setopt($this->session, CURLOPT_URL, $loginUrl);
        curl_setopt($this->session, CURLOPT_POST, 1);
        curl_setopt($this->session, CURLOPT_POSTFIELDS, 'username=' . $username . '&password=' . $password);
        curl_setopt($this->session, CURLOPT_COOKIEJAR, 'cookie.txt');
        curl_setopt($this->session, CURLOPT_RETURNTRANSFER, 1);
        $store = curl_exec($this->session);
        if (strpos($store, "Logout") === false) {
            $error = curl_error($this->session);
            curl_close($this->session);
            if ($error) {
                return $error;
            }
            return "$error  Check username ($username) and password.";
        }
        return "OK`";
    }

    /*
     * This is for deleting rides, which only availble via the web page, not the API.  You need to login, then
     * delete the rides then logout.
     */
    public function delete($id)     {
        if (!$this->session) {return false;}
        curl_setopt($this->session, CURLOPT_URL, self::WEB_PAGE_BASE_URL."/add.php");
        curl_setopt($this->session, CURLOPT_POSTFIELDS, "r=1&lid=$id");
        curl_exec($this->session);
        return true;
    }

    public function logout () {
        if ($this->session) {
            curl_close($this->session);
        }
    }






}