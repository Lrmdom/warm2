<?PHP
/*
# Warmrental Google Sync
# Powered by comPonto.com
# BKP Functions - Backend
*/


require_once 'settings/global.php';
require_once 'settings/config_api.php';

set_include_path('src/' . PATH_SEPARATOR . get_include_path());
require_once 'src/Google/Client.php';
require_once 'src/Google/Service/Calendar.php';

global $client;

$client = new Google_Client();
$client->setApplicationName("warmrental_google_sync");
$client->setClientId($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri($redirect_uri);
$client->addScope("https://www.googleapis.com/auth/calendar");
$client->setAccessType('offline');

//Google Calendar Service start
global $service;
$service = new Google_Service_Calendar($client);


//Inicial ligação à BD
$db_conn = new mysqli($db_host, $db_username, $db_password, $db_database);
//db encoding
$rs0=$db_conn->query("SET NAMES 'utf8' COLLATE 'utf8_unicode_ci'");
header('Content-Type: text/html; charset=utf-8');


//array prettyPrint by Diogo Brito
function pArray($arr, $nome = "Sem nome definido") {
	print("<br/>##################Start#####################");
	print("<br/> Print do Array ".$nome."<br/>");
	print(var_dump($arr));
	print("<br/>###################End######################");
}


function get_db_Calendars () {
	global $db_conn;
	
	$db_Calendars = array();
	
	$sql_cals="SELECT * FROM vrs6_calendars ORDER BY `vrs6_calendars`.`calendar_title` ASC";
	$rs_cals = $db_conn->query($sql_cals);
	
	if($rs_cals === false) {
	  trigger_error('Wrong SQL: ' . $sql_cals . ' Error: ' . $db_conn->error, E_USER_ERROR);
	} else {
	  $rows_Cals = $rs_cals->num_rows;
	}
	
	//Record sets
	$rs_cals->data_seek(0);

	$autocomplete = "";
	while($row = $rs_cals->fetch_assoc()){
		$autocomplete .= "\"".$row['calendar_title']."\",";
		$db_Calendars[$row['calendar_id']]['calendar_id']		= $row['calendar_id'];
		//Fica em UTF-8
		$db_Calendars[$row['calendar_id']]['calendar_title']	= $row['calendar_title'];
		$db_Calendars[$row['calendar_id']]['listing_id']		= $row['listing_id'];		
	}
	$rs_cals->free();
	$_SESSION['autocomplete'] = substr($autocomplete, 0, -1);;
	return $db_Calendars;
}//end get_db_Calendars()

//get events by cal
function get_db_eventsCalID ($cal_id) {
	global $db_conn;
	$events = array();
	
	$sql_evts="SELECT * FROM `vrs6_bookings` WHERE `calendar_id` = '".$cal_id."'";
	$rs_evts = $db_conn->query($sql_evts);
	
	if($rs_evts === false) {
	  trigger_error('Wrong SQL: ' . $sql_evts . ' Error: ' . $db_conn->error, E_USER_ERROR);
	} else {
	  $rows_evts = $rs_evts->num_rows;
	}

	//Record sets \
	$rs_evts->data_seek(0);

	//Record sets 2
	$rs_evts->data_seek(0);
	while($row = $rs_evts->fetch_assoc()){
		$dateSdb 	 = new DateTime($row['start_date']);
		$dateEdb 	 = new DateTime($row['end_date']);
		
		$createddb 	 = new DateTime($row['ctime']);
		$updateddb	 = new DateTime($row['mtime']);
		
		$dateSdb	= $dateSdb->format('Y-m-d H:i:s'); 
		$dateEdb 	= $dateEdb->format('Y-m-d H:i:s'); 
		
		$createddb  = $createddb->format('Y-m-d H:i:s');
		$updateddb  = $updateddb->format('Y-m-d H:i:s');
		
		//Organizar por event_id, no final apenas serão considerados os eventos com flag
		
		$events[$row['event_id']]['booking_id']		= $row['booking_id'];
		$events[$row['event_id']]['event_id']		= $row['event_id'];
		$events[$row['event_id']]['calendar_id']	= $row['calendar_id'];
		$events[$row['event_id']]['listing_id']		= $row['listing_id'];
		$events[$row['event_id']]['event_name']		= $row['event_name'];
		$events[$row['event_id']]['start_date']		= $dateSdb;
		$events[$row['event_id']]['end_date']		= $dateEdb;
		$events[$row['event_id']]['created']		= $row['ctime'];
		$events[$row['event_id']]['updated']		= $row['mtime'];
		$events[$row['event_id']]['user_id']		= $row['user_id'];
		$events[$row['event_id']]['blank_booking']	= $row['blank_booking'];
		$events[$row['event_id']]['booking_status']	= $row['booking_status'];
		$events[$row['event_id']]['admin_status']	= $row['admin_status'];
		
		
		//RESERVAS WARMRENTAL - CONFIRMADAS E PENDENTES
		if ($row['booking_status']==1 and $row['blank_booking']==0) {
			$events[$row['event_id']]['flag']			= 1;
			$events[$row['event_id']]['cancelada']		= 0;
			$events[$row['event_id']]['proprietario']	= 0;
			$events[$row['event_id']]['warmrental']		= 1;
		
		//RESERVAS DO PROPRIETARIO
		} elseif ($row['blank_booking']==1) {
			$events[$row['event_id']]['flag']			= 1;
			$events[$row['event_id']]['cancelada']		= 0;
			$events[$row['event_id']]['proprietario']	= 1;
			$events[$row['event_id']]['warmrental']		= 0;
			
		//RESERVAS CANCELADAS
		} elseif ($row['admin_status']==1 and $row['booking_status']==0) {
			$events[$row['event_id']]['flag']			= 1;
			$events[$row['event_id']]['cancelada']		= 1;
			$events[$row['event_id']]['proprietario']	= 0;
			$events[$row['event_id']]['warmrental']		= 0;
			
		}
	}
	$rs_evts->free();
	return $events;
}//end get_db_eventsCalID


function get_google_Calendars () {
	global $service;
	$google_Calendars = array();
	
	$calendarList = $service->calendarList->listCalendarList();
	
	$i=0;
	while(true) {
	  foreach ($calendarList->getItems() as $calendarListEntry) {
		$google_Calendars[$calendarListEntry->getId()]['calendar_id'] = $calendarListEntry->getId();
		//Fica em UTF-8
		$google_Calendars[$calendarListEntry->getId()]['calendar_title'] = $calendarListEntry->getSummary();
		
	  }
	  $pageToken = $calendarList->getNextPageToken();
	  if ($pageToken) {
		$optParams = array('pageToken' => $pageToken);
		$calendarList = $service->calendarList->listCalendarList($optParams);
	  } else {
		break;
	  }
	  $i++;
	}	
	return $google_Calendars;
}//end get_google_Calendars()

function get_syncCals ($db_Calendars, $google_Calendars) {
	global $db_conn;
	global $db_Calendars;

	$Calendars = array();
	$i=0;
	foreach ($google_Calendars as $row_G) {
		if (isset($db_Calendars[$row_G['calendar_id']])) {
			if (($db_Calendars[$row_G['calendar_id']]['listing_id'])>0) {
				$i++;
				$Calendars[$row_G['calendar_id']]['calendar_id'] 	= $row_G['calendar_id'];
				$Calendars[$row_G['calendar_id']]['listing_id'] 		= $db_Calendars[$row_G['calendar_id']]['listing_id'];
				$Calendars[$row_G['calendar_id']]['calendar_title'] = $row_G['calendar_title'];
			}
		} else {
			$sql_ins = "INSERT INTO vrs6_calendars (`calendar_id`, `calendar_title`, `listing_id`) VALUES ('".$row_G['calendar_id']."', '".$row_G['calendar_title']."', 0)";
			mysqli_query($db_conn, $sql_ins);
			//Actualizar array db calendars
			$db_Calendars = get_db_Calendars();
		}
	}
	
	$_SESSION['num_props'] = $i;
	return $Calendars;
}//end get_syncCals()

function exists_DB ($event_id) {
	global $db_conn;
	$result = array();
	$event_id = mysqli_real_escape_string($db_conn, $event_id);
	
	$resposta['ok']	=  0;
	$sql = "SELECT * FROM vrs6_bookings WHERE booking_status!='0' AND event_id='$event_id'";
	$rs = $db_conn->query($sql);
	
	if($rs === false) {
	  trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $db_conn->error, E_USER_ERROR);
	} else {
	  $rows = $rs->num_rows;
	}
	
	$rs->data_seek(0);

	while($row = $rs->fetch_assoc()){	
		$resposta['bid'] 			= $row['booking_id'];
		$resposta['blank_booking'] 	= $row['blank_booking'];
		$resposta['booking_status'] = $row['booking_status'];
		$resposta['admin_status']	= $row['admin_status'];
		$resposta['start_date'] 	= $row['start_date'];
		$resposta['end_date'] = $row['end_date'];
		$resposta['event_name'] = $row['event_name'];
		$resposta['calendar_id'] = $row['calendar_id'];
		$resposta['listing_id'] = $row['listing_id'];
		$resposta['ok']	 = 1;
	}
	$rs->free();
	
	if ($resposta['ok']) {
		if ($resposta['blank_booking']==0 and $resposta['booking_status']==1) {
			$resposta['warmrental'] = 1;
		} elseif($resposta['blank_booking']==1) {
			$resposta['warmrental'] = 0;
		}
	}
		
	return $resposta;
}


