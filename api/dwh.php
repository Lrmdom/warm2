<?PHP
/*
# Warmrental Google Sync
# Powered by comPonto.com
# Configurações globais - build dwh
*/

error_reporting(E_ALL);
ini_set('display_errors', 1);
//Check if session start is needed
if(session_id() == '') {
	session_name("warmrental_google_sync_session");
	session_start();
	
}

//relativo a root, de onde será chamado
require_once 'api/backend/functions.php';

//Apply logout
if (isset($_REQUEST['logout'])) {
}


//Configure Google API Security
if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
	//Autenticar o cliente para de seguida iniciar servico Google
	
	
	$tokens = html_entity_decode($_SESSION['access_token']);
	$tokens = json_decode($tokens, true);
	$tokens["refresh_token"] = $_SESSION['refresh_token'];
	$tokens = json_encode($tokens, true);
	
	$client->setAccessToken($tokens);
	
	//$client->setAccessToken($_SESSION['access_token']);
	

} else {
	
	$false_access_token = 1;

}
//################################## GET last_sync #########################################
$sql="SELECT * FROM tbl_History";
$syncs = query_db($sql);

foreach ($syncs as $sync) {
	$_SESSION['last_sync'] = $sync['last_sync'];
}
//################################## Permanent Error check ###################################
$sql2="SELECT * FROM sync_errors WHERE RESOLVIDO = 0";
$erros = query_db($sql2);
$error_string ="";
$num_erros = 0;
foreach ($erros as $erro) {
	$error_string .= "Calendário: ".$erro['calendar_id']."; Inicio: ".$erro['start_date']."; Fim:".$erro['end_date']."; Conflito b_ID: ".$erro['conflict_with_bID'].";<br>";
	$num_erros++;
}
if ($num_erros>0) {
	$exists_Error = 1;
} else {
	$exists_Error = 0;
}

//################################## DATABASE ARRAYS #########################################
//Array Backoffice Users
global $bo_Users;

$bo_Users = array();
$sql1="SELECT * FROM AUTH";

$rs1=$db_conn->query($sql1);

if($rs1 === false) {
  trigger_error('Wrong SQL: ' . $sql1 . ' Error: ' . $db_conn->error, E_USER_ERROR);
} else {
  $rows_returned1 = $rs1->num_rows;
} 

//Record sets
$rs1->data_seek(0);

//Popular array Users
while($row = $rs1->fetch_assoc()){
	$bo_Users[$row['id']]['id']				= $row['id'];
	$bo_Users[$row['id']]['username']		= $row['username'];
	$bo_Users[$row['id']]['password']		= $row['password'];
	$bo_Users[$row['id']]['email']			= $row['email'];	
	
	$bo_Users[$row['id']]['refresh_token']	= $row['refresh_token'];					
}
$rs1->free();

//Array db_Calendars
global $db_Calendars;

$db_Calendars = get_db_Calendars();

//################################## GOOGLE ARRAYS ###########################################
//Retrieve Calendars and work with google, only if authenticated
if (isset($_SESSION['access_token'])) {
global $google_Calendars;
$google_Calendars = get_google_Calendars ();

//################################## Synced Arrays #########################################
global $Calendars;

$Calendars = get_syncCals ($db_Calendars, $google_Calendars);

}//end of if isset($_SESSION['access_token'])


?>