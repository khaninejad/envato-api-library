<?php

  namespace Smafe;

  class Envato {

    /**
    *** Construct authentication details
    **/

    public function __construct( $data ) {

      if( isset( $data['app_id'] ) )
        $this->app_id = $data['app_id'];

      if( isset( $data['app_secret'] ) )
        $this->app_secret = $data['app_secret'];

      if( isset( $data['app_redirect'] ) )
        $this->app_redirect = $data['app_redirect'];

      if( isset( $data['app_token'] ) )
        $this->app_token = $data['app_token'];

      if( isset( $data['user_request'] ) )
        $this->user_request = $data['user_request'];

    }


    /**
    *** Generate Auth URL
    **/

    public function getAuthUrl() {
      return 'https://api.envato.com/authorization?response_type=code&client_id=' . $this->app_id . '&redirect_uri=' . $this->app_redirect;
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

    public function valid_token() {
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
      $ch = curl_init( 'https://api.envato.com/token' );

      curl_setopt( $ch, CURLOPT_POST, true );
      curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

      if( $type == 'AUTH' ) {
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( array(
          'client_id' => $this->app_id,
          'redirect_uri' => $this->app_redirect,
          'client_secret' => $this->app_secret,
          'code' => $code,
          'grant_type' => 'authorization_code'
        ) ) );
      } elseif( $type == 'REFRESH' ) {
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( array(
          'client_id' => $this->app_id,
          'client_secret' => $this->app_secret,
          'refresh_token' => $code,
          'grant_type' => 'refresh_token'
        ) ) );
      }

      $token = json_decode( curl_exec( $ch ) );

      if( isset( $token->error ) )
        throw new \ErrorException( $token->error_description );

      if( function_exists( 'saveAccessToken' ) )
        saveAccessToken( $token->access_token );

      self::setAccessToken( $token->access_token );

      return $token;
    }


    /**
    *** Get current live token
    **/

    private function getToken() {
      if( isset( $this->user_token ) )
        return $this->user_token;

      if( isset( $this->app_token ) )
        return $this->app_token;

      return false;
    }


    /**
    *** Submit requests using curl
    **/

    private function curl( $endpoint, $method = 'GET', $data = false, $retry = false ) {

      $curl = curl_init( 'https://api.envato.com/' . $endpoint );

      curl_setopt( $curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
      curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
      curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' , 'Authorization: Bearer ' . self::getToken() ) );

      if( $method == 'POST' ) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
      }

      if( $method == 'PUT' ) {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
      }

      $result = json_decode( curl_exec( $curl ) );
      curl_close( $curl );

      // Throw Exception if error exist
      if( isset( $result->error ) )
        throw new \ErrorException( $result->error_description );

      return $result;

    }

  }