function exists_G ($tot_google, $event_id) {
	
	if (isset($tot_google[$event_id]) and $tot_google[$event_id]['event_id']==$event_id) {
		return true;
	} else {
		return false;
	}
}

function isCanceled_DB ($event_id) {
	global $db_conn;
	$result = array();
	$event_id = mysqli_real_escape_string($db_conn, $event_id);
	
	$sql = "SELECT booking_id FROM vrs6_bookings WHERE booking_status='0' AND event_id='$event_id'";
	$rs = $db_conn->query($sql);
	
	if($rs === false) {
	  trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $db_conn->error, E_USER_ERROR);
	} else {
	  $rows = $rs->num_rows;
	}
	
	if ($rows>0) {
		return true;
	} else {
		return false;
	}
}

function check_canceled ($cal_id) {
	global $db_conn;
	$resposta = array();
	$resposta['cancel_flag']=0;
	
	$sql = "SELECT * FROM vrs6_bookings WHERE booking_status='0' AND end_date>=now() AND calendar_id='$cal_id'";
	$rs = $db_conn->query($sql);
	
	if($rs === false) {
	  trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $db_conn->error, E_USER_ERROR);
	} else {
	  $rows = $rs->num_rows;
	}
	if ($rows>0) {
		$resposta['cancel_flag']=1;
		//Record sets \
		$rs->data_seek(0);
	
		while($row = $rs->fetch_assoc()){	
			$resposta['eventos'][$row['booking_id']] = $row;
		}
		$rs->free();
		
		return $resposta;
	} else {
		
		return $resposta;
	}

}

