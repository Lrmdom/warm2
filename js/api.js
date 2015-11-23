/* ############################
*	warmrental.com google sync
*	
*	powered by comPonto.com
*/
// ############################

// ############################
// #### APP CORE FUNCTIONS ####
// ############################

//Fechar ligações
function close_sync(valArray) {
	
	var msg = '<br><span class="server_waiting">Fechar ligações... </span>';
	$('#closing_div').append(msg);
	
	//Close sync
	$.ajax({
		url : "processor.php",
		type: "POST",
		dataType : 'json',
		data : {
			flag : 'sync_close'
		},
		success: function(data, textStatus, jqXHR) {
			var serverbox 	= $('#server_results');
			var debugbox 	= $('#debug_div');
			var debug_msg;
			console.dir(data);
			
			var dNow = new Date();
			var localdate= dNow.getDate() + '/' + (dNow.getMonth()+1) + '/' + dNow.getFullYear() + ' ' + dNow.getHours() + ':' + dNow.getMinutes();				

			debug_msg = "<p><span class='server_notice'><strong>#### ULTIMO ARRAY REQUISITADO AO SERVIDOR ("+localdate+") ####</strong></span><br/>";
			debug_msg += print_arr(valArray, 0);
			debug_msg += "<br><span class='server_notice'><strong>#### ULTIMO ARRAY REQUISITADO AO SERVIDOR EOF ####</strong></span></p>";
	
			var resposta = $.parseJSON(data);
			
			if (resposta.ok) {
				console.log("CLOSE SYNC OK");
				console.dir(resposta);
				if (!resposta.ok) {
					var msg = '<br><span class="server_error"><strong>Não foi possivel escrever na base de dados.</strong></span><br>';
					$('#closing_div').append(msg);
					
				} else {
					var msg = ' <span class="server_notice"><strong>OK!</strong></span><br>';
					$('#closing_div').append(msg);
					
					msg = '<br><span class="server_notice"><strong>####################### Sincronização acabada #######################</strong></span><br>';
					$('#closing_div').append(msg);	
					
					debugbox.append(debug_msg);
					
					add_export_button("dl_conteudos");
				}	
					
			} else {
				var msg = '<span class="server_error"><strong>'+resposta.responseText+'</strong></span> <br>';
				serverbox.append(msg);								
			}
			
		},
		error: function(jqXHR, textStatus, errorThrown) {
			var serverbox 	= $('#server_results');
				var msg = '<span class="server_error"><strong>O servidor retornou um erro após o seu pedido. Por favor contacte o administrador do sistema.</strong></span> <br>';
				serverbox.append(msg);		
			
		}
	}); 
	return false;	
}
//EOF Close sync Fechar ligações


//Start Cancelar Reservas
function cancelar_reservas (res_canceladas, res_google)	{
	
	//Notify user	
	var msg = '<span class="server_waiting">A cancelar reservas... </span> ';
	$('#cancel_div').append(msg);
	
	//Cancelar Reservas
	$.ajax({
		url : "processor.php",
		type: "POST",
		dataType : 'json',
		data : {
			flag : 'sync_cancel_res',
			canceladas: res_canceladas,
			tot_google: res_google
		},
		success: function(data, textStatus, jqXHR) {
			var cancelbox 	= $('#cancel_div');
			var serverbox 	= $('#server_results');
			var debugbox 	= $('#debug_div');
	
			var resposta = $.parseJSON(data);
			
			if (resposta.ok) {
				console.log("Canceladas - 1");
				console.dir(resposta);
				msg = ' <span class="server_notice"><strong>OK!</strong></span>';
				
				$('#cancel_div').append(msg);
				
				msg = '<span class="server_notice"> - As reservas a cancelar foram canceladas. Nomeadamente: '+resposta.apagados+'</span><br>';
				$('#cancel_div').append(msg);
					
			} else {
				console.log("Canceladas - 0");
				console.dir(resposta);
				
				msg = ' <span class="server_notice"><strong>OK!</strong></span>';
				
				$('#cancel_div').append(msg);
				
				msg = '<span class="server_notice"> - Não existiam reservas no google a cancelar.</span><br>';
				$('#cancel_div').append(msg);
												
			}
			
		},
		error: function(jqXHR, textStatus, errorThrown) {
			var serverbox 	= $('#server_results');
			var msg = '<span class="server_error"><strong>O servidor retornou um erro após o seu pedido. Por favor contacte o administrador do sistema.</strong></span> <br>';
			serverbox.append(msg);		
			
		}
	}); //EOF Cancelar Reservas ajax
	
	return false;	
}
//EOF Cancelar Reservas

