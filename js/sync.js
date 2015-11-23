/* ############################
*	warmrental.com google sync
*	
*	powered by comPonto.com
*/
// ############################

$(document).ready(function() {
	//Mensagem de boas vindas
	
	var msge = '<span class="server_notice">Clique em Sincronizar para iniciar.</span> <br>';
	$('#server_results').append(msge);
	
	//Start Sync Button
	$("#sync_start").click(function(event) {
		console.clear();
		console.log("New console instance");
		
		$('#server_results').empty();
		var serverbox 	= $('#server_results');
		
		
		//Criar os containers necessarios as status msgs
		build_containers();
		
		//Iniciar sincronização
		start_sync();
							
		return false;
	});	
	//EOF Start sync button behavior
	
	//Start Logout Button behavior
	$("#logoutbut").click(function(event) {
		window.location = 'api/logout.php';
		return false;
	});
	//EOF Logout button
});