function get_mod_googleEvents ($cal_id) {
	global $service;
	global $db_conn;
	global $Calendars;
	
	$resposta = array();
	
	//Check Google
	$events = $service->events->listEvents($cal_id);
	while(true) {
	  foreach ($events->getItems() as $event) {
		 
		$hoje = date('Y-m-d', time());
		$hoje = strtotime($hoje);
		  
						
		$upd = $event['updated']; 
		$updated_ts = strtotime("$upd UTC"); 
						
		$ctd = $event['created']; 
		$created_ts = strtotime("$ctd UTC");
		
		
		$created  = date('Y-m-d H:i:s', $created_ts);
		$updated  = date('Y-m-d H:i:s', $updated_ts);
		
		$last_sync_ts= strtotime($_SESSION['last_sync']);
		$evento_inicio=strtotime($event['end']['date']);
		
		$listing_id = get_listing_id($cal_id);
		
		if (($evento_inicio>=$hoje)) {
			
			//Array com todos os eventos google de hoje em diante, tenham ou nao sido actualizados recentemente
			$resposta['tot_google'][$event['id']]['event_id'] 		= $event['id'];
			$resposta['tot_google'][$event['id']]['calendar_id']	= $cal_id;
			$resposta['tot_google'][$event['id']]['created'] 		= $created;
			$resposta['tot_google'][$event['id']]['updated'] 		= $updated;
			if (isset($event['summary']) and $event['summary']!='') {
				$resposta['tot_google'][$event['id']]['event_name']		= $event['summary']; 
			} else {
				$resposta['tot_google'][$event['id']]['event_name']		= "(Sem título - No name)"; 
			}
			$resposta['tot_google'][$event['id']]['start_date']		= $event['start']['date'];
			$resposta['tot_google'][$event['id']]['end_date']		= $event['end']['date']	;
		
			/*
			$sql_ins = "INSERT INTO vrs6_bookings (`user_id`, `listing_id`, `blank_booking`, `booking_status`, `admin_status`,  `start_date`, `end_date`, `event_id`, `calendar_id`, `event_name`) VALUES ('555', '".$Calendars[$cal_id]['listing_id']."', 1, 0, 1, '".$event['start']['date']."', '".$event['end']['date']."', '".$event['id']."', '".$cal_id."', '".$event['summary']."')";
			$ins = insert_db($sql_ins);	
			*/				
			
		}
		
		if (($updated_ts>$last_sync_ts) and ($evento_inicio>=$hoje) ) {
			
			$flag 	= 1;
			
			//Existir na BD tambem implica nao ser reserva cancelada
			$res = exists_DB ($event['id']);
			
			//Os que são eventos warmrental n serão actualizados e serão repostos no googl
			

			if ($res['ok']) {
				
				if (isset($res['warmrental'])and($res['warmrental'])) {
					$start_date_timestamp = strtotime($res['start_date']);
					$new_start_date = date('Y-m-d', $start_date_timestamp);
					
					$end_date_timestamp = strtotime($res['end_date']);
					$new_end_date = date('Y-m-d', $end_date_timestamp);		
									
					$updData['start_date'] = $new_start_date;
					$updData['end_date']   = $new_end_date;
					$updData['summary'] = $res['event_name'];		
					
					$resp 	 = update_google_event($res['calendar_id'], $event['id'], $updData);
				}
				
				$resposta['eventos'][$event['id']]['event_id'] 			= $event['id'];
				$resposta['eventos'][$event['id']]['db_bID'] 			= $res['bid'];
				$resposta['eventos'][$event['id']]['warmrental'] 			= $res['warmrental'];
				$resposta['eventos'][$event['id']]['calendar_id']		= $cal_id;
				$resposta['eventos'][$event['id']]['created'] 			= $created;
				$resposta['eventos'][$event['id']]['updated'] 			= $updated;
				if (isset($event['summary']) and $event['summary']!='') {
					$resposta['eventos'][$event['id']]['event_name']		= $event['summary']; 
				} else {
					$resposta['eventos'][$event['id']]['event_name']		= "(Sem título - No name)"; 
				}
				$resposta['eventos'][$event['id']]['start_date']		= $event['start']['date'];
				$resposta['eventos'][$event['id']]['end_date']			= $event['end']['date']	;
				$resposta['eventos'][$event['id']]['to_update']		= 1	;
				
				
			} else if (!isCanceled_DB ($event['id'])) {
				$resposta['eventos'][$event['id']]['event_id'] 		= $event['id'];
				$resposta['eventos'][$event['id']]['calendar_id']		= $cal_id;
				$resposta['eventos'][$event['id']]['created'] 			= $created;
				$resposta['eventos'][$event['id']]['updated'] 			= $updated;
				if (isset($event['summary']) and $event['summary']!='') {
					$resposta['eventos'][$event['id']]['event_name']		= $event['summary']; 
				} else {
					$resposta['eventos'][$event['id']]['event_name']		= "(Sem título - No name)"; 
				}
				$resposta['eventos'][$event['id']]['start_date']		= $event['start']['date'];
				$resposta['eventos'][$event['id']]['end_date']			= $event['end']['date']	;
				$resposta['eventos'][$event['id']]['to_insert']		= 1	;
				
			} else {
				$resposta['eventos'][$event['id']]['event_id'] 		= $event['id'];
				$resposta['eventos'][$event['id']]['calendar_id']		= $cal_id;
				$resposta['eventos'][$event['id']]['created'] 			= $created;
				$resposta['eventos'][$event['id']]['updated'] 			= $updated;
				if (isset($event['summary']) and $event['summary']!='') {
					$resposta['eventos'][$event['id']]['event_name']		= $event['summary']; 
				} else {
					$resposta['eventos'][$event['id']]['event_name']		= "(Sem título - No name)"; 
				}
				$resposta['eventos'][$event['id']]['start_date']		= $event['start']['date'];
				$resposta['eventos'][$event['id']]['end_date']			= $event['end']['date']	;
				$resposta['eventos'][$event['id']]['to_delete']		= 1	;
				
			}	
			
	/*
	$sql_ins = "INSERT INTO vrs6_bookings (`user_id`, `listing_id`, `blank_booking`, `booking_status`, `admin_status`, `start_date`, `end_date`, `event_id`, `event_name`) VALUES ('555', '".$listing_id."', 1, 0, 1, '".$event['start']['date']."', '".$event['end']['date']."', '".$event['id']."', '".$event['summary']."')";
	
	$ins = insert_db($sql_ins);	
	*/
		}
	  }
	  
	  $pageToken = $events->getNextPageToken();
	  if ($pageToken) {
		$optParams = array('pageToken' => $pageToken);
		$events = $service->events->listEvents($cal_id, $optParams);
	  } else {
		break;
	  }
	  
	} //end while
	$retorno['tot_google'] = array();
	if (isset($resposta['tot_google'])) {
		$retorno['tot_google']	= $resposta['tot_google'];
	}
	if (isset($flag)) {
		$retorno['eventos']		= $resposta['eventos'];
		$retorno['flag']	  = $flag;
	}
	
	return $retorno;
}