//Start start_sync();
function start_sync() {
	//Verificação de necessidades
	$.ajax({
		url : "processor.php",
		type: "POST",
		dataType : 'json',
		data : {
			flag : 'get_mod_arrays'
		},
		success: function(data, textStatus, jqXHR) {
			var serverbox 	= $('#server_results');
			var resposta = $.parseJSON(data);
			
	
			console.log("Mod Arrays: ");
			console.dir(resposta);
			
			
			//Sem calendarios
			if (resposta.ok) {
				var msg = ' <span class="server_notice"><strong>OK!</strong></span><br>';
				$('#building_div').append(msg);
				
				var msg = '<span class="server_error">Sem calendários para sincronizar. </span> ';
				$('#building_div').append(msg);
				
				console.log("SEM CALENDARIOS PARA SINCRONIZAR");
				
				close_sync(resposta);
					
			//Alteracoes a fazer
			} else {
				//Alterações para fazer nos dois
				if ((resposta.google_flag>0 & resposta.db_flag>0)) {
					console.log("ALTERACOES GOOGLE E BD");
					
					msg = ' <span class="server_notice"><strong>OK!</strong></span><br>';
					$('#building_div').append(msg);
					
					//Verificar se existem reservas a cancelar
					if (resposta.cancel_flag>0) {
						
						cancelar_reservas(resposta.canceladas, resposta.tot_google);
						
					} 
					
					//EXECUTA ALTERACOES NOS DOIS!
					
					//Verificar se existem reservas a cancelar
					if (resposta.cancel_flag>0) {
						
						//EXECUTA CANCELAMENTO DE RESERVAS
						cancelar_reservas(resposta.canceladas, resposta.tot_google);
						
					}
					
					
					//Verifica se ha conflitos a resolver para depois EXECUTAR ALTERACOES DA BD
					if (resposta.conflitos_flag) {
						process_conflicts(resposta, "BOTH");
					} else {
						sync_BOTH (resposta);
						
					}
				
				//Sem alterações em nenhum
				} else if ((resposta.google_flag==0 & resposta.db_flag==0)) {
					console.log("SEM ALTERACOES");
					
					msg = ' <span class="server_notice"><strong>OK!</strong></span><br>';
					$('#building_div').append(msg);
					
					//Verificar se existem reservas a cancelar
					if (resposta.cancel_flag>0) {
						
						cancelar_reservas(resposta.canceladas, resposta.tot_google);
				
					}
					
					msg = '<span class="server_error">Sem alterações desde a ultima sincronização. </span> ';
					$('#alteracoes_div').append(msg);
					
					close_sync(resposta);
				
				
				//Alterações só no google
				} else if (resposta.google_flag==1) {
					console.log("ALTERACOES GOOGLE");
					
					msg = ' <span class="server_notice"><strong>OK!</strong></span><br>';
					$('#building_div').append(msg);
					
					//Verificar se existem reservas a cancelar
					if (resposta.cancel_flag>0) {
						
						cancelar_reservas(resposta.canceladas, resposta.tot_google);
					} 
					
					msg = '<span class="server_waiting">Existem alterações no Google, a analisar... </span> ';
					$('#alteracoes_div').append(msg);
					
					
					//Verificar se existem reservas a cancelar
					if (resposta.cancel_flag>0) {
						
						//EXECUTA CANCELAMENTO DE RESERVAS
						cancelar_reservas(resposta.canceladas, resposta.tot_google);
						
					}
					
					
					//Verifica se ha conflitos a resolver para depois EXECUTAR ALTERACOES DA BD
					if (resposta.conflitos_flag) {
						process_conflicts(resposta, "BOTH");
					} else {
						sync_BOTH (resposta);
						
					}
					
					
				//Alterações só na BD
				} else if (resposta.db_flag==1) {
					console.log("ALTERACOES BD");
				
					msg = ' <span class="server_notice"><strong>OK!</strong></span><br>';
					$('#building_div').append(msg);
					
					//Verificar se existem reservas a cancelar
					if (resposta.cancel_flag>0) {
						
						//EXECUTA CANCELAMENTO DE RESERVAS
						cancelar_reservas(resposta.canceladas, resposta.tot_google);
						
					}
					
					
					//Verifica se ha conflitos a resolver para depois EXECUTAR ALTERACOES DA BD
					if (resposta.conflitos_flag) {
						process_conflicts(resposta, "DB");
					} else {
						sync_onlyDB (resposta);
						
					}
					
					
				}
							
			}
			
		},
		error: function(jqXHR, textStatus, errorThrown) {
			var serverbox 	= $('#server_results');
			var msg = '<span class="server_error"><strong>O servidor retornou um erro após o seu pedido. Por favor contacte o administrador do sistema.</strong></span> <br>';
			serverbox.append(msg);
		}
	}); 	
}	
//EOF start_sync();

