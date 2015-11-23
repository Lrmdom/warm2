<?PHP

error_reporting(E_ALL);
ini_set('display_errors', 1);

global $access_login;
global $access_pass;

$access_login = 'warmrental';
$access_pass = 'googlesync';


/* * **********************************************
  ATTENTION: Fill in these values! Make sure
  the redirect URI is to this page, e.g:
  http://localhost:8080/user-example.php
 * ********************************************** */
global $client_id;
global $client_secret;
global $redirect_uri;


$client_id = '330250655872-7v2ubsghospj9pqfsdn65enmco24vgik.apps.googleusercontent.com';
$client_secret = 'R0F8WD0yb3vtERPWcpeV5fX_';
$redirect_uri = 'http://local.warmrental.com/google/api/oauth2callback';
?>