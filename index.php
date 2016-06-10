<?php

  /**
  *** Start session to store refresh token
  **/

  session_start();


  /**
  *** Function to save access token
  **/

  function saveAccessToken( $token ) {
    $_SESSION['envato_token'] = $token;
  }


  /**
  *** Initiate Envato API
  **/

  include 'vendor/autoload.php';
  include 'config.php';

  $envato = new \Smafe\Envato( array(
    'app_id' => APP_ID
  , 'app_secret' => APP_SECRET
  , 'app_redirect' => APP_REDIRECT
  ) );


  /**
  *** Set Access Token if it exists
  **/

  if( isset( $_SESSION['envato_token'] ) )
    $envato->setAccessToken( $_SESSION['envato_token'] );


  /**
  *** Logout user
  **/

  if( isset( $_GET['exit'] ) ) {
    session_destroy();
    $_SESSION['envato_token'] = NULL;
    header( 'Location: index.php', 0 );
    exit();
  }

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="description" content="Envato PHP API library to easily connect and communicate with the Rest API.">
    <meta name="author" content="Smafe Web Solutions">

    <title>Smafe - Envato PHP API Library</title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <style>
      h3 {
        margin-top: 50px;
      }

      .container {
        max-width: 760px;
      }
    </style>
  </head>

  <body>

    <div class="container">

      <div class="page-header">
        <h1>Envato PHP API Library</h1>
        <p class="lead">Simple PHP library to connect and communicate with the Envato API.</p>
      </div>

      <h3>Initiate the library</h3>
      <p>You can easily initiate the library by including the correct information.</p>

      <pre>$envato = new \Smafe\Envato( array(
  'app_id' => 'ENVATO APP ID'
, 'app_secret' => 'ENVATO SECRET KEY'
, 'app_redirect' => 'APP REDIRECT URI'
, 'app_token' => 'APP TOKEN'
, 'user_request' => 'PRE-EXISTING USER REFRESH TOKEN'
) );</pre>

      <br />

      <code>app_id</code>
      <p><small>The application ID you got when you created the application with Envato.</small></p>

      <code>app_secret</code>
      <p><small>The secret key you got when you generated the applicated with Envato.</small></p>

      <code>app_redirect</code>
      <p><small>The redirect URI where the app will go when you authorize your Envato account. This has to match the URI registered with the application at Envato.</small></p>

      <code>app_token</code>
      <p><small>The app token is the same as your "Personal token" with Envato. You can use this to access the API directly without logging in. This is useful when your only accessing your own account and dont require the user to authenticate with their own Envato account.</small></p>

      <code>user_request</code>
      <p><small>When a user login for the first time, you are given a "refresh token", this refresh token can be used to generate a new access token when it expires without having the user login again. By defining this, then the system will generate a new access token based on the request key and invoke the function <code>saveAccessToken()</code></small></p>


      <h3 class="text-danger">saveAccessToken()</h3>
      <p>When the library generates a new access token it checks if the function <code>saveAccessToken()</code> exists. It is up to your app / code to create this function and do with the access token as you please. An example of such a function could be something like this...</p>

      <pre>function saveAccessToken( $token ) {
  $_SESSION['envato_token'] = $token;
}</pre>

      <p>It is recommended that you store the access token and re-use it using <code>setAccessToken()</code> so you avoid generating new tokens for every visit.</p>


      <h3 class="text-danger">setAccessToken()</h3>
      <p>Using <code>setAccessToken()</code> then you can define the access token after the library is initiated.</p>

      <pre>$envato = new \Smafe\Envato( array(
  'app_id' => 'ENVATO APP ID'
, 'app_secret' => 'ENVATO SECRET KEY'
, 'app_redirect' => 'APP REDIRECT URI'
) );

