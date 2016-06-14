<?php

  namespace Smafe;

  class Envato {

    /**
    *** Construct authentication details
    **/

    public function __construct( $data ) {

      $this->endpoint = 'https://api.envato.com/';

      if( isset( $data['api_id'] ) )
        $this->api_id = $data['api_id'];

      if( isset( $data['api_secret'] ) )
        $this->api_secret = $data['api_secret'];

      if( isset( $data['api_redirect'] ) )
        $this->api_redirect = $data['api_redirect'];

      if( isset( $data['api_token'] ) )
        $this->api_token = $data['api_token'];

      if( isset( $data['api_refresh_token'] ) )
        $this->api_refresh_token = $data['api_refresh_token'];

    }


    /**
    *** Generate Auth URL
    **/

    public function getAuthUrl() {

      return $this->endpoint . 'authorization?response_type=code&client_id=' . $this->api_id . '&redirect_uri=' . $this->api_redirect;

    }


    /**
    *** Request function
    **/

    public function request( $route, $path = 'GET', $data = false ) {

      return self::curl( $route, $path, $data );

    }


    /**
    *** Look for valid token (Is user logged in)
    **/

    public function validToken() {

      return self::curl( 'v1/market/private/user/email.json', 'GET' );

    }


    /**
    *** Set access token
    **/

    public function setAccessToken( $token ) {

      $this->user_token = $token;

    }


    /**
    *** Code to Token
    **/

    public function getAccessToken( $code, $type = 'AUTH' ) {

      $ch = curl_init( $this->endpoint . 'token' );

      curl_setopt( $ch, CURLOPT_POST, true );
      curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

      if( $type == 'AUTH' )
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( array(
          'client_id' => $this->api_id,
          'redirect_uri' => $this->api_redirect,
          'client_secret' => $this->api_secret,
          'code' => $code,
          'grant_type' => 'authorization_code'
        ) ) );
      elseif( $type == 'REFRESH' )
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( array(
          'client_id' => $this->api_id,
          'client_secret' => $this->api_secret,
          'refresh_token' => $code,
          'grant_type' => 'refresh_token'
        ) ) );

      $token = json_decode( curl_exec( $ch ) );

      if( isset( $token->error ) )
        throw new \ErrorException( $token->error_description );

      if( function_exists( 'saveAccessToken' ) )
        saveAccessToken( $token->access_token );

      self::setAccessToken( $token->access_token );

      return $token;

    }


    /**
    *** Request code to token
    **/

    function renewToken() {

      if( $this->api_refresh_token ) {

        $token = $this->getAccessToken( $this->api_refresh_token, 'REFRESH' );

        if( !$token->access_token )
          return false;

        return true;

      } else {

        return false;

      }

    }


    /**
    *** Get current live token
    **/

    private function getToken() {

      if( isset( $this->user_token ) )
        return $this->user_token;

      if( isset( $this->api_token ) )
        return $this->api_token;

      return false;

    }


    /**
    *** Submit requests using curl
    **/

    private function curl( $endpoint, $method = 'GET', $data = false, $retry = false ) {

      if( $method == 'GET' AND is_array( $data) )
        $filter = '?' . http_build_query( $data );

      $curl = curl_init( $this->endpoint . $endpoint . $filter );

      $data = json_encode( $data );

      curl_setopt( $curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
      curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
      curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' , 'Authorization: Bearer ' . self::getToken() ) );

      if( $method == 'POST' ) {
        curl_setopt( $curl, CURLOPT_POST, true );
        curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );
      }

      if( $method == 'PUT' ) {
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'PUT' );
        curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );
      }

      $result = json_decode( curl_exec( $curl ) );
      curl_close( $curl );

      // Error & Refresh token? Try again
      if( isset( $result->error ) && isset( $this->api_refresh_token ) && !$retry ) {

        $refresh = self::renewToken();

        if( $refresh )
          return self::curl( $endpoint, $method, $data, true );

      }

      // Throw Exception if error exist
      if( isset( $result->error ) )
        throw new \ErrorException( ( $result->error_description ) ? $result->error_description : $result->error );

      // Return JSON decoded result
      return $result;

    }

  }
