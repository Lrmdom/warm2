<?PHP
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../settings/global.php';

$webdir = $web_dir;

//Destroying Session
unset($_SESSION);
session_destroy();
header('Location: '.$webdir);

?>