function get_mod_dbEvents ($cal_id) {
	global $db_conn;
	$resposta = array();
	$resposta['eventos'] = array();
	
	$listing_id = get_listing_id($cal_id);

	$hoje = date('Y-m-d', time());
	$hoje = strtotime($hoje);
	$hoje = date('Y-m-d H:i:s', $hoje);
	
	$lastSync= $_SESSION['last_sync'];
	$_SESSION['DEBUGGINGLASTSYNC'] = $hoje;
	
	$sql = "SELECT * FROM vrs6_bookings WHERE mtime>='$lastSync' AND end_date>='$hoje' AND booking_status!='0' AND listing_id='$listing_id'";
	
	$eventos = query_db($sql) ;
	if (sizeof($eventos)>0) {
		$resposta['flag'] = 1;
		
		foreach ($eventos as $evento) {
			//RESERVAS PENDENTES E CONFIRMADAS WARMRENTAL
			if ($evento['booking_status']==1 and $evento['blank_booking']==0) {
				$resposta['eventos']["i".$evento['booking_id']] = $evento;
				$resposta['eventos']["i".$evento['booking_id']]['warmrental'] = 1;
			} elseif ($evento['blank_booking']==1) {
				//RESERVAS DE PROPRIETARIO
				$resposta['eventos']["i".$evento['booking_id']] = $evento;
				$resposta['eventos']["i".$evento['booking_id']]['proprietario'] = 1;
			} 
		}
		
		$sql = "SELECT * FROM vrs6_bookings WHERE booking_status!='0' AND listing_id='$listing_id'";
		$eventos = query_db($sql) ;
		foreach ($eventos as $evento) {
			//RESERVAS PENDENTES E CONFIRMADAS WARMRENTAL
			if ($evento['booking_status']==1 and $evento['blank_booking']==0) {
				$resposta['tot_db']["i".$evento['booking_id']] = $evento;
				$resposta['tot_db']["i".$evento['booking_id']]['warmrental'] = 1;
			} elseif ($evento['blank_booking']==1) {
				//RESERVAS DE PROPRIETARIO
				$resposta['tot_db']["i".$evento['booking_id']] = $evento;
				$resposta['tot_db']["i".$evento['booking_id']]['proprietario'] = 1;
			} else {
				$resposta['tot_db']["i".$evento['booking_id']] = $evento;
				$resposta['tot_db']["i".$evento['booking_id']]['nao_reserva'] = 1;
			}
		}
	} else {
		//Não existem eventos a modificar neste calendario
		$sql = "SELECT * FROM vrs6_bookings WHERE booking_status!='0' AND listing_id='$listing_id'";
		
		$eventos = query_db($sql) ;
		foreach ($eventos as $evento) {
			//RESERVAS PENDENTES E CONFIRMADAS WARMRENTAL
			if ($evento['admin_status']==1 and $evento['blank_booking']==0) {
				$resposta['tot_db']["i".$evento['booking_id']] = $evento;
				$resposta['tot_db']["i".$evento['booking_id']]['warmrental'] = 1;
			} elseif ($evento['blank_booking']==1) {
				//RESERVAS DE PROPRIETARIO
				$resposta['tot_db']["i".$evento['booking_id']] = $evento;
				$resposta['tot_db']["i".$evento['booking_id']]['proprietario'] = 1;
			} else {
				$resposta['tot_db']["i".$evento['booking_id']] = $evento;
				$resposta['tot_db']["i".$evento['booking_id']]['nao_reserva'] = 1;
			}
		}
		$resposta['flag'] = 0;
		
	}

	return $resposta;
}

