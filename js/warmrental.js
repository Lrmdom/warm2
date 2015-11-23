/* ############################
*	warmrental.com google sync
*	
*	powered by comPonto.com
*/
// ############################
$(document).ready(function() {
	var msge = '<span class="server_notice">Bases de dados carregadas... Bem-vindo! </span> <br>';
	$('#server_results').append(msge);
	

	
	$("#calendar_redirect").click(function(event) {
		window.location = 'sync.php';
		return false;
	});
	
	$("#logoutbut").click(function(event) {
		window.location = 'api/logout.php';
		return false;
	});
	
	
	//callback handler for form submit
	$("#login_form").submit(function(e) { 
		var postData = $(this).serializeArray();
		$.ajax({
			url : "processor.php",
			type: "POST",
			dataType : 'json',
			data : postData,
			success: function(data, textStatus, jqXHR) {
				console.dir(data);
				if (data.ok) {
					var msg_success;
					var msg_access;
					
					msg_success = '<span style="color: green; font-sixe: 12px;">Login realizado com sucesso! Clique no botão abaixo para conectar-se à base de dados e à API Google.</span>';
					$('#info_box').html(msg_success);
					
					$("input[type=button]").removeAttr("disabled");
					$("input[type=button]").removeAttr("style");
					$("#connectBut").click(function(event) {
						window.location.replace(data.authLink);
						return false;
					});					
				} else {
					alert('Credenciais erradas. A sua tentativa foi registada.');
					//
					console.dir(data);
					console.log(data.ok);
					location.reload();	
				}
				
			},
			error: function(jqXHR, textStatus, errorThrown) {
				//Falhou=/     
				console.log("ERRO");
				alert('Algo correu mal :( ');
			}
		});
		e.preventDefault(); 
	});
	
	$("#loginBut").click(function(event) {
		$("#login_form").submit(); //Submit  the FORM
		return false;
	});

	$("#get_prop").click(function(event) {
		var serverbox 	= $('#server_results');
		var msg = '<span class="server_request">Pedido de visualização de propriedades com id de propriedade diferente de zero.</span> <br>';
		serverbox.append(msg);
		
		$.ajax({
			url : "processor.php",
			type: "POST",
			dataType : 'json',
			data : {
				flag		: 'get_property'
			},
			success: function(data, textStatus, jqXHR) {
				var serverbox 	= $('#server_results');
				var resposta = $.parseJSON(data);
				if (resposta.ok) {
					var props = resposta.array;
					var msg = '<span class="server_notice">Existem as seguintes propriedades registadas:</span><br>';
					serverbox.append(msg);
					
					msg = '<span class="server_notice">#############################</span><br>';
					serverbox.append(msg);
					
					for (var i = 0; i < props.length; i++) {
						msg = '<span class="server_notice">ID: '+props[i]['listing_id']+' - Titulo: '+props[i]['calendar_title']+' ('+props[i]['calendar_id']+')  </span><br>';
						serverbox.append(msg);																	
					}	
					
					msg = '<span class="server_notice">#############################</span><br>';
					serverbox.append(msg);																	
									
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
	});	

	
	$("#error_unassign_prop").click(function(event) {
		var id = $('#error_listing_id').val();
		
		if (id.length<1) {
			console.log("MTTO PEQUENO");	
		} else {
			var propid 		= $('#error_listing_id').val();
			var serverbox 	= $('#server_results');
			var msg = '<span class="server_request">Unassign de propriedade: <strong>'+propid+'</strong></span> <br>';
			serverbox.append(msg);
			
			$.ajax({
				url : "processor.php",
				type: "POST",
				dataType : 'json',
				data : {
					flag		: 'unassign_property',
					listing_id		: propid
				},
				success: function(data, textStatus, jqXHR) {
					var serverbox 	= $('#server_results');
					var resposta = $.parseJSON(data);
					console.dir(resposta);
					if (resposta.ok) {
						console.log("EAFDADAD");
						var msg = '<span class="server_notice"><strong> '+resposta.responseText+'.</strong></span> <br>';
						serverbox.append(msg);																			
										
					} else {
						var msg = '<span class="server_error"><strong>'+resposta.responseText+'</strong></span> <br>';
						serverbox.append(msg);								
					}
					
				},
				error: function(jqXHR, textStatus, errorThrown) {
					//Falhou=/     
					console.log("ERRO");
					alert('Algo correu mal :( ');
				}
			});
		}
		return false;
	});	

	
	$("#upd_prop").click(function(event) {
		var id = $('#calendar_id').val();
		
		if (id.length<1) {
			console.log("ID DE PROPRIEDADE DEVE SER DIFERENTE DE 0");	
		} else {
			var propid 		= $('#listing_id').val();
			var serverbox 	= $('#server_results');
			var msg = '<span class="server_request">Tentativa de actualização de id de propriedade do calendário <strong>'+$('#autocomplete').val()+'</strong></span> <br>';
			serverbox.append(msg);
			
			$.ajax({
				url : "processor.php",
				type: "POST",
				dataType : 'json',
				data : {
					flag		: 'upd_property',
					calendar_id	: id,
					listing_id		: propid
				},
				success: function(data, textStatus, jqXHR) {
					var serverbox 	= $('#server_results');
					var resposta = $.parseJSON(data);
					if (resposta.ok) {
						console.log("EAFDADAD");
						var msg = '<span class="server_notice"><strong>ID de propriedade do calendário '+$('#autocomplete').val()+' actualizado com sucesso.</strong></span> <br>';
						serverbox.append(msg);																			
										
					} else {
						var msg = '<span class="server_error"><strong>'+resposta.responseText+'</strong></span> <br>';
						serverbox.append(msg);								
					}
					
				},
				error: function(jqXHR, textStatus, errorThrown) {
					//Falhou=/     
					console.log("ERRO");
					alert('Algo correu mal :( ');
				}
			});
		}
		return false;
	});	
		
	$("#manutencao").click(function(event) {
		event.preventDefault();
		
			var serverbox 	= $('#server_results');
			var msg = '<span class="server_request">Manutenção iniciada...</strong></span> <br>';
			serverbox.append(msg);
			
			$.ajax({
				url : "processor.php",
				type: "POST",
				dataType : 'json',
				data : {
					flag		: 'calendar_maintenance'
				},
				success: function(data, textStatus, jqXHR) {
					
					var serverbox 	= $('#server_results');
					var resposta = $.parseJSON(data);
					console.dir(resposta);
					
					if (resposta.ok) {
						console.log("MANUTENÇÃO AOS CALENDARIOS GOOGLE");
						var msg = '<span class="server_notice"><strong>Manutenção concluida.</strong>'+resposta.responseText+'</span> <br>';
						serverbox.append(msg);																	
										
					} else {
						var msg = '<span class="server_error"><strong>Manutenção concluida.</strong>'+resposta.responseText+'</span> <br>';
						serverbox.append(msg);								
					}
					
				},
				error: function(jqXHR, textStatus, errorThrown) {
					//Falhou=/     
					console.log("ERRO");
					alert('Algo correu mal :( ');
				}
			});
		return false;
	});		
	
	$("#correct_mtime").click(function(event) {
		event.preventDefault();
		
			var serverbox 	= $('#server_results');
			var msg = '<span class="server_request">A corrigir mtimes do calendário ...</strong></span> <br>';
			serverbox.append(msg);
			
			$.ajax({
				url : "processor.php",
				type: "POST",
				dataType : 'json',
				data : {
					flag		: 'calendar_mtime_correction',
					calendarID  :  $('#calendar_id_mtime').val()
				},
				success: function(data, textStatus, jqXHR) {
					
					var serverbox 	= $('#server_results');
					var resposta = $.parseJSON(data);
					console.dir(resposta);
					
					if (resposta.ok) {
						console.log("CORRECÇÃO DE MTIMES DE CALENDARIO GOOGLE");
						var msg = '<span class="server_notice"><strong>Correcção concluida.</strong>'+resposta.responseText+'</span> <br>';
						serverbox.append(msg);																	
										
					} else {
						var msg = '<span class="server_error"><strong>Correcção concluida.</strong>'+resposta.responseText+'</span> <br>';
						serverbox.append(msg);								
					}
					
				},
				error: function(jqXHR, textStatus, errorThrown) {
					//Falhou=/     
					console.log("ERRO");
					alert('Algo correu mal :( ');
				}
			});
		return false;
	});			
});