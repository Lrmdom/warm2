<?PHP
require_once '../settings/global.php';
require_once '../settings/config_api.php';

set_include_path('../src/' . PATH_SEPARATOR . get_include_path());
require_once '../src/Google/Client.php';
require_once '../src/Google/Service/Calendar.php';

//array prettyPrint by Diogo Brito
function pArray($arr, $nome = "Sem nome definido") {
	print("<br/>##################Start#####################");
	print("<br/> Print do Array ".$nome."<br/>");
	print("<pre>".print_r($arr,true)."</pre>");
	print("<br/>###################End######################");
}


?>