function enviar_email($to, $subject, $html_mail) {

	require_once 'mailer/PHPMailerAutoload.php';
	
	$mail = new PHPMailer;
	
	//$mail->SMTPDebug = 3;                               // Enable verbose debug output
	$mail->CharSet = 'UTF-8';
	//$mail->isSMTP();                                      // Set mailer to use SMTP
	$mail->Host = 'server.warmrental.com';  // Specify main and backup SMTP servers
	$mail->SMTPAuth = true;                               // Enable SMTP authentication
	$mail->Username = 'erros@warmrental.com';                 // SMTP username
	$mail->Password = 'diogobrito';                           // SMTP password
	$mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
	$mail->Port = 465;                                     // TCP port to connect to
	
	$mail->From = "erros@warmrental.com";
	$mail->FromName = "WarmRental.com"; //Antonio Lapa <Antonio.Lapa@rottapharmmadaus.pt>
	$mail->addAddress($to, '');     // Add a recipient
	//$mail->addAddress('ellen@example.com');               // Name is optional
	$mail->addReplyTo("erros@warmrental.com", "WarmRental.com");
	//$mail->addCC('cc@example.com');
	//$mail->addBCC('bcc@example.com');
	
	$mail->isHTML(true);                                  // Set email format to HTML
	
	$mail->Subject = $subject;
	$mail_body    = ('
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>WarmRental | Erro Actualização Calendário | Calendar Updating Problem</title>
		<style type="text/css">
		a:link {
			color: #333;
		}
		a:visited {
			color: #333;
		}
		a:hover {
			color: #39F;
		}
		a:active {
			color: #333;
		}
		</style>
		</head>
		
		<body style="background-color:#231f20;">
		<table style="margin-top: 20px; margin-bottom:20px;" width="600" border="0" cellspacing="0" cellpadding="0" align="center">
			<tr bgcolor="#FFFFFF">
				<td width="600" height="25" style="font-family:Arial, Helvetica, sans-serif; font-size:25px; color:#009; padding-top:10px; padding-left:10px;">
					<table width="550" border="0" cellspacing="0" cellpadding="0" align="left">
						<tr>
							<td>
								<a href="http://warmrental.com/"><img src="http://www.warmrental.com/public/themes/default_theme/images/logo.png" style="" alt="" /></a>
							</td>
							<td>&nbsp;</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr bgcolor="#FFFFFF">
				<td>
					<hr style="color:#1F83B4; border-color: #1F83B4; background-color: #1F83B4; " />
				</td>
			</tr>
			<tr bgcolor="#FFFFFF">
				<td >     
					<table width="600" border="0" cellspacing="10" cellpadding="0">
			
						<tr>
							<td>
							  <h2>Notificação / Notification</h2>
							  <br>'.
							  $html_mail.'
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr bgcolor="#FFFFFF">
				<td>
					<hr style="color:#1F83B4; border-color: #1F83B4; background-color: #1F83B4; " />
				</td>
			</tr>
			 <tr bgcolor="#FFFFFF">
				<td >     
					<table width="600" border="0" cellspacing="10" cellpadding="0">     
						<tr bgcolor="#FFFFFF">
							<td >  
								<table width="600" border="0" cellspacing="0" >
									<td>http://www.warmrental.com</td>
									<td align="right">&nbsp;</td>
								</table>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		</body>
		</html>');
	$mail->Body = $mail_body;
	$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
	
	if(!$mail->send()) {
		return false;
	} else {
		return true;
	}
}

function recover_google_event($cal_id, $event_id) {
	global $service;
	$resposta = array();
	
	
	$event 					 = $service->events->get($cal_id, $event_id);
	
	$resposta 				 = array();
	$resposta['calendar_id'] = $cal_id;
	$resposta['event_id'] 	 = $event->getId();
	
	$event->setStatus('confirmed');

	//Update Sequence
	$event->setSequence($event->getSequence() + 1);
	
	//FIRE UPDATE
	$updatedEvent = $service->events->update($cal_id, $event->getId(), $event);
	
	return $resposta;
}

function google_cancelled_status ($cal_id) {
	global $service;
	$resposta = array();
	
	//Check Google
	$opt = array('showDeleted' => 'true');
	$events = $service->events->listEvents($cal_id, $opt);
	while(true) {
	  foreach ($events->getItems() as $event) {
		 
		$hoje 		= date('Y-m-d', time());
		$hoje 		= strtotime($hoje);
		$upd 		= $event['updated']; 
		$updated_ts = strtotime("$upd UTC"); 
		$ctd 		= $event['created']; 
		$created_ts = strtotime("$ctd UTC");
		$created  	= date('Y-m-d H:i:s', $created_ts);
		$updated  	= date('Y-m-d H:i:s', $updated_ts);
		
		$last_sync_ts= strtotime($_SESSION['last_sync']);
		$evento_inicio=strtotime($event['end']['date']);
		
		
		if (($evento_inicio>=$hoje and $event['status']=="cancelled")) {
			
			//Array com todos os eventos google de hoje em diante, tenham ou nao sido actualizados recentemente
			$resposta[$event['id']]['event_id'] 	= $event['id'];
			$resposta[$event['id']]['calendar_id']	= $cal_id;
			$resposta[$event['id']]['created'] 		= $created;
			$resposta[$event['id']]['updated'] 		= $updated;
			if (isset($event['summary']) and $event['summary']!='') {
				$resposta[$event['id']]['event_name']		= $event['summary']; 
			} else {
				$resposta[$event['id']]['event_name']		= "(Sem título - No name)"; 
			}
			$resposta[$event['id']]['start_date']		= $event['start']['date'];
			$resposta[$event['id']]['end_date']		= $event['end']['date']	;
		
			/*
			$sql_ins = "INSERT INTO vrs6_bookings (`user_id`, `listing_id`, `blank_booking`, `booking_status`, `admin_status`, `start_date`, `end_date`, `event_id`, `calendar_id`, `event_name`) VALUES ('555', '".$Calendars[$cal_id]['listing_id']."', 1, 1, 1, '".$event['start']['date']."', '".$event['end']['date']."', '".$event['id']."', '".$cal_id."', '".$event['summary']."')";
			$ins = insert_db($sql_ins);	
			*/				
			
		}
		
	  }
	  
	  $pageToken = $events->getNextPageToken();
	  if ($pageToken) {
		$optParams = array('pageToken' => $pageToken);
		$events = $service->events->listEvents($cal_id, $optParams);
	  } else {
		break;
	  }
	  
	} //end while
		
	return $resposta;

}

function get_google_deleted($cal_id) {
	global $service;
	global $db_conn;
	$Deleted = array();
	$eventos = array();
	
	$sql = "SELECT * FROM vrs6_bookings WHERE event_id IS NOT NULL AND TRIM(event_id) <> '' AND booking_status!='0' AND (blank_booking=1 OR (blank_booking=0 AND admin_status = 1)) AND end_date >= NOW() AND calendar_id = '".$cal_id."'";
	$eventos = query_db($sql) ;
	
	if (sizeof($eventos)>0)  {
		$Events = array();
		foreach ($eventos as $evento) {
			$Events[$evento['event_id']] = $evento;
		}
		
		$Del = array();
		$Del = google_cancelled_status ($cal_id);
		
		if (sizeof($Del)>0) {
			foreach ($Del as $D) {
				if (isset($Events[$D['event_id']])) {
					$Deleted[$D['event_id']] = $D;
					if ($Events[$D['event_id']]['blank_booking']=="1") {
						$Deleted[$D['event_id']]['proprietario'] = 1;
						$Deleted[$D['event_id']]['email']		 = $Events[$evento['event_id']]['email'];
						$Deleted[$D['event_id']]['listing_id']		 = $Events[$evento['event_id']]['listing_id'];
					} else {
						$Deleted[$D['event_id']]['proprietario'] = 0;
						$Deleted[$D['event_id']]['email']		 = $Events[$evento['event_id']]['email'];
						$Deleted[$D['event_id']]['listing_id']	 = $Events[$evento['event_id']]['listing_id'];
					}
				
				}
			}
		} else {
			return false;
		}
		
	} else {
		return false;	
	}
	return $Deleted;
		
}

function get_mod_googleEvents2 ($cal_id) {
	global $service;
	global $db_conn;
	global $Calendars;
	
	$resposta = array();
	
	//Check Google
	$events = $service->events->listEvents($cal_id);
	while(true) {
	  foreach ($events->getItems() as $event) {
		 
		$hoje = date('Y-m-d', time());
		$hoje = strtotime($hoje);
		  
						
		$upd = $event['updated']; 
		$updated_ts = strtotime("$upd UTC"); 
						
		$ctd = $event['created']; 
		$created_ts = strtotime("$ctd UTC");
		
		
		$created  = date('Y-m-d H:i:s', $created_ts);
		$updated  = date('Y-m-d H:i:s', $updated_ts);
		
		$last_sync_ts= strtotime($_SESSION['last_sync']);
		$evento_inicio=strtotime($event['end']['date']);
		
		$listing_id = get_listing_id($cal_id);
		
		if (($evento_inicio>=$hoje)) {
			
			//Array com todos os eventos google de hoje em diante, tenham ou nao sido actualizados recentemente
			$resposta[$event['id']]['event_id'] 		= $event['id'];
			$resposta[$event['id']]['calendar_id']	= $cal_id;
			$resposta[$event['id']]['created'] 		= $created;
			$resposta[$event['id']]['updated'] 		= $updated;
			if (isset($event['summary']) and $event['summary']!='') {
				$resposta[$event['id']]['event_name']		= $event['summary']; 
			} else {
				$resposta[$event['id']]['event_name']		= "(Sem título - No name)"; 
			}
			$resposta[$event['id']]['start_date']		= $event['start']['date'];
			$resposta[$event['id']]['end_date']		= $event['end']['date']	;
		
		}
	  }
	  
	  $pageToken = $events->getNextPageToken();
	  if ($pageToken) {
		$optParams = array('pageToken' => $pageToken);
		$events = $service->events->listEvents($cal_id, $optParams);
	  } else {
		break;
	  }
	  
	} //end while
	
	return $resposta;
}



function conflicts_db_google ($D, $G_total, $D_total) {
	$DB = $D;
	
	$Google = array();
	
	foreach ($G_total as $goog) {
		$Google[$goog['calendar_id']][$goog['event_id']] = $goog;	
	}
	
	$conflitos = array();
	
	foreach ($D as $key=>$dbEvent) {
		$dbCheck['start']		=	$dbEvent['start_date'];
		$dbCheck['end']			=	$dbEvent['end_date'];
		$dbCheck['self_id']		= 	$dbEvent['event_id'];
		$dbCheck['booking_id']	=   $dbEvent['booking_id'];	
		if (isset($Google[$dbEvent['calendar_id']])) {
			$conflitos["i".$dbEvent['booking_id']] = checkDates ($dbCheck, $Google[$dbEvent['calendar_id']]);
		}
		
	}
	
	return $conflitos;
}

function conflicts_google_db ($G, $G_total, $D_total) {
	
	$Google = array();
	
	foreach ($G_total as $goog) {
		$Google[$goog['calendar_id']][$goog['event_id']] = $goog;	
	}
	
	$conflitos = array();
	
	foreach ($G as $key=>$googleEvent) {
		$googleCheck['start']		=	$googleEvent['start_date'];
		$googleCheck['end']			=	$googleEvent['end_date'];
		$googleCheck['self_id']		= 	$googleEvent['event_id'];	
		
		$conflitos[$key] = checkDatesGoogle ($googleCheck, $Google[$googleEvent['calendar_id']]);
	}
	
	return $conflitos;
}

function check_event_gCal_conflicts ($dbEvent, $gCal) {
	
	$resposta['evento'] = $dbEvent;
	$resposta['gCal']	= $gCal;
	//$resposta['conflitos'] = checkDates($dbEvent
	return $resposta;
}

function checkDates ($date_Check, $Array) {
	
	$resultado['conflicts_free'] = 1;

	$A1			=	$date_Check['start'];
	$A2			=	$date_Check['end'];
	$booking_id =   $date_Check['booking_id'];
	$self_id	= 	$date_Check['self_id'];
	
	$x=0;
	foreach ($Array as $evento) {
		$B1	=	$evento['start_date']; 
		$B2	=	$evento['end_date'];	
		
		if ((isset($evento['event_id']) and $evento['event_id']==$self_id)) {
			continue;
		}
		
		//redundant$cond1 = (strtotime($A1) > strtotime($B1) and strtotime($A2) < strtotime($B2));   //__.-.===.-.__
		//redundant$cond1 = (strtotime($A1) == strtotime($B1) and strtotime($A2) == strtotime($B2)); // __..===..__
		$cond1 = (strtotime($A1) <  strtotime($B1) and strtotime($A2) <= strtotime($B2) and strtotime($A2)>strtotime($B1));
		$cond2 = (strtotime($A1) >= strtotime($B1) and strtotime($A1) <  strtotime($B2) and strtotime($A2)>=strtotime($B2));
		$cond3 = (strtotime($A1) >= strtotime($B1) and strtotime($A1) <  strtotime($B2) and strtotime($A2)<strtotime($B2));
		$cond4 = (strtotime($A1) <  strtotime($B1) and strtotime($A2) >  strtotime($B2));
		
		//$isProprietario = (isset($google_event['proprietario'])and($google_event['proprietario']==1));
		if ( ($cond1 || $cond2  || $cond3 || $cond4 ) == true ) {
			$cond = array();
			
			$cond['cond1'] = $cond1;
			$cond['cond2'] = $cond2;
			$cond['cond3'] = $cond3;
			$cond['cond4'] = $cond4;
			
			//Check if there was ate least one conflict
			$resultado['conflicts_free'] 	= 0;
			$resultado['conflitos'][$x] 	= $evento;
			
			$resultado['conflitos'][$x]['conflict_case'] = $cond;
			
			$resultado['conflitos'][$x]['booking_id'] = $booking_id;
			
			$x++;
			
		}
		
	}
	
	return $resultado;
	
}

function checkDatesGoogle ($date_Check, $Array) {
	
	$resultado['conflicts_free'] = 1;

	$A1			=	$date_Check['start'];
	$A2			=	$date_Check['end'];
	$self_id	= 	$date_Check['self_id'];
	
	$x=0;
	foreach ($Array as $evento) {
		$B1	=	$evento['start_date']; 
		$B2	=	$evento['end_date'];	
		
		if ((isset($evento['event_id']) and $evento['event_id']==$self_id)) {
			continue;
		}
		
		//redundant$cond1 = (strtotime($A1) > strtotime($B1) and strtotime($A2) < strtotime($B2));   //__.-.===.-.__
		//redundant$cond1 = (strtotime($A1) == strtotime($B1) and strtotime($A2) == strtotime($B2)); // __..===..__
		$cond1 = (strtotime($A1) <  strtotime($B1) and strtotime($A2) <= strtotime($B2) and strtotime($A2)>strtotime($B1));
		$cond2 = (strtotime($A1) >= strtotime($B1) and strtotime($A1) <  strtotime($B2) and strtotime($A2)>=strtotime($B2));
		$cond3 = (strtotime($A1) >= strtotime($B1) and strtotime($A1) <  strtotime($B2) and strtotime($A2)<strtotime($B2));
		$cond4 = (strtotime($A1) <  strtotime($B1) and strtotime($A2) >  strtotime($B2));
		
		//$isProprietario = (isset($google_event['proprietario'])and($google_event['proprietario']==1));
		if ( ($cond1 || $cond2  || $cond3 || $cond4 ) == true ) {
			$cond = array();
			
			$cond['cond1'] = $cond1;
			$cond['cond2'] = $cond2;
			$cond['cond3'] = $cond3;
			$cond['cond4'] = $cond4;
			
			//Check if there was ate least one conflict
			$resultado['conflicts_free'] 	= 0;
			$resultado['conflitos'][$x] 	= $evento;
			
			$resultado['conflitos'][$x]['conflict_case'] = $cond;
			//$resultado['conflitos'][$x]['booking_id'] = $booking_id;
			
			$x++;
			
		}
		
	}
	
	return $resultado;
	
}

function insert_db($sql) {
	global $db_conn;
	
	$result= mysqli_query($db_conn, $sql);	
	
	return $result;
}

function update_db($sql) {
	global $db_conn;
	
	$result= mysqli_query($db_conn, $sql);	
	
	return $result;
}

function query_db($sql) {
	global $db_conn;
	$result = array();
	
	
	$rs = $db_conn->query($sql);
	
	if($rs === false) {
	  trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $db_conn->error, E_USER_ERROR);
	} else {
	  $rows = $rs->num_rows;
	}
	
	//Record sets
	$rs->data_seek(0);
	
	$i=0;
	while($row = $rs->fetch_assoc()){
		$result[$i]	= $row;
		$i++;
	}
	$rs->free();
	
	return $result;
}
function get_email_listing($bID) {
	global $db_conn;
	$result = array();
	
	
	$rs = $db_conn->query("SELECT * FROM vrs6_bookings WHERE booking_id=".$bID);
	
	if($rs === false) {
	  trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $db_conn->error, E_USER_ERROR);
	} else {
	  $rows = $rs->num_rows;
	}
	
	//Record sets
	$rs->data_seek(0);
	
	$i=0;
	while($row = $rs->fetch_assoc()){
		$result['listing_id']	= $row['listing_id'];
		$result['email']		= $row['email'];
		$i++;
	}
	$rs->free();
	
	return $result;	
}
function delete_db_calendar($cal_id) {
	global $db_conn;
	$result = array();
	
	$sql = "DELETE FROM `vrs6_calendars` WHERE `calendar_id` = '".$cal_id."'";
	
	$rs = $db_conn->query($sql);
	
	if($rs === false) {
	  trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $db_conn->error, E_USER_ERROR);
	} 
	

	return $rs;
}