$envato->setAccessToken( $token );</pre>
      <p>You have now defined the access token the library should use to login with.</p>

      <?php if( isset( $_GET['code'] ) ): ?>
      <h3>User attempt to authorize account</h3>
      <p >A user is attempting to authorize his account. The result of this would be.</p>

      <pre><?php

        if( isset( $_GET['code'] ) ) {

          try {

            $code = $envato->getAccessToken( $_GET['code'] );
            print_r( $code );

          } catch( \ErrorException $e ) {

            echo $e->getMessage();

          }

        }

      ?></pre>
      <?php endif; ?>

      <h3>Is user logged in?</h3>
      <p>Check if a user is logged in or not</p>

      <pre><?php

        try {

          $logged = $envato->valid_token();
          print_r( $logged );

        } catch( \ErrorException $e ) {

          echo 'User is not logged in. Failed with error: ' . $e->getMessage();
          // Don't need to echo error here. If $logged is defined, i means the access is granted.

        }

      ?></pre>

      <?php if( isset( $logged ) ): ?>
      <a href="index.php?exit=true">Logout</a>
      <?php endif; ?>

      <?php if( !isset( $logged ) ): ?>
      <h3>You are not logged in. Want to?</h3>
      <p>Login to your Envato account to view how full access looks like.</p>

      <?php

        if( !isset( $logged ) )
          echo '<a href="' . $envato->getAuthUrl() . '" class="btn btn-danger">Login to your Envato account</a>';

      ?>
      <?php endif; ?>

      <h3>Single item</h3>
      <p>Print details for a single specific item</p>
      <br />

      <div>
        <ul class="nav nav-tabs" role="tablist">
          <li role="presentation" class="active"><a href="#single_markup" aria-controls="home" role="tab" data-toggle="tab">Markup</a></li>
          <li role="presentation"><a href="#single_result" aria-controls="profile" role="tab" data-toggle="tab">Result</a></li>
        </ul>

        <br />

        <div class="tab-content">
          <div role="tabpanel" class="tab-pane active" id="single_markup">
          <pre>$item = $envato->request( 'v3/market/catalog/item?id=13041404' );
print_r( $item );
</pre>
          </div>

          <br />

          <div role="tabpanel" class="tab-pane" id="single_result">
            <pre><?php

              try {

                $item = $envato->request( 'v3/market/catalog/item?id=13041404' );
                print_r( $item );

              } catch( \ErrorException $e ) {

                echo $e->getMessage();

              }

            ?></pre>
          </div>
        </div>
      </div>

      <h3>All users purchases</h3>
      <p>View a complete list of all the users purchases.</p>
      <br />

      <div>
        <ul class="nav nav-tabs" role="tablist">
          <li role="presentation" class="active"><a href="#purchase_markup" aria-controls="home" role="tab" data-toggle="tab">Markup</a></li>
          <li role="presentation"><a href="#purchase_result" aria-controls="profile" role="tab" data-toggle="tab">Result</a></li>
        </ul>

        <br />

        <div class="tab-content">
          <div role="tabpanel" class="tab-pane active" id="purchase_markup">
          <pre>$purchase = $envato->request( 'v3/market/buyer/list-purchases' );
print_r( $purchase );
</pre>
          </div>

          <br />

          <div role="tabpanel" class="tab-pane" id="purchase_result">
            <pre><?php

              try {

                $purchase = $envato->request( 'v3/market/buyer/list-purchases' );
                print_r( $purchase );

              } catch( \ErrorException $e ) {

                echo $e->getMessage();

              }

            ?></pre>
          </div>
        </div>

      </div>

      <h3 class="text-danger">request()</h3>
      <p>Request allows you to visit any route using any method from the Envato API. By using the request function then you will get any information you want from any route.</p>

      <br />

      <div>
        <ul class="nav nav-tabs" role="tablist">
          <li role="presentation" class="active"><a href="#request_markup" aria-controls="home" role="tab" data-toggle="tab">Markup</a></li>
        </ul>

        <br />

        <div class="tab-content">
          <div role="tabpanel" class="tab-pane active" id="purchase_markup">
            <pre>$request = $envato->request( 'ROUTE' );
print_r( $request );
</pre>

            <p>There is a lot of routes to choose from, go to the <a href="https://build.envato.com/api/" target="_blank">Envato API documentation pages</a> to view a complete list of all your options. Some examples of these options are listed below.</p>

            <ul>
              <li>v3/market/catalog/item?id=23323</li>
              <li>v1/market/categories:themeforest.net.json</li>
              <li>v1/market/user:username.json</li>
            </ul>
          </div>
        </div>
      </div>

      <h3>Working example</h3>
      <p>This is a complete working example using the Envato API that should work out of the box :)</p>

      <pre>$envato = new \Smafe\Envato( array(
  'app_id' => 'ENVATO APP ID'
, 'app_secret' => 'ENVATO SECRET KEY'
, 'app_redirect' => 'APP REDIRECT URI'
, 'app_token' => 'APP TOKEN'
) );

$request = $envato->request( 'v3/market/catalog/item?id=13041404' );

print_r( $request );</pre>

      <br />
      <br />
      <br />

    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>

  </body>
</html>
