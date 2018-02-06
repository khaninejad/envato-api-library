<?php

  namespace Smafe;

class Envato
{

    /**
    *** Construct authentication details
    **/

    public function __construct($data)
    {
        $this->endpoint = 'https://api.envato.com/';

        if (isset($data['api_id'])) {
            $this->api_id = $data['api_id'];
        }

        if (isset($data['api_secret'])) {
            $this->api_secret = $data['api_secret'];
        }

        if (isset($data['api_redirect'])) {
            $this->api_redirect = $data['api_redirect'];
        }

        if (isset($data['api_token'])) {
            $this->api_token = $data['api_token'];
        }

        if (isset($data['api_refresh_token'])) {
            $this->api_refresh_token = $data['api_refresh_token'];
        }
    }


    /**
    *** Generate Auth URL
    **/

    public function getAuthUrl()
    {
        return $this->endpoint . 'authorization?response_type=code&client_id=' . $this->api_id . '&redirect_uri=' . $this->api_redirect;
    }


    /**
    *** Request function
    **/

    public function request($route, $path = 'GET', $data = false)
    {
        return self::curl($route, $path, $data);
    }


    /**
    *** Look for valid token (Is user logged in)
    **/

    public function validToken()
    {
        return self::curl('v1/market/private/user/email.json', 'GET');
    }


    /**
    *** Set access token
    **/

    public function setAccessToken($token)
    {
        $this->user_token = $token;
    }


    /**
    *** Code to Token
    **/

    public function getAccessToken($code, $type = 'AUTH', $exceptions = true)
    {
        $ch = curl_init($this->endpoint . 'token');

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if ($type == 'AUTH') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
          'client_id' => $this->api_id,
          'redirect_uri' => $this->api_redirect,
          'client_secret' => $this->api_secret,
          'code' => $code,
          'grant_type' => 'authorization_code'
        )));
        } elseif ($type == 'REFRESH') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
          'client_id' => $this->api_id,
          'client_secret' => $this->api_secret,
          'refresh_token' => $code,
          'grant_type' => 'refresh_token'
        )));
        }

        $token = json_decode(curl_exec($ch));

        // Throw Exception if error exist
        if (isset($token->error) and $exceptions) {
            throw new \ErrorException((isset($token->error_description)) ? $token->error_description : $token->error);
        }

        // Return empty result if error and $exceptions disabled
        if (isset($token->error) and !$exceptions) {
            return false;
        }

        // Save token if function exists
        if (function_exists('saveAccessToken')) {
            saveAccessToken($token->access_token);
        }

        // Set current access token in the library
        self::setAccessToken($token->access_token);

        return $token;
    }


    /**
    *** Request code to token
    **/

    public function renewToken()
    {
        if (is_array($this->api_refresh_token)) {
            $token = $this->getAccessToken(current($this->api_refresh_token), 'REFRESH', false);

            if (!isset($token->access_token) and next($this->api_refresh_token)) {
                return self::renewToken();
            }

            if (!isset($token->access_token) and !next($this->api_refresh_token)) {
                return false;
            }

            return true;
        } elseif ($this->api_refresh_token) {
            $token = $this->getAccessToken($this->api_refresh_token, 'REFRESH');

            if (!$token->access_token) {
                return false;
            }

            return true;
        } else {
            return false;
        }
    }


    /**
    *** Get current live token
    **/

    private function getToken()
    {
        if (isset($this->user_token)) {
            return $this->user_token;
        }

        if (isset($this->api_token)) {
            return $this->api_token;
        }

        return false;
    }


    /**
    *** Submit requests using curl
    **/

    private function curl($endpoint, $method = 'GET', $data = false, $retry = false)
    {
        if ($method == 'GET' and is_array($data)) {
            $filter = '?' . http_build_query($data);
        }

        $sort = (isset($filter)) ? $filter : '';

        $curl = curl_init($this->endpoint . $endpoint . $sort);

        $data_j = json_encode($data);

        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' , 'Authorization: Bearer ' . self::getToken() ));

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data_j);
        }

        if ($method == 'PUT') {
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data_j);
        }

        $result = json_decode(curl_exec($curl));

        if (curl_error($curl)) {
            throw new \ErrorException(curl_error($curl));
        }

        curl_close($curl);

        // Error & Refresh token? Try again
        if ((isset($result->error) && isset($this->api_refresh_token) && !$retry) or isset($result->message)) {
            $refresh = self::renewToken();

            if ($refresh) {
                return self::curl($endpoint, $method, $data, true);
            }
        }

        // Throw Exception if error exist
        if (isset($result->error) and isset($result->error_description)) {
            throw new \ErrorException($result->error_description);
        } elseif (isset($result->error) and isset($result->description)) {
            throw new \ErrorException($result->description);
        } elseif (isset($result->error)) {
            throw new \ErrorException($result->error);
        } elseif (isset($result->message) and $result->message === 'Unauthorized') {
            throw new \ErrorException($result->message);
        }

        // Return JSON decoded result
        return $result;
    }
}