function get_listing_id($cal_id) {
	global $Calendars;
	
	return $Calendars[$cal_id]['listing_id'];
	
}

function get_calendar_id($listing_id) {
	global $Calendars;
	$found = 0;
	
	foreach ($Calendars as $cal) {
		if ($cal['listing_id']==$listing_id) {
			$found=1;
			$calendar_id = $cal['calendar_id'];
		}
	}
	
	if ($found) {
		return $calendar_id;
	} 
	
	return 0;
}

function get_booking_id($event_id) {
	global $db_conn;
	$result = array();
	$event_id = mysqli_real_escape_string($db_conn, $event_id);
	
	$sql = "SELECT booking_id FROM vrs6_bookings WHERE event_id='$event_id'";
	$rs = $db_conn->query($sql);
	
	if($rs === false) {
	  trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $db_conn->error, E_USER_ERROR);
	} else {
	  $rows = $rs->num_rows;
	}
	//Record sets
	$rs->data_seek(0);
	
	$i=0;
	while($row = $rs->fetch_assoc()){
		$resultado	= $row['booking_id'];
		$i++;
	}
	$rs->free();
	
	if ($rows>0) {
		return $resultado;
	} else {
		return false;
	}
}

function insert_google_event($cal_id = '', $insertData = array()) {	
	global $service;

	$res 					 = "ERRO Sem dados de entrada suficientes";
	
	if ( strlen($cal_id) == 0 || strlen($insertData['start_date']) == 0 || strlen($insertData['end_date']) == 0 ) return $res;

	if ( strlen($insertData['event_name']) == 0) $insertData['event_name'] = "(Sem título - No name)";
	
	$resposta['event_name'] = $event_name = $insertData['event_name'];
	$resposta['start_date'] = $start_date = $insertData['start_date'];
	$resposta['end_date']   = $end_date = $insertData['end_date'];
	
	$event = new Google_Service_Calendar_Event();
	$event->setSummary($event_name);
	
	$start = new Google_Service_Calendar_EventDateTime();
	
	$start->setDate($start_date);
	
	$event->setStart($start);
	
	$end = new Google_Service_Calendar_EventDateTime();
	
	$end->setDate($end_date);
	
	$event->setEnd($end);
	
	$createdEvent = $service->events->insert($cal_id, $event);
	
	$resposta['new_event_id'] = $createdEvent->getId();
	
	return $resposta;
}

