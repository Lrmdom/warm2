<?PHP

/*
  # Warmrental Google Sync
  # Powered by comPonto.com
  # Configurações globais - Conecções e variáveis globais
 */

//Check if session start is needed
if (session_id() == '') {
    session_name("warmrental_google_sync_session");
    session_start();
}
//Timezone Lisboa!
date_default_timezone_set('Europe/London');

global $web_dir;
global $root_dir;

$sub_dirs = "/google/api/";
$web_dir = "http://" . $_SERVER['HTTP_HOST'] . $sub_dirs;
$root_dir = $_SERVER['DOCUMENT_ROOT'] . $sub_dirs;

global $db_host;
$db_host = "localhost";

global $db_username;
$db_username = "root";

global $db_password;
$db_password = "";

global $db_database;
$db_database = "warm";

//DB Connection
global $db_conn;

//Coneccao realizada no datawarehouse
?>