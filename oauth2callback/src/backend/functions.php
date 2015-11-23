<?PHP

//array prettyPrint by Diogo Brito
function pArray($arr, $nome = "Sem nome definido") {
	print("<br/>##################Start#####################");
	print("<br/> Print do Array ".$nome."<br/>");
	print("<pre>".print_r($arr,true)."</pre>");
	print("<br/>###################End######################");
}


?>