function update_google_event($cal_id = '', $event_id = '', $updateData = array()) {	
	global $service;
	
	$res 					 = "[UGE-01] Sem dados de entrada suficientes";
	
	if (strlen($cal_id) == 0 || strlen($event_id) == 0 || empty($updateData)) return $res;
	
	$event 					 = $service->events->get($cal_id, $event_id);
	
	$resposta 				 = array();
	$resposta['calendar_id'] = $cal_id;
	$resposta['event_id'] 	 = $event->getId();
	
	//Set summary
	if(isset($updateData['summary'])) {
	  $event->setSummary($updateData['summary']);
	  $resposta['new_summary'] = $updateData['summary'];
	}
	
	//Set location
	if(isset($updateData['location'])) {
	  $event->setLocation($updateData['location']);
	  $resposta['new_location'] = $updateData['location'];
	}

	//Set start_date
	if(isset($updateData['start_date'])) { 
		$new_date_start = $event->getStart();
		$new_date_start->setDate($updateData['start_date']); 
        $event->setStart($new_date_start);
	  	$resposta['new_start_date'] = $updateData['start_date'];
	}

	//Set end_date
	if(isset($updateData['end_date'])) { 
		$new_date_end =  $event->getEnd();
		$new_date_end -> setDate($updateData['end_date']); 
        $event->setEnd($new_date_end);
	  	$resposta['new_end_date'] = $updateData['end_date'];
	}
	
	//Update Sequence
	$event->setSequence($event->getSequence() + 1);
	
	//FIRE UPDATE
	$updatedEvent = $service->events->update($cal_id, $event->getId(), $event);
	
	return $resposta;
}