//Start of process conflicts
function process_conflicts(entry, str) {
	var msg = '<br><span class="server_waiting">A resolver conflitos... </span>';
	$('#alteracoes_div').append(msg);
	
	console.log("PROCESSAR CONFLITOS");
	
	//CONFLICTS
	$.ajax({
		url : "processor.php",
		type: "POST",
		dataType : 'json',
		data : {
			flag 		: 'process_conflicts',
			entrada		: entry,
			mode		: str
		},
		success: function(data, textStatus, jqXHR) {
				 
			var resposta = $.parseJSON(data);
			 
			console.log("Conflitos RESPOSTA:");
			console.dir(resposta);

			if (resposta.erro_grave) { 
				$('.jumbotron').css("background-color", "#A00016");
				alert("CONFLITO ENTRE EVENTO DE PROPRIETÁRIO E EVENTO WARMRENTAL, por favor deixe a sincronização acabar e faça um refresh (F5). Obrigado.");
			}
						 
			msg = ' <span class="server_notice"><strong>OK!</strong></span><br>';
			$('#alteracoes_div').append(msg);
			if (str=="DB") {
				sync_onlyDB (resposta);
			} else {
				sync_BOTH (resposta);
			}
			
		},
		error: function(jqXHR, textStatus, errorThrown) {
			var serverbox 	= $('#server_results');
			var msg = '<span class="server_error"><strong>O servidor retornou um erro após o seu pedido. Por favor contacte o administrador do sistema.</strong></span> <br>';
			serverbox.append(msg);		
			
		}
	}); //EOF process conflicts AJAX	
}

//Start of sync_onlyDB(resposta);
function sync_onlyDB (modArrays) {
	var mod_DB 		= modArrays;
	
	var msg = '<span class="server_waiting">Existem alterações na BD, a analisar... </span>';
	$('#alteracoes_div').append(msg);
	
	//sync DB -> Google
	$.ajax({
		url : "processor.php",
		type: "POST",
		dataType : 'json',
		data : {
			flag 		: 'sync_onlyDB',
			mod_DB		: mod_DB
		},
		success: function(data, textStatus, jqXHR) {
				
			var resposta = $.parseJSON(data);
			
			console.log("SYNC DB RESPOSTA:");
			console.dir(resposta);
				
			msg = ' <span class="server_notice"><strong>OK!</strong></span><br>';
			$('#alteracoes_div').append(msg);
				
			if (resposta.updated) {
				console.log("onlyDB - UPDATE");
				
			}
			 
			if (resposta.inserted) {
				console.log("onlyDB - INSERT");					
			}

			close_sync(resposta); 
			
		},
		error: function(jqXHR, textStatus, errorThrown) {
			var serverbox 	= $('#server_results');
			var msg = '<span class="server_error"><strong>O servidor retornou um erro após o seu pedido. Por favor contacte o administrador do sistema.</strong></span> <br>';
			serverbox.append(msg);		
			
		}
	}); //EOF sync_onlyDB(resposta) AJAX
}//END OF SCRIPT
//EOF sync_onlyDB(resposta) 

//Start of sync_BOTH(resposta);
function sync_BOTH (modArrays) {
	var mod_DB 		= modArrays;
	
	var msg = '<span class="server_waiting">Existem alterações no google e db, a analisar... </span>';
	$('#alteracoes_div').append(msg);
	
	//sync DB <-> Google
	$.ajax({
		url : "processor.php",
		type: "POST",
		dataType : 'json',
		data : {
			flag 		: 'sync_BOTH',
			mod_DB		: mod_DB
		},
		success: function(data, textStatus, jqXHR) {
				
			var resposta = $.parseJSON(data);
			
			console.log("SYNC BOTH RESPOSTA:");
			console.dir(resposta);
				
			msg = ' <span class="server_notice"><strong>OK!</strong></span><br>';
			$('#alteracoes_div').append(msg);
				
			if (resposta.updated) {
				console.log("BOTH - UPDATE");
				
			}
			 
			if (resposta.inserted) {
				console.log("both - INSERT");					
			}
		
			close_sync(resposta); 
			
		},
		error: function(jqXHR, textStatus, errorThrown) {
			var serverbox 	= $('#server_results');
			var msg = '<span class="server_error"><strong>O servidor retornou um erro após o seu pedido. Por favor contacte o administrador do sistema.</strong></span> <br>';
			serverbox.append(msg);		
			
		}
	}); //EOF sync_BOTH(resposta) AJAX
}//END OF SCRIPT
//EOF sync_BOTH(resposta) 


