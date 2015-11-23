<?PHP
/*
# Warmrental Google Sync
# Powered by comPonto.com
# Configurações globais - Conecções e variáveis globais
*/


session_name("warmrental_google_sync_session");
session_start();
global $client;

require_once 'backend/functions.php';
//Inicial ligação à BD
$db_conn = new mysqli($db_host, $db_username, $db_password, $db_database);

$client = new Google_Client();
$client->setApplicationName("warmrental_google_sync");
$client->setClientId($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri($redirect_uri);
$client->addScope("https://www.googleapis.com/auth/calendar");
$client->setAccessType('offline');

if (isset($_GET['error'])) {
	$_SESSION['oauth_error'] = 1;
	$home = $web_dir.'index.php';
	header('Location: '.$home);
}

//Autenticação pós autorização
if (isset($_GET['code'])) {
  $client->authenticate($_GET['code']);
  $_SESSION['access_token'] = $client->getAccessToken();
  $decodedText = html_entity_decode($_SESSION['access_token']);
  $jsonArray = json_decode($decodedText, true);
  if (isset($jsonArray['refresh_token'])) {
	  $sql_upd = "UPDATE `AUTH` SET `refresh_token` = '".$jsonArray['refresh_token']."' WHERE `AUTH`.`id` =".$_SESSION['id_user'];
	  mysqli_query($db_conn, $sql_upd);
  } else {
	//echo "nao";  
  }
  //Fazer o redirect para passar ao if seguinte
  $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
  
  header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
} else {
	if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
		//Autenticar o cliente para de seguida iniciar servico Google
	  	$client->setAccessToken($_SESSION['access_token']);
		if ($client->getAccessToken()) {
			//Autenticação Ok, redirect para refresh.
			$refshdir = $web_dir.'calendars.php';
			header('Location: '.$refshdir);
		}
		
	} else {
		die("<br>ACESSO INVALIDO");	
	}
}

?>