function delete_google_event($cal_id = '', $event_id = '') {	
	global $service;
	
	$service->events->delete($cal_id, $event_id);
	$resposta['calendar_id'] = $cal_id;
	$resposta['event_id']	= $event_id;
	
	return $resposta;
}
function check_dupe_conflict($Evento, $Cal) {
	$self_bid = $Evento['booking_id'];
	$arr_Bids = array();
	$i=0;
	$resposta['ok'] = 0;
	foreach ($Cal as $key=>$Bid) {
		if($key==$self_bid) {
			continue;
		} else {
			if (isset($Cal[$key][$Evento['event_id']])) {
				$resposta['ok'] = 1;
				$arr_Bids[$i] = $key;
				$i++;
			}
		}
	}
	if (isset($arr_Bids)) {
		$resposta['dupe_conflicts'] = $arr_Bids;
	}
	return $resposta;
}

function db_google_merge_delete($Eventos, $Totals){
	$bID = $Eventos['booking_id'];
	unset($Eventos['booking_id']);
	unset($Eventos['merge_dates']);
	
	//Verificar se algum dos eventos a fazer merge esta em conflito com algumas outras datas da base de dados q n sejam as do proprio evento nem as do evento que originou o conflito
	foreach ($Eventos as $evento) {

		$date_Check['start']  		= $evento['start_date'];
		$date_Check['end'] 	  		= $evento['end_date'];
		$date_Check['self_id']		= $Totals['db'][$evento['calendar_id']][$bID]['event_id'];
		$date_Check['booking_id']	= $bID;
		
		$date_conflicts = checkDates($date_Check, $Totals['db'][$evento['calendar_id']]);	
		
		if (!$date_conflicts['ok']) {
			$resposta['to_delete'][$evento['event_id']] = $evento;
			$resposta['to_delete'][$evento['event_id']]['delete_reason'] = "[DGMD-00] Conflito com reserva na base de dados (booking_id: ".$bID.").";
			if (isset($date_conflicts['db_booking_id'])) {
				$bookID = $date_conflicts['db_booking_id'];
				$resposta['to_delete'][$evento['event_id']]['delete_reason'] = "[DGMD-01] Duplo conflito com reservas na base de dados (booking_ids: ".$bookID." e ".$bID.").";
			}
			
			unset($Eventos[$evento['event_id']]);		
		}
	}
	$resposta['to_merge'] = $Eventos;
	
	//$to_Delete[$conf_evt['calendar_id']][$conf_evt['booking_id']][$conf_evt['event_id']] = $conf_evt;
	//$to_Delete[$conf_evt['calendar_id']][$conf_evt['booking_id']][$conf_evt['event_id']]['delete_reason'] = "Conflito com reserva a actualizar (booking_id: ".$conf_evt['booking_id'].").";
	
	return $resposta;
}
?>