// ###############################
// #### APP SUPPORT FUNCTIONS ####
// ###############################

//Construção dos containers necessarios à comunicacao com o servidor
function build_containers() {
	
	$('<div/>', {
		id: 'start_div',
		title: 'Sync INIT'
	}).appendTo('#server_results');
	
	var msg = '<br><span class="server_request">Sincronização iniciada. Este processo poderá demorar alguns minutos, por favor não faça nada até à próxima instrução. Obrigado.</span> <br>';
	$('#start_div').append(msg);
	
	msg = '<br><span class="server_notice"><strong>####################### Sincronização iniciada #######################</strong></span><br>';
	$('#server_results').append(msg);	
	
	$('<div/>', {
		id: 'building_div',
		title: 'Building Cloud'
	}).appendTo('#server_results');
	
	msg = '<span class="server_waiting">Building the cloud... </span> ';
	$('#building_div').append(msg);
	
	$('<div/>', {
		id: 'cancel_div',
		title: 'Reservas canceladas'
	}).appendTo('#server_results');
	
	$('<div/>', {
		id: 'alteracoes_div',
		title: 'Alterações a fazer'
	}).appendTo('#server_results');
	
	$('<div/>', {
		id: 'closing_div',
		title: 'Closing Connections'
	}).appendTo('#server_results');	
	
}
//EOF Container build

//Button para exportacao de relatorio
function add_export_button (id_div) {
	var pagina;
	var estilos;

	estilos = "<style>.server_notice {font-family:'Lucida Grande', 'Lucida Sans Unicode', 'Lucida Sans', 'DejaVu Sans', Verdana, sans-serif;color: green;}.server_request {font-family:'Lucida Grande', 'Lucida Sans Unicode', 'Lucida Sans', 'DejaVu Sans', Verdana, sans-serif;color: #09C;}.server_error {font-family:'Lucida Grande', 'Lucida Sans Unicode', 'Lucida Sans', 'DejaVu Sans', Verdana, sans-serif;color: #900;}.server_waiting {font-family:'Lucida Grande', 'Lucida Sans Unicode', 'Lucida Sans', 'DejaVu Sans', Verdana, sans-serif;color: #F90;}</style>";
	
	if (!Date.now) {
		Date.now = function() { return new Date().getTime(); };
	}
	
	var timestamp = Date.now();

	var dNow = new Date();
	var localdate= dNow.getDate() + '/' + (dNow.getMonth()+1) + '/' + dNow.getFullYear() + ' ' + dNow.getHours() + ':' + dNow.getMinutes();
	
	var clean = document.getElementById(id_div);
	
	clean.innerHTML = " ";
	
	var exportar = document.getElementById(id_div).appendChild(
		document.createElement("a")
	);
	
	pagina = "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">"+estilos+"</head><body><h2 style=\"font-size: 24px; font-family: 'Lobster', Georgia, Times, serif;\">SYNC REPORT - <strong>" + localdate + "</strong></h2> " + $(".container-fluid").html()+"</div></body></html>";
	
	exportar.download = "Warmrental.Sync_Export."+timestamp+".html";
	exportar.href = "data:text/html," + pagina;
	exportar.innerHTML = "<p>[Exportar]</p><br><br><br>";	
}

//comPonto.com PHP<->JS array interpreter
//Diogo Brito
function print_arr (o, flag) {
	var str='';

	for (var p in o){
		if(typeof o[p] == 'string' || typeof o[p] == 'number'){
			if (flag) {
				str+= "   <span class='server_request'>" + p + "</span> : <span class='server_notice'>"+ o[p] + '</span>; </br>';
			} else {
				str+= "<span class='server_request'>" + p + "</span> : <span class='server_notice'>"+ o[p] + '</span>; </br>';
			}
		} else {
			if (flag) {
				str+= "  <span class='server_request'>" + p + ": { </br><span class='server_notice'>" + print_arr(o[p], 1) + '</span>}';
			} else {
				str+= "<span class='server_request'>" + p + ": { </br><span class='server_notice'>" + print_arr(o[p], 1) + '</span>}';
			}
		}
	}
	return str;
}

// ############################ API EOF #########################