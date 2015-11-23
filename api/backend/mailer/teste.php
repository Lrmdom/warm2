<?PHP


	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	
	require_once 'PHPMailerAutoload.php';
	
	$mail = new PHPMailer;
	
	$mail->SMTPDebug = 3;                               // Enable verbose debug output
	$mail->CharSet = 'UTF-8';
	$mail->isSMTP();     
	//$mail->SMTPDebug = 1;                                 // Set mailer to use SMTP
	$mail->SMTPSecure = 'ssl';                          // Enable TLS encryption, `ssl` also accepted
	$mail->Host = 'instant0279.server.com';  				  // Specify main and backup SMTP servers
	$mail->Port = 465;                                     // TCP port to connect to
	$mail->SMTPAuth = true;                               // Enable SMTP authentication
	$mail->Username = 'erros@warmrental.com';                 // SMTP username
	$mail->Password = 'diogobrito';                           // SMTP password
	
	$mail->From = "erros@warmrental.com";
	$mail->FromName = "WarmRental.com"; //Antonio Lapa <Antonio.Lapa@rottapharmmadaus.pt>
	$mail->addAddress('diogobrito@prospectiva.pt', '');     // Add a recipient
	//$mail->addAddress('ellen@example.com');               // Name is optional
	$mail->addReplyTo("erros@warmrental.com", "WarmRental.com");
	//$mail->addCC('cc@example.com');
	//$mail->addBCC('bcc@example.com');
	
	$mail->isHTML(true);                                  // Set email format to HTML
	
	$mail->Subject = "bgcfdsfsd";
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
							  <br>
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
		echo "1";
	} else {
		echo "2";
	}
    ?>